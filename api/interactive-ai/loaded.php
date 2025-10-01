<?php
require_once '../../config/Database.php';

// Set JSON header
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $contentId = $input['content_id'] ?? null;
    $courseId = $input['course_id'] ?? null;
    $moduleId = $input['module_id'] ?? null;
    $loadedAt = $input['loaded_at'] ?? time();
    
    if (!$contentId || !$courseId) {
        echo json_encode([
            'success' => false,
            'message' => 'Content ID and Course ID are required'
        ]);
        exit;
    }
    
    // Log content loaded event
    error_log("Interactive AI content loaded: Content ID {$contentId}, Course ID {$courseId}, Module ID {$moduleId}, Time: {$loadedAt}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Content loaded event recorded',
        'data' => [
            'content_id' => $contentId,
            'course_id' => $courseId,
            'module_id' => $moduleId,
            'loaded_at' => $loadedAt
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>
