<?php
/**
 * Social Media Post Queue Manager
 * Manages queued posts for all social media platforms
 * Optimized for shared hosting with cron job processing
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/MetaIntegration.php';
require_once __DIR__ . '/TwitterIntegration.php';
require_once __DIR__ . '/LinkedInIntegration.php';

class SocialMediaQueue {
    private $db;
    private $meta;
    private $twitter;
    private $linkedin;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        $this->meta = new MetaIntegration();
        $this->twitter = new TwitterIntegration();
        $this->linkedin = new LinkedInIntegration();
    }
    
    /**
     * Add post to queue
     */
    public function addToQueue($platforms, $message, $imageUrl = null, $scheduledAt = null) {
        try {
            $scheduledAt = $scheduledAt ?? date('Y-m-d H:i:s');
            
            foreach ($platforms as $platform) {
                $stmt = $this->db->prepare("
                    INSERT INTO social_post_queue 
                    (platform, content, image_url, scheduled_at, status, created_at) 
                    VALUES (?, ?, ?, ?, 'pending', NOW())
                ");
                
                $stmt->execute([
                    $platform,
                    $message,
                    $imageUrl,
                    $scheduledAt
                ]);
            }
            
            return [
                'success' => true,
                'message' => 'Posts added to queue',
                'count' => count($platforms)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process pending posts (called by cron job)
     */
    public function processPendingPosts($limit = 10) {
        try {
            // Get pending posts that are ready to post
            $stmt = $this->db->prepare("
                SELECT * FROM social_post_queue 
                WHERE status = 'pending' 
                AND scheduled_at <= NOW() 
                ORDER BY scheduled_at ASC 
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $results = [
                'processed' => 0,
                'succeeded' => 0,
                'failed' => 0,
                'details' => []
            ];
            
            foreach ($posts as $post) {
                $results['processed']++;
                $result = $this->postToPlatform($post);
                
                if ($result['success']) {
                    $results['succeeded']++;
                    $this->updatePostStatus($post['id'], 'posted', $result['post_id'] ?? null);
                } else {
                    $results['failed']++;
                    $this->updatePostStatus($post['id'], 'failed', null, $result['error']);
                }
                
                $results['details'][] = [
                    'id' => $post['id'],
                    'platform' => $post['platform'],
                    'success' => $result['success'],
                    'error' => $result['error'] ?? null
                ];
            }
            
            return [
                'success' => true,
                'data' => $results
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Post to specific platform
     */
    private function postToPlatform($post) {
        $platform = $post['platform'];
        $content = $post['content'];
        $imageUrl = $post['image_url'];
        $link = $post['link_url'];
        
        switch ($platform) {
            case 'facebook':
                return $this->meta->postToFacebook($content, $imageUrl, $link);
                
            case 'instagram':
                if (!$imageUrl) {
                    return ['success' => false, 'error' => 'Instagram requires an image'];
                }
                return $this->meta->postToInstagram($imageUrl, $content);
                
            case 'twitter':
                return $this->twitter->postTweet($content, $imageUrl);
                
            case 'linkedin':
                return $this->linkedin->postToLinkedIn($content, $imageUrl, $link);
                
            default:
                return ['success' => false, 'error' => 'Unknown platform: ' . $platform];
        }
    }
    
    /**
     * Update post status
     */
    private function updatePostStatus($id, $status, $postId = null, $error = null) {
        $stmt = $this->db->prepare("
            UPDATE social_post_queue 
            SET status = ?, 
                posted_at = NOW(), 
                external_post_id = ?,
                error_message = ?,
                attempts = attempts + 1
            WHERE id = ?
        ");
        
        $stmt->execute([$status, $postId, $error, $id]);
    }
    
    /**
     * Get queue statistics
     */
    public function getQueueStats() {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM social_post_queue
            ");
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Retry failed posts
     */
    public function retryFailedPosts($limit = 5) {
        try {
            $stmt = $this->db->prepare("
                UPDATE social_post_queue 
                SET status = 'pending', 
                    error_message = NULL,
                    scheduled_at = NOW()
                WHERE status = 'failed' 
                AND attempts < 3
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            
            return [
                'success' => true,
                'retried' => $stmt->rowCount()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
