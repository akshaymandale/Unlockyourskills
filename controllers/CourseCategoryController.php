<?php

require_once 'models/CourseCategoryModel.php';
require_once 'controllers/BaseController.php';
require_once 'config/Database.php';
require_once 'core/UrlHelper.php';
require_once 'config/Localization.php';
require_once 'includes/toast_helper.php';

class CourseCategoryController extends BaseController {
    private $courseCategoryModel;
    private $db;

    public function __construct() {
        // Start output buffering to prevent any unexpected output
        ob_start();
        
        $this->courseCategoryModel = new CourseCategoryModel();
    }

    /**
     * Main index page - Course Categories Management
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
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 10;

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_GET['client_id'])) {
            $filterClientId = intval($_GET['client_id']);
        }

        // Get categories with pagination
        $result = $this->courseCategoryModel->getCategoriesWithPagination(
            $page, 
            $limit, 
            $filterClientId, 
            $searchTerm, 
            $statusFilter !== 'active' // include inactive if not filtering for active only
        );

        $categories = $result['records'];
        $totalRecords = $result['total'];
        $totalPages = $result['pages'];
        $currentPage = $result['current_page'];

        // Get unique statuses for filter dropdown
        $allCategories = $this->courseCategoryModel->getAllCategories($filterClientId, true);
        $uniqueStatuses = array_unique(array_column($allCategories, 'is_active'));

        // Load the view
        require 'views/course_categories.php';
    }

    /**
     * AJAX search categories
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
            $sortOrder = trim($_POST['sort_order'] ?? '');
            $subcategoryCount = trim($_POST['subcategory_count'] ?? '');

            // Determine client ID for filtering
            $filterClientId = $clientId;
            if ($systemRole === 'super_admin' && isset($_POST['client_id'])) {
                $filterClientId = intval($_POST['client_id']);
            }

            // Get categories from database with filters
            $result = $this->courseCategoryModel->getCategoriesWithPagination(
                $page, 
                $limit, 
                $filterClientId, 
                $search, 
                $status !== 'active' // includeInactive parameter - include inactive when not specifically filtering for active only
            );

            // Apply additional filters
            $filteredCategories = $result['records'];
            
            // Filter by status if specified
            if ($status === 'inactive') {
                $filteredCategories = array_filter($filteredCategories, function($cat) {
                    return $cat['is_active'] == 0;
                });
            } elseif ($status === 'active') {
                $filteredCategories = array_filter($filteredCategories, function($cat) {
                    return $cat['is_active'] == 1;
                });
            }
            // If status is empty, show all records (both active and inactive)
            
            // Filter by subcategory count
            if ($subcategoryCount === '0') {
                $filteredCategories = array_filter($filteredCategories, function($cat) {
                    return $cat['subcategory_count'] == 0;
                });
            } elseif ($subcategoryCount === '1+') {
                $filteredCategories = array_filter($filteredCategories, function($cat) {
                    return $cat['subcategory_count'] > 0;
                });
            }

            // Sort by sort order if specified
            if ($sortOrder === 'asc') {
                usort($filteredCategories, function($a, $b) {
                    return $a['sort_order'] - $b['sort_order'];
                });
            } elseif ($sortOrder === 'desc') {
                usort($filteredCategories, function($a, $b) {
                    return $b['sort_order'] - $a['sort_order'];
                });
            }

            // Re-index array after filtering
            $filteredCategories = array_values($filteredCategories);
            $totalCategories = count($filteredCategories);
            $totalPages = ceil($totalCategories / $limit);

            $response = [
                'success' => true,
                'categories' => $filteredCategories,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalCategories' => $totalCategories
                ]
            ];

            $this->jsonResponse($response);

        } catch (Exception $e) {
            error_log('Course categories AJAX search error: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error loading categories: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create new category
     */
    public function create() {
        if (!isset($_SESSION['user']['client_id'])) {
            UrlHelper::redirect('index.php?controller=LoginController');
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate input
            $errors = $this->validateCategoryData($_POST, $clientId);

            if (!empty($errors)) {
                $this->redirectWithToast(implode(', ', $errors), 'error', UrlHelper::url('course-categories'));
                return;
            }

            // Prepare data
            $data = [
                'client_id' => $clientId,
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description'] ?? ''),
                'sort_order' => $this->courseCategoryModel->getNextSortOrder($clientId),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'created_by' => $currentUser['id']
            ];

            // Create category
            $result = $this->courseCategoryModel->createCategory($data);

            if ($result) {
                $this->redirectWithToast('Course category created successfully!', 'success', UrlHelper::url('course-categories'));
            } else {
                $this->redirectWithToast('Failed to create course category.', 'error', UrlHelper::url('course-categories'));
            }
        } else {
            // Show create form
            require 'views/course_categories_create.php';
        }
    }

    /**
     * Edit category
     */
    public function edit($id = null) {
        if (!isset($_SESSION['user']['client_id'])) {
            UrlHelper::redirect('index.php?controller=LoginController');
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];
        $systemRole = $currentUser['system_role'];

        // Get category ID
        if (!$id && isset($_GET['id'])) {
            $id = $_GET['id'];
        }

        if (!$id) {
            $this->redirectWithToast('Category ID is required.', 'error', UrlHelper::url('course-categories'));
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_GET['client_id'])) {
            $filterClientId = intval($_GET['client_id']);
        }

        // Get category
        $category = $this->courseCategoryModel->getCategoryById($id, $filterClientId);

        if (!$category) {
            $this->redirectWithToast('Category not found.', 'error', UrlHelper::url('course-categories'));
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate input
            $errors = $this->validateCategoryData($_POST, $filterClientId, $id);

            if (!empty($errors)) {
                $this->redirectWithToast(implode(', ', $errors), 'error', UrlHelper::url('course-categories'));
                return;
            }

            // Prepare data
            $data = [
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description'] ?? ''),
                'sort_order' => intval($_POST['sort_order'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'updated_by' => $currentUser['id']
            ];

            // Update category
            $result = $this->courseCategoryModel->updateCategory($id, $data);

            if ($result) {
                $this->redirectWithToast('Course category updated successfully!', 'success', UrlHelper::url('course-categories'));
            } else {
                $this->redirectWithToast('Failed to update course category.', 'error', UrlHelper::url('course-categories'));
            }
        } else {
            // Show edit form
            require 'views/course_categories_edit.php';
        }
    }

    /**
     * Delete category
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

        // Get category ID
        if (!$id && isset($_GET['id'])) {
            $id = $_GET['id'];
        }

        if (!$id) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Category ID is required']);
                return;
            }
            $this->redirectWithToast('Category ID is required.', 'error', UrlHelper::url('course-categories'));
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_GET['client_id'])) {
            $filterClientId = intval($_GET['client_id']);
        }

        // Get category
        $category = $this->courseCategoryModel->getCategoryById($id, $filterClientId);

        if (!$category) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Category not found']);
                return;
            }
            $this->redirectWithToast('Category not found.', 'error', UrlHelper::url('course-categories'));
            return;
        }

        // Check if category has subcategories
        if ($category['subcategory_count'] > 0) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Cannot delete category with subcategories. Please delete subcategories first.']);
                return;
            }
            $this->redirectWithToast('Cannot delete category with subcategories. Please delete subcategories first.', 'error', UrlHelper::url('course-categories'));
            return;
        }

        // Delete category
        $result = $this->courseCategoryModel->deleteCategory($id);

        if ($result) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => true, 'message' => 'Course category deleted successfully!']);
                return;
            }
            $this->redirectWithToast('Course category deleted successfully!', 'success', UrlHelper::url('course-categories'));
        } else {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to delete course category.']);
                return;
            }
            $this->redirectWithToast('Failed to delete course category.', 'error', UrlHelper::url('course-categories'));
        }
    }

    /**
     * Toggle category status
     */
    public function toggleStatus($id = null) {
        if (!isset($_SESSION['user']['client_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];
        $systemRole = $currentUser['system_role'];

        // Get category ID
        if (!$id && isset($_POST['id'])) {
            $id = $_POST['id'];
        }

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Category ID is required']);
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_POST['client_id'])) {
            $filterClientId = intval($_POST['client_id']);
        }

        // Get category
        $category = $this->courseCategoryModel->getCategoryById($id, $filterClientId);

        if (!$category) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            return;
        }

        // Toggle status
        $result = $this->courseCategoryModel->toggleCategoryStatus($id);

        if ($result) {
            $newStatus = $category['is_active'] ? 0 : 1;
            $statusText = $newStatus ? 'Active' : 'Inactive';
            
            echo json_encode([
                'success' => true,
                'message' => "Category status updated to $statusText",
                'new_status' => $newStatus,
                'status_text' => $statusText
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update category status']);
        }
    }

    /**
     * Load add category modal content
     */
    public function loadAddModal() {
        if (!isset($_SESSION['user']['client_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];

        // Get next sort order
        $nextSortOrder = $this->courseCategoryModel->getNextSortOrder($clientId);

        echo json_encode([
            'success' => true,
            'mode' => 'add',
            'next_sort_order' => $nextSortOrder
        ]);
    }

    /**
     * Load edit category modal content
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

        $categoryId = $_GET['id'] ?? null;

        if (!$categoryId) {
            http_response_code(400);
            echo json_encode(['error' => 'Category ID is required']);
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_GET['client_id'])) {
            $filterClientId = intval($_GET['client_id']);
        }

        // Get category
        $category = $this->courseCategoryModel->getCategoryById($categoryId, $filterClientId);

        if (!$category) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            return;
        }

        echo json_encode([
            'success' => true,
            'mode' => 'edit',
            'category' => $category
        ]);
    }

    /**
     * Submit add category modal
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
        $errors = $this->validateCategoryData($_POST, $clientId);

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
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'created_by' => $currentUser['id']
        ];

        // Create category
        $result = $this->courseCategoryModel->createCategory($data);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Course category created successfully!',
                'redirect' => UrlHelper::url('course-categories')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create course category.'
            ]);
        }
    }

    /**
     * Submit edit category modal
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

        $categoryId = $_POST['category_id'] ?? null;

        if (!$categoryId) {
            echo json_encode([
                'success' => false,
                'message' => 'Category ID is required'
            ]);
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_POST['client_id'])) {
            $filterClientId = intval($_POST['client_id']);
        }

        // Validate input
        $errors = $this->validateCategoryData($_POST, $filterClientId, $categoryId);

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
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'updated_by' => $currentUser['id']
        ];

        // Update category
        $result = $this->courseCategoryModel->updateCategory($categoryId, $data);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Course category updated successfully!',
                'redirect' => UrlHelper::url('course-categories')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update course category.'
            ]);
        }
    }

    /**
     * Submit category form (unified for add/edit)
     */
    public function submitCategory() {
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
        $categoryId = $_POST['category_id'] ?? null;
        $mode = $categoryId ? 'edit' : 'add';

        if ($mode === 'edit' && !$categoryId) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Category ID is required for editing']);
                return;
            }
            $this->redirectWithToast('Category ID is required for editing.', 'error', UrlHelper::url('course-categories'));
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_POST['client_id'])) {
            $filterClientId = intval($_POST['client_id']);
        }

        // Validate input
        $errors = $this->validateCategoryData($_POST, $filterClientId, $categoryId);

        if (!empty($errors)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => implode(', ', $errors),
                    'errors' => $errors
                ]);
                return;
            }
            $this->redirectWithToast(implode(', ', $errors), 'error', UrlHelper::url('course-categories'));
            return;
        }

        if ($mode === 'add') {
            // Create category
            $data = [
                'client_id' => $clientId,
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description'] ?? ''),
                'sort_order' => intval($_POST['sort_order'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'created_by' => $currentUser['id']
            ];

            $result = $this->courseCategoryModel->createCategory($data);
            $message = 'Course category created successfully!';
        } else {
            // Update category
            $data = [
                'name' => trim($_POST['name']),
                'description' => trim($_POST['description'] ?? ''),
                'sort_order' => intval($_POST['sort_order'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'updated_by' => $currentUser['id']
            ];

            $result = $this->courseCategoryModel->updateCategory($categoryId, $data);
            $message = 'Course category updated successfully!';
        }

        if ($result) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => $message
                ]);
                return;
            }
            $this->redirectWithToast($message, 'success', UrlHelper::url('course-categories'));
        } else {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to ' . ($mode === 'add' ? 'create' : 'update') . ' course category.'
                ]);
                return;
            }
            $this->redirectWithToast('Failed to ' . ($mode === 'add' ? 'create' : 'update') . ' course category.', 'error', UrlHelper::url('course-categories'));
        }
    }

    /**
     * Validate category data
     */
    private function validateCategoryData($data, $clientId, $excludeId = null) {
        $errors = [];

        // Validate name
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            $errors['name'] = 'Category name is required';
        } elseif (strlen($name) > 100) {
            $errors['name'] = 'Category name cannot exceed 100 characters';
        } elseif ($this->courseCategoryModel->checkCategoryNameExists($name, $clientId, $excludeId)) {
            $errors['name'] = 'A category with this name already exists';
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
     * API endpoint to get categories for dropdown
     */
    public function getCategoriesForDropdown() {
        if (!isset($_SESSION['user']['client_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];

        $categories = $this->courseCategoryModel->getActiveCategoriesForDropdown($clientId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
    }

    /**
     * Get single category by ID (AJAX)
     */
    public function get() {
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

        // Get category ID from URL parameter (legacy routing)
        $id = $_GET['id'] ?? null;
        if (!$id) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Category ID is required']);
                return;
            }
            $this->redirectWithToast('Category ID is required.', 'error', UrlHelper::url('course-categories'));
            return;
        }

        // Determine client ID for filtering
        $filterClientId = $clientId;
        if ($systemRole === 'super_admin' && isset($_GET['client_id'])) {
            $filterClientId = intval($_GET['client_id']);
        }

        // Get category
        $category = $this->courseCategoryModel->getCategoryById($id, $filterClientId);

        if (!$category) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Category not found']);
                return;
            }
            $this->redirectWithToast('Category not found.', 'error', UrlHelper::url('course-categories'));
            return;
        }

        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['success' => true, 'category' => $category]);
            return;
        }
        
        // For non-AJAX requests, redirect to edit page
        $this->redirectWithToast('Category found.', 'success', UrlHelper::url('course-categories'));
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