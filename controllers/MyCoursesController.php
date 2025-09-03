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

        // Handle survey submission if present in GET parameters
        $surveySubmitted = false;
        if (isset($_GET['responses']) && isset($_GET['course_id']) && isset($_GET['survey_package_id'])) {
            $this->handleSurveySubmission();
            $surveySubmitted = true;
            // Don't return - continue processing the page
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
                                // For module content, only count attempts from THIS course
                                $attempts = $assessmentModel->getUserCompletedAssessmentAttemptsForCourse($assessmentId, $userId, $course['id'], $clientId);
                                $assessmentAttempts[$assessmentId] = $attempts;
                                
                                // Get assessment details including num_attempts
                                $assessmentDetails[$assessmentId] = $assessmentModel->getAssessmentDetails($assessmentId, $clientId);
                                
                                // Get assessment results (pass/fail status) for this specific course
                                $assessmentResults[$assessmentId] = $assessmentModel->getUserAssessmentResults($assessmentId, $userId, $clientId, $course['id']);
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
                        // For prerequisites, only count attempts from THIS course
                        $attempts = $assessmentModel->getUserCompletedAssessmentAttemptsForCourse($assessmentId, $userId, $course['id'], $clientId);
                        $assessmentAttempts[$assessmentId] = $attempts;
                        
                        // Get assessment details including num_attempts
                        $assessmentDetails[$assessmentId] = $assessmentModel->getAssessmentDetails($assessmentId, $clientId);
                        
                        // Get assessment results (pass/fail status) for this specific course
                        $assessmentResults[$assessmentId] = $assessmentModel->getUserAssessmentResults($assessmentId, $userId, $clientId, $course['id']);
                    }
                }
            }
            
            // Check attempts for each assessment in post-requisites
            if (!empty($course['post_requisites'])) {
                foreach ($course['post_requisites'] as $post) {
                    if ($post['content_type'] === 'assessment') {
                        $assessmentId = $post['content_id'];
                        // For post-requisites, only count attempts from THIS course
                        $attempts = $assessmentModel->getUserCompletedAssessmentAttemptsForCourse($assessmentId, $userId, $course['id'], $clientId);
                        $assessmentAttempts[$assessmentId] = $attempts;
                        
                        // Get assessment details including num_attempts
                        $assessmentDetails[$assessmentId] = $assessmentModel->getAssessmentDetails($assessmentId, $clientId);
                        
                        // Get assessment results (pass/fail status) for this specific course
                        $assessmentResults[$assessmentId] = $assessmentModel->getUserAssessmentResults($assessmentId, $userId, $clientId, $course['id']);
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
        } elseif ($type === 'image') {
            $courseId = $_GET['course_id'] ?? null;
            $moduleId = $_GET['module_id'] ?? null;
            $contentId = $_GET['content_id'] ?? null;
            $imagePackageId = $_GET['image_package_id'] ?? null;
            $clientId = $_GET['client_id'] ?? null;
            
            // Validate required parameters
            if (!$courseId || !$moduleId || !$contentId || !$imagePackageId || !$clientId) {
                UrlHelper::redirect('my-courses');
            }
            
            // Get image file path from image_package table
            require_once 'models/ImageProgressModel.php';
            $imageModel = new ImageProgressModel();
            $imagePackage = $imageModel->getImagePackageById($imagePackageId);
            
            if (!$imagePackage) {
                UrlHelper::redirect('my-courses');
            }
            
            // Construct proper image URL
            $imageFileName = $imagePackage['image_file'];
            $src = '/Unlockyourskills/uploads/image/' . $imageFileName;
            
            // Expose additional data for image content
            $GLOBALS['course_id'] = $courseId;
            $GLOBALS['module_id'] = $moduleId;
            $GLOBALS['content_id'] = $contentId;
            $GLOBALS['image_package_id'] = $imagePackageId;
            $GLOBALS['client_id'] = $clientId;
        } elseif ($type === 'external') {
            $courseId = $_GET['course_id'] ?? null;
            $moduleId = $_GET['module_id'] ?? null;
            $contentId = $_GET['content_id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            
            // Validate required parameters for external content
            if (!$courseId || !$moduleId || !$contentId || !$clientId) {
                UrlHelper::redirect('my-courses');
            }
            
            // Process the source URL for external content (convert YouTube to embed, etc.)
            // Ensure proper URL decoding
            $decodedSrc = urldecode($rawSrc);
            $src = $this->normalizeEmbedUrl($decodedSrc, $type);
            
            // Debug logging for URL processing
            error_log("External content URL processing:");
            error_log("  Raw source: $rawSrc");
            error_log("  Decoded source: $decodedSrc");
            error_log("  Processed source: $src");
            error_log("  Type: $type");
            
            // Expose additional data for external content
            $GLOBALS['course_id'] = $courseId;
            $GLOBALS['module_id'] = $moduleId;
            $GLOBALS['content_id'] = $contentId;
            $GLOBALS['client_id'] = $clientId;
        } else {
            $src = $this->normalizeEmbedUrl($rawSrc, $type);
        }
        
        // Ensure all variables are defined
        $type = $type ?? 'iframe';
        $src = $src ?? '';
        $title = $title ?? 'Content';
        
        // Expose $type and $src to view
        $GLOBALS['type'] = $type;
        $GLOBALS['src'] = $src;
        $GLOBALS['title'] = $title;
        
        // Set local variables for the template
        $type = $type;
        $src = $src;
        $title = $title;
        
        // Debug logging for external content
        if ($type === 'external') {
            error_log("External content debug - Type: $type, Src: $src, Title: $title");
            error_log("Course ID: " . ($GLOBALS['course_id'] ?? 'NOT SET'));
            error_log("Module ID: " . ($GLOBALS['module_id'] ?? 'NOT SET'));
            error_log("Content ID: " . ($GLOBALS['content_id'] ?? 'NOT SET'));
            error_log("Client ID: " . ($GLOBALS['client_id'] ?? 'NOT SET'));
        }
        

        
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
                
                // Get course_id from URL parameters FIRST
                $courseId = $_GET['course_id'] ?? null;
                error_log("Course ID from URL: {$courseId}");
                
                if (!$courseId) {
                    error_log("ERROR: No course_id provided, redirecting to my-courses");
                    UrlHelper::redirect('my-courses');
                    return;
                }
                
                // Check if user can take this assessment (course-specific)
                error_log("Checking if user can take assessment...");
                if (!$assessmentModel->canUserTakeAssessment($id, $userId, $clientId, $courseId)) {
                    error_log("ERROR: User cannot take this assessment, redirecting to my-courses");
                    UrlHelper::redirect('my-courses');
                    return;
                }
                error_log("User can take assessment");
                    
                    // Create or get existing attempt with course_id
                    error_log("Creating/getting assessment attempt with course_id: {$courseId}...");
                    $attemptId = $assessmentModel->createOrGetAttempt($id, $userId, $clientId, $courseId);
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
            // YouTube - always convert to embed for external content
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
            
            // For external content type, detect iframe-blocking sites
            if ($type === 'external') {
                // List of known iframe-blocking domains
                $blockingDomains = [
                    'linkedin.com',
                    'udemy.com',
                    'coursera.org',
                    'edx.org',
                    'facebook.com',
                    'twitter.com',
                    'instagram.com',
                    'tiktok.com',
                    'reddit.com',
                    'medium.com',
                    'github.com',
                    'stackoverflow.com',
                    'quora.com',
                    'wikipedia.org'
                ];
                
                $urlDomain = parse_url($url, PHP_URL_HOST);
                if ($urlDomain) {
                    foreach ($blockingDomains as $blockingDomain) {
                        if (strpos($urlDomain, $blockingDomain) !== false) {
                            // Mark this as a potentially blocking site
                            $GLOBALS['iframe_blocking_site'] = true;
                            error_log("Detected potentially iframe-blocking site: $urlDomain");
                            break;
                        }
                    }
                }
                
                return $url;
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

    /**
     * Handle survey submission from GET parameters
     */
    private function handleSurveySubmission() {

        
        try {
            // Get survey submission data from GET parameters
            $courseId = $_GET['course_id'] ?? '';
            $surveyPackageId = $_GET['survey_package_id'] ?? '';
            $responses = $_GET['responses'] ?? [];
            


            if (empty($courseId) || empty($surveyPackageId) || empty($responses)) {

                return;
            }

            // Decrypt IDs
            $courseId = IdEncryption::decrypt($courseId);
            $surveyPackageId = IdEncryption::decrypt($surveyPackageId);

            if (!$courseId || !$surveyPackageId) {

                return;
            }



            // Get the correct client_id from the course data to ensure consistency
            require_once 'models/CourseModel.php';
            $courseModel = new CourseModel();
            $course = $courseModel->getCourseById($courseId);
            
            if (!$course) {

                return;
            }
            
            // Use the course's client_id to ensure data consistency
            $clientId = $course['client_id'];
            $userId = $_SESSION['id'];
            


            // Initialize survey model
            require_once 'models/SurveyResponseModel.php';
            $surveyResponseModel = new SurveyResponseModel();



            // Process each response
            foreach ($responses as $questionId => $responseData) {
                $responseType = $responseData['type'] ?? '';
                $responseValue = $responseData['value'] ?? '';

                // Prepare data for database
                $data = [
                    'client_id' => $clientId,
                    'course_id' => $courseId,
                    'user_id' => $userId,
                    'survey_package_id' => $surveyPackageId,
                    'question_id' => $questionId,
                    'response_type' => $responseType,
                    'rating_value' => null,
                    'text_response' => null,
                    'choice_response' => null,
                    'file_response' => null,
                    'response_data' => null
                ];

                // Map response types to database fields
                switch ($responseType) {
                    case 'rating':
                        $data['rating_value'] = intval($responseValue);
                        break;
                    case 'text':
                        $data['text_response'] = $responseValue;
                        break;
                    case 'choice':
                        $data['choice_response'] = $responseValue;
                        break;
                    case 'file':
                        $data['file_response'] = $responseValue;
                        break;
                    default:
                        $data['response_data'] = json_encode($responseValue);
                        break;
                }

                // Save response
                $surveyResponseModel->saveResponse($data);
            }

            // Set success flag in session instead of redirecting
            $_SESSION['survey_success'] = true;
            error_log("Survey submission completed successfully");
            error_log("Session ID: " . ($_SESSION['id'] ?? 'NOT SET'));
            error_log("Session user: " . (isset($_SESSION['user']) ? 'SET' : 'NOT SET'));

        } catch (Exception $e) {
            // Silent error handling - set error flag
            $_SESSION['survey_error'] = true;
            error_log("Survey submission error: " . $e->getMessage());
        }
    }


} 