<?php
require_once 'models/AudioProgressModel.php';
require_once 'models/SharedContentCompletionService.php';
require_once 'models/CourseModel.php';

class AudioProgressController {
    private $audioProgressModel;
    private $courseModel;
    private $sharedContentService;

    public function __construct() {
        $this->audioProgressModel = new AudioProgressModel();
        $this->courseModel = new CourseModel();
        $this->sharedContentService = new SharedContentCompletionService();
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
                // If audio is completed, handle shared completion
                if ($isCompleted) {
                    // Determine content type and IDs for shared completion
                    $contentType = 'module'; // Default to module
                    $prerequisiteId = null;
                    $audioPackageId = $audioPackageId;
                    
                    // Check if this is a prerequisite by looking at the progress record
                    if ($progress['prerequisite_id'] && !$progress['content_id']) {
                        $contentType = 'prerequisite';
                        $prerequisiteId = $progress['prerequisite_id'];
                    }
                    
                    // Handle shared audio completion
                    $this->handleSharedAudioCompletion(
                        $userId, $courseId, $contentId, $clientId, 
                        $contentType, $prerequisiteId, $audioPackageId
                    );
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
                // If audio is completed, handle shared completion
                if ($updateData['is_completed']) {
                    // Determine content type and IDs for shared completion
                    $contentType = 'module'; // Default to module
                    $prerequisiteId = null;
                    $audioPackageId = $audioPackageId;
                    
                    // Check if this is a prerequisite by looking at the progress record
                    if ($progress['prerequisite_id'] && !$progress['content_id']) {
                        $contentType = 'prerequisite';
                        $prerequisiteId = $progress['prerequisite_id'];
                    }
                    
                    // Handle shared audio completion
                    $this->handleSharedAudioCompletion(
                        $userId, $courseId, $contentId, $clientId, 
                        $contentType, $prerequisiteId, $audioPackageId
                    );
                }
                
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
                    
                    // Determine content type and IDs for shared completion
                    $contentType = 'module'; // Default to module
                    $prerequisiteId = null;
                    $audioPackageId = $progress['audio_package_id'];
                    
                    // Check if this is a prerequisite by looking at the progress record
                    if ($progress['prerequisite_id'] && !$progress['content_id']) {
                        $contentType = 'prerequisite';
                        $prerequisiteId = $progress['prerequisite_id'];
                    }
                    
                    // Handle shared audio completion
                    $this->handleSharedAudioCompletion(
                        $progress['user_id'], $progress['course_id'], $progress['content_id'], 
                        $progress['client_id'], $contentType, $prerequisiteId, $audioPackageId
                    );
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
            // Get audio package ID from the content info endpoint
            $audioPackageId = $this->getAudioPackageId($contentId);
            
            if (!$audioPackageId) {
                error_log("Audio resume position: Could not get audio package ID for content $contentId");
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Could not determine audio package ID']);
                exit;
            }
            
            // Get or create progress record
            $progress = $this->audioProgressModel->getOrCreateProgress($userId, $courseId, $contentId, $audioPackageId, $clientId);
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
            // Get audio package ID from course_module_content or course_prerequisites
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();

            // First try course_module_content
            $sql = "SELECT content_id as audio_package_id FROM course_module_content 
                    WHERE id = ? AND content_type = 'audio'";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$contentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                // If not found in course_module_content, try course_prerequisites
                $sql = "SELECT prerequisite_id as audio_package_id FROM course_prerequisites 
                        WHERE id = ? AND prerequisite_type = 'audio'";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$contentId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }

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
     * Get audio package ID for a given content ID
     */
    private function getAudioPackageId($contentId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();

            // First try course_module_content
            $sql = "SELECT content_id as audio_package_id FROM course_module_content 
                    WHERE id = ? AND content_type = 'audio'";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$contentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                // If not found in course_module_content, try course_prerequisites
                $sql = "SELECT prerequisite_id as audio_package_id FROM course_prerequisites 
                        WHERE id = ? AND prerequisite_type = 'audio'";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$contentId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return $result ? $result['audio_package_id'] : null;
        } catch (Exception $e) {
            error_log("Error getting audio package ID: " . $e->getMessage());
            return null;
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
    
    /**
     * Handle shared audio completion (auto-complete if same audio exists in modules/prerequisites)
     */
    private function handleSharedAudioCompletion($userId, $courseId, $contentId, $clientId, $contentType, $prerequisiteId = null, $audioPackageId = null) {
        try {
            // Determine the actual audio package ID that was completed
            $completedAudioId = null;
            
            if ($contentType === 'prerequisite' && $audioPackageId) {
                $completedAudioId = $audioPackageId;
            } else {
                $completedAudioId = $contentId;
            }
            
            if (!$completedAudioId) {
                error_log("[AUDIO] handleSharedAudioCompletion - No audio ID to work with");
                return;
            }
            
            error_log("[AUDIO] handleSharedAudioCompletion - Processing audio ID: $completedAudioId for user: $userId, course: $courseId");
            
            // Get the latest audio progress for this audio package
            $audioProgress = $this->getLatestAudioProgress($completedAudioId, $userId, $courseId, $clientId);
            
            if (!$audioProgress) {
                error_log("[AUDIO] handleSharedAudioCompletion - No audio progress found");
                return;
            }
            
            // Check if this audio also exists in modules (if completed as prerequisite)
            if ($contentType === 'prerequisite') {
                $moduleContents = $this->getModuleContentsForAudio($courseId, $completedAudioId);
                
                if (!empty($moduleContents)) {
                    // Create duplicate entries for each module content
                    foreach ($moduleContents as $moduleContent) {
                        $this->createModuleAudioProgress($audioProgress, $moduleContent, $userId, $courseId, $clientId);
                    }
                    
                    error_log("[AUDIO] Created module audio progress for shared audio $completedAudioId in course $courseId for user $userId");
                }
            }
            
            // Check if this audio also exists as a prerequisite (if completed as module)
            if ($contentType !== 'prerequisite') {
                $prerequisiteIds = $this->getPrerequisiteIdsForAudio($courseId, $completedAudioId);
                
                if (!empty($prerequisiteIds)) {
                    // Create duplicate entries for each prerequisite
                    foreach ($prerequisiteIds as $prereqId) {
                        $this->createPrerequisiteAudioProgress($audioProgress, $prereqId, $userId, $courseId, $clientId);
                    }
                    
                    error_log("[AUDIO] Created prerequisite audio progress for shared audio $completedAudioId in course $courseId for user $userId");
                }
            }
            
        } catch (Exception $e) {
            error_log("[AUDIO] Error handling shared audio completion: " . $e->getMessage());
        }
    }
    
    /**
     * Get the latest audio progress for a given audio package
     */
    private function getLatestAudioProgress($audioId, $userId, $courseId, $clientId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            // Try to find progress by content_id first (for module content)
            $sql = "SELECT * FROM audio_progress 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                    ORDER BY updated_at DESC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $audioId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result;
            }
            
            // If not found, try by prerequisite_id (for prerequisite content)
            $sql = "SELECT * FROM audio_progress 
                    WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?
                    ORDER BY updated_at DESC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $audioId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result;
            }
            
            // If still not found, try by audio_package_id (for any content with this audio package)
            $sql = "SELECT * FROM audio_progress 
                    WHERE user_id = ? AND course_id = ? AND audio_package_id = ? AND client_id = ?
                    ORDER BY updated_at DESC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $audioId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("[AUDIO] Error getting latest audio progress: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get module contents that contain this audio
     */
    private function getModuleContentsForAudio($courseId, $audioId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT cmc.id as content_id, cmc.module_id, cmc.content_id as audio_id
                    FROM course_module_content cmc
                    JOIN course_modules cm ON cmc.module_id = cm.id
                    WHERE cm.course_id = ? AND cmc.content_id = ? AND cmc.content_type = 'audio' 
                    AND cmc.deleted_at IS NULL AND cm.deleted_at IS NULL";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId, $audioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("[AUDIO] Error getting module contents for audio: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get prerequisite IDs that contain this audio
     */
    private function getPrerequisiteIdsForAudio($courseId, $audioId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT cp.id as prerequisite_id
                    FROM course_prerequisites cp
                    WHERE cp.course_id = ? AND cp.prerequisite_id = ? AND cp.prerequisite_type = 'audio'
                    AND cp.deleted_at IS NULL";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId, $audioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("[AUDIO] Error getting prerequisite IDs for audio: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create audio progress entry for module content
     */
    private function createModuleAudioProgress($audioProgress, $moduleContent, $userId, $courseId, $clientId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            // Check if progress already exists for this module content
            $checkSql = "SELECT id FROM audio_progress 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$userId, $courseId, $moduleContent['content_id'], $clientId]);
            
            if ($checkStmt->fetch()) {
                error_log("[AUDIO] Module audio progress already exists for content {$moduleContent['content_id']}");
                return;
            }
            
            // Create new progress entry for module content
            $sql = "INSERT INTO audio_progress (
                        user_id, course_id, content_id, audio_package_id, client_id,
                        started_at, `current_time`, duration, listened_percentage, completion_threshold,
                        is_completed, audio_status, playback_status, play_count, last_listened_at,
                        playback_speed, notes, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, NOW(), NOW()
                    )";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                $userId, $courseId, $moduleContent['content_id'], $audioProgress['audio_package_id'], $clientId,
                $audioProgress['started_at'], $audioProgress['current_time'], $audioProgress['duration'], 
                $audioProgress['listened_percentage'], $audioProgress['completion_threshold'],
                $audioProgress['is_completed'], $audioProgress['audio_status'], $audioProgress['playback_status'],
                $audioProgress['play_count'], $audioProgress['last_listened_at'],
                $audioProgress['playback_speed'], $audioProgress['notes']
            ]);
            
            if ($result) {
                error_log("[AUDIO] Created module audio progress for content {$moduleContent['content_id']}");
            } else {
                error_log("[AUDIO] Failed to create module audio progress for content {$moduleContent['content_id']}");
            }
            
        } catch (Exception $e) {
            error_log("[AUDIO] Error creating module audio progress: " . $e->getMessage());
        }
    }
    
    /**
     * Create audio progress entry for prerequisite
     */
    private function createPrerequisiteAudioProgress($audioProgress, $prerequisiteId, $userId, $courseId, $clientId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            // Check if progress already exists for this prerequisite
            $checkSql = "SELECT id FROM audio_progress 
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$userId, $courseId, $prerequisiteId, $clientId]);
            
            if ($checkStmt->fetch()) {
                error_log("[AUDIO] Prerequisite audio progress already exists for prerequisite {$prerequisiteId}");
                return;
            }
            
            // Create new progress entry for prerequisite
            $sql = "INSERT INTO audio_progress (
                        user_id, course_id, prerequisite_id, audio_package_id, client_id,
                        started_at, `current_time`, duration, listened_percentage, completion_threshold,
                        is_completed, audio_status, playback_status, play_count, last_listened_at,
                        playback_speed, notes, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, NOW(), NOW()
                    )";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                $userId, $courseId, $prerequisiteId, $audioProgress['audio_package_id'], $clientId,
                $audioProgress['started_at'], $audioProgress['current_time'], $audioProgress['duration'], 
                $audioProgress['listened_percentage'], $audioProgress['completion_threshold'],
                $audioProgress['is_completed'], $audioProgress['audio_status'], $audioProgress['playback_status'],
                $audioProgress['play_count'], $audioProgress['last_listened_at'],
                $audioProgress['playback_speed'], $audioProgress['notes']
            ]);
            
            if ($result) {
                error_log("[AUDIO] Created prerequisite audio progress for prerequisite {$prerequisiteId}");
            } else {
                error_log("[AUDIO] Failed to create prerequisite audio progress for prerequisite {$prerequisiteId}");
            }
            
        } catch (Exception $e) {
            error_log("[AUDIO] Error creating prerequisite audio progress: " . $e->getMessage());
        }
    }
}
?>
