<?php
/**
 * LinkedIn Integration
 * Post to LinkedIn Organization Pages
 */

class LinkedInIntegration {
    private $clientId;
    private $clientSecret;
    private $accessToken;
    private $organizationId;
    private $apiBaseUrl = 'https://api.linkedin.com/v2';
    
    public function __construct() {
        $this->clientId = $_ENV['LINKEDIN_CLIENT_ID'] ?? null;
        $this->clientSecret = $_ENV['LINKEDIN_CLIENT_SECRET'] ?? null;
        $this->accessToken = $_ENV['LINKEDIN_ACCESS_TOKEN'] ?? null;
        $this->organizationId = $_ENV['LINKEDIN_ORGANIZATION_ID'] ?? null;
    }
    
    /**
     * Check if integration is enabled
     */
    public function isEnabled() {
        return !empty($this->accessToken) && !empty($this->organizationId);
    }
    
    /**
     * Post to LinkedIn
     */
    public function postToLinkedIn($text, $imageUrl = null, $link = null) {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'LinkedIn integration not configured'];
        }
        
        try {
            $author = 'urn:li:organization:' . $this->organizationId;
            
            $shareContent = [
                'shareCommentary' => ['text' => $text],
                'shareMediaCategory' => $imageUrl ? 'IMAGE' : ($link ? 'ARTICLE' : 'NONE')
            ];
            
            if ($imageUrl) {
                // Upload image first
                $mediaUrn = $this->uploadImage($imageUrl);
                if ($mediaUrn) {
                    $shareContent['media'] = [[
                        'status' => 'READY',
                        'media' => $mediaUrn
                    ]];
                }
            } elseif ($link) {
                $shareContent['media'] = [[
                    'status' => 'READY',
                    'originalUrl' => $link
                ]];
            }
            
            $data = [
                'author' => $author,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => $shareContent
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
                ]
            ];
            
            $response = $this->makeApiCall('/ugcPosts', 'POST', $data);
            
            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'post_id' => $response['id'],
                    'platform' => 'linkedin'
                ];
            }
            
            return [
                'success' => false,
                'error' => $response['message'] ?? 'Unknown error'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Upload image to LinkedIn
     */
    private function uploadImage($imageUrl) {
        try {
            // Step 1: Register upload
            $registerData = [
                'registerUploadRequest' => [
                    'recipes' => ['urn:li:digitalmediaRecipe:feedshare-image'],
                    'owner' => 'urn:li:organization:' . $this->organizationId,
                    'serviceRelationships' => [[
                        'relationshipType' => 'OWNER',
                        'identifier' => 'urn:li:userGeneratedContent'
                    ]]
                ]
            ];
            
            $registerResponse = $this->makeApiCall('/assets?action=registerUpload', 'POST', $registerData);
            
            if (!isset($registerResponse['value']['uploadMechanism'])) {
                return null;
            }
            
            $uploadUrl = $registerResponse['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
            $assetUrn = $registerResponse['value']['asset'];
            
            // Step 2: Upload image
            $imageData = file_get_contents($imageUrl);
            if (!$imageData) {
                return null;
            }
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $uploadUrl,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $imageData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->accessToken,
                    'Content-Type: application/octet-stream'
                ],
                CURLOPT_TIMEOUT => 60
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 201) {
                return $assetUrn;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log('LinkedIn image upload failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Make API call to LinkedIn
     */
    private function makeApiCall($endpoint, $method = 'GET', $data = []) {
        $url = $this->apiBaseUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json',
            'X-Restli-Protocol-Version: 2.0.0'
        ];
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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
}
