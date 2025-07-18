<?php
require_once 'models/CourseApplicabilityModel.php';
require_once 'models/CourseModel.php';
require_once 'models/UserModel.php';
require_once 'models/CustomFieldModel.php';
require_once 'config/Localization.php';
require_once 'core/UrlHelper.php';

class CourseApplicabilityController {
    private $applicabilityModel;
    private $courseModel;
    private $userModel;
    private $customFieldModel;

    public function __construct() {
        $this->applicabilityModel = new CourseApplicabilityModel();
        $this->courseModel = new CourseModel();
        $this->userModel = new UserModel();
        $this->customFieldModel = new CustomFieldModel();
    }

    // Main page: show applicability management UI
    public function index() {
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $courses = $this->courseModel->getAllCourses($clientId);
        $customFields = $this->customFieldModel->getCustomFieldsByClient($clientId);
        $users = $this->userModel->getUsersByClient($clientId);
        require 'views/course_applicability.php';
    }

    // Fetch applicability rules for a course (AJAX)
    public function getApplicability() {
        $courseId = $_GET['course_id'] ?? null;
        if (!$courseId) {
            echo json_encode(['success' => false, 'message' => 'Course ID required']);
            exit;
        }
        $rules = $this->applicabilityModel->getApplicability($courseId);
        $course = $this->courseModel->getCourseById($courseId);
        $courseName = $course['name'] ?? '';
        foreach ($rules as &$rule) {
            $rule['course_name'] = $courseName;
        }
        echo json_encode(['success' => true, 'rules' => $rules]);
        exit;
    }

    // Assign applicability (AJAX)
    public function assign() {
        $courseId = $_POST['course_id'] ?? null;
        $type = $_POST['applicability_type'] ?? null;
        $data = $_POST;
        if (!$courseId) {
            echo json_encode(['success' => false, 'message' => 'Please select a course.']);
            exit;
        }
        if (!$type) {
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            exit;
        }
        $result = $this->applicabilityModel->assignApplicability($courseId, $type, $data);
        if ($result === 'duplicate') {
            echo json_encode(['success' => false, 'message' => 'This applicability rule already exists for the selected course.']);
            exit;
        }
        echo json_encode(['success' => $result]);
        exit;
    }

    // Remove applicability (AJAX)
    public function remove() {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing ID']);
            exit;
        }
        $result = $this->applicabilityModel->removeApplicability($id);
        echo json_encode(['success' => $result]);
        exit;
    }

    // Fetch users by custom field value (AJAX)
    public function getUsersByCustomField() {
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $fieldId = $_GET['field_id'] ?? null;
        $value = $_GET['value'] ?? null;
        if (!$fieldId || !$value) {
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            exit;
        }
        $users = $this->userModel->getUsersByCustomField($clientId, $fieldId, $value);
        echo json_encode(['success' => true, 'users' => $users]);
        exit;
    }

    // AJAX: Search users by name/email for autocomplete (paginated)
    public function searchUsers() {
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $query = $_GET['query'] ?? '';
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $users = $this->userModel->searchUsersByClient($clientId, $query, $limit + 1, $offset); // fetch one extra for has_more
        $hasMore = count($users) > $limit;
        if ($hasMore) array_pop($users);
        $result = array_map(function($u) {
            return [
                'id' => $u['id'],
                'name' => $u['full_name'],
                'email' => $u['email']
            ];
        }, $users);
        echo json_encode(['success' => true, 'users' => $result, 'has_more' => $hasMore]);
        exit;
    }
} 