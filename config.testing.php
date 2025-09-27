<?php

// Testing environment configuration

return [
    'app' => [
        'debug' => true,
        'log_level' => 'debug',
        'maintenance_mode' => false
    ],
    
    'database' => [
        'connection' => 'testing',
        'host' => 'localhost',
        'dbname' => 'framework_testing',
        'user' => 'root',
        'password' => '',
        'log_queries' => true
    ],
    
    'cache' => [
        'driver' => 'memory',
        'ttl' => 60 // 1 minute for testing
    ],
    
    'session' => [
        'driver' => 'memory',
        'secure' => false,
        'lifetime' => 60
    ],
    
    'mail' => [
        'driver' => 'array', // Store emails in memory for testing
    ],
    
    'security' => [
        'strict_mode' => false,
        'rate_limit_enabled' => false,
        'csrf_required' => false
    ],
    
    'testing' => [
        'reset_database' => true,
        'seed_data' => true,
        'mock_external_apis' => true
    ]
];