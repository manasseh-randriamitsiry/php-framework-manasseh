<?php

namespace App\Responses;

use App\Response;

class JsonResponse extends Response
{
    public function __construct($data = [], $statusCode = 200, $headers = [])
    {
        $headers['Content-Type'] = 'application/json';
        
        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON encoding error: ' . json_last_error_msg());
        }
        
        parent::__construct($content, $statusCode, $headers);
    }
    
    /**
     * Create success response
     */
    public static function success($data = [], $message = 'Success')
    {
        return new static([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 200);
    }
    
    /**
     * Create error response
     */
    public static function error($message = 'Error', $statusCode = 400, $errors = [])
    {
        return new static([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }
    
    /**
     * Create validation error response
     */
    public static function validationError($errors, $message = 'Validation failed')
    {
        return new static([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], 422);
    }
    
    /**
     * Create not found response
     */
    public static function notFound($message = 'Resource not found')
    {
        return self::error($message, 404);
    }
    
    /**
     * Create unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        return self::error($message, 401);
    }
    
    /**
     * Create forbidden response
     */
    public static function forbidden($message = 'Forbidden')
    {
        return self::error($message, 403);
    }
    
    /**
     * Create server error response
     */
    public static function serverError($message = 'Internal server error')
    {
        return self::error($message, 500);
    }
}