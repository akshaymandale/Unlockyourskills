<?php
/**
 * Base Controller Class
 * Provides common functionality for all controllers including toast notifications
 */

class BaseController {
    
    /**
     * Helper method to redirect with toast message
     * This method handles both URL parameter passing and JavaScript fallback
     * 
     * @param string $message The message to display
     * @param string $type The type of message (success, error, warning, info)
     * @param string|null $url The URL to redirect to (optional)
     */
    protected function redirectWithToast($message, $type = 'info', $url = null) {
        // Default URL if none provided
        if ($url === null) {
            $url = $_SERVER['REQUEST_URI']; // Stay on current page
        }
        
        // Handle javascript:history.back() case
        if (strpos($url, 'javascript:') === 0) {
            echo "<script>
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('" . addslashes($message) . "', '" . $type . "');
                } else {
                    alert('" . addslashes($message) . "');
                }
                " . substr($url, 11) . ";
            </script>";
            return;
        }
        
        // Encode message for URL
        $encodedMessage = urlencode($message);
        $separator = strpos($url, '?') !== false ? '&' : '?';
        $redirectUrl = $url . $separator . "message=" . $encodedMessage . "&type=" . $type;
        
        header("Location: " . $redirectUrl);
        exit();
    }
    
    /**
     * Show success toast message
     * 
     * @param string $message The success message
     * @param string|null $url The URL to redirect to (optional)
     */
    protected function toastSuccess($message, $url = null) {
        $this->redirectWithToast($message, 'success', $url);
    }
    
    /**
     * Show error toast message
     * 
     * @param string $message The error message
     * @param string|null $url The URL to redirect to (optional)
     */
    protected function toastError($message, $url = null) {
        $this->redirectWithToast($message, 'error', $url);
    }
    
    /**
     * Show warning toast message
     * 
     * @param string $message The warning message
     * @param string|null $url The URL to redirect to (optional)
     */
    protected function toastWarning($message, $url = null) {
        $this->redirectWithToast($message, 'warning', $url);
    }
    
    /**
     * Show info toast message
     * 
     * @param string $message The info message
     * @param string|null $url The URL to redirect to (optional)
     */
    protected function toastInfo($message, $url = null) {
        $this->redirectWithToast($message, 'info', $url);
    }
    
    /**
     * Legacy method to replace alert() calls with toast notifications
     * This method is for backward compatibility
     * 
     * @param string $message The message to display
     * @param string|null $url The URL to redirect to (optional)
     */
    protected function alertToToast($message, $url = null) {
        // Auto-detect message type based on content
        $type = $this->detectMessageType($message);
        $this->redirectWithToast($message, $type, $url);
    }
    
    /**
     * Detect message type based on content
     * 
     * @param string $message The message to analyze
     * @return string The detected type (success, error, warning, info)
     */
    private function detectMessageType($message) {
        $lowerMessage = strtolower($message);
        
        if (strpos($lowerMessage, 'success') !== false || 
            strpos($lowerMessage, 'added') !== false ||
            strpos($lowerMessage, 'updated') !== false || 
            strpos($lowerMessage, 'saved') !== false ||
            strpos($lowerMessage, 'deleted') !== false || 
            strpos($lowerMessage, 'imported') !== false) {
            return 'success';
        } elseif (strpos($lowerMessage, 'error') !== false || 
                  strpos($lowerMessage, 'failed') !== false ||
                  strpos($lowerMessage, 'invalid') !== false || 
                  strpos($lowerMessage, 'unauthorized') !== false) {
            return 'error';
        } elseif (strpos($lowerMessage, 'warning') !== false || 
                  strpos($lowerMessage, 'note') !== false) {
            return 'warning';
        } else {
            return 'info';
        }
    }

    /**
     * Check if the current request is an AJAX request
     * 
     * @return bool True if AJAX request, false otherwise
     */
    protected function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
?>
