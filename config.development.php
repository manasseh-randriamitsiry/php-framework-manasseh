<?php

// Development environment configuration

return [
    'app' => [
        'debug' => true,
        'log_level' => 'debug',
        'maintenance_mode' => false
    ],
    
    'database' => [
        'log_queries' => true,
        'slow_query_threshold' => 1000, // milliseconds
    ],
    
    'cache' => [
        'ttl' => 300, // 5 minutes for development
    ],
    
    'session' => [
        'secure' => false, // Allow HTTP in development
        'lifetime' => 1440 // 24 hours for development
    ],
    
    'mail' => [
        'driver' => 'log', // Log emails instead of sending
        'log_file' => 'logs/mail.log'
    ],
    
    'security' => [
        'strict_mode' => false,
        'rate_limit_enabled' => false
    ]
];