<?php
require_once 'models/SurveyQuestionModel.php';

class SurveyQuestionController
{
    private $surveyQuestionModel;

    public function __construct()
    {
        $this->surveyQuestionModel = new SurveyQuestionModel();
    }

    public function index()
    {
        $limit = 10;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        // Fetch paginated questions
        $questions = $this->surveyQuestionModel->getQuestions('','',$limit, $offset);

        // Get total count of questions
        $totalQuestions = $this->surveyQuestionModel->getTotalQuestionCount();

        // Calculate total pages
        $totalPages = ceil($totalQuestions / $limit);

        require 'views/add_survey.php';  // Your view file that uses pagination
    }

    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!isset($_SESSION['id'])) {
                echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
                exit();
            }

            $title = trim($_POST['surveyQuestionTitle'] ?? '');
            $type = $_POST['surveyQuestionType'] ?? '';
            $ratingScale = $_POST['ratingScale'] ?? null;
            $ratingSymbol = $_POST['ratingSymbol'] ?? null;
            $tags = trim($_POST['tagList'] ?? '');  // Tags as comma-separated string
            $createdBy = $_SESSION['id'];

            $errors = [];
            if ($title === '') $errors[] = 'Title is required.';
            if ($type === '') $errors[] = 'Type is required.';
            if (!in_array($type, ['multi_choice', 'checkbox', 'short_answer', 'long_answer', 'dropdown', 'upload', 'rating'])) {
                $errors[] = 'Invalid question type.';
            }
            if ($tags === '') $errors[] = 'At least one tag is required.';

            $mediaFileName = null;
            if (!empty($_FILES['surveyQuestionMedia']['name'])) {
                $mediaFileName = $this->handleUpload($_FILES['surveyQuestionMedia'], 'survey');
                if (!$mediaFileName) $errors[] = 'Invalid media file type.';
            }

            if (!empty($errors)) {
                $message = implode('\n', $errors);
                echo "<script>alert('$message'); window.location.href='index.php?controller=VLRController';</script>";
                return;
            }

            // Only save filename, not full path
            $mediaNameOnly = $mediaFileName ? basename($mediaFileName) : null;

            // Save question with tags
            $questionId = $this->surveyQuestionModel->saveQuestion([
                'title' => $title,
                'type' => $type,
                'media_path' => $mediaNameOnly,
                'rating_scale' => $ratingScale,
                'rating_symbol' => $ratingSymbol,
                'tags' => $tags,
                'created_by' => $createdBy
            ]);

            // Save options for applicable question types
            if (in_array($type, ['multi_choice', 'checkbox', 'dropdown'])) {
                $options = $_POST['optionText'] ?? [];
                $optionMedias = $_FILES['optionMedia'] ?? null;

                $this->surveyQuestionModel->saveOptions($questionId, $options, $optionMedias, $createdBy);
            }

            $message = 'Survey question saved successfully.';
            echo "<script>alert('$message'); window.location.href='index.php?controller=SurveyQuestionController';</script>";
        } else {
            echo "<script>alert('Invalid request parameters.'); window.location.href='index.php?controller=SurveyQuestionController';</script>";
        }
    }

    private function handleUpload($file, $folder = 'survey')
    {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'video/mp4', 'application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $randomName = bin2hex(random_bytes(10)) . '.' . $ext;
        $uploadDir = "uploads/$folder/";

        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $targetPath = $uploadDir . $randomName;

        return move_uploaded_file($file['tmp_name'], $targetPath) ? $targetPath : null;
    }

    public function delete()
    {
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $id = (int)$_GET['id'];
            $success = $this->surveyQuestionModel->deleteQuestion($id); // Soft delete

            if ($success) {
                $message = 'Survey question deleted successfully.';
            } else {
                $message = 'Failed to delete survey question.';
            }

            echo "<script>alert('$message'); window.location.href='index.php?controller=SurveyQuestionController';</script>";
        } else {
            echo "<script>alert('Invalid request parameters.'); window.location.href='index.php?controller=SurveyQuestionController';</script>";
        }
    }

    public function getQuestions()
{
    $search = $_GET['search'] ?? '';
    $type = $_GET['type'] ?? '';
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    $questions = $this->surveyQuestionModel->getQuestions($search, $type, $limit, $offset);
    $total = $this->surveyQuestionModel->getTotalQuestionCount($search, $type);
    $totalPages = ceil($total / $limit);

    header('Content-Type: application/json');
    echo json_encode([
        'questions' => $questions,
        'totalPages' => $totalPages
    ]);
}

public function getFilterOptions()
{
    $types = $this->surveyQuestionModel->getDistinctTypes();

    header('Content-Type: application/json');
    echo json_encode([
        'types' => $types
    ]);
}

public function getSelectedQuestions()
{
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);
    $ids = $data['ids'] ?? [];

    $questions = $this->surveyQuestionModel->getSelectedQuestions($ids);

    header('Content-Type: application/json');
    echo json_encode([
        'questions' => $questions
    ]);
}
}
