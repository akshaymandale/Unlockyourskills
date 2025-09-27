<?php

require_once 'models/AnnouncementModel.php';
require_once 'models/CustomFieldModel.php';
require_once 'controllers/BaseController.php';

class AnnouncementController extends BaseController {
    private $announcementModel;
    private $customFieldModel;

    public function __construct() {
        $this->announcementModel = new AnnouncementModel();
        $this->customFieldModel = new CustomFieldModel();
    }

    /**
     * User-facing announcements page
     */
    public function viewAnnouncements() {
        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
        $userId = $_SESSION['user']['id'];

        // Set page title and include view
        $pageTitle = 'My Announcements';
        include 'views/my_announcements.php';
    }

    /**
     * Main announcement management page
     */
    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        // Get client ID
        $clientId = $_SESSION['user']['client_id'];

        // Check user role permissions
        $systemRole = $_SESSION['user']['system_role'] ?? '';
        if (!in_array($systemRole, ['super_admin', 'admin'])) {
            $this->toastError('Access denied. Insufficient permissions.', 'index.php?controller=DashboardController');
            return;
        }

        // Get custom fields for group-specific announcements (exact same as opinion polls)
        $allCustomFields = $this->customFieldModel->getCustomFieldsByClient($clientId);
        $customFields = array_filter($allCustomFields, function($field) {
            return $field['field_type'] === 'select';
        });

        // Set page title and include view
        $pageTitle = 'Announcement Management';
        include 'views/announcements.php';
    }

    /**
     * Get user announcements with AJAX (for pagination and filtering)
     */
    public function getUserAnnouncements() {
        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }

        $clientId = $_SESSION['user']['client_id'];
        $userId = $_SESSION['user']['id'];

        try {
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $filters = [
                'urgency' => $_GET['urgency'] ?? '',
                'search' => $_GET['search'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
                'acknowledged' => $_GET['acknowledged'] ?? ''
            ];

            // Remove empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== '';
            });

            $announcements = $this->announcementModel->getUserAnnouncements($userId, $clientId, $filters, $page, $limit);
            $totalCount = $this->announcementModel->getUserAnnouncementsCount($userId, $clientId, $filters);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'announcements' => $announcements,
                'pagination' => [
                    'current_page' => $page,
                    'total_count' => $totalCount,
                    'per_page' => $limit,
                    'total_pages' => ceil($totalCount / $limit)
                ]
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load announcements: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get announcement by ID for user view
     */
    public function getAnnouncementById() {
        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }

        $clientId = $_SESSION['user']['client_id'];
        $userId = $_SESSION['user']['id'];
        $announcementId = $_GET['id'] ?? null;

        if (!$announcementId) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Announcement ID is required']);
            exit;
        }

        try {
            // Get announcement details
            $announcement = $this->announcementModel->getAnnouncementById($announcementId, $clientId);
            
            if (!$announcement) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Announcement not found']);
                exit;
            }

            // Check if user can view this announcement
            $userAnnouncements = $this->announcementModel->getUserAnnouncements($userId, $clientId, [], 1, 1000);
            $canView = false;
            
            foreach ($userAnnouncements as $userAnn) {
                if ($userAnn['id'] == $announcementId) {
                    $canView = true;
                    break;
                }
            }

            if (!$canView) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'You do not have permission to view this announcement']);
                exit;
            }

            // Check if user has acknowledged this announcement
            $userAcknowledged = $this->announcementModel->hasUserAcknowledged($announcementId, $userId, $clientId);
            $announcement['user_acknowledged'] = $userAcknowledged ? 1 : 0;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'announcement' => $announcement
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Failed to load announcement: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Acknowledge announcement
     */
    public function acknowledgeAnnouncement() {
        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }

        $clientId = $_SESSION['user']['client_id'];
        $userId = $_SESSION['user']['id'];
        $announcementId = $_POST['announcement_id'] ?? null;

        if (!$announcementId) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Announcement ID is required']);
            exit;
        }

        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $result = $this->announcementModel->acknowledgeAnnouncement($announcementId, $userId, $clientId, $ipAddress, $userAgent);

            if ($result) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Announcement acknowledged successfully'
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to acknowledge announcement'
                ]);
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Failed to acknowledge announcement: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get announcements with AJAX (for pagination and filtering)
     */
    public function getAnnouncements() {
        if (!$this->isAjaxRequest()) {
            $this->toastError('Invalid request.', 'index.php?controller=AnnouncementController');
            return;
        }

        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized access']);
            exit;
        }

        try {
            $clientId = $_SESSION['user']['client_id'];
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);

            // Build filters
            $filters = [];
            if (!empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            if (!empty($_GET['audience_type'])) {
                $filters['audience_type'] = $_GET['audience_type'];
            }
            if (!empty($_GET['urgency'])) {
                $filters['urgency'] = $_GET['urgency'];
            }
            if (!empty($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            if (!empty($_GET['date_from'])) {
                $filters['date_from'] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
                $filters['date_to'] = $_GET['date_to'];
            }

            // Role-based filtering
            $systemRole = $_SESSION['user']['system_role'] ?? '';
            if ($systemRole === 'user') {
                $filters['created_by'] = $_SESSION['user']['id'];
            }

            $announcements = $this->announcementModel->getAnnouncements($clientId, $filters, $page, $limit);
            $totalCount = $this->announcementModel->getAnnouncementsCount($clientId, $filters);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'announcements' => $announcements,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($totalCount / $limit),
                    'total_count' => $totalCount,
                    'per_page' => $limit
                ]
            ]);
            exit;

        } catch (Exception $e) {
            error_log("Get announcements error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to load announcements']);
            exit;
        }
    }

    /**
     * Create new announcement
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request method.', 'index.php?controller=AnnouncementController');
            return;
        }

        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        try {
            $clientId = $_SESSION['user']['client_id'];
            $userId = $_SESSION['user']['id'];
            $systemRole = $_SESSION['user']['system_role'] ?? '';

            // Server-side validation
            $errors = [];

            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $errors[] = 'Announcement title is required.';
            } elseif (strlen($title) > 255) {
                $errors[] = 'Title cannot exceed 255 characters.';
            }

            $body = trim($_POST['body'] ?? '');
            if (empty($body)) {
                $errors[] = 'Announcement body is required.';
            }

            $audienceType = $_POST['audience_type'] ?? '';
            if (!in_array($audienceType, ['global', 'group_specific'])) {
                $errors[] = 'Invalid audience type.';
            }

            // Role-based audience restrictions
            if ($systemRole === 'user' && $audienceType === 'global') {
                $errors[] = 'Regular users cannot create global announcements.';
            }

            // Validate custom fields for group_specific target audience
            if ($audienceType === 'group_specific') {
                $customFieldId = $_POST['custom_field_id'] ?? '';
                $customFieldValue = $_POST['custom_field_value'] ?? '';
                
                if (empty($customFieldId)) {
                    $errors[] = 'Custom field selection is required for group specific announcements.';
                }
                
                if (empty($customFieldValue)) {
                    $errors[] = 'Custom field value selection is required for group specific announcements.';
                }
            }

            $urgency = $_POST['urgency'] ?? 'info';
            if (!in_array($urgency, ['info', 'warning', 'urgent'])) {
                $errors[] = 'Invalid urgency level.';
            }

            $requireAcknowledgment = isset($_POST['require_acknowledgment']);
            $ctaLabel = trim($_POST['cta_label'] ?? '');
            $ctaUrl = trim($_POST['cta_url'] ?? '');

            $startDatetime = $_POST['start_datetime'] ?? null;
            $endDatetime = $_POST['end_datetime'] ?? null;

            // Validate dates
            if ($startDatetime && $endDatetime) {
                $startTime = strtotime($startDatetime);
                $endTime = strtotime($endDatetime);
                
                if ($startTime >= $endTime) {
                    $errors[] = 'End date must be after start date.';
                }
            }

            // Validate CTA
            if (!empty($ctaLabel) && empty($ctaUrl)) {
                $errors[] = 'CTA URL is required when CTA label is provided.';
            }
            if (!empty($ctaUrl) && !filter_var($ctaUrl, FILTER_VALIDATE_URL)) {
                $errors[] = 'Invalid CTA URL format.';
            }

            if (!empty($errors)) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => implode(' ', $errors)
                    ]);
                    exit;
                } else {
                    $this->toastError(implode(' ', $errors), 'index.php?controller=AnnouncementController');
                    return;
                }
            }

            // Determine status
            $status = 'draft';
            if ($startDatetime) {
                $status = (strtotime($startDatetime) <= time()) ? 'active' : 'scheduled';
            } else {
                $status = 'active';
            }

            // Prepare announcement data
            $announcementData = [
                'client_id' => $clientId,
                'title' => $title,
                'body' => $body,
                'audience_type' => $audienceType,
                'urgency' => $urgency,
                'require_acknowledgment' => $requireAcknowledgment,
                'cta_label' => $ctaLabel ?: null,
                'cta_url' => $ctaUrl ?: null,
                'start_datetime' => $startDatetime ?: null,
                'end_datetime' => $endDatetime ?: null,
                'status' => $status,
                'created_by' => $userId,
                'custom_field_id' => $audienceType === 'group_specific' ? ($_POST['custom_field_id'] ?? null) : null,
                'custom_field_value' => $audienceType === 'group_specific' ? ($_POST['custom_field_value'] ?? null) : null
            ];

            // Create announcement
            $announcementId = $this->announcementModel->createAnnouncement($announcementData);

            if ($announcementId) {
                $message = "Announcement created successfully!";
                if ($status === 'scheduled') {
                    $message .= " It will be published on " . date('M j, Y g:i A', strtotime($startDatetime)) . ".";
                }

                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => $message
                    ]);
                    exit;
                } else {
                    $this->toastSuccess($message, 'index.php?controller=AnnouncementController');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to create announcement. Please try again.'
                    ]);
                    exit;
                } else {
                    $this->toastError('Failed to create announcement. Please try again.', 'index.php?controller=AnnouncementController');
                }
            }

        } catch (Exception $e) {
            error_log("Announcement creation error: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'An unexpected error occurred. Please try again.'
                ]);
                exit;
            } else {
                $this->toastError('An unexpected error occurred. Please try again.', 'index.php?controller=AnnouncementController');
            }
        }
    }

    /**
     * Get announcement for editing
     */
    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Announcement ID is required']);
                exit;
            }
            $this->toastError('Announcement ID is required.', 'index.php?controller=AnnouncementController');
            return;
        }

        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthorized access']);
                exit;
            }
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
        $announcement = $this->announcementModel->getAnnouncementById($id, $clientId);
        
        if (!$announcement) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Announcement not found']);
                exit;
            }
            $this->toastError('Announcement not found.', 'index.php?controller=AnnouncementController');
            return;
        }

        // Check permissions
        $systemRole = $_SESSION['user']['system_role'] ?? '';
        $userId = $_SESSION['user']['id'];
        if ($systemRole === 'user' && $announcement['created_by'] != $userId) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Access denied']);
                exit;
            }
            $this->toastError('Access denied.', 'index.php?controller=AnnouncementController');
            return;
        }

        // No course-specific logic needed
        $courses = [];

        // Return JSON data for AJAX request
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'announcement' => $announcement,
                'courses' => $courses
            ]);
            exit;
        }

        // For non-AJAX requests, redirect to main page
        $this->toastInfo('Use the edit button to modify announcements.', 'index.php?controller=AnnouncementController');
    }

    /**
     * Update announcement
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request method.', 'index.php?controller=AnnouncementController');
            return;
        }

        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        try {
            $clientId = $_SESSION['user']['client_id'];
            $userId = $_SESSION['user']['id'];
            $systemRole = $_SESSION['user']['system_role'] ?? '';
            $announcementId = $_POST['announcement_id'] ?? null;

            if (!$announcementId) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Announcement ID is required.'
                    ]);
                    exit;
                }
                $this->toastError('Announcement ID is required.', 'index.php?controller=AnnouncementController');
                return;
            }

            // Check if announcement exists and user has permission
            $announcement = $this->announcementModel->getAnnouncementById($announcementId, $clientId);
            if (!$announcement) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Announcement not found.'
                    ]);
                    exit;
                }
                $this->toastError('Announcement not found.', 'index.php?controller=AnnouncementController');
                return;
            }

            // Check permissions
            if ($systemRole === 'user' && $announcement['created_by'] != $userId) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Access denied.'
                    ]);
                    exit;
                }
                $this->toastError('Access denied.', 'index.php?controller=AnnouncementController');
                return;
            }

            // Server-side validation (same as create)
            $errors = [];

            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $errors[] = 'Announcement title is required.';
            } elseif (strlen($title) > 255) {
                $errors[] = 'Title cannot exceed 255 characters.';
            }

            $body = trim($_POST['body'] ?? '');
            if (empty($body)) {
                $errors[] = 'Announcement body is required.';
            }

            $audienceType = $_POST['audience_type'] ?? '';
            if (!in_array($audienceType, ['global', 'group_specific'])) {
                $errors[] = 'Invalid audience type.';
            }

            // Role-based audience restrictions
            if ($systemRole === 'user' && $audienceType === 'global') {
                $errors[] = 'Regular users cannot create global announcements.';
            }

            // Validate custom fields for group_specific target audience
            if ($audienceType === 'group_specific') {
                $customFieldId = $_POST['custom_field_id'] ?? '';
                $customFieldValue = $_POST['custom_field_value'] ?? '';
                
                if (empty($customFieldId)) {
                    $errors[] = 'Custom field selection is required for group specific announcements.';
                }
                
                if (empty($customFieldValue)) {
                    $errors[] = 'Custom field value selection is required for group specific announcements.';
                }
            }

            $urgency = $_POST['urgency'] ?? 'info';
            if (!in_array($urgency, ['info', 'warning', 'urgent'])) {
                $errors[] = 'Invalid urgency level.';
            }

            $requireAcknowledgment = isset($_POST['require_acknowledgment']);
            $ctaLabel = trim($_POST['cta_label'] ?? '');
            $ctaUrl = trim($_POST['cta_url'] ?? '');

            $startDatetime = $_POST['start_datetime'] ?? null;
            $endDatetime = $_POST['end_datetime'] ?? null;

            // Validate dates
            if ($startDatetime && $endDatetime) {
                $startTime = strtotime($startDatetime);
                $endTime = strtotime($endDatetime);

                if ($startTime >= $endTime) {
                    $errors[] = 'End date must be after start date.';
                }
            }

            // Validate CTA
            if (!empty($ctaLabel) && empty($ctaUrl)) {
                $errors[] = 'CTA URL is required when CTA label is provided.';
            }
            if (!empty($ctaUrl) && !filter_var($ctaUrl, FILTER_VALIDATE_URL)) {
                $errors[] = 'Invalid CTA URL format.';
            }

            if (!empty($errors)) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => implode(' ', $errors)
                    ]);
                    exit;
                } else {
                    $this->toastError(implode(' ', $errors), 'index.php?controller=AnnouncementController');
                    return;
                }
            }

            // Determine status
            $status = $announcement['status']; // Keep existing status by default
            if ($startDatetime) {
                $status = (strtotime($startDatetime) <= time()) ? 'active' : 'scheduled';
            }

            // Prepare announcement data
            $announcementData = [
                'client_id' => $clientId,
                'title' => $title,
                'body' => $body,
                'audience_type' => $audienceType,
                'urgency' => $urgency,
                'require_acknowledgment' => $requireAcknowledgment,
                'cta_label' => $ctaLabel ?: null,
                'cta_url' => $ctaUrl ?: null,
                'start_datetime' => $startDatetime ?: null,
                'end_datetime' => $endDatetime ?: null,
                'status' => $status,
                'updated_by' => $userId,
                'custom_field_id' => $audienceType === 'group_specific' ? ($_POST['custom_field_id'] ?? null) : null,
                'custom_field_value' => $audienceType === 'group_specific' ? ($_POST['custom_field_value'] ?? null) : null
            ];

            // Update announcement
            $result = $this->announcementModel->updateAnnouncement($announcementId, $announcementData);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Announcement updated successfully!'
                    ]);
                    exit;
                } else {
                    $this->toastSuccess('Announcement updated successfully!', 'index.php?controller=AnnouncementController');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update announcement. Please try again.'
                    ]);
                    exit;
                } else {
                    $this->toastError('Failed to update announcement. Please try again.', 'index.php?controller=AnnouncementController');
                }
            }

        } catch (Exception $e) {
            error_log("Announcement update error: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'An unexpected error occurred. Please try again.'
                ]);
                exit;
            } else {
                $this->toastError('An unexpected error occurred. Please try again.', 'index.php?controller=AnnouncementController');
            }
        }
    }

    /**
     * Delete announcement
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request method.', 'index.php?controller=AnnouncementController');
            return;
        }

        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        try {
            $clientId = $_SESSION['user']['client_id'];
            $userId = $_SESSION['user']['id'];
            $systemRole = $_SESSION['user']['system_role'] ?? '';
            $announcementId = $_POST['id'] ?? null;

            if (!$announcementId) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Announcement ID is required.'
                    ]);
                    exit;
                }
                $this->toastError('Announcement ID is required.', 'index.php?controller=AnnouncementController');
                return;
            }

            // Check if announcement exists and user has permission
            $announcement = $this->announcementModel->getAnnouncementById($announcementId, $clientId);
            if (!$announcement) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Announcement not found.'
                    ]);
                    exit;
                }
                $this->toastError('Announcement not found.', 'index.php?controller=AnnouncementController');
                return;
            }

            // Check permissions
            if ($systemRole === 'user' && $announcement['created_by'] != $userId) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Access denied.'
                    ]);
                    exit;
                }
                $this->toastError('Access denied.', 'index.php?controller=AnnouncementController');
                return;
            }

            // Delete announcement (soft delete)
            $result = $this->announcementModel->deleteAnnouncement($announcementId, $clientId);

            if ($result) {
                $message = "Announcement deleted successfully!";

                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => $message
                    ]);
                    exit;
                } else {
                    $this->toastSuccess($message, 'index.php?controller=AnnouncementController');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to delete announcement.'
                    ]);
                    exit;
                } else {
                    $this->toastError('Failed to delete announcement.', 'index.php?controller=AnnouncementController');
                }
            }

        } catch (Exception $e) {
            error_log("Announcement deletion error: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'An unexpected error occurred. Please try again.'
                ]);
                exit;
            } else {
                $this->toastError('An unexpected error occurred. Please try again.', 'index.php?controller=AnnouncementController');
            }
        }
    }

    /**
     * Update announcement status
     */
    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request method.', 'index.php?controller=AnnouncementController');
            return;
        }

        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        try {
            $clientId = $_SESSION['user']['client_id'];
            $userId = $_SESSION['user']['id'];
            $systemRole = $_SESSION['user']['system_role'] ?? '';
            $announcementId = $_POST['announcement_id'] ?? null;
            $status = $_POST['status'] ?? null;

            if (!$announcementId || !$status) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Announcement ID and status are required.'
                    ]);
                    exit;
                }
                $this->toastError('Announcement ID and status are required.', 'index.php?controller=AnnouncementController');
                return;
            }

            // Validate status
            if (!in_array($status, ['draft', 'active', 'scheduled', 'expired', 'archived'])) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid status.'
                    ]);
                    exit;
                }
                $this->toastError('Invalid status.', 'index.php?controller=AnnouncementController');
                return;
            }

            // Check if announcement exists and user has permission
            $announcement = $this->announcementModel->getAnnouncementById($announcementId, $clientId);
            if (!$announcement) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Announcement not found.'
                    ]);
                    exit;
                }
                $this->toastError('Announcement not found.', 'index.php?controller=AnnouncementController');
                return;
            }

            // Check permissions
            if ($systemRole === 'user' && $announcement['created_by'] != $userId) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Access denied.'
                    ]);
                    exit;
                }
                $this->toastError('Access denied.', 'index.php?controller=AnnouncementController');
                return;
            }

            // Update status
            $result = $this->announcementModel->updateAnnouncementStatus($announcementId, $status, $clientId, $userId);

            if ($result) {
                $statusLabels = [
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'scheduled' => 'Scheduled',
                    'expired' => 'Expired',
                    'archived' => 'Archived'
                ];

                $message = "Announcement status updated to " . $statusLabels[$status] . "!";

                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => $message
                    ]);
                    exit;
                } else {
                    $this->toastSuccess($message, 'index.php?controller=AnnouncementController');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update announcement status.'
                    ]);
                    exit;
                } else {
                    $this->toastError('Failed to update announcement status.', 'index.php?controller=AnnouncementController');
                }
            }

        } catch (Exception $e) {
            error_log("Announcement status update error: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'An unexpected error occurred. Please try again.'
                ]);
                exit;
            } else {
                $this->toastError('An unexpected error occurred. Please try again.', 'index.php?controller=AnnouncementController');
            }
        }
    }

    /**
     * Check if the current request is an AJAX request
     */
    protected function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Manually trigger expiration updates for testing and admin purposes
     */
    public function updateExpired() {
        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        // Check user role permissions - only admins can trigger this
        $systemRole = $_SESSION['user']['system_role'] ?? '';
        if (!in_array($systemRole, ['super_admin', 'admin'])) {
            $this->toastError('Access denied. Insufficient permissions.', 'index.php?controller=DashboardController');
            return;
        }

        try {
            $clientId = $_SESSION['user']['client_id'];
            
            // Update expired announcements
            $result = $this->announcementModel->updateExpiredAnnouncements($clientId);
            
            // Get statistics
            $stats = $this->announcementModel->getExpiredAnnouncementsStats($clientId);
            
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Expired announcements updated successfully!',
                    'stats' => $stats
                ]);
                exit;
            } else {
                $this->toastSuccess('Expired announcements updated successfully!', 'index.php?controller=AnnouncementController');
            }

        } catch (Exception $e) {
            error_log("Expiration update error: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'An unexpected error occurred. Please try again.'
                ]);
                exit;
            } else {
                $this->toastError('An unexpected error occurred. Please try again.', 'index.php?controller=AnnouncementController');
            }
        }
    }
}
