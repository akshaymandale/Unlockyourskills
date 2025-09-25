<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'models/CourseEnrollmentModel.php';
require_once 'core/UrlHelper.php';
require_once 'config/Localization.php';
require_once 'core/IdEncryption.php';

class EnrollmentApprovalController {
    private $enrollmentModel;

    public function __construct() {
        $this->enrollmentModel = new CourseEnrollmentModel();
    }

    /**
     * Display the enrollment approval page
     */
    public function index() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }

        // Check if user has admin privileges
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            UrlHelper::redirect('dashboard');
        }

        require 'views/enrollment_approval.php';
    }

    /**
     * Get enrollment requests with optional status filtering
     */
    public function getEnrollments() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // Check if user has admin privileges
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
            exit;
        }

        $clientId = $_SESSION['user']['client_id'] ?? null;
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = intval($_GET['per_page'] ?? 10);
        $status = $_GET['status'] ?? 'pending'; // Default to pending if no status specified
        
        if (!$clientId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit;
        }

        try {
            $enrollments = $this->enrollmentModel->getEnrollmentsByStatus($clientId, $status, $page, $perPage);
            $totalCount = $this->enrollmentModel->getEnrollmentsCountByStatus($clientId, $status);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'enrollments' => $enrollments,
                'total' => $totalCount,
                'page' => $page,
                'per_page' => $perPage
            ]);
            exit;

        } catch (Exception $e) {
            error_log("EnrollmentApprovalController: Error getting enrollments: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error loading enrollments']);
            exit;
        }
    }

    /**
     * Get pending enrollment requests (legacy method for backward compatibility)
     */
    public function getPendingEnrollments() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // Check if user has admin privileges
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
            exit;
        }

        $clientId = $_SESSION['user']['client_id'] ?? null;
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = intval($_GET['per_page'] ?? 10);
        
        if (!$clientId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit;
        }

        try {
            $enrollments = $this->enrollmentModel->getPendingEnrollments($clientId, $page, $perPage);
            $totalCount = $this->enrollmentModel->getPendingEnrollmentsCount($clientId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'enrollments' => $enrollments,
                'total' => $totalCount,
                'page' => $page,
                'per_page' => $perPage
            ]);
            exit;

        } catch (Exception $e) {
            error_log("EnrollmentApprovalController: Error getting pending enrollments: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error loading enrollments']);
            exit;
        }
    }

    /**
     * Approve or reject an enrollment
     */
    public function updateEnrollmentStatus() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // Check if user has admin privileges
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $enrollmentId = $_POST['enrollment_id'] ?? null;
        $status = $_POST['status'] ?? null;
        $rejectionReason = $_POST['rejection_reason'] ?? null;
        $adminId = $_SESSION['user']['id'];

        if (!$enrollmentId || !$status || !in_array($status, ['approved', 'rejected'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        try {
            $result = $this->enrollmentModel->updateEnrollmentStatus($enrollmentId, $status, $adminId, $rejectionReason);
            
            header('Content-Type: application/json');
            echo json_encode($result);
            exit;

        } catch (Exception $e) {
            error_log("EnrollmentApprovalController: Error updating enrollment status: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error updating enrollment status']);
            exit;
        }
    }

    /**
     * Get enrollment statistics
     */
    public function getEnrollmentStats() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        // Check if user has admin privileges
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
            exit;
        }

        $clientId = $_SESSION['user']['client_id'] ?? null;
        
        if (!$clientId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit;
        }

        try {
            $stats = $this->enrollmentModel->getEnrollmentStatistics($clientId);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'stats' => $stats]);
            exit;

        } catch (Exception $e) {
            error_log("EnrollmentApprovalController: Error getting enrollment stats: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error loading statistics']);
            exit;
        }
    }
}
?>
