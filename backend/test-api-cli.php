#!/usr/bin/env php
<?php
/**
 * CLI API Endpoint Tester
 * Test API endpoints from command line
 * 
 * Usage:
 *   php test-api-cli.php
 *   php test-api-cli.php https://adilcreator.com/backend
 */

// Color codes for CLI output
class Colors {
    const RESET = "\033[0m";
    const RED = "\033[31m";
    const GREEN = "\033[32m";
    const YELLOW = "\033[33m";
    const BLUE = "\033[34m";
    const MAGENTA = "\033[35m";
    const CYAN = "\033[36m";
    const BOLD = "\033[1m";
}

// Get base URL from command line argument or use default
$baseUrl = $argv[1] ?? 'http://localhost:8000';
$baseUrl = rtrim($baseUrl, '/');

echo Colors::BOLD . Colors::CYAN . "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║           🧪 API ENDPOINT TESTER - CLI VERSION            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo Colors::RESET . "\n";

echo Colors::BOLD . "Testing against: " . Colors::MAGENTA . $baseUrl . Colors::RESET . "\n";
echo str_repeat("─", 60) . "\n\n";

// Load environment if available
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
    
    // Use BACKEND_URL from .env if available and no argument provided
    if (!isset($argv[1]) && isset($_ENV['BACKEND_URL'])) {
        $baseUrl = rtrim($_ENV['BACKEND_URL'], '/');
        echo Colors::YELLOW . "ℹ️  Using BACKEND_URL from .env: " . $baseUrl . Colors::RESET . "\n\n";
    }
}

// Test endpoints
$testEndpoints = [
    '/api/test.php' => 'API Test',
    '/api/settings.php' => 'Settings API',
    '/api/blogs.php' => 'Blogs API',
    '/api/portfolio.php' => 'Portfolio API',
    '/api/services.php' => 'Services API',
    '/api/testimonials.php' => 'Testimonials API'
];

$successCount = 0;
$warningCount = 0;
$errorCount = 0;

foreach ($testEndpoints as $endpoint => $name) {
    $url = $baseUrl . $endpoint;
    
    // Print endpoint being tested
    echo Colors::BOLD . "Testing: " . Colors::RESET . $name . "\n";
    echo "  URL: " . Colors::BLUE . $url . Colors::RESET . "\n";
    
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $startTime = microtime(true);
        $response = @file_get_contents($url, false, $context);
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000, 2);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "  " . Colors::GREEN . "✅ Status: Working" . Colors::RESET . "\n";
                echo "  ⏱️  Response time: {$responseTime}ms\n";
                
                // Show some response data
                if (isset($data['success'])) {
                    echo "  📦 Success: " . ($data['success'] ? 'true' : 'false') . "\n";
                }
                if (isset($data['message'])) {
                    echo "  💬 Message: " . substr($data['message'], 0, 50) . "\n";
                }
                if (isset($data['data'])) {
                    $dataCount = is_array($data['data']) ? count($data['data']) : 'N/A';
                    echo "  📊 Data items: {$dataCount}\n";
                }
                
                $successCount++;
            } else {
                echo "  " . Colors::YELLOW . "⚠️  Status: Response not JSON" . Colors::RESET . "\n";
                echo "  ⏱️  Response time: {$responseTime}ms\n";
                echo "  📄 Response preview: " . substr($response, 0, 100) . "...\n";
                $warningCount++;
            }
        } else {
            echo "  " . Colors::RED . "❌ Status: No response" . Colors::RESET . "\n";
            echo "  ⏱️  Response time: {$responseTime}ms\n";
            
            // Check if it's a connection error
            if (!@fsockopen(parse_url($baseUrl, PHP_URL_HOST), 80, $errno, $errstr, 5)) {
                echo "  ⚠️  Connection error: {$errstr} ({$errno})\n";
            }
            
            $errorCount++;
        }
    } catch (Exception $e) {
        echo "  " . Colors::RED . "❌ Error: " . $e->getMessage() . Colors::RESET . "\n";
        $errorCount++;
    }
    
    echo "\n";
}

// Print summary
echo str_repeat("═", 60) . "\n";
echo Colors::BOLD . Colors::CYAN . "📊 TEST SUMMARY\n" . Colors::RESET;
echo str_repeat("─", 60) . "\n";

$total = count($testEndpoints);
echo Colors::GREEN . "✅ Working:  {$successCount}/{$total}\n" . Colors::RESET;
echo Colors::YELLOW . "⚠️  Warnings: {$warningCount}/{$total}\n" . Colors::RESET;
echo Colors::RED . "❌ Errors:   {$errorCount}/{$total}\n" . Colors::RESET;

echo str_repeat("═", 60) . "\n";

// Final status
if ($successCount === $total) {
    echo Colors::BOLD . Colors::GREEN . "\n🎉 SUCCESS! All APIs are working perfectly!\n" . Colors::RESET;
    exit(0);
} else if ($successCount > 0) {
    echo Colors::BOLD . Colors::YELLOW . "\n⚠️  PARTIAL SUCCESS: Some APIs need attention.\n" . Colors::RESET;
    exit(1);
} else {
    echo Colors::BOLD . Colors::RED . "\n❌ FAILURE: No APIs are responding.\n" . Colors::RESET;
    echo "\nTroubleshooting steps:\n";
    echo "1. Check if the backend URL is correct\n";
    echo "2. Verify the server is running and accessible\n";
    echo "3. Check firewall and security settings\n";
    echo "4. Review server error logs\n";
    echo "5. Ensure PHP extensions are installed (pdo, json, etc.)\n\n";
    exit(2);
}
