<?php
/**
 * Twitter/X Integration
 * Post tweets using Twitter API v2
 */

class TwitterIntegration {
    private $apiKey;
    private $apiSecret;
    private $accessToken;
    private $accessTokenSecret;
    private $bearerToken;
    private $apiBaseUrl = 'https://api.twitter.com/2';
    
    public function __construct() {
        $this->apiKey = $_ENV['TWITTER_API_KEY'] ?? null;
        $this->apiSecret = $_ENV['TWITTER_API_SECRET'] ?? null;
        $this->accessToken = $_ENV['TWITTER_ACCESS_TOKEN'] ?? null;
        $this->accessTokenSecret = $_ENV['TWITTER_ACCESS_TOKEN_SECRET'] ?? null;
        $this->bearerToken = $_ENV['TWITTER_BEARER_TOKEN'] ?? null;
    }
    
    /**
     * Check if integration is enabled
     */
    public function isEnabled() {
        return !empty($this->apiKey) && !empty($this->accessToken);
    }
    
    /**
     * Post a tweet
     */
    public function postTweet($text, $imageUrl = null) {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'Twitter integration not configured'];
        }
        
        try {
            $data = ['text' => $text];
            
            // Upload media if image provided
            if ($imageUrl) {
                $mediaId = $this->uploadMedia($imageUrl);
                if ($mediaId) {
                    $data['media'] = ['media_ids' => [$mediaId]];
                }
            }
            
            $endpoint = '/tweets';
            $response = $this->makeApiCall($endpoint, 'POST', $data);
            
            if (isset($response['data']['id'])) {
                return [
                    'success' => true,
                    'tweet_id' => $response['data']['id'],
                    'platform' => 'twitter'
                ];
            }
            
            return [
                'success' => false,
                'error' => $response['errors'][0]['message'] ?? 'Unknown error'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Upload media (Twitter API v1.1)
     */
    private function uploadMedia($imageUrl) {
        try {
            // Download image first
            $imageData = file_get_contents($imageUrl);
            if (!$imageData) {
                return null;
            }
            
            $uploadUrl = 'https://upload.twitter.com/1.1/media/upload.json';
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $uploadUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => ['media_data' => base64_encode($imageData)],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $this->getOAuthHeaders($uploadUrl, 'POST'),
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $decoded = json_decode($response, true);
            
            return $decoded['media_id_string'] ?? null;
            
        } catch (Exception $e) {
            error_log('Twitter media upload failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Make API call to Twitter API v2
     */
    private function makeApiCall($endpoint, $method = 'GET', $data = []) {
        $url = $this->apiBaseUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $this->bearerToken,
            'Content-Type: application/json'
        ];
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            
            // Use OAuth 1.0a for POST requests
            $headers = $this->getOAuthHeaders($url, $method, $data);
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Generate OAuth 1.0a headers
     */
    private function getOAuthHeaders($url, $method, $params = []) {
        $oauth = [
            'oauth_consumer_key' => $this->apiKey,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0'
        ];
        
        $baseInfo = $this->buildBaseString($url, $method, array_merge($oauth, $params));
        $compositeKey = rawurlencode($this->apiSecret) . '&' . rawurlencode($this->accessTokenSecret);
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $baseInfo, $compositeKey, true));
        
        $header = 'Authorization: OAuth ';
        $values = [];
        foreach ($oauth as $key => $value) {
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        }
        $header .= implode(', ', $values);
        
        return [$header, 'Content-Type: application/json'];
    }
    
    /**
     * Build OAuth base string
     */
    private function buildBaseString($baseURI, $method, $params) {
        $r = [];
        ksort($params);
        foreach ($params as $key => $value) {
            $r[] = "$key=" . rawurlencode($value);
        }
        return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $r));
    }
}
