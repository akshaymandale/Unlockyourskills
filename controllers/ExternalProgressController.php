<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'models/ExternalProgressModel.php';

class ExternalProgressController {
    private $externalProgressModel;

    public function __construct() {
        $this->externalProgressModel = new ExternalProgressModel();
    }

    /**
     * Record visit to external content
     */
    public function recordVisit() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_POST['course_id'] ?? null;
        $contentId = $_POST['content_id'] ?? null;
        $externalPackageId = $_POST['external_package_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;

        if (!$userId || !$courseId || !$contentId || !$externalPackageId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            $result = $this->externalProgressModel->recordVisit(
                $userId, $courseId, $contentId, $externalPackageId, $clientId
            );

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Visit recorded successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to record visit']);
            }

        } catch (Exception $e) {
            error_log("Error in recordVisit: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Update time spent on external content
     */
    public function updateTimeSpent() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_POST['course_id'] ?? null;
        $contentId = $_POST['content_id'] ?? null;
        $timeSpent = $_POST['time_spent'] ?? 0;
        $isCompleted = isset($_POST['is_completed']) ? $_POST['is_completed'] : null;
        $clientId = $_SESSION['user']['client_id'] ?? null;

        if (!$userId || !$courseId || !$contentId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            // Get current progress to preserve existing completion status
            $currentProgress = $this->externalProgressModel->getProgress($userId, $courseId, $contentId, $clientId);
            $currentIsCompleted = $currentProgress ? $currentProgress['is_completed'] : 0;
            $currentCompletionNotes = $currentProgress ? $currentProgress['completion_notes'] : null;
            
            // Use updateProgress method instead of updateTimeSpent to get the calculation logic
            $updateData = [
                'visit_count' => 1, // Default visit count
                'time_spent' => $timeSpent,
                'is_completed' => $isCompleted !== null ? $isCompleted : $currentIsCompleted,
                'completion_notes' => $currentCompletionNotes // Preserve existing completion notes
            ];

            $result = $this->externalProgressModel->updateProgress(
                $userId, $courseId, $contentId, $clientId, $updateData
            );

            if ($result) {
                // Update prerequisite completion if this external content is a prerequisite
                $this->updatePrerequisiteCompletionIfApplicable($userId, $courseId, $contentId, $clientId);
                
                echo json_encode(['success' => true, 'message' => 'Time spent updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update time spent']);
            }

        } catch (Exception $e) {
            error_log("Error in updateTimeSpent: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Mark external content as completed
     */
    public function markCompleted() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_POST['course_id'] ?? null;
        $contentId = $_POST['content_id'] ?? null;
        $completionNotes = $_POST['completion_notes'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;

        if (!$userId || !$courseId || !$contentId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            // Note: Completion tracking is now handled only when content is actually completed

            $result = $this->externalProgressModel->markCompleted(
                $userId, $courseId, $contentId, $clientId, $completionNotes
            );

            if ($result) {
                // Update completion tracking
                require_once 'models/CompletionTrackingService.php';
                $completionService = new CompletionTrackingService();
                $completionService->handleContentCompletion($userId, $courseId, $contentId, 'external', $clientId);
                
                // Completion tracking is already handled by handleContentCompletion above

                echo json_encode(['success' => true, 'message' => 'Content marked as completed']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to mark as completed']);
            }

        } catch (Exception $e) {
            error_log("Error in markCompleted: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Get external content statistics
     */
    public function getStatistics() {
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
            $result = $this->externalProgressModel->getContentStatistics(
                $userId, $courseId, $contentId, $clientId
            );

            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Error in getStatistics: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Get user's course progress for external content
     */
    public function getCourseProgress() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_GET['course_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;

        if (!$userId || !$courseId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            $result = $this->externalProgressModel->getCourseProgress(
                $userId, $courseId, $clientId
            );

            if ($result !== false) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to get course progress']);
            }

        } catch (Exception $e) {
            error_log("Error in getCourseProgress: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Update external content progress (comprehensive update)
     */
    public function updateProgress() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $courseId = $_POST['course_id'] ?? null;
        $contentId = $_POST['content_id'] ?? null;
        $externalPackageId = $_POST['external_package_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $visitCount = $_POST['visit_count'] ?? 1;
        $timeSpent = $_POST['time_spent'] ?? 0;
        $isCompleted = $_POST['is_completed'] ?? 0;
        $completionNotes = $_POST['completion_notes'] ?? null;

        if (!$userId || !$courseId || !$contentId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            // First ensure progress record exists
            if ($externalPackageId) {
                $progress = $this->externalProgressModel->getOrCreateProgress(
                    $userId, $courseId, $contentId, $externalPackageId, $clientId
                );

                if (!$progress) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to create progress record']);
                    exit;
                }
            }

            // Update progress
            $updateData = [
                'visit_count' => $visitCount,
                'time_spent' => $timeSpent,
                'is_completed' => $isCompleted,
                'completion_notes' => $completionNotes
            ];

            $result = $this->externalProgressModel->updateProgress(
                $userId, $courseId, $contentId, $clientId, $updateData
            );

            if ($result) {
                // Update prerequisite completion if this external content is a prerequisite
                $this->updatePrerequisiteCompletionIfApplicable($userId, $courseId, $contentId, $clientId);
                
                echo json_encode(['success' => true, 'message' => 'Progress updated successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update progress']);
            }

        } catch (Exception $e) {
            error_log("Error in updateProgress: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Get user's overall external content completion rate
     */
    public function getCompletionRate() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;

        if (!$userId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            $result = $this->externalProgressModel->getUserCompletionRate($userId, $clientId);

            if ($result !== false) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to get completion rate']);
            }

        } catch (Exception $e) {
            error_log("Error in getCompletionRate: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Get progress by content type
     */
    public function getContentTypeProgress() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;

        if (!$userId || !$clientId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            $result = $this->externalProgressModel->getContentTypeProgress($userId, $clientId);

            if ($result !== false) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to get content type progress']);
            }

        } catch (Exception $e) {
            error_log("Error in getContentTypeProgress: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Batch update for multiple progress records (for performance)
     */
    public function batchUpdate() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $updates = json_decode($_POST['updates'] ?? '[]', true);

        if (!$userId || !$clientId || empty($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required parameters or updates']);
            exit;
        }

        try {
            $successCount = 0;
            $errorCount = 0;

            foreach ($updates as $update) {
                $courseId = $update['course_id'] ?? null;
                $contentId = $update['content_id'] ?? null;
                $timeSpent = $update['time_spent'] ?? 0;

                if ($courseId && $contentId) {
                    $result = $this->externalProgressModel->updateTimeSpent(
                        $userId, $courseId, $contentId, $clientId, $timeSpent
                    );

                    if ($result) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                } else {
                    $errorCount++;
                }
            }

            echo json_encode([
                'success' => true,
                'message' => "Batch update completed: {$successCount} successful, {$errorCount} errors",
                'successful_updates' => $successCount,
                'failed_updates' => $errorCount
            ]);

        } catch (Exception $e) {
            error_log("Error in batchUpdate: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Check if external content is a prerequisite and start tracking
     */
    private function startPrerequisiteTrackingIfApplicable($userId, $courseId, $contentId, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Check if this external content is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'external');
            
            if ($isPrerequisite) {
                $completionService->startPrerequisiteTracking($userId, $courseId, $contentId, 'external', $clientId);
            }
        } catch (Exception $e) {
            error_log("Error in startPrerequisiteTrackingIfApplicable: " . $e->getMessage());
        }
    }

    /**
     * Start module tracking if external content belongs to a module
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
     * Check if external content is a prerequisite and mark as complete
     */
    private function markPrerequisiteCompleteIfApplicable($userId, $courseId, $contentId, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Check if this external content is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'external');
            
            if ($isPrerequisite) {
                $completionService->markPrerequisiteComplete($userId, $courseId, $contentId, 'external', $clientId);
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

    /**
     * Update prerequisite completion if external content is a prerequisite
     */
    private function updatePrerequisiteCompletionIfApplicable($userId, $courseId, $contentId, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Check if this external content is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'external');
            
            if ($isPrerequisite) {
                $completionService->updatePrerequisiteCompletion($userId, $courseId, $contentId, 'external', $clientId);
            }
        } catch (Exception $e) {
            error_log("Error in updatePrerequisiteCompletionIfApplicable: " . $e->getMessage());
        }
    }
}
