<?php

require_once 'core/UrlHelper.php';

/**
 * Router Class - Laravel-style routing for PHP MVC
 * Handles URL routing, middleware, and request dispatching
 */
class Router
{
    private static $routes = [];
    private static $middlewareGroups = [];
    private static $currentMiddleware = [];
    private static $currentPrefix = '';
    
    /**
     * Add GET route
     */
    public static function get($uri, $action)
    {
        return self::addRoute('GET', $uri, $action);
    }
    
    /**
     * Add POST route
     */
    public static function post($uri, $action)
    {
        return self::addRoute('POST', $uri, $action);
    }
    
    /**
     * Add PUT route
     */
    public static function put($uri, $action)
    {
        return self::addRoute('PUT', $uri, $action);
    }
    
    /**
     * Add DELETE route
     */
    public static function delete($uri, $action)
    {
        return self::addRoute('DELETE', $uri, $action);
    }
    
    /**
     * Add route for any HTTP method
     */
    public static function any($uri, $action)
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
        foreach ($methods as $method) {
            self::addRoute($method, $uri, $action);
        }
        return new Route($uri, $action);
    }
    
    /**
     * Add resource routes (RESTful)
     */
    public static function resource($uri, $controller)
    {
        $name = trim($uri, '/');
        $routes = [
            ['GET', $uri, $controller . '@index'],
            ['GET', $uri . '/create', $controller . '@create'],
            ['POST', $uri, $controller . '@store'],
            ['GET', $uri . '/{id}', $controller . '@show'],
            ['GET', $uri . '/{id}/edit', $controller . '@edit'],
            ['PUT', $uri . '/{id}', $controller . '@update'],
            ['DELETE', $uri . '/{id}', $controller . '@destroy'],
        ];
        
        foreach ($routes as $route) {
            self::addRoute($route[0], $route[1], $route[2]);
        }
        
        return new Route($uri, $controller);
    }
    
    /**
     * Group routes with middleware
     */
    public static function middleware($middleware)
    {
        self::$currentMiddleware = is_array($middleware) ? $middleware : [$middleware];
        return new static();
    }
    
    /**
     * Group routes with prefix
     */
    public static function prefix($prefix)
    {
        self::$currentPrefix = trim($prefix, '/');
        return new static();
    }
    
    /**
     * Group routes
     */
    public static function group($callback)
    {
        if (is_callable($callback)) {
            $callback();
        }
        
        // Reset current settings after group
        self::$currentMiddleware = [];
        self::$currentPrefix = '';
        
        return new static();
    }
    
    /**
     * Add route to routes array
     */
    private static function addRoute($method, $uri, $action)
    {
        // Apply current prefix
        if (self::$currentPrefix) {
            $uri = '/' . self::$currentPrefix . '/' . ltrim($uri, '/');
        }
        
        // Clean up URI
        $uri = '/' . trim($uri, '/');
        if ($uri === '/') {
            $uri = '/';
        }
        
        $route = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'middleware' => self::$currentMiddleware,
            'parameters' => []
        ];
        
        self::$routes[] = $route;
        
        return new Route($uri, $action);
    }
    
    /**
     * Dispatch the current request
     */
    public function dispatch()
    {
        $requestUri = $this->getRequestUri();
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        // Debug logging
        error_log("Router::dispatch - Request URI: $requestUri, Method: $requestMethod");
        error_log("Router::dispatch - Available routes count: " . count(self::$routes));

        // Handle method override for forms
        if ($requestMethod === 'POST' && isset($_POST['_method'])) {
            $requestMethod = strtoupper($_POST['_method']);
        }

        // Find matching route
        $matchedRoute = $this->findRoute($requestMethod, $requestUri);

        if (!$matchedRoute) {
            error_log("Router::dispatch - No route found for $requestMethod $requestUri");
            error_log("Router::dispatch - All available routes: " . print_r(self::$routes, true));
            $this->handleNotFound();
            return;
        }

        error_log("Router::dispatch - Matched route: " . print_r($matchedRoute, true));

        // Execute middleware
        if (!$this->executeMiddleware($matchedRoute['middleware'])) {
            error_log("Router::dispatch - Middleware failed");
            return;
        }

        // Execute controller action
        $this->executeAction($matchedRoute);
    }
    
    /**
     * Get clean request URI
     */
    private function getRequestUri()
    {
        $uri = $_SERVER['REQUEST_URI'];

        // Debug logging
        error_log("Router::getRequestUri - Original URI: $uri");

        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Remove script directory from URI to get relative path
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $scriptDir = dirname($scriptName);

        // Normalize script directory
        $scriptDir = rtrim($scriptDir, '/');

        error_log("Router::getRequestUri - Script name: $scriptName, Script dir: $scriptDir");

        // If URI starts with script directory, remove it
        if ($scriptDir !== '' && $scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }

        // Ensure URI starts with /
        $uri = '/' . ltrim($uri, '/');

        // Handle root case
        $finalUri = $uri === '/' ? '/' : rtrim($uri, '/');
        
        error_log("Router::getRequestUri - Final URI: $finalUri");
        
        return $finalUri;
    }
    
    /**
     * Find matching route
     */
    private function findRoute($method, $uri)
    {
        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            $pattern = $this->convertToRegex($route['uri']);
            $matchResult = @preg_match($pattern, $uri, $matches);
            if ($matchResult === false) {
                error_log("Regex error with pattern: $pattern for URI: {$route['uri']} testing against: $uri");
                continue;
            }
            if ($matchResult === 1) {
                // Extract parameters
                $parameters = [];
                if (preg_match_all('/\{([^}]+)\}/', $route['uri'], $paramMatches)) {
                    foreach ($paramMatches[1] as $index => $paramName) {
                        if (isset($matches[$index + 1])) {
                            $parameters[$paramName] = $matches[$index + 1];
                        }
                    }
                }
                $route['parameters'] = $parameters;
                return $route;
            }
        }
        return null;
    }
    
    /**
     * Convert route URI to regex pattern
     */
    private function convertToRegex($uri)
    {
        try {
            // Handle empty or root URI
            if (empty($uri) || $uri === '/') {
                return '#^/$#';
            }

            // Ensure URI starts with /
            if ($uri[0] !== '/') {
                $uri = '/' . $uri;
            }

            // Use # as delimiter instead of / to avoid conflicts
            $pattern = preg_quote($uri, '#');

            // Replace escaped parameter patterns with regex groups
            // Allow alphanumeric, hyphens, underscores, equals, plus signs for encrypted IDs
            // This covers base64 and URL-encoded characters
            $pattern = preg_replace('/\\\{[^}]+\\\}/', '([a-zA-Z0-9\-_=+%]+)', $pattern);

            $finalPattern = '#^' . $pattern . '$#';

            // Test the pattern before returning
            if (@preg_match($finalPattern, '/test') === false) {
                error_log("Invalid regex pattern generated: " . $finalPattern . " from URI: " . $uri);
                // Return a safe fallback pattern that matches exactly
                return '#^' . preg_quote($uri, '#') . '$#';
            }

            return $finalPattern;

        } catch (Exception $e) {
            error_log("Error in convertToRegex for URI '$uri': " . $e->getMessage());
            // Return a safe fallback
            return '#^' . preg_quote($uri, '#') . '$#';
        }
    }
    
    /**
     * Execute middleware
     */
    private function executeMiddleware($middleware)
    {
        foreach ($middleware as $middlewareName) {
            $middlewareClass = $middlewareName . 'Middleware';
            $middlewareFile = "core/middleware/{$middlewareClass}.php";
            
            if (file_exists($middlewareFile)) {
                require_once $middlewareFile;
                
                if (class_exists($middlewareClass)) {
                    $middlewareInstance = new $middlewareClass();
                    
                    if (method_exists($middlewareInstance, 'handle')) {
                        $result = $middlewareInstance->handle();
                        
                        if ($result === false) {
                            return false;
                        }
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Execute controller action
     */
    private function executeAction($route)
    {
        $action = $route['action'];
        $parameters = $route['parameters'];
        
        if (is_string($action) && strpos($action, '@') !== false) {
            // Controller@method format
            list($controller, $method) = explode('@', $action);
            
            $controllerFile = "controllers/{$controller}.php";
            
            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                
                if (class_exists($controller)) {
                    $controllerInstance = new $controller();
                    
                    if (method_exists($controllerInstance, $method)) {
                        // Pass parameters to method
                        call_user_func_array([$controllerInstance, $method], array_values($parameters));
                    } else {
                        $this->handleError("Method '{$method}' not found in controller '{$controller}'");
                    }
                } else {
                    $this->handleError("Controller class '{$controller}' not found");
                }
            } else {
                $this->handleError("Controller file '{$controllerFile}' not found");
            }
        } elseif (is_callable($action)) {
            // Closure/function
            call_user_func_array($action, array_values($parameters));
        }
    }
    
    /**
     * Handle 404 Not Found
     */
    private function handleNotFound()
    {
        // Redirect to login page for 404 errors
        UrlHelper::redirect('index.php?controller=LoginController');
    }
    
    /**
     * Handle errors
     */
    private function handleError($message)
    {
        http_response_code(500);
        echo "Error: " . $message;
    }
    
    /**
     * Get all registered routes (for debugging)
     */
    public static function getRoutes()
    {
        return self::$routes;
    }
}
