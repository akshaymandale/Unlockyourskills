<?php
require_once 'models/FeedbackQuestionModel.php';
require_once 'controllers/BaseController.php';

class FeedbackQuestionController extends BaseController
{
    private $feedbackQuestionModel;

    public function __construct()
    {
        $this->feedbackQuestionModel = new FeedbackQuestionModel();
    }

    // List with pagination & filters
    public function index()
    {
        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];

        // ✅ Don't load initial data - let JavaScript handle it via AJAX
        // This prevents duplicate data rendering issues
        $questions = []; // Empty array for initial page load
        $totalQuestions = 0;
        $totalPages = 0;
        $page = 1;

        // Get unique values for filter dropdowns (client-specific)
        $uniqueQuestionTypes = $this->feedbackQuestionModel->getDistinctTypes($clientId);

        require 'views/add_feedback_question.php';
    }

    // Save new or update existing feedback question
    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request method.', '/unlockyourskills/feedback');
            return;
        }

        if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];

        $id = $_POST['feedbackQuestionId'] ?? null;  // for update

        $title = trim($_POST['feedbackQuestionTitle'] ?? '');
        $type = $_POST['feedbackQuestionType'] ?? '';
        $ratingScale = $_POST['feedbackRatingScale'] ?? null;
        $ratingSymbol = $_POST['feedbackRatingSymbol'] ?? null;
        $tags = trim($_POST['feedbackTagList'] ?? '');
        $createdBy = $_SESSION['id'];

        // Validate
        $errors = [];
        if ($title === '') $errors[] = 'Title is required.';
        if ($type === '') $errors[] = 'Type is required.';
        if (!in_array($type, ['multi_choice', 'checkbox', 'short_answer', 'long_answer', 'dropdown', 'upload', 'rating'])) {
            $errors[] = 'Invalid question type.';
        }
        if ($tags === '') $errors[] = 'At least one tag is required.';

        // Handle main question media upload if any
        $mediaFileName = null;
        if (!empty($_FILES['feedbackQuestionMedia']['name'])) {
            $mediaFileName = $this->handleUpload($_FILES['feedbackQuestionMedia'], 'feedback');
            if (!$mediaFileName) {
                $errors[] = 'Invalid media file type.';
            }
        }

        if ($id) {
            // For update: optionally keep old media if no new upload
            $currentUser = $_SESSION['user'] ?? null;
            $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;

            $existingQuestion = $this->feedbackQuestionModel->getQuestionById($id, $filterClientId);

            if (!$existingQuestion) {
                $this->toastError('Feedback question not found or access denied.', '/unlockyourskills/feedback');
                return;
            }
            if (!$mediaFileName) {
                $mediaFileName = $existingQuestion['media_path'];
            } else {
                // Optionally delete old media file here
            }
        }

        if (!empty($errors)) {
            $errorMessage = implode(', ', $errors);
            $this->toastError($errorMessage, '/unlockyourskills/feedback');
            return;
        }

        $mediaNameOnly = $mediaFileName ? basename($mediaFileName) : null;

        $data = [
            'client_id' => $clientId,
            'title' => $title,
            'type' => $type,
            'media_path' => $mediaNameOnly,
            'rating_scale' => $ratingScale,
            'rating_symbol' => $ratingSymbol,
            'tags' => $tags,
            'created_by' => $createdBy
        ];

        if ($id) {
            $currentUser = $_SESSION['user'] ?? null;
            $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;

            $result = $this->feedbackQuestionModel->updateQuestion($id, $data, $filterClientId);
            if (!$result) {
                $this->toastError('Failed to update feedback question or access denied.', '/unlockyourskills/feedback');
                return;
            }
            $questionId = $id;
        } else {
            $questionId = $this->feedbackQuestionModel->saveQuestion($data);
            if (!$questionId) {
                $this->toastError('Failed to save feedback question.', '/unlockyourskills/feedback');
                return;
            }
        }

        // Save/Update options for types that have them
        if (in_array($type, ['multi_choice', 'checkbox', 'dropdown'])) {
            if ($id) {
                // For updates, use updateOptions method
                $optionsData = [];
                $optionIds = $_POST['optionId'] ?? [];
                $optionTexts = $_POST['optionText'] ?? [];
                $optionMediaFiles = $_FILES['optionMedia'] ?? null;
                $existingOptionMedia = $_POST['existingOptionMedia'] ?? [];

                $countOptions = max(count($optionTexts), count($optionIds));

                for ($i = 0; $i < $countOptions; $i++) {
                    $optionsData[$i] = [
                        'id' => isset($optionIds[$i]) && is_numeric($optionIds[$i]) ? (int)$optionIds[$i] : null,
                        'text' => trim($optionTexts[$i] ?? ''),
                        'media_path' => null,
                        'media_file' => null
                    ];

                    // Check if new file is uploaded for this option
                    if ($optionMediaFiles && isset($optionMediaFiles['name'][$i]) && $optionMediaFiles['name'][$i] !== '') {
                        $optionsData[$i]['media_file'] = [
                            'name' => $optionMediaFiles['name'][$i],
                            'type' => $optionMediaFiles['type'][$i],
                            'tmp_name' => $optionMediaFiles['tmp_name'][$i],
                            'error' => $optionMediaFiles['error'][$i],
                            'size' => $optionMediaFiles['size'][$i],
                        ];
                    } else if (isset($existingOptionMedia[$i]) && !empty($existingOptionMedia[$i])) {
                        // No new file uploaded, preserve existing media
                        $optionsData[$i]['media_path'] = $existingOptionMedia[$i];
                    }
                }

                $this->feedbackQuestionModel->updateOptions($questionId, $optionsData, $createdBy);

                // Handle deleted options if any
                $deletedOptionIds = $_POST['deletedOptionIds'] ?? [];
                if (is_array($deletedOptionIds) && count($deletedOptionIds) > 0) {
                    $this->feedbackQuestionModel->deleteOptions($deletedOptionIds);
                }
            } else {
                // For new questions, use saveOptions method
                $options = $_POST['optionText'] ?? [];
                $optionMedias = $_FILES['optionMedia'] ?? null;
                $this->feedbackQuestionModel->saveOptions($questionId, $options, $optionMedias, $createdBy, $clientId);
            }
        }

        if ($id) {
            $this->toastSuccess('Feedback question updated successfully!', '/unlockyourskills/feedback');
        } else {
            $this->toastSuccess('Feedback question saved successfully!', '/unlockyourskills/feedback');
        }
    }

    // File upload helper
    private function handleUpload($file, $folder = 'feedback')
    {
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
            'video/mp4', 'application/pdf'
        ];
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $randomName = bin2hex(random_bytes(10)) . '.' . $ext;
        $uploadDir = "uploads/$folder/";

        // ✅ Create directory if it doesn't exist with proper permissions
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0777); // Ensure proper permissions
        }

        $targetPath = $uploadDir . $randomName;

        return move_uploaded_file($file['tmp_name'], $targetPath) ? $targetPath : null;
    }

    // Delete question by id
    public function delete()
    {
        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;

        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            $this->toastError('Invalid request parameters.', '/unlockyourskills/feedback');
            return;
        }

        $id = (int)$_GET['id'];

        // Determine client filtering based on user role
        $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;

        $success = $this->feedbackQuestionModel->deleteQuestion($id, $filterClientId);

        if ($success) {
            $this->toastSuccess('Feedback question deleted successfully!', '/unlockyourskills/feedback');
        } else {
            $this->toastError('Failed to delete feedback question or access denied.', '/unlockyourskills/feedback');
        }
    }

    // AJAX: get filtered paginated questions as JSON
    public function getQuestions()
    {
        $search = $_GET['search'] ?? '';
        $type = $_GET['type'] ?? '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        $questions = $this->feedbackQuestionModel->getQuestions($search, $type, $limit, $offset);
        $total = $this->feedbackQuestionModel->getTotalQuestionCount($search, $type);
        $totalPages = ceil($total / $limit);

        header('Content-Type: application/json');
        echo json_encode([
            'questions' => $questions,
            'totalPages' => $totalPages,
        ]);
    }

    // AJAX: get distinct question types for filter dropdown
    public function getFilterOptions()
    {
        $types = $this->feedbackQuestionModel->getDistinctTypes();

        header('Content-Type: application/json');
        echo json_encode(['types' => $types]);
    }

    public function ajaxSearch() {
        header('Content-Type: application/json');

        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized access. Please log in.'
            ]);
            exit();
        }

        $clientId = $_SESSION['user']['client_id'];

        try {
            $limit = 10;
            $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
            $offset = ($page - 1) * $limit;

            // Get search and filter parameters
            $search = trim($_POST['search'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $tags = trim($_POST['tags'] ?? '');

            // Get questions from database (client-specific)
            $questions = $this->feedbackQuestionModel->getQuestions($search, $type, $limit, $offset, $tags, $clientId);
            $totalQuestions = $this->feedbackQuestionModel->getTotalQuestionCount($search, $type, $tags, $clientId);
            $totalPages = ceil($totalQuestions / $limit);

            $response = [
                'success' => true,
                'questions' => $questions,
                'totalQuestions' => $totalQuestions,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalQuestions' => $totalQuestions
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error loading questions: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    // AJAX: get questions by array of IDs
    public function getSelectedQuestions()
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        $ids = $data['ids'] ?? [];

        $questions = $this->feedbackQuestionModel->getSelectedQuestions($ids);

        header('Content-Type: application/json');
        echo json_encode(['questions' => $questions]);
    }

    // AJAX: get single question details for editing
    public function getQuestionById()
    {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid question ID']);
            return;
        }

        $id = (int)$_GET['id'];
        $question = $this->feedbackQuestionModel->getQuestionById($id);

        if (!$question) {
            http_response_code(404);
            echo json_encode(['error' => 'Question not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'question' => $question,
            'options' => $question['options'] ?? []
        ]);
    }

    // Debug endpoint to check raw data
    public function debugQuestion()
    {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            echo "Invalid ID";
            return;
        }

        $id = (int)$_GET['id'];
        $question = $this->feedbackQuestionModel->getQuestionById($id);

        echo "<h3>Question Data:</h3>";
        echo "<pre>" . print_r($question, true) . "</pre>";

        echo "<h3>Options Data:</h3>";
        $options = $this->feedbackQuestionModel->getOptionsByQuestionId($id);
        echo "<pre>" . print_r($options, true) . "</pre>";
    }
}
