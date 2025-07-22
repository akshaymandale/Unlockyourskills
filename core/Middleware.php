<?php

/**
 * Base Middleware Class
 * All middleware classes should extend this
 */
abstract class Middleware
{
    /**
     * Handle the request
     * Return false to stop execution, true to continue
     */
    abstract public function handle();
    
    /**
     * Redirect to a URL
     */
    protected function redirect($url)
    {
        header("Location: $url");
        exit();
    }
    
    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    /**
     * Abort with error
     */
    protected function abort($statusCode = 404, $message = '')
    {
        http_response_code($statusCode);
        echo $message ?: "Error $statusCode";
        exit();
    }
}
