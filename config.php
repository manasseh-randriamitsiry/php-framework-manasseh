<?php

use App\Environment;

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
        'key' => Environment::get('APP_KEY', '')
    ],
    'database' => Environment::getDatabaseConfig(),
    'security' => [
        'csrf_token_name' => Environment::get('CSRF_TOKEN_NAME', '_token'),
        'session_lifetime' => Environment::get('SESSION_LIFETIME', 120)
    ],
    'upload' => [
        'max_size' => Environment::get('MAX_UPLOAD_SIZE', 2048),
        'allowed_types' => Environment::get('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf')
    ]
];
