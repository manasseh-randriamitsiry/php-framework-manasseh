<?php

namespace App;

class Cache
{
    protected static $store = [];
    protected static $cacheDir = null;
    
    /**
     * Initialize cache directory
     */
    public static function init()
    {
        if (self::$cacheDir === null) {
            self::$cacheDir = __DIR__ . '/../cache/';
            
            if (!is_dir(self::$cacheDir)) {
                mkdir(self::$cacheDir, 0755, true);
            }
        }
    }
    
    /**
     * Get cached value
     */
    public static function get($key, $default = null)
    {
        // Try memory cache first
        if (isset(self::$store[$key])) {
            $item = self::$store[$key];
            if ($item['expires'] === null || $item['expires'] > time()) {
                return $item['value'];
            } else {
                unset(self::$store[$key]);
            }
        }
        
        // Try file cache
        $filePath = self::getFilePath($key);
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $data = unserialize($content);
            
            if ($data && ($data['expires'] === null || $data['expires'] > time())) {
                // Store in memory for faster access
                self::$store[$key] = $data;
                return $data['value'];
            } else {
                // Expired, remove file
                unlink($filePath);
            }
        }
        
        return $default;
    }
    
    /**
     * Store value in cache
     */
    public static function put($key, $value, $ttl = 3600)
    {
        self::init();
        
        $expires = $ttl > 0 ? time() + $ttl : null;
        $data = [
            'value' => $value,
            'expires' => $expires,
            'created' => time()
        ];
        
        // Store in memory
        self::$store[$key] = $data;
        
        // Store in file
        $filePath = self::getFilePath($key);
        file_put_contents($filePath, serialize($data), LOCK_EX);
        
        return true;
    }
    
    /**
     * Check if key exists in cache
     */
    public static function has($key)
    {
        return self::get($key) !== null;
    }
    
    /**
     * Remove key from cache
     */
    public static function forget($key)
    {
        // Remove from memory
        unset(self::$store[$key]);
        
        // Remove file
        $filePath = self::getFilePath($key);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        return true;
    }
    
    /**
     * Clear all cache
     */
    public static function flush()
    {
        // Clear memory
        self::$store = [];
        
        // Clear files
        self::init();
        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Get or set cache value
     */
    public static function remember($key, $ttl, $callback)
    {
        $value = self::get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        self::put($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Get cache forever (no expiration)
     */
    public static function forever($key, $value)
    {
        return self::put($key, $value, 0);
    }
    
    /**
     * Increment cache value
     */
    public static function increment($key, $value = 1)
    {
        $current = self::get($key, 0);
        $new = $current + $value;
        self::put($key, $new);
        
        return $new;
    }
    
    /**
     * Decrement cache value
     */
    public static function decrement($key, $value = 1)
    {
        return self::increment($key, -$value);
    }
    
    /**
     * Get file path for cache key
     */
    protected static function getFilePath($key)
    {
        self::init();
        $hash = md5($key);
        return self::$cacheDir . $hash . '.cache';
    }
    
    /**
     * Clean expired cache files
     */
    public static function cleanExpired()
    {
        self::init();
        $files = glob(self::$cacheDir . '*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = unserialize($content);
            
            if ($data && $data['expires'] !== null && $data['expires'] <= time()) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Get cache statistics
     */
    public static function getStats()
    {
        self::init();
        $files = glob(self::$cacheDir . '*.cache');
        $totalSize = 0;
        $expired = 0;
        $valid = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $content = file_get_contents($file);
            $data = unserialize($content);
            
            if ($data && $data['expires'] !== null && $data['expires'] <= time()) {
                $expired++;
            } else {
                $valid++;
            }
        }
        
        return [
            'total_files' => count($files),
            'valid_files' => $valid,
            'expired_files' => $expired,
            'total_size' => $totalSize,
            'memory_items' => count(self::$store)
        ];
    }
}