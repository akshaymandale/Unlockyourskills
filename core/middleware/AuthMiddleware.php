<?php

require_once 'core/Middleware.php';
require_once 'core/UrlHelper.php';

/**
 * Authentication Middleware
 * Ensures user is logged in before accessing protected routes
 * Includes session timeout functionality (1 hour idle timeout)
 */
class AuthMiddleware extends Middleware
{
    private $timeoutMinutes = 60; // 1 hour timeout
    
    public function handle()
    {
        error_log('=== AUTH MIDDLEWARE DEBUG ===');
        error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
        error_log('Session: ' . print_r($_SESSION, true));
        error_log('Is AJAX: ' . (isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : 'NO'));
        
        // Debug logging
        error_log("AuthMiddleware::handle called for URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
        error_log("Session data: " . print_r($_SESSION, true));
        error_log("Is AJAX request: " . ($this->isAjaxRequest() ? 'yes' : 'no'));
        error_log("Session ID exists: " . (isset($_SESSION['id']) ? 'yes' : 'no'));
        error_log("Session user exists: " . (isset($_SESSION['user']) ? 'yes' : 'no'));
        
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
            error_log("AuthMiddleware::handle - User not logged in, redirecting");
            error_log("SESSION['id']: " . ($_SESSION['id'] ?? 'NOT SET'));
            error_log("SESSION['user']: " . (isset($_SESSION['user']) ? 'SET' : 'NOT SET'));
<<<<<<< HEAD
            error_log("Full session data in AuthMiddleware: " . print_r($_SESSION, true));
=======
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
            
            // If AJAX request, return JSON error
            if ($this->isAjaxRequest()) {
                error_log("AuthMiddleware::handle - Returning JSON error for AJAX request");
                $this->json([
                    'success' => false,
                    'message' => 'Authentication required',
                    'redirect' => UrlHelper::url('login')
                ], 401);
            }

            // Regular request, redirect to login (preserve client_code if present)
            $clientCode = $_SESSION['user']['client_code'] ?? ($_COOKIE['last_client_code'] ?? '');
            $suffix = $clientCode ? ('?client_code=' . urlencode($clientCode)) : '';
            $this->redirect(UrlHelper::url('login' . $suffix));
            return false;
        }
        
        // Check session timeout (skip for login/logout routes)
        if (strpos($currentUri, '/login') === false && strpos($currentUri, '/logout') === false) {
            if (!$this->checkSessionTimeout()) {
                error_log("AuthMiddleware::handle - Session timeout detected");
                return false; // Session timeout handled in checkSessionTimeout()
            }
        }

        error_log("AuthMiddleware::handle - Authentication successful");
        return true;
    }
    
    /**
     * Check if session has timed out due to inactivity
     */
    private function checkSessionTimeout()
    {
        error_log("AuthMiddleware::checkSessionTimeout called");
        error_log("Last activity: " . ($_SESSION['last_activity'] ?? 'not set'));
        
        // Check if last activity timestamp exists
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            error_log("AuthMiddleware::checkSessionTimeout - Set initial last_activity to: " . time());
            return true;
        }
        
        // Calculate time since last activity
        $timeSinceLastActivity = time() - $_SESSION['last_activity'];
        $timeoutSeconds = $this->timeoutMinutes * 60;
        
        error_log("AuthMiddleware::checkSessionTimeout - Time since last activity: {$timeSinceLastActivity}s, Timeout: {$timeoutSeconds}s");
        
        // Check if session has timed out
        if ($timeSinceLastActivity > $timeoutSeconds) {
            error_log("AuthMiddleware::checkSessionTimeout - Session timed out!");
            $this->handleSessionTimeout();
            return false;
        }
        
        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
        error_log("AuthMiddleware::checkSessionTimeout - Updated last_activity to: " . time());
        
        return true;
    }
    
    /**
     * Handle session timeout
     */
    private function handleSessionTimeout()
    {
        // Clear session data
        session_unset();
        session_destroy();
        
        // If AJAX request, return JSON response
        if ($this->isAjaxRequest()) {
            $this->json([
                'success' => false,
                'message' => 'Session expired due to inactivity. Please log in again.',
                'timeout' => true,
                'redirect' => UrlHelper::url('login')
            ], 401);
        }
        
                // Regular request, redirect to login with timeout message (preserve client_code)
            $clientCode = $_SESSION['user']['client_code'] ?? ($_COOKIE['last_client_code'] ?? '');
            $qs = 'timeout=1' . ($clientCode ? ('&client_code=' . urlencode($clientCode)) : '');
            $this->redirect(UrlHelper::url('login?' . $qs));
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest()
    {
        // Debug logging
        error_log("AuthMiddleware::isAjaxRequest - Checking headers:");
        error_log("HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'not set'));
        error_log("HTTP_CONTENT_TYPE: " . ($_SERVER['HTTP_CONTENT_TYPE'] ?? 'not set'));
        error_log("HTTP_ACCEPT: " . ($_SERVER['HTTP_ACCEPT'] ?? 'not set'));
        error_log("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set'));
        
        // Check for X-Requested-With header (standard AJAX indicator)
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            error_log("AuthMiddleware::isAjaxRequest - Detected via X-Requested-With header");
            return true;
        }
        
        // Check for Content-Type header that indicates AJAX
        if (!empty($_SERVER['HTTP_CONTENT_TYPE'])) {
            $contentType = strtolower($_SERVER['HTTP_CONTENT_TYPE']);
            // Check for multipart/form-data (FormData submissions)
            if (strpos($contentType, 'multipart/form-data') !== false) {
                error_log("AuthMiddleware::isAjaxRequest - Detected via multipart/form-data Content-Type");
                return true;
            }
            // Check for application/json
            if (strpos($contentType, 'application/json') !== false) {
                error_log("AuthMiddleware::isAjaxRequest - Detected via application/json Content-Type");
                return true;
            }
        }
        
        // Check for Accept header that indicates AJAX
        if (!empty($_SERVER['HTTP_ACCEPT'])) {
            $accept = strtolower($_SERVER['HTTP_ACCEPT']);
            if (strpos($accept, 'application/json') !== false) {
                error_log("AuthMiddleware::isAjaxRequest - Detected via Accept header");
                return true;
            }
        }
        
        // Check if the request is coming from a modal or specific AJAX endpoint
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($currentUri, '/modal/') !== false || 
            strpos($currentUri, '/ajax/') !== false ||
            strpos($currentUri, '/api/') !== false) {
            error_log("AuthMiddleware::isAjaxRequest - Detected via URI pattern");
            return true;
        }
        
        error_log("AuthMiddleware::isAjaxRequest - Not detected as AJAX request");
        return false;
    }
    
    /**
     * Set timeout duration (for testing or configuration)
     */
    public function setTimeoutMinutes($minutes)
    {
        $this->timeoutMinutes = (int)$minutes;
    }
    
    /**
     * Get current timeout duration
     */
    public function getTimeoutMinutes()
    {
        return $this->timeoutMinutes;
    }
}
