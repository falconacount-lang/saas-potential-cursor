<?php
/**
 * File-Based Cache System
 * Optimized for Shared Hosting (No Redis/Memcached)
 */

class Cache {
    private $cacheDir;
    private $defaultTTL = 3600; // 1 hour
    
    public function __construct($cacheDir = null) {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../cache/data/';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // Create .htaccess to protect cache directory
        $htaccess = $this->cacheDir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Order allow,deny\nDeny from all");
        }
    }
    
    /**
     * Get cached value
     */
    public function get($key) {
        $cacheFile = $this->getCacheFile($key);
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $data = file_get_contents($cacheFile);
        if (!$data) {
            return null;
        }
        
        $cached = unserialize($data);
        
        // Check if expired
        if ($cached['expires_at'] < time()) {
            unlink($cacheFile);
            return null;
        }
        
        return $cached['value'];
    }
    
    /**
     * Set cache value
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $cacheFile = $this->getCacheFile($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];
        
        return file_put_contents($cacheFile, serialize($data)) !== false;
    }
    
    /**
     * Delete cached value
     */
    public function delete($key) {
        $cacheFile = $this->getCacheFile($key);
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return true;
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }
    
    /**
     * Clear expired cache entries
     */
    public function clearExpired() {
        $files = glob($this->cacheDir . '*.cache');
        $count = 0;
        
        foreach ($files as $file) {
            if (!is_file($file)) continue;
            
            $data = file_get_contents($file);
            if (!$data) continue;
            
            $cached = unserialize($data);
            
            if ($cached['expires_at'] < time()) {
                unlink($file);
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Check if key exists and not expired
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Get cache file path for key
     */
    private function getCacheFile($key) {
        return $this->cacheDir . md5($key) . '.cache';
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $files = glob($this->cacheDir . '*.cache');
        $total = count($files);
        $expired = 0;
        $totalSize = 0;
        
        foreach ($files as $file) {
            if (!is_file($file)) continue;
            
            $totalSize += filesize($file);
            $data = file_get_contents($file);
            if (!$data) continue;
            
            $cached = unserialize($data);
            if ($cached['expires_at'] < time()) {
                $expired++;
            }
        }
        
        return [
            'total_entries' => $total,
            'expired_entries' => $expired,
            'active_entries' => $total - $expired,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize)
        ];
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
