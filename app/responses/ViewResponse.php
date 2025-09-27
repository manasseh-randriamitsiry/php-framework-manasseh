<?php

namespace App\Responses;

use App\Response;

class ViewResponse extends Response
{
    protected $viewPath;
    protected $data;
    
    public function __construct($viewPath, $data = [], $statusCode = 200, $headers = [])
    {
        $this->viewPath = $viewPath;
        $this->data = $data;
        
        // Set default content type
        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'text/html; charset=UTF-8';
        }
        
        parent::__construct('', $statusCode, $headers);
    }
    
    /**
     * Render the view
     */
    protected function sendContent()
    {
        $content = $this->renderView();
        echo $content;
    }
    
    /**
     * Render view content
     */
    protected function renderView()
    {
        $viewFile = $this->getViewFile();
        
        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$viewFile}");
        }
        
        // Extract data to variables
        if (is_array($this->data)) {
            extract($this->data);
        }
        
        // Include security helpers
        $csrf_token = \App\Security::generateCsrfToken();
        $csrf_field = \App\Security::csrfField();
        
        // Start output buffering
        ob_start();
        
        try {
            include $viewFile;
            return ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw new \Exception("Error rendering view: " . $e->getMessage());
        }
    }
    
    /**
     * Get full path to view file
     */
    protected function getViewFile()
    {
        $basePath = __DIR__ . '/../../views/';
        $viewPath = str_replace('.', '/', $this->viewPath);
        
        // Try .php extension first
        $phpFile = $basePath . $viewPath . '.php';
        if (file_exists($phpFile)) {
            return $phpFile;
        }
        
        // Try without extension
        $noExtFile = $basePath . $viewPath;
        if (file_exists($noExtFile)) {
            return $noExtFile;
        }
        
        return $phpFile; // Return expected path for error message
    }
    
    /**
     * Add data to view
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        
        return $this;
    }
    
    /**
     * Get view data
     */
    public function getData()
    {
        return $this->data;
    }
    
    /**
     * Get view path
     */
    public function getViewPath()
    {
        return $this->viewPath;
    }
}