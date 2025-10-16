<?php
/**
 * Endpoint Verification Script
 * Tests all API endpoints to ensure they're working
 */

echo "\n🔍 ADIL CREATOR - ENDPOINT VERIFICATION\n";
echo str_repeat('=', 60) . "\n\n";

// Configuration
$BASE_URL = 'http://localhost:8000'; // Change to your backend URL
$endpoints = [];
$results = [];

// Define all endpoints to test
$publicEndpoints = [
    'API Status' => '/api/test',
    'Blogs List' => '/api/blogs',
    'Portfolio List' => '/api/portfolio',
    'Services List' => '/api/services',
    'Testimonials List' => '/api/testimonials',
    'FAQs List' => '/api/faqs',
    'Homepage Content' => '/api/homepage',
    'Settings' => '/api/settings',
    'Tags' => '/api/tags',
    'Carousel' => '/api/carousel',
    'Pages' => '/api/pages',
];

$adminEndpoints = [
    'Admin Stats' => '/api/admin/stats',
    'Admin Users' => '/api/admin/users',
    'Admin Blogs' => '/api/admin/blogs',
    'Admin API Keys' => '/api/admin/api-keys',
    'Admin Notifications' => '/api/admin/notifications',
    'Admin Activity' => '/api/admin/activity',
    'Admin Audit' => '/api/admin/audit',
];

$aiEndpoints = [
    'AI Chat' => '/api/ai/chat',
];

// Test public endpoints
echo "📋 Testing Public Endpoints...\n";
echo str_repeat('-', 60) . "\n";

foreach ($publicEndpoints as $name => $endpoint) {
    $result = testEndpoint($BASE_URL . $endpoint);
    $status = $result['success'] ? '✅' : '❌';
    $code = $result['code'];
    
    echo sprintf("%-25s %s [%d]\n", $name, $status, $code);
    $results[$name] = $result;
}

echo "\n📋 Testing Admin Endpoints (require auth)...\n";
echo str_repeat('-', 60) . "\n";

foreach ($adminEndpoints as $name => $endpoint) {
    $result = testEndpoint($BASE_URL . $endpoint);
    // 401 is expected without token - that's good!
    $status = ($result['code'] == 401 || $result['code'] == 200) ? '✅' : '❌';
    $code = $result['code'];
    $note = $result['code'] == 401 ? '(Auth required - OK)' : '';
    
    echo sprintf("%-25s %s [%d] %s\n", $name, $status, $code, $note);
    $results[$name] = $result;
}

echo "\n📋 Testing AI Endpoints...\n";
echo str_repeat('-', 60) . "\n";

foreach ($aiEndpoints as $name => $endpoint) {
    $result = testEndpoint($BASE_URL . $endpoint, 'POST');
    // 400 or 405 expected for GET on POST endpoint
    $status = in_array($result['code'], [200, 400, 405]) ? '✅' : '❌';
    $code = $result['code'];
    $note = $result['code'] == 400 ? '(Requires payload - OK)' : '';
    
    echo sprintf("%-25s %s [%d] %s\n", $name, $status, $code, $note);
    $results[$name] = $result;
}

// Summary
echo "\n" . str_repeat('=', 60) . "\n";
echo "📊 SUMMARY\n";
echo str_repeat('=', 60) . "\n";

$total = count($results);
$working = count(array_filter($results, function($r) {
    return $r['success'] || in_array($r['code'], [200, 401, 400, 405]);
}));
$failed = $total - $working;

echo "Total Endpoints: {$total}\n";
echo "Working: {$working} ✅\n";
echo "Failed: {$failed} " . ($failed > 0 ? '❌' : '✅') . "\n";
echo "\nSuccess Rate: " . round(($working / $total) * 100, 1) . "%\n";

if ($failed === 0) {
    echo "\n🎉 ALL ENDPOINTS WORKING! Ready to deploy! 🚀\n";
} else {
    echo "\n⚠️  Some endpoints need attention. Check failed endpoints above.\n";
}

echo "\n";

// Helper function
function testEndpoint($url, $method = 'GET') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => $httpCode == 200,
        'code' => $httpCode,
        'error' => $error,
        'response' => $response
    ];
}
