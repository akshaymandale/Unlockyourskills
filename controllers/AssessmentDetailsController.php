<?php

require_once 'models/AssessmentAttemptIncreaseModel.php';

class AssessmentDetailsController {
    private $model;
    
    public function __construct() {
        $this->model = new AssessmentAttemptIncreaseModel();
    }
    
    public function index() {
        // Get courses with failed assessments
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $courses = $this->model->getCoursesWithFailedAssessments($clientId);
        
        require_once 'views/assessment_details.php';
    }
    
    public function getAssessmentContexts() {
        try {
            $courseId = $_GET['course_id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            
            if (!$courseId || !$clientId) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }
            
            $contexts = $this->model->getAssessmentContextsForCourse($courseId, $clientId);
            
            echo json_encode([
                'success' => true,
                'contexts' => $contexts
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching assessment contexts: ' . $e->getMessage()
            ]);
        }
    }
    
    public function getFailedUsers() {
        try {
            $courseId = $_GET['course_id'] ?? null;
            $assessmentId = $_GET['assessment_id'] ?? null;
            $contextType = $_GET['context_type'] ?? null;
            $contextId = $_GET['context_id'] ?? null;
            $searchTerm = $_GET['search'] ?? '';
            $clientId = $_SESSION['user']['client_id'] ?? null;
            
            if (!$courseId || !$assessmentId || !$contextType || !$contextId || !$clientId) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }
            
            $users = $this->model->searchFailedUsers($courseId, $assessmentId, $contextType, $contextId, $clientId, $searchTerm);
            
            echo json_encode([
                'success' => true,
                'users' => $users
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching failed users: ' . $e->getMessage()
            ]);
        }
    }
    
    public function increaseAttempts() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $courseId = $input['course_id'] ?? null;
            $assessmentId = $input['assessment_id'] ?? null;
            $contextType = $input['context_type'] ?? null;
            $contextId = $input['context_id'] ?? null;
            $userIds = $input['user_ids'] ?? [];
            $attemptsToAdd = $input['attempts_to_add'] ?? 1;
            $reason = $input['reason'] ?? '';
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $increasedBy = $_SESSION['user']['id'] ?? null;
            
            if (!$courseId || !$assessmentId || !$contextType || !$contextId || empty($userIds) || !$clientId || !$increasedBy) {
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }
            
            $result = $this->model->increaseAssessmentAttempts(
                $courseId, $assessmentId, $contextType, $contextId, 
                $userIds, $attemptsToAdd, $reason, $increasedBy, $clientId
            );
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error increasing attempts: ' . $e->getMessage()
            ]);
        }
    }
    
    public function getHistory() {
        try {
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $limit = $_GET['limit'] ?? 50;
            
            if (!$clientId) {
                echo json_encode(['success' => false, 'message' => 'Missing client ID']);
                return;
            }
            
            $history = $this->model->getAttemptIncreaseHistory($clientId, $limit);
            
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching history: ' . $e->getMessage()
            ]);
        }
    }
}
?>
