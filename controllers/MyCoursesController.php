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
        
        try {
            $courses = $this->myCoursesModel->getUserCourses($userId, $status, $search, $page, $perPage, $clientId);
            
            // Add encrypted IDs for secure URLs
            if (is_array($courses)) {
                foreach ($courses as &$course) {
                    if (isset($course['id'])) {
                        $course['encrypted_id'] = IdEncryption::encrypt($course['id']);
                    }
                }
            }
        } catch (Exception $e) {
            error_log("MyCoursesController: Error getting courses: " . $e->getMessage());
            error_log("MyCoursesController: Stack trace: " . $e->getTraceAsString());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error loading courses: ' . $e->getMessage()]);
            exit;
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
            // Add debugging output for AJAX requests
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Session validation failed',
                    'session_id' => session_id(),
                    'session_data' => $_SESSION,
                    'redirect' => UrlHelper::url('login')
                ]);
                exit;
            }
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
                        $moduleProgress = $courseModel->getModuleContentProgress($module['id'], $userId, $clientId, $courseId);
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

        // Note: Completion tracking is now handled only when content is actually completed
        // No initialization on course page load
        
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
            $moduleProgress = $courseModel->getModuleContentProgress($moduleId, $userId, $clientId, $courseId);
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
        
        // Handle Interactive AI content with enhanced functionality
        if ($type === 'interactive' || ($type === 'iframe' && strpos($rawSrc, 'interactive') !== false)) {
            $contentId = $_GET['content_id'] ?? null;
            $courseId = $_GET['course_id'] ?? null;
            $moduleId = $_GET['module_id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            
            if (!$contentId || !$courseId || !$clientId) {
                UrlHelper::redirect('my-courses');
            }
            
            // Use enhanced Interactive AI content viewer
            require_once 'controllers/InteractiveAIController.php';
            $interactiveController = new InteractiveAIController();
            $interactiveController->viewInteractiveContent();
            return;
        }
        
        // Handle video content differently
        if ($type === 'video') {
            $courseId = $_GET['course_id'] ?? null;
            $moduleId = $_GET['module_id'] ?? null;
            $contentId = $_GET['content_id'] ?? null;
            $videoPackageId = $_GET['video_package_id'] ?? null;
            $clientId = $_GET['client_id'] ?? null;
            $prerequisiteId = $_GET['prerequisite_id'] ?? null;
            
            // For prerequisites, use prerequisite_id as content_id if not provided
            if ($prerequisiteId && !$contentId) {
                $contentId = $prerequisiteId;
            }
            
            // Get video package ID if not provided
            if (!$videoPackageId && $contentId) {
                require_once 'config/Database.php';
                $database = new Database();
                $conn = $database->connect();
                
                // Check if this is a prerequisite or module content
                if ($prerequisiteId) {
                    // For prerequisites, get video package ID from course_prerequisites
                    $sql = "SELECT prerequisite_id as video_package_id FROM course_prerequisites 
                            WHERE id = ? AND prerequisite_type = 'video'";
                } else {
                    // For module content, get video package ID from course_module_content
                    $sql = "SELECT content_id as video_package_id FROM course_module_content 
                            WHERE id = ? AND content_type = 'video'";
                }
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([$contentId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $videoPackageId = $result['video_package_id'];
                }
            }
            
            // Validate required parameters - module_id is optional for prerequisites
            if (!$courseId || !$contentId || !$videoPackageId || !$clientId) {
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
            $prerequisiteId = $_GET['prerequisite_id'] ?? null;
            
            // Validate required parameters
            if (!$courseId || !$imagePackageId || !$clientId) {
                UrlHelper::redirect('my-courses');
            }
            
            // Determine context: prerequisite vs module
            if ($prerequisiteId) {
                // This is a prerequisite - don't use content_id, use prerequisite_id
                $contentId = null;
                $moduleId = null;
            } else {
                // This is module content - prerequisite_id should not be set
                $prerequisiteId = null;
                // For module content, module_id and content_id are required
                if (!$moduleId || !$contentId) {
                    UrlHelper::redirect('my-courses');
                }
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
            $GLOBALS['prerequisite_id'] = $prerequisiteId;
        } elseif ($type === 'audio') {
            $courseId = $_GET['course_id'] ?? null;
            $moduleId = $_GET['module_id'] ?? null;
            $contentId = $_GET['content_id'] ?? null;
            $audioPackageId = $_GET['audio_package_id'] ?? null;
            $clientId = $_GET['client_id'] ?? null;
            $prerequisiteId = $_GET['prerequisite_id'] ?? null;
            
            // For prerequisites, use prerequisite_id as content_id if not provided
            if ($prerequisiteId && !$contentId) {
                $contentId = $prerequisiteId;
            }
            
            // Get audio package ID if not provided
            if (!$audioPackageId && $contentId) {
                require_once 'config/Database.php';
                $database = new Database();
                $conn = $database->connect();
                
                // Check if this is a prerequisite or module content
                if ($prerequisiteId) {
                    // For prerequisites, get audio package ID from course_prerequisites
                    $sql = "SELECT prerequisite_id as audio_package_id FROM course_prerequisites 
                            WHERE id = ? AND prerequisite_type = 'audio'";
                } else {
                    // For module content, get audio package ID from course_module_content
                    $sql = "SELECT content_id as audio_package_id FROM course_module_content 
                            WHERE id = ? AND content_type = 'audio'";
                }
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([$contentId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $audioPackageId = $result['audio_package_id'];
                }
            }
            
            // Validate required parameters
            if (!$courseId || !$contentId || !$audioPackageId || !$clientId) {
                UrlHelper::redirect('my-courses');
            }
            
            // Get audio file path from audio_package table
            require_once 'models/AudioProgressModel.php';
            $audioModel = new AudioProgressModel();
            $audioPackage = $audioModel->getAudioPackageById($audioPackageId);
            
            if (!$audioPackage) {
                UrlHelper::redirect('my-courses');
            }
            
            // Construct proper audio URL
            $audioFileName = $audioPackage['audio_file'];
            $src = '/Unlockyourskills/uploads/audio/' . $audioFileName;
            
            // Expose additional data for audio content
            $GLOBALS['course_id'] = $courseId;
            $GLOBALS['module_id'] = $moduleId;
            $GLOBALS['content_id'] = $contentId;
            $GLOBALS['audio_package_id'] = $audioPackageId;
            $GLOBALS['client_id'] = $clientId;
        } elseif ($type === 'external') {
            $courseId = $_GET['course_id'] ?? null;
            $moduleId = $_GET['module_id'] ?? null;
            $contentId = $_GET['content_id'] ?? null;
            $externalPackageId = $_GET['external_package_id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $prerequisiteId = $_GET['prerequisite_id'] ?? null;
            
            // Validate required parameters
            if (!$courseId || !$externalPackageId || !$clientId) {
                UrlHelper::redirect('my-courses');
            }
            
            // Determine context: prerequisite vs module
            if ($prerequisiteId) {
                // This is a prerequisite - don't use content_id, use prerequisite_id
                $contentId = null;
                $moduleId = null;
            } else {
                // This is module content - prerequisite_id should not be set
                $prerequisiteId = null;
                // For module content, module_id and content_id are required
                if (!$moduleId || !$contentId) {
                    UrlHelper::redirect('my-courses');
                }
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
            $GLOBALS['prerequisite_id'] = $prerequisiteId;
            $GLOBALS['external_package_id'] = $externalPackageId;
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
                    
                    // Note: Prerequisite tracking is now handled only when assessment is completed
                    // This prevents creating completion records when user just opens the assessment preview
                    
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
            case 'audio':
                // Handle audio prerequisites - redirect to viewContent with proper parameters
                $courseId = $_GET['course_id'] ?? null;
                $clientId = $_SESSION['user']['client_id'] ?? null;
                
                if (!$courseId || !$clientId) {
                    UrlHelper::redirect('my-courses');
                }
                
                // Get audio package data
                require_once 'models/AudioProgressModel.php';
                $audioModel = new AudioProgressModel();
                $audioPackage = $audioModel->getAudioPackageById($id);
                
                if (!$audioPackage) {
                    UrlHelper::redirect('my-courses');
                }
                
                // Construct audio file path
                $audioFileName = $audioPackage['audio_file'];
                $audioSrc = '/Unlockyourskills/uploads/audio/' . $audioFileName;
                
                // Redirect to viewContent with proper parameters
                $redirectUrl = UrlHelper::url('my-courses/view-content') . '?' . http_build_query([
                    'type' => 'audio',
                    'title' => $audioPackage['title'],
                    'src' => $audioSrc,
                    'course_id' => $courseId,
                    'content_id' => $id, // This is the prerequisite ID for prerequisites
                    'audio_package_id' => $id,
                    'prerequisite_id' => $id,
                    'client_id' => $clientId
                ]);
                
                header('Location: ' . $redirectUrl);
                exit;
            case 'video':
                // Handle video prerequisites - redirect to viewContent with proper parameters
                $courseId = $_GET['course_id'] ?? null;
                $clientId = $_SESSION['user']['client_id'] ?? null;
                
                if (!$courseId || !$clientId) {
                    UrlHelper::redirect('my-courses');
                }
                
                // Get video package data
                require_once 'models/VideoProgressModel.php';
                $videoModel = new VideoProgressModel();
                $videoPackage = $videoModel->getVideoPackageById($id);
                
                if (!$videoPackage) {
                    UrlHelper::redirect('my-courses');
                }
                
                // Construct video file path
                $videoFileName = $videoPackage['video_file'];
                $videoSrc = '/Unlockyourskills/uploads/video/' . $videoFileName;
                
                // Redirect to viewContent with proper parameters
                $redirectUrl = UrlHelper::url('my-courses/view-content') . '?' . http_build_query([
                    'type' => 'video',
                    'title' => $videoPackage['title'],
                    'src' => $videoSrc,
                    'course_id' => $courseId,
                    'content_id' => $id, // This is the prerequisite ID for prerequisites
                    'video_package_id' => $id,
                    'prerequisite_id' => $id,
                    'client_id' => $clientId
                ]);
                
                header('Location: ' . $redirectUrl);
                exit;
            case 'image':
                // Handle image prerequisites - redirect to viewContent with proper parameters
                $courseId = $_GET['course_id'] ?? null;
                $clientId = $_SESSION['user']['client_id'] ?? null;
                
                if (!$courseId || !$clientId) {
                    UrlHelper::redirect('my-courses');
                }
                
                // Get the course_prerequisites.id for this prerequisite
                require_once 'config/Database.php';
                $database = new Database();
                $conn = $database->connect();
                
                $stmt = $conn->prepare("
                    SELECT id FROM course_prerequisites 
                    WHERE course_id = ? AND prerequisite_id = ? AND prerequisite_type = 'image'
                ");
                $stmt->execute([$courseId, $id]);
                $prereqResult = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$prereqResult) {
                    UrlHelper::redirect('my-courses');
                }
                
                // Get image package data
                require_once 'models/ImageProgressModel.php';
                $imageModel = new ImageProgressModel();
                $imagePackage = $imageModel->getImagePackageById($id);
                
                if (!$imagePackage) {
                    UrlHelper::redirect('my-courses');
                }
                
                // Construct image file path
                $imageFileName = $imagePackage['image_file'];
                $imageSrc = '/Unlockyourskills/uploads/image/' . $imageFileName;
                
                // Redirect to viewContent with proper parameters
                $redirectUrl = UrlHelper::url('my-courses/view-content') . '?' . http_build_query([
                    'type' => 'image',
                    'title' => $imagePackage['title'],
                    'src' => $imageSrc,
                    'course_id' => $courseId,
                    'content_id' => $prereqResult['id'], // This is the course_prerequisites.id
                    'image_package_id' => $id, // This is the actual image package ID
                    'prerequisite_id' => $prereqResult['id'], // This is the course_prerequisites.id
                    'client_id' => $clientId
                ]);
                
                header('Location: ' . $redirectUrl);
                exit;
            case 'document':
                // Handle document prerequisites - redirect to viewContent with proper parameters
                $courseId = $_GET['course_id'] ?? null;
                $clientId = $_SESSION['user']['client_id'] ?? null;
                
                if (!$courseId || !$clientId) {
                    UrlHelper::redirect('my-courses');
                }
                
                // Get document package data
                require_once 'models/DocumentProgressModel.php';
                $documentModel = new DocumentProgressModel();
                $documentPackage = $documentModel->getDocumentPackageById($id);
                
                if (!$documentPackage) {
                    UrlHelper::redirect('my-courses');
                }
                
                // Construct document file path
                $documentFileName = $documentPackage['word_excel_ppt_file'] ?? $documentPackage['ebook_manual_file'] ?? $documentPackage['research_file'] ?? '';
                $documentSrc = '/Unlockyourskills/uploads/documents/' . $documentFileName;
                
                // Get the course_prerequisites.id for this prerequisite
                require_once 'config/Database.php';
                $database = new Database();
                $conn = $database->connect();
                
                $stmt = $conn->prepare("
                    SELECT id FROM course_prerequisites 
                    WHERE course_id = ? AND prerequisite_id = ? AND prerequisite_type = 'document'
                ");
                $stmt->execute([$courseId, $id]);
                $prereqResult = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$prereqResult) {
                    UrlHelper::redirect('my-courses');
                }
                
                // Redirect to viewContent with proper parameters
                $redirectUrl = UrlHelper::url('my-courses/view-content') . '?' . http_build_query([
                    'type' => 'document',
                    'title' => $documentPackage['title'],
                    'src' => $documentSrc,
                    'course_id' => $courseId,
                    'prerequisite_id' => $prereqResult['id'], // This is the course_prerequisites.id
                    'document_package_id' => $id,
                    'client_id' => $clientId
                ]);
                
                header('Location: ' . $redirectUrl);
                exit;
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
        
        // Special handling for external content with uploaded audio files
        if ($type === 'external' && strpos($url, 'uploads/external/') === 0 && !strpos($url, 'uploads/external/audio/')) {
            // Check if this is an audio file by extension
            $audioExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac'];
            $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
            
            if (in_array($extension, $audioExtensions)) {
                // Fix the path to include /audio/ directory
                $filename = basename($url);
                $correctPath = 'uploads/external/audio/' . $filename;
                
                // Check if the file exists in the correct location
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/Unlockyourskills/' . $correctPath;
                if (file_exists($fullPath)) {
                    error_log("Fixed external audio path: $url -> $correctPath");
                    return UrlHelper::url($correctPath);
                } else {
                    error_log("Audio file not found at corrected path: $fullPath");
                }
            }
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