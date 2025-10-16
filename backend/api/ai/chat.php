<?php
/**
 * AI Chat API Endpoint
 * 
 * Handles real-time chat with AI assistant
 * Part of Rocket Site Plan - Phase 2: AI Content Generation
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

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/OpenAIIntegration.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['message']) || empty(trim($input['message']))) {
        throw new Exception('Message is required', 400);
    }
    
    $message = trim($input['message']);
    $sessionId = $input['session_id'] ?? 'session_' . time();
    $context = $input['context'] ?? [];
    
    // Initialize OpenAI
    $openai = new OpenAIIntegration();
    
    // Generate response
    $result = $openai->generateSupportResponse($message, $context);
    
    if ($result['success']) {
        // Log chat session
        $db = new Database();
        $conn = $db->getConnection();
        
        try {
            // Save chat session if doesn't exist
            $stmt = $conn->prepare("
                INSERT OR IGNORE INTO ai_chat_sessions (session_id, visitor_id, status, created_at)
                VALUES (?, ?, 'active', datetime('now'))
            ");
            $stmt->execute([$sessionId, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            
            // Save messages
            $stmt = $conn->prepare("
                INSERT INTO ai_chat_messages (session_id, message_type, message_content, created_at)
                VALUES (?, 'user', ?, datetime('now'))
            ");
            $stmt->execute([$sessionId, $message]);
            
            $stmt = $conn->prepare("
                INSERT INTO ai_chat_messages (session_id, message_type, message_content, created_at)
                VALUES (?, 'ai', ?, datetime('now'))
            ");
            $stmt->execute([$sessionId, $result['data']['response']]);
            
            // Update session
            $stmt = $conn->prepare("
                UPDATE ai_chat_sessions 
                SET total_messages = total_messages + 2,
                    ai_responses = ai_responses + 1,
                    updated_at = datetime('now')
                WHERE session_id = ?
            ");
            $stmt->execute([$sessionId]);
            
        } catch (PDOException $e) {
            // Log error but don't fail the request
            error_log('Chat logging error: ' . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'response' => $result['data']['response'],
                'suggested_actions' => $result['data']['suggested_actions'] ?? [],
                'session_id' => $sessionId
            ]
        ]);
    } else {
        throw new Exception($result['error'] ?? 'Failed to generate response', 500);
    }
    
} catch (Exception $e) {
    $statusCode = $e->getCode() ?: 500;
    if ($statusCode < 100 || $statusCode > 599) {
        $statusCode = 500;
    }
    
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
