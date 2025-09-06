<?php
/**
 * Module Progress API Endpoint
 * Provides real-time progress data for course modules
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/autoload.php';

// Check if user is logged in - check both session formats
$userId = null;
$clientId = null;

if (isset($_SESSION['user']['id'])) {
    // New session format
    $userId = $_SESSION['user']['id'];
    $clientId = $_SESSION['user']['client_id'] ?? null;
} elseif (isset($_SESSION['id'])) {
    // Legacy session format
    $userId = $_SESSION['id'];
    $clientId = $_SESSION['client_id'] ?? null;
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - User not logged in']);
    exit;
}

// Get request parameters
$moduleId = $_GET['module_id'] ?? null;
$courseId = $_GET['course_id'] ?? null;

if (!$moduleId || !$courseId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

try {
    // Load required models
    require_once '../models/CourseModel.php';
    $courseModel = new CourseModel();
    
    // Get module progress data
    $moduleProgress = $courseModel->getModuleContentProgress($moduleId, $userId, $clientId, $courseId);
    
    // Verify the module belongs to the specified course
    $course = $courseModel->getCourseById($courseId, $clientId, $userId);
    if (!$course) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Course not found']);
        exit;
    }
    
    // Check if module exists in this course
    $moduleExists = false;
    foreach ($course['modules'] ?? [] as $module) {
        if ($module['id'] == $moduleId) {
            $moduleExists = true;
            break;
        }
    }
    
    if (!$moduleExists) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Module not found in course']);
        exit;
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $moduleProgress,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Module progress API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Internal server error',
        'debug' => $e->getMessage()
    ]);
}
?>
