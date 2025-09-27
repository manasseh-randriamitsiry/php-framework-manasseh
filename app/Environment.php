<?php

namespace App;

class Environment
{
    protected static $variables = [];
    protected static $loaded = false;
    
    /**
     * Load environment variables from .env file
     */
    public static function load($path = null)
    {
        if (self::$loaded) {
            return;
        }
        
        $envFile = $path ?: __DIR__ . '/../.env';
        
        if (!file_exists($envFile)) {
            // Try .env.example as fallback
            $envFile = __DIR__ . '/../.env.example';
            if (!file_exists($envFile)) {
                throw new \Exception('.env file not found. Please create one from .env.example');
            }
        }
        
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) {
                continue; // Skip comments
            }
            
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }
                
                // Convert boolean strings
                if (strtolower($value) === 'true') {
                    $value = true;
                } elseif (strtolower($value) === 'false') {
                    $value = false;
                } elseif (strtolower($value) === 'null') {
                    $value = null;
                } elseif (is_numeric($value)) {
                    $value = is_float($value) ? (float) $value : (int) $value;
                }
                
                self::$variables[$name] = $value;
                
                // Set as PHP environment variable
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                    putenv($name . '=' . $value);
                }
            }
        }
        
        self::$loaded = true;
        
        // Set global constants
        self::setGlobalConstants();
    }
    
    /**
     * Get environment variable
     */
    public static function get($key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$variables[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }
    
    /**
     * Set environment variable
     */
    public static function set($key, $value)
    {
        self::$variables[$key] = $value;
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
    
    /**
     * Check if environment variable exists
     */
    public static function has($key)
    {
        return isset(self::$variables[$key]) || isset($_ENV[$key]) || getenv($key) !== false;
    }
    
    /**
     * Get all environment variables
     */
    public static function all()
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return array_merge($_ENV, self::$variables);
    }
    
    /**
     * Check if environment is development
     */
    public static function isDevelopment()
    {
        return self::get('APP_ENV', 'production') === 'development';
    }
    
    /**
     * Check if environment is production
     */
    public static function isProduction()
    {
        return self::get('APP_ENV', 'production') === 'production';
    }
    
    /**
     * Check if debug mode is enabled
     */
    public static function isDebug()
    {
        return self::get('APP_DEBUG', false) === true;
    }
    
    /**
     * Set global constants for backward compatibility
     */
    protected static function setGlobalConstants()
    {
        if (!defined('APP_ENV')) {
            define('APP_ENV', self::get('APP_ENV', 'production'));
        }
        
        if (!defined('APP_DEBUG')) {
            define('APP_DEBUG', self::get('APP_DEBUG', false));
        }
        
        if (!defined('APP_URL')) {
            define('APP_URL', self::get('APP_URL', 'http://localhost'));
        }
        
        if (!defined('APP_KEY')) {
            define('APP_KEY', self::get('APP_KEY', ''));
        }
    }
    
    /**
     * Generate a secure application key
     */
    public static function generateKey($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Validate required environment variables
     */
    public static function validate($required = [])
    {
        $missing = [];
        
        foreach ($required as $key) {
            if (!self::has($key) || empty(self::get($key))) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new \Exception('Missing required environment variables: ' . implode(', ', $missing));
        }
    }
    
    /**
     * Get database configuration from environment
     */
    public static function getDatabaseConfig()
    {
        return [
            'driver' => self::get('DB_CONNECTION', 'mysql'),
            'host' => self::get('DB_HOST', '127.0.0.1'),
            'port' => self::get('DB_PORT', 3306),
            'dbname' => self::get('DB_DATABASE'),
            'user' => self::get('DB_USERNAME'),
            'password' => self::get('DB_PASSWORD'),
            'charset' => self::get('DB_CHARSET', 'utf8mb4')
        ];
    }
}