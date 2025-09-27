<?php
use App\Router;
use App\Request;
use App\Environment;
use App\Security;

// Load environment configuration
Environment::load();

// Start session with security settings
session_start([
    'cookie_lifetime' => Environment::get('SESSION_LIFETIME', 120) * 60,
    'cookie_secure' => Environment::get('APP_ENV') === 'production',
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);

// Create database tables (migrations)
try {
    CreateClient::createClientTable(connect());
    CreateVirement::createVirementTable(connect());
    CreateAuditVirement::createAuditVirementTable(connect());
} catch (Exception $e) {
    if (Environment::isDebug()) {
        die('Migration error: ' . $e->getMessage());
    } else {
        die('Database initialization failed');
    }
}

// Handle the request
try {
    Router::load("routes.php")
        ->show(Request::uri(), Request::method());
} catch (Exception $e) {
    if (Environment::isDebug()) {
        dd($e);
    } else {
        abort(500, 'Application error');
    }
}
