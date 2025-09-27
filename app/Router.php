<?php
namespace App;

class Router
{
    protected $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => [],
        'PATCH' => []
    ];

    protected $middleware = [];
    protected $globalMiddleware = [];
    protected $routeParameters = [];

    /**
     * Handle the incoming request and execute the appropriate route
     */
    public function show($uri, $method)
    {
        try {
            // Execute global middleware first
            $this->executeGlobalMiddleware();
            
            // Find matching route
            $route = $this->findRoute($uri, $method);
            
            if ($route) {
                // Execute route-specific middleware
                if (isset($this->middleware[$method][$uri])) {
                    $this->executeMiddleware($this->middleware[$method][$uri]);
                }
                
                return $this->callMethod($route['controller'], $route['action'], $route['parameters']);
            }
            
            throw new \Exception("Route not found: {$method} {$uri}", 404);
            
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Find a matching route for the given URI and method
     */
    protected function findRoute($uri, $method)
    {
        if (!isset($this->routes[$method])) {
            return null;
        }
        
        // Check for exact match first
        if (array_key_exists($uri, $this->routes[$method])) {
            return [
                'controller' => explode('@', $this->routes[$method][$uri])[0],
                'action' => explode('@', $this->routes[$method][$uri])[1],
                'parameters' => []
            ];
        }
        
        // Check for parametric routes
        foreach ($this->routes[$method] as $routePattern => $controller) {
            $parameters = $this->matchRoute($routePattern, $uri);
            if ($parameters !== false) {
                return [
                    'controller' => explode('@', $controller)[0],
                    'action' => explode('@', $controller)[1],
                    'parameters' => $parameters
                ];
            }
        }
        
        return null;
    }

    /**
     * Match route pattern with parameters
     */
    protected function matchRoute($pattern, $uri)
    {
        // Convert route pattern to regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches); // Remove full match
            return $matches;
        }
        
        return false;
    }

    /**
     * Call the controller method with improved error handling
     */
    public function callMethod($controller, $action, $parameters = [])
    {
        try {
            $controllerClass = "App\\Controllers\\{$controller}";
            
            if (!class_exists($controllerClass)) {
                throw new \Exception("Controller {$controllerClass} not found", 500);
            }
            
            $controllerInstance = new $controllerClass;
            
            if (!method_exists($controllerInstance, $action)) {
                throw new \Exception("Method {$action} not found in {$controllerClass}", 500);
            }
            
            // Call method with parameters
            return call_user_func_array([$controllerInstance, $action], $parameters);
            
        } catch (\Exception $e) {
            throw new \Exception("Error executing controller: " . $e->getMessage(), 500);
        }
    }

    /**
     * Add middleware to a specific route
     */
    public function middleware($middleware, $method = null, $uri = null)
    {
        if ($method && $uri) {
            $this->middleware[$method][$uri][] = $middleware;
        } else {
            $this->globalMiddleware[] = $middleware;
        }
        return $this;
    }

    /**
     * Execute global middleware
     */
    protected function executeGlobalMiddleware()
    {
        foreach ($this->globalMiddleware as $middleware) {
            $this->executeMiddleware([$middleware]);
        }
    }

    /**
     * Execute middleware stack
     */
    protected function executeMiddleware($middlewareStack)
    {
        foreach ($middlewareStack as $middleware) {
            if (is_callable($middleware)) {
                $middleware();
            } elseif (class_exists($middleware)) {
                $middlewareInstance = new $middleware;
                if (method_exists($middlewareInstance, 'handle')) {
                    $middlewareInstance->handle();
                }
            }
        }
    }

    /**
     * Handle exceptions with proper HTTP status codes
     */
    protected function handleException(\Exception $e)
    {
        $statusCode = $e->getCode() ?: 500;
        http_response_code($statusCode);
        
        // In development, show detailed error
        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo "<h1>Error {$statusCode}</h1>";
            echo "<p>{$e->getMessage()}</p>";
            echo "<pre>{$e->getTraceAsString()}</pre>";
        } else {
            // In production, show generic error
            switch ($statusCode) {
                case 404:
                    echo "<h1>404 - Page Not Found</h1>";
                    break;
                case 500:
                    echo "<h1>500 - Internal Server Error</h1>";
                    break;
                default:
                    echo "<h1>Error {$statusCode}</h1>";
            }
        }
    }

    /**
     * Register GET route
     */
    public function get($uri, $controller)
    {
        $this->routes['GET'][$uri] = $controller;
        return $this;
    }

    /**
     * Register POST route
     */
    public function post($uri, $controller)
    {
        $this->routes['POST'][$uri] = $controller;
        return $this;
    }

    /**
     * Register PUT route
     */
    public function put($uri, $controller)
    {
        $this->routes['PUT'][$uri] = $controller;
        return $this;
    }

    /**
     * Register DELETE route
     */
    public function delete($uri, $controller)
    {
        $this->routes['DELETE'][$uri] = $controller;
        return $this;
    }

    /**
     * Register PATCH route
     */
    public function patch($uri, $controller)
    {
        $this->routes['PATCH'][$uri] = $controller;
        return $this;
    }

    /**
     * Load routes from file
     */
    public static function load($file)
    {
        $router = new static;
        require $file;
        return $router;
    }

    /**
     * Get all registered routes
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Check if route exists
     */
    public function hasRoute($uri, $method)
    {
        return isset($this->routes[$method][$uri]);
    }
}
