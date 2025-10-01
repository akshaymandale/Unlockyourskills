<?php
require_once '../../config/Database.php';
require_once '../../controllers/InteractiveAIController.php';

// Set JSON header
header('Content-Type: application/json');

try {
    $controller = new InteractiveAIController();
    $controller->validateContentRequirements();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>
