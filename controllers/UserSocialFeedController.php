<?php
require_once 'models/UserSocialFeedModel.php';
require_once 'controllers/BaseController.php';
require_once 'config/autoload.php';
require_once 'core/middleware/AuthMiddleware.php';

class UserSocialFeedController extends BaseController {
    private $userSocialFeedModel;

    public function __construct() {
        // Start output buffering to prevent any unexpected output
        ob_start();
        
        $this->userSocialFeedModel = new UserSocialFeedModel();
        
        // Apply authentication middleware
        $authMiddleware = new AuthMiddleware();
        $authMiddleware->handle();
    }

    // Display user social feed page
    public function index() {
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

        // Set page title and include view
        $pageTitle = 'My Social Feed';
        include 'views/user_social_feed.php';
    }

    // Get user social feed posts via AJAX
    public function getUserPosts() {
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
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            $search = $_GET['search'] ?? '';
            $postType = $_GET['post_type'] ?? '';
            $visibility = $_GET['visibility'] ?? '';
            $status = $_GET['status'] ?? 'active';
            $pinned = $_GET['pinned'] ?? '';
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';

            $offset = ($page - 1) * $limit;

            $posts = $this->userSocialFeedModel->getUserPosts(
                $userId, 
                $clientId, 
                $search, 
                $postType, 
                $visibility, 
                $status, 
                $pinned, 
                $dateFrom, 
                $dateTo, 
                $limit, 
                $offset
            );

            $totalPosts = $this->userSocialFeedModel->countUserPosts(
                $userId, 
                $clientId, 
                $search, 
                $postType, 
                $visibility, 
                $status, 
                $pinned, 
                $dateFrom, 
                $dateTo
            );

            $totalPages = ceil($totalPosts / $limit);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'posts' => $posts,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_posts' => $totalPosts,
                    'per_page' => $limit,
                    'has_next' => $page < $totalPages,
                    'has_prev' => $page > 1
                ]
            ]);

        } catch (Exception $e) {
            error_log("Error in getUserPosts: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error loading posts: ' . $e->getMessage()]);
        }
        exit();
    }

    // Get post details for modal
    public function getPostDetails() {
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
            $postId = $_GET['post_id'] ?? null;
            if (!$postId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Post ID is required']);
                exit();
            }

            $userId = $_SESSION['user']['id'];
            $post = $this->userSocialFeedModel->getPostDetails($postId, $userId, $clientId);

            if (!$post) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Post not found or access denied']);
                exit();
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'post' => $post
            ]);

        } catch (Exception $e) {
            error_log("Error in getPostDetails: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error loading post details: ' . $e->getMessage()]);
        }
        exit();
    }

    // Handle post reactions
    public function toggleReaction() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized access - No session']);
            exit();
        }

        try {
            $postId = $_POST['post_id'] ?? null;
            $reactionType = $_POST['reaction_type'] ?? null;

            if (!$postId || !$reactionType) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Post ID and reaction type are required']);
                exit();
            }

            $userId = $_SESSION['user']['id'];
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $result = $this->userSocialFeedModel->toggleReaction($postId, $userId, $reactionType, $clientId);

            header('Content-Type: application/json');
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Error in toggleReaction: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error processing reaction: ' . $e->getMessage()]);
        }
        exit();
    }

    // Get post comments
    public function getComments() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized access - No session']);
            exit();
        }

        try {
            $postId = $_GET['post_id'] ?? null;
            if (!$postId) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Post ID is required']);
                exit();
            }

            $comments = $this->userSocialFeedModel->getPostComments($postId);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'comments' => $comments
            ]);

        } catch (Exception $e) {
            error_log("Error in getComments: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error loading comments: ' . $e->getMessage()]);
        }
        exit();
    }

    // Add comment
    public function addComment() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized access - No session']);
            exit();
        }

        try {
            $postId = $_POST['post_id'] ?? null;
            $content = $_POST['content'] ?? '';

            if (!$postId || empty(trim($content))) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Post ID and comment content are required']);
                exit();
            }

            $userId = $_SESSION['user']['id'];
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $result = $this->userSocialFeedModel->addComment($postId, $userId, $content, $clientId);

            header('Content-Type: application/json');
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Error in addComment: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error adding comment: ' . $e->getMessage()]);
        }
        exit();
    }

    // Handle poll voting
    public function votePoll() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized access - No session']);
            exit();
        }

        try {
            $pollId = $_POST['poll_id'] ?? null;
            $options = $_POST['options'] ?? null;

            if (!$pollId || !$options) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Poll ID and options are required']);
                exit();
            }

            $userId = $_SESSION['user']['id'];
            $clientId = $_SESSION['user']['client_id'] ?? null;
            
            // Decode options if it's JSON
            if (is_string($options)) {
                $options = json_decode($options, true);
            }

            $result = $this->userSocialFeedModel->votePoll($pollId, $userId, $options, $clientId);

            header('Content-Type: application/json');
            echo json_encode($result);

        } catch (Exception $e) {
            error_log("Error in votePoll: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error submitting vote: ' . $e->getMessage()]);
        }
        exit();
    }
}
?>
