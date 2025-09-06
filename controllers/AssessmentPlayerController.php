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
} 