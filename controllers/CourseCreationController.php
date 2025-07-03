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
     * Display course creation page
     */
    public function index()
    {
        // Check if user is authenticated
        if (!isset($_SESSION['id'])) {
            $this->redirectWithToast('Please login to access course creation.', 'error', '/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'] ?? null;
        $currentUser = $_SESSION['id'];

        // Get categories and subcategories
        $categories = $this->courseCategoryModel->getAllCategories($clientId);
        $subcategories = $this->courseSubcategoryModel->getAllSubcategories($clientId);

        // Get available VLR content
        $vlrContent = $this->courseModel->getAvailableVLRContent($clientId);

        // Get existing courses for prerequisites
        $existingCourses = $this->courseModel->getCourses($clientId);

        require 'views/course_creation.php';
    }

    /**
     * Create a new course
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithToast('Invalid request method.', 'error', '/course-creation');
            return;
        }

        // Check if user is authenticated
        if (!isset($_SESSION['id'])) {
            $this->redirectWithToast('Please login to create courses.', 'error', '/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'] ?? null;
        $currentUser = $_SESSION['id'];

        // Validate input
        $errors = $this->validateCourseData($_POST);

        if (!empty($errors)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => implode(', ', $errors),
                    'errors' => $errors
                ]);
                return;
            }
            $this->redirectWithToast(implode(', ', $errors), 'error', '/course-creation');
            return;
        }

        // Prepare course data
        $courseData = [
            'client_id' => $clientId,
            'title' => trim($_POST['title']),
            'description' => trim($_POST['description'] ?? ''),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'category_id' => intval($_POST['category_id']),
            'subcategory_id' => intval($_POST['subcategory_id']),
            'course_type' => $_POST['course_type'],
            'difficulty_level' => $_POST['difficulty_level'],
            'duration_hours' => floatval($_POST['duration_hours'] ?? 0),
            'duration_minutes' => intval($_POST['duration_minutes'] ?? 0),
            'max_attempts' => intval($_POST['max_attempts'] ?? 1),
            'passing_score' => floatval($_POST['passing_score'] ?? 70.0),
            'is_self_paced' => isset($_POST['is_self_paced']) ? 1 : 0,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_published' => isset($_POST['is_published']) ? 1 : 0,
            'thumbnail_image' => $_POST['thumbnail_image'] ?? null,
            'banner_image' => $_POST['banner_image'] ?? null,
            'tags' => json_encode($_POST['tags'] ?? []),
            'learning_objectives' => json_encode($_POST['learning_objectives'] ?? []),
            'prerequisites' => json_encode($_POST['prerequisites'] ?? []),
            'target_audience' => trim($_POST['target_audience'] ?? ''),
            'certificate_template' => $_POST['certificate_template'] ?? null,
            'completion_criteria' => json_encode($_POST['completion_criteria'] ?? []),
            'created_by' => $currentUser
        ];

        // Handle file uploads
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $courseData['thumbnail_image'] = $this->uploadFile($_FILES['thumbnail'], 'uploads/courses/thumbnails/');
        }

        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $courseData['banner_image'] = $this->uploadFile($_FILES['banner'], 'uploads/courses/banners/');
        }

        // Process modules
        if (!empty($_POST['modules'])) {
            $courseData['modules'] = $this->processModules($_POST['modules'], $currentUser);
        }

        // Process prerequisites
        if (!empty($_POST['prerequisite_courses'])) {
            $courseData['prerequisite_courses'] = $this->processPrerequisites($_POST['prerequisite_courses'], $currentUser);
        }

        // Process assessments
        if (!empty($_POST['assessments'])) {
            $courseData['assessments'] = $this->processAssessments($_POST['assessments'], $currentUser);
        }

        // Process feedback
        if (!empty($_POST['feedback'])) {
            $courseData['feedback'] = $this->processFeedback($_POST['feedback'], $currentUser);
        }

        // Process surveys
        if (!empty($_POST['surveys'])) {
            $courseData['surveys'] = $this->processSurveys($_POST['surveys'], $currentUser);
        }

        // Create course
        $courseId = $this->courseModel->createCourse($courseData);

        if ($courseId) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Course created successfully!',
                    'course_id' => $courseId
                ]);
                return;
            }
            $this->redirectWithToast('Course created successfully!', 'success', '/course-creation');
        } else {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to create course. Please try again.'
                ]);
                return;
            }
            $this->redirectWithToast('Failed to create course. Please try again.', 'error', '/course-creation');
        }
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
     * Validate course data
     */
    private function validateCourseData($data)
    {
        $errors = [];

        // Required fields
        if (empty($data['title'])) {
            $errors[] = 'Course title is required';
        }

        if (empty($data['category_id'])) {
            $errors[] = 'Category is required';
        }

        if (empty($data['subcategory_id'])) {
            $errors[] = 'Subcategory is required';
        }

        if (empty($data['course_type'])) {
            $errors[] = 'Course type is required';
        }

        if (empty($data['difficulty_level'])) {
            $errors[] = 'Difficulty level is required';
        }

        // Validate title length
        if (strlen($data['title']) > 255) {
            $errors[] = 'Course title cannot exceed 255 characters';
        }

        // Validate description length
        if (!empty($data['description']) && strlen($data['description']) > 65535) {
            $errors[] = 'Course description is too long';
        }

        // Validate short description length
        if (!empty($data['short_description']) && strlen($data['short_description']) > 500) {
            $errors[] = 'Short description cannot exceed 500 characters';
        }

        // Validate duration
        if (isset($data['duration_hours']) && ($data['duration_hours'] < 0 || $data['duration_hours'] > 999.99)) {
            $errors[] = 'Duration hours must be between 0 and 999.99';
        }

        if (isset($data['duration_minutes']) && ($data['duration_minutes'] < 0 || $data['duration_minutes'] > 59)) {
            $errors[] = 'Duration minutes must be between 0 and 59';
        }

        // Validate passing score
        if (isset($data['passing_score']) && ($data['passing_score'] < 0 || $data['passing_score'] > 100)) {
            $errors[] = 'Passing score must be between 0 and 100';
        }

        // Validate max attempts
        if (isset($data['max_attempts']) && ($data['max_attempts'] < 1 || $data['max_attempts'] > 999)) {
            $errors[] = 'Maximum attempts must be between 1 and 999';
        }

        return $errors;
    }

    /**
     * Process modules data
     */
    private function processModules($modulesData, $createdBy)
    {
        $modules = [];
        foreach ($modulesData as $index => $module) {
            if (!empty($module['title'])) {
                $modules[] = [
                    'title' => trim($module['title']),
                    'description' => trim($module['description'] ?? ''),
                    'sort_order' => intval($module['sort_order'] ?? $index + 1),
                    'is_required' => isset($module['is_required']) ? 1 : 0,
                    'estimated_duration' => intval($module['estimated_duration'] ?? 0),
                    'learning_objectives' => json_encode($module['learning_objectives'] ?? []),
                    'created_by' => $createdBy,
                    'content' => isset($module['content']) ? $this->processModuleContent($module['content'], $createdBy) : []
                ];
            }
        }
        return $modules;
    }

    /**
     * Process module content data
     */
    private function processModuleContent($contentData, $createdBy)
    {
        $content = [];
        foreach ($contentData as $index => $item) {
            if (!empty($item['content_type']) && !empty($item['content_id'])) {
                $content[] = [
                    'content_type' => $item['content_type'],
                    'content_id' => intval($item['content_id']),
                    'title' => trim($item['title'] ?? ''),
                    'description' => trim($item['description'] ?? ''),
                    'sort_order' => intval($item['sort_order'] ?? $index + 1),
                    'is_required' => isset($item['is_required']) ? 1 : 0,
                    'estimated_duration' => intval($item['estimated_duration'] ?? 0),
                    'completion_criteria' => json_encode($item['completion_criteria'] ?? []),
                    'created_by' => $createdBy
                ];
            }
        }
        return $content;
    }

    /**
     * Process prerequisites data
     */
    private function processPrerequisites($prerequisitesData, $createdBy)
    {
        $prerequisites = [];
        foreach ($prerequisitesData as $prerequisite) {
            if (!empty($prerequisite['prerequisite_course_id'])) {
                $prerequisites[] = [
                    'prerequisite_course_id' => intval($prerequisite['prerequisite_course_id']),
                    'prerequisite_type' => $prerequisite['prerequisite_type'] ?? 'required',
                    'minimum_score' => floatval($prerequisite['minimum_score'] ?? 0),
                    'created_by' => $createdBy
                ];
            }
        }
        return $prerequisites;
    }

    /**
     * Process assessments data
     */
    private function processAssessments($assessmentsData, $createdBy)
    {
        $assessments = [];
        foreach ($assessmentsData as $index => $assessment) {
            if (!empty($assessment['assessment_id'])) {
                $assessments[] = [
                    'assessment_id' => intval($assessment['assessment_id']),
                    'assessment_type' => $assessment['assessment_type'] ?? 'post_course',
                    'module_id' => !empty($assessment['module_id']) ? intval($assessment['module_id']) : null,
                    'title' => trim($assessment['title'] ?? ''),
                    'description' => trim($assessment['description'] ?? ''),
                    'is_required' => isset($assessment['is_required']) ? 1 : 0,
                    'passing_score' => floatval($assessment['passing_score'] ?? 70.0),
                    'max_attempts' => intval($assessment['max_attempts'] ?? 1),
                    'time_limit' => !empty($assessment['time_limit']) ? intval($assessment['time_limit']) : null,
                    'sort_order' => intval($assessment['sort_order'] ?? $index + 1),
                    'created_by' => $createdBy
                ];
            }
        }
        return $assessments;
    }

    /**
     * Process feedback data
     */
    private function processFeedback($feedbackData, $createdBy)
    {
        $feedback = [];
        foreach ($feedbackData as $index => $item) {
            if (!empty($item['feedback_id'])) {
                $feedback[] = [
                    'feedback_id' => intval($item['feedback_id']),
                    'feedback_type' => $item['feedback_type'] ?? 'post_course',
                    'module_id' => !empty($item['module_id']) ? intval($item['module_id']) : null,
                    'title' => trim($item['title'] ?? ''),
                    'description' => trim($item['description'] ?? ''),
                    'is_required' => isset($item['is_required']) ? 1 : 0,
                    'sort_order' => intval($item['sort_order'] ?? $index + 1),
                    'created_by' => $createdBy
                ];
            }
        }
        return $feedback;
    }

    /**
     * Process surveys data
     */
    private function processSurveys($surveysData, $createdBy)
    {
        $surveys = [];
        foreach ($surveysData as $index => $survey) {
            if (!empty($survey['survey_id'])) {
                $surveys[] = [
                    'survey_id' => intval($survey['survey_id']),
                    'survey_type' => $survey['survey_type'] ?? 'post_course',
                    'module_id' => !empty($survey['module_id']) ? intval($survey['module_id']) : null,
                    'title' => trim($survey['title'] ?? ''),
                    'description' => trim($survey['description'] ?? ''),
                    'is_required' => isset($survey['is_required']) ? 1 : 0,
                    'sort_order' => intval($survey['sort_order'] ?? $index + 1),
                    'created_by' => $createdBy
                ];
            }
        }
        return $surveys;
    }

    /**
     * Upload file
     */
    private function uploadFile($file, $uploadDir)
    {
        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $targetPath;
        }

        return null;
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
            $result = $this->courseModel->updateCourseStatus($courseId, 'published', $clientId);
            
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
            $result = $this->courseModel->updateCourseStatus($courseId, 'draft', $clientId);
            
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
     * Edit Course Page
     */
    public function editCourse($courseId) {
        if (!isset($_SESSION['id'])) {
            $this->redirectWithToast('Please login to edit courses.', 'error', '/login');
            return;
        }
        
        try {
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $course = $this->courseModel->getCourseById($courseId, $clientId);
            
            if (!$course) {
                $this->redirectWithToast('Course not found.', 'error', '/course-management');
                return;
            }
            
            $categories = $this->courseCategoryModel->getAllCategories($clientId);
            $subcategories = $this->courseSubcategoryModel->getSubcategoriesByCategoryId($course['category_id'], $clientId);
            
            require 'views/course_creation.php';
        } catch (Exception $e) {
            $this->redirectWithToast('Error loading course.', 'error', '/course-management');
        }
    }

    /**
     * Preview Course Page
     */
    public function previewCourse($courseId) {
        if (!isset($_SESSION['id'])) {
            $this->redirectWithToast('Please login to preview courses.', 'error', '/login');
            return;
        }
        
        try {
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $course = $this->courseModel->getCourseById($courseId, $clientId);
            
            if (!$course) {
                $this->redirectWithToast('Course not found.', 'error', '/course-management');
                return;
            }
            
            // Get course modules and content
            $modules = $this->courseModel->getCourseModules($courseId);
            $prerequisites = $this->courseModel->getCoursePrerequisites($courseId);
            $assessments = $this->courseModel->getCourseAssessments($courseId);
            $feedback = $this->courseModel->getCourseFeedback($courseId);
            $surveys = $this->courseModel->getCourseSurveys($courseId);
            
            require 'views/course_preview.php';
        } catch (Exception $e) {
            $this->redirectWithToast('Error loading course.', 'error', '/course-management');
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
                $this->redirectWithToast('Course not found.', 'error', '/course-management');
                return;
            }
            
            // Get analytics data
            $analytics = $this->courseModel->getCourseAnalytics($courseId);
            
            require 'views/course_analytics.php';
        } catch (Exception $e) {
            $this->redirectWithToast('Error loading analytics.', 'error', '/course-management');
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
        error_log('[DEBUG] $vlrContent (flattened) in controller: ' . print_r($vlrContent, true));
        require 'views/modals/add_course_modal_content.php';
    }

    // Handle form POST (standard submit)
    public function createCourse() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id'])) {
            header('Location: /course-management');
            exit;
        }
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $userId = $_SESSION['id'];
        $result = $this->courseModel->createCourse($_POST, $_FILES, $userId, $clientId);
        if ($result['success']) {
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Course created successfully!'];
            header('Location: /course-management');
        } else {
            $_SESSION['toast'] = ['type' => 'error', 'message' => $result['message'] ?? 'Failed to create course.'];
            header('Location: /course-management');
        }
        exit;
    }
} 