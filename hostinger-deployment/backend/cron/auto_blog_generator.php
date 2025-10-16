<?php
/**
 * Auto Blog Generator - Cron Job
 * 
 * Automatically generates blog posts based on trending topics
 * Run daily via cron: 0 9 * * * php /path/to/auto_blog_generator.php
 * 
 * Part of Rocket Site Plan - Phase 2: AI Content Generation
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/OpenAIIntegration.php';
require_once __DIR__ . '/../classes/APIKeyManager.php';

// Configuration
$BLOG_TOPICS = [
    'Logo Design Trends 2024',
    'YouTube Thumbnail Best Practices',
    'Video Editing Tips for Beginners',
    'Branding Mistakes to Avoid',
    'How to Choose the Right Logo Designer',
    'Social Media Graphics That Convert',
    'Color Psychology in Design',
    'Typography Tips for Non-Designers',
    'Creating a Consistent Brand Identity',
    'Design Tools Every Creator Needs'
];

$MAX_BLOGS_PER_DAY = 1; // Generate 1 blog per day
$AUTO_PUBLISH = false; // Set to true to auto-publish, false for draft

// Initialize
$db = new Database();
$conn = $db->getConnection();
$openai = new OpenAIIntegration();
$apiKeyManager = new APIKeyManager();

echo "🚀 Auto Blog Generator Started - " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 60) . "\n";

// Check if OpenAI API is configured
$openaiKey = $apiKeyManager->getAPIKey('openai', true);
if (!$openaiKey || !$openaiKey['is_enabled']) {
    echo "❌ OpenAI API key not configured or disabled\n";
    exit(1);
}

// Check budget
$stats = $apiKeyManager->getUsageStats('openai');
if (!$stats || $stats['budget_used_percent'] > 90) {
    echo "⚠️  AI budget at {$stats['budget_used_percent']}% - Skipping generation\n";
    exit(0);
}

echo "✅ OpenAI API ready\n";
echo "💰 Budget: \${$stats['current_spend']}/\${$stats['budget_limit']} ({$stats['budget_used_percent']}%)\n\n";

// Check how many blogs were generated today
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM blogs 
        WHERE DATE(created_at) = DATE('now')
        AND content LIKE '%AI Generated%'
    ");
    $stmt->execute();
    $todayCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($todayCount >= $MAX_BLOGS_PER_DAY) {
        echo "✓ Already generated {$todayCount} blog(s) today\n";
        exit(0);
    }
    
    echo "📝 Generating {$MAX_BLOGS_PER_DAY} blog post(s)...\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
}

// Select random topics that haven't been used recently
$topic = $BLOG_TOPICS[array_rand($BLOG_TOPICS)];

echo "📌 Topic: {$topic}\n";
echo "⏳ Generating content...\n";

// Generate blog content
$result = $openai->generateBlogContent(
    $topic,
    ['design', 'branding', 'creative services'],
    'professional',
    'long'
);

if (!$result['success']) {
    echo "❌ Failed to generate blog: " . ($result['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

$blogData = $result['data'];
$cost = $result['cost'];

echo "✅ Content generated (Cost: $" . number_format($cost, 4) . ")\n";
echo "📊 Title: {$blogData['title']}\n";
echo "📏 Length: ~" . str_word_count(strip_tags($blogData['content'])) . " words\n";
echo "⏱️  Read time: {$blogData['estimated_read_time']} minutes\n";

// Get admin user ID
$stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
$authorId = $adminUser['id'] ?? 1;

// Generate unique slug
$slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $blogData['title'])));
$originalSlug = $slug;
$counter = 1;

while (true) {
    $stmt = $conn->prepare("SELECT id FROM blogs WHERE slug = ?");
    $stmt->execute([$slug]);
    if (!$stmt->fetch()) break;
    $slug = $originalSlug . '-' . $counter++;
}

// Save to database
try {
    $stmt = $conn->prepare("
        INSERT INTO blogs (
            title, 
            slug, 
            excerpt, 
            content, 
            status, 
            author_id,
            published,
            read_time,
            created_at,
            published_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), ?)
    ");
    
    $publishedAt = $AUTO_PUBLISH ? date('Y-m-d H:i:s') : null;
    
    $stmt->execute([
        $blogData['title'],
        $slug,
        $blogData['excerpt'] ?? substr(strip_tags($blogData['content']), 0, 200),
        $blogData['content'],
        $AUTO_PUBLISH ? 'published' : 'draft',
        $authorId,
        $AUTO_PUBLISH ? 1 : 0,
        $blogData['estimated_read_time'] ?? 5,
        $publishedAt
    ]);
    
    $blogId = $conn->lastInsertId();
    
    echo "✅ Blog saved (ID: {$blogId})\n";
    echo "📍 Status: " . ($AUTO_PUBLISH ? 'Published' : 'Draft') . "\n";
    echo "🔗 Slug: {$slug}\n";
    
    // Add tags if provided
    if (!empty($blogData['tags'])) {
        foreach ($blogData['tags'] as $tagName) {
            try {
                // Create tag if doesn't exist
                $tagSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tagName)));
                $stmt = $conn->prepare("
                    INSERT OR IGNORE INTO tags (name, slug, created_at)
                    VALUES (?, ?, datetime('now'))
                ");
                $stmt->execute([$tagName, $tagSlug]);
                
                echo "🏷️  Tag: {$tagName}\n";
            } catch (Exception $e) {
                echo "⚠️  Tag error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Generate social media posts for the blog
    if ($AUTO_PUBLISH) {
        echo "\n📱 Generating social media posts...\n";
        
        $socialResult = $openai->generateSocialMediaPosts(
            $blogData['title'] . ': ' . $blogData['excerpt'],
            ['linkedin', 'twitter', 'facebook']
        );
        
        if ($socialResult['success']) {
            $socialPosts = $socialResult['data'];
            
            // Save to social post queue (Phase 3)
            foreach ($socialPosts as $platform => $postData) {
                echo "  ✓ {$platform}\n";
            }
        }
    }
    
    echo "\n" . str_repeat('=', 60) . "\n";
    echo "✅ Auto blog generation completed successfully!\n";
    echo "💰 Total cost: $" . number_format($cost, 4) . "\n";
    echo "🎯 Next run: Tomorrow at 9:00 AM\n";
    
} catch (PDOException $e) {
    echo "❌ Database error saving blog: " . $e->getMessage() . "\n";
    exit(1);
}

// Log the operation
$apiKeyManager->logAPIUsage(
    'openai',
    'auto_blog_generation',
    ['topic' => $topic],
    ['blog_id' => $blogId],
    200,
    0,
    true,
    null,
    $result['usage']['total_tokens'] ?? 0,
    $cost,
    $authorId
);

echo "\n🎉 Done!\n";
exit(0);
