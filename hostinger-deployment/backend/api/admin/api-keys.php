<?php
/**
 * API Keys Management Endpoint
 * 
 * Handles CRUD operations for third-party API keys
 * Part of Rocket Site Plan - Phase 1: Foundation & API Management
 * 
 * Endpoints:
 * GET    /api/admin/api-keys           - Get all API keys
 * GET    /api/admin/api-keys/{service} - Get specific API key
 * POST   /api/admin/api-keys           - Add/update API key
 * POST   /api/admin/api-keys/{service}/test - Test API key
 * DELETE /api/admin/api-keys/{service} - Delete API key
 * PUT    /api/admin/api-keys/{service}/toggle - Enable/disable API key
 * GET    /api/admin/api-keys/{service}/usage  - Get usage stats
 * PUT    /api/admin/api-keys/{service}/budget - Update budget
 */

header('Content-Type: application/json');

// CORS headers
$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:8080',
    'https://adilcreator.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Auth.php';
require_once __DIR__ . '/../../classes/APIKeyManager.php';
require_once __DIR__ . '/../../classes/APIKeyTester.php';

try {
    // Initialize
    $db = new Database();
    $conn = $db->getConnection();
    $auth = new Auth($conn);
    $apiKeyManager = new APIKeyManager();
    $apiKeyTester = new APIKeyTester();
    
    // Verify authentication and admin role
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader)) {
        throw new Exception('Authorization header missing', 401);
    }
    
    $token = str_replace('Bearer ', '', $authHeader);
    $userData = $auth->validateToken($token);
    
    if (!$userData) {
        throw new Exception('Invalid or expired token', 401);
    }
    
    if ($userData['role'] !== 'admin') {
        throw new Exception('Admin access required', 403);
    }
    
    // Parse request
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['REQUEST_URI'];
    $pathParts = explode('/', trim(parse_url($path, PHP_URL_PATH), '/'));
    
    // Find 'api-keys' index
    $apiKeysIndex = array_search('api-keys', $pathParts);
    if ($apiKeysIndex === false) {
        throw new Exception('Invalid endpoint', 400);
    }
    
    $serviceName = $pathParts[$apiKeysIndex + 1] ?? null;
    $action = $pathParts[$apiKeysIndex + 2] ?? null;
    
    // Get request body for POST/PUT
    $input = null;
    if (in_array($method, ['POST', 'PUT'])) {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE && !empty($rawInput)) {
            throw new Exception('Invalid JSON in request body', 400);
        }
    }
    
    // Route to appropriate handler
    switch ($method) {
        case 'GET':
            if (!$serviceName) {
                // GET /api/admin/api-keys - Get all keys
                $maskKeys = isset($_GET['mask']) && $_GET['mask'] === 'true';
                $includeInactive = !isset($_GET['active_only']) || $_GET['active_only'] !== 'true';
                
                $apiKeys = $apiKeyManager->getAllAPIKeys($includeInactive, $maskKeys);
                
                echo json_encode([
                    'success' => true,
                    'data' => $apiKeys,
                    'count' => count($apiKeys)
                ]);
                
            } elseif ($action === 'usage') {
                // GET /api/admin/api-keys/{service}/usage - Get usage stats
                $year = $_GET['year'] ?? null;
                $month = $_GET['month'] ?? null;
                
                $stats = $apiKeyManager->getUsageStats($serviceName, $year, $month);
                
                if ($stats) {
                    echo json_encode([
                        'success' => true,
                        'data' => $stats
                    ]);
                } else {
                    throw new Exception('Failed to get usage stats', 500);
                }
                
            } elseif ($action === 'logs') {
                // GET /api/admin/api-keys/{service}/logs - Get recent logs
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
                
                $logs = $apiKeyManager->getRecentUsage($serviceName, $limit);
                
                echo json_encode([
                    'success' => true,
                    'data' => $logs,
                    'count' => count($logs)
                ]);
                
            } else {
                // GET /api/admin/api-keys/{service} - Get specific key
                $apiKey = $apiKeyManager->getAPIKey($serviceName, true);
                
                if ($apiKey) {
                    // Mask the key unless explicitly requested
                    if (!isset($_GET['reveal']) || $_GET['reveal'] !== 'true') {
                        if ($apiKey['api_key']) {
                            $apiKey['api_key'] = '••••' . substr($apiKey['api_key'], -4);
                        }
                        if ($apiKey['api_secret']) {
                            $apiKey['api_secret'] = '••••' . substr($apiKey['api_secret'], -4);
                        }
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $apiKey
                    ]);
                } else {
                    throw new Exception('API key not found', 404);
                }
            }
            break;
            
        case 'POST':
            if ($action === 'test') {
                // POST /api/admin/api-keys/{service}/test - Test API key
                $apiKey = $apiKeyManager->getAPIKey($serviceName, true);
                
                if (!$apiKey) {
                    throw new Exception('API key not found', 404);
                }
                
                // Test the API
                $testResult = $apiKeyTester->testAPI(
                    $serviceName,
                    $apiKey['api_key'],
                    $apiKey['api_secret'],
                    $apiKey['additional_config']
                );
                
                // Update status in database
                $apiKeyManager->updateAPIKeyStatus(
                    $serviceName,
                    $testResult['status'],
                    $testResult['message'],
                    $testResult['response_time']
                );
                
                echo json_encode([
                    'success' => $testResult['status'] === 'active',
                    'data' => $testResult
                ]);
                
            } else {
                // POST /api/admin/api-keys - Add/update API key
                if (!$input || !isset($input['service_name']) || !isset($input['api_key'])) {
                    throw new Exception('Missing required fields: service_name, api_key', 400);
                }
                
                $result = $apiKeyManager->saveAPIKey(
                    $input['service_name'],
                    $input['display_name'] ?? $input['service_name'],
                    $input['api_key'],
                    $input['api_secret'] ?? null,
                    $input['webhook_url'] ?? null,
                    $input['additional_config'] ?? null
                );
                
                if ($result['success']) {
                    // Optionally auto-test after saving
                    if (isset($input['auto_test']) && $input['auto_test']) {
                        $apiKey = $apiKeyManager->getAPIKey($input['service_name'], true);
                        $testResult = $apiKeyTester->testAPI(
                            $input['service_name'],
                            $apiKey['api_key'],
                            $apiKey['api_secret'],
                            $apiKey['additional_config']
                        );
                        
                        $apiKeyManager->updateAPIKeyStatus(
                            $input['service_name'],
                            $testResult['status'],
                            $testResult['message'],
                            $testResult['response_time']
                        );
                        
                        $result['test_result'] = $testResult;
                    }
                }
                
                echo json_encode($result);
            }
            break;
            
        case 'PUT':
            if ($action === 'toggle') {
                // PUT /api/admin/api-keys/{service}/toggle - Enable/disable
                if (!$serviceName) {
                    throw new Exception('Service name required', 400);
                }
                
                if (!isset($input['enabled'])) {
                    throw new Exception('enabled field required', 400);
                }
                
                $result = $apiKeyManager->toggleAPIKey($serviceName, $input['enabled']);
                echo json_encode($result);
                
            } elseif ($action === 'budget') {
                // PUT /api/admin/api-keys/{service}/budget - Update budget
                if (!$serviceName) {
                    throw new Exception('Service name required', 400);
                }
                
                if (!isset($input['budget_limit'])) {
                    throw new Exception('budget_limit field required', 400);
                }
                
                $result = $apiKeyManager->updateBudgetLimit(
                    $serviceName,
                    $input['budget_limit'],
                    $input['year'] ?? null,
                    $input['month'] ?? null
                );
                
                echo json_encode($result);
                
            } else {
                // PUT /api/admin/api-keys/{service} - Update API key
                if (!$serviceName) {
                    throw new Exception('Service name required', 400);
                }
                
                $result = $apiKeyManager->saveAPIKey(
                    $serviceName,
                    $input['display_name'] ?? $serviceName,
                    $input['api_key'] ?? null,
                    $input['api_secret'] ?? null,
                    $input['webhook_url'] ?? null,
                    $input['additional_config'] ?? null
                );
                
                echo json_encode($result);
            }
            break;
            
        case 'DELETE':
            // DELETE /api/admin/api-keys/{service} - Delete API key
            if (!$serviceName) {
                throw new Exception('Service name required', 400);
            }
            
            $result = $apiKeyManager->deleteAPIKey($serviceName);
            echo json_encode($result);
            break;
            
        default:
            throw new Exception('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    $statusCode = $e->getCode() ?: 500;
    if ($statusCode < 100 || $statusCode > 599) {
        $statusCode = 500;
    }
    
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => $statusCode
    ]);
}
