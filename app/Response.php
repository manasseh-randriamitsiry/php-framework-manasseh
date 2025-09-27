<?php

namespace App;

abstract class Response
{
    protected $content;
    protected $statusCode;
    protected $headers;
    
    public function __construct($content = '', $statusCode = 200, $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }
    
    /**
     * Send the response
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
        return $this;
    }
    
    /**
     * Send headers
     */
    protected function sendHeaders()
    {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
    }
    
    /**
     * Send content
     */
    protected function sendContent()
    {
        echo $this->content;
    }
    
    /**
     * Set status code
     */
    public function setStatusCode($code)
    {
        $this->statusCode = $code;
        return $this;
    }
    
    /**
     * Set header
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }
    
    /**
     * Get status code
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    
    /**
     * Get headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * Get content
     */
    public function getContent()
    {
        return $this->content;
    }
}