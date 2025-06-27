<?php

require_once 'models/EventModel.php';
require_once 'controllers/BaseController.php';

class EventController extends BaseController {
    private $eventModel;

    public function __construct() {
        $this->eventModel = new EventModel();
    }

    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Display events management page
     */
    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        // Check user permissions
        $systemRole = $_SESSION['user']['system_role'] ?? '';
        if (!in_array($systemRole, ['super_admin', 'admin', 'instructor'])) {
            $this->toastError('You do not have permission to access this page.', 'index.php?controller=DashboardController');
            return;
        }

        // Set page title and load view
        $pageTitle = 'Event Management';
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => 'index.php?controller=DashboardController'],
            ['title' => 'Manage Portal', 'url' => 'index.php?controller=ManagePortalController'],
            ['title' => 'Event Management', 'url' => '']
        ];

        // Check permissions for global events
        $canCreateGlobal = in_array($systemRole, ['super_admin', 'admin']);

        require_once 'views/events.php';
    }

    /**
     * Get events via AJAX
     */
    public function getEvents() {
        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit();
        }

        try {
            $clientId = $_SESSION['user']['client_id'];
            $limit = 10;
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $offset = ($page - 1) * $limit;

            // Get search and filter parameters
            $search = trim($_GET['search'] ?? '');
            $filters = [];

            if (!empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }

            if (!empty($_GET['event_type'])) {
                $filters['event_type'] = $_GET['event_type'];
            }

            if (!empty($_GET['audience_type'])) {
                $filters['audience_type'] = $_GET['audience_type'];
            }

            if (!empty($_GET['date_from'])) {
                $filters['date_from'] = $_GET['date_from'];
            }

            if (!empty($_GET['date_to'])) {
                $filters['date_to'] = $_GET['date_to'];
            }

            // Get events from database
            $events = $this->eventModel->getAllEvents($limit, $offset, $search, $filters, $clientId);
            $totalEvents = count($this->eventModel->getAllEvents(999999, 0, $search, $filters, $clientId));
            $totalPages = ceil($totalEvents / $limit);

            $response = [
                'success' => true,
                'events' => $events,
                'totalEvents' => $totalEvents,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalEvents' => $totalEvents
                ]
            ];

            header('Content-Type: application/json');
            echo json_encode($response);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error loading events: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Create new event
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request method.', 'index.php?controller=EventController');
            return;
        }

        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        try {
            error_log('--- EventController::create() called ---');
            error_log('POST data: ' . print_r($_POST, true));
            error_log('SESSION data: ' . print_r($_SESSION, true));

            $clientId = $_SESSION['user']['client_id'];
            $userId = $_SESSION['user']['id'];

            // Server-side validation
            $errors = [];

            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $errors[] = 'Event title is required.';
            }

            $description = trim($_POST['description'] ?? '');
            if (empty($description)) {
                $errors[] = 'Event description is required.';
            }

            $eventType = $_POST['event_type'] ?? '';
            if (!in_array($eventType, ['live_class', 'webinar', 'deadline', 'maintenance', 'meeting', 'workshop'])) {
                $errors[] = 'Invalid event type.';
            }

            $audienceType = $_POST['audience_type'] ?? '';
            if (!in_array($audienceType, ['global', 'course_specific', 'group_specific'])) {
                $errors[] = 'Invalid audience type.';
            }

            $startDatetime = $_POST['start_datetime'] ?? '';
            if (empty($startDatetime)) {
                $errors[] = 'Start date and time is required.';
            } else {
                $startTime = strtotime($startDatetime);
                if ($startTime < time() - 300) { // Allow 5 minutes buffer
                    $errors[] = 'Start date cannot be in the past.';
                }
            }

            $endDatetime = $_POST['end_datetime'] ?? '';
            if (!empty($endDatetime) && !empty($startDatetime)) {
                $endTime = strtotime($endDatetime);
                $startTime = strtotime($startDatetime);
                if ($endTime <= $startTime) {
                    $errors[] = 'End date must be after start date.';
                }
            }

            $sendReminderBefore = $_POST['send_reminder_before'] ?? 0;
            if (!is_numeric($sendReminderBefore) || $sendReminderBefore < 0) {
                $sendReminderBefore = 0;
            }

            error_log('Validation errors: ' . print_r($errors, true));

            if (!empty($errors)) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => implode(' ', $errors)
                    ]);
                    error_log('Returning validation errors: ' . implode(' ', $errors));
                    exit;
                } else {
                    $this->toastError(implode(' ', $errors), 'index.php?controller=EventController');
                    return;
                }
            }

            // Prepare event data
            $eventData = [
                'client_id' => $clientId,
                'title' => $title,
                'description' => $description,
                'event_type' => $eventType,
                'event_link' => trim($_POST['event_link'] ?? ''),
                'start_datetime' => $startDatetime,
                'end_datetime' => !empty($endDatetime) ? $endDatetime : null,
                'audience_type' => $audienceType,
                'location' => trim($_POST['location'] ?? ''),
                'enable_rsvp' => isset($_POST['enable_rsvp']) ? 1 : 0,
                'send_reminder_before' => (int)$sendReminderBefore,
                'status' => 'active',
                'created_by' => $userId
            ];

            error_log('Event data to insert: ' . print_r($eventData, true));

            // Create event
            $eventId = $this->eventModel->createEvent($eventData);
            error_log('EventModel::createEvent() returned: ' . print_r($eventId, true));

            if ($eventId) {
                // Handle course/group specific audiences
                if ($audienceType === 'course_specific' && !empty($_POST['target_courses'])) {
                    $targetCourses = $_POST['target_courses'];
                    error_log('Adding course-specific audiences: ' . print_r($targetCourses, true));
                    foreach ($targetCourses as $courseId) {
                        $this->eventModel->addEventAudience($eventId, 'course', $courseId, $clientId);
                    }
                }

                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Event created successfully!'
                    ]);
                    error_log('Event created successfully!');
                    exit;
                } else {
                    $this->toastSuccess('Event created successfully!', 'index.php?controller=EventController');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to create event. Please try again.'
                    ]);
                    error_log('Failed to create event.');
                    exit;
                } else {
                    $this->toastError('Failed to create event. Please try again.', 'index.php?controller=EventController');
                }
            }

        } catch (Exception $e) {
            error_log("Event creation error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'An unexpected error occurred. Please try again.'
                ]);
                exit;
            } else {
                $this->toastError('An unexpected error occurred. Please try again.', 'index.php?controller=EventController');
            }
        }
    }

    /**
     * Get event data for edit modal (AJAX request)
     */
    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Event ID is required']);
                exit;
            }
            $this->toastError('Event ID is required.', 'index.php?controller=EventController');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
        $event = $this->eventModel->getEventById($id, $clientId);

        if (!$event) {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Event not found']);
                exit;
            }
            $this->toastError('Event not found.', 'index.php?controller=EventController');
            return;
        }

        // Get event audiences
        $audiences = $this->eventModel->getEventAudiences($id);

        // Return JSON data for AJAX request
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'event' => $event,
                'audiences' => $audiences
            ]);
            exit;
        }

        // For non-AJAX requests, redirect to main page
        $this->toastInfo('Use the edit button to modify events.', 'index.php?controller=EventController');
    }

    /**
     * Update event
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request method.', 'index.php?controller=EventController');
            return;
        }

        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        try {
            error_log('--- EventController::update() called ---');
            error_log('POST data: ' . print_r($_POST, true));
            error_log('SESSION data: ' . print_r($_SESSION, true));

            $clientId = $_SESSION['user']['client_id'];
            $userId = $_SESSION['user']['id'];
            $eventId = $_POST['event_id'] ?? null;

            if (!$eventId) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Event ID is required.'
                    ]);
                    exit;
                }
                $this->toastError('Event ID is required.', 'index.php?controller=EventController');
                return;
            }

            // Server-side validation (same as create)
            $errors = [];

            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $errors[] = 'Event title is required.';
            }

            $description = trim($_POST['description'] ?? '');
            if (empty($description)) {
                $errors[] = 'Event description is required.';
            }

            $eventType = $_POST['event_type'] ?? '';
            if (!in_array($eventType, ['live_class', 'webinar', 'deadline', 'maintenance', 'meeting', 'workshop'])) {
                $errors[] = 'Invalid event type.';
            }

            $audienceType = $_POST['audience_type'] ?? '';
            if (!in_array($audienceType, ['global', 'course_specific', 'group_specific'])) {
                $errors[] = 'Invalid audience type.';
            }

            $startDatetime = $_POST['start_datetime'] ?? '';
            if (empty($startDatetime)) {
                $errors[] = 'Start date and time is required.';
            }

            $endDatetime = $_POST['end_datetime'] ?? '';
            if (!empty($endDatetime) && !empty($startDatetime)) {
                $endTime = strtotime($endDatetime);
                $startTime = strtotime($startDatetime);
                if ($endTime <= $startTime) {
                    $errors[] = 'End date must be after start date.';
                }
            }

            $sendReminderBefore = $_POST['send_reminder_before'] ?? 0;
            if (!is_numeric($sendReminderBefore) || $sendReminderBefore < 0) {
                $sendReminderBefore = 0;
            }

            error_log('Validation errors: ' . print_r($errors, true));

            if (!empty($errors)) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => implode(' ', $errors)
                    ]);
                    error_log('Returning validation errors: ' . implode(' ', $errors));
                    exit;
                } else {
                    $this->toastError(implode(' ', $errors), 'index.php?controller=EventController');
                    return;
                }
            }

            // Prepare event data
            $eventData = [
                'client_id' => $clientId,
                'title' => $title,
                'description' => $description,
                'event_type' => $eventType,
                'event_link' => trim($_POST['event_link'] ?? ''),
                'start_datetime' => $startDatetime,
                'end_datetime' => !empty($endDatetime) ? $endDatetime : null,
                'audience_type' => $audienceType,
                'location' => trim($_POST['location'] ?? ''),
                'enable_rsvp' => isset($_POST['enable_rsvp']) ? 1 : 0,
                'send_reminder_before' => (int)$sendReminderBefore,
                'status' => $_POST['status'] ?? 'active',
                'updated_by' => $userId
            ];

            error_log('Event data to update: ' . print_r($eventData, true));

            // Update event
            $result = $this->eventModel->updateEvent($eventId, $eventData);
            error_log('EventModel::updateEvent() returned: ' . print_r($result, true));

            if ($result) {
                // Update audiences
                $this->eventModel->removeEventAudiences($eventId, $clientId);

                if ($audienceType === 'course_specific' && !empty($_POST['target_courses'])) {
                    $targetCourses = $_POST['target_courses'];
                    error_log('Adding course-specific audiences: ' . print_r($targetCourses, true));
                    foreach ($targetCourses as $courseId) {
                        $this->eventModel->addEventAudience($eventId, 'course', $courseId, $clientId);
                    }
                }

                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Event updated successfully!'
                    ]);
                    error_log('Event updated successfully!');
                    exit;
                } else {
                    $this->toastSuccess('Event updated successfully!', 'index.php?controller=EventController');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update event. Please try again.'
                    ]);
                    error_log('Failed to update event.');
                    exit;
                } else {
                    $this->toastError('Failed to update event. Please try again.', 'index.php?controller=EventController');
                }
            }

        } catch (Exception $e) {
            error_log("Event update error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'An unexpected error occurred. Please try again.'
                ]);
                exit;
            } else {
                $this->toastError('An unexpected error occurred. Please try again.', 'index.php?controller=EventController');
            }
        }
    }

    /**
     * Update event status only
     */
    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                exit;
            }
            $this->toastError('Invalid request method.', 'index.php?controller=EventController');
            return;
        }

        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
                exit;
            }
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        try {
            $clientId = $_SESSION['user']['client_id'];
            $eventId = $_POST['event_id'] ?? null;
            $status = $_POST['status'] ?? null;

            if (!$eventId) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
                    exit;
                }
                $this->toastError('Event ID is required.', 'index.php?controller=EventController');
                return;
            }

            if (!$status || !in_array($status, ['active', 'draft', 'cancelled', 'completed', 'archived'])) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Invalid status']);
                    exit;
                }
                $this->toastError('Invalid status.', 'index.php?controller=EventController');
                return;
            }

            // Update event status
            $result = $this->eventModel->updateEventStatus($eventId, $status, $clientId);

            if ($result) {
                $statusMessages = [
                    'active' => 'Event activated successfully!',
                    'draft' => 'Event saved as draft successfully!',
                    'cancelled' => 'Event cancelled successfully!',
                    'completed' => 'Event marked as completed successfully!',
                    'archived' => 'Event archived successfully!'
                ];

                $message = $statusMessages[$status] ?? 'Event status updated successfully!';

                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => $message
                    ]);
                    exit;
                } else {
                    $this->toastSuccess($message, 'index.php?controller=EventController');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update event status. Please try again.'
                    ]);
                    exit;
                } else {
                    $this->toastError('Failed to update event status. Please try again.', 'index.php?controller=EventController');
                }
            }

        } catch (Exception $e) {
            error_log("Event status update error: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'An unexpected error occurred. Please try again.'
                ]);
                exit;
            } else {
                $this->toastError('An unexpected error occurred. Please try again.', 'index.php?controller=EventController');
            }
        }
    }

    /**
     * Delete event
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request method.', 'index.php?controller=EventController');
            return;
        }

        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        try {
            $clientId = $_SESSION['user']['client_id'];
            $eventId = $_POST['event_id'] ?? null;

            if (!$eventId) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Event ID is required.'
                    ]);
                    exit;
                }
                $this->toastError('Event ID is required.', 'index.php?controller=EventController');
                return;
            }

            // Delete event
            $result = $this->eventModel->deleteEvent($eventId, $clientId);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Event deleted successfully!'
                    ]);
                    exit;
                } else {
                    $this->toastSuccess('Event deleted successfully!', 'index.php?controller=EventController');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to delete event. Please try again.'
                    ]);
                    exit;
                } else {
                    $this->toastError('Failed to delete event. Please try again.', 'index.php?controller=EventController');
                }
            }

        } catch (Exception $e) {
            error_log("Event deletion error: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'An unexpected error occurred. Please try again.'
                ]);
                exit;
            } else {
                $this->toastError('An unexpected error occurred. Please try again.', 'index.php?controller=EventController');
            }
        }
    }

    /**
     * Submit RSVP
     */
    public function rsvp() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }

        try {
            $clientId = $_SESSION['user']['client_id'];
            $userId = $_SESSION['user']['id'];
            $eventId = $_POST['event_id'] ?? null;
            $response = $_POST['response'] ?? null;

            if (!$eventId || !$response) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Event ID and response are required']);
                exit;
            }

            if (!in_array($response, ['yes', 'no', 'maybe'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid RSVP response']);
                exit;
            }

            $rsvpData = [
                'event_id' => $eventId,
                'user_id' => $userId,
                'client_id' => $clientId,
                'response' => $response
            ];

            $result = $this->eventModel->submitRSVP($rsvpData);

            if ($result) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'RSVP submitted successfully!'
                ]);
            } else {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to submit RSVP. Please try again.'
                ]);
            }

        } catch (Exception $e) {
            error_log("RSVP submission error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.'
            ]);
        }
        exit;
    }

    /**
     * Get event attendees/RSVPs
     */
    public function attendees() {
        $eventId = $_GET['event_id'] ?? null;
        if (!$eventId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Event ID is required']);
            exit;
        }

        // Check if user is logged in
        if (!isset($_SESSION['user']['client_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }

        try {
            $clientId = $_SESSION['user']['client_id'];
            $rsvps = $this->eventModel->getEventRSVPs($eventId, $clientId);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'rsvps' => $rsvps
            ]);

        } catch (Exception $e) {
            error_log("Get attendees error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.'
            ]);
        }
        exit;
    }
}
