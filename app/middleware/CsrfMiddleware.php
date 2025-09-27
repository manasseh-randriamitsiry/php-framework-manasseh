<?php

namespace App\Middleware;

use App\Security;
use App\Environment;

class CsrfMiddleware extends BaseMiddleware
{
    /**
     * Handle CSRF token validation
     */
    public function handle()
    {
        // Skip for GET requests (they don't modify data)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        }
        
        // Skip for specific routes if configured
        if ($this->shouldSkip()) {
            return true;
        }
        
        // Get token from request
        $tokenName = Environment::get('CSRF_TOKEN_NAME', '_token');
        $token = $_POST[$tokenName] ?? $_GET[$tokenName] ?? $this->getTokenFromHeader();
        
        if (empty($token)) {
            $this->handleMissingToken();
            return false;
        }
        
        if (!Security::verifyCsrfToken($token)) {
            $this->handleInvalidToken();
            return false;
        }
        
        return true;
    }
    
    /**
     * Get CSRF token from headers (for AJAX requests)
     */
    protected function getTokenFromHeader()
    {
        $headers = getallheaders();
        return $headers['X-CSRF-TOKEN'] ?? $headers['X-Requested-With-Token'] ?? '';
    }
    
    /**
     * Handle missing CSRF token
     */
    protected function handleMissingToken()
    {
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'CSRF token missing'], 419);
        } else {
            $this->abort(419, 'CSRF token missing');
        }
    }
    
    /**
     * Handle invalid CSRF token
     */
    protected function handleInvalidToken()
    {
        // Log security event
        Security::logSecurityEvent('csrf_token_mismatch', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown'
        ]);
        
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['error' => 'CSRF token mismatch'], 419);
        } else {
            $this->abort(419, 'CSRF token mismatch');
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
    
    /**
     * Check if middleware should be skipped for current route
     */
    protected function shouldSkip()
    {
        $skipRoutes = [
            '/api/webhook',
            '/health',
            '/status'
        ];
        
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        return in_array($currentUri, $skipRoutes);
    }
}