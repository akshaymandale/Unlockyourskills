<?php
require_once 'models/AssessmentModel.php';

class AssessmentController {
    private $model;

    public function __construct() {
        $this->model = new AssessmentModel();
    }

    // Used to serve the assessment form view
    public function index() {
        require 'views/add_assessment.php';
    }

    public function getQuestions() {
        $search = $_GET['search'] ?? '';
        $marks = $_GET['marks'] ?? '';
        $type = $_GET['type'] ?? '';
        $limit = (int) ($_GET['limit'] ?? 10);
        $page = (int) ($_GET['page'] ?? 1);

        $offset = ($page - 1) * $limit;
        $questions = $this->model->getFilteredQuestions($search, $marks, $type, $limit, $offset);
        $totalCount = $this->model->getFilteredQuestionCount($search, $marks, $type);
        $totalPages = ceil($totalCount / $limit);

        echo json_encode(['questions' => $questions, 'totalPages' => $totalPages]);
    }

    public function getSelectedQuestions()
    {
        header('Content-Type: application/json');
    
        $rawInput = file_get_contents("php://input");
        $request = json_decode($rawInput, true);
    
        if (!isset($request['ids']) || !is_array($request['ids'])) {
            echo json_encode(['error' => 'Invalid input']);
            return;
        }
    
        $questions = $this->model->getQuestionsByIds($request['ids']);
    
        echo json_encode(['questions' => $questions]);
    }
    

    public function getFilterOptions() {
        $marks = $this->model->getDistinctMarks();
        $types = $this->model->getDistinctTypes();
    
        echo json_encode([
            'marks' => $marks,
            'types' => $types
        ]);
    }
    
    
}
