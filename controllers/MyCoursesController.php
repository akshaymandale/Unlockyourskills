<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'models/MyCoursesModel.php';
require_once 'core/UrlHelper.php';
require_once 'config/Localization.php';
require_once 'core/IdEncryption.php';

class MyCoursesController {
    private $myCoursesModel;

    public function __construct() {
        $this->myCoursesModel = new MyCoursesModel();
    }

    // Render My Courses page
    public function index() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }
        require 'views/my_courses.php';
    }

    // AJAX: Get user courses (with status, search, pagination)
    public function getUserCourses() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = intval($_GET['per_page'] ?? 12);
        
        if (!$clientId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit;
        }
        
        $courses = $this->myCoursesModel->getUserCourses($userId, $status, $search, $page, $perPage, $clientId);
        
        // Add encrypted IDs for secure URLs
        if (is_array($courses)) {
            foreach ($courses as &$course) {
                if (isset($course['id'])) {
                    $course['encrypted_id'] = IdEncryption::encrypt($course['id']);
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'courses' => $courses]);
        exit;
    }

    // AJAX: Get total count of user courses (for pagination)
    public function getUserCoursesCount() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        
        if (!$clientId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit;
        }
        
        $totalCount = $this->myCoursesModel->getUserCoursesCount($userId, $status, $search, $clientId);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'total' => $totalCount]);
        exit;
    }

    // Render Course Details page
    public function details($id) {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }
        $courseId = IdEncryption::getId($id);
        if (!$courseId) {
            UrlHelper::redirect('my-courses');
        }
        require_once 'models/CourseModel.php';
        $courseModel = new CourseModel();
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $userId = $_SESSION['user']['id'] ?? null;
        $course = $courseModel->getCourseById($courseId, $clientId, $userId);
        if (!$course) {
            UrlHelper::redirect('my-courses');
        }

        // Load assessment attempts data, assessment details, and results for this user
        $assessmentAttempts = [];
        $assessmentDetails = [];
        $assessmentResults = [];
        if (!empty($course['modules']) || !empty($course['prerequisites']) || !empty($course['post_requisites'])) {
            require_once 'models/AssessmentPlayerModel.php';
            $assessmentModel = new AssessmentPlayerModel();
            
            // Check attempts for each assessment in modules
            if (!empty($course['modules'])) {
                foreach ($course['modules'] as &$module) {
                    if (!empty($module['content'])) {
                        // Calculate real-time module progress
                        $moduleProgress = $courseModel->getModuleContentProgress($module['id'], $userId, $clientId);
                        $module['real_progress'] = $moduleProgress['progress_percentage'];
                        $module['completed_items'] = $moduleProgress['completed_items'];
                        $module['total_items'] = $moduleProgress['total_items'];
                        
                        // Update content with progress information
                        $module['content'] = $moduleProgress['content_progress'];
                        
                        foreach ($module['content'] as $content) {
                            if ($content['content_type'] === 'assessment') {
                                $assessmentId = $content['content_id'];
                                $attempts = $assessmentModel->getUserCompletedAssessmentAttempts($assessmentId, $userId, $clientId);
                                $assessmentAttempts[$assessmentId] = $attempts;
                                
                                // Get assessment details including num_attempts
                                $assessmentDetails[$assessmentId] = $assessmentModel->getAssessmentDetails($assessmentId, $clientId);
                                
                                // Get assessment results (pass/fail status)
                                $assessmentResults[$assessmentId] = $assessmentModel->getUserAssessmentResults($assessmentId, $userId, $clientId);
                            }
                        }
                    }
                }
                // Important: break the reference to avoid unintended carry-over between iterations
                unset($module);
            }
            
            // Check attempts for each assessment in prerequisites
            if (!empty($course['prerequisites'])) {
                foreach ($course['prerequisites'] as $pre) {
                    if ($pre['prerequisite_type'] === 'assessment') {
                        $assessmentId = $pre['prerequisite_id'];
                        $attempts = $assessmentModel->getUserCompletedAssessmentAttempts($assessmentId, $userId, $clientId);
                        $assessmentAttempts[$assessmentId] = $attempts;
                        
                        // Get assessment details including num_attempts
                        $assessmentDetails[$assessmentId] = $assessmentModel->getAssessmentDetails($assessmentId, $clientId);
                        
                        // Get assessment results (pass/fail status)
                        $assessmentResults[$assessmentId] = $assessmentModel->getUserAssessmentResults($assessmentId, $userId, $clientId);
                    }
                }
            }
            
            // Check attempts for each assessment in post-requisites
            if (!empty($course['post_requisites'])) {
                foreach ($course['post_requisites'] as $post) {
                    if ($post['content_type'] === 'assessment') {
                        $assessmentId = $post['content_id'];
                        $attempts = $assessmentModel->getUserCompletedAssessmentAttempts($assessmentId, $userId, $clientId);
                        $assessmentAttempts[$assessmentId] = $attempts;
                        
                        // Get assessment details including num_attempts
                        $assessmentDetails[$assessmentId] = $assessmentModel->getAssessmentDetails($assessmentId, $clientId);
                        
                        // Get assessment results (pass/fail status)
                        $assessmentResults[$assessmentId] = $assessmentModel->getUserAssessmentResults($assessmentId, $userId, $clientId);
                    }
                }
            }
        }

        // Expose assessment attempts data, details, and results to view
        $GLOBALS['assessmentAttempts'] = $assessmentAttempts;
        $GLOBALS['assessmentDetails'] = $assessmentDetails;
        $GLOBALS['assessmentResults'] = $assessmentResults;
        
        // Expose course data to view
        $GLOBALS['course'] = $course;
        
        require 'views/my_course_details.php';
    }

    // API endpoint for module progress
    public function getModuleProgress() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        
        // Get request parameters
        $moduleId = $_GET['module_id'] ?? null;
        $courseId = $_GET['course_id'] ?? null;

        if (!$moduleId || !$courseId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            exit;
        }

        try {
            error_log("Module progress API called - Module: $moduleId, Course: $courseId, User: $userId, Client: $clientId");
            
            require_once 'models/CourseModel.php';
            $courseModel = new CourseModel();
            
            // Get module progress data
            $moduleProgress = $courseModel->getModuleContentProgress($moduleId, $userId, $clientId);
            error_log("Module progress data retrieved: " . json_encode($moduleProgress));
            
            // Verify the module belongs to the specified course
            $course = $courseModel->getCourseById($courseId, $clientId, $userId);
            if (!$course) {
                error_log("Course not found: $courseId");
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Course not found']);
                exit;
            }
            
            // Check if module exists in this course
            $moduleExists = false;
            foreach ($course['modules'] ?? [] as $module) {
                if ($module['id'] == $moduleId) {
                    $moduleExists = true;
                    break;
                }
            }
            
            if (!$moduleExists) {
                error_log("Module $moduleId not found in course $courseId");
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Module not found in course']);
                exit;
            }
            
            error_log("Module progress API success - returning data");
            
            // Return success response
            echo json_encode([
                'success' => true,
                'data' => $moduleProgress,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            error_log("Module progress API error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'error' => 'Internal server error',
                'debug' => $e->getMessage()
            ]);
        }
    }

    // Standard content viewer in a new tab (iframe page)
    public function viewContent() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }
        $type = $_GET['type'] ?? 'iframe';
        $rawSrc = $_GET['src'] ?? '';
        $title = $_GET['title'] ?? 'Content';
        
        // Handle video content differently
        if ($type === 'video') {
            $courseId = $_GET['course_id'] ?? null;
            $moduleId = $_GET['module_id'] ?? null;
            $contentId = $_GET['content_id'] ?? null;
            $videoPackageId = $_GET['video_package_id'] ?? null;
            $clientId = $_GET['client_id'] ?? null;
            
            // Validate required parameters
            if (!$courseId || !$moduleId || !$contentId || !$videoPackageId || !$clientId) {
                UrlHelper::redirect('my-courses');
            }
            
            // Get video file path from video_package table
            require_once 'models/VideoProgressModel.php';
            $videoModel = new VideoProgressModel();
            $videoPackage = $videoModel->getVideoPackageById($videoPackageId);
            
            if (!$videoPackage) {
                UrlHelper::redirect('my-courses');
            }
            
            // Construct proper video URL
            $videoFileName = $videoPackage['video_file'];
            $src = '/Unlockyourskills/uploads/video/' . $videoFileName;
            
            // Expose additional data for video content
            $GLOBALS['course_id'] = $courseId;
            $GLOBALS['module_id'] = $moduleId;
            $GLOBALS['content_id'] = $contentId;
            $GLOBALS['video_package_id'] = $videoPackageId;
            $GLOBALS['client_id'] = $clientId;
        } else {
            $src = $this->normalizeEmbedUrl($rawSrc, $type);
        }
        
        // Expose $type and $src to view
        $GLOBALS['type'] = $type;
        $GLOBALS['src'] = $src;
        $GLOBALS['title'] = $title;
        
        // Set local variables for the template
        $type = $type;
        $src = $src;
        $title = $title;
        

        
        require 'views/content_viewer.php';
    }
    
    /**
     * Helper to resolve URLs for content paths
     */
    private function resolveContentUrl($path) {
        if (empty($path)) {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        if ($path[0] === '/') {
            return $path;
        }
        return UrlHelper::url($path);
    }

    // Start an assessment/survey/feedback/assignment in a new tab
    public function start() {
        // ===== DEBUGGING START =====
        error_log("=== MyCoursesController::start() DEBUG START ===");
        error_log("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
        error_log("Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'unknown'));
        error_log("Session ID: " . session_id());
        error_log("Session Status: " . session_status());
        error_log("Session data: " . print_r($_SESSION, true));
        error_log("GET data: " . print_r($_GET, true));
        error_log("User ID in session: " . ($_SESSION['user']['id'] ?? 'NOT SET'));
        error_log("Client ID in session: " . ($_SESSION['user']['client_id'] ?? 'NOT SET'));
        error_log("Current time: " . time());
        error_log("Last activity: " . ($_SESSION['last_activity'] ?? 'NOT SET'));
        
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            error_log("ERROR: User not authenticated, redirecting to login");
            error_log("Session ID: " . ($_SESSION['id'] ?? 'NOT SET'));
            error_log("Session user: " . (isset($_SESSION['user']) ? 'SET' : 'NOT SET'));
            UrlHelper::redirect('login');
        }
        
        error_log("User authentication check PASSED");
        // ===== DEBUGGING END =====
        
        $type = $_GET['type'] ?? '';
        $id = isset($_GET['id']) ? IdEncryption::getId($_GET['id']) : 0;
        
        if (!$type || !$id) {
            UrlHelper::redirect('my-courses');
        }
        require_once 'models/VLRModel.php';
        $vlr = new VLRModel();
        $payload = null;
        switch ($type) {
            case 'assessment':
                error_log("=== Processing Assessment Case ===");
                error_log("Assessment ID: {$id}");
                
                // Load assessment data and use the new assessment player view
                $payload = $vlr->getAssessmentByIdWithQuestions($id);
                error_log("Assessment payload: " . print_r($payload, true));
                
                if ($payload) {
                    error_log("Assessment data loaded successfully");
                    
                    // For assessments, we need to create an attempt and handle user permissions
                    // Load the AssessmentPlayerModel to handle this properly
                    require_once 'models/AssessmentPlayerModel.php';
                    $assessmentModel = new AssessmentPlayerModel();
                    error_log("AssessmentPlayerModel loaded successfully");
                    
                    $userId = $_SESSION['user']['id'];
                    $clientId = $_SESSION['user']['client_id'] ?? null;
                    error_log("User ID: {$userId}, Client ID: {$clientId}");
                    
                    // Check if user can take this assessment
                    error_log("Checking if user can take assessment...");
                    if (!$assessmentModel->canUserTakeAssessment($id, $userId, $clientId)) {
                        error_log("ERROR: User cannot take this assessment, redirecting to my-courses");
                        UrlHelper::redirect('my-courses');
                        return;
                    }
                    error_log("User can take assessment");
                    
                    // Create or get existing attempt
                    error_log("Creating/getting assessment attempt...");
                    $attemptId = $assessmentModel->createOrGetAttempt($id, $userId, $clientId);
                    error_log("Attempt ID: {$attemptId}");
                    
                    // Get current attempt data
                    $attempt = $assessmentModel->getAttempt($attemptId);
                    error_log("Attempt data: " . print_r($attempt, true));
                    
                    // Expose data to view (same structure as AssessmentPlayerController)
                    $GLOBALS['assessment'] = $payload;
                    $GLOBALS['attempt'] = $attempt;
                    $GLOBALS['attemptId'] = $attemptId;
                    error_log("Data exposed to view, loading assessment_player.php");
                    
                    require 'views/assessment_player.php';
                    error_log("Assessment player view loaded successfully");
                    return; // Exit early to prevent loading activity_player.php
                } else {
                    error_log("ERROR: Failed to load assessment data");
                }
                break;
            case 'survey':
                $payload = $vlr->getSurveyByIdWithQuestions($id, $_SESSION['user']['client_id'] ?? null);
                break;
            case 'feedback':
                $payload = $vlr->getFeedbackByIdWithQuestions($id);
                break;
            case 'assignment':
                // Basic details only for now
                $payload = $this->myCoursesModel; // placeholder to avoid undefined; real fetch not implemented
                $payload = ['id' => $id, 'title' => 'Assignment'];
                break;
            default:
                UrlHelper::redirect('my-courses');
        }
        
        // Load activity_player.php for non-assessment types
        error_log("=== Loading activity_player.php for type: {$type} ===");
        $activityType = $type;
        $activity = $payload;
        require 'views/activity_player.php';
        error_log("=== activity_player.php loaded successfully ===");
    }

    // Debug method for testing API routing
    public function debug() {
        echo json_encode([
            'success' => true,
            'message' => 'Debug route working',
            'session' => [
                'user_id' => $_SESSION['user']['id'] ?? 'not_set',
                'client_id' => $_SESSION['user']['client_id'] ?? 'not_set',
                'session_id' => session_id()
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    private function normalizeEmbedUrl($url, $type) {
        if (empty($url)) return '';
        // If absolute http(s), possibly transform for YouTube/Vimeo
        if (preg_match('#^https?://#i', $url)) {
            // YouTube
            if (strpos($url, 'youtube.com/watch') !== false || strpos($url, 'youtu.be/') !== false) {
                // Extract video id
                $videoId = null;
                if (preg_match('#youtu\.be/([^?&/]+)#', $url, $m)) {
                    $videoId = $m[1];
                } elseif (preg_match('#v=([^&]+)#', $url, $m)) {
                    $videoId = $m[1];
                }
                if ($videoId) {
                    return 'https://www.youtube.com/embed/' . $videoId . '?rel=0&modestbranding=1';
                }
            }
            // Vimeo
            if (preg_match('#vimeo\.com/(\d+)#', $url, $m)) {
                return 'https://player.vimeo.com/video/' . $m[1];
            }
            return $url;
        }
        // Site-root absolute path
        if ($url[0] === '/') {
            return $url;
        }
        // Relative path within project
        return UrlHelper::url($url);
    }
} 