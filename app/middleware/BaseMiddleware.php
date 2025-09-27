<?php

namespace App\Middleware;

abstract class BaseMiddleware
{
    /**
     * Handle the middleware request
     */
    abstract public function handle();
    
    /**
     * Check if middleware should be skipped
     */
    protected function shouldSkip()
    {
        return false;
    }
    
    /**
     * Redirect with error message
     */
    protected function redirectWithError($url, $message)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['error_message'] = $message;
        header("Location: {$url}");
        exit();
    }
    
    /**
     * Abort with HTTP status
     */
    protected function abort($code, $message = '')
    {
        http_response_code($code);
        
        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo "<h1>Error {$code}</h1>";
            if ($message) {
                echo "<p>{$message}</p>";
            }
        } else {
            switch ($code) {
                case 401:
                    echo "<h1>401 - Unauthorized</h1>";
                    break;
                case 403:
                    echo "<h1>403 - Forbidden</h1>";
                    break;
                case 419:
                    echo "<h1>419 - CSRF Token Mismatch</h1>";
                    break;
                default:
                    echo "<h1>Error {$code}</h1>";
            }
        }
        exit();
    }
}