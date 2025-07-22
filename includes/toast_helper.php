<?php
/**
 * Toast Notification Helper
 * Provides server-side functions to generate toast notifications
 * Replaces alert() calls with professional toast notifications
 */

class ToastHelper {
    
    /**
     * Redirect with toast notification
     * @param string $message - The message to display
     * @param string $type - success, error, warning, info
     * @param string $url - URL to redirect to
     */
    public static function redirectWithToast($message, $type = 'success', $url = null) {
        if ($url === null) {
            $url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        }
        
        // Encode message for URL
        $encodedMessage = urlencode($message);
        
        // Add parameters to URL
        $separator = strpos($url, '?') !== false ? '&' : '?';
        $redirectUrl = $url . $separator . "message={$encodedMessage}&type={$type}";
        
        header("Location: {$redirectUrl}");
        exit();
    }
    
    /**
     * Generate JavaScript toast notification
     * @param string $message - The message to display
     * @param string $type - success, error, warning, info
     * @param int $duration - Duration in milliseconds
     * @return string - JavaScript code to show toast
     */
    public static function generateToastJS($message, $type = 'success', $duration = 5000) {
        $escapedMessage = addslashes($message);
        return "<script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.showToast) {
                    window.showToast.{$type}('{$escapedMessage}', {$duration});
                }
            });
        </script>";
    }
    
    /**
     * Show toast and redirect (for immediate display)
     * @param string $message - The message to display
     * @param string $type - success, error, warning, info
     * @param string $url - URL to redirect to
     * @param int $delay - Delay before redirect in milliseconds
     */
    public static function showToastAndRedirect($message, $type = 'success', $url = null, $delay = 2000) {
        if ($url === null) {
            $url = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        }
        
        $escapedMessage = addslashes($message);
        $escapedUrl = addslashes($url);
        
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                if (window.showToast) {
                    window.showToast.{$type}('{$escapedMessage}');
                    setTimeout(function() {
                        window.location.href = '{$escapedUrl}';
                    }, {$delay});
                }
            });
        </script>";
        exit();
    }
    
    /**
     * Replace alert() calls with toast notifications
     * @param string $message - The message to display
     * @param string $url - URL to redirect to (optional)
     */
    public static function alertToToast($message, $url = null) {
        // Determine message type based on content
        $lowerMessage = strtolower($message);
        $type = 'info';
        
        if (strpos($lowerMessage, 'success') !== false || 
            strpos($lowerMessage, 'added') !== false || 
            strpos($lowerMessage, 'updated') !== false || 
            strpos($lowerMessage, 'saved') !== false ||
            strpos($lowerMessage, 'deleted') !== false || 
            strpos($lowerMessage, 'imported') !== false) {
            $type = 'success';
        } elseif (strpos($lowerMessage, 'error') !== false || 
                  strpos($lowerMessage, 'failed') !== false || 
                  strpos($lowerMessage, 'invalid') !== false || 
                  strpos($lowerMessage, 'unauthorized') !== false) {
            $type = 'error';
        } elseif (strpos($lowerMessage, 'warning') !== false || 
                  strpos($lowerMessage, 'note') !== false) {
            $type = 'warning';
        }
        
        if ($url) {
            self::showToastAndRedirect($message, $type, $url);
        } else {
            echo self::generateToastJS($message, $type);
        }
    }
    
    /**
     * Success toast notification
     */
    public static function success($message, $url = null) {
        if ($url) {
            self::redirectWithToast($message, 'success', $url);
        } else {
            echo self::generateToastJS($message, 'success');
        }
    }
    
    /**
     * Error toast notification
     */
    public static function error($message, $url = null) {
        if ($url) {
            self::redirectWithToast($message, 'error', $url);
        } else {
            echo self::generateToastJS($message, 'error');
        }
    }
    
    /**
     * Warning toast notification
     */
    public static function warning($message, $url = null) {
        if ($url) {
            self::redirectWithToast($message, 'warning', $url);
        } else {
            echo self::generateToastJS($message, 'warning');
        }
    }
    
    /**
     * Info toast notification
     */
    public static function info($message, $url = null) {
        if ($url) {
            self::redirectWithToast($message, 'info', $url);
        } else {
            echo self::generateToastJS($message, 'info');
        }
    }
}

// Global convenience functions
function toastSuccess($message, $url = null) {
    ToastHelper::success($message, $url);
}

function toastError($message, $url = null) {
    ToastHelper::error($message, $url);
}

function toastWarning($message, $url = null) {
    ToastHelper::warning($message, $url);
}

function toastInfo($message, $url = null) {
    ToastHelper::info($message, $url);
}

function replaceAlert($message, $url = null) {
    ToastHelper::alertToToast($message, $url);
}
?>
