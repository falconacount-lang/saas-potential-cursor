<?php
/**
 * Cache Cleanup Script
 * Removes expired cache entries to save disk space
 * RUN VIA HOSTINGER CRON: Daily at 3 AM
 * Command: /usr/bin/php /home/u720615217/public_html/backend/cron/cleanup_cache.php
 */

// Prevent direct web access
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key'])) {
    die('Access denied. This script should be run via cron job.');
}

// Load configuration
require_once __DIR__ . '/../classes/Cache.php';

@set_time_limit(30);

error_log('[CRON] Cache cleanup started at ' . date('Y-m-d H:i:s'));

try {
    $cache = new Cache();
    
    // Get stats before cleanup
    $statsBefore = $cache->getStats();
    
    // Clear expired entries
    $removedCount = $cache->clearExpired();
    
    // Get stats after cleanup
    $statsAfter = $cache->getStats();
    
    $spaceSaved = $statsBefore['total_size'] - $statsAfter['total_size'];
    
    error_log(sprintf(
        '[CRON] Cache cleanup: %d expired entries removed, %s space freed',
        $removedCount,
        formatBytes($spaceSaved)
    ));
    
    echo "Cleaned up $removedCount expired cache entries\n";
    echo "Space freed: " . formatBytes($spaceSaved) . "\n";
    
} catch (Exception $e) {
    error_log('[CRON] Cache cleanup error: ' . $e->getMessage());
    echo "ERROR: " . $e->getMessage() . "\n";
}

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

error_log('[CRON] Cache cleanup completed at ' . date('Y-m-d H:i:s'));
echo "Completed at " . date('Y-m-d H:i:s') . "\n";
