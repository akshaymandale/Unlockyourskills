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
        require 'views/add_survey.php'; // ✅ Load the survey question form
    }

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
            if (!isset($_SESSION['id'])) {
                echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
                exit();
            }
    
            $title = trim($_POST['surveyQuestionTitle'] ?? '');
            $type = $_POST['surveyQuestionType'] ?? '';
            $ratingScale = $_POST['ratingScale'] ?? null;
            $ratingSymbol = $_POST['ratingSymbol'] ?? null;
            $createdBy = $_SESSION['id'];
    
            $errors = [];
            if ($title === '') $errors[] = 'Title is required.';
            if ($type === '') $errors[] = 'Type is required.';
            if (!in_array($type, ['multi_choice', 'checkbox', 'short_answer', 'long_answer', 'dropdown', 'upload', 'rating'])) {
                $errors[] = 'Invalid question type.';
            }
    
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
    
            // Only save file name, not full path
            $mediaNameOnly = $mediaFileName ? basename($mediaFileName) : null;
    
            // Save question
            $questionId = $this->surveyQuestionModel->saveQuestion([
                'title' => $title,
                'type' => $type,
                'media_path' => $mediaNameOnly,
                'rating_scale' => $ratingScale,
                'rating_symbol' => $ratingSymbol,
                'created_by' => $createdBy
            ]);
    
            // Save options for types that have options
            if (in_array($type, ['multi_choice', 'checkbox', 'dropdown'])) {
                $options = $_POST['optionText'] ?? [];
                $optionMedias = $_FILES['optionMedia'] ?? null;
    
                $this->surveyQuestionModel->saveOptions($questionId, $options, $optionMedias, $createdBy);
            }
    
            $message = 'Survey question saved successfully.';
            echo "<script>alert('$message'); window.location.href='index.php?controller=VLRController';</script>";
        } else {
            echo "<script>alert('Invalid request parameters.'); window.location.href='index.php?controller=VLRController';</script>";
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

}
