<?php
require_once 'models/ProgressTrackingModel.php';
require_once 'config/Localization.php';

class ProgressTrackingController {
    private $progressModel;

    public function __construct() {
        $this->progressModel = new ProgressTrackingModel();
    }

    // =====================================================
    // COURSE PROGRESS MANAGEMENT
    // =====================================================

    /**
     * Initialize course progress for a user
     */
    public function initializeProgress() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        
        if (!$courseId) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID is required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        // Check if user has access to the course
        error_log("ProgressTrackingController: Checking access for user $userId, course $courseId, client $clientId");
        $hasAccess = $this->progressModel->hasCourseAccess($userId, $courseId, $clientId);
        error_log("ProgressTrackingController: hasCourseAccess result: " . ($hasAccess ? 'true' : 'false'));
        
        if (!$hasAccess) {
            $this->jsonResponse(['success' => false, 'message' => 'Access denied to this course']);
            return;
        }

        try {
            $progress = $this->progressModel->initializeCourseProgress($userId, $courseId, $clientId);
            
            if ($progress) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Progress initialized successfully',
                    'progress' => $progress
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to initialize progress']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::initializeProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Get course progress for a user
     */
    public function getProgress() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $courseId = $_GET['course_id'] ?? null;
        
        if (!$courseId) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID is required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            $progress = $this->progressModel->getCourseProgress($userId, $courseId, $clientId);
            
            if ($progress) {
                $this->jsonResponse([
                    'success' => true,
                    'progress' => $progress
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Progress not found']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::getProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Update course progress
     */
    public function updateProgress() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $data = $input['data'] ?? [];
        
        if (!$courseId || empty($data)) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID and data are required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            $result = $this->progressModel->updateCourseProgress($userId, $courseId, $clientId, $data);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Progress updated successfully'
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update progress']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::updateProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    // =====================================================
    // MODULE PROGRESS MANAGEMENT
    // =====================================================

    /**
     * Update module progress
     */
    public function updateModuleProgress() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $moduleId = $input['module_id'] ?? null;
        $data = $input['data'] ?? [];
        
        if (!$courseId || !$moduleId || empty($data)) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID, module ID, and data are required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            $result = $this->progressModel->updateModuleProgress($userId, $courseId, $moduleId, $clientId, $data);
            
            if ($result) {
                // Recalculate overall course progress
                $this->progressModel->calculateCourseProgress($userId, $courseId, $clientId);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Module progress updated successfully'
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update module progress']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::updateModuleProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    // =====================================================
    // CONTENT PROGRESS MANAGEMENT
    // =====================================================

    /**
     * Update content progress (generic method)
     */
    public function updateContentProgress() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $contentId = $input['content_id'] ?? null;
        $contentType = $input['content_type'] ?? null;
        $data = $input['data'] ?? [];
        
        error_log("DEBUG: updateContentProgress called - courseId: $courseId, contentId: $contentId, contentType: $contentType");
        error_log("DEBUG: updateContentProgress data: " . json_encode($data));
        
        if (!$courseId || !$contentId || !$contentType || empty($data)) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID, content ID, content type, and data are required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];
        
        error_log("DEBUG: updateContentProgress - userId: $userId, clientId: $clientId");

        try {
            $result = false;
            
            // Update content-specific progress based on type
            switch ($contentType) {
                case 'scorm':
                    error_log("DEBUG: updateContentProgress - calling updateScormProgress");
                    $result = $this->progressModel->updateScormProgress($userId, $courseId, $contentId, $clientId, $data);
                    break;
                case 'assignment':
                    error_log("DEBUG: updateContentProgress - calling updateAssignmentProgress");
                    $result = $this->progressModel->updateAssignmentProgress($userId, $courseId, $contentId, $clientId, $data);
                    break;
                case 'video':
                    error_log("DEBUG: updateContentProgress - calling updateVideoProgress");
                    $result = $this->progressModel->updateVideoProgress($userId, $courseId, $contentId, $clientId, $data);
                    break;
                case 'audio':
                    error_log("DEBUG: updateContentProgress - calling updateAudioProgress");
                    $result = $this->progressModel->updateAudioProgress($userId, $courseId, $contentId, $clientId, $data);
                    break;
                case 'document':
                    error_log("DEBUG: updateContentProgress - calling updateDocumentProgress");
                    $result = $this->progressModel->updateDocumentProgress($userId, $courseId, $contentId, $clientId, $data);
                    break;
                case 'interactive':
                    error_log("DEBUG: updateContentProgress - calling updateInteractiveProgress");
                    $result = $this->progressModel->updateInteractiveProgress($userId, $courseId, $contentId, $clientId, $data);
                    break;
                case 'external':
                    error_log("DEBUG: updateContentProgress - calling updateExternalProgress");
                    $result = $this->progressModel->updateExternalProgress($userId, $courseId, $contentId, $clientId, $data);
                    break;
                default:
                    error_log("DEBUG: updateContentProgress - unsupported content type: $contentType");
                    $this->jsonResponse(['success' => false, 'message' => 'Unsupported content type']);
                    return;
            }
            
            error_log("DEBUG: updateContentProgress - result: " . ($result ? 'SUCCESS' : 'FAILED'));
            
            if ($result) {
                // Recalculate overall course progress
                $this->progressModel->calculateCourseProgress($userId, $courseId, $clientId);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Content progress updated successfully'
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update content progress']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::updateContentProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    // =====================================================
    // SCORM PROGRESS TRACKING
    // =====================================================

    /**
     * Update SCORM progress
     */
    public function updateScormProgress() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $contentId = $input['content_id'] ?? null;
        $data = $input['data'] ?? [];
        
        if (!$courseId || !$contentId || empty($data)) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID, content ID, and data are required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            $result = $this->progressModel->updateScormProgress($userId, $courseId, $contentId, $clientId, $data);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'SCORM progress updated successfully'
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update SCORM progress']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::updateScormProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    // =====================================================
    // VIDEO PROGRESS TRACKING
    // =====================================================

    /**
     * Update video progress
     */
    public function updateVideoProgress() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $contentId = $input['content_id'] ?? null;
        $data = $input['data'] ?? [];
        
        if (!$courseId || !$contentId || empty($data)) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID, content ID, and data are required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            $result = $this->progressModel->updateVideoProgress($userId, $courseId, $contentId, $clientId, $data);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Video progress updated successfully'
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update video progress']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::updateVideoProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    // =====================================================
    // AUDIO PROGRESS TRACKING
    // =====================================================

    /**
     * Update audio progress
     */
    public function updateAudioProgress() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $contentId = $input['content_id'] ?? null;
        $data = $input['data'] ?? [];
        
        if (!$courseId || !$contentId || empty($data)) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID, content ID, and data are required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            $result = $this->progressModel->updateAudioProgress($userId, $courseId, $contentId, $clientId, $data);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Audio progress updated successfully'
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update audio progress']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::updateAudioProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    // =====================================================
    // DOCUMENT PROGRESS TRACKING
    // =====================================================

    /**
     * Update document progress
     */
    public function updateDocumentProgress() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $contentId = $input['content_id'] ?? null;
        $data = $input['data'] ?? [];
        
        if (!$courseId || !$contentId || empty($data)) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID, content ID, and data are required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            $result = $this->progressModel->updateDocumentProgress($userId, $courseId, $contentId, $clientId, $data);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Document progress updated successfully'
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update document progress']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::updateDocumentProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    // =====================================================
    // INTERACTIVE CONTENT PROGRESS TRACKING
    // =====================================================

    /**
     * Update interactive content progress
     */
    public function updateInteractiveProgress() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $contentId = $input['content_id'] ?? null;
        $data = $input['data'] ?? [];
        
        if (!$courseId || !$contentId || empty($data)) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID, content ID, and data are required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            $result = $this->progressModel->updateInteractiveProgress($userId, $courseId, $contentId, $clientId, $data);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Interactive content progress updated successfully'
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update interactive content progress']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::updateInteractiveProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    // =====================================================
    // EXTERNAL CONTENT PROGRESS TRACKING
    // =====================================================

    /**
     * Update external content progress
     */
    public function updateExternalProgress() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $contentId = $input['content_id'] ?? null;
        $data = $input['data'] ?? [];
        
        if (!$courseId || !$contentId || empty($data)) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID, content ID, and data are required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            $result = $this->progressModel->updateExternalProgress($userId, $courseId, $contentId, $clientId, $data);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'External content progress updated successfully'
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update external content progress']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::updateExternalProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    // =====================================================
    // RESUME FUNCTIONALITY
    // =====================================================

    /**
     * Get resume position for a user in a course
     */
    public function getResumePosition() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $courseId = $_GET['course_id'] ?? null;
        $contentId = $_GET['content_id'] ?? null;
        
        if (!$courseId) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID is required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            // If content_id is provided, get specific content resume data
            if ($contentId) {
                $resumeData = $this->progressModel->getContentResumePosition($userId, $courseId, $contentId, $clientId);
            } else {
                $resumeData = $this->progressModel->getResumePosition($userId, $courseId, $clientId);
            }
            
            if ($resumeData) {
                $this->jsonResponse([
                    'success' => true,
                    'resume_data' => $resumeData
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Resume position not found']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::getResumePosition error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Set resume position for a user in a course
     */
    public function setResumePosition() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $moduleId = $input['module_id'] ?? null;
        $contentId = $input['content_id'] ?? null;
        $resumePosition = $input['resume_position'] ?? null;
        
        if (!$courseId) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID is required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            $data = [
                'current_module_id' => $moduleId,
                'current_content_id' => $contentId,
                'resume_position' => json_encode($resumePosition)
            ];
            
            $result = $this->progressModel->updateCourseProgress($userId, $courseId, $clientId, $data);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Resume position set successfully'
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to set resume position']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::setResumePosition error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    // =====================================================
    // PROGRESS CALCULATION
    // =====================================================

    /**
     * Calculate course progress
     */
    public function calculateProgress() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $courseId = $_GET['course_id'] ?? null;
        
        if (!$courseId) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID is required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            $progress = $this->progressModel->calculateCourseProgress($userId, $courseId, $clientId);
            
            if ($progress) {
                $this->jsonResponse([
                    'success' => true,
                    'progress' => $progress
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to calculate progress']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::calculateProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    // =====================================================
    // USER PROGRESS SUMMARY
    // =====================================================

    /**
     * Get user's progress summary for all courses
     */
    public function getUserProgressSummary() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            $summary = $this->progressModel->getUserProgressSummary($userId, $clientId);
            
            if ($summary !== false) {
                $this->jsonResponse([
                    'success' => true,
                    'summary' => $summary
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to get progress summary']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::getUserProgressSummary error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    // =====================================================
    // CONTENT PROGRESS RETRIEVAL
    // =====================================================

    /**
     * Get latest progress for specific content
     */
    public function getContentProgress() {
        if (!isset($_SESSION['user']['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access']);
            return;
        }

        $courseId = $_GET['course_id'] ?? null;
        $contentId = $_GET['content_id'] ?? null;
        $contentType = $_GET['content_type'] ?? null;
        
        if (!$courseId || !$contentId || !$contentType) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID, Content ID, and Content Type are required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'];

        try {
            $progress = $this->progressModel->getContentProgress($userId, $courseId, $contentId, $contentType, $clientId);
            
            if ($progress) {
                $this->jsonResponse([
                    'success' => true,
                    'progress' => $progress
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Progress not found']);
            }
        } catch (Exception $e) {
            error_log("ProgressTrackingController::getContentProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    // =====================================================
    // UTILITY METHODS
    // =====================================================

    /**
     * Send JSON response
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

