<?php
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

    // Render Course Details page
    public function details($id) {
        if (!isset($_SESSION['user']['id'])) {
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
        require 'views/my_course_details.php';
    }

    // Standard content viewer in a new tab (iframe page)
    public function viewContent() {
        if (!isset($_SESSION['user']['id'])) {
            UrlHelper::redirect('login');
        }
        $type = $_GET['type'] ?? 'iframe';
        $rawSrc = $_GET['src'] ?? '';
        $title = $_GET['title'] ?? 'Content';
        $src = $this->normalizeEmbedUrl($rawSrc, $type);
        // Expose $type to view
        $GLOBALS['type'] = $type;
        require 'views/content_viewer.php';
    }

    // Start an assessment/survey/feedback/assignment in a new tab
    public function start() {
        if (!isset($_SESSION['user']['id'])) {
            UrlHelper::redirect('login');
        }
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
                $payload = $vlr->getAssessmentByIdWithQuestions($id);
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
        $activityType = $type;
        $activity = $payload;
        require 'views/activity_player.php';
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