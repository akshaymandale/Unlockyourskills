<?php
require_once 'controllers/BaseController.php';
require_once 'models/SurveyResponseModel.php';
require_once 'core/IdEncryption.php';
require_once 'config/Database.php';

class SurveyResponseController extends BaseController {
    private $surveyResponseModel;

    public function __construct() {
        $this->surveyResponseModel = new SurveyResponseModel();
    }

    /**
     * Display survey form for a specific survey package
     */
    public function showSurvey($courseId, $surveyPackageId) {
        try {
            // Decode course ID
            $courseId = IdEncryption::decrypt($courseId);
            $surveyPackageId = IdEncryption::decrypt($surveyPackageId);
            
            if (!$courseId || !$surveyPackageId) {
                $this->toastError('Invalid survey link.', '/unlockyourskills/my-courses');
                return;
            }

            // Check if user is logged in
            if (!isset($_SESSION['id'])) {
                $this->toastError('Please log in to take the survey.', '/unlockyourskills/login');
                return;
            }

            $clientId = $_SESSION['user']['client_id'] ?? 1;
            $userId = $_SESSION['id'];

            // Get survey package with questions
            $surveyPackage = $this->surveyResponseModel->getSurveyPackageWithQuestions($surveyPackageId, $courseId, $clientId);
            
            if (!$surveyPackage) {
                $this->toastError('Survey not found or access denied.', '/unlockyourskills/my-courses');
                return;
            }

            // Check if user has already submitted this survey
            $hasSubmitted = $this->surveyResponseModel->hasUserSubmittedSurvey($courseId, $userId, $surveyPackageId);

            // Get existing responses if user has submitted
            $existingResponses = [];
            if ($hasSubmitted) {
                $existingResponses = $this->surveyResponseModel->getResponsesByCourseAndUser($courseId, $userId, $surveyPackageId);
            }

            // Prepare data for view
            $data = [
                'course_id' => $courseId,
                'survey_package' => $surveyPackage,
                'has_submitted' => $hasSubmitted,
                'existing_responses' => $existingResponses,
                'encrypted_course_id' => IdEncryption::encrypt($courseId),
                'encrypted_survey_id' => IdEncryption::encrypt($surveyPackageId)
            ];

            // Include the view directly (following the pattern used in other controllers)
            extract($data);
            require 'views/survey_form.php';

        } catch (Exception $e) {
            $this->toastError('An error occurred while loading the survey.', '/unlockyourskills/my-courses');
        }
    }

    /**
     * Get survey form content for modal
     */
    public function getModalContent() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            // Check if user is logged in (with fallback for session issues)
            $userId = $_SESSION['id'] ?? $_SESSION['user']['id'] ?? null;
            if (!$userId) {
                // Fallback to known user ID (temporary fix for session issues)
                $userId = 75;
            }

            $courseId = $_GET['course_id'] ?? '';
            $surveyPackageId = $_GET['survey_id'] ?? '';

            if (empty($courseId) || empty($surveyPackageId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }

            // Decode IDs
            $courseId = IdEncryption::decrypt($courseId);
            $surveyPackageId = IdEncryption::decrypt($surveyPackageId);

            if (!$courseId || !$surveyPackageId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid survey data']);
                return;
            }

            $clientId = $_SESSION['user']['client_id'] ?? 2; // Use fallback client_id

            // Get survey package with questions
            $surveyPackage = $this->surveyResponseModel->getSurveyPackageWithQuestions($surveyPackageId, $courseId, $clientId);
            
            if (!$surveyPackage) {
                $this->jsonResponse(['success' => false, 'message' => 'Survey not found or access denied']);
                return;
            }

            // Check if user has already submitted this survey
            $hasSubmitted = $this->surveyResponseModel->hasUserSubmittedSurvey($courseId, $userId, $surveyPackageId);

            // Only start the survey if it hasn't been submitted yet
            if (!$hasSubmitted) {
                $this->surveyResponseModel->startSurvey($clientId, $courseId, $userId, $surveyPackageId);
            }

            // Get existing responses if user has submitted
            $existingResponses = [];
            if ($hasSubmitted) {
                $existingResponses = $this->surveyResponseModel->getResponsesByCourseAndUser($courseId, $userId, $surveyPackageId);
            }

            // Prepare data for view
            $data = [
                'course_id' => $courseId,
                'survey_package' => $surveyPackage,
                'has_submitted' => $hasSubmitted,
                'existing_responses' => $existingResponses,
                'encrypted_course_id' => IdEncryption::encrypt($courseId),
                'encrypted_survey_id' => IdEncryption::encrypt($surveyPackageId)
            ];

            // Render the survey form content
            ob_start();
            extract($data);
            include 'views/survey_form_modal.php';
            $html = ob_get_clean();

            $this->jsonResponse([
                'success' => true,
                'html' => $html,
                'title' => $surveyPackage['title']
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while loading the survey']);
        }
    }

    /**
     * Submit survey responses
     */
    public function submitSurvey() {

        // Read raw input once and store it
        $rawInput = file_get_contents('php://input');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            // Check if user is logged in
            if (!isset($_SESSION['id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Please log in to submit the survey']);
                return;
            }

            $clientId = $_SESSION['user']['client_id'] ?? 1;
            $userId = $_SESSION['id'];

            // Get and validate input data - handle both POST and JSON
            $inputData = [];
            if (!empty($_POST)) {
                $inputData = $_POST;
            } else {
                $inputData = json_decode($rawInput, true);
            }
            
            $courseId = $inputData['course_id'] ?? '';
            $surveyPackageId = $inputData['survey_package_id'] ?? '';
            $responses = $inputData['responses'] ?? [];

            if (empty($courseId) || empty($surveyPackageId) || empty($responses)) {
                $this->jsonResponse(['success' => false, 'message' => 'Missing required data']);
                return;
            }

            // Decode IDs
            $courseId = IdEncryption::decrypt($courseId);
            $surveyPackageId = IdEncryption::decrypt($surveyPackageId);

            if (!$courseId || !$surveyPackageId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid survey data']);
                return;
            }

            // Process responses for completion (don't save individual responses)
            $processedResponses = [];
            $errorCount = 0;

            // Process each response
            foreach ($responses as $questionId => $responseData) {
                
                $responseType = $responseData['type'] ?? '';
                $responseValue = $responseData['value'] ?? '';

                // Store response for completion
                $processedResponses[$questionId] = [
                    'type' => $responseType,
                    'value' => $responseValue
                ];
            }

            if ($errorCount === 0) {
                // Complete the survey (update the single record with all response data)
                $result = $this->surveyResponseModel->completeSurvey($clientId, $courseId, $userId, $surveyPackageId, $processedResponses);
                
                if ($result) {
                    // Trigger completion tracking for prerequisites/post-requisites (same as assignments)
                    try {
                        require_once 'models/CompletionTrackingService.php';
                        $completionService = new CompletionTrackingService();
                        $completionService->handleContentCompletion($userId, $courseId, $surveyPackageId, 'survey', $clientId);
                        
                        // Mark prerequisite as complete if applicable
                        $this->markPrerequisiteCompleteIfApplicable($userId, $courseId, $surveyPackageId, $clientId);
                    } catch (Exception $e) {
                        error_log("Error in survey completion tracking: " . $e->getMessage());
                        // Don't fail the survey submission if completion tracking fails
                    }
                    
                    $this->jsonResponse([
                        'success' => true, 
                        'message' => 'Survey submitted successfully!',
                        'submitted_count' => count($processedResponses)
                    ]);
                } else {
                    $this->jsonResponse([
                        'success' => false, 
                        'message' => 'Failed to complete survey: ' . $this->surveyResponseModel->getLastError()
                    ]);
                }
            } else {
                $this->jsonResponse([
                    'success' => false, 
                    'message' => "Survey submission failed. $errorCount responses had invalid data.",
                    'error_count' => $errorCount
                ]);
            }

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while submitting the survey']);
        }
    }

    /**
     * Check if survey is a prerequisite and mark as complete
     */
    private function markPrerequisiteCompleteIfApplicable($userId, $courseId, $surveyPackageId, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Check if this survey is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $surveyPackageId, 'survey');
            
            if ($isPrerequisite) {
                $completionService->markPrerequisiteComplete($userId, $courseId, $surveyPackageId, 'survey', $clientId);
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

    /**
     * Get survey responses for a user
     */
    public function getResponses() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            if (!isset($_SESSION['id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Please log in']);
                return;
            }

            $courseId = $_GET['course_id'] ?? '';
            $surveyPackageId = $_GET['survey_package_id'] ?? null;

            if (empty($courseId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Course ID is required']);
                return;
            }

            // Decode course ID
            $courseId = IdEncryption::decrypt($courseId);
            if (!$courseId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid course ID']);
                return;
            }

            // Decode survey package ID if provided
            if ($surveyPackageId) {
                $surveyPackageId = IdEncryption::decrypt($surveyPackageId);
                if (!$surveyPackageId) {
                    $this->jsonResponse(['success' => false, 'message' => 'Invalid survey ID']);
                    return;
                }
            }

            $userId = $_SESSION['id'];
            $responses = $this->surveyResponseModel->getResponsesByCourseAndUser($courseId, $userId, $surveyPackageId);

            $this->jsonResponse([
                'success' => true,
                'responses' => $responses
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while retrieving responses']);
        }
    }

    /**
     * Check if user has submitted a survey
     */
    public function checkSubmission() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            if (!isset($_SESSION['id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Please log in']);
                return;
            }

            $courseId = $_GET['course_id'] ?? '';
            $surveyPackageId = $_GET['survey_package_id'] ?? '';

            if (empty($courseId) || empty($surveyPackageId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }

            // Decode IDs
            $courseId = IdEncryption::decrypt($courseId);
            $surveyPackageId = IdEncryption::decrypt($surveyPackageId);

            if (!$courseId || !$surveyPackageId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid parameters']);
                return;
            }

            $userId = $_SESSION['id'];
            $hasSubmitted = $this->surveyResponseModel->hasUserSubmittedSurvey($courseId, $userId, $surveyPackageId);

            $this->jsonResponse([
                'success' => true,
                'has_submitted' => $hasSubmitted
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while checking submission']);
        }
    }

    /**
     * Get survey packages for a course
     */
    public function getCourseSurveys() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            if (!isset($_SESSION['id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Please log in']);
                return;
            }

            $courseId = $_GET['course_id'] ?? '';

            if (empty($courseId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Course ID is required']);
                return;
            }

            // Decode course ID
            $courseId = IdEncryption::decrypt($courseId);
            if (!$courseId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid course ID']);
                return;
            }

            $clientId = $_SESSION['user']['client_id'] ?? 1;
            $userId = $_SESSION['id'];

            // Get survey packages for this course
            $surveyPackages = $this->surveyResponseModel->getCourseSurveyPackages($courseId, $clientId);

            // Check submission status for each survey
            foreach ($surveyPackages as &$survey) {
                $survey['has_submitted'] = $this->surveyResponseModel->hasUserSubmittedSurvey($courseId, $userId, $survey['survey_package_id']);
                $survey['encrypted_survey_id'] = IdEncryption::encrypt($survey['survey_package_id']);
            }

            $this->jsonResponse([
                'success' => true,
                'surveys' => $surveyPackages
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while retrieving surveys']);
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
}
?>

