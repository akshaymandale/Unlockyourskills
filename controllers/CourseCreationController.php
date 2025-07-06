<?php
require_once 'controllers/BaseController.php';
require_once 'models/CourseModel.php';
require_once 'models/CourseCategoryModel.php';
require_once 'models/CourseSubcategoryModel.php';
require_once 'core/UrlHelper.php';

class CourseCreationController extends BaseController
{
    private $courseModel;
    private $courseCategoryModel;
    private $courseSubcategoryModel;

    public function __construct()
    {
        $this->courseModel = new CourseModel();
        $this->courseCategoryModel = new CourseCategoryModel();
        $this->courseSubcategoryModel = new CourseSubcategoryModel();
    }

    /**
     * Course Creation Page
     */
    public function index() {
        $userId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
        if (!$userId) {
            $this->redirectWithToast('Please login to access course creation.', 'error', '/login');
            return;
        }
        
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $categories = $this->courseCategoryModel->getAllCategories($clientId);
        $vlrContent = $this->courseModel->getAvailableVLRContent($clientId);
        $existingCourses = $this->courseModel->getAllCourses($clientId);
        $currencies = $this->courseModel->getCurrencies();
        
        require 'views/course_creation.php';
    }

    /**
     * Get subcategories for a category (AJAX)
     */
    public function getSubcategories()
    {
        if (!isset($_SESSION['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $categoryId = intval($_POST['category_id'] ?? 0);
        $clientId = $_SESSION['user']['client_id'] ?? null;

        if ($categoryId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid category ID']);
            return;
        }

        $subcategories = $this->courseSubcategoryModel->getSubcategoriesByCategoryId($categoryId, $clientId);

        $this->jsonResponse([
            'success' => true,
            'subcategories' => $subcategories
        ]);
    }

    /**
     * Get VLR content for a specific type (AJAX)
     */
    public function getVLRContent()
    {
        if (!isset($_SESSION['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $contentType = $_POST['content_type'] ?? '';
        $clientId = $_SESSION['user']['client_id'] ?? null;

        $vlrContent = $this->courseModel->getAvailableVLRContent($clientId);

        if (isset($vlrContent[$contentType])) {
            $this->jsonResponse([
                'success' => true,
                'content' => $vlrContent[$contentType]
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid content type'
            ]);
        }
    }

    /**
     * Course Management Page
     */
    public function courseManagement() {
        if (!isset($_SESSION['id'])) {
            $this->redirectWithToast('Please login to access course management.', 'error', '/login');
            return;
        }
        
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $courses = $this->courseModel->getAllCourses($clientId);
        $categories = $this->courseCategoryModel->getAllCategories($clientId);
        $subcategories = $this->courseSubcategoryModel->getAllSubcategories($clientId);
        
        require 'views/course_management.php';
    }

    /**
     * Get all courses for API with search, filtering, and pagination
     */
    public function getCourses() {
        if (!isset($_SESSION['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        try {
            $clientId = $_SESSION['user']['client_id'] ?? null;
            
            // Get search and filter parameters
            $search = $_GET['search'] ?? '';
            $courseStatus = $_GET['course_status'] ?? '';
            $category = $_GET['category'] ?? '';
            $subcategory = $_GET['subcategory'] ?? '';
            $page = max(1, intval($_GET['page'] ?? 1));
            $perPage = 10; // Items per page
            $offset = ($page - 1) * $perPage;
            
            // Build filters array
            $filters = [
                'search' => $search,
                'course_status' => $courseStatus,
                'category' => $category,
                'subcategory' => $subcategory,
                'limit' => $perPage,
                'offset' => $offset
            ];
            
            // Get courses with filters
            $courses = $this->courseModel->getCourses($clientId, $filters);
            
            // Get total count for pagination
            $totalFilters = [
                'search' => $search,
                'course_status' => $courseStatus,
                'category' => $category,
                'subcategory' => $subcategory
            ];
            $totalCourses = $this->courseModel->getCoursesCount($clientId, $totalFilters);
            
            // Calculate pagination info
            $totalPages = ceil($totalCourses / $perPage);
            $filteredCount = count($courses);
            
            $this->jsonResponse([
                'success' => true,
                'courses' => $courses,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $totalCourses,
                    'per_page' => $perPage
                ],
                'total' => $totalCourses,
                'filtered' => $filteredCount
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error loading courses: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Publish a course
     */
    public function publishCourse($courseId) {
        if (!isset($_SESSION['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        try {
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $result = $this->courseModel->updateCourseStatus($courseId, 'active', $clientId);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Course published successfully'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to publish course'
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error publishing course: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Unpublish a course
     */
    public function unpublishCourse($courseId) {
        if (!isset($_SESSION['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        try {
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $result = $this->courseModel->updateCourseStatus($courseId, 'inactive', $clientId);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Course unpublished successfully'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to unpublish course'
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error unpublishing course: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete a course
     */
    public function deleteCourse($courseId) {
        if (!isset($_SESSION['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        try {
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $result = $this->courseModel->deleteCourse($courseId, $clientId);
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Course deleted successfully'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete course'
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error deleting course: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Preview Course Page
     */
    public function previewCourse($courseId) {
        if (!isset($_SESSION['id'])) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Please login to preview courses.',
                    'redirect' => '/Unlockyourskills/login'
                ]);
            } else {
                $this->redirectWithToast('Please login to preview courses.', 'error', '/login');
            }
            return;
        }
        
        try {
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $course = $this->courseModel->getCourseById($courseId, $clientId);
            
            if (!$course) {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse([
                        'success' => false,
                        'message' => 'Course not found.'
                    ]);
                } else {
                    $this->redirectWithToast('Course not found.', 'error', '/Unlockyourskills/course-management');
                }
                return;
            }
            
            // Get course modules and content
            $modules = $this->courseModel->getCourseModules($courseId);
            $prerequisites = $this->courseModel->getCoursePrerequisites($courseId);
            $postRequisites = $this->courseModel->getCoursePostRequisites($courseId);
            
            // Separate post-requisites by type for the view
            $assessments = [];
            $feedback = [];
            $surveys = [];
            $assignments = [];
            
            foreach ($postRequisites as $requisite) {
                switch ($requisite['content_type']) {
                    case 'assessment':
                        $assessments[] = $requisite;
                        break;
                    case 'feedback':
                        $feedback[] = $requisite;
                        break;
                    case 'survey':
                        $surveys[] = $requisite;
                        break;
                    case 'assignment':
                        $assignments[] = $requisite;
                        break;
                }
            }
            
            if ($this->isAjaxRequest()) {
                // For AJAX requests, capture the output and return as JSON
                ob_start();
                require 'views/course_preview.php';
                $html = ob_get_clean();
                
                $this->jsonResponse([
                    'success' => true,
                    'html' => $html
                ]);
            } else {
                // For direct requests, include the view normally
                require 'views/course_preview.php';
            }
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error loading course: ' . $e->getMessage()
                ]);
            } else {
                $this->redirectWithToast('Error loading course.', 'error', '/Unlockyourskills/course-management');
            }
        }
    }

    /**
     * Course Analytics Page
     */
    public function courseAnalytics($courseId) {
        if (!isset($_SESSION['id'])) {
            $this->redirectWithToast('Please login to view analytics.', 'error', '/login');
            return;
        }
        
        try {
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $course = $this->courseModel->getCourseById($courseId, $clientId);
            
            if (!$course) {
                $this->redirectWithToast('Course not found.', 'error', '/Unlockyourskills/course-management');
                return;
            }
            
            // Get analytics data
            $analytics = $this->courseModel->getCourseAnalytics($courseId);
            
            require 'views/course_analytics.php';
        } catch (Exception $e) {
            $this->redirectWithToast('Error loading analytics.', 'error', '/Unlockyourskills/course-management');
        }
    }

    /**
     * Helper method to send JSON response
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Load Add Course Modal Content (AJAX)
     */
    public function loadAddCourseModal() {
        error_log('[DEBUG] loadAddCourseModal called');
        if (!isset($_SESSION['id'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Authentication required',
                'redirect' => '/Unlockyourskills/login'
            ]);
            return;
        }
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $categories = $this->courseCategoryModel->getAllCategories($clientId);
        $vlrContent = $this->courseModel->getAvailableVLRContent($clientId);
        // Flatten the array for the modal
        $flatVlrContent = [];
        foreach ($vlrContent as $type => $items) {
            foreach ($items as $item) {
                $flatVlrContent[] = $item;
            }
        }
        $vlrContent = $flatVlrContent;
        // Get existing courses for prerequisites
        $existingCourses = $this->courseModel->getAllCourses($clientId);
        // Get currencies from countries table
        $currencies = $this->courseModel->getCurrencies();
        error_log('[DEBUG] $vlrContent (flattened) in controller: ' . print_r($vlrContent, true));
        require 'views/modals/add_course_modal_content.php';
    }

    // Handle form POST (standard submit)
    public function createCourse() {
        error_log('[DEBUG] createCourse method called');
        error_log('[DEBUG] REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
        error_log('[DEBUG] SESSION data: ' . print_r($_SESSION, true));
        
        try {
            // Check for different possible session user ID keys
            $userId = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
            error_log('[DEBUG] User ID found: ' . $userId);
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$userId) {
                error_log('[DEBUG] Authentication failed - Method: ' . $_SERVER['REQUEST_METHOD'] . ', User ID: ' . $userId);
                $this->jsonResponse(['success' => false, 'message' => 'Authentication required', 'redirect' => '/Unlockyourskills/login']);
                return;
            }
            
            $clientId = $_SESSION['user']['client_id'] ?? $_SESSION['client_id'] ?? null;
            error_log('[DEBUG] Client ID: ' . $clientId);
            
            // Check if this is a JSON request
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            error_log('[DEBUG] Content-Type: ' . $contentType);
            
            if (strpos($contentType, 'application/json') !== false) {
                error_log('[DEBUG] Processing JSON request');
                // Handle JSON request
                $input = file_get_contents('php://input');
                error_log('[DEBUG] Raw input: ' . substr($input, 0, 1000)); // Log first 1000 chars
                
                $jsonData = json_decode($input, true);
                if ($jsonData === null) {
                    error_log('[DEBUG] JSON decode failed: ' . json_last_error_msg());
                    $this->jsonResponse(['success' => false, 'message' => 'Invalid JSON data']);
                    return;
                }
                
                error_log('[DEBUG] JSON data received: ' . print_r($jsonData, true));
                error_log('[DEBUG] JSON data keys: ' . json_encode(array_keys($jsonData)));
                
                // Check for post-requisites specifically
                if (isset($jsonData['post_requisites'])) {
                    error_log('[DEBUG] Post-requisites found in JSON: ' . json_encode($jsonData['post_requisites']));
                    error_log('[DEBUG] Post-requisites count: ' . count($jsonData['post_requisites']));
                    error_log('[DEBUG] Post-requisites type: ' . gettype($jsonData['post_requisites']));
                } else {
                    error_log('[DEBUG] No post_requisites key found in JSON data');
                }
                
                // Process JSON data
                error_log('[DEBUG] Calling courseModel->createCourse');
                $result = $this->courseModel->createCourse($jsonData, [], $userId, $clientId);
                error_log('[DEBUG] Course creation result: ' . print_r($result, true));
                $this->jsonResponse($result);
            } else {
                error_log('[DEBUG] Processing form data request');
                error_log('[DEBUG] POST data: ' . print_r($_POST, true));
                error_log('[DEBUG] FILES data: ' . print_r($_FILES, true));
                
                // Handle form data request
                $result = $this->courseModel->createCourse($_POST, $_FILES, $userId, $clientId);
                error_log('[DEBUG] Form course creation result: ' . print_r($result, true));
                
                if ($result['success']) {
                    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Course created successfully!'];
                    header('Location: /Unlockyourskills/course-management');
                } else {
                    $_SESSION['toast'] = ['type' => 'error', 'message' => $result['message'] ?? 'Failed to create course.'];
                    header('Location: /Unlockyourskills/course-management');
                }
                exit;
            }
        } catch (Exception $e) {
            error_log('[ERROR] Exception in createCourse: ' . $e->getMessage());
            error_log('[ERROR] Exception trace: ' . $e->getTraceAsString());
            $this->jsonResponse(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        } catch (Error $e) {
            error_log('[FATAL ERROR] Error in createCourse: ' . $e->getMessage());
            error_log('[FATAL ERROR] Error trace: ' . $e->getTraceAsString());
            $this->jsonResponse(['success' => false, 'message' => 'Fatal server error: ' . $e->getMessage()]);
        }
    }
} 