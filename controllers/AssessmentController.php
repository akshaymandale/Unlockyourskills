<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
    

    public function addOrEditAssessment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            return;
        }

        if (!isset($_SESSION['id'])) {
            echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
            exit();
        }
    
        // Server-side validation
        $title = trim($_POST['title'] ?? '');
        $tags = trim($_POST['tags'] ?? '');
        $numAttempts = (int) ($_POST['num_attempts'] ?? 0);
        $passingPercentage = (float) ($_POST['passing_percentage'] ?? 0);
        $timeLimit = (int) ($_POST['time_limit'] ?? 0);
        $negativeMarking = $_POST['assessment_negativeMarking'] ?? 'No';
        $negativeMarkingPercentage = $_POST['negative_marking_percentage'] ?? null;
        $assessmentType = $_POST['assessment_assessmentType'] ?? 'Fixed';
        $numQuestionsToDisplay = $_POST['num_questions_to_display'] ?? null;
        $selectedQuestions = $_POST['selected_question_ids'] ?? ''; // Comma-separated string
        $createdBy = $_SESSION['id'] ?? null;
    
        $errors = [];
    
        if (empty($title)) $errors[] = "Assessment title is required.";
        if (empty($tags)) $errors[] = "Tags/keywords are required.";
        if ($numAttempts <= 0) $errors[] = "Number of attempts must be greater than 0.";
        if ($passingPercentage < 0 || $passingPercentage > 100) $errors[] = "Passing percentage must be between 0 and 100.";
        //if ($timeLimit <= 0) $errors[] = "Time limit must be greater than 0.";
        if ($negativeMarking === 'Yes' && empty($negativeMarkingPercentage)) $errors[] = "Negative marking percentage required.";
        if ($assessmentType === 'Dynamic') {
            if (empty($numQuestionsToDisplay) || !is_numeric($numQuestionsToDisplay)) {
                $errors[] = "Number of questions to display is required for dynamic assessments.";
            }
        }
    
        if (empty($selectedQuestions)) {
            $errors[] = "At least one question must be selected.";
        }
    
        if (!empty($errors)) {
            // TODO: send errors to the view if needed
            echo json_encode(['status' => 'error', 'errors' => $errors]);
            return;
        }
    
        // Proceed to save
        $questionIds = explode(',', $selectedQuestions);
        $data = [
            'title' => $title,
            'tags' => $tags,
            'num_attempts' => $numAttempts,
            'passing_percentage' => $passingPercentage,
            'time_limit' => $timeLimit,
            'negative_marking' => $negativeMarking === 'Yes' ? 1 : 0,
            'negative_marking_percentage' => $negativeMarking === 'Yes' ? (int) $negativeMarkingPercentage : null,
            'assessment_type' => $assessmentType,
            'num_questions_to_display' => $assessmentType === 'Dynamic' ? (int) $numQuestionsToDisplay : null,
            'created_by' => $createdBy,
            'question_ids' => $questionIds
        ];
    
        $result = $this->model->saveAssessmentWithQuestions($data);

        if ($result) {
            $message = "Assessment saved successfully!";
            echo "<script>alert('$message'); window.location.href='index.php?controller=VLRController';</script>";
        } else {
            $message = "Failed to save assessment.";
            echo "<script>alert('$message'); window.location.href='index.php?controller=VLRController';</script>";
        }
    }
    
    
}
