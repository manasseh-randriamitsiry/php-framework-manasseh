<?php

namespace App;

class Request
{
    protected static $instance;
    protected $data = [];
    protected $files = [];
    protected $headers = [];
    
    /**
     * Singleton pattern for request instance
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new static();
        }
        return self::$instance;
    }
    
    public function __construct()
    {
        $this->parseRequest();
    }
    
    /**
     * Parse incoming request data
     */
    protected function parseRequest()
    {
        // Parse headers
        $this->headers = getallheaders() ?: [];
        
        // Parse files
        $this->files = $_FILES ?: [];
        
        // Parse data based on content type
        $contentType = $this->header('Content-Type', '');
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $this->data = json_decode($input, true) ?: [];
        } else {
            $this->data = array_merge($_GET, $_POST);
        }
    }
    
    /**
     * Get the request URI
     */
    public static function uri()
    {
        return trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    }
    
    /**
     * Get the request method
     */
    public static function method()
    {
        // Check for method override
        if (isset($_POST['_method'])) {
            return strtoupper($_POST['_method']);
        }
        
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Get all request values (deprecated - use input() instead)
     */
    public static function values()
    {
        return self::getInstance()->all();
    }
    
    /**
     * Get all input data
     */
    public function all()
    {
        return $this->data;
    }
    
    /**
     * Get input value with optional default and validation
     */
    public function input($key, $default = null, $filter = null)
    {
        $value = $this->data[$key] ?? $default;
        
        if ($filter && $value !== null) {
            $value = $this->applyFilter($value, $filter);
        }
        
        return $value;
    }
    
    /**
     * Get input value statically
     */
    public static function get($key, $default = null, $filter = null)
    {
        return self::getInstance()->input($key, $default, $filter);
    }
    
    /**
     * Check if input key exists
     */
    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }
    
    /**
     * Get only specified keys
     */
    public function only($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $result = [];
        
        foreach ($keys as $key) {
            if ($this->has($key)) {
                $result[$key] = $this->input($key);
            }
        }
        
        return $result;
    }
    
    /**
     * Get all except specified keys
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $result = $this->all();
        
        foreach ($keys as $key) {
            unset($result[$key]);
        }
        
        return $result;
    }
    
    /**
     * Get header value
     */
    public function header($name, $default = null)
    {
        $name = $this->normalizeHeaderName($name);
        return $this->headers[$name] ?? $default;
    }
    
    /**
     * Get all headers
     */
    public function headers()
    {
        return $this->headers;
    }
    
    /**
     * Get file information
     */
    public function file($key)
    {
        return $this->files[$key] ?? null;
    }
    
    /**
     * Check if request has file
     */
    public function hasFile($key)
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }
    
    /**
     * Get client IP address
     */
    public function ip()
    {
        $keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Get user agent
     */
    public function userAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Check if request is AJAX
     */
    public function isAjax()
    {
        return strtolower($this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }
    
    /**
     * Check if request is secure (HTTPS)
     */
    public function isSecure()
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
        );
    }
    
    /**
     * Validate input data
     */
    public function validate($rules)
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $this->input($field);
            $fieldRules = explode('|', $rule);
            
            foreach ($fieldRules as $fieldRule) {
                $error = $this->validateField($field, $value, $fieldRule);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate individual field
     */
    protected function validateField($field, $value, $rule)
    {
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleValue = $ruleParts[1] ?? null;
        
        switch ($ruleName) {
            case 'required':
                if (empty($value)) {
                    return "The {$field} field is required.";
                }
                break;
                
            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "The {$field} must be a valid email address.";
                }
                break;
                
            case 'min':
                if (strlen($value) < $ruleValue) {
                    return "The {$field} must be at least {$ruleValue} characters.";
                }
                break;
                
            case 'max':
                if (strlen($value) > $ruleValue) {
                    return "The {$field} may not be greater than {$ruleValue} characters.";
                }
                break;
                
            case 'numeric':
                if ($value && !is_numeric($value)) {
                    return "The {$field} must be a number.";
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Apply filter to value
     */
    protected function applyFilter($value, $filter)
    {
        switch ($filter) {
            case 'string':
                return filter_var($value, FILTER_SANITIZE_STRING);
            case 'email':
                return filter_var($value, FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var($value, FILTER_SANITIZE_URL);
            default:
                return $value;
        }
    }
    
    /**
     * Normalize header name
     */
    protected function normalizeHeaderName($name)
    {
        // Convert to standard format (Title-Case)
        return str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
    }
    
    /**
     * Sanitize input data to prevent XSS
     */
    public function sanitize($data = null)
    {
        if ($data === null) {
            $data = $this->all();
        }
        
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get request URL
     */
    public function url()
    {
        $protocol = $this->isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        return $protocol . '://' . $host . $uri;
    }
}