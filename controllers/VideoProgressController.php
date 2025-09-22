<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'models/VideoProgressModel.php';
require_once 'models/SharedContentCompletionService.php';

class VideoProgressController {
    private $videoProgressModel;
    private $sharedContentService;

    public function __construct() {
        $this->videoProgressModel = new VideoProgressModel();
        $this->sharedContentService = new SharedContentCompletionService();
    }

    /**
     * Save video progress immediately (for critical actions)
     */
    public function immediateSave() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_POST['course_id'] ?? null;
        $contentId = $_POST['content_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $videoPackageId = $_POST['video_package_id'] ?? null;
        $currentTime = $_POST['current_time'] ?? 0;
        $duration = $_POST['duration'] ?? 0;
        $watchedPercentage = $_POST['watched_percentage'] ?? 0;
        $isCompleted = $_POST['is_completed'] ?? 0;
        $videoStatus = $_POST['video_status'] ?? 'not_started';
        $playCount = $_POST['play_count'] ?? 0;
        $action = $_POST['action'] ?? '';

        if (!$userId || !$courseId || !$contentId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            // Get or create progress record
            $progress = $this->videoProgressModel->getOrCreateProgress(
                $userId, $courseId, $contentId, $videoPackageId, $clientId
            );

            if (!$progress) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to create progress record']);
                exit;
            }

            // Update progress
            $updateData = [
                'current_time' => $currentTime,
                'duration' => $duration,
                'watched_percentage' => $watchedPercentage,
                'is_completed' => $isCompleted,
                'video_status' => $videoStatus,
                'play_count' => $playCount
            ];

            $result = $this->videoProgressModel->updateProgress(
                $userId, $courseId, $contentId, $clientId, $updateData
            );

            if ($result) {
                error_log("Video progress saved immediately - Action: $action, User: $userId, Content: $contentId, Progress: $watchedPercentage%");
                
                // Handle shared content completion if video is completed
                // Only trigger shared completion if video is actually 100% complete, not just at threshold
                if ($isCompleted == 1 && $watchedPercentage >= 100) {
                    // Determine content type and IDs for shared completion
                    $contentType = 'module'; // Default to module
                    $prerequisiteId = null;
                    $sharedContentId = $contentId; // Default to contentId
                    
                    // Check if this is a prerequisite by looking at the progress record
                    if ($progress['prerequisite_id'] && !$progress['content_id']) {
                        $contentType = 'prerequisite';
                        $prerequisiteId = $progress['prerequisite_id'];
                        $sharedContentId = $videoPackageId; // Use video package ID for shared content lookup
                    }
                    
                    // Handle shared video completion
                    $this->sharedContentService->handleSharedContentCompletion(
                        $userId, $courseId, $sharedContentId, $clientId, 'video', $contentType, $prerequisiteId
                    );
                }
                
                echo json_encode(['success' => true, 'message' => 'Progress saved']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to save progress']);
            }

        } catch (Exception $e) {
            error_log("Error in immediateSave: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Save video progress via beacon API (for reliable unload transmission)
     */
    public function beaconSave() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_POST['course_id'] ?? null;
        $contentId = $_POST['content_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $videoPackageId = $_POST['video_package_id'] ?? null;
        $currentTime = $_POST['current_time'] ?? 0;
        $duration = $_POST['duration'] ?? 0;
        $watchedPercentage = $_POST['watched_percentage'] ?? 0;
        $isCompleted = $_POST['is_completed'] ?? 0;
        $videoStatus = $_POST['video_status'] ?? 'not_started';
        $playCount = $_POST['play_count'] ?? 0;

        if (!$userId || !$courseId || !$contentId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            // Get or create progress record
            $progress = $this->videoProgressModel->getOrCreateProgress(
                $userId, $courseId, $contentId, $videoPackageId, $clientId
            );

            if (!$progress) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to create progress record']);
                exit;
            }

            // Update progress
            $updateData = [
                'current_time' => $currentTime,
                'duration' => $duration,
                'watched_percentage' => $watchedPercentage,
                'is_completed' => $isCompleted,
                'video_status' => $videoStatus,
                'play_count' => $playCount
            ];

            $result = $this->videoProgressModel->updateProgress(
                $userId, $courseId, $contentId, $clientId, $updateData
            );

            if ($result) {
                error_log("Video progress saved via beacon - User: $userId, Content: $contentId, Progress: $watchedPercentage%");
                
                // Handle shared content completion if video is completed
                // Only trigger shared completion if video is actually 100% complete, not just at threshold
                if ($isCompleted == 1 && $watchedPercentage >= 100) {
                    // Determine content type and IDs for shared completion
                    $contentType = 'module'; // Default to module
                    $prerequisiteId = null;
                    $sharedContentId = $contentId; // Default to contentId
                    
                    // Check if this is a prerequisite by looking at the progress record
                    if ($progress['prerequisite_id'] && !$progress['content_id']) {
                        $contentType = 'prerequisite';
                        $prerequisiteId = $progress['prerequisite_id'];
                        $sharedContentId = $videoPackageId; // Use video package ID for shared content lookup
                    }
                    
                    // Handle shared video completion
                    $this->sharedContentService->handleSharedContentCompletion(
                        $userId, $courseId, $sharedContentId, $clientId, 'video', $contentType, $prerequisiteId
                    );
                }
                
                echo json_encode(['success' => true, 'message' => 'Progress saved via beacon']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to save progress']);
            }

        } catch (Exception $e) {
            error_log("Error in beaconSave: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Batch update video progress
     */
    public function batchUpdate() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $updates = $_POST['updates'] ?? [];

        if (!$userId || !$clientId || empty($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            $successCount = 0;
            $totalCount = count($updates);

            foreach ($updates as $update) {
                $courseId = $update['course_id'] ?? null;
                $contentId = $update['content_id'] ?? null;
                $videoPackageId = $update['video_package_id'] ?? null;
                $currentTime = $update['current_time'] ?? 0;
                $duration = $update['duration'] ?? 0;
                $watchedPercentage = $update['watched_percentage'] ?? 0;
                $isCompleted = $update['is_completed'] ?? 0;
                $videoStatus = $update['video_status'] ?? 'not_started';
                $playCount = $update['play_count'] ?? 0;

                if ($courseId && $contentId) {
                    // Get or create progress record
                    $progress = $this->videoProgressModel->getOrCreateProgress(
                        $userId, $courseId, $contentId, $videoPackageId, $clientId
                    );

                    if ($progress) {
                        // Update progress
                        $updateData = [
                            'current_time' => $currentTime,
                            'duration' => $duration,
                            'watched_percentage' => $watchedPercentage,
                            'is_completed' => $isCompleted,
                            'video_status' => $videoStatus,
                            'play_count' => $playCount
                        ];

                        $result = $this->videoProgressModel->updateProgress(
                            $userId, $courseId, $contentId, $clientId, $updateData
                        );

                        if ($result) {
                            $successCount++;
                        }
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'message' => "Batch update completed: $successCount/$totalCount records updated"
            ]);

        } catch (Exception $e) {
            error_log("Error in batchUpdate: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Get resume position for video
     */
    public function getResumePosition() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_GET['course_id'] ?? null;
        $contentId = $_GET['content_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $videoPackageId = $_GET['video_package_id'] ?? null;

        if (!$userId || !$courseId || !$contentId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            // Get video package ID if not provided
            if (!$videoPackageId) {
                $videoPackageId = $this->getVideoPackageId($contentId);
            }
            
            if (!$videoPackageId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Could not determine video package ID']);
                exit;
            }
            
            // Get or create progress record (this will create the first entry if it doesn't exist)
            $progress = $this->videoProgressModel->getOrCreateProgress($userId, $courseId, $contentId, $videoPackageId, $clientId);
            
            if ($progress && $progress['current_time'] > 0) {
                echo json_encode([
                    'success' => true,
                    'resume_position' => $progress['current_time'],
                    'duration' => $progress['duration'],
                    'watched_percentage' => $progress['watched_percentage'],
                    'play_count' => $progress['play_count'],
                    'last_watched_at' => $progress['last_watched_at']
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'resume_position' => 0,
                    'duration' => 0,
                    'watched_percentage' => 0,
                    'play_count' => 0,
                    'last_watched_at' => null
                ]);
            }

        } catch (Exception $e) {
            error_log("Error in getResumePosition: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Get video package ID for content
     */
    private function getVideoPackageId($contentId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();

            // First try course_module_content
            $sql = "SELECT content_id as video_package_id FROM course_module_content 
                    WHERE id = ? AND content_type = 'video'";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$contentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                // If not found in course_module_content, try course_prerequisites
                $sql = "SELECT prerequisite_id as video_package_id FROM course_prerequisites 
                        WHERE id = ? AND prerequisite_type = 'video'";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$contentId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return $result ? $result['video_package_id'] : null;
        } catch (Exception $e) {
            error_log("Error getting video package ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get video statistics
     */
    public function getVideoStats() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_GET['course_id'] ?? null;
        $contentId = $_GET['content_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;

        if (!$userId || !$courseId || !$contentId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            $result = $this->videoProgressModel->getVideoStats($userId, $courseId, $contentId, $clientId);
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Error in getVideoStats: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }
}
?>
