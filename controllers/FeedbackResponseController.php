<?php
require_once 'config/autoload.php';
require_once 'models/FeedbackResponseModel.php';
require_once 'core/IdEncryption.php';
require_once 'controllers/BaseController.php';
require_once 'core/UrlHelper.php';

class FeedbackResponseController extends BaseController {
    private $FeedbackResponseModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->FeedbackResponseModel = new FeedbackResponseModel();
    }

    /**
     * Display feedback form for a course
     */
    public function showFeedbackForm() {
        if (!isset($_SESSION['id'])) {
            $this->toastError('Please log in to access feedback.', '/unlockyourskills/login');
            return;
        }

        $courseId = $_GET['course_id'] ?? null;
        $feedbackPackageId = $_GET['feedback_id'] ?? null;

        if (!$courseId || !$feedbackPackageId) {
            $this->toastError('Invalid course or feedback information.', '/unlockyourskills/my-courses');
            return;
        }

        // Decrypt IDs if they're encrypted
        try {
            $courseId = IdEncryption::decrypt($courseId);
            $feedbackPackageId = IdEncryption::decrypt($feedbackPackageId);
        } catch (Exception $e) {
            $this->toastError('Invalid course or feedback information.', '/unlockyourskills/my-courses');
            return;
        }

        // Get feedback package details
        $feedbackPackage = $this->FeedbackResponseModel->getFeedbackPackageForCourse($courseId, $feedbackPackageId);
        
        if (!$feedbackPackage) {
            $this->toastError('Feedback not found or not available for this course.', '/unlockyourskills/my-courses');
            return;
        }

        // Check if user has already submitted feedback
        $hasSubmitted = $this->FeedbackResponseModel->hasUserSubmittedFeedback($courseId, $_SESSION['id'], $feedbackPackageId);

        // Get course details
        require_once 'models/CourseModel.php';
        $courseModel = new CourseModel();
        $course = $courseModel->getCourseById($courseId);

        if (!$course) {
            $this->toastError('Course not found.', '/unlockyourskills/my-courses');
            return;
        }

        // Include the view
        include 'views/feedback_form.php';
    }

    /**
     * Process feedback form submission
     */
    public function submitFeedback() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $courseId = $_POST['course_id'] ?? null;
        $feedbackPackageId = $_POST['feedback_package_id'] ?? null;
        $responses = $_POST['responses'] ?? [];

        // Debug logging
        error_log("=== FEEDBACK SUBMISSION DEBUG ===");
        error_log("POST data: " . print_r($_POST, true));
        error_log("Course ID: " . ($courseId ?? 'NULL'));
        error_log("Feedback Package ID: " . ($feedbackPackageId ?? 'NULL'));
        error_log("Responses: " . print_r($responses, true));
        error_log("================================");

        if (!$courseId || !$feedbackPackageId || empty($responses)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required data']);
            return;
        }

        // Decrypt IDs if they're encrypted
        try {
            $courseId = IdEncryption::decrypt($courseId);
            $feedbackPackageId = IdEncryption::decrypt($feedbackPackageId);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid course or feedback information']);
            return;
        }

        $clientId = $_SESSION['user']['client_id'] ?? 1;
        $userId = $_SESSION['id'];
        $successCount = 0;
        $errorCount = 0;

        // Process each response
        foreach ($responses as $questionId => $responseData) {
            error_log("Processing question $questionId: " . print_r($responseData, true));
            
            $responseType = $responseData['type'] ?? '';
            $responseValue = $responseData['value'] ?? '';

            error_log("Response type: $responseType, value: " . print_r($responseValue, true));

            // Determine response type and value
            $data = [
                'client_id' => $clientId,
                'course_id' => $courseId,
                'user_id' => $userId,
                'feedback_package_id' => $feedbackPackageId,
                'question_id' => $questionId,
                'response_type' => $responseType,
                'rating_value' => null,
                'text_response' => null,
                'choice_response' => null,
                'file_response' => null,
                'response_data' => null
            ];

            // Map response types to database enum values
            switch ($responseType) {
                case 'rating':
                    $data['rating_value'] = intval($responseValue);
                    $data['response_type'] = 'rating';
                    break;
                case 'text':
                case 'short_answer':
                case 'long_answer':
                    $data['text_response'] = $responseValue;
                    $data['response_type'] = 'text';
                    break;
                case 'choice':
                case 'multi_choice':
                case 'checkbox':
                case 'dropdown':
                    $data['choice_response'] = $responseValue;
                    $data['response_type'] = 'choice';
                    break;
                case 'file':
                case 'upload':
                    $data['file_response'] = $responseValue;
                    $data['response_type'] = 'file';
                    break;
                default:
                    // Store complex responses in JSON
                    $data['response_data'] = json_encode($responseData);
                    $data['response_type'] = 'text'; // Default to text for unknown types
                    break;
            }

            // Save the response
            $result = $this->FeedbackResponseModel->saveResponse($data);
            if ($result) {
                $successCount++;
            } else {
                // Capture model error if available
                $modelError = method_exists($this->FeedbackResponseModel, 'getLastError') ? $this->FeedbackResponseModel->getLastError() : '';
                if (!empty($modelError)) {
                    error_log("Feedback save error for question {$questionId}: " . $modelError);
                }
                $errorCount++;
            }
        }

        // Set JSON header for modal response
        header('Content-Type: application/json');
        
        if ($errorCount === 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Feedback submitted successfully!',
                'responses_saved' => $successCount
            ]);
        } else {
            $lastError = method_exists($this->FeedbackResponseModel, 'getLastError') ? $this->FeedbackResponseModel->getLastError() : '';
            echo json_encode([
                'success' => false,
                'message' => "Feedback partially saved. $successCount responses saved, $errorCount failed." . (!empty($lastError) ? " Error: " . $lastError : ''),
                'responses_saved' => $successCount,
                'responses_failed' => $errorCount
            ]);
        }
    }

    /**
     * Get feedback form data via AJAX
     */
    public function getFeedbackForm() {
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $courseId = $_GET['course_id'] ?? null;
        $feedbackPackageId = $_GET['feedback_id'] ?? null;

        if (!$courseId || !$feedbackPackageId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required data']);
            return;
        }

        // Decrypt IDs if they're encrypted
        try {
            $courseId = IdEncryption::decrypt($courseId);
            $feedbackPackageId = IdEncryption::decrypt($feedbackPackageId);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid course or feedback information']);
            return;
        }

        // Get feedback package details
        $feedbackPackage = $this->FeedbackResponseModel->getFeedbackPackageForCourse($courseId, $feedbackPackageId);
        
        if (!$feedbackPackage) {
            http_response_code(404);
            echo json_encode(['error' => 'Feedback not found']);
            return;
        }

        // Check if user has already submitted feedback
        $hasSubmitted = $this->FeedbackResponseModel->hasUserSubmittedFeedback($courseId, $_SESSION['id'], $feedbackPackageId);

        // Get existing responses if any
        $existingResponses = [];
        if ($hasSubmitted) {
            $existingResponses = $this->FeedbackResponseModel->getResponsesByCourseAndUser($courseId, $_SESSION['id'], $feedbackPackageId);
        }

        // Get course details
        require_once 'models/CourseModel.php';
        $courseModel = new CourseModel();
        $course = $courseModel->getCourseById($courseId);

        if (!$course) {
            http_response_code(404);
            echo json_encode(['error' => 'Course not found']);
            return;
        }

        // Render the feedback form HTML for modal
        ob_start();
        include 'views/feedback_form_modal.php';
        $html = ob_get_clean();

        // Set JSON header for modal response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'html' => $html,
            'hasSubmitted' => $hasSubmitted,
            'feedbackPackage' => $feedbackPackage,
            'existingResponses' => $existingResponses
        ]);
    }

    /**
     * Check feedback submission status
     */
    public function checkFeedbackStatus() {
        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $courseId = $_GET['course_id'] ?? null;
        $feedbackPackageId = $_GET['feedback_id'] ?? null;

        if (!$courseId || !$feedbackPackageId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required data']);
            return;
        }

        // Decrypt IDs if they're encrypted
        try {
            $courseId = IdEncryption::decrypt($courseId);
            $feedbackPackageId = IdEncryption::decrypt($feedbackPackageId);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid course or feedback information']);
            return;
        }

        $hasSubmitted = $this->FeedbackResponseModel->hasUserSubmittedFeedback($courseId, $_SESSION['id'], $feedbackPackageId);

        echo json_encode([
            'success' => true,
            'has_submitted' => $hasSubmitted
        ]);
    }

    /**
     * Delete feedback responses (for resubmission)
     */
    public function deleteFeedback() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        if (!isset($_SESSION['id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $courseId = $_POST['course_id'] ?? null;
        $feedbackPackageId = $_POST['feedback_package_id'] ?? null;

        if (!$courseId || !$feedbackPackageId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required data']);
            return;
        }

        // Decrypt IDs if they're encrypted
        try {
            $courseId = IdEncryption::decrypt($courseId);
            $feedbackPackageId = IdEncryption::decrypt($feedbackPackageId);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid course or feedback information']);
            return;
        }

        $result = $this->FeedbackResponseModel->deleteResponses($courseId, $_SESSION['id'], $feedbackPackageId);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Feedback responses deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete feedback responses'
            ]);
        }
    }
}
?>
