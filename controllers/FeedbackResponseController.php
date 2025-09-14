<?php
require_once 'config/autoload.php';
require_once 'models/FeedbackResponseModel.php';
require_once 'core/IdEncryption.php';
require_once 'config/Database.php';
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

        // Process responses for completion (don't save individual responses)
        $processedResponses = [];
        $errorCount = 0;

        // Process each response
        foreach ($responses as $questionId => $responseData) {
            $processedResponses[$questionId] = $responseData;
        }

        // Complete the feedback with all responses
        $result = $this->FeedbackResponseModel->completeFeedback($clientId, $courseId, $userId, $feedbackPackageId, $processedResponses);

        if ($result) {
            $successCount = count($processedResponses);
            
            // Trigger completion tracking for prerequisites/post-requisites (same as surveys)
            try {
                require_once 'models/CompletionTrackingService.php';
                $completionService = new CompletionTrackingService();
                $completionService->handleContentCompletion($userId, $courseId, $feedbackPackageId, 'feedback', $clientId);
                
                // Mark prerequisite as complete if applicable
                $this->markPrerequisiteCompleteIfApplicable($userId, $courseId, $feedbackPackageId, $clientId);
            } catch (Exception $e) {
                error_log("Error in feedback completion tracking: " . $e->getMessage());
                // Don't fail the feedback submission if completion tracking fails
            }
        } else {
            $errorCount = count($processedResponses);
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

        try {
            $courseId = IdEncryption::decrypt($_GET['course_id'] ?? '');
            $feedbackPackageId = IdEncryption::decrypt($_GET['feedback_id'] ?? '');
            
            if (!$courseId || !$feedbackPackageId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid feedback link']);
                return;
            }

            $clientId = $_SESSION['user']['client_id'] ?? 1;
            $userId = $_SESSION['id'];

            // Check if user has already submitted this feedback
            $hasSubmitted = $this->FeedbackResponseModel->hasUserSubmittedFeedback($courseId, $userId, $feedbackPackageId);

            // Only start feedback if it hasn't been submitted yet
            if (!$hasSubmitted) {
                $this->FeedbackResponseModel->startFeedback($clientId, $courseId, $userId, $feedbackPackageId);
            }

            // Get existing responses if user has submitted
            $existingResponses = [];
            if ($hasSubmitted) {
                $existingResponses = $this->FeedbackResponseModel->getResponsesByCourseAndUser($courseId, $userId, $feedbackPackageId);
            }

            // Get feedback package with questions
            $feedbackPackage = $this->FeedbackResponseModel->getFeedbackPackageForCourse($courseId, $feedbackPackageId);
            
            if (!$feedbackPackage) {
                $this->jsonResponse(['success' => false, 'message' => 'Feedback not found or access denied']);
                return;
            }

            // Get course details
            require_once 'models/CourseModel.php';
            $courseModel = new CourseModel();
            $course = $courseModel->getCourseById($courseId);

            if (!$course) {
                $this->jsonResponse(['success' => false, 'message' => 'Course not found']);
                return;
            }

            // Prepare data for view
            $data = [
                'course_id' => $courseId,
                'course' => $course,
                'feedback_package' => $feedbackPackage,
                'has_submitted' => $hasSubmitted,
                'existing_responses' => $existingResponses,
                'encrypted_course_id' => IdEncryption::encrypt($courseId),
                'encrypted_feedback_id' => IdEncryption::encrypt($feedbackPackageId)
            ];

            // Render the feedback form content
            ob_start();
            extract($data);
            include 'views/feedback_form_modal.php';
            $html = ob_get_clean();

            $this->jsonResponse([
                'success' => true,
                'html' => $html,
                'title' => $feedbackPackage['title']
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while loading the feedback']);
        }
    }

    /**
     * Send JSON response
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
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

    /**
     * Check if feedback is a prerequisite and mark as complete
     */
    private function markPrerequisiteCompleteIfApplicable($userId, $courseId, $feedbackPackageId, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Check if this feedback is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $feedbackPackageId, 'feedback');
            
            if ($isPrerequisite) {
                $completionService->markPrerequisiteComplete($userId, $courseId, $feedbackPackageId, 'feedback', $clientId);
            }
        } catch (Exception $e) {
            error_log("Error in markPrerequisiteCompleteIfApplicable: " . $e->getMessage());
        }
    }

    /**
     * Check if content is a prerequisite
     */
    private function isContentPrerequisite($courseId, $contentId, $contentType) {
        try {
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT COUNT(*) FROM course_prerequisites 
                    WHERE course_id = ? AND prerequisite_id = ? AND prerequisite_type = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId, $contentId, $contentType]);
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error checking if content is prerequisite: " . $e->getMessage());
            return false;
        }
    }
}
?>
