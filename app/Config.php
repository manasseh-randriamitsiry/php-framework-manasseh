<?php

namespace App;

class Config
{
    protected static $config = [];
    protected static $loaded = false;
    protected static $environment = 'development';
    
    /**
     * Load configuration from files
     */
    public static function load()
    {
        if (self::$loaded) {
            return;
        }
        
        // Load environment first
        Environment::load();
        self::$environment = Environment::get('APP_ENV', 'development');
        
        // Load base configuration
        self::loadConfigFile('config.php');
        
        // Load environment-specific configuration
        $envConfigFile = "config." . self::$environment . ".php";
        if (file_exists($envConfigFile)) {
            self::loadConfigFile($envConfigFile);
        }
        
        self::$loaded = true;
    }
    
    /**
     * Load configuration from a file
     */
    protected static function loadConfigFile($filename)
    {
        $filepath = __DIR__ . '/../' . $filename;
        
        if (file_exists($filepath)) {
            $config = require $filepath;
            if (is_array($config)) {
                self::$config = array_merge_recursive(self::$config, $config);
            }
        }
    }
    
    /**
     * Get configuration value
     */
    public static function get($key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::getArrayValue(self::$config, $key, $default);
    }
    
    /**
     * Set configuration value
     */
    public static function set($key, $value)
    {
        if (!self::$loaded) {
            self::load();
        }
        
        self::setArrayValue(self::$config, $key, $value);
    }
    
    /**
     * Check if configuration key exists
     */
    public static function has($key)
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::getArrayValue(self::$config, $key) !== null;
    }
    
    /**
     * Get all configuration
     */
    public static function all()
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$config;
    }
    
    /**
     * Get configuration for specific section
     */
    public static function section($section)
    {
        return self::get($section, []);
    }
    
    /**
     * Get array value using dot notation
     */
    protected static function getArrayValue($array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }
        
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        if (strpos($key, '.') === false) {
            return $default;
        }
        
        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }
        
        return $array;
    }
    
    /**
     * Set array value using dot notation
     */
    protected static function setArrayValue(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }
        
        $keys = explode('.', $key);
        
        while (count($keys) > 1) {
            $key = array_shift($keys);
            
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }
            
            $array = &$array[$key];
        }
        
        $array[array_shift($keys)] = $value;
    }
    
    /**
     * Get database configuration
     */
    public static function database($connection = 'default')
    {
        $databases = self::get('database', []);
        
        if ($connection === 'default') {
            return $databases;
        }
        
        return $databases[$connection] ?? null;
    }
    
    /**
     * Get cache configuration
     */
    public static function cache()
    {
        return self::get('cache', [
            'driver' => 'file',
            'ttl' => 3600,
            'path' => 'cache/'
        ]);
    }
    
    /**
     * Get mail configuration
     */
    public static function mail()
    {
        return self::get('mail', [
            'driver' => Environment::get('MAIL_DRIVER', 'mail'),
            'host' => Environment::get('MAIL_HOST', 'localhost'),
            'port' => Environment::get('MAIL_PORT', 587),
            'username' => Environment::get('MAIL_USERNAME', ''),
            'password' => Environment::get('MAIL_PASSWORD', ''),
            'encryption' => Environment::get('MAIL_ENCRYPTION', 'tls'),
            'from_address' => Environment::get('MAIL_FROM_ADDRESS', 'noreply@localhost'),
            'from_name' => Environment::get('MAIL_FROM_NAME', 'Application')
        ]);
    }
    
    /**
     * Get security configuration
     */
    public static function security()
    {
        return self::get('security', [
            'csrf_token_name' => Environment::get('CSRF_TOKEN_NAME', '_token'),
            'session_lifetime' => Environment::get('SESSION_LIFETIME', 120),
            'password_min_length' => 8,
            'max_login_attempts' => 5,
            'lockout_duration' => 900 // 15 minutes
        ]);
    }
    
    /**
     * Get upload configuration
     */
    public static function upload()
    {
        return self::get('upload', [
            'max_size' => Environment::get('MAX_UPLOAD_SIZE', 2048),
            'allowed_types' => explode(',', Environment::get('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf')),
            'path' => 'uploads/',
            'url' => '/uploads/'
        ]);
    }
    
    /**
     * Get logging configuration
     */
    public static function logging()
    {
        return self::get('logging', [
            'level' => Environment::get('LOG_LEVEL', 'info'),
            'path' => 'logs/',
            'max_file_size' => 10485760, // 10MB
            'max_files' => 5
        ]);
    }
    
    /**
     * Get current environment
     */
    public static function environment()
    {
        return self::$environment;
    }
    
    /**
     * Check if in development environment
     */
    public static function isDevelopment()
    {
        return self::$environment === 'development';
    }
    
    /**
     * Check if in production environment
     */
    public static function isProduction()
    {
        return self::$environment === 'production';
    }
    
    /**
     * Check if in testing environment
     */
    public static function isTesting()
    {
        return self::$environment === 'testing';
    }
    
    /**
     * Get debug mode status
     */
    public static function isDebug()
    {
        return Environment::get('APP_DEBUG', false) === true;
    }
    
    /**
     * Reload configuration
     */
    public static function reload()
    {
        self::$config = [];
        self::$loaded = false;
        self::load();
    }
    
    /**
     * Export configuration as array
     */
    public static function export()
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return [
            'environment' => self::$environment,
            'debug' => self::isDebug(),
            'config' => self::$config
        ];
    }
    
    /**
     * Validate required configuration
     */
    public static function validate($required = [])
    {
        $missing = [];
        
        foreach ($required as $key) {
            if (!self::has($key)) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new \Exception('Missing required configuration: ' . implode(', ', $missing));
        }
        
        return true;
    }
}