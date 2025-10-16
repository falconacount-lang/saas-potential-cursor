<?php
/**
 * Auto Blog Generator
 * Automatically generates and publishes blog posts using AI
 * RUN VIA HOSTINGER CRON: Daily at 9 AM
 * Command: /usr/bin/php /home/u720615217/public_html/backend/cron/auto_blog_generator.php
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    die('Access denied. This script should be run via cron job.');
}

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/OpenAIIntegration.php';
require_once __DIR__ . '/../classes/WordPressIntegration.php';
require_once __DIR__ . '/../classes/SocialMediaQueue.php';

// Set execution time limit
@set_time_limit(50);

// Log start
error_log('[CRON] Auto-blog generation started at ' . date('Y-m-d H:i:s'));

// Check if enabled
if (($_ENV['CRON_AUTO_BLOG_ENABLED'] ?? 'false') !== 'true') {
    error_log('[CRON] Auto-blog generation is disabled');
    echo "Auto-blog generation is disabled in .env\n";
    exit(0);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $ai = new OpenAIIntegration();
    $wp = new WordPressIntegration();
    $socialQueue = new SocialMediaQueue();
    
    // Define blog topics (rotate daily)
    $topics = [
        'Latest Logo Design Trends for 2025',
        'YouTube Thumbnail Best Practices for Maximum Views',
        'Video Editing Tips Every Content Creator Should Know',
        'Branding Strategies for Small Businesses',
        'Color Psychology in Logo Design',
        'How to Create Professional YouTube Thumbnails',
        'Top Video Editing Software Comparison',
        'Typography Tips for Modern Logo Design',
        'Social Media Content Strategy for Designers',
        'Building a Strong Brand Identity in 2025'
    ];
    
    // Select topic based on day of year (rotates automatically)
    $dayOfYear = (int)date('z');
    $topicIndex = $dayOfYear % count($topics);
    $topic = $topics[$topicIndex];
    
    error_log('[CRON] Generating blog for topic: ' . $topic);
    
    // Generate blog content using AI
    $keywords = ['design', 'branding', 'logo', 'youtube', 'video editing'];
    $blogResult = $ai->generateBlogContent($topic, $keywords, 'professional', 'medium');
    
    if (!$blogResult['success']) {
        throw new Exception('Failed to generate blog: ' . $blogResult['error']);
    }
    
    $blogData = $blogResult['data'];
    error_log('[CRON] Blog generated: ' . $blogData['title']);
    
    // Save to local database
    $stmt = $db->prepare("
        INSERT INTO blogs 
        (title, slug, excerpt, content, meta_title, meta_description, 
         status, featured, read_time, published_at, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'published', 1, ?, NOW(), NOW())
    ");
    
    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $blogData['title']));
    
    $stmt->execute([
        $blogData['title'],
        $slug,
        $blogData['excerpt'] ?? '',
        $blogData['content'],
        $blogData['meta_title'] ?? $blogData['title'],
        $blogData['meta_description'] ?? $blogData['excerpt'],
        $blogData['estimated_read_time'] ?? 5
    ]);
    
    $blogId = $db->lastInsertId();
    error_log('[CRON] Blog saved to database with ID: ' . $blogId);
    
    // Post to WordPress if configured
    if ($wp->isEnabled()) {
        $wpResult = $wp->publishPost(
            $blogData['title'],
            $blogData['content'],
            $blogData['excerpt'] ?? '',
            ['Design Tips', 'Tutorials'],
            $blogData['tags'] ?? []
        );
        
        if ($wpResult['success']) {
            error_log('[CRON] Blog published to WordPress: ' . $wpResult['post_url']);
        } else {
            error_log('[CRON] WordPress publish failed: ' . $wpResult['error']);
        }
    }
    
    // Create social media posts
    $socialMessage = "📝 New blog post: " . $blogData['title'] . "\n\n";
    $socialMessage .= substr(strip_tags($blogData['excerpt'] ?? ''), 0, 200) . "...\n\n";
    $socialMessage .= "Read more: https://adilcreator.com/blog/" . $slug;
    
    $socialResult = $socialQueue->addToQueue(
        ['facebook', 'twitter', 'linkedin'],
        $socialMessage,
        null, // Add featured image URL if available
        date('Y-m-d H:i:s', strtotime('+1 hour')) // Post 1 hour after blog is published
    );
    
    if ($socialResult['success']) {
        error_log('[CRON] Social media posts queued: ' . $socialResult['count']);
    }
    
    echo "SUCCESS: Blog published and social posts queued\n";
    echo "Title: " . $blogData['title'] . "\n";
    echo "Cost: $" . number_format($blogResult['cost'], 4) . "\n";
    
} catch (Exception $e) {
    error_log('[CRON] Auto-blog fatal error: ' . $e->getMessage());
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
}

error_log('[CRON] Auto-blog generation completed at ' . date('Y-m-d H:i:s'));
echo "Completed at " . date('Y-m-d H:i:s') . "\n";
