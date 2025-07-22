<?php

require_once 'core/Middleware.php';

/**
 * Super Admin Middleware
 * Ensures user has super admin privileges
 */
class SuperAdminMiddleware extends Middleware
{
    public function handle()
    {
        // Check if user is logged in first
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            return false; // Let AuthMiddleware handle this
        }
        
        // Check if user is super admin
        $systemRole = $_SESSION['user']['system_role'] ?? '';
        
        if ($systemRole !== 'super_admin') {
            if ($this->isAjaxRequest()) {
                $this->json([
                    'success' => false,
                    'message' => 'Super admin access required'
                ], 403);
            }
            
            $this->abort(403, 'Access denied: Super admin privileges required');
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
