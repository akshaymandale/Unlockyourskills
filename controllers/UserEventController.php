<?php

require_once 'models/UserEventModel.php';
require_once 'controllers/BaseController.php';
require_once 'includes/permission_helper.php';

class UserEventController extends BaseController {
    private $userEventModel;

    public function __construct() {
        $this->userEventModel = new UserEventModel();
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Display user events page
     */
    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }
        
        $clientId = $_SESSION['user']['client_id'] ?? null;
        if (!$clientId) {
            $this->toastError('Client ID not found in session. Please log in again.', 'index.php?controller=LoginController');
            return;
        }

        // Set page title and load view
        $pageTitle = 'My Events';
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => 'index.php?controller=DashboardController'],
            ['title' => 'My Events', 'url' => '']
        ];

        require_once 'views/user_events.php';
    }

    /**
     * Get user events via AJAX
     */
    public function getUserEvents() {
        // Check if user is logged in
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized access - No session']);
            exit();
        }
        
        $clientId = $_SESSION['user']['client_id'] ?? null;
        if (!$clientId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit();
        }

        try {
            $userId = $_SESSION['user']['id'];
            $limit = (int)($_GET['limit'] ?? 10);
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $offset = ($page - 1) * $limit;

            // Get search and filter parameters
            $search = trim($_GET['search'] ?? '');
            $filters = [];

            if (!empty($_GET['event_type'])) {
                $filters['event_type'] = $_GET['event_type'];
            }

            if (!empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }

            if (!empty($_GET['date_from'])) {
                $filters['date_from'] = $_GET['date_from'];
            }

            if (!empty($_GET['date_to'])) {
                $filters['date_to'] = $_GET['date_to'];
            }

            // Get events from database
            $events = $this->userEventModel->getUserEvents($userId, $clientId, $limit, $offset, $search, $filters);
            $totalEvents = count($this->userEventModel->getUserEvents($userId, $clientId, 999999, 0, $search, $filters));
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
     * Submit RSVP
     */
    public function rsvp() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        // Check if user is logged in
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit;
        }
        
        $clientId = $_SESSION['user']['client_id'] ?? null;
        if (!$clientId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit;
        }

        try {
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

            $result = $this->userEventModel->submitRSVP($rsvpData);

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
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.'
            ]);
        }
        exit;
    }

    /**
     * Get upcoming events for user
     */
    public function getUpcomingEvents() {
        // Check if user is logged in
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            exit();
        }
        
        $clientId = $_SESSION['user']['client_id'] ?? null;
        if (!$clientId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit();
        }

        try {
            $userId = $_SESSION['user']['id'];
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;

            $events = $this->userEventModel->getUpcomingEventsForUser($userId, $clientId, $limit);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'events' => $events
            ]);

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error loading upcoming events: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Get event details by ID
     */
    public function getEventDetails() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized access - No session']);
            exit();
        }
        
        $clientId = $_SESSION['user']['client_id'] ?? null;
        if (!$clientId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Client ID not found in session']);
            exit();
        }

        try {
            $eventId = $_GET['event_id'] ?? null;
            if (!$eventId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Event ID is required']);
                exit();
            }

            $userId = $_SESSION['user']['id'];
            $event = $this->userEventModel->getEventDetails($eventId, $userId, $clientId);

            if (!$event) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Event not found or access denied']);
                exit();
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'event' => $event
            ]);

        } catch (Exception $e) {
            error_log("Error in getEventDetails: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error loading event details: ' . $e->getMessage()]);
        }
        exit();
    }
}
