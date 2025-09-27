<?php

namespace App;

class Session
{
    protected static $started = false;
    
    /**
     * Start session with secure configuration
     */
    public static function start($config = [])
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        
        // Default secure session configuration
        $defaultConfig = [
            'cookie_lifetime' => Environment::get('SESSION_LIFETIME', 120) * 60,
            'cookie_secure' => Environment::get('APP_ENV') === 'production',
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => 1,
            'use_only_cookies' => 1
        ];
        
        $config = array_merge($defaultConfig, $config);
        
        // Start session with configuration
        session_start($config);
        
        // Set IP address for security
        if (!isset($_SESSION['ip_address'])) {
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        
        // Set user agent for security
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        self::$started = true;
        return true;
    }
    
    /**
     * Get session value
     */
    public static function get($key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Set session value
     */
    public static function set($key, $value)
    {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Check if session key exists
     */
    public static function has($key)
    {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session key
     */
    public static function remove($key)
    {
        self::start();
        unset($_SESSION[$key]);
    }
    
    /**
     * Flash message (store for next request only)
     */
    public static function flash($key, $value = null)
    {
        if ($value === null) {
            // Get flash message
            $message = self::get('flash_' . $key);
            self::remove('flash_' . $key);
            return $message;
        } else {
            // Set flash message
            self::set('flash_' . $key, $value);
        }
    }
    
    /**
     * Set flash success message
     */
    public static function flashSuccess($message)
    {
        self::flash('success', $message);
    }
    
    /**
     * Set flash error message
     */
    public static function flashError($message)
    {
        self::flash('error', $message);
    }
    
    /**
     * Set flash warning message
     */
    public static function flashWarning($message)
    {
        self::flash('warning', $message);
    }
    
    /**
     * Set flash info message
     */
    public static function flashInfo($message)
    {
        self::flash('info', $message);
    }
    
    /**
     * Get flash success message
     */
    public static function getSuccess()
    {
        return self::flash('success');
    }
    
    /**
     * Get flash error message
     */
    public static function getError()
    {
        return self::flash('error');
    }
    
    /**
     * Get flash warning message
     */
    public static function getWarning()
    {
        return self::flash('warning');
    }
    
    /**
     * Get flash info message
     */
    public static function getInfo()
    {
        return self::flash('info');
    }
    
    /**
     * Store old input for form resubmission
     */
    public static function flashInput($input = null)
    {
        if ($input === null) {
            $input = $_POST;
        }
        
        self::set('old_input', $input);
    }
    
    /**
     * Get old input value
     */
    public static function getOldInput($key = null, $default = null)
    {
        $oldInput = self::get('old_input', []);
        
        if ($key === null) {
            // Return all old input
            self::remove('old_input');
            return $oldInput;
        }
        
        // Return specific key
        return $oldInput[$key] ?? $default;
    }
    
    /**
     * Store validation errors
     */
    public static function flashErrors($errors)
    {
        self::set('validation_errors', $errors);
    }
    
    /**
     * Get validation errors
     */
    public static function getErrors()
    {
        $errors = self::get('validation_errors', []);
        self::remove('validation_errors');
        return $errors;
    }
    
    /**
     * Get specific field error
     */
    public static function getFieldError($field)
    {
        $errors = self::get('validation_errors', []);
        return $errors[$field] ?? null;
    }
    
    /**
     * Check if field has errors
     */
    public static function hasError($field)
    {
        $errors = self::get('validation_errors', []);
        return isset($errors[$field]) && !empty($errors[$field]);
    }
    
    /**
     * Get all session data
     */
    public static function all()
    {
        self::start();
        return $_SESSION;
    }
    
    /**
     * Clear all session data
     */
    public static function clear()
    {
        self::start();
        $_SESSION = [];
    }
    
    /**
     * Regenerate session ID
     */
    public static function regenerate($deleteOld = true)
    {
        self::start();
        return session_regenerate_id($deleteOld);
    }
    
    /**
     * Destroy session
     */
    public static function destroy()
    {
        self::start();
        
        // Clear session data
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy session
        session_destroy();
        self::$started = false;
    }
    
    /**
     * Check if session is valid
     */
    public static function isValid()
    {
        self::start();
        
        // Check session timeout
        if (self::has('last_activity')) {
            $sessionLifetime = Environment::get('SESSION_LIFETIME', 120) * 60;
            if (time() - self::get('last_activity') > $sessionLifetime) {
                return false;
            }
        }
        
        // Check IP address
        if (self::has('ip_address')) {
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            if (self::get('ip_address') !== $currentIp) {
                return false;
            }
        }
        
        // Check user agent
        if (self::has('user_agent')) {
            $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            if (self::get('user_agent') !== $currentAgent) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get session ID
     */
    public static function getId()
    {
        self::start();
        return session_id();
    }
    
    /**
     * Set session ID
     */
    public static function setId($id)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new \Exception('Cannot set session ID after session has started');
        }
        
        session_id($id);
    }
    
    /**
     * Get session name
     */
    public static function getName()
    {
        return session_name();
    }
    
    /**
     * Set session name
     */
    public static function setName($name)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            throw new \Exception('Cannot set session name after session has started');
        }
        
        session_name($name);
    }
}