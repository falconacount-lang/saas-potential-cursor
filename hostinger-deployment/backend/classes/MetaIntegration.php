<?php
/**
 * Meta (Facebook/Instagram) Integration
 * Post to Facebook Pages and Instagram Business Accounts
 */

class MetaIntegration {
    private $appId;
    private $appSecret;
    private $pageId;
    private $pageAccessToken;
    private $instagramAccountId;
    private $apiVersion = 'v18.0';
    private $apiBaseUrl = 'https://graph.facebook.com';
    
    public function __construct() {
        $this->appId = $_ENV['META_APP_ID'] ?? null;
        $this->appSecret = $_ENV['META_APP_SECRET'] ?? null;
        $this->pageId = $_ENV['META_PAGE_ID'] ?? null;
        $this->pageAccessToken = $_ENV['META_PAGE_ACCESS_TOKEN'] ?? null;
        $this->instagramAccountId = $_ENV['META_INSTAGRAM_ACCOUNT_ID'] ?? null;
    }
    
    /**
     * Check if integration is enabled
     */
    public function isEnabled() {
        return !empty($this->appId) && !empty($this->pageAccessToken);
    }
    
    /**
     * Post to Facebook Page
     */
    public function postToFacebook($message, $imageUrl = null, $link = null) {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'Meta integration not configured'];
        }
        
        try {
            $endpoint = "/{$this->apiVersion}/{$this->pageId}/";
            
            if ($imageUrl) {
                // Post photo
                $endpoint .= 'photos';
                $data = [
                    'message' => $message,
                    'url' => $imageUrl,
                    'access_token' => $this->pageAccessToken
                ];
            } else {
                // Post text/link
                $endpoint .= 'feed';
                $data = [
                    'message' => $message,
                    'access_token' => $this->pageAccessToken
                ];
                
                if ($link) {
                    $data['link'] = $link;
                }
            }
            
            $response = $this->makeApiCall($endpoint, 'POST', $data);
            
            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'post_id' => $response['id'],
                    'platform' => 'facebook'
                ];
            }
            
            return [
                'success' => false,
                'error' => $response['error']['message'] ?? 'Unknown error'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Post to Instagram
     */
    public function postToInstagram($imageUrl, $caption) {
        if (!$this->isEnabled() || empty($this->instagramAccountId)) {
            return ['success' => false, 'error' => 'Instagram integration not configured'];
        }
        
        try {
            // Step 1: Create media container
            $endpoint = "/{$this->apiVersion}/{$this->instagramAccountId}/media";
            $data = [
                'image_url' => $imageUrl,
                'caption' => $caption,
                'access_token' => $this->pageAccessToken
            ];
            
            $response = $this->makeApiCall($endpoint, 'POST', $data);
            
            if (!isset($response['id'])) {
                return [
                    'success' => false,
                    'error' => $response['error']['message'] ?? 'Failed to create media container'
                ];
            }
            
            $creationId = $response['id'];
            
            // Step 2: Publish media
            $publishEndpoint = "/{$this->apiVersion}/{$this->instagramAccountId}/media_publish";
            $publishData = [
                'creation_id' => $creationId,
                'access_token' => $this->pageAccessToken
            ];
            
            $publishResponse = $this->makeApiCall($publishEndpoint, 'POST', $publishData);
            
            if (isset($publishResponse['id'])) {
                return [
                    'success' => true,
                    'post_id' => $publishResponse['id'],
                    'platform' => 'instagram'
                ];
            }
            
            return [
                'success' => false,
                'error' => $publishResponse['error']['message'] ?? 'Failed to publish'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Make API call to Facebook Graph API
     */
    private function makeApiCall($endpoint, $method = 'GET', $data = []) {
        $url = $this->apiBaseUrl . $endpoint;
        
        $ch = curl_init();
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            if (!empty($data)) {
                $url .= '?' . http_build_query($data);
            }
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        $decoded = json_decode($response, true);
        
        if (!$decoded) {
            throw new Exception('Invalid JSON response');
        }
        
        return $decoded;
    }
    
    /**
     * Get page information
     */
    public function getPageInfo() {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'Meta integration not configured'];
        }
        
        try {
            $endpoint = "/{$this->apiVersion}/{$this->pageId}";
            $data = [
                'fields' => 'name,fan_count,followers_count,about',
                'access_token' => $this->pageAccessToken
            ];
            
            $response = $this->makeApiCall($endpoint, 'GET', $data);
            
            return [
                'success' => true,
                'data' => $response
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
