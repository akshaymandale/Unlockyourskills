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
        
        // Only get custom fields with field_type = 'select' for course applicability
        $allCustomFields = $this->customFieldModel->getCustomFieldsByClient($clientId);
        $customFields = array_filter($allCustomFields, function($field) {
            return $field['field_type'] === 'select';
        });
        
        $users = $this->userModel->getUsersByClient($clientId);
        require 'views/course_applicability.php';
    }

    // Fetch applicability rules for a course (AJAX)
    public function getApplicability() {
        $courseId = $_GET['course_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;
        if (!$courseId) {
            echo json_encode(['success' => false, 'message' => 'Course ID required']);
            exit;
        }
        $rules = $this->applicabilityModel->getApplicability($courseId, $clientId);
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
        // Prevent any HTML output before JSON
        ob_start();
        header('Content-Type: application/json');
        ob_clean();
        
        // Suppress all errors and warnings to prevent HTML output
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);
        
        $courseId = $_POST['course_id'] ?? null;
        $type = $_POST['applicability_type'] ?? null;
        $data = $_POST;
        $clientId = $_SESSION['user']['client_id'] ?? null;
        
        if (!$courseId) {
            echo json_encode(['success' => false, 'message' => 'Please select a course.']);
            exit;
        }
        if (!$type) {
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            exit;
        }
        if (!$clientId) {
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit;
        }
        try {
            $result = $this->applicabilityModel->assignApplicability($courseId, $type, $data, $clientId);
            if ($result === 'duplicate') {
                echo json_encode(['success' => false, 'message' => 'This applicability rule already exists for the selected course.']);
                exit;
            }
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        } finally {
            // Restore error reporting settings
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
        exit;
    }

    // Remove applicability (AJAX)
    public function remove() {
        // Prevent any HTML output before JSON
        ob_start();
        header('Content-Type: application/json');
        ob_clean();
        
        // Suppress all errors and warnings to prevent HTML output
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);
        
        $id = $_POST['id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;
        try {
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Missing ID']);
                exit;
            }
            $result = $this->applicabilityModel->removeApplicability($id, $clientId);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        } finally {
            // Restore error reporting settings
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
        exit;
    }

    // Fetch users by custom field value (AJAX)
    public function getUsersByCustomField() {
        // Prevent any HTML output before JSON
        ob_start();
        header('Content-Type: application/json');
        ob_clean();
        
        // Suppress all errors and warnings to prevent HTML output
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);
        
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $fieldId = $_GET['field_id'] ?? null;
        $value = $_GET['value'] ?? null;
        try {
            if (!$fieldId || !$value) {
                echo json_encode(['success' => false, 'message' => 'Missing data']);
                exit;
            }
            $users = $this->userModel->getUsersByCustomField($clientId, $fieldId, $value);
            echo json_encode(['success' => true, 'users' => $users]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        } finally {
            // Restore error reporting settings
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
        exit;
    }

    // AJAX: Search users by name/email for autocomplete (paginated)
    public function searchUsers() {
        // Prevent any HTML output before JSON
        ob_start();
        header('Content-Type: application/json');
        ob_clean();
        
        // Suppress all errors and warnings to prevent HTML output
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);
        
        try {
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
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        } finally {
            // Restore error reporting settings
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
        exit;
    }

    // AJAX: Get users with applicability_type='user' for a course
    public function getApplicableUsers() {
        // Prevent any HTML output before JSON
        ob_start();
        header('Content-Type: application/json');
        ob_clean();
        
        // Suppress all errors and warnings to prevent HTML output
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);
        
        try {
            $courseId = $_GET['course_id'] ?? null;
            if (!$courseId) {
                echo json_encode(['success' => false, 'message' => 'Course ID required']);
                exit;
            }
            $rules = $this->applicabilityModel->getApplicability($courseId);
            $userIds = [];
            foreach ($rules as $rule) {
                if ($rule['applicability_type'] === 'user' && !empty($rule['user_id'])) {
                    $userIds[] = $rule['user_id'];
                }
            }
            // Optionally, return user details for summary
            $users = [];
            if (!empty($userIds)) {
                $users = $this->userModel->getUsersByIds($userIds);
            }
            echo json_encode(['success' => true, 'user_ids' => $userIds, 'users' => $users]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        } finally {
            // Restore error reporting settings
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
        exit;
    }
} 