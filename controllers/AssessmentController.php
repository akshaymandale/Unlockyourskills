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

    // Fetch filtered questions for the modal grid (called via AJAX)
    public function getQuestions() {
        $search = $_GET['search'] ?? '';
        $marks = $_GET['marks'] ?? '';
        $type = $_GET['type'] ?? '';
        $limit = $_GET['limit'] ?? 10;

        $questions = $this->model->getFilteredQuestions($search, $marks, $type, $limit);
        //print_r($questions);die;
        echo json_encode(['questions' => $questions]);
    }

    // Return full data for selected question IDs (AJAX)
    public function getSelectedQuestions() {
        $input = json_decode(file_get_contents("php://input"), true);
        $ids = $input['ids'] ?? [];

        $questions = $this->model->getQuestionsByIds($ids);
        echo json_encode(['questions' => $questions]);
    }
}
