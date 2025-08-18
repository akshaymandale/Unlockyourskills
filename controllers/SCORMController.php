<?php

require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/VLRModel.php';
require_once __DIR__ . '/../models/ProgressTrackingModel.php';

/**
 * SCORM Controller
 * Handles SCORM content launching and management
 */
class SCORMController extends BaseController
{
    private $vlrModel;
    private $progressTrackingModel;

    public function __construct()
    {
        $this->vlrModel = new VLRModel();
        $this->progressTrackingModel = new ProgressTrackingModel();
    }

    /**
     * Launch SCORM content
     * GET /scorm/launch
     */
    public function launch()
    {
        try {
            // Debug session information
            error_log("[SCORM] Launch method called");
            error_log("[SCORM] Session data: " . print_r($_SESSION, true));
            error_log("[SCORM] Session ID: " . session_id());
            error_log("[SCORM] Session status: " . session_status());
            
            // Get parameters
            $courseId = $_GET['course_id'] ?? null;
            $moduleId = $_GET['module_id'] ?? null;
            $contentId = $_GET['content_id'] ?? null;
            $scormId = $_GET['scorm_id'] ?? null;

            // Validate required parameters
            if (!$courseId || !$moduleId || !$contentId) {
                $this->toastError('Missing required parameters for SCORM content.', '/unlockyourskills/my-courses');
                return;
            }

            // Get user information
            $userId = $_SESSION['user']['id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            
            error_log("[SCORM] User ID: " . ($userId ?? 'null'));
            error_log("[SCORM] Client ID: " . ($clientId ?? 'null'));

            if (!$userId || !$clientId) {
                error_log("[SCORM] Authentication failed - redirecting to login");
                $this->toastError('User not authenticated.', '/unlockyourskills/login');
                return;
            }
            
            error_log("[SCORM] Authentication successful");

            // Get SCORM package information from the database
            $scormPackage = null;
            
            // First try to get by scormId if provided
            if ($scormId) {
                $scormPackage = $this->vlrModel->getScormPackageById($scormId, $clientId);
            }
            
            // If no specific SCORM package, try to get from content
            if (!$scormPackage) {
                // Try to get SCORM package by content ID from the database
                try {
                    $scormPackage = $this->vlrModel->getScormPackageByContentId($contentId, $clientId);
                    
                    if ($scormPackage) {
                        error_log("[SCORM] Found SCORM package for content {$contentId}: " . json_encode($scormPackage));
                    } else {
                        error_log("[SCORM] No SCORM package found for content {$contentId}");
                    }
                } catch (Exception $e) {
                    error_log("[SCORM] Error finding SCORM package for content {$contentId}: " . $e->getMessage());
                }
            }
            
            // If still no SCORM package, create a fallback
            if (!$scormPackage) {
                error_log("[SCORM] Using fallback SCORM package for content {$contentId}");
                $scormPackage = [
                    'title' => 'SCORM Content',
                    'launch_path' => "/Unlockyourskills/uploads/scorm/content_{$contentId}/index.html"
                ];
            }

            if (!$scormPackage) {
                $this->toastError('SCORM package not found.', '/unlockyourskills/my-courses');
                return;
            }

            // Check if user has access to this course
            $hasAccess = $this->progressTrackingModel->hasCourseAccess($userId, $courseId, $clientId);
            if (!$hasAccess) {
                $this->toastError('Access denied to this course.', '/unlockyourskills/my-courses');
                return;
            }

            // Initialize course progress if needed
            $this->progressTrackingModel->initializeCourseProgress($userId, $courseId, $clientId);

            // Get resume data
            $resumeData = $this->progressTrackingModel->getContentResumePosition($userId, $courseId, $contentId, $clientId);

                                // Prepare data for the launcher
                    $launcherData = [
                        'course_id' => $courseId,
                        'module_id' => $moduleId,
                        'content_id' => $contentId,
                        'scorm_url' => $this->buildScormUrl($scormPackage),
                        'title' => $scormPackage['title'],
                        'resume_data' => $resumeData
                    ];

            // Log SCORM launch
            error_log("[SCORM] User {$userId} launching SCORM content: " . json_encode($launcherData));

            // Include the SCORM launcher view
            include __DIR__ . '/../views/scorm_launcher.php';

        } catch (Exception $e) {
            error_log("[SCORM] Error launching SCORM content: " . $e->getMessage());
            $this->toastError('Failed to launch SCORM content: ' . $e->getMessage(), '/unlockyourskills/my-courses');
        }
    }

    

    /**
     * Build the full SCORM URL from package data
     */
    private function buildScormUrl($scormPackage)
    {
        // Extract the package name from zip_file (remove .zip extension)
        $packageName = str_replace('.zip', '', $scormPackage['zip_file']);
        
        // Build the full path to the extracted SCORM content
        $basePath = "/Unlockyourskills/uploads/scorm/{$packageName}";
        $launchPath = $scormPackage['launch_path'];
        
        // If launch_path is relative, combine with base path
        if (!preg_match('/^https?:\/\//', $launchPath)) {
            $fullUrl = $basePath . '/' . ltrim($launchPath, '/');
        } else {
            $fullUrl = $launchPath;
        }
        
        error_log("[SCORM] Built SCORM URL: {$fullUrl} from package: " . json_encode($scormPackage));
        
        return $fullUrl;
    }

    /**
     * Get SCORM progress data
     * POST /scorm/progress
     */
    public function getProgress()
    {
        try {
            // Validate request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }

            $userId = $_SESSION['user']['id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $courseId = $_POST['course_id'] ?? null;
            $contentId = $_POST['content_id'] ?? null;

            if (!$userId || !$clientId || !$courseId || !$contentId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                return;
            }

            // Get progress data
            $progress = $this->progressTrackingModel->getContentProgress($userId, $courseId, $contentId, 'scorm', $clientId);

            echo json_encode([
                'success' => true,
                'data' => $progress
            ]);

        } catch (Exception $e) {
            error_log("[SCORM] Error getting progress: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    /**
     * Update SCORM progress
     * POST /scorm/update
     */
    public function updateProgress()
    {
        try {
            // Validate request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }

            $userId = $_SESSION['user']['id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $courseId = $_POST['course_id'] ?? null;
            $contentId = $_POST['content_id'] ?? null;
            $progressData = $_POST['progress_data'] ?? null;

            if (!$userId || !$clientId || !$courseId || !$contentId || !$progressData) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                return;
            }

            // Parse progress data
            if (is_string($progressData)) {
                $progressData = json_decode($progressData, true);
            }

            if (!$progressData) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid progress data']);
                return;
            }

            // Update progress using the specific SCORM method
            $result = $this->progressTrackingModel->updateScormProgress(
                $userId,
                $courseId,
                $contentId,
                $clientId,
                $progressData
            );

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Progress updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to update progress'
                ]);
            }

        } catch (Exception $e) {
            error_log("[SCORM] Error updating progress: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    /**
     * Complete SCORM content
     * POST /scorm/complete
     */
    public function complete()
    {
        try {
            error_log("[SCORM] Complete endpoint called");
            error_log("[SCORM] Complete endpoint - POST data: " . json_encode($_POST));
            
            // Validate request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }

            $userId = $_SESSION['user']['id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $contentId = $_POST['content_id'] ?? null;
            $courseId = $_POST['course_id'] ?? null;
            $moduleId = $_POST['module_id'] ?? null;
            
            error_log("[SCORM] Complete endpoint - Parameters: userId=$userId, clientId=$clientId, contentId=$contentId, courseId=$courseId, moduleId=$moduleId");

            if (!$userId || !$clientId || !$contentId || !$courseId || !$moduleId) {
                error_log("[SCORM] Complete endpoint - Missing required parameters");
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                return;
            }

            // Mark content as complete
            $updateData = [
                'lesson_status' => 'completed'
            ];
            
            error_log("[SCORM] Complete endpoint - calling updateScormProgress with data: " . json_encode($updateData));
            
            $result = $this->progressTrackingModel->updateScormProgress(
                $userId,
                $courseId,
                $contentId,
                $clientId,
                $updateData
            );
            
            error_log("[SCORM] Complete endpoint - updateScormProgress result: " . ($result ? 'SUCCESS' : 'FAILED'));

            if ($result) {
                // Update module and course progress
                $this->progressTrackingModel->updateModuleProgress($userId, $courseId, $moduleId, $clientId, [
                    'status' => 'completed',
                    'completion_percentage' => 100.0
                ]);
                $this->progressTrackingModel->updateCourseProgress($userId, $courseId, $clientId, [
                    'status' => 'in_progress',
                    'completion_percentage' => 100.0
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Content completed successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to complete content'
                ]);
            }

        } catch (Exception $e) {
            error_log("[SCORM] Error completing content: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    /**
     * Get SCORM resume data
     * GET /scorm/resume
     */
    public function getResume()
    {
        try {
            // Validate request
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Missing required parameters']);
                return;
            }

            $userId = $_SESSION['user']['id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $courseId = $_GET['course_id'] ?? null;
            $contentId = $_GET['content_id'] ?? null;

            if (!$userId || !$clientId || !$courseId || !$contentId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                return;
            }

            // Get resume data
            $resumeData = $this->progressTrackingModel->getContentResumePosition($userId, $courseId, $contentId, $clientId);

            echo json_encode([
                'success' => true,
                'data' => $resumeData
            ]);

        } catch (Exception $e) {
            error_log("[SCORM] Error getting resume data: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }
}
