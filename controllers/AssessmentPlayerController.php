<?php
require_once 'models/AssessmentPlayerModel.php';
require_once 'core/IdEncryption.php';
require_once 'config/Localization.php';

class AssessmentPlayerController
{
    private $model;

    public function __construct()
    {
        $this->model = new AssessmentPlayerModel();
    }

    // Start assessment - show assessment player
    public function start($assessmentId = null)
    {
        if (!isset($_SESSION['user']['id'])) {
            header('Location: /unlockyourskills/login');
            exit;
        }

        if (!$assessmentId) {
            $assessmentId = $_GET['id'] ?? null;
        }

        if (!$assessmentId) {
            header('Location: /unlockyourskills/my-courses');
            exit;
        }

        $decryptedId = IdEncryption::getId($assessmentId);
        if (!$decryptedId) {
            header('Location: /unlockyourskills/my-courses');
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;

        // Get assessment data
        $assessment = $this->model->getAssessmentWithQuestions($decryptedId, $clientId);
        if (!$assessment) {
            header('Location: /unlockyourskills/my-courses');
            exit;
        }

        // Check if user can take this assessment
        if (!$this->model->canUserTakeAssessment($decryptedId, $userId, $clientId)) {
            header('Location: /unlockyourskills/my-courses');
            exit;
        }

        // Create or get existing attempt
        $attemptId = $this->model->createOrGetAttempt($decryptedId, $userId, $clientId);
        
        // Get current attempt data
        $attempt = $this->model->getAttempt($attemptId);
        
        // Note: Completion tracking is now handled only when content is actually completed
        $courseId = $this->getCourseIdForAssessment($decryptedId);
        
        // Expose data to view
        $GLOBALS['assessment'] = $assessment;
        $GLOBALS['attempt'] = $attempt;
        $GLOBALS['attemptId'] = $attemptId;

        require 'views/assessment_player.php';
    }

    // AJAX: Save answer
    public function saveAnswer()
    {
        if (!isset($_SESSION['user']['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $attemptId = $input['attempt_id'] ?? null;
        $questionId = $input['question_id'] ?? null;
        $answer = $input['answer'] ?? null;
        $currentQuestion = $input['current_question'] ?? null;

        if (!$attemptId || !$questionId) {
            echo json_encode(['success' => false, 'message' => 'Missing required data']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        
        // Verify attempt belongs to user
        if (!$this->model->verifyAttemptOwnership($attemptId, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $result = $this->model->saveAnswer($attemptId, $questionId, $answer, $currentQuestion);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
    }

    // AJAX: Submit assessment
    public function submitAssessment()
    {
        if (!isset($_SESSION['user']['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $attemptId = $input['attempt_id'] ?? null;

        if (!$attemptId) {
            echo json_encode(['success' => false, 'message' => 'Missing attempt ID']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        
        // Verify attempt belongs to user
        if (!$this->model->verifyAttemptOwnership($attemptId, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $result = $this->model->submitAssessment($attemptId, $clientId);
        
        // Mark prerequisite as complete only if passed OR attempts exhausted
        if ($result['success']) {
            $assessmentId = $this->getAssessmentIdFromAttempt($attemptId);
            
            // Resolve course_id robustly: attempt -> module mapping -> prerequisites mapping
            $courseId = $this->getCourseIdFromAttempt($attemptId);
            if (!$courseId && $assessmentId) {
                $courseId = $this->getCourseIdForAssessment($assessmentId);
            }
            if (!$courseId && $assessmentId) {
                $courseId = $this->getCourseIdFromPrerequisites($assessmentId);
            }
            
            if ($assessmentId && $courseId) {
                try {
                    require_once 'config/Database.php';
                    $database = new Database();
                    $conn = $database->connect();
                    
                    // Get max attempts from package
                    $stmt = $conn->prepare("SELECT num_attempts FROM assessment_package WHERE id = ?");
                    $stmt->execute([$assessmentId]);
                    $maxAttempts = (int)($stmt->fetchColumn() ?? 0);
                    
                    // Count attempts used by this user for this assessment (completed attempts)
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM assessment_attempts WHERE assessment_id = ? AND user_id = ? AND status = 'completed' AND is_deleted = 0");
                    $stmt->execute([$assessmentId, $userId]);
                    $attemptsUsed = (int)$stmt->fetchColumn();
                    
                    $passed = !empty($result['passed']);
                    $attemptsExhausted = ($maxAttempts > 0) ? ($attemptsUsed >= $maxAttempts) : false;
                    
                    if ($passed || $attemptsExhausted) {
                        $this->markPrerequisiteCompleteIfApplicable($userId, $courseId, $assessmentId, $clientId);
                    }
                } catch (Exception $e) {
                    error_log("Error evaluating assessment prerequisite completion: " . $e->getMessage());
                }
            }
        }
        
        header('Content-Type: application/json');
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'score' => $result['score'],
                'max_score' => $result['max_score'],
                'percentage' => $result['percentage'],
                'passed' => $result['passed'],
                'redirect_url' => '/unlockyourskills/my-courses'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
    }

    // AJAX: Get attempt progress
    public function getProgress()
    {
        if (!isset($_SESSION['user']['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $attemptId = $_GET['attempt_id'] ?? null;
        if (!$attemptId) {
            echo json_encode(['success' => false, 'message' => 'Missing attempt ID']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        
        // Verify attempt belongs to user
        if (!$this->model->verifyAttemptOwnership($attemptId, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $progress = $this->model->getAttemptProgress($attemptId);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'progress' => $progress]);
    }

    // AJAX: Update time remaining
    public function updateTime()
    {
        if (!isset($_SESSION['user']['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $attemptId = $input['attempt_id'] ?? null;
        $timeRemaining = $input['time_remaining'] ?? null;

        if (!$attemptId || $timeRemaining === null) {
            echo json_encode(['success' => false, 'message' => 'Missing required data']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        
        // Verify attempt belongs to user
        if (!$this->model->verifyAttemptOwnership($attemptId, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $result = $this->model->updateTimeRemaining($attemptId, $timeRemaining);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
    }

    // Health check endpoint for connection monitoring
    public function healthCheck()
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'healthy'
        ]);
    }

    /**
     * Check if assessment is a prerequisite and start tracking
     */
    private function startPrerequisiteTrackingIfApplicable($userId, $courseId, $assessmentId, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Check if this assessment is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $assessmentId, 'assessment');
            
            if ($isPrerequisite) {
                $completionService->startPrerequisiteTracking($userId, $courseId, $assessmentId, 'assessment', $clientId);
            }
        } catch (Exception $e) {
            error_log("Error in startPrerequisiteTrackingIfApplicable: " . $e->getMessage());
        }
    }

    /**
     * Start module tracking if assessment belongs to a module
     */
    private function startModuleTrackingIfApplicable($userId, $courseId, $assessmentId, $contentType, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Start module tracking if this content belongs to a module
            $completionService->startModuleTrackingIfApplicable($userId, $courseId, $assessmentId, $contentType, $clientId);
        } catch (Exception $e) {
            error_log("Error in startModuleTrackingIfApplicable: " . $e->getMessage());
        }
    }

    /**
     * Check if assessment is a prerequisite and mark as complete
     */
    private function markPrerequisiteCompleteIfApplicable($userId, $courseId, $assessmentId, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Check if this assessment is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $assessmentId, 'assessment');
            
            if ($isPrerequisite) {
                $completionService->markPrerequisiteComplete($userId, $courseId, $assessmentId, 'assessment', $clientId);
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
     * Get course ID for an assessment
     */
    private function getCourseIdForAssessment($assessmentId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT course_id FROM course_module_content 
                    WHERE content_id = ? AND content_type = 'assessment' AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$assessmentId]);
            
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error getting course ID for assessment: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get assessment ID from attempt ID
     */
    private function getAssessmentIdFromAttempt($attemptId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT assessment_id FROM assessment_attempts WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$attemptId]);
            
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error getting assessment ID from attempt: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get course ID from attempt ID
     */
    private function getCourseIdFromAttempt($attemptId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT course_id FROM assessment_attempts WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$attemptId]);
            
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error getting course ID from attempt: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fallback: Get course ID from course_prerequisites mapping
     */
    private function getCourseIdFromPrerequisites($assessmentId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT course_id FROM course_prerequisites 
                    WHERE prerequisite_id = ? AND prerequisite_type = 'assessment' 
                    AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00') 
                    ORDER BY id DESC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$assessmentId]);
            
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error getting course ID from prerequisites: " . $e->getMessage());
            return null;
        }
    }
} 