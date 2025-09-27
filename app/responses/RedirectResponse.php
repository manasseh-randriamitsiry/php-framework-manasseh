<?php

namespace App\Responses;

use App\Response;

class RedirectResponse extends Response
{
    protected $url;
    protected $statusCode = 302;
    
    public function __construct($url, $statusCode = 302, $headers = [])
    {
        $this->url = $url;
        $this->statusCode = $statusCode;
        
        parent::__construct('', $statusCode, $headers);
    }
    
    /**
     * Send redirect headers
     */
    protected function sendHeaders()
    {
        parent::sendHeaders();
        header('Location: ' . $this->url);
    }
    
    /**
     * Send content (empty for redirects)
     */
    protected function sendContent()
    {
        // No content for redirects
    }
    
    /**
     * Create permanent redirect (301)
     */
    public static function permanent($url)
    {
        return new static($url, 301);
    }
    
    /**
     * Create temporary redirect (302)
     */
    public static function temporary($url)
    {
        return new static($url, 302);
    }
    
    /**
     * Create redirect with flash message
     */
    public static function withMessage($url, $message, $type = 'info')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['flash_' . $type] = $message;
        
        return new static($url);
    }
    
    /**
     * Create redirect with success message
     */
    public static function withSuccess($url, $message)
    {
        return self::withMessage($url, $message, 'success');
    }
    
    /**
     * Create redirect with error message
     */
    public static function withError($url, $message)
    {
        return self::withMessage($url, $message, 'error');
    }
    
    /**
     * Create redirect with warning message
     */
    public static function withWarning($url, $message)
    {
        return self::withMessage($url, $message, 'warning');
    }
    
    /**
     * Create redirect with input data (for form resubmission)
     */
    public static function withInput($url, $input = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($input === null) {
            $input = $_POST;
        }
        
        $_SESSION['old_input'] = $input;
        
        return new static($url);
    }
    
    /**
     * Create redirect with errors and input
     */
    public static function withErrors($url, $errors, $input = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['validation_errors'] = $errors;
        
        if ($input === null) {
            $input = $_POST;
        }
        
        $_SESSION['old_input'] = $input;
        
        return new static($url);
    }
    
    /**
     * Create redirect back to previous page
     */
    public static function back()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return new static($referer);
    }
    
    /**
     * Create redirect to intended URL (after login)
     */
    public static function intended($default = '/')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $intended = $_SESSION['intended_url'] ?? $default;
        unset($_SESSION['intended_url']);
        
        return new static($intended);
    }
    
    /**
     * Get redirect URL
     */
    public function getUrl()
    {
        return $this->url;
    }
    
    /**
     * Set redirect URL
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }
}