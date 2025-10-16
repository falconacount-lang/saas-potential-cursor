<?php
/**
 * Live API Endpoint Tester
 * Tests API endpoints using the current server URL
 * Access via: https://adilcreator.com/backend/test-api-live.php
 */

// Set content type to HTML for better display
header('Content-Type: text/html; charset=utf-8');

// Detect the current server URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = $protocol . '://' . $host . $scriptPath;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Endpoint Tester - Adil Creator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-left: 4px solid #667eea;
            margin: 20px;
        }
        
        .info-box strong {
            color: #667eea;
        }
        
        .test-results {
            padding: 20px;
        }
        
        .endpoint {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #ddd;
        }
        
        .endpoint.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .endpoint.warning {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        .endpoint.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .endpoint-name {
            font-weight: bold;
            font-size: 1.1em;
            margin-bottom: 8px;
        }
        
        .endpoint-url {
            font-family: monospace;
            font-size: 0.9em;
            color: #666;
            margin-bottom: 8px;
        }
        
        .endpoint-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .status-success {
            background: #28a745;
            color: white;
        }
        
        .status-warning {
            background: #ffc107;
            color: #333;
        }
        
        .status-error {
            background: #dc3545;
            color: white;
        }
        
        .response-preview {
            margin-top: 10px;
            padding: 10px;
            background: white;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85em;
            max-height: 200px;
            overflow: auto;
        }
        
        .summary {
            margin: 20px;
            padding: 20px;
            background: #e7f3ff;
            border-radius: 8px;
            text-align: center;
        }
        
        .summary h2 {
            color: #0066cc;
            margin-bottom: 10px;
        }
        
        .stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 15px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9em;
            color: #666;
        }
        
        .refresh-btn {
            display: inline-block;
            margin: 20px;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            transition: transform 0.2s;
        }
        
        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 API Endpoint Tester</h1>
            <p>Testing API connectivity on your production server</p>
        </div>
        
        <div class="info-box">
            <strong>Server URL:</strong> <?php echo htmlspecialchars($baseUrl); ?><br>
            <strong>Protocol:</strong> <?php echo strtoupper($protocol); ?><br>
            <strong>Host:</strong> <?php echo htmlspecialchars($host); ?>
        </div>
        
        <div class="test-results">
            <h2 style="margin-bottom: 20px;">🔍 Testing Endpoints...</h2>
            
            <?php
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
                    
                    $response = @file_get_contents($url, false, $context);
                    
                    if ($response !== false) {
                        $data = json_decode($response, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $status = 'success';
                            $statusClass = 'status-success';
                            $statusText = '✅ Working';
                            $successCount++;
                        } else {
                            $status = 'warning';
                            $statusClass = 'status-warning';
                            $statusText = '⚠️ Response not JSON';
                            $warningCount++;
                        }
                    } else {
                        $status = 'error';
                        $statusClass = 'status-error';
                        $statusText = '❌ No response';
                        $errorCount++;
                        $response = 'No response received';
                    }
                } catch (Exception $e) {
                    $status = 'error';
                    $statusClass = 'status-error';
                    $statusText = '❌ Error';
                    $errorCount++;
                    $response = 'Exception: ' . $e->getMessage();
                }
                
                echo '<div class="endpoint ' . $status . '">';
                echo '<div class="endpoint-name">' . htmlspecialchars($name) . '</div>';
                echo '<div class="endpoint-url">' . htmlspecialchars($url) . '</div>';
                echo '<span class="endpoint-status ' . $statusClass . '">' . $statusText . '</span>';
                
                if ($response && strlen($response) > 0) {
                    $preview = strlen($response) > 500 ? substr($response, 0, 500) . '...' : $response;
                    echo '<div class="response-preview">' . htmlspecialchars($preview) . '</div>';
                }
                
                echo '</div>';
            }
            ?>
        </div>
        
        <div class="summary">
            <h2>📊 Test Summary</h2>
            <div class="stats">
                <div class="stat">
                    <span class="stat-number" style="color: #28a745;"><?php echo $successCount; ?></span>
                    <span class="stat-label">Working</span>
                </div>
                <div class="stat">
                    <span class="stat-number" style="color: #ffc107;"><?php echo $warningCount; ?></span>
                    <span class="stat-label">Warnings</span>
                </div>
                <div class="stat">
                    <span class="stat-number" style="color: #dc3545;"><?php echo $errorCount; ?></span>
                    <span class="stat-label">Errors</span>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; padding-bottom: 30px;">
            <a href="?" class="refresh-btn">🔄 Refresh Tests</a>
        </div>
    </div>
</body>
</html>
