<?php

require_once 'core/Middleware.php';
require_once 'core/UrlHelper.php';

/**
 * Session Timeout Middleware
 * Automatically logs out users after 1 hour of inactivity
 */
class SessionTimeoutMiddleware extends Middleware
{
    private $timeoutMinutes = 60; // 1 hour timeout
    
    public function handle()
    {
        // Skip timeout check for login/logout routes
        $currentUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($currentUri, '/login') !== false || strpos($currentUri, '/logout') !== false) {
            return true;
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            return true; // Let AuthMiddleware handle this
        }
        
        // Check if last activity timestamp exists
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return true;
        }
        
        // Calculate time since last activity
        $timeSinceLastActivity = time() - $_SESSION['last_activity'];
        $timeoutSeconds = $this->timeoutMinutes * 60;
        
        // Check if session has timed out
        if ($timeSinceLastActivity > $timeoutSeconds) {
            // Session has timed out, destroy it
            $this->handleSessionTimeout();
            return false;
        }
        
        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Handle session timeout
     */
    private function handleSessionTimeout()
    {
        // Store timeout information for potential logging
        $timeoutData = [
            'user_id' => $_SESSION['id'] ?? null,
            'client_id' => $_SESSION['user']['client_id'] ?? null,
            'timeout_duration' => $this->timeoutMinutes,
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'timeout_time' => time()
        ];
        
        // Log timeout event if logging is enabled
        $this->logSessionTimeout($timeoutData);
        
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
     * Log session timeout event
     */
    private function logSessionTimeout($timeoutData)
    {
        try {
            // You can implement logging to database or file here
            error_log("Session timeout: " . json_encode($timeoutData));
        } catch (Exception $e) {
            // Silently fail if logging fails
            error_log("Failed to log session timeout: " . $e->getMessage());
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
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