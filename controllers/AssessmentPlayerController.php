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
        
        // If assessment is completed successfully, check if it's also part of modules or prerequisites
        if ($result['success'] && $result['passed']) {
            $this->handleSharedAssessmentCompletion($attemptId, $userId, $clientId);
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
     * Handle shared assessment completion - create entries for both modules and prerequisites
     */
    private function handleSharedAssessmentCompletion($attemptId, $userId, $clientId) {
        try {
            // Get assessment details from attempt
            $assessmentId = $this->getAssessmentIdFromAttempt($attemptId);
            $courseId = $this->getCourseIdFromAttempt($attemptId);
            
            if (!$assessmentId || !$courseId) {
                return;
            }
            
            // Get the latest assessment result for this attempt
            $assessmentResult = $this->getLatestAssessmentResult($assessmentId, $userId, $courseId);
            
            if (!$assessmentResult) {
                return;
            }
            
            // Check if this assessment also exists in modules
            $moduleContents = $this->getModuleContentsForAssessment($courseId, $assessmentId);
            
            if (!empty($moduleContents)) {
                // Create duplicate entries for each module content
                foreach ($moduleContents as $moduleContent) {
                    $this->createModuleAssessmentResult($assessmentResult, $moduleContent, $userId, $courseId, $clientId);
                }
                
                error_log("Created module assessment results for shared assessment $assessmentId in course $courseId for user $userId");
            }
            
            // Check if this assessment also exists as a prerequisite
            $prerequisiteIds = $this->getPrerequisiteIdsForAssessment($courseId, $assessmentId);
            
            if (!empty($prerequisiteIds)) {
                // Create duplicate entries for each prerequisite
                foreach ($prerequisiteIds as $prerequisiteId) {
                    $this->createPrerequisiteAssessmentResult($assessmentResult, $prerequisiteId, $userId, $courseId, $clientId);
                }
                
                error_log("Created prerequisite assessment results for shared assessment $assessmentId in course $courseId for user $userId");
            }
        } catch (Exception $e) {
            error_log("Error handling shared assessment completion: " . $e->getMessage());
        }
    }
    
    /**
     * Get module contents that contain this assessment
     */
    private function getModuleContentsForAssessment($courseId, $assessmentId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT cmc.id as content_id, cmc.module_id, cmc.content_id as assessment_id
                    FROM course_module_content cmc
                    JOIN course_modules cm ON cmc.module_id = cm.id
                    WHERE cm.course_id = ? AND cmc.content_id = ? AND cmc.content_type = 'assessment' 
                    AND cmc.deleted_at IS NULL AND cm.deleted_at IS NULL";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId, $assessmentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting module contents for assessment: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get the latest assessment result for this assessment
     */
    private function getLatestAssessmentResult($assessmentId, $userId, $courseId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT * FROM assessment_results 
                    WHERE assessment_id = ? AND user_id = ? AND course_id = ? 
                    ORDER BY completed_at DESC LIMIT 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$assessmentId, $userId, $courseId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting latest assessment result: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create assessment result entry for module content
     */
    private function createModuleAssessmentResult($assessmentResult, $moduleContent, $userId, $courseId, $clientId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            // Check if entry already exists for this module content
            $sql = "SELECT id FROM assessment_results 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND assessment_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $moduleContent['content_id'], $assessmentResult['assessment_id']]);
            
            if ($stmt->fetch()) {
                // Entry already exists, update it
                $sql = "UPDATE assessment_results SET 
                        attempt_number = ?, score = ?, max_score = ?, percentage = ?, 
                        passed = ?, time_taken = ?, started_at = ?, completed_at = ?, 
                        answers = ?, updated_at = NOW()
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND assessment_id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $assessmentResult['attempt_number'],
                    $assessmentResult['score'],
                    $assessmentResult['max_score'],
                    $assessmentResult['percentage'],
                    $assessmentResult['passed'],
                    $assessmentResult['time_taken'],
                    $assessmentResult['started_at'],
                    $assessmentResult['completed_at'],
                    $assessmentResult['answers'],
                    $userId,
                    $courseId,
                    $moduleContent['content_id'],
                    $assessmentResult['assessment_id']
                ]);
            } else {
                // Create new entry
                $sql = "INSERT INTO assessment_results (
                        course_id, user_id, client_id, assessment_id, attempt_number, 
                        score, max_score, percentage, passed, time_taken, started_at, 
                        completed_at, answers, content_id, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $courseId,
                    $userId,
                    $clientId,
                    $assessmentResult['assessment_id'],
                    $assessmentResult['attempt_number'],
                    $assessmentResult['score'],
                    $assessmentResult['max_score'],
                    $assessmentResult['percentage'],
                    $assessmentResult['passed'],
                    $assessmentResult['time_taken'],
                    $assessmentResult['started_at'],
                    $assessmentResult['completed_at'],
                    $assessmentResult['answers'],
                    $moduleContent['content_id']
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error creating module assessment result: " . $e->getMessage());
        }
    }
    
    /**
     * Get prerequisite IDs for this assessment
     */
    private function getPrerequisiteIdsForAssessment($courseId, $assessmentId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT id FROM course_prerequisites 
                    WHERE course_id = ? AND prerequisite_id = ? AND prerequisite_type = 'assessment' 
                    AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId, $assessmentId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            error_log("Error getting prerequisite IDs for assessment: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create assessment result entry for prerequisite
     */
    private function createPrerequisiteAssessmentResult($assessmentResult, $prerequisiteId, $userId, $courseId, $clientId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            // Check if entry already exists for this prerequisite
            $sql = "SELECT id FROM assessment_results 
                    WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND assessment_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $prerequisiteId, $assessmentResult['assessment_id']]);
            
            if ($stmt->fetch()) {
                // Entry already exists, update it
                $sql = "UPDATE assessment_results SET 
                        attempt_number = ?, score = ?, max_score = ?, percentage = ?, 
                        passed = ?, time_taken = ?, started_at = ?, completed_at = ?, 
                        answers = ?, updated_at = NOW()
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND assessment_id = ?";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $assessmentResult['attempt_number'],
                    $assessmentResult['score'],
                    $assessmentResult['max_score'],
                    $assessmentResult['percentage'],
                    $assessmentResult['passed'],
                    $assessmentResult['time_taken'],
                    $assessmentResult['started_at'],
                    $assessmentResult['completed_at'],
                    $assessmentResult['answers'],
                    $userId,
                    $courseId,
                    $prerequisiteId,
                    $assessmentResult['assessment_id']
                ]);
            } else {
                // Create new entry
                $sql = "INSERT INTO assessment_results (
                        course_id, user_id, client_id, assessment_id, attempt_number, 
                        score, max_score, percentage, passed, time_taken, started_at, 
                        completed_at, answers, prerequisite_id, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    $courseId,
                    $userId,
                    $clientId,
                    $assessmentResult['assessment_id'],
                    $assessmentResult['attempt_number'],
                    $assessmentResult['score'],
                    $assessmentResult['max_score'],
                    $assessmentResult['percentage'],
                    $assessmentResult['passed'],
                    $assessmentResult['time_taken'],
                    $assessmentResult['started_at'],
                    $assessmentResult['completed_at'],
                    $assessmentResult['answers'],
                    $prerequisiteId
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Error creating prerequisite assessment result: " . $e->getMessage());
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