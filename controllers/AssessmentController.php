<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'models/AssessmentModel.php';

class AssessmentController
{ 
    private $model; 

    public function __construct()
    {
        $this->model = new AssessmentModel();
    }

    // Used to serve the assessment form view
    public function index()
    {
        require 'views/add_assessment.php';
    }

    public function getQuestions()
    {
        header('Content-Type: application/json');
        
        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            echo json_encode(['error' => 'Unauthorized access. Please log in.']);
            exit;
        }
        
        $clientId = $_SESSION['user']['client_id'];
        
        $search = $_GET['search'] ?? '';
        $marks = $_GET['marks'] ?? '';
        $type = $_GET['type'] ?? '';
        $limit = (int) ($_GET['limit'] ?? 10);
        $page = (int) ($_GET['page'] ?? 1);

        $offset = ($page - 1) * $limit;
        $questions = $this->model->getFilteredQuestions($search, $marks, $type, $limit, $offset, $clientId);
        $totalCount = $this->model->getFilteredQuestionCount($search, $marks, $type, $clientId);
        $totalPages = ceil($totalCount / $limit);

        echo json_encode(['questions' => $questions, 'totalPages' => $totalPages]);
    }

    public function getSelectedQuestions()
    {
        header('Content-Type: application/json');

        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            echo json_encode(['error' => 'Unauthorized access. Please log in.']);
            exit;
        }
        
        $clientId = $_SESSION['user']['client_id'];

        $rawInput = file_get_contents("php://input");
        $request = json_decode($rawInput, true);

        if (!isset($request['ids']) || !is_array($request['ids'])) {
            echo json_encode(['error' => 'Invalid input']);
            return;
        }

        $questions = $this->model->getQuestionsByIds($request['ids'], $clientId);

        echo json_encode(['questions' => $questions]);
    }

    public function getFilterOptions()
    {
        header('Content-Type: application/json');
        
        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            echo json_encode(['error' => 'Unauthorized access. Please log in.']);
            exit;
        }
        
        $clientId = $_SESSION['user']['client_id'];

        $marks = $this->model->getDistinctMarks($clientId);
        $types = $this->model->getDistinctTypes($clientId);

        echo json_encode([
            'marks' => $marks,
            'types' => $types
        ]);
    }


  


}
