<?php

namespace App;

class Security
{
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token HTML input
     */
    public static function csrfField()
    {
        $token = self::generateCsrfToken();
        $name = Environment::get('CSRF_TOKEN_NAME', '_token');
        
        return '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Sanitize input to prevent XSS
     */
    public static function sanitizeInput($input, $allowHtml = false)
    {
        if (is_array($input)) {
            return array_map(function($item) use ($allowHtml) {
                return self::sanitizeInput($item, $allowHtml);
            }, $input);
        }
        
        if (!$allowHtml) {
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
        
        // If HTML is allowed, use a whitelist approach
        return strip_tags($input, '<p><br><strong><em><u><a><ul><ol><li>');
    }
    
    /**
     * Generate secure random string
     */
    public static function generateRandomString($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Hash password securely
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if request is from same origin (CSRF protection)
     */
    public static function checkOrigin()
    {
        if (!isset($_SERVER['HTTP_REFERER'])) {
            return false;
        }
        
        $refererHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        $currentHost = $_SERVER['HTTP_HOST'];
        
        return $refererHost === $currentHost;
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $rateLimitKey = 'rate_limit_' . $key;
        $now = time();
        
        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = ['count' => 1, 'time' => $now];
            return true;
        }
        
        $rateData = $_SESSION[$rateLimitKey];
        
        // Reset if time window has passed
        if ($now - $rateData['time'] > $timeWindow) {
            $_SESSION[$rateLimitKey] = ['count' => 1, 'time' => $now];
            return true;
        }
        
        // Check if limit exceeded
        if ($rateData['count'] >= $maxAttempts) {
            return false;
        }
        
        // Increment counter
        $_SESSION[$rateLimitKey]['count']++;
        return true;
    }
    
    /**
     * Get time until rate limit resets
     */
    public static function getRateLimitReset($key, $timeWindow = 300)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $rateLimitKey = 'rate_limit_' . $key;
        
        if (!isset($_SESSION[$rateLimitKey])) {
            return 0;
        }
        
        $rateData = $_SESSION[$rateLimitKey];
        $timeLeft = $timeWindow - (time() - $rateData['time']);
        
        return max(0, $timeLeft);
    }
    
    /**
     * Validate file upload security
     */
    public static function validateFileUpload($file)
    {
        $errors = [];
        
        // Check if file was uploaded
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = 'File is too large';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = 'File upload was interrupted';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = 'No file was uploaded';
                    break;
                default:
                    $errors[] = 'File upload failed';
            }
            return $errors;
        }
        
        // Check file size
        $maxSize = Environment::get('MAX_UPLOAD_SIZE', 2048) * 1024; // Convert KB to bytes
        if ($file['size'] > $maxSize) {
            $errors[] = 'File is too large';
        }
        
        // Check file type
        $allowedTypes = explode(',', Environment::get('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf'));
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExtension, $allowedTypes)) {
            $errors[] = 'File type not allowed';
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimeTypes = [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            $errors[] = 'Invalid file format';
        }
        
        return $errors;
    }
    
    /**
     * Encrypt sensitive data
     */
    public static function encrypt($data)
    {
        $key = Environment::get('APP_KEY');
        if (empty($key)) {
            throw new \Exception('APP_KEY not set in environment');
        }
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    public static function decrypt($encryptedData)
    {
        $key = Environment::get('APP_KEY');
        if (empty($key)) {
            throw new \Exception('APP_KEY not set in environment');
        }
        
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = [])
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        error_log('SECURITY: ' . json_encode($logEntry));
    }
    
    /**
     * Check if IP is in whitelist
     */
    public static function isIpWhitelisted($ip, $whitelist = [])
    {
        if (empty($whitelist)) {
            return true; // No whitelist means all IPs allowed
        }
        
        foreach ($whitelist as $allowedIp) {
            if (self::matchIpPattern($ip, $allowedIp)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Match IP against pattern (supports CIDR notation)
     */
    protected static function matchIpPattern($ip, $pattern)
    {
        if ($ip === $pattern) {
            return true;
        }
        
        // Handle CIDR notation
        if (strpos($pattern, '/') !== false) {
            list($subnet, $mask) = explode('/', $pattern);
            return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
        }
        
        // Handle wildcard patterns
        if (strpos($pattern, '*') !== false) {
            $pattern = str_replace('*', '.*', $pattern);
            return preg_match('/^' . $pattern . '$/', $ip);
        }
        
        return false;
    }
}