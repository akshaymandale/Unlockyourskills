<?php
require_once 'models/QuestionModel.php';

class QuestionController {
    private $questionModel;

    public function __construct() {
        $this->questionModel = new QuestionModel();
    }

    public function index() {
       // require 'views/add_assessment.php'; // ✅ Load the question form

        $limit = 10;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
    
        $questions = $this->questionModel->getQuestions($limit, $offset);
        $totalQuestions = $this->questionModel->getTotalQuestionCount();
        $totalPages = ceil($totalQuestions / $limit);
    
        require 'views/add_assessment.php';
    }

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!isset($_SESSION['id'])) {
                echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
                exit();
            }

            $errors = [];

            // Collect and sanitize input
            $questionText = trim($_POST['questionText'] ?? '');
            $tags = trim($_POST['tags'] ?? '');
            $skills = trim($_POST['skills'] ?? '');
            $level = $_POST['level'] ?? 'Low';
            $marks = intval($_POST['marks'] ?? 1);
            $status = $_POST['status'] ?? 'Active';
            $questionType = $_POST['questionFormType'] ?? 'Objective';
            $answerCount = intval($_POST['answerCount'] ?? 0);
            $mediaType = $_POST['questionMediaType'] ?? 'text';
            $createdBy = $_SESSION['id'] ?? 'Unknown';

            // Validations
            if (empty($questionText)) $errors[] = "Question text is required.";
            if (empty($tags)) $errors[] = "Tags are required.";
            if (!in_array($level, ['Low', 'Medium', 'Hard'])) $errors[] = "Invalid level.";
            if (!in_array($status, ['Active', 'Inactive'])) $errors[] = "Invalid status.";
            if (!in_array($questionType, ['Objective', 'Subjective'])) $errors[] = "Invalid question type.";
            if (!in_array($mediaType, ['text', 'image', 'audio', 'video'])) $errors[] = "Invalid media type.";

            $mediaFilePath = null;

            // Handle file upload
            if ($mediaType !== 'text' && isset($_FILES['mediaFile']) && $_FILES['mediaFile']['error'] === 0) {
                $targetDir = "uploads/media/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = basename($_FILES["mediaFile"]["name"]);
                $mediaFilePath = $targetDir . time() . "_" . $fileName;

                if (!move_uploaded_file($_FILES["mediaFile"]["tmp_name"], $mediaFilePath)) {
                    $errors[] = "Media file upload failed.";
                }
            }

            if ($questionType === 'Objective') {
                $options = $_POST['options'] ?? [];
                if (count($options) < 1) {
                    $errors[] = "At least one option is required.";
                }
            }

            if (empty($errors)) {
                $questionId = $this->questionModel->insertQuestion([
                    'question_text' => $questionText,
                    'tags' => $tags,
                    'competency_skills' => $skills,
                    'level' => $level,
                    'marks' => $marks,
                    'status' => $status,
                    'question_type' => $questionType,
                    'answer_count' => $answerCount,
                    'media_type' => $mediaType,
                    'media_file' => $mediaFilePath,
                    'created_by' => $createdBy
                ]);

                if ($questionType === 'Objective' && $questionId && isset($options)) {
                    foreach ($options as $index => $option) {
                        $optionText = trim($option['text']);
                        $isCorrect = isset($option['correct']) ? 1 : 0;

                        if (!empty($optionText)) {
                            $this->questionModel->insertOption([
                                'question_id' => $questionId,
                                'option_index' => $index,
                                'option_text' => $optionText,
                                'is_correct' => $isCorrect
                            ]);
                        }
                    }
                }

                $message = $questionId ? "Question added successfully." : "Failed to insert question.";
                echo "<script>alert('$message'); window.location.href='index.php?controller=QuestionController';</script>";
                exit();
            } else {
                $errorMsg = implode("\\n", $errors);
                echo "<script>alert('Error(s):\\n$errorMsg'); window.location.href='index.php?controller=QuestionController';</script>";
                exit();
            }
        }

        require 'views/add_assessment_question.php';
    }

    public function edit() {
        if (!isset($_SESSION['id'])) {
            echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
            exit();
        }
    
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $questionId = intval($_POST['question_id'] ?? 0);
            $errors = [];
    
            $questionText = trim($_POST['questionText'] ?? '');
            $tags = trim($_POST['tags'] ?? '');
            $skills = trim($_POST['skills'] ?? '');
            $level = $_POST['level'] ?? 'Low';
            $marks = intval($_POST['marks'] ?? 1);
            $status = $_POST['status'] ?? 'Active';
            $questionType = $_POST['questionFormType'] ?? 'Objective';
            $answerCount = intval($_POST['answerCount'] ?? 0);
            $mediaType = $_POST['questionMediaType'] ?? 'text';
    
            if (empty($questionText)) $errors[] = "Question text is required.";
            if (empty($tags)) $errors[] = "Tags are required.";
            if (!in_array($level, ['Low', 'Medium', 'Hard'])) $errors[] = "Invalid level.";
            if (!in_array($status, ['Active', 'Inactive'])) $errors[] = "Invalid status.";
            if (!in_array($questionType, ['Objective', 'Subjective'])) $errors[] = "Invalid question type.";
            if (!in_array($mediaType, ['text', 'image', 'audio', 'video'])) $errors[] = "Invalid media type.";
    
            $mediaFilePath = $_POST['existing_media_file'] ?? null;
    
            if ($mediaType !== 'text' && isset($_FILES['mediaFile']) && $_FILES['mediaFile']['error'] === 0) {
                $targetDir = "uploads/media/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = basename($_FILES["mediaFile"]["name"]);
                $mediaFilePath = $targetDir . time() . "_" . $fileName;
    
                if (!move_uploaded_file($_FILES["mediaFile"]["tmp_name"], $mediaFilePath)) {
                    $errors[] = "Media file upload failed.";
                }
            }
    
            if ($questionType === 'Objective') {
                $options = $_POST['options'] ?? [];
                if (count($options) < 1) {
                    $errors[] = "At least one option is required.";
                }
            }
    
            if (empty($errors)) {
                $updateResult = $this->questionModel->updateQuestion($questionId, [
                    'question_text' => $questionText,
                    'tags' => $tags,
                    'competency_skills' => $skills,
                    'level' => $level,
                    'marks' => $marks,
                    'status' => $status,
                    'question_type' => $questionType,
                    'answer_count' => $answerCount,
                    'media_type' => $mediaType,
                    'media_file' => $mediaFilePath,
                ]);
    
                if ($questionType === 'Objective') {
                    $this->questionModel->deleteOptionsByQuestionId($questionId);
                    foreach ($options as $index => $option) {
                        $optionText = trim($option['text']);
                        $isCorrect = isset($option['correct']) ? 1 : 0;
    
                        if (!empty($optionText)) {
                            $this->questionModel->updateOption([
                                'question_id' => $questionId,
                                'option_index' => $index,
                                'option_text' => $optionText,
                                'is_correct' => $isCorrect
                            ]);
                        }
                    }
                }
    
                $msg = $updateResult ? "Question updated successfully." : "Failed to update question.";
                echo "<script>alert('$msg'); window.location.href='index.php?controller=QuestionController';</script>";
                exit();
            } else {
                $errorMsg = implode("\\n", $errors);
                echo "<script>alert('Error(s):\\n$errorMsg'); window.location.href='index.php?controller=QuestionController';</script>";
                exit();
            }
        }
        // Handle initial page load (GET)
        else if (isset($_GET['id'])) {
            $questionId = intval($_GET['id']);
            $question = $this->questionModel->getQuestionById($questionId);
            $options = $this->questionModel->getOptionsByQuestionId($questionId);
    
            if ($question) {
                // Reuse add_assessment_question.php for editing
                $isEdit = true;
                require 'views/add_assessment_question.php';
            } else {
                echo "<script>alert('Question not found.'); window.location.href='index.php?controller=QuestionController';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Invalid request.'); window.location.href='index.php?controller=QuestionController';</script>";
            exit();
        }
    }
    
    
    
    public function delete() {
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $success = $this->questionModel->softDeleteQuestion($id);
            $message = $success ? "Question deleted successfully." : "Failed to delete question.";
            echo "<script>alert('$message'); window.location.href='index.php?controller=QuestionController';</script>";
        } else {
            echo "<script>alert('Invalid request.'); window.location.href='index.php?controller=QuestionController';</script>";
        }
    }


}
