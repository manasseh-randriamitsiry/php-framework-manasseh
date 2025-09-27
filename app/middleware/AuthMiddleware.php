<?php

namespace App\Middleware;

use App\Security;

class AuthMiddleware extends BaseMiddleware
{
    /**
     * Handle authentication check
     */
    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            $this->handleUnauthenticated();
            return false;
        }
        
        // Check if session is still valid
        if (!$this->isSessionValid()) {
            $this->handleInvalidSession();
            return false;
        }
        
        // Rate limiting for authenticated users
        if (!$this->checkRateLimit()) {
            $this->handleRateLimitExceeded();
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if session is valid
     */
    protected function isSessionValid()
    {
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $sessionLifetime = Environment::get('SESSION_LIFETIME', 120) * 60;
            if (time() - $_SESSION['last_activity'] > $sessionLifetime) {
                return false;
            }
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Check IP address (optional security measure)
        if (isset($_SESSION['ip_address'])) {
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if ($_SESSION['ip_address'] !== $currentIp) {
                // IP changed - potential session hijacking
                Security::logSecurityEvent('session_ip_mismatch', [
                    'old_ip' => $_SESSION['ip_address'],
                    'new_ip' => $currentIp,
                    'user_id' => $_SESSION['user_id'] ?? 'unknown'
                ]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check rate limit for authenticated requests
     */
    protected function checkRateLimit()
    {
        $userId = $_SESSION['user_id'] ?? 'guest';
        $key = 'auth_user_' . $userId;
        
        return Security::checkRateLimit($key, 100, 60); // 100 requests per minute
    }
    
    /**
     * Handle unauthenticated user
     */
    protected function handleUnauthenticated()
    {
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'Authentication required'], 401);
        } else {
            // Store intended URL for redirect after login
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/';
            $this->redirectWithError('/login', 'Please log in to access this page');
        }
    }
    
    /**
     * Handle invalid session
     */
    protected function handleInvalidSession()
    {
        // Clear session data
        session_unset();
        session_destroy();
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'Session expired'], 401);
        } else {
            session_start(); // Start new session for flash message
            $this->redirectWithError('/login', 'Your session has expired. Please log in again.');
        }
    }
    
    /**
     * Handle rate limit exceeded
     */
    protected function handleRateLimitExceeded()
    {
        $userId = $_SESSION['user_id'] ?? 'unknown';
        Security::logSecurityEvent('rate_limit_exceeded', [
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'Too many requests'], 429);
        } else {
            $this->abort(429, 'Too many requests. Please try again later.');
        }
    }
    
    /**
     * Check if request is AJAX
     */
    protected function isAjaxRequest()
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }
    
    /**
     * Send JSON response
     */
    protected function jsonResponse($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}