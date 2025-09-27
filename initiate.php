<?php
use App\Router;
use App\Request;
use App\Environment;
use App\Security;
use App\Session;
use App\Logger;

// Load environment configuration
Environment::load();

// Start secure session
Session::start();

// Initialize logging
Logger::info('Application started', [
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
]);

// Create database tables (migrations)
try {
    CreateClient::createClientTable(connect());
    CreateVirement::createVirementTable(connect());
    CreateAuditVirement::createAuditVirementTable(connect());
    
    Logger::info('Database migrations completed successfully');
} catch (Exception $e) {
    Logger::error('Database migration failed', ['error' => $e->getMessage()]);
    
    if (Environment::isDebug()) {
        die('Migration error: ' . $e->getMessage());
    } else {
        die('Database initialization failed');
    }
}

// Handle the request
try {
    $response = Router::load("routes.php")
        ->show(Request::uri(), Request::method());
    
    // Send response if it's a response object
    if ($response && method_exists($response, 'send')) {
        $response->send();
    }
    
    Logger::info('Request handled successfully');
    
} catch (Exception $e) {
    Logger::error('Request handling failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    if (Environment::isDebug()) {
        dd($e);
    } else {
        abort(500, 'Application error');
    }
}
