<?php
require_once 'models/MyCoursesModel.php';
require_once 'core/UrlHelper.php';
require_once 'config/Localization.php';

class MyCoursesController {
    private $myCoursesModel;

    public function __construct() {
        $this->myCoursesModel = new MyCoursesModel();
    }

    // Render My Courses page
    public function index() {
        if (!isset($_SESSION['user']['id'])) {
            UrlHelper::redirect('login');
        }
        require 'views/my_courses.php';
    }

    // AJAX: Get user courses (with status, search, pagination)
    public function getUserCourses() {
        if (!isset($_SESSION['user']['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $userId = $_SESSION['user']['id'];
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = intval($_GET['per_page'] ?? 12);
        $courses = $this->myCoursesModel->getUserCourses($userId, $status, $search, $page, $perPage);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'courses' => $courses]);
        exit;
    }
} 