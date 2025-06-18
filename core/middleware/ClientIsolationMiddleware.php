<?php

require_once 'core/Middleware.php';

/**
 * Client Isolation Middleware
 * Ensures proper client data isolation
 */
class ClientIsolationMiddleware extends Middleware
{
    public function handle()
    {
        // Check if user is logged in first
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            return false; // Let AuthMiddleware handle this
        }
        
        // Check if user has client_id
        if (!isset($_SESSION['user']['client_id'])) {
            if ($this->isAjaxRequest()) {
                $this->json([
                    'success' => false,
                    'message' => 'Client access required'
                ], 403);
            }
            
            $this->abort(403, 'Access denied: Client information required');
            return false;
        }
        
        // Validate client_id is numeric and positive
        $clientId = $_SESSION['user']['client_id'];
        if (!is_numeric($clientId) || $clientId <= 0) {
            if ($this->isAjaxRequest()) {
                $this->json([
                    'success' => false,
                    'message' => 'Invalid client access'
                ], 403);
            }
            
            $this->abort(403, 'Access denied: Invalid client information');
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
