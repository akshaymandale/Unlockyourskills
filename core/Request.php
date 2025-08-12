<?php

/**
 * Request Class - HTTP Request handling
 * Provides convenient access to request data
 */
class Request
{
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Get request method
     */
    public function method()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Handle method override
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        return $method;
    }
    
    /**
     * Get request URI
     */
    public function uri()
    {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        return $uri;
    }
    
    /**
     * Get full URL
     */
    public function url()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        
        return $protocol . '://' . $host . $uri;
    }
    
    /**
     * Get input value
     */
    public function input($key, $default = null)
    {
        // Check POST first, then GET
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        
        return $default;
    }
    
    /**
     * Get all input data
     */
    public function all()
    {
        return array_merge($_GET, $_POST);
    }
    
    /**
     * Check if input exists
     */
    public function has($key)
    {
        return isset($_POST[$key]) || isset($_GET[$key]);
    }
    
    /**
     * Get only specified inputs
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
     * Get all inputs except specified
     */
    public function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $all = $this->all();
        
        foreach ($keys as $key) {
            unset($all[$key]);
        }
        
        return $all;
    }
    
    /**
     * Check if request is AJAX
     */
    public function ajax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Check if request is JSON
     */
    public function json()
    {
        return isset($_SERVER['CONTENT_TYPE']) && 
               strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;
    }
    
    /**
     * Get uploaded file
     */
    public function file($key)
    {
        return isset($_FILES[$key]) ? $_FILES[$key] : null;
    }
    
    /**
     * Check if file was uploaded
     */
    public function hasFile($key)
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }
    
    /**
     * Get client IP address
     */
    public function ip()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    /**
     * Get user agent
     */
    public function userAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Get header value
     */
    public function header($key, $default = null)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$key] ?? $default;
    }
    
    /**
     * Get bearer token from Authorization header
     */
    public function bearerToken()
    {
        $header = $this->header('Authorization');
        
        if ($header && strpos($header, 'Bearer ') === 0) {
            return substr($header, 7);
        }
        
        return null;
    }
}
