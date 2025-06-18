<?php

/**
 * URL Helper Class
 * Handles URL generation relative to project directory
 */
class UrlHelper
{
    /**
     * Get the base URL of the project
     */
    public static function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // Get the script directory
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $scriptDir = dirname($scriptName);
        
        // Clean up the directory path
        $basePath = ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;
        
        return $protocol . '://' . $host . $basePath;
    }
    
    /**
     * Get the base path (directory) of the project
     */
    public static function getBasePath()
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $scriptDir = dirname($scriptName);
        
        // Clean up the directory path
        return ($scriptDir === '/' || $scriptDir === '\\') ? '' : $scriptDir;
    }
    
    /**
     * Generate a URL relative to the project
     */
    public static function url($path = '')
    {
        $basePath = self::getBasePath();
        $path = ltrim($path, '/');
        
        if (empty($path)) {
            return $basePath ?: '/';
        }
        
        return $basePath . '/' . $path;
    }
    
    /**
     * Generate a full URL with protocol and host
     */
    public static function fullUrl($path = '')
    {
        $baseUrl = self::getBaseUrl();
        $path = ltrim($path, '/');
        
        if (empty($path)) {
            return $baseUrl;
        }
        
        return $baseUrl . '/' . $path;
    }
    
    /**
     * Redirect to a URL relative to the project
     */
    public static function redirect($path = '')
    {
        $url = self::url($path);
        header("Location: $url");
        exit();
    }
    
    /**
     * Get current URL
     */
    public static function current()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        
        return $protocol . '://' . $host . $uri;
    }
    
    /**
     * Check if current request is for the project root
     */
    public static function isProjectRoot()
    {
        $basePath = self::getBasePath();
        $requestUri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        if (($pos = strpos($requestUri, '?')) !== false) {
            $requestUri = substr($requestUri, 0, $pos);
        }
        
        $requestUri = rtrim($requestUri, '/');
        $basePath = rtrim($basePath, '/');
        
        return $requestUri === $basePath;
    }
}
