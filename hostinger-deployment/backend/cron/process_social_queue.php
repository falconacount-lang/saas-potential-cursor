<?php
/**
 * Process Social Media Post Queue
 * RUN VIA HOSTINGER CRON: Every 1 hour
 * Command: /usr/bin/php /home/u720615217/public_html/backend/cron/process_social_queue.php
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    die('Access denied. This script should be run via cron job.');
}

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/SocialMediaQueue.php';

// Set execution time limit (shared hosting limit)
@set_time_limit(50); // Leave 10 seconds buffer

// Log start
error_log('[CRON] Social queue processing started at ' . date('Y-m-d H:i:s'));

try {
    $queue = new SocialMediaQueue();
    
    // Process up to 10 posts per run (adjust based on performance)
    $result = $queue->processPendingPosts(10);
    
    if ($result['success']) {
        $stats = $result['data'];
        error_log(sprintf(
            '[CRON] Processed: %d, Succeeded: %d, Failed: %d',
            $stats['processed'],
            $stats['succeeded'],
            $stats['failed']
        ));
        
        // Log each post result
        foreach ($stats['details'] as $detail) {
            if (!$detail['success']) {
                error_log(sprintf(
                    '[CRON] Failed to post to %s (ID: %d): %s',
                    $detail['platform'],
                    $detail['id'],
                    $detail['error']
                ));
            }
        }
        
        echo "SUCCESS: Processed {$stats['processed']} posts\n";
    } else {
        error_log('[CRON] Error: ' . $result['error']);
        echo "ERROR: " . $result['error'] . "\n";
    }
    
    // Retry failed posts (max 3 attempts)
    $retryResult = $queue->retryFailedPosts(5);
    if ($retryResult['success'] && $retryResult['retried'] > 0) {
        error_log('[CRON] Retried ' . $retryResult['retried'] . ' failed posts');
    }
    
} catch (Exception $e) {
    error_log('[CRON] Fatal error: ' . $e->getMessage());
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}

error_log('[CRON] Social queue processing completed at ' . date('Y-m-d H:i:s'));
echo "Completed at " . date('Y-m-d H:i:s') . "\n";
