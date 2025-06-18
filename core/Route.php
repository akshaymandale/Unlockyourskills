<?php

/**
 * Route Class - Individual route representation
 * Provides fluent interface for route configuration
 */
class Route
{
    private $uri;
    private $action;
    private $middleware = [];
    private $name;
    private $where = [];
    
    public function __construct($uri, $action)
    {
        $this->uri = $uri;
        $this->action = $action;
    }
    
    /**
     * Add middleware to route
     */
    public function middleware($middleware)
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }
        
        return $this;
    }
    
    /**
     * Set route name
     */
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * Add parameter constraints
     */
    public function where($parameter, $pattern)
    {
        if (is_array($parameter)) {
            $this->where = array_merge($this->where, $parameter);
        } else {
            $this->where[$parameter] = $pattern;
        }
        
        return $this;
    }
    
    /**
     * Get route URI
     */
    public function getUri()
    {
        return $this->uri;
    }
    
    /**
     * Get route action
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * Get route middleware
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }
    
    /**
     * Get route name
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Get parameter constraints
     */
    public function getWhere()
    {
        return $this->where;
    }
}
