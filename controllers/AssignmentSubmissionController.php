<?php
/**
 * Assignment Submission Controller
 * 
 * Handles assignment submission operations
 */

require_once 'controllers/BaseController.php';
require_once 'models/AssignmentSubmissionModel.php';
require_once 'models/SharedContentCompletionService.php';
require_once 'core/IdEncryption.php';

class AssignmentSubmissionController extends BaseController {
    private $assignmentSubmissionModel;
    private $conn;
    private $sharedContentService;

    public function __construct() {
        $this->assignmentSubmissionModel = new AssignmentSubmissionModel();
        $this->sharedContentService = new SharedContentCompletionService();
        
        // Initialize database connection
        require_once 'config/Database.php';
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Start assignment tracking
     */
    public function startAssignment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            // Check if user is logged in
            if (!isset($_SESSION['id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Please log in to start the assignment']);
                return;
            }

            $clientId = $_SESSION['user']['client_id'] ?? 1;
            $userId = $_SESSION['id'];

            // Get and validate input data
            $inputData = [];
            if (!empty($_POST)) {
                $inputData = $_POST;
            } else {
                $rawInput = file_get_contents('php://input');
                $inputData = json_decode($rawInput, true);
            }
            
            $courseId = $inputData['course_id'] ?? '';
            $assignmentPackageId = $inputData['assignment_package_id'] ?? '';
            $submissionType = $inputData['submission_type'] ?? null;
            
            if (empty($courseId) || empty($assignmentPackageId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Missing required data']);
                return;
            }

            // Decode IDs
            $courseId = IdEncryption::decrypt($courseId);
            $assignmentPackageId = IdEncryption::decrypt($assignmentPackageId);

            if (!$courseId || !$assignmentPackageId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid assignment data']);
                return;
            }

            // Get assignment package details
            $assignmentPackage = $this->assignmentSubmissionModel->getAssignmentPackage($assignmentPackageId);
            if (!$assignmentPackage) {
                $this->jsonResponse(['success' => false, 'message' => 'Assignment not found']);
                return;
            }

            // Check if user already has a completed submission
            $existingSubmissions = $this->assignmentSubmissionModel->getSubmissionsByCourseAndUser($courseId, $userId, $assignmentPackageId);
            $hasCompletedSubmission = false;
            foreach ($existingSubmissions as $submission) {
                if (in_array($submission['submission_status'], ['submitted', 'graded', 'returned'])) {
                    $hasCompletedSubmission = true;
                    break;
                }
            }

            if ($hasCompletedSubmission) {
                $this->jsonResponse(['success' => false, 'message' => 'Assignment already completed']);
                return;
            }

            // Get attempt count for in-progress submissions
            $attemptCount = $this->assignmentSubmissionModel->getUserSubmissionAttempts($courseId, $userId, $assignmentPackageId);
            $maxAttempts = $assignmentPackage['max_attempts'] ?? 1;
            
            if ($attemptCount >= $maxAttempts) {
                $this->jsonResponse(['success' => false, 'message' => "Maximum attempts ($maxAttempts) exceeded for this assignment"]);
                return;
            }

            // Note: Completion tracking is now handled only when content is actually completed

            // Start assignment tracking
            $trackingData = [
                'client_id' => $clientId,
                'course_id' => $courseId,
                'user_id' => $userId,
                'assignment_package_id' => $assignmentPackageId,
                'submission_type' => $submissionType,
                'attempt_number' => $attemptCount + 1
            ];

            $submissionId = $this->assignmentSubmissionModel->startAssignmentTracking($trackingData);
            
            if ($submissionId) {
                $this->jsonResponse([
                    'success' => true, 
                    'message' => 'Assignment tracking started successfully',
                    'submission_id' => $submissionId
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Failed to start assignment tracking: ' . $this->assignmentSubmissionModel->getLastError()
                ]);
            }

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while starting assignment tracking']);
        }
    }

    /**
     * Save assignment progress (without submitting)
     */
    public function saveProgress() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            // Check if user is logged in
            if (!isset($_SESSION['id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Please log in to save progress']);
                return;
            }

            $clientId = $_SESSION['user']['client_id'] ?? 1;
            $userId = $_SESSION['id'];

            // Get and validate input data
            $inputData = [];
            if (!empty($_POST)) {
                $inputData = $_POST;
            } else {
                $rawInput = file_get_contents('php://input');
                $inputData = json_decode($rawInput, true);
            }
            
            $courseId = $inputData['course_id'] ?? '';
            $assignmentPackageId = $inputData['assignment_package_id'] ?? '';
            $submissionType = $inputData['submission_type'] ?? 'file_upload';
            $submissionText = $inputData['submission_text'] ?? '';
            $submissionUrl = $inputData['submission_url'] ?? '';
            
            if (empty($courseId) || empty($assignmentPackageId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Missing required data']);
                return;
            }

            // Decode IDs
            $courseId = IdEncryption::decrypt($courseId);
            $assignmentPackageId = IdEncryption::decrypt($assignmentPackageId);

            if (!$courseId || !$assignmentPackageId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid assignment data']);
                return;
            }

            // Get assignment package details
            $assignmentPackage = $this->assignmentSubmissionModel->getAssignmentPackage($assignmentPackageId);
            if (!$assignmentPackage) {
                $this->jsonResponse(['success' => false, 'message' => 'Assignment not found']);
                return;
            }

            // Check if user already has a completed submission
            $existingSubmissions = $this->assignmentSubmissionModel->getSubmissionsByCourseAndUser($courseId, $userId, $assignmentPackageId);
            $hasCompletedSubmission = false;
            foreach ($existingSubmissions as $submission) {
                if (in_array($submission['submission_status'], ['submitted', 'graded', 'returned'])) {
                    $hasCompletedSubmission = true;
                    break;
                }
            }

            if ($hasCompletedSubmission) {
                $this->jsonResponse(['success' => false, 'message' => 'Assignment already completed']);
                return;
            }

            // Handle file upload if submission type includes file_upload
            $submissionFile = null;
            if (in_array('file_upload', is_array($submissionType) ? $submissionType : [$submissionType]) && !empty($_FILES['submission_file'])) {
                $uploadResult = $this->handleFileUpload($_FILES['submission_file'], $assignmentPackage);
                if (!$uploadResult['success']) {
                    $this->jsonResponse(['success' => false, 'message' => $uploadResult['message']]);
                    return;
                }
                $submissionFile = $uploadResult['filename'];
            }

            // Get or create in-progress submission
            $existingInProgressSubmission = $this->assignmentSubmissionModel->getInProgressSubmission($courseId, $userId, $assignmentPackageId);
            
            if ($existingInProgressSubmission) {
                // Update existing in-progress submission
                $updateData = [
                    'submission_type' => is_array($submissionType) ? implode(',', $submissionType) : $submissionType,
                    'submission_file' => $submissionFile,
                    'submission_text' => $submissionText,
                    'submission_url' => $submissionUrl,
                    'submission_status' => 'in-progress'
                ];
                
                $result = $this->assignmentSubmissionModel->updateInProgressSubmission($existingInProgressSubmission['id'], $updateData);
                
                if ($result) {
                    $this->jsonResponse([
                        'success' => true, 
                        'message' => 'Progress saved successfully',
                        'submission_id' => $existingInProgressSubmission['id']
                    ]);
                } else {
                    $this->jsonResponse([
                        'success' => false, 
                        'message' => 'Failed to save progress: ' . $this->assignmentSubmissionModel->getLastError()
                    ]);
                }
            } else {
                // Note: Completion tracking is now handled only when content is actually completed
                
                // Create new in-progress submission
                $trackingData = [
                    'client_id' => $clientId,
                    'course_id' => $courseId,
                    'user_id' => $userId,
                    'assignment_package_id' => $assignmentPackageId,
                    'submission_type' => is_array($submissionType) ? implode(',', $submissionType) : $submissionType,
                    'submission_file' => $submissionFile,
                    'submission_text' => $submissionText,
                    'submission_url' => $submissionUrl,
                    'attempt_number' => 1
                ];

                $submissionId = $this->assignmentSubmissionModel->startAssignmentTracking($trackingData);
                
                if ($submissionId) {
                    $this->jsonResponse([
                        'success' => true, 
                        'message' => 'Progress saved successfully',
                        'submission_id' => $submissionId
                    ]);
                } else {
                    $this->jsonResponse([
                        'success' => false, 
                        'message' => 'Failed to save progress: ' . $this->assignmentSubmissionModel->getLastError()
                    ]);
                }
            }

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while saving progress']);
        }
    }

    /**
     * Show assignment submission form
     */
    public function showAssignment($assignmentId, $courseId) {
        try {
            // Decode IDs
            $assignmentId = IdEncryption::decrypt($assignmentId);
            $courseId = IdEncryption::decrypt($courseId);

            if (!$assignmentId || !$courseId) {
                $this->toastError('Invalid assignment data.', '/unlockyourskills/my-courses');
                return;
            }

            // Get assignment package details
            $assignmentPackage = $this->assignmentSubmissionModel->getAssignmentPackage($assignmentId);
            
            if (!$assignmentPackage) {
                $this->toastError('Assignment not found.', '/unlockyourskills/my-courses');
                return;
            }

            // Get user's existing submissions
            $userId = $_SESSION['id'];
            $existingSubmissions = $this->assignmentSubmissionModel->getSubmissionsByCourseAndUser($courseId, $userId, $assignmentId);
            
            // Check if user has completed submissions (not just in-progress)
            $hasSubmitted = false;
            foreach ($existingSubmissions as $submission) {
                if (in_array($submission['submission_status'], ['submitted', 'graded', 'returned'])) {
                    $hasSubmitted = true;
                    break;
                }
            }

            $data = [
                'assignment' => $assignmentPackage,
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'existing_submissions' => $existingSubmissions,
                'has_submitted' => $hasSubmitted,
                'user_id' => $userId
            ];

            // Include the view directly
            extract($data);
            require 'views/assignment_submission.php';

        } catch (Exception $e) {
            $this->toastError('An error occurred while loading the assignment.', '/unlockyourskills/my-courses');
        }
    }

    /**
     * Get assignment form content for modal
     */
    public function getAssignmentModalContent() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            $assignmentId = $_GET['assignment_id'] ?? '';
            $courseId = $_GET['course_id'] ?? '';

            if (empty($assignmentId) || empty($courseId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }

            // Decode IDs
            $assignmentId = IdEncryption::decrypt($assignmentId);
            $courseId = IdEncryption::decrypt($courseId);

            if (!$assignmentId || !$courseId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid assignment data']);
                return;
            }

            // Get assignment package details
            $assignmentPackage = $this->assignmentSubmissionModel->getAssignmentPackage($assignmentId);
            
            if (!$assignmentPackage) {
                $this->jsonResponse(['success' => false, 'message' => 'Assignment not found']);
                return;
            }

            // Get user's existing submissions
            $userId = $_SESSION['id'];
            $existingSubmissions = $this->assignmentSubmissionModel->getSubmissionsByCourseAndUser($courseId, $userId, $assignmentId);
            
            // Check if user has completed submissions (not just in-progress)
            $hasSubmitted = false;
            foreach ($existingSubmissions as $submission) {
                if (in_array($submission['submission_status'], ['submitted', 'graded', 'returned'])) {
                    $hasSubmitted = true;
                    break;
                }
            }

            // Get in-progress submission data for pre-populating form
            $inProgressSubmission = $this->assignmentSubmissionModel->getInProgressSubmission($courseId, $userId, $assignmentId);
            $hasInProgress = !empty($inProgressSubmission);

            $data = [
                'assignment' => $assignmentPackage,
                'course_id' => $courseId,
                'assignment_id' => $assignmentId,
                'existing_submissions' => $existingSubmissions,
                'has_submitted' => $hasSubmitted,
                'has_in_progress' => $hasInProgress,
                'in_progress_submission' => $inProgressSubmission,
                'user_id' => $userId
            ];

            // Capture the view output
            ob_start();
            extract($data);
            require 'views/assignment_submission_modal.php';
            $html = ob_get_clean();

            $this->jsonResponse([
                'success' => true,
                'html' => $html,
                'title' => $assignmentPackage['title']
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while loading the assignment']);
        }
    }

    /**
     * Submit assignment
     */
    public function submitAssignment() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            // Check if user is logged in
            if (!isset($_SESSION['id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Please log in to submit the assignment']);
                return;
            }

            $clientId = $_SESSION['user']['client_id'] ?? 1;
            $userId = $_SESSION['id'];

            // Get and validate input data
            $inputData = [];
            if (!empty($_POST)) {
                $inputData = $_POST;
            } else {
                $rawInput = file_get_contents('php://input');
                $inputData = json_decode($rawInput, true);
            }
            
            $courseId = $inputData['course_id'] ?? '';
            $assignmentPackageId = $inputData['assignment_package_id'] ?? '';
            $submissionType = $inputData['submission_type'] ?? 'file_upload';
            $submissionText = $inputData['submission_text'] ?? '';
            $submissionUrl = $inputData['submission_url'] ?? '';
            
            // Handle mixed submission format (array of submission types)
            $submissionTypes = is_array($submissionType) ? $submissionType : [$submissionType];

            if (empty($courseId) || empty($assignmentPackageId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Missing required data']);
                return;
            }

            // Decode IDs
            $courseId = IdEncryption::decrypt($courseId);
            $assignmentPackageId = IdEncryption::decrypt($assignmentPackageId);

            if (!$courseId || !$assignmentPackageId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid assignment data']);
                return;
            }

            // Get assignment package details
            $assignmentPackage = $this->assignmentSubmissionModel->getAssignmentPackage($assignmentPackageId);
            if (!$assignmentPackage) {
                $this->jsonResponse(['success' => false, 'message' => 'Assignment not found']);
                return;
            }

            // Note: Completion tracking is now handled only when content is actually completed

            // Check submission attempts
            $attemptCount = $this->assignmentSubmissionModel->getUserSubmissionAttempts($courseId, $userId, $assignmentPackageId);
            $maxAttempts = $assignmentPackage['max_attempts'] ?? 1;
            
            if ($attemptCount >= $maxAttempts) {
                $this->jsonResponse(['success' => false, 'message' => "Maximum attempts ($maxAttempts) exceeded for this assignment"]);
                return;
            }

            // Check if there's an existing in-progress submission to update
            $existingInProgressSubmission = $this->assignmentSubmissionModel->getInProgressSubmission($courseId, $userId, $assignmentPackageId);
            
            // Check for duplicate completed submission within last 30 seconds (only for completed submissions)
            $recentCompletedSubmission = $this->assignmentSubmissionModel->getRecentCompletedSubmission($courseId, $userId, $assignmentPackageId, 30);
            if ($recentCompletedSubmission) {
                $this->jsonResponse(['success' => false, 'message' => 'Duplicate submission detected. Please wait before submitting again.']);
                return;
            }

            // Handle file upload if submission type includes file_upload
            $submissionFile = null;
            if (in_array('file_upload', $submissionTypes) && !empty($_FILES['submission_file'])) {
                $uploadResult = $this->handleFileUpload($_FILES['submission_file'], $assignmentPackage);
                if (!$uploadResult['success']) {
                    $this->jsonResponse(['success' => false, 'message' => $uploadResult['message']]);
                    return;
                }
                $submissionFile = $uploadResult['filename'];
            }

            // Validate submission based on selected types
            $validationErrors = [];
            if (in_array('file_upload', $submissionTypes) && empty($submissionFile)) {
                $validationErrors[] = 'Please upload a file for file upload submission';
            }
            if (in_array('text_entry', $submissionTypes) && empty($submissionText)) {
                $validationErrors[] = 'Please provide text content for text entry submission';
            }
            if (in_array('url_submission', $submissionTypes) && empty($submissionUrl)) {
                $validationErrors[] = 'Please provide a URL for URL submission';
            }
            
            if (!empty($validationErrors)) {
                $this->jsonResponse(['success' => false, 'message' => implode('. ', $validationErrors)]);
                return;
            }

            // Calculate if submission is late (for now, default to not late)
            $isLate = 0; // Default to not late since we don't have due date logic yet
            
            // Prepare submission data
            $submissionData = [
                'client_id' => $clientId,
                'course_id' => $courseId,
                'user_id' => $userId,
                'assignment_package_id' => $assignmentPackageId,
                'submission_type' => implode(',', $submissionTypes), // Store as comma-separated string
                'submission_file' => $submissionFile,
                'submission_text' => $submissionText,
                'submission_url' => $submissionUrl,
                'submission_status' => 'submitted',
                'due_date' => null, // No due date logic implemented yet
                'is_late' => $isLate,
                'attempt_number' => $attemptCount + 1
            ];

            // Save or update submission
            if ($existingInProgressSubmission) {
                // Update existing in-progress submission
                $submissionId = $this->assignmentSubmissionModel->updateSubmissionToCompleted($existingInProgressSubmission['id'], $submissionData);
            } else {
                // Create new submission
                $submissionId = $this->assignmentSubmissionModel->saveSubmission($submissionData);
            }
            
            if ($submissionId) {
                // Update progress tracking for assignment submission
                try {
                    require_once 'models/ProgressTrackingModel.php';
                    $progressModel = new ProgressTrackingModel();
                    
                    // Get the course_module_content.id for this assignment
                    $stmt = $this->conn->prepare("
                        SELECT cmc.id as content_id 
                        FROM course_module_content cmc
                        WHERE cmc.content_id = ? AND cmc.content_type = 'assignment'
                    ");
                    $stmt->execute([$assignmentPackageId]);
                    $contentId = $stmt->fetchColumn();
                    
                    if ($contentId) {
                        // Update assignment progress
                        $progressModel->updateAssignmentProgress($userId, $courseId, $contentId, $clientId, [
                            'submission_status' => 'submitted',
                            'submission_id' => $submissionId
                        ]);
                        
                        // Recalculate course progress
                        $progressModel->calculateCourseProgress($userId, $courseId, $clientId);
                    }
                    
                    // Handle shared content completion
                    $this->sharedContentService->handleSharedContentCompletion(
                        $userId, $courseId, $assignmentPackageId, $clientId, 'assignment', 'module'
                    );
                } catch (Exception $e) {
                    error_log("Error updating assignment progress: " . $e->getMessage());
                    // Don't fail the submission if progress tracking fails
                }
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Assignment submitted successfully!',
                    'submission_id' => $submissionId
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to submit assignment: ' . $this->assignmentSubmissionModel->getLastError()
                ]);
            }

        } catch (Exception $e) {
            error_log("Assignment submission error: " . $e->getMessage());
            error_log("Assignment submission error trace: " . $e->getTraceAsString());
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while submitting the assignment: ' . $e->getMessage()]);
        }
    }

    /**
     * Check assignment submission status
     */
    public function checkSubmissionStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            $assignmentId = $_GET['assignment_id'] ?? '';
            $courseId = $_GET['course_id'] ?? '';

            if (empty($assignmentId) || empty($courseId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }

            // Decode IDs
            $assignmentId = IdEncryption::decrypt($assignmentId);
            $courseId = IdEncryption::decrypt($courseId);

            if (!$assignmentId || !$courseId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid assignment data']);
                return;
            }

            // Check assignment status
            $userId = $_SESSION['id'];
            $hasSubmitted = $this->assignmentSubmissionModel->hasUserSubmittedAssignment($courseId, $userId, $assignmentId);
            $hasInProgress = !empty($this->assignmentSubmissionModel->getInProgressSubmission($courseId, $userId, $assignmentId));

            $this->jsonResponse([
                'success' => true,
                'has_submitted' => $hasSubmitted,
                'has_in_progress' => $hasInProgress
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while checking assignment status']);
        }
    }

    /**
     * Handle file upload
     */
    private function handleFileUpload($file, $assignmentPackage) {
        try {
            // Check file size (50MB max)
            if ($file['size'] > 50 * 1024 * 1024) {
                return ['success' => false, 'message' => 'File size cannot exceed 50MB'];
            }

            // Check file type
            $allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedTypes)) {
                return ['success' => false, 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)];
            }

            // Create upload directory (use correct path from controllers directory)
            $uploadDir = dirname(__DIR__) . "/uploads/assignment_submissions/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
                chmod($uploadDir, 0777);
            }

            // Generate unique filename
            $uniqueName = uniqid("assignment_submission_") . "." . $fileExtension;
            $targetPath = $uploadDir . $uniqueName;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                return ['success' => true, 'filename' => $uniqueName];
            } else {
                $error = error_get_last();
                error_log("File upload failed. Source: " . $file['tmp_name'] . ", Target: " . $targetPath);
                error_log("Upload error: " . ($error['message'] ?? 'Unknown error'));
                error_log("Upload directory exists: " . (is_dir($uploadDir) ? 'Yes' : 'No'));
                error_log("Upload directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No'));
                return ['success' => false, 'message' => 'Failed to upload file: ' . ($error['message'] ?? 'Unknown error')];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'File upload error: ' . $e->getMessage()];
        }
    }

    /**
     * Get assignment submissions for a user
     */
    public function getSubmissions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            $courseId = $_GET['course_id'] ?? '';
            $assignmentPackageId = $_GET['assignment_id'] ?? '';

            if (empty($courseId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Missing course ID']);
                return;
            }

            // Decode IDs
            $courseId = IdEncryption::decrypt($courseId);
            if ($assignmentPackageId) {
                $assignmentPackageId = IdEncryption::decrypt($assignmentPackageId);
            }

            if (!$courseId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid course data']);
                return;
            }

            $userId = $_SESSION['id'];
            $submissions = $this->assignmentSubmissionModel->getSubmissionsByCourseAndUser($courseId, $userId, $assignmentPackageId);

            $this->jsonResponse([
                'success' => true,
                'submissions' => $submissions
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while retrieving submissions']);
        }
    }


    /**
     * Get assignment packages for a course
     */
    public function getCourseAssignments() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->jsonResponse(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        try {
            $courseId = $_GET['course_id'] ?? '';

            if (empty($courseId)) {
                $this->jsonResponse(['success' => false, 'message' => 'Missing course ID']);
                return;
            }

            // Decode course ID
            $courseId = IdEncryption::decrypt($courseId);

            if (!$courseId) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid course data']);
                return;
            }

            $assignmentPackages = $this->assignmentSubmissionModel->getCourseAssignmentPackages($courseId);

            $this->jsonResponse([
                'success' => true,
                'assignments' => $assignmentPackages
            ]);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while retrieving assignments']);
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
     * Check if content is a prerequisite
     */
    private function isContentPrerequisite($courseId, $contentId, $contentType) {
        try {
            $sql = "SELECT COUNT(*) FROM course_prerequisites 
                    WHERE course_id = ? AND prerequisite_id = ? AND prerequisite_type = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $contentId, $contentType]);
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error checking if content is prerequisite: " . $e->getMessage());
            return false;
        }
    }
}
?>
