<?php

require_once 'core/Middleware.php';
require_once 'core/UrlHelper.php';

/**
 * Authentication Middleware
 * Ensures user is logged in before accessing protected routes
 */
class AuthMiddleware extends Middleware
{
    public function handle()
    {
        // Temporary: Allow assessment routes without authentication for testing
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($currentUri, '/vlr/assessment-packages/') !== false) {
            // For assessment routes, check if user is logged in but don't block if not
            if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
                // Set a default client_id for testing
                $_SESSION['user'] = [
                    'client_id' => 1,
                    'id' => 1
                ];
            }
            return true;
        }

        // Check if user is logged in
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            // If AJAX request, return JSON error
            if ($this->isAjaxRequest()) {
                $this->json([
                    'success' => false,
                    'message' => 'Authentication required',
                    'redirect' => UrlHelper::url('login')
                ], 401);
            }

            // Regular request, redirect to login
            $this->redirect(UrlHelper::url('login'));
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
