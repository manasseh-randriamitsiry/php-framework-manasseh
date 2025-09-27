<?php
use App\App;
use App\Environment;
use App\Security;
use Database\Connection;

if (!function_exists('dd')){
    function dd($data){
        echo '<pre>';
        die(var_dump($data));
        echo '</pre>';
    }
}

if (!function_exists('dump')){
    function dump($data){
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}

if (!function_exists('connect')){
    function connect(){
        App::bind('config', require "config.php");
        return Connection::makeConnection(
            App::get('config')['database']
        );
    }
}

if (!function_exists('view')){
    function view($view, $data = null){
        // Extract data to variables if provided
        if ($data && is_array($data)) {
            extract($data);
        }
        
        // Include CSRF token helper
        $csrf_token = Security::generateCsrfToken();
        $csrf_field = Security::csrfField();
        
        require "views/{$view}.php";
    }
}

if (!function_exists('redirect')){
    function redirect($url, $statusCode = 302) {
        header("Location: {$url}", true, $statusCode);
        exit();
    }
}

if (!function_exists('old')){
    function old($key, $default = '') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['old_input'][$key] ?? $default;
    }
}

if (!function_exists('flash')){
    function flash($key, $default = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $value;
        }
        
        return $default;
    }
}

if (!function_exists('session')){
    function session($key, $default = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('csrf_token')){
    function csrf_token() {
        return Security::generateCsrfToken();
    }
}

if (!function_exists('csrf_field')){
    function csrf_field() {
        return Security::csrfField();
    }
}

if (!function_exists('env')){
    function env($key, $default = null) {
        return Environment::get($key, $default);
    }
}

if (!function_exists('config')){
    function config($key, $default = null) {
        App::bind('config', require "config.php");
        $config = App::get('config');
        
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
}

if (!function_exists('sanitize')){
    function sanitize($input, $allowHtml = false) {
        return Security::sanitizeInput($input, $allowHtml);
    }
}

if (!function_exists('asset')){
    function asset($path) {
        $baseUrl = rtrim(env('APP_URL', 'http://localhost'), '/');
        return $baseUrl . '/public/' . ltrim($path, '/');
    }
}

if (!function_exists('url')){
    function url($path = '') {
        $baseUrl = rtrim(env('APP_URL', 'http://localhost'), '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('abort')){
    function abort($statusCode, $message = '') {
        http_response_code($statusCode);
        
        switch ($statusCode) {
            case 404:
                $defaultMessage = 'Page Not Found';
                break;
            case 403:
                $defaultMessage = 'Forbidden';
                break;
            case 500:
                $defaultMessage = 'Internal Server Error';
                break;
            default:
                $defaultMessage = 'Error';
        }
        
        echo "<h1>{$statusCode} - " . ($message ?: $defaultMessage) . "</h1>";
        exit();
    }
}

if (!function_exists('validate_csrf')){
    function validate_csrf($token = null) {
        if ($token === null) {
            $tokenName = env('CSRF_TOKEN_NAME', '_token');
            $token = $_POST[$tokenName] ?? $_GET[$tokenName] ?? '';
        }
        
        if (!Security::verifyCsrfToken($token)) {
            abort(419, 'CSRF token mismatch');
        }
        
        return true;
    }
}