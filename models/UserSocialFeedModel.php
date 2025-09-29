<?php
require_once 'config/Database.php';

class UserSocialFeedModel {
    private $conn;
    private $table_posts = 'feed_posts';
    private $table_reactions = 'feed_reactions';
    private $table_comments = 'feed_comments';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Get user social feed posts
    public function getUserPosts($userId, $clientId, $search = '', $postType = '', $visibility = '', $status = 'active', $pinned = '', $dateFrom = '', $dateTo = '', $limit = 10, $offset = 0) {
        $whereConditions = ["p.client_id = ?", "p.deleted_at IS NULL"];
        $params = [$clientId];

        // Add status filter
        if ($status) {
            $whereConditions[] = "p.status = ?";
            $params[] = $status;
        }

        // Add search filter
        if (!empty($search)) {
            $whereConditions[] = "(p.title LIKE ? OR p.body LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Add post type filter
        if (!empty($postType)) {
            $whereConditions[] = "p.post_type = ?";
            $params[] = $postType;
        }

        // Add visibility filter
        if (!empty($visibility)) {
            $whereConditions[] = "p.visibility = ?";
            $params[] = $visibility;
        }

        // Add pinned filter
        if ($pinned !== '') {
            $whereConditions[] = "p.is_pinned = ?";
            $params[] = $pinned;
        }

        // Add date range filter
        if (!empty($dateFrom)) {
            $whereConditions[] = "DATE(p.created_at) >= ?";
            $params[] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $whereConditions[] = "DATE(p.created_at) <= ?";
            $params[] = $dateTo;
        }

        // Add audience filtering for group specific posts
        $whereConditions[] = "(p.visibility = 'global' OR p.visibility = 'group_specific')";

        $whereClause = implode(' AND ', $whereConditions);

        $sql = "SELECT p.*,
                       up.full_name as author_name,
                       up.email as author_email,
                       up.profile_picture as author_avatar,
                       up.system_role as author_role,
                       (SELECT COUNT(*) FROM {$this->table_comments} WHERE post_id = p.id) as comment_count,
                       (SELECT COUNT(*) FROM {$this->table_reactions} WHERE post_id = p.id) as reaction_count,
                       (SELECT COUNT(*) FROM {$this->table_reactions} WHERE post_id = p.id AND reaction_type = 'like') as like_count,
                       (SELECT COUNT(*) FROM {$this->table_reactions} WHERE post_id = p.id AND reaction_type = 'love') as love_count,
                       (SELECT COUNT(*) FROM {$this->table_reactions} WHERE post_id = p.id AND reaction_type = 'laugh') as laugh_count,
                       (SELECT COUNT(*) FROM {$this->table_reactions} WHERE post_id = p.id AND reaction_type = 'angry') as angry_count,
                       (SELECT reaction_type FROM {$this->table_reactions} WHERE post_id = p.id AND user_id = ? ORDER BY created_at DESC LIMIT 1) as user_reaction,
                       (SELECT GROUP_CONCAT(poll_option_index) FROM feed_poll_votes WHERE post_id = p.id AND user_id = ?) as user_poll_votes
                FROM {$this->table_posts} p
                LEFT JOIN user_profiles up ON p.user_id = up.id
                WHERE {$whereClause}
                GROUP BY p.id
                ORDER BY p.is_pinned DESC, p.created_at DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        // Add userId parameters for the user_reaction and user_poll_votes subqueries (must be first)
        array_unshift($params, $userId, $userId);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process posts to add additional data
        foreach ($posts as &$post) {
            $post['author'] = [
                'id' => $post['user_id'],
                'name' => $post['author_name'],
                'email' => $post['author_email'],
                'avatar' => $post['author_avatar'],
                'role' => $post['author_role']
            ];

            // Parse media files
            $post['media'] = [];
            if ($post['media_files']) {
                $mediaFiles = json_decode($post['media_files'], true);
                if (is_array($mediaFiles)) {
                    // Transform media data to include proper fields for frontend
                    $post['media'] = array_map(function($file) {
                        return [
                            'filename' => $file['filename'] ?? '',
                            'filepath' => $file['filepath'] ?? '',
                            'filesize' => $file['filesize'] ?? 0,
                            'filetype' => $file['filetype'] ?? '',
                            'type' => $file['filetype'] ?? '', // MIME type for frontend
                            'url' => $file['filepath'] ?? '', // File path as URL for frontend
                            'name' => $file['filename'] ?? '' // Filename for frontend
                        ];
                    }, $mediaFiles);
                }
            }

            // Parse link preview
            $post['link_preview'] = null;
            if ($post['link_preview']) {
                $linkPreview = json_decode($post['link_preview'], true);
                if (is_array($linkPreview)) {
                    $post['link_preview'] = $linkPreview;
                }
            }

            // Parse poll data
            $post['poll'] = null;
            if ($post['poll_data']) {
                $pollData = json_decode($post['poll_data'], true);
                if (is_array($pollData)) {
                    $post['poll'] = $pollData;
                    
                    // Add user's selected options
                    $post['poll']['user_selected_options'] = [];
                    if ($post['user_poll_votes']) {
                        $userVotes = explode(',', $post['user_poll_votes']);
                        $post['poll']['user_selected_options'] = array_map('intval', $userVotes);
                    }
                }
            }

            // Parse tags
            $post['tags'] = $post['tags'] ? explode(',', $post['tags']) : [];

            // Format dates
            $post['formatted_date'] = date('M j, Y', strtotime($post['created_at']));
            $post['formatted_time'] = date('g:i A', strtotime($post['created_at']));
            $post['time_ago'] = $this->timeAgo($post['created_at']);

            // Set permissions
            $post['can_react'] = true;
            $post['can_comment'] = !$post['comments_locked'];
        }

        return $posts;
    }

    // Count user social feed posts
    public function countUserPosts($userId, $clientId, $search = '', $postType = '', $visibility = '', $status = 'active', $pinned = '', $dateFrom = '', $dateTo = '') {
        $whereConditions = ["p.client_id = ?", "p.deleted_at IS NULL"];
        $params = [$clientId];

        // Add status filter
        if ($status) {
            $whereConditions[] = "p.status = ?";
            $params[] = $status;
        }

        // Add search filter
        if (!empty($search)) {
            $whereConditions[] = "(p.title LIKE ? OR p.body LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Add post type filter
        if (!empty($postType)) {
            $whereConditions[] = "p.post_type = ?";
            $params[] = $postType;
        }

        // Add visibility filter
        if (!empty($visibility)) {
            $whereConditions[] = "p.visibility = ?";
            $params[] = $visibility;
        }

        // Add pinned filter
        if ($pinned !== '') {
            $whereConditions[] = "p.is_pinned = ?";
            $params[] = $pinned;
        }

        // Add date range filter
        if (!empty($dateFrom)) {
            $whereConditions[] = "DATE(p.created_at) >= ?";
            $params[] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $whereConditions[] = "DATE(p.created_at) <= ?";
            $params[] = $dateTo;
        }

        // Add audience filtering for group specific posts
        $whereConditions[] = "(p.visibility = 'global' OR p.visibility = 'group_specific')";

        $whereClause = implode(' AND ', $whereConditions);

        $sql = "SELECT COUNT(DISTINCT p.id) as count
                FROM {$this->table_posts} p
                WHERE {$whereClause}";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['count'] ?? 0;
    }

    // Get post details for modal
    public function getPostDetails($postId, $userId, $clientId) {
        $sql = "SELECT p.*,
                       up.full_name as author_name,
                       up.email as author_email,
                       up.profile_picture as author_avatar,
                       up.system_role as author_role,
                       (SELECT COUNT(*) FROM {$this->table_comments} WHERE post_id = p.id) as comment_count,
                       (SELECT COUNT(*) FROM {$this->table_reactions} WHERE post_id = p.id) as reaction_count,
                       (SELECT COUNT(*) FROM {$this->table_reactions} WHERE post_id = p.id AND reaction_type = 'like') as like_count,
                       (SELECT COUNT(*) FROM {$this->table_reactions} WHERE post_id = p.id AND reaction_type = 'love') as love_count,
                       (SELECT COUNT(*) FROM {$this->table_reactions} WHERE post_id = p.id AND reaction_type = 'laugh') as laugh_count,
                       (SELECT COUNT(*) FROM {$this->table_reactions} WHERE post_id = p.id AND reaction_type = 'angry') as angry_count,
                       (SELECT reaction_type FROM {$this->table_reactions} WHERE post_id = p.id AND user_id = ? ORDER BY created_at DESC LIMIT 1) as user_reaction,
                       (SELECT GROUP_CONCAT(poll_option_index) FROM feed_poll_votes WHERE post_id = p.id AND user_id = ?) as user_poll_votes
                FROM {$this->table_posts} p
                LEFT JOIN user_profiles up ON p.user_id = up.id
                WHERE p.id = ? AND p.client_id = ? AND p.deleted_at IS NULL 
                AND p.status = 'active'
                AND (p.visibility = 'global' OR p.visibility = 'group_specific')";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $userId, $postId, $clientId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post) {
            // Process post data similar to getUserPosts
            $post['author'] = [
                'id' => $post['user_id'],
                'name' => $post['author_name'],
                'email' => $post['author_email'],
                'avatar' => $post['author_avatar'],
                'role' => $post['author_role']
            ];

            // Parse media files
            $post['media'] = [];
            if ($post['media_files']) {
                $mediaFiles = json_decode($post['media_files'], true);
                if (is_array($mediaFiles)) {
                    // Transform media data to include proper fields for frontend
                    $post['media'] = array_map(function($file) {
                        return [
                            'filename' => $file['filename'] ?? '',
                            'filepath' => $file['filepath'] ?? '',
                            'filesize' => $file['filesize'] ?? 0,
                            'filetype' => $file['filetype'] ?? '',
                            'type' => $file['filetype'] ?? '', // MIME type for frontend
                            'url' => $file['filepath'] ?? '', // File path as URL for frontend
                            'name' => $file['filename'] ?? '' // Filename for frontend
                        ];
                    }, $mediaFiles);
                }
            }

            // Parse link preview
            $post['link_preview'] = null;
            if ($post['link_preview']) {
                $linkPreview = json_decode($post['link_preview'], true);
                if (is_array($linkPreview)) {
                    $post['link_preview'] = $linkPreview;
                }
            }

            // Parse poll data
            $post['poll'] = null;
            if ($post['poll_data']) {
                $pollData = json_decode($post['poll_data'], true);
                if (is_array($pollData)) {
                    $post['poll'] = $pollData;
                    
                    // Add user's selected options
                    $post['poll']['user_selected_options'] = [];
                    if ($post['user_poll_votes']) {
                        $userVotes = explode(',', $post['user_poll_votes']);
                        $post['poll']['user_selected_options'] = array_map('intval', $userVotes);
                    }
                }
            }

            // Parse tags
            $post['tags'] = $post['tags'] ? explode(',', $post['tags']) : [];

            // Format dates
            $post['formatted_date'] = date('M j, Y', strtotime($post['created_at']));
            $post['formatted_time'] = date('g:i A', strtotime($post['created_at']));
            $post['time_ago'] = $this->timeAgo($post['created_at']);

            // Set permissions
            $post['can_react'] = true;
            $post['can_comment'] = !$post['comments_locked'];
        }

        return $post;
    }

    // Toggle post reaction
    public function toggleReaction($postId, $userId, $reactionType, $clientId = null) {
        try {
            // Check if user already reacted
            $checkSql = "SELECT id, reaction_type FROM {$this->table_reactions} WHERE post_id = ? AND user_id = ?";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute([$postId, $userId]);
            $existingReaction = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingReaction) {
                if ($existingReaction['reaction_type'] === $reactionType) {
                    // Remove reaction
                    $deleteSql = "DELETE FROM {$this->table_reactions} WHERE id = ?";
                    $deleteStmt = $this->conn->prepare($deleteSql);
                    $deleteStmt->execute([$existingReaction['id']]);
                    return ['success' => true, 'action' => 'removed', 'reaction_type' => null];
                } else {
                    // Update reaction
                    $updateSql = "UPDATE {$this->table_reactions} SET reaction_type = ? WHERE id = ?";
                    $updateStmt = $this->conn->prepare($updateSql);
                    $updateStmt->execute([$reactionType, $existingReaction['id']]);
                    return ['success' => true, 'action' => 'updated', 'reaction_type' => $reactionType];
                }
            } else {
                // Add new reaction
                $insertSql = "INSERT INTO {$this->table_reactions} (post_id, user_id, reaction_type, client_id, created_at) VALUES (?, ?, ?, ?, NOW())";
                $insertStmt = $this->conn->prepare($insertSql);
                $insertStmt->execute([$postId, $userId, $reactionType, $clientId]);
                return ['success' => true, 'action' => 'added', 'reaction_type' => $reactionType];
            }
        } catch (Exception $e) {
            error_log("Error in toggleReaction: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error processing reaction'];
        }
    }

    // Get post comments
    public function getPostComments($postId) {
        $sql = "SELECT c.*,
                       up.full_name as author_name,
                       up.profile_picture as author_avatar,
                       up.system_role as author_role
                FROM {$this->table_comments} c
                LEFT JOIN user_profiles up ON c.user_id = up.id
                WHERE c.post_id = ? AND c.status = 'active'
                ORDER BY c.created_at ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$postId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process comments
        foreach ($comments as &$comment) {
            $comment['author'] = [
                'id' => $comment['user_id'],
                'name' => $comment['author_name'],
                'avatar' => $comment['author_avatar'],
                'role' => $comment['author_role']
            ];
            $comment['time_ago'] = $this->timeAgo($comment['created_at']);
            // Map body to content for consistency with frontend
            $comment['content'] = $comment['body'];
        }

        return $comments;
    }

    // Vote on poll
    public function votePoll($pollId, $userId, $options, $clientId = null) {
        try {
            // First, check if user has already voted
            $checkSql = "SELECT id FROM feed_poll_votes WHERE post_id = ? AND user_id = ?";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute([$pollId, $userId]);
            $existingVote = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingVote) {
                return ['success' => false, 'message' => 'You have already voted on this poll'];
            }

            // Insert vote(s)
            $insertSql = "INSERT INTO feed_poll_votes (post_id, user_id, client_id, poll_option_index, created_at) VALUES (?, ?, ?, ?, NOW())";
            $insertStmt = $this->conn->prepare($insertSql);
            
            foreach ($options as $optionIndex) {
                $insertStmt->execute([$pollId, $userId, $clientId, $optionIndex]);
            }

            return ['success' => true, 'message' => 'Vote submitted successfully'];

        } catch (Exception $e) {
            error_log("Error in votePoll: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error submitting vote'];
        }
    }

    // Add comment
    public function addComment($postId, $userId, $content, $clientId = null) {
        try {
            $sql = "INSERT INTO {$this->table_comments} (post_id, user_id, client_id, body, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'active', NOW(), NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$postId, $userId, $clientId, $content]);

            return ['success' => true, 'message' => 'Comment added successfully'];
        } catch (Exception $e) {
            error_log("Error in addComment: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error adding comment'];
        }
    }

    // Helper function to calculate time ago
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' minutes ago';
        if ($time < 86400) return floor($time/3600) . ' hours ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        if ($time < 31536000) return floor($time/2592000) . ' months ago';
        return floor($time/31536000) . ' years ago';
    }
}
?>
