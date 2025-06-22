<?php
require_once 'models/SocialFeedModel.php';
require_once 'controllers/BaseController.php';
require_once 'config/autoload.php';
require_once 'core/middleware/AuthMiddleware.php';

class SocialFeedController extends BaseController {
    private $socialFeedModel;
    private $userModel;

    public function __construct() {
        // Start output buffering to prevent any unexpected output
        ob_start();
        
        $this->socialFeedModel = new SocialFeedModel();
        $this->userModel = new UserModel();
        
        // Apply authentication middleware
        $authMiddleware = new AuthMiddleware();
        $authMiddleware->handle();
    }

    // List posts (with filters/search/pagination)
    public function index() {
        $this->render('social_feed');
    }

    public function list() {
        // Debug logging
        error_log("SocialFeedController::list() called");
        error_log("Session data: " . print_r($_SESSION, true));
        error_log("GET data: " . print_r($_GET, true));
        
        // TEMPORARY: If no session, use client_id = 1 for testing
        $clientId = $_SESSION['user']['client_id'] ?? 1;
        
        if (!$clientId) {
            error_log("SocialFeedController::list() - No client_id available");
            $this->jsonResponse([
                'success' => false,
                'message' => 'Unauthorized access - No client_id available'
            ]);
            return;
        }
        
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $filters = [
                'post_type' => $_GET['post_type'] ?? '',
                'visibility' => $_GET['visibility'] ?? '',
                'status' => $_GET['status'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? '',
                'search' => $_GET['search'] ?? '',
                'client_id' => $clientId
            ];

            error_log("SocialFeedController::list() - Filters: " . print_r($filters, true));

            $result = $this->socialFeedModel->getPosts($page, $limit, $filters);
            
            error_log("SocialFeedController::list() - Model result: " . print_r($result, true));
            
            if ($result['success']) {
                // If no posts, return empty array and pagination
                if (empty($result['posts'])) {
                    error_log("SocialFeedController::list() - No posts found, returning empty array");
                    $this->jsonResponse([
                        'success' => true,
                        'posts' => [],
                        'pagination' => [
                            'current_page' => $page,
                            'total_pages' => 0,
                            'total_posts' => 0,
                            'per_page' => $limit
                        ]
                    ]);
                } else {
                    error_log("SocialFeedController::list() - Found " . count($result['posts']) . " posts");
                    $this->jsonResponse([
                        'success' => true,
                        'posts' => $result['posts'],
                        'pagination' => $result['pagination']
                    ]);
                }
            } else {
                error_log("SocialFeedController::list() - Model returned error: " . $result['message']);
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            error_log("SocialFeedController::list() - Exception: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to load posts: ' . $e->getMessage()
            ]);
        }
    }

    // Create new post
    public function create() {
        try {
            // Clear any previous output
            if (ob_get_length()) {
                ob_clean();
            }
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            // Debug logging
            error_log('SocialFeedController::create - POST data received: ' . print_r($_POST, true));

            // Check if user is logged in
            if (!isset($_SESSION['user']['client_id'])) {
                throw new Exception('Unauthorized access. Please log in.');
            }

            // Server-side validation
            $errors = [];

            // Validate title (optional but if provided, validate length)
            $title = trim($_POST['title'] ?? '');
            if (!empty($title) && strlen($title) > 150) {
                $errors[] = 'Post title cannot exceed 150 characters.';
            }

            // Validate required fields
            $content = trim($_POST['content'] ?? '');
            if (empty($content)) {
                $errors[] = 'Post content is required.';
            } elseif (strlen($content) > 2000) {
                $errors[] = 'Post content cannot exceed 2000 characters.';
            }

            $category = trim($_POST['post_type'] ?? '');
            if (empty($category)) {
                $errors[] = 'Category is required.';
            } elseif (!in_array($category, ['text', 'media', 'poll', 'link'])) {
                $errors[] = 'Invalid category selected.';
            }

            $visibility = $_POST['visibility'] ?? 'global';
            if (!in_array($visibility, ['global', 'course_specific', 'group_specific'])) {
                $errors[] = 'Invalid visibility setting.';
            }

            // Validate tags (optional)
            $tags = trim($_POST['tags'] ?? '');
            if (!empty($tags)) {
                $tagArray = array_filter(array_map('trim', explode(',', $tags)));
                if (count($tagArray) > 10) {
                    $errors[] = 'Maximum 10 tags allowed.';
                }
                foreach ($tagArray as $tag) {
                    if (strlen($tag) > 50) {
                        $errors[] = 'Individual tags cannot exceed 50 characters.';
                        break;
                    }
                }
            }

            // Validate scheduled post
            $scheduledAt = null;
            if (isset($_POST['schedule_post']) && !empty($_POST['scheduled_at'])) {
                $scheduledAt = new DateTime($_POST['scheduled_at']);
                $now = new DateTime();
                
                if ($scheduledAt <= $now) {
                    $errors[] = 'Scheduled time must be in the future.';
                }
            }

            // Validate media files
            $mediaFiles = [];
            if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
                $mediaValidation = $this->validateMediaFiles($_FILES['media']);
                if (!$mediaValidation['valid']) {
                    $errors[] = $mediaValidation['message'];
                } else {
                    $mediaFiles = $this->handleMediaUploads($_FILES['media']);
                }
            }

            // Validate poll data
            $pollData = null;
            if (isset($_POST['include_poll']) && !empty($_POST['poll_options'])) {
                $pollValidation = $this->validatePollData($_POST);
                if (!$pollValidation['valid']) {
                    $errors[] = $pollValidation['message'];
                } else {
                    $pollData = $pollValidation['data'];
                }
            }

            // Check for validation errors
            if (!empty($errors)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => implode(' ', $errors)
                ]);
                return;
            }

            // Prepare post data
            $postData = [
                'title' => $title,
                'content' => $content,
                'post_type' => $category,
                'visibility' => $visibility,
                'tags' => $tags,
                'is_pinned' => isset($_POST['pin_post']) ? 1 : 0,
                'author_id' => $_SESSION['user']['id'],
                'client_id' => $_SESSION['user']['client_id'] ?? null,
                'scheduled_at' => $scheduledAt ? $scheduledAt->format('Y-m-d H:i:s') : null
            ];

            // Create the post
            $result = $this->socialFeedModel->createPost($postData, $mediaFiles, $pollData);
            
            if ($result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Post created successfully!',
                    'post_id' => $result['post_id']
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to create post: ' . $e->getMessage()
            ]);
        }
    }

    // Show post details
    public function show() {
        try {
            $postId = (int)($_GET['post_id'] ?? 0);
            
            if ($postId <= 0) {
                throw new Exception('Invalid post ID');
            }

            $result = $this->socialFeedModel->getPostById($postId, $_SESSION['user']['client_id'] ?? null);
            
            if ($result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'post' => $result['post']
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to load post: ' . $e->getMessage()
            ]);
        }
    }

    // Edit post
    public function edit() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            // Check if user is logged in
            if (!isset($_SESSION['user']['client_id'])) {
                throw new Exception('Unauthorized access. Please log in.');
            }

            $postId = (int)($_POST['post_id'] ?? 0);
            if ($postId <= 0) {
                throw new Exception('Invalid post ID');
            }

            // Check if user can edit this post
            $post = $this->socialFeedModel->getPostById($postId, $_SESSION['user']['client_id'] ?? null);
            if (!$post['success']) {
                throw new Exception($post['message']);
            }

            if (!$post['post']['can_edit']) {
                throw new Exception('You do not have permission to edit this post');
            }

            // Server-side validation
            $errors = [];

            // Validate title (optional but if provided, validate length)
            $title = trim($_POST['title'] ?? '');
            if (!empty($title) && strlen($title) > 150) {
                $errors[] = 'Post title cannot exceed 150 characters.';
            }

            // Validate required fields
            $content = trim($_POST['content'] ?? '');
            if (empty($content)) {
                $errors[] = 'Post content is required.';
            } elseif (strlen($content) > 2000) {
                $errors[] = 'Post content cannot exceed 2000 characters.';
            }

            $category = trim($_POST['post_type'] ?? '');
            if (empty($category)) {
                $errors[] = 'Category is required.';
            } elseif (!in_array($category, ['text', 'media', 'poll', 'link'])) {
                $errors[] = 'Invalid category selected.';
            }

            $visibility = $_POST['visibility'] ?? 'global';
            if (!in_array($visibility, ['global', 'course_specific', 'group_specific'])) {
                $errors[] = 'Invalid visibility setting.';
            }

            // Validate tags (optional)
            $tags = trim($_POST['tags'] ?? '');
            if (!empty($tags)) {
                $tagArray = array_filter(array_map('trim', explode(',', $tags)));
                if (count($tagArray) > 10) {
                    $errors[] = 'Maximum 10 tags allowed.';
                }
                foreach ($tagArray as $tag) {
                    if (strlen($tag) > 50) {
                        $errors[] = 'Individual tags cannot exceed 50 characters.';
                        break;
                    }
                }
            }

            // Check for validation errors
            if (!empty($errors)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => implode(' ', $errors)
                ]);
                return;
            }

            // Prepare post data
            $postData = [
                'id' => $postId,
                'title' => $title,
                'content' => $content,
                'post_type' => $category,
                'visibility' => $visibility,
                'tags' => $tags,
                'is_pinned' => isset($_POST['pin_post']) ? 1 : 0,
                'author_id' => $_SESSION['user']['id'],
                'client_id' => $_SESSION['user']['client_id'] ?? null
            ];

            // Update the post
            $result = $this->socialFeedModel->updatePost($postData);
            
            if ($result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Post updated successfully!',
                    'post_id' => $postId
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update post: ' . $e->getMessage()
            ]);
        }
    }

    // Delete post
    public function delete() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $postId = (int)($input['post_id'] ?? 0);
            
            if ($postId <= 0) {
                throw new Exception('Invalid post ID');
            }

            $result = $this->socialFeedModel->deletePost($postId, $_SESSION['user']['id'], $_SESSION['user']['client_id'] ?? null);
            
            if ($result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Post deleted successfully!'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to delete post: ' . $e->getMessage()
            ]);
        }
    }

    // Get comments for a post
    public function comments() {
        try {
            $postId = (int)($_GET['post_id'] ?? 0);
            
            if ($postId <= 0) {
                throw new Exception('Invalid post ID');
            }

            $result = $this->socialFeedModel->getComments($postId, $_SESSION['user']['client_id'] ?? null);
            
            if ($result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'comments' => $result['comments']
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to load comments: ' . $e->getMessage()
            ]);
        }
    }

    // Add comment to a post
    public function comment() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            // Check if user is logged in
            if (!isset($_SESSION['user']['client_id'])) {
                throw new Exception('Unauthorized access. Please log in.');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            // Server-side validation
            $errors = [];

            $postId = (int)($input['post_id'] ?? 0);
            if ($postId <= 0) {
                $errors[] = 'Invalid post ID.';
            }

            $content = trim($input['content'] ?? '');
            if (empty($content)) {
                $errors[] = 'Comment content is required.';
            } elseif (strlen($content) > 1000) {
                $errors[] = 'Comment content cannot exceed 1000 characters.';
            }

            // Check for validation errors
            if (!empty($errors)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => implode(' ', $errors)
                ]);
                return;
            }

            $commentData = [
                'post_id' => $postId,
                'content' => $content,
                'author_id' => $_SESSION['user']['id'],
                'client_id' => $_SESSION['user']['client_id'] ?? null
            ];

            $result = $this->socialFeedModel->addComment($commentData);
            
            if ($result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Comment added successfully!',
                    'comment_id' => $result['comment_id']
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to add comment: ' . $e->getMessage()
            ]);
        }
    }

    // Add reaction
    public function reaction() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $postId = (int)($input['post_id'] ?? 0);
            $reactionType = $input['reaction_type'] ?? '';
            
            if ($postId <= 0) {
                throw new Exception('Invalid post ID');
            }

            if (!in_array($reactionType, ['like', 'love', 'laugh', 'wow', 'sad', 'angry'])) {
                throw new Exception('Invalid reaction type');
            }

            $reactionData = [
                'post_id' => $postId,
                'user_id' => $_SESSION['user']['id'],
                'reaction_type' => $reactionType,
                'client_id' => $_SESSION['user']['client_id'] ?? null
            ];

            $result = $this->socialFeedModel->toggleReaction($reactionData);
            
            if ($result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Reaction updated!',
                    'new_count' => $result['new_count'],
                    'is_reacted' => $result['is_reacted']
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update reaction: ' . $e->getMessage()
            ]);
        }
    }

    // Report a post
    public function report() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            // Check if user is logged in
            if (!isset($_SESSION['user']['client_id'])) {
                throw new Exception('Unauthorized access. Please log in.');
            }

            // Server-side validation
            $errors = [];

            $postId = (int)($_POST['post_id'] ?? 0);
            if ($postId <= 0) {
                $errors[] = 'Invalid post ID.';
            }

            $reason = trim($_POST['reason'] ?? '');
            if (empty($reason)) {
                $errors[] = 'Report reason is required.';
            } elseif (!in_array($reason, ['spam', 'inappropriate', 'harassment', 'violence', 'copyright', 'other'])) {
                $errors[] = 'Invalid report reason.';
            }

            $description = trim($_POST['description'] ?? '');
            if (empty($description)) {
                $errors[] = 'Report description is required.';
            } elseif (strlen($description) > 500) {
                $errors[] = 'Report description cannot exceed 500 characters.';
            }

            // Check for validation errors
            if (!empty($errors)) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => implode(' ', $errors)
                ]);
                return;
            }

            $reportData = [
                'post_id' => $postId,
                'reason' => $reason,
                'description' => $description,
                'reported_by_user_id' => $_SESSION['user']['id'],
                'client_id' => $_SESSION['user']['client_id'] ?? null
            ];

            $result = $this->socialFeedModel->reportPost($reportData);
            
            if ($result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Report submitted successfully!'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to submit report: ' . $e->getMessage()
            ]);
        }
    }

    // Pin/unpin post
    public function pin() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $postId = (int)($input['post_id'] ?? 0);
            $isPinned = (bool)($input['is_pinned'] ?? false);
            
            if ($postId <= 0) {
                throw new Exception('Invalid post ID');
            }

            $result = $this->socialFeedModel->pinPost($postId, $isPinned, $_SESSION['user']['id'], $_SESSION['user']['client_id'] ?? null);
            
            if ($result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => $isPinned ? 'Post pinned successfully!' : 'Post unpinned successfully!'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update pin status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update post status (activate, pause, resume, archive, etc.)
     */
    public function updateStatus() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $postId = (int)($input['post_id'] ?? 0);
            $status = $input['status'] ?? null;

            if (!$postId || !$status) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Post ID and status are required.'
                ]);
                return;
            }

            // Validate status
            if (!in_array($status, ['draft', 'active', 'archived'])) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Invalid status.'
                ]);
                return;
            }

            // Check if user is logged in
            if (!isset($_SESSION['user']['id'])) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Unauthorized access. Please log in.'
                ]);
                return;
            }

            $result = $this->socialFeedModel->updateStatus(
                $postId,
                $status,
                $_SESSION['user']['id'],
                $_SESSION['user']['client_id'] ?? null
            );

            if ($result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update post status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get post statistics
     */
    public function statistics() {
        try {
            $postId = (int)($_GET['post_id'] ?? 0);
            
            if (!$postId) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Post ID is required.'
                ]);
                return;
            }

            // Check if user is logged in
            if (!isset($_SESSION['user']['id'])) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Unauthorized access. Please log in.'
                ]);
                return;
            }

            // For now, return mock statistics
            // In a real implementation, you would query the database for actual statistics
            $statistics = [
                'total_reactions' => rand(10, 100),
                'total_comments' => rand(5, 50),
                'total_views' => rand(100, 1000),
                'total_shares' => rand(1, 20),
                'reaction_breakdown' => [
                    'like' => rand(5, 30),
                    'love' => rand(3, 20)
                ]
            ];

            $this->jsonResponse([
                'success' => true,
                'statistics' => $statistics
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to load statistics: ' . $e->getMessage()
            ]);
        }
    }

    // Get notifications
    public function notifications() {
        try {
            $userId = $_SESSION['user']['id'];
            $clientId = $_SESSION['user']['client_id'] ?? null;
            
            $result = $this->socialFeedModel->getNotifications($userId, $clientId);
            
            if ($result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'notifications' => $result['notifications']
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to load notifications: ' . $e->getMessage()
            ]);
        }
    }

    // Vote on poll
    public function pollVote() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $pollId = (int)($input['poll_id'] ?? 0);
            $optionId = (int)($input['option_id'] ?? 0);
            
            if ($pollId <= 0 || $optionId <= 0) {
                throw new Exception('Invalid poll or option ID');
            }

            $voteData = [
                'poll_id' => $pollId,
                'option_id' => $optionId,
                'user_id' => $_SESSION['user']['id'],
                'client_id' => $_SESSION['user']['client_id'] ?? null
            ];

            $result = $this->socialFeedModel->votePoll($voteData);
            
            if ($result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Vote recorded successfully!'
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to record vote: ' . $e->getMessage()
            ]);
        }
    }

    private function handleMediaUploads($files) {
        $uploadedFiles = [];
        $uploadDir = 'uploads/social_feed/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $files['name'][$i];
                $fileTmpName = $files['tmp_name'][$i];
                $fileSize = $files['size'][$i];
                $fileType = $files['type'][$i];
                
                // Generate unique filename
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
                $filePath = $uploadDir . $uniqueFileName;
                
                if (move_uploaded_file($fileTmpName, $filePath)) {
                    $uploadedFiles[] = [
                        'filename' => $fileName,
                        'filepath' => $filePath,
                        'filesize' => $fileSize,
                        'filetype' => $fileType
                    ];
                }
            }
        }
        
        return $uploadedFiles;
    }

    /**
     * Validate media files
     */
    private function validateMediaFiles($files) {
        $maxSize = 10 * 1024 * 1024; // 10MB
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/avi', 'video/mov', 'video/wmv',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                // Check file size
                if ($files['size'][$i] > $maxSize) {
                    return [
                        'valid' => false,
                        'message' => 'File "' . $files['name'][$i] . '" is too large. Maximum size is 10MB.'
                    ];
                }

                // Check file type
                if (!in_array($files['type'][$i], $allowedTypes)) {
                    return [
                        'valid' => false,
                        'message' => 'File "' . $files['name'][$i] . '" has an invalid type. Please upload images, videos, or documents.'
                    ];
                }

                // Check for malicious files
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $files['tmp_name'][$i]);
                finfo_close($finfo);

                if (!in_array($mimeType, $allowedTypes)) {
                    return [
                        'valid' => false,
                        'message' => 'File "' . $files['name'][$i] . '" appears to be corrupted or has an invalid type.'
                    ];
                }
            } else {
                return [
                    'valid' => false,
                    'message' => 'Error uploading file "' . $files['name'][$i] . '".'
                ];
            }
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validate poll data
     */
    private function validatePollData($postData) {
        $pollOptions = json_decode($postData['poll_options'], true);
        
        if (!is_array($pollOptions)) {
            return [
                'valid' => false,
                'message' => 'Invalid poll options format.'
            ];
        }

        // Filter out empty options
        $validOptions = array_filter(array_map('trim', $pollOptions));
        
        if (count($validOptions) < 2) {
            return [
                'valid' => false,
                'message' => 'Poll must have at least 2 options.'
            ];
        }

        if (count($validOptions) > 5) {
            return [
                'valid' => false,
                'message' => 'Poll cannot have more than 5 options.'
            ];
        }

        // Validate individual options
        foreach ($validOptions as $option) {
            if (strlen($option) > 200) {
                return [
                    'valid' => false,
                    'message' => 'Poll options cannot exceed 200 characters.'
                ];
            }
        }

        $pollData = [
            'question' => trim($postData['poll_question'] ?? 'What do you think?'),
            'options' => $validOptions,
            'allow_multiple_votes' => isset($postData['allow_multiple_votes']) ? 1 : 0
        ];

        if (strlen($pollData['question']) > 500) {
            return [
                'valid' => false,
                'message' => 'Poll question cannot exceed 500 characters.'
            ];
        }

        return [
            'valid' => true,
            'data' => $pollData
        ];
    }

    private function jsonResponse($data) {
        // Clear any previous output
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Set proper headers
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        // Ensure no errors are output
        error_reporting(0);
        
        echo json_encode($data);
        exit;
    }

    private function render($view) {
        require_once "views/{$view}.php";
    }
} 