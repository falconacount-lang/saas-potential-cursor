<?php
/**
 * API Key Manager
 * 
 * Manages external API keys with encryption, testing, and usage tracking
 * Part of Rocket Site Plan - Phase 1: Foundation & API Management
 * 
 * Features:
 * - Secure encryption/decryption of API keys
 * - API key validation and testing
 * - Usage tracking and cost monitoring
 * - Rate limiting
 * - Budget management
 */

require_once __DIR__ . '/../config/database.php';

class APIKeyManager {
    private $db;
    private $conn;
    private $encryptionKey;
    private $encryptionMethod = 'AES-256-CBC';
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->initializeEncryptionKey();
    }
    
    /**
     * Initialize or retrieve encryption key from settings
     */
    private function initializeEncryptionKey() {
        // Try to get from environment first
        if (!empty($_ENV['API_ENCRYPTION_KEY'])) {
            $this->encryptionKey = $_ENV['API_ENCRYPTION_KEY'];
            return;
        }
        
        // Try to get from database
        try {
            $stmt = $this->conn->prepare("
                SELECT setting_value FROM system_settings 
                WHERE setting_key = 'api_encryption_key' 
                LIMIT 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['setting_value'])) {
                $this->encryptionKey = $result['setting_value'];
            } else {
                // Generate new encryption key
                $this->encryptionKey = $this->generateEncryptionKey();
                $this->saveEncryptionKey($this->encryptionKey);
            }
        } catch (PDOException $e) {
            // Fallback to temporary key if database not ready
            $this->encryptionKey = hash('sha256', 'TEMP_KEY_' . $_ENV['JWT_SECRET'] ?? 'fallback_key');
        }
    }
    
    /**
     * Generate a secure encryption key
     */
    private function generateEncryptionKey() {
        return base64_encode(random_bytes(32));
    }
    
    /**
     * Save encryption key to database
     */
    private function saveEncryptionKey($key) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, is_encrypted)
                VALUES ('api_encryption_key', ?, 'string', 'security', 'Encryption key for API credentials', 1)
                ON CONFLICT(setting_key) DO UPDATE SET setting_value = ?
            ");
            $stmt->execute([$key, $key]);
        } catch (PDOException $e) {
            error_log("Failed to save encryption key: " . $e->getMessage());
        }
    }
    
    /**
     * Encrypt sensitive data
     */
    private function encrypt($data) {
        if (empty($data)) return null;
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $data,
            $this->encryptionMethod,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Combine IV and encrypted data
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    private function decrypt($data) {
        if (empty($data)) return null;
        
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt(
            $encrypted,
            $this->encryptionMethod,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
    
    /**
     * Add or update an API key
     */
    public function saveAPIKey($serviceName, $displayName, $apiKey, $apiSecret = null, $webhookUrl = null, $additionalConfig = null) {
        try {
            // Encrypt sensitive data
            $encryptedKey = $this->encrypt($apiKey);
            $encryptedSecret = $apiSecret ? $this->encrypt($apiSecret) : null;
            
            // Check if API key exists
            $stmt = $this->conn->prepare("
                SELECT id FROM api_keys WHERE service_name = ?
            ");
            $stmt->execute([$serviceName]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing
                $stmt = $this->conn->prepare("
                    UPDATE api_keys 
                    SET display_name = ?,
                        api_key = ?,
                        api_secret = ?,
                        webhook_url = ?,
                        additional_config = ?,
                        status = 'testing',
                        updated_at = datetime('now')
                    WHERE service_name = ?
                ");
                $success = $stmt->execute([
                    $displayName,
                    $encryptedKey,
                    $encryptedSecret,
                    $webhookUrl,
                    $additionalConfig ? json_encode($additionalConfig) : null,
                    $serviceName
                ]);
            } else {
                // Insert new
                $stmt = $this->conn->prepare("
                    INSERT INTO api_keys (
                        service_name, display_name, api_key, api_secret, 
                        webhook_url, additional_config, status
                    ) VALUES (?, ?, ?, ?, ?, ?, 'testing')
                ");
                $success = $stmt->execute([
                    $serviceName,
                    $displayName,
                    $encryptedKey,
                    $encryptedSecret,
                    $webhookUrl,
                    $additionalConfig ? json_encode($additionalConfig) : null
                ]);
            }
            
            return [
                'success' => $success,
                'message' => $success ? 'API key saved successfully' : 'Failed to save API key'
            ];
            
        } catch (PDOException $e) {
            error_log("APIKeyManager::saveAPIKey error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get an API key (decrypted)
     */
    public function getAPIKey($serviceName, $decrypt = true) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM api_keys WHERE service_name = ?
            ");
            $stmt->execute([$serviceName]);
            $apiKey = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$apiKey) {
                return null;
            }
            
            // Decrypt sensitive fields if requested
            if ($decrypt) {
                $apiKey['api_key'] = $this->decrypt($apiKey['api_key']);
                if ($apiKey['api_secret']) {
                    $apiKey['api_secret'] = $this->decrypt($apiKey['api_secret']);
                }
            }
            
            // Parse JSON fields
            if ($apiKey['additional_config']) {
                $apiKey['additional_config'] = json_decode($apiKey['additional_config'], true);
            }
            
            return $apiKey;
            
        } catch (PDOException $e) {
            error_log("APIKeyManager::getAPIKey error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all API keys
     */
    public function getAllAPIKeys($includeInactive = true, $maskKeys = false) {
        try {
            $sql = "SELECT * FROM vw_api_keys_overview ORDER BY display_name";
            if (!$includeInactive) {
                $sql = "SELECT * FROM vw_api_keys_overview WHERE is_enabled = 1 ORDER BY display_name";
            }
            
            $stmt = $this->conn->query($sql);
            $apiKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decrypt and optionally mask API keys
            foreach ($apiKeys as &$key) {
                $decrypted = $this->decrypt($key['api_key'] ?? '');
                if ($maskKeys && $decrypted) {
                    // Mask key (show only last 4 characters)
                    $key['api_key'] = '••••' . substr($decrypted, -4);
                } else {
                    $key['api_key'] = $decrypted;
                }
            }
            
            return $apiKeys;
            
        } catch (PDOException $e) {
            error_log("APIKeyManager::getAllAPIKeys error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete an API key
     */
    public function deleteAPIKey($serviceName) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM api_keys WHERE service_name = ?
            ");
            $success = $stmt->execute([$serviceName]);
            
            return [
                'success' => $success,
                'message' => $success ? 'API key deleted successfully' : 'Failed to delete API key'
            ];
            
        } catch (PDOException $e) {
            error_log("APIKeyManager::deleteAPIKey error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update API key status
     */
    public function updateAPIKeyStatus($serviceName, $status, $testResult = null, $responseTime = null) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE api_keys 
                SET status = ?,
                    test_result = ?,
                    test_response_time_ms = ?,
                    last_tested = datetime('now'),
                    updated_at = datetime('now')
                WHERE service_name = ?
            ");
            $success = $stmt->execute([
                $status,
                $testResult,
                $responseTime,
                $serviceName
            ]);
            
            return $success;
            
        } catch (PDOException $e) {
            error_log("APIKeyManager::updateAPIKeyStatus error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enable or disable an API key
     */
    public function toggleAPIKey($serviceName, $enabled) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE api_keys 
                SET is_enabled = ?,
                    updated_at = datetime('now')
                WHERE service_name = ?
            ");
            $success = $stmt->execute([$enabled ? 1 : 0, $serviceName]);
            
            return [
                'success' => $success,
                'message' => $success ? 'API key ' . ($enabled ? 'enabled' : 'disabled') : 'Failed to update API key'
            ];
            
        } catch (PDOException $e) {
            error_log("APIKeyManager::toggleAPIKey error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Log API usage
     */
    public function logAPIUsage($serviceName, $operation, $requestData = null, $responseData = null, 
                                 $statusCode = 200, $responseTime = 0, $success = true, 
                                 $errorMessage = null, $tokensUsed = 0, $cost = 0, $userId = null) {
        try {
            // Get API key ID
            $stmt = $this->conn->prepare("SELECT id FROM api_keys WHERE service_name = ?");
            $stmt->execute([$serviceName]);
            $apiKey = $stmt->fetch(PDO::FETCH_ASSOC);
            $apiKeyId = $apiKey ? $apiKey['id'] : null;
            
            // Log the usage
            $stmt = $this->conn->prepare("
                INSERT INTO api_usage_logs (
                    api_key_id, service_name, operation, request_data, response_data,
                    status_code, response_time_ms, success, error_message, tokens_used,
                    cost, user_id, ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $apiKeyId,
                $serviceName,
                $operation,
                $requestData ? json_encode($requestData) : null,
                $responseData ? json_encode($responseData) : null,
                $statusCode,
                $responseTime,
                $success ? 1 : 0,
                $errorMessage,
                $tokensUsed,
                $cost,
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("APIKeyManager::logAPIUsage error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get usage statistics for a service
     */
    public function getUsageStats($serviceName, $year = null, $month = null) {
        try {
            $year = $year ?? date('Y');
            $month = $month ?? date('m');
            
            $stmt = $this->conn->prepare("
                SELECT * FROM api_cost_tracking
                WHERE service_name = ? AND year = ? AND month = ?
            ");
            $stmt->execute([$serviceName, $year, $month]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$stats) {
                // Create new entry if doesn't exist
                $stmt = $this->conn->prepare("
                    INSERT INTO api_cost_tracking (service_name, year, month, budget_limit)
                    VALUES (?, ?, ?, 50.00)
                ");
                $stmt->execute([$serviceName, $year, $month]);
                
                return [
                    'service_name' => $serviceName,
                    'year' => $year,
                    'month' => $month,
                    'budget_limit' => 50.00,
                    'current_spend' => 0,
                    'total_requests' => 0,
                    'successful_requests' => 0,
                    'failed_requests' => 0,
                    'budget_used_percent' => 0
                ];
            }
            
            $stats['budget_used_percent'] = $stats['budget_limit'] > 0 
                ? round(($stats['current_spend'] / $stats['budget_limit']) * 100, 2)
                : 0;
                
            return $stats;
            
        } catch (PDOException $e) {
            error_log("APIKeyManager::getUsageStats error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if service is within budget
     */
    public function isWithinBudget($serviceName, $additionalCost = 0) {
        $stats = $this->getUsageStats($serviceName);
        if (!$stats) return false;
        
        $projectedSpend = $stats['current_spend'] + $additionalCost;
        return $projectedSpend <= $stats['budget_limit'];
    }
    
    /**
     * Update budget limit for a service
     */
    public function updateBudgetLimit($serviceName, $budgetLimit, $year = null, $month = null) {
        try {
            $year = $year ?? date('Y');
            $month = $month ?? date('m');
            
            $stmt = $this->conn->prepare("
                INSERT INTO api_cost_tracking (service_name, year, month, budget_limit)
                VALUES (?, ?, ?, ?)
                ON CONFLICT(service_name, year, month) DO UPDATE SET budget_limit = ?
            ");
            $success = $stmt->execute([$serviceName, $year, $month, $budgetLimit, $budgetLimit]);
            
            return [
                'success' => $success,
                'message' => $success ? 'Budget limit updated' : 'Failed to update budget'
            ];
            
        } catch (PDOException $e) {
            error_log("APIKeyManager::updateBudgetLimit error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get recent API usage logs
     */
    public function getRecentUsage($serviceName = null, $limit = 100) {
        try {
            if ($serviceName) {
                $stmt = $this->conn->prepare("
                    SELECT * FROM vw_recent_api_usage 
                    WHERE service_name = ?
                    LIMIT ?
                ");
                $stmt->execute([$serviceName, $limit]);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT * FROM vw_recent_api_usage 
                    LIMIT ?
                ");
                $stmt->execute([$limit]);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("APIKeyManager::getRecentUsage error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get API key for a specific service (decrypted, for internal use)
     */
    public function getServiceAPIKey($serviceName) {
        $apiKey = $this->getAPIKey($serviceName, true);
        
        if (!$apiKey || !$apiKey['is_enabled'] || $apiKey['status'] !== 'active') {
            return null;
        }
        
        return $apiKey['api_key'];
    }
}
