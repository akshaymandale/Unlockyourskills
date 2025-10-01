<?php
require_once '../../config/Database.php';

// Set JSON header
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $contentId = $input['content_id'] ?? null;
    $courseId = $input['course_id'] ?? null;
    $moduleId = $input['module_id'] ?? null;
    $expiredAt = $input['expired_at'] ?? time();
    
    if (!$contentId || !$courseId) {
        echo json_encode([
            'success' => false,
            'message' => 'Content ID and Course ID are required'
        ]);
        exit;
    }
    
    // Log time expired event
    error_log("Interactive AI content time expired: Content ID {$contentId}, Course ID {$courseId}, Module ID {$moduleId}, Time: {$expiredAt}");
    
    // Here you could implement additional logic like:
    // - Auto-save progress
    // - Send notifications
    // - Update completion status
    
    echo json_encode([
        'success' => true,
        'message' => 'Time expired event recorded',
        'data' => [
            'content_id' => $contentId,
            'course_id' => $courseId,
            'module_id' => $moduleId,
            'expired_at' => $expiredAt
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
