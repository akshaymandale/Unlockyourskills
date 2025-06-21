<?php
require_once 'models/PollModel.php';
require_once 'controllers/BaseController.php';

class OpinionPollController extends BaseController {
    private $pollModel;

    public function __construct() {
        $this->pollModel = new PollModel();
    }

    /**
     * Display opinion poll management page
     */
    public function index() {
        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];

        // Don't load initial data - let JavaScript handle it via AJAX
        $polls = [];
        $totalPolls = 0;
        $totalPages = 0;
        $page = 1;

        // Get unique values for filter dropdowns (client-specific)
        $uniqueStatuses = $this->pollModel->getUniqueStatuses($clientId);
        $uniqueTypes = $this->pollModel->getUniqueTypes($clientId);

        require 'views/opinion_polls.php';
    }

    /**
     * AJAX search for dynamic poll loading
     */
    public function ajaxSearch() {
        header('Content-Type: application/json');

        // Check if user is logged in
        if (!isset($_SESSION['user'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized access. Please log in.'
            ]);
            exit();
        }

        try {
            $clientId = $_SESSION['user']['client_id'];
            $limit = 10;
            $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
            $offset = ($page - 1) * $limit;

            // Get search and filter parameters
            $search = trim($_POST['search'] ?? '');
            $filters = [];

            if (!empty($_POST['status'])) {
                $filters['status'] = $_POST['status'];
            }

            if (!empty($_POST['type'])) {
                $filters['type'] = $_POST['type'];
            }

            if (!empty($_POST['audience'])) {
                $filters['target_audience'] = $_POST['audience'];
            }

            if (!empty($_POST['date_from'])) {
                $filters['date_from'] = $_POST['date_from'];
            }

            if (!empty($_POST['date_to'])) {
                $filters['date_to'] = $_POST['date_to'];
            }

            // Get polls from database
            $polls = $this->pollModel->getAllPolls($limit, $offset, $search, $filters, $clientId);
            $totalPolls = count($this->pollModel->getAllPolls(999999, 0, $search, $filters, $clientId));
            $totalPages = ceil($totalPolls / $limit);

            $response = [
                'success' => true,
                'polls' => $polls,
                'totalPolls' => $totalPolls,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalPolls' => $totalPolls
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error loading polls: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Create new poll
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request method.', 'index.php?controller=OpinionPollController');
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

            // Server-side validation
            $errors = [];

            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $errors[] = 'Poll title is required.';
            }

            $description = trim($_POST['description'] ?? '');
            $type = $_POST['type'] ?? '';
            if (!in_array($type, ['single_choice', 'multiple_choice'])) {
                $errors[] = 'Invalid poll type.';
            }

            $targetAudience = $_POST['target_audience'] ?? '';
            if (!in_array($targetAudience, ['global', 'course_specific', 'group_specific'])) {
                $errors[] = 'Invalid target audience.';
            }

            $startDatetime = $_POST['start_datetime'] ?? '';
            $endDatetime = $_POST['end_datetime'] ?? '';
            
            if (empty($startDatetime) || empty($endDatetime)) {
                $errors[] = 'Start and end dates are required.';
            } else {
                $startTime = strtotime($startDatetime);
                $endTime = strtotime($endDatetime);
                
                if ($startTime >= $endTime) {
                    $errors[] = 'End date must be after start date.';
                }
                
                if ($startTime < time() - 300) { // Allow 5 minutes buffer
                    $errors[] = 'Start date cannot be in the past.';
                }
            }

            $showResults = $_POST['show_results'] ?? '';
            if (!in_array($showResults, ['after_vote', 'after_end', 'admin_only'])) {
                $errors[] = 'Invalid show results option.';
            }

            $allowAnonymous = isset($_POST['allow_anonymous']) ? 1 : 0;
            $allowVoteChange = isset($_POST['allow_vote_change']) ? 1 : 0;

            // Validate questions and options
            $questions = $_POST['questions'] ?? [];
            if (empty($questions)) {
                $errors[] = 'At least one question is required.';
            } else {
                foreach ($questions as $index => $question) {
                    $questionText = trim($question['text'] ?? '');
                    if (empty($questionText)) {
                        $errors[] = "Question " . ($index + 1) . " text is required.";
                    }

                    $options = $question['options'] ?? [];
                    if (count($options) < 2) {
                        $errors[] = "Question " . ($index + 1) . " must have at least 2 options.";
                    } else {
                        foreach ($options as $optIndex => $option) {
                            $optionText = trim($option['text'] ?? '');
                            if (empty($optionText)) {
                                $errors[] = "Question " . ($index + 1) . ", Option " . ($optIndex + 1) . " text is required.";
                            }
                        }
                    }
                }
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
                    $this->toastError(implode(' ', $errors), 'index.php?controller=OpinionPollController');
                    return;
                }
            }

            // Prepare poll data
            $pollData = [
                'client_id' => $clientId,
                'title' => $title,
                'description' => $description,
                'type' => $type,
                'target_audience' => $targetAudience,
                'course_id' => $targetAudience === 'course_specific' ? ($_POST['course_id'] ?? null) : null,
                'group_id' => $targetAudience === 'group_specific' ? ($_POST['group_id'] ?? null) : null,
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
                'show_results' => $showResults,
                'allow_anonymous' => $allowAnonymous,
                'allow_vote_change' => $allowVoteChange,
                'status' => 'draft',
                'created_by' => $userId
            ];

            // Create poll
            $pollId = $this->pollModel->createPoll($pollData);

            if ($pollId) {
                // Create questions and options
                foreach ($questions as $questionIndex => $question) {
                    $questionData = [
                        'poll_id' => $pollId,
                        'client_id' => $clientId,
                        'question_text' => trim($question['text']),
                        'question_order' => $questionIndex + 1,
                        'media_type' => 'none',
                        'media_path' => null,
                        'is_required' => true
                    ];

                    $questionId = $this->pollModel->createPollQuestion($questionData);

                    if ($questionId) {
                        $options = $question['options'] ?? [];
                        foreach ($options as $optionIndex => $option) {
                            $optionData = [
                                'question_id' => $questionId,
                                'poll_id' => $pollId,
                                'client_id' => $clientId,
                                'option_text' => trim($option['text']),
                                'option_order' => $optionIndex + 1,
                                'media_type' => 'none',
                                'media_path' => null
                            ];

                            $this->pollModel->createPollOption($optionData);
                        }
                    }
                }

                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Opinion poll created successfully!'
                    ]);
                    exit;
                } else {
                    $this->toastSuccess('Opinion poll created successfully!', 'index.php?controller=OpinionPollController');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to create opinion poll. Please try again.'
                    ]);
                    exit;
                } else {
                    $this->toastError('Failed to create opinion poll. Please try again.', 'index.php?controller=OpinionPollController');
                }
            }

        } catch (Exception $e) {
            error_log("Opinion poll creation error: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'An unexpected error occurred. Please try again.'
                ]);
                exit;
            } else {
                $this->toastError('An unexpected error occurred. Please try again.', 'index.php?controller=OpinionPollController');
            }
        }
    }

    /**
     * Get poll data for edit modal (AJAX request)
     */
    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Poll ID is required']);
                exit;
            }
            $this->toastError('Poll ID is required.', 'index.php?controller=OpinionPollController');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
        $poll = $this->pollModel->getPollById($id, $clientId);

        if (!$poll) {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Poll not found']);
                exit;
            }
            $this->toastError('Poll not found.', 'index.php?controller=OpinionPollController');
            return;
        }

        // Check if poll can be edited
        $canEdit = $this->pollModel->canEditPoll($id, $clientId);
        if (!$canEdit['can_edit']) {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => $canEdit['reason'],
                    'can_edit' => false
                ]);
                exit;
            }
            $this->toastError($canEdit['reason'], 'index.php?controller=OpinionPollController');
            return;
        }

        // Get poll questions and options
        $questions = $this->pollModel->getPollQuestions($id, $clientId);
        foreach ($questions as &$question) {
            $question['options'] = $this->pollModel->getPollOptions($question['id'], $clientId);
        }

        // Return JSON data for AJAX request
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'poll' => $poll,
                'questions' => $questions,
                'can_edit' => true
            ]);
            exit;
        }

        // For non-AJAX requests, redirect to main page
        $this->toastInfo('Use the edit button to modify polls.', 'index.php?controller=OpinionPollController');
    }

    /**
     * Update poll
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request method.', 'index.php?controller=OpinionPollController');
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
            $pollId = $_POST['poll_id'] ?? null;

            if (!$pollId) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Poll ID is required.'
                    ]);
                    exit;
                }
                $this->toastError('Poll ID is required.', 'index.php?controller=OpinionPollController');
                return;
            }

            // Check if poll exists and can be edited
            $canEdit = $this->pollModel->canEditPoll($pollId, $clientId);
            if (!$canEdit['can_edit']) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $canEdit['reason']
                    ]);
                    exit;
                }
                $this->toastError($canEdit['reason'], 'index.php?controller=OpinionPollController');
                return;
            }

            // Server-side validation (same as create)
            $errors = [];

            $title = trim($_POST['title'] ?? '');
            if (empty($title)) {
                $errors[] = 'Poll title is required.';
            }

            $description = trim($_POST['description'] ?? '');
            $type = $_POST['type'] ?? '';
            if (!in_array($type, ['single_choice', 'multiple_choice'])) {
                $errors[] = 'Invalid poll type.';
            }

            $targetAudience = $_POST['target_audience'] ?? '';
            if (!in_array($targetAudience, ['global', 'course_specific', 'group_specific'])) {
                $errors[] = 'Invalid target audience.';
            }

            $startDatetime = $_POST['start_datetime'] ?? '';
            $endDatetime = $_POST['end_datetime'] ?? '';

            if (empty($startDatetime) || empty($endDatetime)) {
                $errors[] = 'Start and end dates are required.';
            } else {
                $startTime = strtotime($startDatetime);
                $endTime = strtotime($endDatetime);

                if ($startTime >= $endTime) {
                    $errors[] = 'End date must be after start date.';
                }

                if ($startTime < time() - 300) { // Allow 5 minutes buffer
                    $errors[] = 'Start date cannot be in the past.';
                }
            }

            $showResults = $_POST['show_results'] ?? '';
            if (!in_array($showResults, ['after_vote', 'after_end', 'admin_only'])) {
                $errors[] = 'Invalid show results option.';
            }

            $allowAnonymous = isset($_POST['allow_anonymous']) ? 1 : 0;
            $allowVoteChange = isset($_POST['allow_vote_change']) ? 1 : 0;

            // Validate questions and options
            $questions = $_POST['questions'] ?? [];
            if (empty($questions)) {
                $errors[] = 'At least one question is required.';
            } else {
                foreach ($questions as $index => $question) {
                    $questionText = trim($question['text'] ?? '');
                    if (empty($questionText)) {
                        $errors[] = "Question " . ($index + 1) . " text is required.";
                    }

                    $options = $question['options'] ?? [];
                    if (count($options) < 2) {
                        $errors[] = "Question " . ($index + 1) . " must have at least 2 options.";
                    } else {
                        foreach ($options as $optIndex => $option) {
                            $optionText = trim($option['text'] ?? '');
                            if (empty($optionText)) {
                                $errors[] = "Question " . ($index + 1) . ", Option " . ($optIndex + 1) . " text is required.";
                            }
                        }
                    }
                }
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
                    $this->toastError(implode(' ', $errors), 'index.php?controller=OpinionPollController');
                    return;
                }
            }

            // Prepare poll data
            $pollData = [
                'client_id' => $clientId,
                'title' => $title,
                'description' => $description,
                'type' => $type,
                'target_audience' => $targetAudience,
                'course_id' => $targetAudience === 'course_specific' ? ($_POST['course_id'] ?? null) : null,
                'group_id' => $targetAudience === 'group_specific' ? ($_POST['group_id'] ?? null) : null,
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
                'show_results' => $showResults,
                'allow_anonymous' => $allowAnonymous,
                'allow_vote_change' => $allowVoteChange,
                'status' => 'draft', // Keep as draft when editing
                'updated_by' => $userId
            ];

            // Update poll
            $result = $this->pollModel->updatePoll($pollId, $pollData);

            if ($result) {
                // Delete existing questions and options
                $this->pollModel->deletePollQuestions($pollId, $clientId);

                // Create new questions and options
                foreach ($questions as $questionIndex => $question) {
                    $questionData = [
                        'poll_id' => $pollId,
                        'client_id' => $clientId,
                        'question_text' => trim($question['text']),
                        'question_order' => $questionIndex + 1,
                        'media_type' => 'none',
                        'media_path' => null,
                        'is_required' => true
                    ];

                    $questionId = $this->pollModel->createPollQuestion($questionData);

                    if ($questionId) {
                        $options = $question['options'] ?? [];
                        foreach ($options as $optionIndex => $option) {
                            $optionData = [
                                'question_id' => $questionId,
                                'poll_id' => $pollId,
                                'client_id' => $clientId,
                                'option_text' => trim($option['text']),
                                'option_order' => $optionIndex + 1,
                                'media_type' => 'none',
                                'media_path' => null
                            ];

                            $this->pollModel->createPollOption($optionData);
                        }
                    }
                }

                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Opinion poll updated successfully!'
                    ]);
                    exit;
                } else {
                    $this->toastSuccess('Opinion poll updated successfully!', 'index.php?controller=OpinionPollController');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update opinion poll. Please try again.'
                    ]);
                    exit;
                } else {
                    $this->toastError('Failed to update opinion poll. Please try again.', 'index.php?controller=OpinionPollController');
                }
            }

        } catch (Exception $e) {
            error_log("Opinion poll update error: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'An unexpected error occurred. Please try again.'
                ]);
                exit;
            } else {
                $this->toastError('An unexpected error occurred. Please try again.', 'index.php?controller=OpinionPollController');
            }
        }
    }

    /**
     * Update poll status (activate, pause, end, etc.)
     */
    public function updateStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request method.', 'index.php?controller=OpinionPollController');
            return;
        }

        $pollId = $_POST['poll_id'] ?? null;
        $status = $_POST['status'] ?? null;

        if (!$pollId || !$status) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Poll ID and status are required.'
                ]);
                exit;
            }
            $this->toastError('Poll ID and status are required.', 'index.php?controller=OpinionPollController');
            return;
        }

        if (!in_array($status, ['draft', 'active', 'paused', 'ended', 'archived'])) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid status.'
                ]);
                exit;
            }
            $this->toastError('Invalid status.', 'index.php?controller=OpinionPollController');
            return;
        }

        try {
            $clientId = $_SESSION['user']['client_id'];
            $userId = $_SESSION['user']['id'];

            // Get current poll
            $poll = $this->pollModel->getPollById($pollId, $clientId);
            if (!$poll) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Poll not found.'
                    ]);
                    exit;
                }
                $this->toastError('Poll not found.', 'index.php?controller=OpinionPollController');
                return;
            }

            // Update poll status
            $updateData = [
                'title' => $poll['title'],
                'description' => $poll['description'],
                'type' => $poll['type'],
                'target_audience' => $poll['target_audience'],
                'course_id' => $poll['course_id'],
                'group_id' => $poll['group_id'],
                'start_datetime' => $poll['start_datetime'],
                'end_datetime' => $poll['end_datetime'],
                'show_results' => $poll['show_results'],
                'allow_anonymous' => $poll['allow_anonymous'],
                'allow_vote_change' => $poll['allow_vote_change'],
                'status' => $status,
                'updated_by' => $userId,
                'client_id' => $clientId
            ];

            $result = $this->pollModel->updatePoll($pollId, $updateData);

            if ($result) {
                $message = "Poll status updated to " . ucfirst($status) . " successfully!";

                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => $message
                    ]);
                    exit;
                } else {
                    $this->toastSuccess($message, 'index.php?controller=OpinionPollController');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update poll status.'
                    ]);
                    exit;
                } else {
                    $this->toastError('Failed to update poll status.', 'index.php?controller=OpinionPollController');
                }
            }

        } catch (Exception $e) {
            error_log("Poll status update error: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'An unexpected error occurred.'
                ]);
                exit;
            } else {
                $this->toastError('An unexpected error occurred.', 'index.php?controller=OpinionPollController');
            }
        }
    }

    /**
     * Delete poll
     */
    public function delete() {
        $pollId = $_GET['id'] ?? $_POST['id'] ?? null;

        if (!$pollId) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Poll ID is required.'
                ]);
                exit;
            }
            $this->toastError('Poll ID is required.', 'index.php?controller=OpinionPollController');
            return;
        }

        try {
            $clientId = $_SESSION['user']['client_id'];

            // Check if poll exists and can be deleted
            $canDelete = $this->pollModel->canDeletePoll($pollId, $clientId);
            if (!$canDelete['can_delete']) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $canDelete['reason']
                    ]);
                    exit;
                }
                $this->toastError($canDelete['reason'], 'index.php?controller=OpinionPollController');
                return;
            }

            // Delete poll (soft delete)
            $result = $this->pollModel->deletePoll($pollId, $clientId);

            if ($result) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Poll deleted successfully!'
                    ]);
                    exit;
                } else {
                    $this->toastSuccess('Poll deleted successfully!', 'index.php?controller=OpinionPollController');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to delete poll.'
                    ]);
                    exit;
                } else {
                    $this->toastError('Failed to delete poll.', 'index.php?controller=OpinionPollController');
                }
            }

        } catch (Exception $e) {
            error_log("Poll deletion error: " . $e->getMessage());

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'An unexpected error occurred.'
                ]);
                exit;
            } else {
                $this->toastError('An unexpected error occurred.', 'index.php?controller=OpinionPollController');
            }
        }
    }

    /**
     * Check if the current request is an AJAX request
     */
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
