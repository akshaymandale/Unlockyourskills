<?php
/**
 * Session Activity API
 * Handles session activity pings and timeout management
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set headers for JSON response
header('Content-Type: application/json');
header('X-Requested-With: XMLHttpRequest');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required',
        'timeout' => true
    ]);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'ping':
            // Update last activity timestamp
            $_SESSION['last_activity'] = time();
            
            // Log activity (optional)
            error_log("Session activity ping: " . json_encode([
                'user_id' => $_SESSION['id'],
                'client_id' => $_SESSION['user']['client_id'] ?? null,
                'timestamp' => time()
            ]));
            
            echo json_encode([
                'success' => true,
                'message' => 'Activity recorded',
                'timestamp' => time()
            ]);
            break;
            
        case 'extend':
            // Extend session by updating last activity
            $_SESSION['last_activity'] = time();
            
            echo json_encode([
                'success' => true,
                'message' => 'Session extended',
                'timestamp' => time()
            ]);
            break;
            
        case 'status':
            // Return current session status
            $lastActivity = $_SESSION['last_activity'] ?? time();
            $timeoutMinutes = 60; // Should match server-side timeout
            $timeSinceLastActivity = time() - $lastActivity;
            $remainingMinutes = $timeoutMinutes - ($timeSinceLastActivity / 60);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'last_activity' => $lastActivity,
                    'remaining_minutes' => max(0, $remainingMinutes),
                    'timeout_minutes' => $timeoutMinutes
                ]
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
    
} catch (Exception $e) {
    error_log("Session activity API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?> 