<?php
/**
 * Session Configuration
 * Centralized configuration for session management
 */

return [
    // Session timeout settings
    'timeout' => [
        'minutes' => env('SESSION_TIMEOUT_MINUTES', 60), // 1 hour default
        'warning_minutes' => env('SESSION_WARNING_MINUTES', 5), // Show warning 5 minutes before
        'check_interval' => env('SESSION_CHECK_INTERVAL', 60000), // Check every minute (in milliseconds)
    ],
    
    // Session security settings
    'security' => [
        'regenerate_id' => env('SESSION_REGENERATE_ID', true), // Regenerate session ID on login
        'secure_cookies' => env('SESSION_SECURE_COOKIES', false), // Set to true for HTTPS
        'http_only' => env('SESSION_HTTP_ONLY', true), // Prevent XSS attacks
        'same_site' => env('SESSION_SAME_SITE', 'Lax'), // CSRF protection
    ],
    
    // Session logging
    'logging' => [
        'enabled' => env('SESSION_LOGGING_ENABLED', true),
        'log_timeouts' => env('SESSION_LOG_TIMEOUTS', true),
        'log_activity' => env('SESSION_LOG_ACTIVITY', false), // Log all activity (can be verbose)
    ],
    
    // Session storage
    'storage' => [
        'driver' => env('SESSION_DRIVER', 'file'), // file, database, redis
        'lifetime' => env('SESSION_LIFETIME', 0), // 0 = until browser closes
        'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
    ],
];

/**
 * Helper function to get session configuration
 */
function session_config($key = null, $default = null) {
    static $config = null;
    
    if ($config === null) {
        $config = require __DIR__ . '/session.php';
    }
    
    if ($key === null) {
        return $config;
    }
    
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $segment) {
        if (!isset($value[$segment])) {
            return $default;
        }
        $value = $value[$segment];
    }
    
    return $value;
}

/**
 * Helper function to get environment variable with fallback
 */
function env($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}
?> 