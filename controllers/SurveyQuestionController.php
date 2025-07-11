<?php
require_once 'models/SurveyQuestionModel.php';
require_once 'controllers/BaseController.php';

class SurveyQuestionController extends BaseController
{
    private $surveyQuestionModel;

    public function __construct()
    {
        $this->surveyQuestionModel = new SurveyQuestionModel();
    }

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
        $uniqueQuestionTypes = $this->surveyQuestionModel->getDistinctTypes($clientId);

        require 'views/add_survey.php';
    }
    
    public function save()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
                    return;
                }
                $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
                return;
            }

            $clientId = $_SESSION['user']['client_id'];

            $questionId = isset($_POST['surveyQuestionId']) && is_numeric($_POST['surveyQuestionId']) ? (int)$_POST['surveyQuestionId'] : null;

            $title = trim($_POST['surveyQuestionTitle'] ?? '');
            $type = $_POST['surveyQuestionType'] ?? '';
            $ratingScale = $_POST['ratingScale'] ?? null;
            $ratingSymbol = $_POST['ratingSymbol'] ?? null;
            $tags = trim($_POST['tagList'] ?? '');
            $createdBy = $_SESSION['id'];
            $existingOptionMedias = $_POST['existingOptionMedia'] ?? []; // array of media names

            if ($type != 'rating') {
                $ratingScale = null;
                $ratingSymbol = null;
            }

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
                if (!$mediaFileName) {
                    $errors[] = 'Invalid media file type.';
                }
            }

            if (!empty($errors)) {
                $message = implode(', ', $errors);
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => false, 'message' => $message]);
                    return;
                }
                $this->toastError($message, '/unlockyourskills/surveys');
                return;
            }

            $existingSurveyQuestionMedia = $_POST['existingSurveyQuestionMedia'] ?? null;

            // Final step: determine actual media to save (handle removal on update)
            $mediaNameOnly = null;
            if ($mediaFileName) {
                $mediaNameOnly = basename($mediaFileName); // newly uploaded media
            } elseif (!empty($existingSurveyQuestionMedia)) {
                $mediaNameOnly = $existingSurveyQuestionMedia; // retain old media if not removed
            } else {
                $mediaNameOnly = null; // media was removed by user
            }

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

            if ($questionId) {
                // UPDATE (with client validation)
                $currentUser = $_SESSION['user'] ?? null;
                $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;

                $result = $this->surveyQuestionModel->updateQuestion($questionId, $data, $filterClientId);

                if ($result) {
                    if (in_array($type, ['multi_choice', 'checkbox', 'dropdown'])) {
                        $options = $_POST['optionText'] ?? [];
                        $optionMedias = $_FILES['optionMedia'] ?? null;
                        $this->surveyQuestionModel->updateOptions($questionId, $options, $optionMedias, $createdBy, $existingOptionMedias, $filterClientId);
                    }
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(['success' => true, 'message' => 'Survey question updated successfully!']);
                        return;
                    }
                    $this->toastSuccess('Survey question updated successfully!', '/unlockyourskills/surveys');
                } else {
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(['success' => false, 'message' => 'Failed to update survey question or access denied.']);
                        return;
                    }
                    $this->toastError('Failed to update survey question or access denied.', '/unlockyourskills/surveys');
                }
            } else {
                // INSERT
                $questionId = $this->surveyQuestionModel->saveQuestion($data);

                if ($questionId && in_array($type, ['multi_choice', 'checkbox', 'dropdown'])) {
                    $options = $_POST['optionText'] ?? [];
                    $optionMedias = $_FILES['optionMedia'] ?? null;
                    $this->surveyQuestionModel->saveOptions($questionId, $options, $optionMedias, $createdBy, $clientId);
                }

                if ($questionId) {
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(['success' => true, 'message' => 'Survey question saved successfully!']);
                        return;
                    }
                    $this->toastSuccess('Survey question saved successfully!', '/unlockyourskills/surveys');
                } else {
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(['success' => false, 'message' => 'Failed to save survey question.']);
                        return;
                    }
                    $this->toastError('Failed to save survey question.', '/unlockyourskills/surveys');
                }
            }
        } else {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request parameters.']);
                return;
            }
            $this->toastError('Invalid request parameters.', '/unlockyourskills/surveys');
        }
    }

    /**
     * Check if the current request is an AJAX request
     */
    protected function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Send JSON response
     */
    private function jsonResponse($data)
    {
        header('Content-Type: application/json');
        $jsonData = json_encode($data);
        error_log("JSON Response being sent: " . $jsonData);
        echo $jsonData;
        exit();
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

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0777); // Ensure proper permissions
        }

        $targetPath = $uploadDir . $randomName;

        return move_uploaded_file($file['tmp_name'], $targetPath) ? $targetPath : null;
    }

    public function delete($id = null)
    {
        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;

        // Get ID from parameter (Router) or from GET request (backward compatibility)
        if ($id === null) {
            $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
        } else {
            $id = is_numeric($id) ? (int)$id : null;
        }

        if ($id) {
            // Determine client filtering based on user role
            $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;

            $success = $this->surveyQuestionModel->deleteQuestion($id, $filterClientId); // Soft delete

            if ($success) {
                $this->toastSuccess('Survey question deleted successfully!', '/unlockyourskills/surveys');
            } else {
                $this->toastError('Failed to delete survey question or access denied.', '/unlockyourskills/surveys');
            }
        } else {
            $this->toastError('Invalid request parameters.', '/unlockyourskills/surveys');
        }
    }

    public function getQuestions()
    {
        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized access']);
            exit();
        }

        $clientId = $_SESSION['user']['client_id'];
        $search = $_GET['search'] ?? '';
        $type = $_GET['type'] ?? '';
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $offset = ($page - 1) * $limit;

        $questions = $this->surveyQuestionModel->getQuestions($search, $type, $limit, $offset, '', $clientId);
        
        $total = $this->surveyQuestionModel->getTotalQuestionCount($search, $type, '', $clientId);
        $totalPages = ceil($total / $limit);

        header('Content-Type: application/json');
        echo json_encode([
            'questions' => $questions,
            'totalPages' => $totalPages
        ]);
    }

    public function getFilterOptions()
    {
        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized access']);
            exit();
        }

        $clientId = $_SESSION['user']['client_id'];
        $types = $this->surveyQuestionModel->getDistinctTypes($clientId);

        header('Content-Type: application/json');
        echo json_encode([
            'types' => $types
        ]);
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
            $questions = $this->surveyQuestionModel->getQuestions($search, $type, $limit, $offset, $tags, $clientId);

            // Add options for each question
            foreach ($questions as &$question) {
                $question['options'] = $this->surveyQuestionModel->getOptionsByQuestionId($question['id'], $clientId);
            }

            $totalQuestions = $this->surveyQuestionModel->getTotalQuestionCount($search, $type, $tags, $clientId);
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

    public function getSelectedQuestions()
    {
        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized access']);
            exit();
        }

        $clientId = $_SESSION['user']['client_id'];
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        $ids = $data['ids'] ?? [];

        $questions = $this->surveyQuestionModel->getSelectedQuestions($ids, $clientId);

        header('Content-Type: application/json');
        echo json_encode([
            'questions' => $questions
        ]);
    }

    public function edit()
    {
        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;

        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $id = (int)$_GET['id'];

            // Determine client filtering based on user role
            $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;

            $question = $this->surveyQuestionModel->getQuestionById($id, $filterClientId);

            if ($question) {
                // Get unique values for filter dropdowns (client-specific)
                $uniqueQuestionTypes = $this->surveyQuestionModel->getDistinctTypes($clientId);

                require 'views/add_survey.php';
            } else {
                $this->toastError('Survey question not found or access denied.', '/unlockyourskills/surveys');
            }
        } else {
            $this->toastError('Invalid request parameters.', '/unlockyourskills/surveys');
        }
    }
}