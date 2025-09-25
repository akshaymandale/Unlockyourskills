<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'models/SearchCoursesModel.php';
require_once 'core/UrlHelper.php';
require_once 'config/Localization.php';
require_once 'core/IdEncryption.php';

class SearchCoursesController {
    private $searchCoursesModel;

    public function __construct() {
        $this->searchCoursesModel = new SearchCoursesModel();
    }

    // Render Search Courses page
    public function index() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }
        require 'views/search_courses.php';
    }

    // AJAX: Get searchable courses (where show_in_search = 'yes')
    public function getCourses() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $search = $_GET['search'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = intval($_GET['per_page'] ?? 12);
        
        if (!$clientId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit;
        }
        
        try {
            $courses = $this->searchCoursesModel->getSearchableCourses($userId, $search, $page, $perPage, $clientId);
            
            // Add encrypted IDs for secure URLs
            if (is_array($courses)) {
                foreach ($courses as &$course) {
                    if (isset($course['id'])) {
                        $course['encrypted_id'] = IdEncryption::encrypt($course['id']);
                    }
                }
            }
        } catch (Exception $e) {
            error_log("SearchCoursesController: Error getting searchable courses: " . $e->getMessage());
            error_log("SearchCoursesController: Stack trace: " . $e->getTraceAsString());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error loading courses: ' . $e->getMessage()]);
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'courses' => $courses]);
        exit;
    }

    // AJAX: Get total count of searchable courses (for pagination)
    public function getCount() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $search = $_GET['search'] ?? '';
        
        if (!$clientId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit;
        }
        
        $totalCount = $this->searchCoursesModel->getSearchableCoursesCount($userId, $search, $clientId);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'total' => $totalCount]);
        exit;
    }
}
