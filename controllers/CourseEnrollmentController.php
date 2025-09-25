<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'models/CourseEnrollmentModel.php';
require_once 'core/UrlHelper.php';

class CourseEnrollmentController {
    private $enrollmentModel;

    public function __construct() {
        $this->enrollmentModel = new CourseEnrollmentModel();
    }

    /**
     * Enroll user in a course
     */
    public function enroll() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // Check if request method is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        
        if (!$clientId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit;
        }

        // Validate that the user exists in the database
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND client_id = ?");
            $stmt->execute([$userId, $clientId]);
            $userExists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userExists) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'User not found. Please log in again.']);
                exit;
            }
        } catch (Exception $e) {
            error_log("CourseEnrollmentController: Error validating user: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error validating user session']);
            exit;
        }

        // Get course ID from POST data
        $courseId = $_POST['course_id'] ?? null;
        
        if (!$courseId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Course ID is required']);
            exit;
        }

        // Validate course ID
        if (!is_numeric($courseId) || $courseId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
            exit;
        }

        try {
            $result = $this->enrollmentModel->enrollUserInCourse($userId, $courseId, $clientId);
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;

        } catch (Exception $e) {
            error_log("CourseEnrollmentController: Error in enroll: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'An error occurred while processing your enrollment']);
            exit;
        }
    }

    /**
     * Get user's enrollments
     */
    public function getUserEnrollments() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $status = $_GET['status'] ?? null; // Optional filter by status
        
        if (!$clientId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit;
        }

        try {
            $enrollments = $this->enrollmentModel->getUserEnrollments($userId, $clientId, $status);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'enrollments' => $enrollments]);
            exit;

        } catch (Exception $e) {
            error_log("CourseEnrollmentController: Error getting enrollments: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error retrieving enrollments']);
            exit;
        }
    }

    /**
     * Check enrollment status for a course
     */
    public function checkEnrollment() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $courseId = $_GET['course_id'] ?? null;
        
        if (!$clientId || !$courseId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            exit;
        }

        try {
            $isEnrolled = $this->enrollmentModel->isUserEnrolled($userId, $courseId, $clientId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'is_enrolled' => $isEnrolled]);
            exit;

        } catch (Exception $e) {
            error_log("CourseEnrollmentController: Error checking enrollment: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error checking enrollment status']);
            exit;
        }
    }
}
?>
