<?php

use App\Environment;
use App\Config;

// Load environment variables
Environment::load();

// Validate required environment variables
Environment::validate(['DB_HOST', 'DB_DATABASE', 'DB_USERNAME']);

return [
    'app' => [
        'name' => Environment::get('APP_NAME', 'PHP MVC Framework'),
        'env' => Environment::get('APP_ENV', 'production'),
        'debug' => Environment::get('APP_DEBUG', false),
        'url' => Environment::get('APP_URL', 'http://localhost'),
        'key' => Environment::get('APP_KEY', ''),
        'timezone' => Environment::get('APP_TIMEZONE', 'UTC'),
        'locale' => Environment::get('APP_LOCALE', 'en')
    ],
    
    'database' => [
        'default' => Environment::getDatabaseConfig(),
        'connections' => [
            'mysql' => Environment::getDatabaseConfig(),
            'testing' => [
                'driver' => 'mysql',
                'host' => Environment::get('DB_TEST_HOST', '127.0.0.1'),
                'port' => Environment::get('DB_TEST_PORT', 3306),
                'dbname' => Environment::get('DB_TEST_DATABASE', 'framework_test'),
                'user' => Environment::get('DB_TEST_USERNAME', 'root'),
                'password' => Environment::get('DB_TEST_PASSWORD', ''),
                'charset' => 'utf8mb4'
            ]
        ]
    ],
    
    'cache' => [
        'driver' => Environment::get('CACHE_DRIVER', 'file'),
        'ttl' => Environment::get('CACHE_TTL', 3600),
        'path' => Environment::get('CACHE_PATH', 'cache/'),
        'prefix' => Environment::get('CACHE_PREFIX', 'framework_')
    ],
    
    'session' => [
        'driver' => Environment::get('SESSION_DRIVER', 'file'),
        'lifetime' => Environment::get('SESSION_LIFETIME', 120),
        'secure' => Environment::get('APP_ENV') === 'production',
        'httponly' => true,
        'samesite' => 'Strict'
    ],
    
    'security' => [
        'csrf_token_name' => Environment::get('CSRF_TOKEN_NAME', '_token'),
        'session_lifetime' => Environment::get('SESSION_LIFETIME', 120),
        'password_min_length' => 8,
        'max_login_attempts' => 5,
        'lockout_duration' => 900,
        'rate_limit' => [
            'requests' => Environment::get('RATE_LIMIT_REQUESTS', 60),
            'window' => Environment::get('RATE_LIMIT_WINDOW', 60)
        ]
    ],
    
    'upload' => [
        'max_size' => Environment::get('MAX_UPLOAD_SIZE', 2048),
        'allowed_types' => explode(',', Environment::get('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf')),
        'path' => 'uploads/',
        'url' => '/uploads/'
    ],
    
    'mail' => [
        'driver' => Environment::get('MAIL_DRIVER', 'mail'),
        'host' => Environment::get('MAIL_HOST', 'localhost'),
        'port' => Environment::get('MAIL_PORT', 587),
        'username' => Environment::get('MAIL_USERNAME', ''),
        'password' => Environment::get('MAIL_PASSWORD', ''),
        'encryption' => Environment::get('MAIL_ENCRYPTION', 'tls'),
        'from_address' => Environment::get('MAIL_FROM_ADDRESS', 'noreply@localhost'),
        'from_name' => Environment::get('MAIL_FROM_NAME', 'Application')
    ],
    
    'logging' => [
        'level' => Environment::get('LOG_LEVEL', 'info'),
        'path' => Environment::get('LOG_PATH', 'logs/'),
        'max_file_size' => 10485760, // 10MB
        'max_files' => 5,
        'channels' => [
            'app' => 'logs/app.log',
            'security' => 'logs/security.log',
            'mail' => 'logs/mail.log',
            'database' => 'logs/database.log'
        ]
    ]
];
