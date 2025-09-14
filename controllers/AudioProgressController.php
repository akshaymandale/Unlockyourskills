<?php
require_once 'models/AudioProgressModel.php';
require_once 'models/CourseModel.php';

class AudioProgressController {
    private $audioProgressModel;
    private $courseModel;

    public function __construct() {
        $this->audioProgressModel = new AudioProgressModel();
        $this->courseModel = new CourseModel();
    }

    /**
     * Update audio progress (AJAX endpoint)
     */
    public function updateProgress() {
        // Check authentication
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        
        if (!$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Client ID not found']);
            exit;
        }

        // Get POST data
        $courseId = $_POST['course_id'] ?? null;
        $contentId = $_POST['content_id'] ?? null;
        $audioPackageId = $_POST['audio_package_id'] ?? null;
        $currentTime = $_POST['current_time'] ?? 0;
        $duration = $_POST['duration'] ?? 0;
        $playbackSpeed = $_POST['playback_speed'] ?? 1.0;
        $notes = $_POST['notes'] ?? '';

        if (!$courseId || !$contentId || !$audioPackageId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        // Note: Completion tracking is now handled only when content is actually completed

        try {
            // Get or create progress record
            $progress = $this->audioProgressModel->getOrCreateProgress(
                $userId, $courseId, $contentId, $audioPackageId, $clientId
            );

            if (!$progress) {
                throw new Exception('Failed to create progress record');
            }

            // Calculate listened percentage
            $listenedPercentage = $duration > 0 ? round(($currentTime / $duration) * 100, 2) : 0;
            
                    // Check if completed (default threshold is 80%)
        $completionThreshold = $progress['completion_threshold'] ?? 80;
        $isCompleted = $listenedPercentage >= $completionThreshold ? 1 : 0;

        // Get statuses from POST data
        $audioStatus = $_POST['audio_status'] ?? 'not_started';
        $playbackStatus = $_POST['playback_status'] ?? 'not_started';

        // Update progress
        $updateData = [
            'current_time' => $currentTime,
            'duration' => $duration,
            'listened_percentage' => $listenedPercentage,
            'is_completed' => $isCompleted,
            'audio_status' => $audioStatus,
            'playback_status' => $playbackStatus,
            'play_count' => $progress['play_count'] + 1,
            'playback_speed' => $playbackSpeed,
            'notes' => $notes
        ];

            $success = $this->audioProgressModel->updateProgress($progress['id'], $updateData);

            if ($success) {
                // If audio is completed, update completion tracking
                if ($isCompleted) {
                    require_once 'models/CompletionTrackingService.php';
                    $completionService = new CompletionTrackingService();
                    $completionService->handleContentCompletion($userId, $courseId, $contentId, 'audio', $clientId);
                }

                echo json_encode([
                    'success' => true,
                    'progress' => [
                        'id' => $progress['id'],
                        'listened_percentage' => $listenedPercentage,
                        'is_completed' => $isCompleted,
                        'current_time' => $currentTime,
                        'duration' => $duration,
                        'play_count' => $updateData['play_count'],
                        'audio_status' => $audioStatus
                    ]
                ]);
            } else {
                throw new Exception('Failed to update progress');
            }

        } catch (Exception $e) {
            error_log("Audio progress update error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update progress']);
        }
    }

    /**
     * Immediate save for critical actions (play, pause, stop)
     */
    public function immediateSave() {
        // Check authentication
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_POST['course_id'] ?? null;
        $contentId = $_POST['content_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $currentTime = $_POST['current_time'] ?? 0;
        $duration = $_POST['duration'] ?? 0;
        $listenedPercentage = $_POST['listened_percentage'] ?? 0;
        $playbackStatus = $_POST['playback_status'] ?? 'playing';
        $audioStatus = $_POST['audio_status'] ?? 'in_progress';
        $action = $_POST['action'] ?? 'unknown';

        if (!$courseId || !$contentId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            // Log the immediate save action
            error_log("Audio immediate save: action=$action, userId=$userId, courseId=$courseId, contentId=$contentId, progress=$listenedPercentage%");
            
            // Get or create progress record
            $audioPackageId = $_POST['audio_package_id'] ?? null;
            $progress = $this->audioProgressModel->getOrCreateProgress(
                $userId, $courseId, $contentId, $audioPackageId, $clientId
            );

            if (!$progress) {
                throw new Exception('Failed to create progress record');
            }

            // Update progress
            $updateData = [
                'current_time' => $currentTime,
                'duration' => $duration,
                'listened_percentage' => $listenedPercentage,
                'is_completed' => $listenedPercentage >= 80 ? 1 : 0,
                'audio_status' => $audioStatus,
                'playback_status' => $playbackStatus,
                'play_count' => $progress['play_count'] + 1,
                'playback_speed' => $_POST['playback_speed'] ?? 1.0,
                'notes' => $_POST['notes'] ?? ''
            ];
            
            $success = $this->audioProgressModel->updateProgress($progress['id'], $updateData);
            
            if ($success) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Progress saved immediately',
                    'action' => $action,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                throw new Exception('Failed to save progress immediately');
            }

        } catch (Exception $e) {
            error_log("Audio immediate save error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save progress immediately']);
        }
    }

    /**
     * Beacon API endpoint for reliable data transmission
     */
    public function beaconSave() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check authentication
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_POST['course_id'] ?? null;
        $contentId = $_POST['content_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $currentTime = $_POST['current_time'] ?? 0;
        $duration = $_POST['duration'] ?? 0;
        $listenedPercentage = $_POST['listened_percentage'] ?? 0;
        $playbackStatus = $_POST['playback_status'] ?? 'stopped';
        $audioStatus = $_POST['audio_status'] ?? 'in_progress';

        if (!$courseId || !$contentId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            // Log the beacon save
            error_log("Audio beacon save: userId=$userId, courseId=$courseId, contentId=$contentId, progress=$listenedPercentage%");
            
            // Get or create progress record
            $audioPackageId = $_POST['audio_package_id'] ?? null;
            $progress = $this->audioProgressModel->getOrCreateProgress(
                $userId, $courseId, $contentId, $audioPackageId, $clientId
            );

            if (!$progress) {
                throw new Exception('Failed to create progress record');
            }

            // Update progress
            $updateData = [
                'current_time' => $currentTime,
                'duration' => $duration,
                'listened_percentage' => $listenedPercentage,
                'is_completed' => $listenedPercentage >= 80 ? 1 : 0,
                'audio_status' => $audioStatus,
                'playback_status' => $playbackStatus,
                'play_count' => $progress['play_count'] + 1,
                'playback_speed' => $_POST['playback_speed'] ?? 1.0,
                'notes' => $_POST['notes'] ?? ''
            ];
            
            $success = $this->audioProgressModel->updateProgress($progress['id'], $updateData);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Progress saved via beacon']);
            } else {
                throw new Exception('Failed to save progress via beacon');
            }

        } catch (Exception $e) {
            error_log("Audio beacon save error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save progress via beacon']);
        }
    }

    /**
     * Batch update for multiple progress entries
     */
    public function batchUpdate() {
        // Check authentication
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $updates = $_POST['updates'] ?? null;

        if (!$clientId || !$updates || !is_array($updates)) {
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
                $currentTime = $update['current_time'] ?? 0;
                $duration = $update['duration'] ?? 0;
                $listenedPercentage = $update['listened_percentage'] ?? 0;
                $playbackStatus = $update['playback_status'] ?? 'playing';
                $audioStatus = $update['audio_status'] ?? 'in_progress';
                $audioPackageId = $update['audio_package_id'] ?? null;

                if ($courseId && $contentId) {
                    // Get or create progress record
                    $progress = $this->audioProgressModel->getOrCreateProgress(
                        $userId, $courseId, $contentId, $audioPackageId, $clientId
                    );

                    if ($progress) {
                        $updateData = [
                            'current_time' => $currentTime,
                            'duration' => $duration,
                            'listened_percentage' => $listenedPercentage,
                            'is_completed' => $listenedPercentage >= 80 ? 1 : 0,
                            'audio_status' => $audioStatus,
                            'playback_status' => $playbackStatus,
                            'play_count' => $progress['play_count'] + 1,
                            'playback_speed' => $update['playback_speed'] ?? 1.0,
                            'notes' => $update['notes'] ?? ''
                        ];
                        
                        $success = $this->audioProgressModel->updateProgress($progress['id'], $updateData);
                        
                        if ($success) {
                            $successCount++;
                        }
                    }
                }
            }
            
            echo json_encode([
                'success' => true, 
                'message' => "Batch update completed: $successCount/$totalCount",
                'success_count' => $successCount,
                'total_count' => $totalCount
            ]);

        } catch (Exception $e) {
            error_log("Audio batch update error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to process batch update']);
        }
    }

    /**
     * Get audio progress for a course (AJAX endpoint)
     */
    public function getProgress() {
        // Check authentication
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $courseId = $_GET['course_id'] ?? null;

        if (!$clientId || !$courseId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            $progress = $this->audioProgressModel->getUserAudioProgress($userId, $courseId, $clientId);
            
            echo json_encode([
                'success' => true,
                'progress' => $progress
            ]);

        } catch (Exception $e) {
            error_log("Audio progress fetch error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch progress']);
        }
    }

    /**
     * Mark audio as completed (AJAX endpoint)
     */
    public function markCompleted() {
        // Check authentication
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $progressId = $_POST['progress_id'] ?? null;

        if (!$progressId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Progress ID required']);
            exit;
        }

        try {
            $success = $this->audioProgressModel->markAsCompleted($progressId);
            
            if ($success) {
                // Get progress details for prerequisite tracking
                $progress = $this->audioProgressModel->getProgressById($progressId);
                if ($progress) {
                    $this->markPrerequisiteCompleteIfApplicable($progress['user_id'], $progress['course_id'], $progress['content_id'], $progress['client_id']);
                }
                
                echo json_encode(['success' => true, 'message' => 'Audio marked as completed']);
            } else {
                throw new Exception('Failed to mark as completed');
            }

        } catch (Exception $e) {
            error_log("Audio completion error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to mark as completed']);
        }
    }

    /**
     * Get resume position for audio content
     */
    public function getResumePosition() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check authentication
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            error_log("Audio resume position: Session not available. Session data: " . print_r($_SESSION, true));
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized - Session not available']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_GET['course_id'] ?? null;
        $contentId = $_GET['content_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;

        error_log("Audio resume position request: userId=$userId, courseId=$courseId, contentId=$contentId, clientId=$clientId");

        if (!$courseId || !$contentId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Course ID, content ID, and client ID required']);
            exit;
        }

        try {
            $progress = $this->audioProgressModel->getProgress($userId, $courseId, $contentId, $clientId);
            error_log("Audio resume position: Progress data: " . print_r($progress, true));
            
            if ($progress && $progress['current_time'] > 0) {
                echo json_encode([
                    'success' => true,
                    'resume_position' => $progress['current_time'],
                    'duration' => $progress['duration'],
                    'listened_percentage' => $progress['listened_percentage'],
                    'play_count' => $progress['play_count'],
                    'last_listened_at' => $progress['last_listened_at']
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'resume_position' => 0,
                    'duration' => 0,
                    'listened_percentage' => 0,
                    'play_count' => 0,
                    'last_listened_at' => null
                ]);
            }

        } catch (Exception $e) {
            error_log("Audio resume position error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to get resume position']);
        }
    }

    /**
     * Update audio status (AJAX endpoint)
     */
    public function updateStatus() {
        // Check authentication
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $progressId = $_POST['progress_id'] ?? null;
        $status = $_POST['status'] ?? null;

        if (!$progressId || !$status) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Progress ID and status required']);
            exit;
        }

        try {
            $success = $this->audioProgressModel->updateAudioStatus($progressId, $status);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Audio status updated']);
            } else {
                throw new Exception('Failed to update audio status');
            }

        } catch (Exception $e) {
            error_log("Audio status update error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update audio status']);
        }
    }

    /**
     * Update playback status (AJAX endpoint)
     */
    public function updatePlaybackStatus() {
        // Check authentication
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $progressId = $_POST['progress_id'] ?? null;
        $playbackStatus = $_POST['playback_status'] ?? null;

        if (!$progressId || !$playbackStatus) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Progress ID and playback status required']);
            exit;
        }

        try {
            $success = $this->audioProgressModel->updatePlaybackStatus($progressId, $playbackStatus);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Playback status updated']);
            } else {
                throw new Exception('Failed to update playback status');
            }

        } catch (Exception $e) {
            error_log("Playback status update error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update playback status']);
        }
    }

    /**
     * Get audio progress summary (AJAX endpoint)
     */
    public function getSummary() {
        // Check authentication
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;

        if (!$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Client ID not found']);
            exit;
        }

        try {
            $summary = $this->audioProgressModel->getAudioProgressSummary($userId, $clientId);
            
            echo json_encode([
                'success' => true,
                'summary' => $summary
            ]);

        } catch (Exception $e) {
            error_log("Audio summary error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch summary']);
        }
    }

    /**
     * Get audio content info (AJAX endpoint)
     */
    public function getContentInfo() {
        // Check authentication
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $contentId = $_GET['content_id'] ?? null;

        if (!$contentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Content ID required']);
            exit;
        }

        try {
            // Get audio package ID from course_module_content
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();

            $sql = "SELECT content_id as audio_package_id FROM course_module_content 
                    WHERE id = ? AND content_type = 'audio'";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$contentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'audio_package_id' => $result['audio_package_id']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Audio content not found'
                ]);
            }

        } catch (Exception $e) {
            error_log("Audio content info error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch content info']);
        }
    }

    /**
     * Check if audio is a prerequisite and start tracking
     */
    private function startPrerequisiteTrackingIfApplicable($userId, $courseId, $contentId, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Check if this audio is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'audio');
            
            if ($isPrerequisite) {
                $completionService->startPrerequisiteTracking($userId, $courseId, $contentId, 'audio', $clientId);
            }
        } catch (Exception $e) {
            error_log("Error in startPrerequisiteTrackingIfApplicable: " . $e->getMessage());
        }
    }

    /**
     * Start module tracking if audio belongs to a module
     */
    private function startModuleTrackingIfApplicable($userId, $courseId, $contentId, $contentType, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Start module tracking if this content belongs to a module
            $completionService->startModuleTrackingIfApplicable($userId, $courseId, $contentId, $contentType, $clientId);
        } catch (Exception $e) {
            error_log("Error in startModuleTrackingIfApplicable: " . $e->getMessage());
        }
    }

    /**
     * Check if audio is a prerequisite and mark as complete
     */
    private function markPrerequisiteCompleteIfApplicable($userId, $courseId, $contentId, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Check if this audio is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'audio');
            
            if ($isPrerequisite) {
                $completionService->markPrerequisiteComplete($userId, $courseId, $contentId, 'audio', $clientId);
            }
        } catch (Exception $e) {
            error_log("Error in markPrerequisiteCompleteIfApplicable: " . $e->getMessage());
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
