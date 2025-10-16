<?php
/**
 * Process Social Media Queue - Cron Job
 * 
 * Processes scheduled social media posts
 * Run every 15 minutes: */15 * * * * php /path/to/process_social_queue.php
 * 
 * Part of Rocket Site Plan - Phase 3: Social Media Automation
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/SocialMediaManager.php';

echo "🚀 Social Media Queue Processor Started - " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 60) . "\n";

try {
    $socialManager = new SocialMediaManager();
    
    // Get available platforms
    $platforms = $socialManager->getAvailablePlatforms();
    echo "✅ Available platforms: " . implode(', ', $platforms) . "\n\n";
    
    // Process scheduled posts
    echo "📤 Processing scheduled posts...\n";
    $result = $socialManager->processScheduledPosts();
    
    if ($result['success']) {
        echo "✅ Processed: {$result['processed']}\n";
        echo "❌ Failed: {$result['failed']}\n";
        echo "📊 Total: {$result['total']}\n";
    } else {
        echo "❌ Error: {$result['message']}\n";
        exit(1);
    }
    
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "✅ Queue processing completed!\n";
    echo "🕒 Next run: In 15 minutes\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

exit(0);
