<?php
/**
 * Prerequisite Progress Controller
 * 
 * Handles prerequisite progress tracking (start, complete)
 */

require_once 'models/PrerequisiteCompletionModel.php';

class PrerequisiteProgressController {
    private $prerequisiteCompletionModel;

    public function __construct() {
        $this->prerequisiteCompletionModel = new PrerequisiteCompletionModel();
    }

    /**
     * Start prerequisite tracking when user opens it
     * Note: No completion entries are created on start - only when content is completed
     */
    public function startTracking() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not authenticated']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_POST['course_id'] ?? null;
        $prerequisiteId = $_POST['prerequisite_id'] ?? null;
        $prerequisiteType = $_POST['prerequisite_type'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? 2;

        if (!$courseId || !$prerequisiteId || !$prerequisiteType) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            return;
        }

        try {
            // No longer create completion entries on start
            // Completion entries are only created when content is actually completed
            echo json_encode(['success' => true, 'message' => 'Prerequisite tracking started (no completion entry created)']);
        } catch (Exception $e) {
            error_log("Error starting prerequisite tracking: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Internal server error']);
        }
    }

    /**
     * Mark prerequisite as completed
     */
    public function markComplete() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not authenticated']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_POST['course_id'] ?? null;
        $prerequisiteId = $_POST['prerequisite_id'] ?? null;
        $prerequisiteType = $_POST['prerequisite_type'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? 2;

        if (!$courseId || !$prerequisiteId || !$prerequisiteType) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            return;
        }

        try {
            $result = $this->prerequisiteCompletionModel->markComplete(
                $userId,
                $courseId,
                $prerequisiteId,
                $prerequisiteType,
                $clientId
            );

            if ($result) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to mark complete']);
            }
        } catch (Exception $e) {
            error_log("Error marking prerequisite complete: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Internal server error']);
        }
    }

    /**
     * Get prerequisite progress
     */
    public function getProgress() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not authenticated']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_GET['course_id'] ?? null;
        $prerequisiteId = $_GET['prerequisite_id'] ?? null;
        $prerequisiteType = $_GET['prerequisite_type'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? 2;

        if (!$courseId || !$prerequisiteId || !$prerequisiteType) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            return;
        }

        try {
            $result = $this->prerequisiteCompletionModel->getProgress(
                $userId,
                $courseId,
                $prerequisiteId,
                $prerequisiteType,
                $clientId
            );

            if ($result) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Progress not found']);
            }
        } catch (Exception $e) {
            error_log("Error getting prerequisite progress: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Internal server error']);
        }
    }
}
?>
