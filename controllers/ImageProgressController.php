<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'models/ImageProgressModel.php';
require_once 'models/SharedContentCompletionService.php';

class ImageProgressController {
    private $imageProgressModel;
    private $sharedContentService;

    public function __construct() {
        $this->imageProgressModel = new ImageProgressModel();
        $this->sharedContentService = new SharedContentCompletionService();
    }

    /**
     * Mark image as viewed
     */
    public function markAsViewed() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST; // Fallback to POST data
            }
            
            $userId = $_SESSION['user']['id'];
            $courseId = $input['course_id'] ?? null;
            $contentId = $input['content_id'] ?? null;
            $clientId = $input['client_id'] ?? $_SESSION['user']['client_id'] ?? null;

            if (!$courseId || !$contentId || !$clientId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }

            // Get or create progress record
            $imagePackageId = $input['image_package_id'] ?? null;
            if (!$imagePackageId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Missing image_package_id']);
                exit;
            }
            
            $progress = $this->imageProgressModel->getOrCreateProgress($userId, $courseId, $contentId, $imagePackageId, $clientId);
            
            if (!$progress) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to create progress record']);
                exit;
            }

            // Note: Completion tracking is now handled only when content is actually completed

            // Mark as viewed
            $success = $this->imageProgressModel->markAsViewed($userId, $courseId, $contentId, $clientId);

            if ($success) {
                // Handle shared content completion
                // Determine content type and IDs for shared completion
                $contextType = 'module'; // Default to module
                $prerequisiteId = null;
                $sharedContentId = $contentId; // Default to contentId
                
                // Check if this is a prerequisite by looking at the progress record
                if ($progress['prerequisite_id'] && !$progress['content_id']) {
                    $contextType = 'prerequisite';
                    $prerequisiteId = $progress['prerequisite_id'];
                    $sharedContentId = $imagePackageId; // Use image package ID for shared content lookup
                }
                
                $this->sharedContentService->handleSharedContentCompletion(
                    $userId, $courseId, $sharedContentId, $clientId, 'image', $contextType, $prerequisiteId
                );
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Image marked as viewed']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to update progress']);
            }

        } catch (Exception $e) {
            error_log("Error in markAsViewed: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Get image progress
     */
    public function getProgress() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            $userId = $_SESSION['user']['id'];
            $courseId = $_GET['course_id'] ?? null;
            $contentId = $_GET['content_id'] ?? null;
            $clientId = $_GET['client_id'] ?? $_SESSION['user']['client_id'] ?? null;

            if (!$courseId || !$contentId || !$clientId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }

            $progress = $this->imageProgressModel->getProgress($userId, $courseId, $contentId, $clientId);

            if ($progress) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $progress]);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => [
                    'image_status' => 'not_viewed',
                    'is_completed' => 0,
                    'view_count' => 0,
                    'viewed_at' => null
                ]]);
            }

        } catch (Exception $e) {
            error_log("Error in getProgress: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Update image progress
     */
    public function updateProgress() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST; // Fallback to POST data
            }
            
            $userId = $_SESSION['user']['id'];
            $courseId = $input['course_id'] ?? null;
            $contentId = $input['content_id'] ?? null;
            $clientId = $input['client_id'] ?? $_SESSION['user']['client_id'] ?? null;

            if (!$courseId || !$contentId || !$clientId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }

            $updateData = [
                'image_status' => $input['image_status'] ?? 'viewed',
                'is_completed' => $input['is_completed'] ?? 1,
                'view_count' => $input['view_count'] ?? 1,
                'viewed_at' => $input['viewed_at'] ?? date('Y-m-d H:i:s'),
                'notes' => $input['notes'] ?? null
            ];

            $success = $this->imageProgressModel->updateProgress($userId, $courseId, $contentId, $clientId, $updateData);

            if ($success) {
                // If image is completed, update completion tracking
                if (isset($updateData['is_completed']) && $updateData['is_completed']) {
                    // Get the progress record to determine context
                    $progress = $this->imageProgressModel->getProgress($userId, $courseId, $contentId, $clientId);
                    
                    if ($progress) {
                        // Determine content type and IDs for shared completion
                        $contextType = 'module'; // Default to module
                        $prerequisiteId = null;
                        $sharedContentId = $contentId; // Default to contentId
                        $imagePackageId = $input['image_package_id'] ?? null;
                        
                        // Check if this is a prerequisite by looking at the progress record
                        if ($progress['prerequisite_id'] && !$progress['content_id']) {
                            $contextType = 'prerequisite';
                            $prerequisiteId = $progress['prerequisite_id'];
                            $sharedContentId = $imagePackageId; // Use image package ID for shared content lookup
                        }
                        
                        // Handle shared content completion
                        $this->sharedContentService->handleSharedContentCompletion(
                            $userId, $courseId, $sharedContentId, $clientId, 'image', $contextType, $prerequisiteId
                        );
                    }
                }

                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Progress updated successfully']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to update progress']);
            }

        } catch (Exception $e) {
            error_log("Error in updateProgress: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }


    /**
     * Check if content is a prerequisite
     */
    private function isContentPrerequisite($courseId, $contentId, $contentType) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT COUNT(*) FROM course_prerequisites 
                    WHERE course_id = ? AND prerequisite_id = ? AND prerequisite_type = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId, $contentId, $contentType]);
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error checking if content is prerequisite: " . $e->getMessage());
            return false;
        }
    }
}
?>
