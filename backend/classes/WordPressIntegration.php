<?php
/**
 * WordPress Integration
 * Auto-post blogs to WordPress via REST API
 */

class WordPressIntegration {
    private $siteUrl;
    private $username;
    private $appPassword;
    
    public function __construct() {
        $this->siteUrl = $_ENV['WORDPRESS_SITE_URL'] ?? null;
        $this->username = $_ENV['WORDPRESS_USERNAME'] ?? null;
        $this->appPassword = $_ENV['WORDPRESS_APP_PASSWORD'] ?? null;
    }
    
    /**
     * Check if integration is enabled
     */
    public function isEnabled() {
        return !empty($this->siteUrl) && !empty($this->username) && !empty($this->appPassword);
    }
    
    /**
     * Publish blog post to WordPress
     */
    public function publishPost($title, $content, $excerpt = '', $categories = [], $tags = [], $featuredImage = null) {
        if (!$this->isEnabled()) {
            return ['success' => false, 'error' => 'WordPress integration not configured'];
        }
        
        try {
            // Get or create categories
            $categoryIds = $this->getCategoryIds($categories);
            
            // Get or create tags
            $tagIds = $this->getTagIds($tags);
            
            // Upload featured image if provided
            $featuredMediaId = null;
            if ($featuredImage) {
                $featuredMediaId = $this->uploadMedia($featuredImage);
            }
            
            // Create post data
            $postData = [
                'title' => $title,
                'content' => $content,
                'excerpt' => $excerpt,
                'status' => 'publish',
                'categories' => $categoryIds,
                'tags' => $tagIds
            ];
            
            if ($featuredMediaId) {
                $postData['featured_media'] = $featuredMediaId;
            }
            
            // Create post
            $response = $this->makeApiCall('/wp/v2/posts', 'POST', $postData);
            
            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'post_id' => $response['id'],
                    'post_url' => $response['link'],
                    'platform' => 'wordpress'
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
     * Upload media to WordPress
     */
    private function uploadMedia($imageUrl) {
        try {
            $imageData = file_get_contents($imageUrl);
            if (!$imageData) {
                return null;
            }
            
            $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
            $mimeType = $this->getMimeType($filename);
            
            $url = rtrim($this->siteUrl, '/') . '/wp-json/wp/v2/media';
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $imageData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Disposition: attachment; filename="' . $filename . '"',
                    'Content-Type: ' . $mimeType,
                    'Authorization: Basic ' . base64_encode($this->username . ':' . $this->appPassword)
                ],
                CURLOPT_TIMEOUT => 60
            ]);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $decoded = json_decode($response, true);
            return $decoded['id'] ?? null;
            
        } catch (Exception $e) {
            error_log('WordPress media upload failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get category IDs, create if not exist
     */
    private function getCategoryIds($categories) {
        $ids = [];
        
        foreach ($categories as $category) {
            // Try to find existing category
            $response = $this->makeApiCall('/wp/v2/categories?search=' . urlencode($category), 'GET');
            
            if (!empty($response) && isset($response[0]['id'])) {
                $ids[] = $response[0]['id'];
            } else {
                // Create new category
                $newCat = $this->makeApiCall('/wp/v2/categories', 'POST', ['name' => $category]);
                if (isset($newCat['id'])) {
                    $ids[] = $newCat['id'];
                }
            }
        }
        
        return $ids;
    }
    
    /**
     * Get tag IDs, create if not exist
     */
    private function getTagIds($tags) {
        $ids = [];
        
        foreach ($tags as $tag) {
            $response = $this->makeApiCall('/wp/v2/tags?search=' . urlencode($tag), 'GET');
            
            if (!empty($response) && isset($response[0]['id'])) {
                $ids[] = $response[0]['id'];
            } else {
                $newTag = $this->makeApiCall('/wp/v2/tags', 'POST', ['name' => $tag]);
                if (isset($newTag['id'])) {
                    $ids[] = $newTag['id'];
                }
            }
        }
        
        return $ids;
    }
    
    /**
     * Make API call to WordPress REST API
     */
    private function makeApiCall($endpoint, $method = 'GET', $data = []) {
        $url = rtrim($this->siteUrl, '/') . '/wp-json' . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Authorization: Basic ' . base64_encode($this->username . ':' . $this->appPassword),
            'Content-Type: application/json'
        ];
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
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
    
    /**
     * Get MIME type from filename
     */
    private function getMimeType($filename) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }
}
