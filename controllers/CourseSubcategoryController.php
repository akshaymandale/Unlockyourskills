<?php

require_once 'models/CourseSubcategoryModel.php';
require_once 'models/CourseCategoryModel.php';
require_once 'controllers/BaseController.php';
require_once 'config/Database.php';
require_once 'core/UrlHelper.php';
require_once 'config/Localization.php';
require_once 'includes/toast_helper.php';

class CourseSubcategoryController extends BaseController {
    private $courseSubcategoryModel;
    private $courseCategoryModel;
    private $db;

    public function __construct() {
        // Start output buffering to prevent any unexpected output
        ob_start();
        
        $this->courseSubcategoryModel = new CourseSubcategoryModel();
        $this->courseCategoryModel = new CourseCategoryModel();
    }

    /**
     * Main index page - Course Subcategories Management
     */
    public function index() {
        // Check authentication
        if (!isset($_SESSION['user']['client_id'])) {
            UrlHelper::redirect('index.php?controller=LoginController');
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];
        $systemRole = $currentUser['system_role'];

        // Get filter parameters
        $searchTerm = $_GET['search'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        $categoryFilter = $_GET['category'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 10;

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_GET['client_id'])) {
            $filterClientId = intval($_GET['client_id']);
        }

        // Get subcategories with pagination
        $result = $this->courseSubcategoryModel->getSubcategoriesWithPagination(
            $page, 
            $limit, 
            $filterClientId, 
            $searchTerm, 
            $statusFilter !== 'active' // include inactive if not filtering for active only
        );

        $subcategories = $result['records'];
        $totalRecords = $result['total'];
        $totalPages = $result['pages'];
        $currentPage = $result['current_page'];

        // Get unique statuses for filter dropdown
        $allSubcategories = $this->courseSubcategoryModel->getAllSubcategories($filterClientId, true);
        $uniqueStatuses = array_unique(array_column($allSubcategories, 'is_active'));

        // Get categories for filter dropdown
        $categories = $this->courseCategoryModel->getActiveCategoriesForDropdown($filterClientId);

        // Load the view
        require 'views/course_subcategories.php';
    }

    /**
     * AJAX search subcategories
     */
    public function ajaxSearch() {
        if (!isset($_SESSION['user']['client_id'])) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
                return;
            }
            UrlHelper::redirect('index.php?controller=LoginController');
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];
        $systemRole = $currentUser['system_role'];

        try {
            $limit = 10;
            $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
            $offset = ($page - 1) * $limit;

            // Get search and filter parameters
            $search = trim($_POST['search'] ?? '');
            $status = trim($_POST['status'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $sortOrder = trim($_POST['sort_order'] ?? '');

            // Determine client ID for filtering
            $filterClientId = $clientId;
            if ($systemRole === 'super_admin' && isset($_POST['client_id'])) {
                $filterClientId = intval($_POST['client_id']);
            }

            // Get subcategories from database with filters
            $result = $this->courseSubcategoryModel->getSubcategoriesWithPagination(
                $page, 
                $limit, 
                $filterClientId, 
                $search, 
                $status !== 'active' // includeInactive parameter - include inactive when not specifically filtering for active only
            );

            // Apply additional filters
            $filteredSubcategories = $result['records'];
            
            // Filter by status if specified
            if ($status === 'inactive') {
                $filteredSubcategories = array_filter($filteredSubcategories, function($subcat) {
                    return $subcat['is_active'] == 0;
                });
            } elseif ($status === 'active') {
                $filteredSubcategories = array_filter($filteredSubcategories, function($subcat) {
                    return $subcat['is_active'] == 1;
                });
            }
            // If status is empty, show all records (both active and inactive)
            
            // Filter by category if specified
            if ($category) {
                $filteredSubcategories = array_filter($filteredSubcategories, function($subcat) use ($category) {
                    return $subcat['category_id'] == $category;
                });
            }

            // Sort by sort order if specified
            if ($sortOrder === 'asc') {
                usort($filteredSubcategories, function($a, $b) {
                    return $a['sort_order'] - $b['sort_order'];
                });
            } elseif ($sortOrder === 'desc') {
                usort($filteredSubcategories, function($a, $b) {
                    return $b['sort_order'] - $a['sort_order'];
                });
            }

            // Re-index array after filtering
            $filteredSubcategories = array_values($filteredSubcategories);
            $totalSubcategories = count($filteredSubcategories);
            $totalPages = ceil($totalSubcategories / $limit);

            // Prepare response data
            $response = [
                'success' => true,
                'subcategories' => $filteredSubcategories,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $totalSubcategories,
                    'has_prev' => $page > 1,
                    'has_next' => $page < $totalPages
                ]
            ];

            $this->jsonResponse($response);

        } catch (Exception $e) {
            error_log("Error in CourseSubcategoryController::ajaxSearch: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while searching subcategories']);
        }
    }

    /**
     * Create subcategory
     */
    public function create() {
        if (!isset($_SESSION['user']['client_id'])) {
            UrlHelper::redirect('index.php?controller=LoginController');
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate input
            $errors = $this->validateSubcategoryData($_POST, $clientId);

            if (!empty($errors)) {
                $this->redirectWithToast(implode(', ', $errors), 'error', UrlHelper::url('course-subcategories'));
                return;
            }

            // Prepare data
            $data = [
                'client_id' => $clientId,
                'category_id' => intval($_POST['category_id']),
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description'] ?? ''),
                'sort_order' => $this->courseSubcategoryModel->getNextSortOrder(intval($_POST['category_id']), $clientId),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'created_by' => $currentUser['id']
            ];

            // Create subcategory
            $result = $this->courseSubcategoryModel->createSubcategory($data);

            if ($result) {
                $this->redirectWithToast('Course subcategory created successfully!', 'success', UrlHelper::url('course-subcategories'));
            } else {
                $this->redirectWithToast('Failed to create course subcategory.', 'error', UrlHelper::url('course-subcategories'));
            }
        } else {
            // Show create form
            $categories = $this->courseCategoryModel->getActiveCategoriesForDropdown($clientId);
            require 'views/course_subcategories_create.php';
        }
    }

    /**
     * Edit subcategory
     */
    public function edit($id = null) {
        if (!isset($_SESSION['user']['client_id'])) {
            UrlHelper::redirect('index.php?controller=LoginController');
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];
        $systemRole = $currentUser['system_role'];

        // Get subcategory ID
        if (!$id && isset($_GET['id'])) {
            $id = $_GET['id'];
        }

        if (!$id) {
            $this->redirectWithToast('Subcategory ID is required.', 'error', UrlHelper::url('course-subcategories'));
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_GET['client_id'])) {
            $filterClientId = intval($_GET['client_id']);
        }

        // Get subcategory
        $subcategory = $this->courseSubcategoryModel->getSubcategoryById($id, $filterClientId);

        if (!$subcategory) {
            $this->redirectWithToast('Subcategory not found.', 'error', UrlHelper::url('course-subcategories'));
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate input
            $errors = $this->validateSubcategoryData($_POST, $filterClientId, $id);

            if (!empty($errors)) {
                $this->redirectWithToast(implode(', ', $errors), 'error', UrlHelper::url('course-subcategories'));
                return;
            }

            // Prepare data
            $data = [
                'category_id' => intval($_POST['category_id']),
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description'] ?? ''),
                'sort_order' => intval($_POST['sort_order'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'updated_by' => $currentUser['id']
            ];

            // Update subcategory
            $result = $this->courseSubcategoryModel->updateSubcategory($id, $data);

            if ($result) {
                $this->redirectWithToast('Course subcategory updated successfully!', 'success', UrlHelper::url('course-subcategories'));
            } else {
                $this->redirectWithToast('Failed to update course subcategory.', 'error', UrlHelper::url('course-subcategories'));
            }
        } else {
            // Show edit form
            $categories = $this->courseCategoryModel->getActiveCategoriesForDropdown($filterClientId);
            require 'views/course_subcategories_edit.php';
        }
    }

    /**
     * Delete subcategory
     */
    public function delete($id = null) {
        if (!isset($_SESSION['user']['client_id'])) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
                return;
            }
            UrlHelper::redirect('index.php?controller=LoginController');
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];
        $systemRole = $currentUser['system_role'];

        // Get subcategory ID
        if (!$id && isset($_GET['id'])) {
            $id = $_GET['id'];
        }
        
        // For AJAX requests, also check POST data
        if (!$id && $this->isAjaxRequest() && isset($_POST['id'])) {
            $id = $_POST['id'];
        }

        if (!$id) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Subcategory ID is required']);
                return;
            }
            $this->redirectWithToast('Subcategory ID is required.', 'error', UrlHelper::url('course-subcategories'));
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_GET['client_id'])) {
            $filterClientId = intval($_GET['client_id']);
        }

        // Get subcategory
        $subcategory = $this->courseSubcategoryModel->getSubcategoryById($id, $filterClientId);

        if (!$subcategory) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Subcategory not found']);
                return;
            }
            $this->redirectWithToast('Subcategory not found.', 'error', UrlHelper::url('course-subcategories'));
            return;
        }

        // Delete subcategory
        $result = $this->courseSubcategoryModel->deleteSubcategory($id);

        if ($result) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => true, 'message' => 'Course subcategory deleted successfully!']);
                return;
            }
            $this->redirectWithToast('Course subcategory deleted successfully!', 'success', UrlHelper::url('course-subcategories'));
        } else {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to delete course subcategory.']);
                return;
            }
            $this->redirectWithToast('Failed to delete course subcategory.', 'error', UrlHelper::url('course-subcategories'));
        }
    }

    /**
     * Toggle subcategory status
     */
    public function toggleStatus() {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];
        $systemRole = $currentUser['system_role'];

        $subcategoryId = $_POST['subcategory_id'] ?? null;

        if (!$subcategoryId) {
            $this->jsonResponse(['success' => false, 'message' => 'Subcategory ID is required']);
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_POST['client_id'])) {
            $filterClientId = intval($_POST['client_id']);
        }

        // Get subcategory to verify ownership
        $subcategory = $this->courseSubcategoryModel->getSubcategoryById($subcategoryId, $filterClientId);

        if (!$subcategory) {
            $this->jsonResponse(['success' => false, 'message' => 'Subcategory not found']);
            return;
        }

        // Toggle status
        $result = $this->courseSubcategoryModel->toggleSubcategoryStatus($subcategoryId);

        if ($result) {
            $newStatus = $subcategory['is_active'] ? 0 : 1;
            $statusText = $newStatus ? 'active' : 'inactive';
            $this->jsonResponse([
                'success' => true, 
                'message' => "Subcategory status updated to {$statusText}",
                'new_status' => $newStatus
            ]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update subcategory status']);
        }
    }

    /**
     * Get subcategory by ID (AJAX)
     */
    public function get($id = null) {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];
        $systemRole = $currentUser['system_role'];

        // Get subcategory ID from GET parameter if not provided
        if (!$id && isset($_GET['id'])) {
            $id = $_GET['id'];
        }

        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'Subcategory ID is required']);
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_GET['client_id'])) {
            $filterClientId = intval($_GET['client_id']);
        }

        // Get subcategory
        $subcategory = $this->courseSubcategoryModel->getSubcategoryById($id, $filterClientId);

        if (!$subcategory) {
            $this->jsonResponse(['success' => false, 'message' => 'Subcategory not found']);
            return;
        }

        $this->jsonResponse(['success' => true, 'subcategory' => $subcategory]);
    }

    /**
     * Load add subcategory modal
     */
    public function loadAddModal() {
        if (!isset($_SESSION['user']['client_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];

        // Get categories for dropdown
        $categories = $this->courseCategoryModel->getActiveCategoriesForDropdown($clientId);

        // Return modal HTML
        $modalHtml = $this->generateAddModalHtml($categories);
        echo json_encode(['success' => true, 'html' => $modalHtml]);
    }

    /**
     * Load edit subcategory modal
     */
    public function loadEditModal() {
        if (!isset($_SESSION['user']['client_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];
        $systemRole = $currentUser['system_role'];

        $subcategoryId = $_POST['subcategory_id'] ?? null;

        if (!$subcategoryId) {
            echo json_encode(['success' => false, 'message' => 'Subcategory ID is required']);
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_POST['client_id'])) {
            $filterClientId = intval($_POST['client_id']);
        }

        // Get subcategory
        $subcategory = $this->courseSubcategoryModel->getSubcategoryById($subcategoryId, $filterClientId);

        if (!$subcategory) {
            echo json_encode(['success' => false, 'message' => 'Subcategory not found']);
            return;
        }

        // Get categories for dropdown
        $categories = $this->courseCategoryModel->getActiveCategoriesForDropdown($filterClientId);

        // Return modal HTML
        $modalHtml = $this->generateEditModalHtml($subcategory, $categories);
        echo json_encode(['success' => true, 'html' => $modalHtml]);
    }

    /**
     * Submit add subcategory modal
     */
    public function submitAddModal() {
        if (!isset($_SESSION['user']['client_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];

        // Validate input
        $errors = $this->validateSubcategoryData($_POST, $clientId);

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => implode(', ', $errors),
                'field_errors' => $errors
            ]);
            return;
        }

        // Prepare data
        $data = [
            'client_id' => $clientId,
            'category_id' => intval($_POST['category_id']),
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'created_by' => $currentUser['id']
        ];

        // Create subcategory
        $result = $this->courseSubcategoryModel->createSubcategory($data);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Course subcategory created successfully!',
                'redirect' => UrlHelper::url('course-subcategories')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create course subcategory.'
            ]);
        }
    }

    /**
     * Submit edit subcategory modal
     */
    public function submitEditModal() {
        if (!isset($_SESSION['user']['client_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];
        $systemRole = $currentUser['system_role'];

        $subcategoryId = $_POST['subcategory_id'] ?? null;

        if (!$subcategoryId) {
            echo json_encode([
                'success' => false,
                'message' => 'Subcategory ID is required'
            ]);
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_POST['client_id'])) {
            $filterClientId = intval($_POST['client_id']);
        }

        // Validate input
        $errors = $this->validateSubcategoryData($_POST, $filterClientId, $subcategoryId);

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => implode(', ', $errors),
                'field_errors' => $errors
            ]);
            return;
        }

        // Prepare data
        $data = [
            'category_id' => intval($_POST['category_id']),
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'updated_by' => $currentUser['id']
        ];

        // Update subcategory
        $result = $this->courseSubcategoryModel->updateSubcategory($subcategoryId, $data);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Course subcategory updated successfully!',
                'redirect' => UrlHelper::url('course-subcategories')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update course subcategory.'
            ]);
        }
    }

    /**
     * Submit subcategory form (unified for add/edit)
     */
    public function submitSubcategory() {
        if (!isset($_SESSION['user']['client_id'])) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
                return;
            }
            UrlHelper::redirect('index.php?controller=LoginController');
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];
        $systemRole = $currentUser['system_role'];

        // Determine if this is an edit or add operation
        $subcategoryId = $_POST['subcategory_id'] ?? null;
        $mode = $subcategoryId ? 'edit' : 'add';

        if ($mode === 'edit' && !$subcategoryId) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Subcategory ID is required for editing']);
                return;
            }
            $this->redirectWithToast('Subcategory ID is required for editing.', 'error', UrlHelper::url('course-subcategories'));
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_POST['client_id'])) {
            $filterClientId = intval($_POST['client_id']);
        }

        // Validate input
        $errors = $this->validateSubcategoryData($_POST, $filterClientId, $subcategoryId);

        if (!empty($errors)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => implode(', ', $errors),
                    'errors' => $errors
                ]);
                return;
            }
            $this->redirectWithToast(implode(', ', $errors), 'error', UrlHelper::url('course-subcategories'));
            return;
        }

        if ($mode === 'add') {
            // Create subcategory
            $data = [
                'client_id' => $clientId,
                'category_id' => intval($_POST['category_id']),
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description'] ?? ''),
                'sort_order' => intval($_POST['sort_order'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'created_by' => $currentUser['id']
            ];

            $result = $this->courseSubcategoryModel->createSubcategory($data);
            $message = 'Course subcategory created successfully!';
        } else {
            // Update subcategory
            $data = [
                'category_id' => intval($_POST['category_id']),
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description'] ?? ''),
                'sort_order' => intval($_POST['sort_order'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'updated_by' => $currentUser['id']
            ];

            $result = $this->courseSubcategoryModel->updateSubcategory($subcategoryId, $data);
            $message = 'Course subcategory updated successfully!';
        }

        if ($result) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => $message
                ]);
                return;
            }
            $this->redirectWithToast($message, 'success', UrlHelper::url('course-subcategories'));
        } else {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to ' . ($mode === 'add' ? 'create' : 'update') . ' course subcategory.'
                ]);
                return;
            }
            $this->redirectWithToast('Failed to ' . ($mode === 'add' ? 'create' : 'update') . ' course subcategory.', 'error', UrlHelper::url('course-subcategories'));
        }
    }

    /**
     * Validate subcategory data
     */
    private function validateSubcategoryData($data, $clientId, $excludeId = null) {
        $errors = [];

        // Validate category_id
        $categoryId = intval($data['category_id'] ?? 0);
        if ($categoryId <= 0) {
            $errors['category_id'] = 'Please select a valid category';
        } else {
            // Verify category exists and belongs to client
            $category = $this->courseCategoryModel->getCategoryById($categoryId, $clientId);
            if (!$category) {
                $errors['category_id'] = 'Selected category does not exist or is not accessible';
            }
        }

        // Validate name
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'] = 'Subcategory name is required';
        } elseif (strlen($name) > 100) {
            $errors['name'] = 'Subcategory name cannot exceed 100 characters';
        } elseif ($this->courseSubcategoryModel->checkSubcategoryNameExists($name, $categoryId, $clientId, $excludeId)) {
            $errors['name'] = 'A subcategory with this name already exists in the selected category';
        }

        // Validate description
        $description = trim($data['description'] ?? '');
        if (!empty($description) && strlen($description) > 500) {
            $errors['description'] = 'Description cannot exceed 500 characters';
        }

        // Validate sort order
        $sortOrder = intval($data['sort_order'] ?? 0);
        if ($sortOrder < 0) {
            $errors['sort_order'] = 'Sort order must be a positive number';
        }

        return $errors;
    }

    /**
     * API endpoint to get subcategories for dropdown
     */
    public function getSubcategoriesForDropdown() {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];
        $systemRole = $currentUser['system_role'];

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_GET['client_id'])) {
            $filterClientId = intval($_GET['client_id']);
        }

        // Get category filter if provided
        $categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;

        try {
            if ($categoryId) {
                $subcategories = $this->courseSubcategoryModel->getSubcategoriesByCategoryId($categoryId, $filterClientId);
            } else {
                $subcategories = $this->courseSubcategoryModel->getActiveSubcategoriesForDropdown($filterClientId);
            }

            $this->jsonResponse(['success' => true, 'subcategories' => $subcategories]);
        } catch (Exception $e) {
            error_log("Error in CourseSubcategoryController::getSubcategoriesForDropdown: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'An error occurred while fetching subcategories']);
        }
    }

    /**
     * Generate add modal HTML
     */
    private function generateAddModalHtml($categories) {
        ob_start();
        ?>
        <div class="modal-header">
            <h5 class="modal-title">
                <i class="fas fa-plus me-2"></i><?php echo Localization::translate('course_subcategories.add_subcategory'); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="addSubcategoryForm">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="subcategoryName" class="form-label">
                                <?php echo Localization::translate('course_subcategories.name'); ?>
                            </label>
                            <input type="text" class="form-control" id="subcategoryName" name="name" 
                                   placeholder="<?php echo Localization::translate('course_subcategories.enter_name'); ?>" 
                                   maxlength="100">
                            <div class="invalid-feedback" id="nameError"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="subcategoryCategory" class="form-label">
                                <?php echo Localization::translate('course_subcategories.category'); ?>
                            </label>
                            <select class="form-select" id="subcategoryCategory" name="category_id">
                                <option value=""><?php echo Localization::translate('course_subcategories.select_category'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="categoryError"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="subcategorySortOrder" class="form-label">
                                <?php echo Localization::translate('course_subcategories.sort_order'); ?>
                            </label>
                            <input type="number" class="form-control" id="subcategorySortOrder" name="sort_order" 
                                   value="0" min="0">
                            <div class="form-text"><?php echo Localization::translate('course_subcategories.sort_order_help'); ?></div>
                            <div class="invalid-feedback" id="sortOrderError"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="subcategoryActive" name="is_active" checked>
                                <label class="form-check-label" for="subcategoryActive">
                                    <?php echo Localization::translate('course_subcategories.active_subcategory'); ?>
                                </label>
                                <div class="form-text"><?php echo Localization::translate('course_subcategories.active_help'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="subcategoryDescription" class="form-label">
                        <?php echo Localization::translate('course_subcategories.description'); ?>
                    </label>
                    <textarea class="form-control" id="subcategoryDescription" name="description" rows="4" 
                              placeholder="<?php echo Localization::translate('course_subcategories.enter_description'); ?>" 
                              maxlength="500"></textarea>
                    <div class="form-text">
                        <span id="charCount">0</span> / 500 <?php echo Localization::translate('course_subcategories.characters'); ?>
                    </div>
                    <div class="invalid-feedback" id="descriptionError"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo Localization::translate('common.cancel'); ?>
                </button>
                <button type="submit" class="btn theme-btn-primary">
                    <i class="fas fa-save me-2"></i><?php echo Localization::translate('course_subcategories.create_subcategory'); ?>
                </button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Generate edit modal HTML
     */
    private function generateEditModalHtml($subcategory, $categories) {
        ob_start();
        ?>
        <div class="modal-header">
            <h5 class="modal-title">
                <i class="fas fa-edit me-2"></i><?php echo Localization::translate('course_subcategories.edit_subcategory'); ?>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="editSubcategoryForm">
            <input type="hidden" name="subcategory_id" value="<?php echo $subcategory['id']; ?>">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="editSubcategoryName" class="form-label">
                                <?php echo Localization::translate('course_subcategories.name'); ?>
                            </label>
                            <input type="text" class="form-control" id="editSubcategoryName" name="name" 
                                   value="<?php echo htmlspecialchars($subcategory['name']); ?>"
                                   placeholder="<?php echo Localization::translate('course_subcategories.enter_name'); ?>" 
                                   maxlength="100">
                            <div class="invalid-feedback" id="editNameError"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="editSubcategoryCategory" class="form-label">
                                <?php echo Localization::translate('course_subcategories.category'); ?>
                            </label>
                            <select class="form-select" id="editSubcategoryCategory" name="category_id">
                                <option value=""><?php echo Localization::translate('course_subcategories.select_category'); ?></option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category['id'] == $subcategory['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback" id="editCategoryError"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="editSubcategorySortOrder" class="form-label">
                                <?php echo Localization::translate('course_subcategories.sort_order'); ?>
                            </label>
                            <input type="number" class="form-control" id="editSubcategorySortOrder" name="sort_order" 
                                   value="<?php echo $subcategory['sort_order']; ?>" min="0">
                            <div class="form-text"><?php echo Localization::translate('course_subcategories.sort_order_help'); ?></div>
                            <div class="invalid-feedback" id="editSortOrderError"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editSubcategoryActive" name="is_active" 
                                       <?php echo $subcategory['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="editSubcategoryActive">
                                    <?php echo Localization::translate('course_subcategories.active_subcategory'); ?>
                                </label>
                                <div class="form-text"><?php echo Localization::translate('course_subcategories.active_help'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="editSubcategoryDescription" class="form-label">
                        <?php echo Localization::translate('course_subcategories.description'); ?>
                    </label>
                    <textarea class="form-control" id="editSubcategoryDescription" name="description" rows="4" 
                              placeholder="<?php echo Localization::translate('course_subcategories.enter_description'); ?>" 
                              maxlength="500"><?php echo htmlspecialchars($subcategory['description'] ?? ''); ?></textarea>
                    <div class="form-text">
                        <span id="editCharCount"><?php echo strlen($subcategory['description'] ?? ''); ?></span> / 500 <?php echo Localization::translate('course_subcategories.characters'); ?>
                    </div>
                    <div class="invalid-feedback" id="editDescriptionError"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo Localization::translate('common.cancel'); ?>
                </button>
                <button type="submit" class="btn theme-btn-primary">
                    <i class="fas fa-save me-2"></i><?php echo Localization::translate('course_subcategories.update_subcategory'); ?>
                </button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if the current request is an AJAX request
     */
    protected function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Send JSON response with proper headers and error handling
     */
    private function jsonResponse($data) {
        // Clear any previous output
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Set proper headers
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        // Ensure no errors are output
        error_reporting(0);
        
        echo json_encode($data);
        exit;
    }
} 