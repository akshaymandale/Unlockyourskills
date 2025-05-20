<?php
require_once 'models/FeedbackQuestionModel.php';

class FeedbackQuestionController
{
    private $feedbackQuestionModel;

    public function __construct()
    {
        $this->feedbackQuestionModel = new FeedbackQuestionModel();
    }

    public function index()
    {
        $limit = 10;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        $questions = $this->feedbackQuestionModel->getQuestions($limit, $offset);
        $totalQuestions = $this->feedbackQuestionModel->getTotalQuestionCount();
        $totalPages = ceil($totalQuestions / $limit);

        require 'views/add_feedback_question.php';
    }

    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_SESSION['id'])) {
                echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
                exit();
            }

            $title = trim($_POST['feedbackQuestionTitle'] ?? '');
            $type = $_POST['feedbackQuestionType'] ?? '';
            $ratingScale = $_POST['feedbackRatingScale'] ?? null;
            $ratingSymbol = $_POST['feedbackRatingSymbol'] ?? null;
            $tags = trim($_POST['feedbackTagList'] ?? '');
            $createdBy = $_SESSION['id'];

            $errors = [];
            if ($title === '') $errors[] = 'Title is required.';
            if ($type === '') $errors[] = 'Type is required.';
            if (!in_array($type, ['multi_choice', 'checkbox', 'short_answer', 'long_answer', 'dropdown', 'upload', 'rating'])) {
                $errors[] = 'Invalid question type.';
            }
            if ($tags === '') $errors[] = 'At least one tag is required.';

            $mediaFileName = null;
            if (!empty($_FILES['feedbackQuestionMedia']['name'])) {
                $mediaFileName = $this->handleUpload($_FILES['feedbackQuestionMedia'], 'feedback');
                if (!$mediaFileName) $errors[] = 'Invalid media file type.';
            }

            if (!empty($errors)) {
                $message = implode('\n', $errors);
                echo "<script>alert('$message'); window.location.href='index.php?controller=VLRController';</script>";
                return;
            }

            $mediaNameOnly = $mediaFileName ? basename($mediaFileName) : null;

            $questionId = $this->feedbackQuestionModel->saveQuestion([
                'title' => $title,
                'type' => $type,
                'media_path' => $mediaNameOnly,
                'rating_scale' => $ratingScale,
                'rating_symbol' => $ratingSymbol,
                'tags' => $tags,
                'created_by' => $createdBy
            ]);

            if (in_array($type, ['multi_choice', 'checkbox', 'dropdown'])) {
                $options = $_POST['optionText'] ?? [];
                $optionMedias = $_FILES['optionMedia'] ?? null;

                $this->feedbackQuestionModel->saveOptions($questionId, $options, $optionMedias, $createdBy);
            }

            echo "<script>alert('Feedback question saved successfully.'); window.location.href='index.php?controller=FeedbackQuestionController';</script>";
        } else {
            echo "<script>alert('Invalid request parameters.'); window.location.href='index.php?controller=FeedbackQuestionController';</script>";
        }
    }

    private function handleUpload($file, $folder = 'feedback')
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
            $success = $this->feedbackQuestionModel->deleteQuestion($id);

            $message = $success ? 'Feedback question deleted successfully.' : 'Failed to delete feedback question.';
            echo "<script>alert('$message'); window.location.href='index.php?controller=FeedbackQuestionController';</script>";
        } else {
            echo "<script>alert('Invalid request parameters.'); window.location.href='index.php?controller=FeedbackQuestionController';</script>";
        }
    }

    public function getQuestions()
    {
        $search = $_GET['search'] ?? '';
        $type = $_GET['type'] ?? '';
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        $questions = $this->feedbackQuestionModel->getQuestions($search, $type, $limit, $offset);
        $total = $this->feedbackQuestionModel->getTotalQuestionCount($search, $type);
        $totalPages = ceil($total / $limit);

        header('Content-Type: application/json');
        echo json_encode([
            'questions' => $questions,
            'totalPages' => $totalPages
        ]);
    }

    public function getFilterOptions()
    {
        $types = $this->feedbackQuestionModel->getDistinctTypes();

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

        $questions = $this->feedbackQuestionModel->getSelectedQuestions($ids);

        header('Content-Type: application/json');
        echo json_encode([
            'questions' => $questions
        ]);
    }
}
