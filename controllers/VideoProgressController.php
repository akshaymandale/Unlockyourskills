<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'models/VideoProgressModel.php';

class VideoProgressController {
    private $videoProgressModel;

    public function __construct() {
        $this->videoProgressModel = new VideoProgressModel();
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

        if (!$userId || !$courseId || !$contentId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            $result = $this->videoProgressModel->getResumePosition($userId, $courseId, $contentId, $clientId);
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Error in getResumePosition: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
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
