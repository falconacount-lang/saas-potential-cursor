<?php
/**
 * Social Media Manager
 * 
 * Manages automated social media posting across multiple platforms
 * Part of Rocket Site Plan - Phase 3: Social Media Automation
 * 
 * Supported Platforms:
 * - LinkedIn
 * - Twitter/X
 * - Facebook
 * - Instagram
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/APIKeyManager.php';
require_once __DIR__ . '/LinkedInIntegration.php';
require_once __DIR__ . '/TwitterIntegration.php';
require_once __DIR__ . '/MetaIntegration.php';

class SocialMediaManager {
    private $db;
    private $conn;
    private $apiKeyManager;
    private $integrations = [];
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->apiKeyManager = new APIKeyManager();
        $this->loadIntegrations();
    }
    
    private function loadIntegrations() {
        // Load enabled integrations
        $apiKeys = $this->apiKeyManager->getAllAPIKeys(false, false);
        
        foreach ($apiKeys as $key) {
            switch ($key['service_name']) {
                case 'linkedin':
                    if ($key['is_enabled']) {
                        $this->integrations['linkedin'] = new LinkedInIntegration();
                    }
                    break;
                case 'twitter':
                    if ($key['is_enabled']) {
                        $this->integrations['twitter'] = new TwitterIntegration();
                    }
                    break;
                case 'facebook':
                case 'meta':
                    if ($key['is_enabled']) {
                        $this->integrations['facebook'] = new MetaIntegration();
                    }
                    break;
            }
        }
    }
    
    /**
     * Schedule a post for later
     */
    public function schedulePost($platform, $content, $scheduledTime, $mediaUrl = null, $blogId = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO social_post_queue (
                    platform, content, media_url, blog_id, 
                    status, scheduled_at, created_at
                ) VALUES (?, ?, ?, ?, 'pending', ?, datetime('now'))
            ");
            
            $success = $stmt->execute([
                $platform,
                $content,
                $mediaUrl,
                $blogId,
                $scheduledTime
            ]);
            
            return [
                'success' => $success,
                'message' => $success ? 'Post scheduled successfully' : 'Failed to schedule post',
                'id' => $success ? $this->conn->lastInsertId() : null
            ];
            
        } catch (PDOException $e) {
            error_log('Schedule post error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Post immediately to one or all platforms
     */
    public function postNow($platform, $content, $mediaUrl = null) {
        if ($platform === 'all') {
            return $this->postToAllPlatforms($content, $mediaUrl);
        }
        
        if (!isset($this->integrations[$platform])) {
            return [
                'success' => false,
                'message' => "Platform {$platform} not configured or disabled"
            ];
        }
        
        try {
            $integration = $this->integrations[$platform];
            $result = $integration->post($content, $mediaUrl);
            
            // Log the post
            $this->logSocialPost($platform, $content, $result['success'], $result['post_id'] ?? null);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Post error on {$platform}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Post to all enabled platforms
     */
    public function postToAllPlatforms($content, $mediaUrl = null) {
        $results = [];
        
        foreach ($this->integrations as $platform => $integration) {
            try {
                $result = $integration->post($content, $mediaUrl);
                $results[$platform] = $result;
                
                // Log the post
                $this->logSocialPost($platform, $content, $result['success'], $result['post_id'] ?? null);
                
            } catch (Exception $e) {
                $results[$platform] = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }
        
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        
        return [
            'success' => $successCount > 0,
            'message' => "Posted to {$successCount} of " . count($results) . " platforms",
            'results' => $results
        ];
    }
    
    /**
     * Process scheduled posts (run via cron)
     */
    public function processScheduledPosts() {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM social_post_queue 
                WHERE status = 'pending' 
                AND scheduled_at <= datetime('now')
                ORDER BY scheduled_at ASC
                LIMIT 50
            ");
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $processed = 0;
            $failed = 0;
            
            foreach ($posts as $post) {
                $result = $this->postNow($post['platform'], $post['content'], $post['media_url']);
                
                if ($result['success']) {
                    // Update status to posted
                    $updateStmt = $this->conn->prepare("
                        UPDATE social_post_queue 
                        SET status = 'posted', 
                            posted_at = datetime('now'),
                            external_post_id = ?
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$result['post_id'] ?? null, $post['id']]);
                    $processed++;
                } else {
                    // Update status to failed
                    $updateStmt = $this->conn->prepare("
                        UPDATE social_post_queue 
                        SET status = 'failed',
                            error_message = ?,
                            attempts = attempts + 1
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$result['message'], $post['id']]);
                    $failed++;
                    
                    // Retry failed posts (max 3 attempts)
                    if ($post['attempts'] < 3) {
                        // Reschedule for 1 hour later
                        $updateStmt = $this->conn->prepare("
                            UPDATE social_post_queue 
                            SET status = 'pending',
                                scheduled_at = datetime('now', '+1 hour')
                            WHERE id = ?
                        ");
                        $updateStmt->execute([$post['id']]);
                    }
                }
            }
            
            return [
                'success' => true,
                'processed' => $processed,
                'failed' => $failed,
                'total' => count($posts)
            ];
            
        } catch (PDOException $e) {
            error_log('Process scheduled posts error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get scheduled posts
     */
    public function getScheduledPosts($platform = null, $status = null, $limit = 50) {
        try {
            $sql = "SELECT * FROM social_post_queue WHERE 1=1";
            $params = [];
            
            if ($platform) {
                $sql .= " AND platform = ?";
                $params[] = $platform;
            }
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY scheduled_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (PDOException $e) {
            error_log('Get scheduled posts error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel a scheduled post
     */
    public function cancelScheduledPost($postId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE social_post_queue 
                SET status = 'cancelled'
                WHERE id = ? AND status = 'pending'
            ");
            $success = $stmt->execute([$postId]);
            
            return [
                'success' => $success && $stmt->rowCount() > 0,
                'message' => $success ? 'Post cancelled' : 'Post not found or already processed'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get social media analytics
     */
    public function getAnalytics($platform = null, $days = 30) {
        try {
            $sql = "
                SELECT 
                    platform,
                    COUNT(*) as total_posts,
                    SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_posts,
                    DATE(created_at) as date
                FROM social_media_posts
                WHERE created_at >= datetime('now', '-{$days} days')
            ";
            
            if ($platform) {
                $sql .= " AND platform = ?";
            }
            
            $sql .= " GROUP BY platform, DATE(created_at) ORDER BY date DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($platform ? [$platform] : []);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Log social media post
     */
    private function logSocialPost($platform, $content, $success, $postId = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO social_media_posts (
                    platform, content, external_post_id, success, created_at
                ) VALUES (?, ?, ?, ?, datetime('now'))
            ");
            $stmt->execute([$platform, $content, $postId, $success ? 1 : 0]);
        } catch (PDOException $e) {
            error_log('Log social post error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get available platforms
     */
    public function getAvailablePlatforms() {
        return array_keys($this->integrations);
    }
}
