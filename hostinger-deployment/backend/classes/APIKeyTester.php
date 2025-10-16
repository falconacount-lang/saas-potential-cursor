<?php
/**
 * API Key Tester
 * 
 * Tests API connections for various third-party services
 * Part of Rocket Site Plan - Phase 1: Foundation & API Management
 * 
 * Supported Services:
 * - OpenAI (GPT-4, ChatGPT)
 * - LinkedIn API
 * - Twitter/X API
 * - Facebook/Meta API
 * - SendGrid Email
 * - Stripe Payments
 * - Google Analytics
 * - Hunter.io
 * - Clearbit
 * - And more...
 */

class APIKeyTester {
    private $timeout = 10; // seconds
    private $testResults = [];
    
    /**
     * Test an API key for a specific service
     */
    public function testAPI($serviceName, $apiKey, $apiSecret = null, $additionalConfig = null) {
        $startTime = microtime(true);
        
        try {
            switch (strtolower($serviceName)) {
                case 'openai':
                    return $this->testOpenAI($apiKey, $startTime);
                    
                case 'linkedin':
                    return $this->testLinkedIn($apiKey, $startTime);
                    
                case 'twitter':
                case 'x':
                    return $this->testTwitter($apiKey, $apiSecret, $additionalConfig, $startTime);
                    
                case 'facebook':
                case 'meta':
                    return $this->testFacebook($apiKey, $startTime);
                    
                case 'sendgrid':
                    return $this->testSendGrid($apiKey, $startTime);
                    
                case 'stripe':
                    return $this->testStripe($apiKey, $startTime);
                    
                case 'google_analytics':
                    return $this->testGoogleAnalytics($apiKey, $startTime);
                    
                case 'hunter':
                    return $this->testHunter($apiKey, $startTime);
                    
                case 'clearbit':
                    return $this->testClearbit($apiKey, $startTime);
                    
                case 'apollo':
                    return $this->testApollo($apiKey, $startTime);
                    
                case 'mailchimp':
                    return $this->testMailchimp($apiKey, $startTime);
                    
                case 'paypal':
                    return $this->testPayPal($apiKey, $apiSecret, $startTime);
                    
                case 'wordpress':
                    return $this->testWordPress($apiKey, $additionalConfig, $startTime);
                    
                default:
                    return $this->testGenericAPI($serviceName, $apiKey, $additionalConfig, $startTime);
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => null
            ];
        }
    }
    
    /**
     * Test OpenAI API
     */
    private function testOpenAI($apiKey, $startTime) {
        $response = $this->makeRequest(
            'https://api.openai.com/v1/models',
            'GET',
            null,
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]
        );
        
        if ($response['status_code'] === 200) {
            $data = json_decode($response['body'], true);
            return [
                'status' => 'active',
                'message' => 'OpenAI API connected successfully',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'models_available' => count($data['data'] ?? []),
                    'sample_models' => array_slice(array_column($data['data'] ?? [], 'id'), 0, 3)
                ]
            ];
        } else {
            throw new Exception("OpenAI API test failed: HTTP {$response['status_code']} - {$response['body']}");
        }
    }
    
    /**
     * Test LinkedIn API
     */
    private function testLinkedIn($apiKey, $startTime) {
        $response = $this->makeRequest(
            'https://api.linkedin.com/v2/me',
            'GET',
            null,
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'X-Restli-Protocol-Version: 2.0.0'
            ]
        );
        
        if ($response['status_code'] === 200) {
            $data = json_decode($response['body'], true);
            return [
                'status' => 'active',
                'message' => 'LinkedIn API connected successfully',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'profile_id' => $data['id'] ?? 'N/A',
                    'first_name' => $data['localizedFirstName'] ?? 'N/A'
                ]
            ];
        } else {
            throw new Exception("LinkedIn API test failed: HTTP {$response['status_code']}");
        }
    }
    
    /**
     * Test Twitter/X API
     */
    private function testTwitter($apiKey, $apiSecret, $config, $startTime) {
        // Twitter uses OAuth 1.0a or 2.0 - this is a simplified test
        $response = $this->makeRequest(
            'https://api.twitter.com/2/users/me',
            'GET',
            null,
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]
        );
        
        if ($response['status_code'] === 200) {
            $data = json_decode($response['body'], true);
            return [
                'status' => 'active',
                'message' => 'Twitter/X API connected successfully',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'username' => $data['data']['username'] ?? 'N/A'
                ]
            ];
        } else {
            throw new Exception("Twitter API test failed: HTTP {$response['status_code']}");
        }
    }
    
    /**
     * Test Facebook/Meta API
     */
    private function testFacebook($apiKey, $startTime) {
        $response = $this->makeRequest(
            'https://graph.facebook.com/v18.0/me?access_token=' . $apiKey,
            'GET'
        );
        
        if ($response['status_code'] === 200) {
            $data = json_decode($response['body'], true);
            return [
                'status' => 'active',
                'message' => 'Facebook API connected successfully',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'user_id' => $data['id'] ?? 'N/A',
                    'name' => $data['name'] ?? 'N/A'
                ]
            ];
        } else {
            throw new Exception("Facebook API test failed: HTTP {$response['status_code']}");
        }
    }
    
    /**
     * Test SendGrid API
     */
    private function testSendGrid($apiKey, $startTime) {
        $response = $this->makeRequest(
            'https://api.sendgrid.com/v3/scopes',
            'GET',
            null,
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]
        );
        
        if ($response['status_code'] === 200) {
            $data = json_decode($response['body'], true);
            return [
                'status' => 'active',
                'message' => 'SendGrid API connected successfully',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'scopes_count' => count($data['scopes'] ?? []),
                    'has_mail_send' => in_array('mail.send', $data['scopes'] ?? [])
                ]
            ];
        } else {
            throw new Exception("SendGrid API test failed: HTTP {$response['status_code']}");
        }
    }
    
    /**
     * Test Stripe API
     */
    private function testStripe($apiKey, $startTime) {
        $response = $this->makeRequest(
            'https://api.stripe.com/v1/balance',
            'GET',
            null,
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]
        );
        
        if ($response['status_code'] === 200) {
            $data = json_decode($response['body'], true);
            return [
                'status' => 'active',
                'message' => 'Stripe API connected successfully',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'account_active' => true,
                    'available_balance' => ($data['available'][0]['amount'] ?? 0) / 100,
                    'currency' => $data['available'][0]['currency'] ?? 'usd'
                ]
            ];
        } else {
            throw new Exception("Stripe API test failed: HTTP {$response['status_code']}");
        }
    }
    
    /**
     * Test Google Analytics API
     */
    private function testGoogleAnalytics($apiKey, $startTime) {
        // Note: Google Analytics typically uses OAuth2, this is simplified
        $response = $this->makeRequest(
            'https://analyticsreporting.googleapis.com/v4/reports:batchGet',
            'GET',
            null,
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]
        );
        
        // Even a 400 with proper error structure confirms API key format is correct
        if ($response['status_code'] === 200 || $response['status_code'] === 400) {
            return [
                'status' => 'active',
                'message' => 'Google Analytics API key format validated',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'note' => 'Full validation requires property configuration'
                ]
            ];
        } else {
            throw new Exception("Google Analytics API test failed: HTTP {$response['status_code']}");
        }
    }
    
    /**
     * Test Hunter.io API
     */
    private function testHunter($apiKey, $startTime) {
        $response = $this->makeRequest(
            'https://api.hunter.io/v2/account?api_key=' . $apiKey,
            'GET'
        );
        
        if ($response['status_code'] === 200) {
            $data = json_decode($response['body'], true);
            return [
                'status' => 'active',
                'message' => 'Hunter.io API connected successfully',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'email' => $data['data']['email'] ?? 'N/A',
                    'requests_available' => $data['data']['requests']['available'] ?? 0,
                    'plan' => $data['data']['plan_name'] ?? 'N/A'
                ]
            ];
        } else {
            throw new Exception("Hunter.io API test failed: HTTP {$response['status_code']}");
        }
    }
    
    /**
     * Test Clearbit API
     */
    private function testClearbit($apiKey, $startTime) {
        $response = $this->makeRequest(
            'https://company.clearbit.com/v2/companies/find?domain=clearbit.com',
            'GET',
            null,
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]
        );
        
        if ($response['status_code'] === 200) {
            $data = json_decode($response['body'], true);
            return [
                'status' => 'active',
                'message' => 'Clearbit API connected successfully',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'test_query' => 'Company lookup successful'
                ]
            ];
        } else {
            throw new Exception("Clearbit API test failed: HTTP {$response['status_code']}");
        }
    }
    
    /**
     * Test Apollo.io API
     */
    private function testApollo($apiKey, $startTime) {
        $response = $this->makeRequest(
            'https://api.apollo.io/v1/auth/health',
            'GET',
            null,
            [
                'X-Api-Key: ' . $apiKey,
                'Content-Type: application/json'
            ]
        );
        
        if ($response['status_code'] === 200) {
            return [
                'status' => 'active',
                'message' => 'Apollo.io API connected successfully',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'api_health' => 'OK'
                ]
            ];
        } else {
            throw new Exception("Apollo.io API test failed: HTTP {$response['status_code']}");
        }
    }
    
    /**
     * Test Mailchimp API
     */
    private function testMailchimp($apiKey, $startTime) {
        // Extract datacenter from API key (format: key-dc)
        $parts = explode('-', $apiKey);
        $datacenter = end($parts);
        
        $response = $this->makeRequest(
            "https://{$datacenter}.api.mailchimp.com/3.0/",
            'GET',
            null,
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]
        );
        
        if ($response['status_code'] === 200) {
            $data = json_decode($response['body'], true);
            return [
                'status' => 'active',
                'message' => 'Mailchimp API connected successfully',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'account_name' => $data['account_name'] ?? 'N/A',
                    'datacenter' => $datacenter
                ]
            ];
        } else {
            throw new Exception("Mailchimp API test failed: HTTP {$response['status_code']}");
        }
    }
    
    /**
     * Test PayPal API
     */
    private function testPayPal($apiKey, $apiSecret, $startTime) {
        // PayPal uses OAuth - get access token first
        $authResponse = $this->makeRequest(
            'https://api-m.paypal.com/v1/oauth2/token',
            'POST',
            'grant_type=client_credentials',
            [
                'Authorization: Basic ' . base64_encode($apiKey . ':' . $apiSecret),
                'Content-Type: application/x-www-form-urlencoded'
            ]
        );
        
        if ($authResponse['status_code'] === 200) {
            $data = json_decode($authResponse['body'], true);
            return [
                'status' => 'active',
                'message' => 'PayPal API connected successfully',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'token_type' => $data['token_type'] ?? 'N/A',
                    'expires_in' => $data['expires_in'] ?? 0
                ]
            ];
        } else {
            throw new Exception("PayPal API test failed: HTTP {$authResponse['status_code']}");
        }
    }
    
    /**
     * Test WordPress API
     */
    private function testWordPress($apiKey, $config, $startTime) {
        $siteUrl = $config['site_url'] ?? null;
        if (!$siteUrl) {
            throw new Exception("WordPress site URL not configured");
        }
        
        $response = $this->makeRequest(
            rtrim($siteUrl, '/') . '/wp-json/wp/v2/users/me',
            'GET',
            null,
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]
        );
        
        if ($response['status_code'] === 200) {
            $data = json_decode($response['body'], true);
            return [
                'status' => 'active',
                'message' => 'WordPress API connected successfully',
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'user_name' => $data['name'] ?? 'N/A',
                    'site_url' => $siteUrl
                ]
            ];
        } else {
            throw new Exception("WordPress API test failed: HTTP {$response['status_code']}");
        }
    }
    
    /**
     * Test generic API endpoint
     */
    private function testGenericAPI($serviceName, $apiKey, $config, $startTime) {
        $testEndpoint = $config['test_endpoint'] ?? null;
        if (!$testEndpoint) {
            throw new Exception("No test endpoint configured for {$serviceName}");
        }
        
        $response = $this->makeRequest(
            $testEndpoint,
            'GET',
            null,
            [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]
        );
        
        if ($response['status_code'] >= 200 && $response['status_code'] < 300) {
            return [
                'status' => 'active',
                'message' => "{$serviceName} API connected successfully",
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'details' => [
                    'status_code' => $response['status_code']
                ]
            ];
        } else {
            throw new Exception("{$serviceName} API test failed: HTTP {$response['status_code']}");
        }
    }
    
    /**
     * Make HTTP request using cURL
     */
    private function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        
        // Set method
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        // Set headers
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        // Execute request
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: {$error}");
        }
        
        return [
            'status_code' => $statusCode,
            'body' => $response
        ];
    }
    
    /**
     * Batch test multiple API keys
     */
    public function batchTest($apiKeys) {
        $results = [];
        
        foreach ($apiKeys as $service => $config) {
            $results[$service] = $this->testAPI(
                $service,
                $config['api_key'],
                $config['api_secret'] ?? null,
                $config['additional_config'] ?? null
            );
        }
        
        return $results;
    }
}
