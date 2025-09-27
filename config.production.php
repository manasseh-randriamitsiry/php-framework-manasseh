<?php

// Production environment configuration

return [
    'app' => [
        'debug' => false,
        'log_level' => 'error',
        'maintenance_mode' => false
    ],
    
    'database' => [
        'log_queries' => false,
        'connection_timeout' => 30,
        'retry_attempts' => 3
    ],
    
    'cache' => [
        'ttl' => 3600, // 1 hour for production
    ],
    
    'session' => [
        'secure' => true, // HTTPS only in production
        'lifetime' => 120, // 2 hours
        'regenerate_interval' => 300 // 5 minutes
    ],
    
    'mail' => [
        'driver' => 'smtp',
        'queue_enabled' => true
    ],
    
    'security' => [
        'strict_mode' => true,
        'rate_limit_enabled' => true,
        'csrf_required' => true,
        'force_https' => true
    ],
    
    'optimization' => [
        'gzip_compression' => true,
        'cache_headers' => true,
        'minify_css' => true,
        'minify_js' => true
    ]
];