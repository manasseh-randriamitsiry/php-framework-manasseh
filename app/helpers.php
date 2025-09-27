<?php
use App\App;
use App\Environment;
use App\Security;
use App\Session;
use App\Validator;
use App\Responses\JsonResponse;
use App\Responses\ViewResponse;
use App\Responses\RedirectResponse;
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
        return new ViewResponse($view, $data);
    }
}

if (!function_exists('json')){
    function json($data = [], $status = 200) {
        return new JsonResponse($data, $status);
    }
}

if (!function_exists('redirect')){
    function redirect($url, $statusCode = 302) {
        return new RedirectResponse($url, $statusCode);
    }
}

if (!function_exists('back')){
    function back() {
        return RedirectResponse::back();
    }
}

if (!function_exists('old')){
    function old($key, $default = '') {
        return Session::getOldInput($key, $default);
    }
}

if (!function_exists('flash')){
    function flash($key, $default = null) {
        return Session::flash($key, $default);
    }
}

if (!function_exists('session')){
    function session($key, $default = null) {
        return Session::get($key, $default);
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
        return \App\Config::get($key, $default);
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

if (!function_exists('validate')){
    function validate($data, $rules, $messages = []) {
        return Validator::validateData($data, $rules, $messages);
    }
}

if (!function_exists('validator')){
    function validator($data, $rules, $messages = []) {
        return Validator::make($data, $rules, $messages);
    }
}

if (!function_exists('response')){
    function response($content = '', $status = 200, $headers = []) {
        $response = new class($content, $status, $headers) extends \App\Response {
            // Concrete implementation of abstract Response class
        };
        return $response;
    }
}

if (!function_exists('request')){
    function request() {
        return \App\Request::getInstance();
    }
}

if (!function_exists('auth_user')){
    function auth_user() {
        return Session::get('user_id');
    }
}

if (!function_exists('is_logged_in')){
    function is_logged_in() {
        return Session::has('user_id') && !empty(Session::get('user_id'));
    }
}

if (!function_exists('logout')){
    function logout() {
        Session::destroy();
    }
}

if (!function_exists('login')){
    function login($userId) {
        Session::set('user_id', $userId);
        Session::set('login_time', time());
        Session::regenerate();
    }
}

if (!function_exists('cache')){
    function cache($key, $value = null, $ttl = 3600) {
        if ($value === null) {
            return \App\Cache::get($key);
        }
        return \App\Cache::put($key, $value, $ttl);
    }
}

if (!function_exists('log_info')){
    function log_info($message, $context = []) {
        return \App\Logger::info($message, $context);
    }
}

if (!function_exists('log_error')){
    function log_error($message, $context = []) {
        return \App\Logger::error($message, $context);
    }
}

if (!function_exists('upload')){
    function upload($fieldName, $config = []) {
        $uploader = new \App\FileUpload($config);
        return $uploader->upload($fieldName);
    }
}

if (!function_exists('mail')){
    function mail() {
        return \App\Mail::create();
    }
}

if (!function_exists('migrate')){
    function migrate() {
        return \Database\Migration::migrate();
    }
}

if (!function_exists('rollback')){
    function rollback($steps = 1) {
        return \Database\Migration::rollback($steps);
    }
}