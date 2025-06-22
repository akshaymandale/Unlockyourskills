<?php

require_once 'config/Database.php';

class SocialFeedModel {
    private $conn;
    private $table_posts = 'feed_posts';
    private $table_comments = 'feed_comments';
    private $table_reactions = 'feed_reactions';
    private $table_reports = 'feed_reports';
    private $table_notifications = 'feed_notifications';
    private $table_media = 'feed_media_files';
    private $table_poll_votes = 'feed_poll_votes';
    private $table_user_settings = 'feed_user_settings';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get posts with filters, search, and pagination
     */
    public function getPosts($page = 1, $limit = 10, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $whereConditions = ["p.status IN ('active', 'reported', 'archived')"];
            $params = [];

            // Add filters
            if (isset($filters['search']) && !empty($filters['search'])) {
                $whereConditions[] = '(p.title LIKE ? OR p.body LIKE ? OR up.full_name LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if (isset($filters['post_type']) && !empty($filters['post_type'])) {
                $whereConditions[] = 'p.post_type = ?';
                $params[] = $filters['post_type'];
            }

            if (isset($filters['visibility']) && !empty($filters['visibility'])) {
                $whereConditions[] = 'p.visibility = ?';
                $params[] = $filters['visibility'];
            }

            if (isset($filters['status']) && !empty($filters['status'])) {
                $whereConditions[] = 'p.status = ?';
                $params[] = $filters['status'];
            }

            if (isset($filters['is_pinned']) && $filters['is_pinned'] !== '') {
                $whereConditions[] = 'p.is_pinned = ?';
                $params[] = $filters['is_pinned'];
            }

            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $whereConditions[] = 'DATE(p.created_at) >= ?';
                $params[] = $filters['date_from'];
            }

            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $whereConditions[] = 'DATE(p.created_at) <= ?';
                $params[] = $filters['date_to'];
            }

            if (isset($filters['client_id']) && $filters['client_id']) {
                $whereConditions[] = 'p.client_id = ?';
                $params[] = $filters['client_id'];
            }

            $whereClause = implode(' AND ', $whereConditions);

            // Get total count
            $countQuery = "
                SELECT COUNT(*) as total
                FROM {$this->table_posts} p
                LEFT JOIN user_profiles up ON p.user_id = up.id
                WHERE {$whereClause}
            ";

            $stmt = $this->conn->prepare($countQuery);
            $stmt->execute($params);
            $totalPosts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Get posts with pagination
            $query = "
                SELECT 
                    p.*,
                    up.full_name as author_name,
                    up.email as author_email,
                    up.profile_picture as author_avatar,
                    up.system_role as author_role,
                    CASE 
                        WHEN p.user_id = ? THEN 1 
                        ELSE 0 
                    END as can_edit,
                    CASE 
                        WHEN p.user_id = ? OR ? = 'super_admin' THEN 1 
                        ELSE 0 
                    END as can_delete,
                    (SELECT COUNT(*) FROM feed_comments WHERE post_id = p.id) as comment_count,
                    (SELECT COUNT(*) FROM feed_reactions WHERE post_id = p.id) as reaction_count,
                    (SELECT COUNT(*) FROM feed_reports WHERE post_id = p.id AND status != 'dismissed') as report_count
                FROM {$this->table_posts} p
                LEFT JOIN user_profiles up ON p.user_id = up.id
                WHERE {$whereClause}
                ORDER BY p.is_pinned DESC, p.created_at DESC
                LIMIT {$limit} OFFSET {$offset}
            ";

            // Add user permissions to params AFTER the WHERE clause params
            $userId = $_SESSION['user']['id'] ?? 0;
            $userRole = $_SESSION['user']['system_role'] ?? '';
            
            // Create a new params array with the correct order
            $finalParams = [];
            $finalParams[] = $userId; // can_edit parameter
            $finalParams[] = $userId; // can_delete parameter
            $finalParams[] = $userRole; // can_delete role parameter
            foreach ($params as $p) $finalParams[] = $p; // WHERE clause params (client_id, etc)

            $stmt = $this->conn->prepare($query);
            $stmt->execute($finalParams);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Format posts
            foreach ($posts as &$post) {
                $post['author'] = [
                    'id' => $post['user_id'],
                    'name' => $post['author_name'],
                    'email' => $post['author_email'],
                    'avatar' => $post['author_avatar'],
                    'role' => $post['author_role']
                ];

                // Get media files
                $post['media'] = $this->getPostMedia($post['id']);

                // Get poll data if exists
                if ($post['post_type'] === 'poll') {
                    $post['poll'] = $this->getPostPoll($post['id']);
                }

                // Get link data if exists
                if ($post['post_type'] === 'link' && !empty($post['link_preview'])) {
                    $post['link'] = json_decode($post['link_preview'], true);
                }

                // Get reactions
                $post['reactions'] = $this->getPostReactions($post['id']);
            }

            // Calculate pagination
            $totalPages = ceil($totalPosts / $limit);
            $pagination = [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_posts' => $totalPosts,
                'per_page' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ];

            return [
                'success' => true,
                'posts' => $posts,
                'pagination' => $pagination
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to load posts: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a new post
     */
    public function createPost($postData, $mediaFiles = [], $pollData = null, $linkData = null) {
        try {
            error_log('SocialFeedModel::createPost - Starting post creation');
            error_log('SocialFeedModel::createPost - Post data: ' . print_r($postData, true));
            error_log('SocialFeedModel::createPost - Media files: ' . print_r($mediaFiles, true));
            error_log('SocialFeedModel::createPost - Poll data: ' . print_r($pollData, true));
            error_log('SocialFeedModel::createPost - Link data: ' . print_r($linkData, true));
            
            $this->conn->beginTransaction();

            // Insert post
            $query = "
                INSERT INTO {$this->table_posts} (
                    user_id, client_id, title, body, tags, post_type, visibility, 
                    media_files, link_preview, poll_data, is_pinned, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $postData['author_id'],
                $postData['client_id'],
                $postData['title'] ?? null,
                $postData['content'],
                $postData['tags'] ?? null,
                $postData['post_type'],
                $postData['visibility'],
                !empty($mediaFiles) ? json_encode($mediaFiles) : null,
                $linkData ? json_encode($linkData) : null,
                $pollData ? json_encode($pollData) : null,
                $postData['is_pinned']
            ]);

            $postId = $this->conn->lastInsertId();

            // Handle media files
            if (!empty($mediaFiles)) {
                error_log('SocialFeedModel::createPost - Inserting ' . count($mediaFiles) . ' media files');
                foreach ($mediaFiles as $media) {
                    error_log('SocialFeedModel::createPost - Inserting media file: ' . print_r($media, true));
                    $mediaQuery = "
                        INSERT INTO feed_media_files (
                            post_id, client_id, file_name, file_path, file_type, 
                            file_size, mime_type, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ";
                    $mediaStmt = $this->conn->prepare($mediaQuery);
                    $result = $mediaStmt->execute([
                        $postId,
                        $postData['client_id'],
                        $media['filename'],
                        $media['filepath'],
                        $this->getFileType($media['filetype']),
                        $media['filesize'],
                        $media['filetype']
                    ]);
                    
                    if ($result) {
                        error_log('SocialFeedModel::createPost - Successfully inserted media file: ' . $media['filename']);
                    } else {
                        error_log('SocialFeedModel::createPost - Failed to insert media file: ' . $media['filename']);
                        error_log('SocialFeedModel::createPost - PDO error: ' . print_r($mediaStmt->errorInfo(), true));
                    }
                }
            } else {
                error_log('SocialFeedModel::createPost - No media files to insert');
            }

            $this->conn->commit();

            return [
                'success' => true,
                'post_id' => $postId,
                'message' => 'Post created successfully!'
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Failed to create post: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get report details for a post
     */
    private function getPostReports($postId)
    {
        try {
            $query = "
                SELECT 
                    r.*,
                    up.full_name as reporter_name,
                    up.profile_picture as reporter_avatar
                FROM {$this->table_reports} r
                LEFT JOIN user_profiles up ON r.reported_by_user_id = up.id
                WHERE r.post_id = ? AND r.status != 'dismissed'
                ORDER BY r.created_at DESC
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([$postId]);
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Enhance reports with reporter data
            foreach ($reports as &$report) {
                $report['reporter'] = [
                    'id' => $report['reported_by_user_id'],
                    'name' => $report['reporter_name'],
                    'avatar' => $report['reporter_avatar']
                ];
            }

            return $reports;
        } catch (Exception $e) {
            error_log('Error getting post reports: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get post by ID with all related data
     */
    public function getPostById($postId, $clientId = null)
    {
        try {
            $whereConditions = ['p.id = ?'];
            $params = [$postId];

            if ($clientId) {
                $whereConditions[] = 'p.client_id = ?';
                $params[] = $clientId;
            }

            $whereClause = implode(' AND ', $whereConditions);

            $query = "
                SELECT 
                    p.*,
                    up.full_name,
                    up.email,
                    up.profile_picture,
                    up.system_role
                FROM {$this->table_posts} p
                LEFT JOIN user_profiles up ON p.user_id = up.id
                WHERE {$whereClause}
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                return [
                    'success' => false,
                    'message' => 'Post not found'
                ];
            }

            // Enhance post with additional data
            $post['media'] = $this->getPostMedia($postId);
            $post['poll'] = $this->getPostPoll($postId);
            
            // Get link data if exists
            if ($post['post_type'] === 'link' && !empty($post['link_preview'])) {
                $post['link'] = json_decode($post['link_preview'], true);
            }
            
            $post['reactions'] = $this->getPostReactions($postId);
            $post['comments'] = $this->getComments($postId, $clientId)['comments'] ?? [];
            $post['reports'] = $this->getPostReports($postId);
            $post['report_count'] = count($post['reports']);
            $post['author'] = [
                'id' => $post['user_id'],
                'name' => $post['full_name'],
                'email' => $post['email'],
                'avatar' => $post['profile_picture'],
                'role' => $post['system_role']
            ];
            $post['can_edit'] = $this->canEditPost($post['user_id'], $post['client_id']);
            $post['can_delete'] = $this->canDeletePost($post['user_id'], $post['client_id']);

            return [
                'success' => true,
                'post' => $post
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to load post: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a post
     */
    public function deletePost($postId, $userId, $clientId = null)
    {
        try {
            // Check if user can delete the post
            $post = $this->getPostById($postId, $clientId);
            if (!$post['success']) {
                return $post;
            }

            if (!$this->canDeletePost($post['post']['user_id'], $post['post']['client_id'])) {
                return [
                    'success' => false,
                    'message' => 'You do not have permission to delete this post'
                ];
            }

            $this->conn->beginTransaction();

            // Soft delete the post
            $query = "UPDATE {$this->table_posts} SET status = 'deleted', deleted_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$postId]);

            // Soft delete related comments
            $commentQuery = "UPDATE {$this->table_comments} SET status = 'deleted', deleted_at = NOW() WHERE post_id = ?";
            $commentStmt = $this->conn->prepare($commentQuery);
            $commentStmt->execute([$postId]);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Post deleted successfully!'
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Failed to delete post: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Add a comment to a post
     */
    public function addComment($commentData)
    {
        try {
            $query = "
                INSERT INTO {$this->table_comments} (
                    post_id, body, user_id, client_id, created_at, updated_at
                ) VALUES (?, ?, ?, ?, NOW(), NOW())
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $commentData['post_id'],
                $commentData['content'],
                $commentData['author_id'],
                $commentData['client_id']
            ]);

            $commentId = $this->conn->lastInsertId();

            return [
                'success' => true,
                'comment_id' => $commentId,
                'message' => 'Comment added successfully!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to add comment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get comments for a post
     */
    public function getComments($postId, $clientId = null)
    {
        try {
            $whereConditions = ['c.post_id = ?', 'c.status = "active"'];
            $params = [$postId];

            if ($clientId) {
                $whereConditions[] = 'c.client_id = ?';
                $params[] = $clientId;
            }

            $whereClause = implode(' AND ', $whereConditions);

            $query = "
                SELECT 
                    c.*,
                    u.full_name,
                    u.email,
                    u.profile_picture
                FROM {$this->table_comments} c
                LEFT JOIN user_profiles u ON c.user_id = u.id
                WHERE {$whereClause}
                ORDER BY c.created_at ASC
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Enhance comments with author data
            foreach ($comments as &$comment) {
                $comment['author'] = [
                    'id' => $comment['user_id'],
                    'name' => $comment['full_name'],
                    'email' => $comment['email'],
                    'avatar' => $comment['profile_picture']
                ];
            }

            return [
                'success' => true,
                'comments' => $comments
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to load comments: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Toggle reaction on a post
     */
    public function toggleReaction($reactionData)
    {
        try {
            // Check if reaction already exists
            $checkQuery = "
                SELECT id FROM {$this->table_reactions} 
                WHERE post_id = ? AND user_id = ? AND reaction_type = ?
            ";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([
                $reactionData['post_id'],
                $reactionData['user_id'],
                $reactionData['reaction_type']
            ]);

            $existingReaction = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingReaction) {
                // Remove existing reaction
                $deleteQuery = "DELETE FROM {$this->table_reactions} WHERE id = ?";
                $deleteStmt = $this->conn->prepare($deleteQuery);
                $deleteStmt->execute([$existingReaction['id']]);
                $isReacted = false;
            } else {
                // Add new reaction
                $insertQuery = "
                    INSERT INTO {$this->table_reactions} (
                        post_id, user_id, reaction_type, client_id, created_at
                    ) VALUES (?, ?, ?, ?, NOW())
                ";
                $insertStmt = $this->conn->prepare($insertQuery);
                $insertStmt->execute([
                    $reactionData['post_id'],
                    $reactionData['user_id'],
                    $reactionData['reaction_type'],
                    $reactionData['client_id']
                ]);
                $isReacted = true;
            }

            // Get updated reaction count
            $countQuery = "
                SELECT COUNT(*) as count 
                FROM {$this->table_reactions} 
                WHERE post_id = ? AND reaction_type = ?
            ";
            $countStmt = $this->conn->prepare($countQuery);
            $countStmt->execute([
                $reactionData['post_id'],
                $reactionData['reaction_type']
            ]);
            $newCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];

            return [
                'success' => true,
                'new_count' => $newCount,
                'is_reacted' => $isReacted
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update reaction: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Report a post
     */
    public function reportPost($reportData)
    {
        try {
            // Check if already reported by this user
            $checkQuery = "
                SELECT id FROM {$this->table_reports} 
                WHERE post_id = ? AND reported_by_user_id = ?
            ";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([
                $reportData['post_id'],
                $reportData['reported_by_user_id']
            ]);

            if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
                return [
                    'success' => false,
                    'message' => 'You have already reported this post'
                ];
            }

            $this->conn->beginTransaction();

            // Insert report
            $reportQuery = "
                INSERT INTO {$this->table_reports} (
                    post_id, reported_by_user_id, report_reason, report_details, client_id, created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ";
            $reportStmt = $this->conn->prepare($reportQuery);
            $reportStmt->execute([
                $reportData['post_id'],
                $reportData['reported_by_user_id'],
                $reportData['reason'],
                $reportData['description'],
                $reportData['client_id']
            ]);

            // Update post status to reported
            $updateQuery = "
                UPDATE {$this->table_posts} 
                SET status = 'reported' 
                WHERE id = ?
            ";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->execute([$reportData['post_id']]);

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Report submitted successfully!'
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Failed to submit report: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Pin/unpin a post
     */
    public function pinPost($postId, $isPinned, $userId, $clientId = null)
    {
        try {
            // Check if user has permission to pin posts
            if (!$this->canPinPost($userId, $clientId)) {
                return [
                    'success' => false,
                    'message' => 'You do not have permission to pin posts'
                ];
            }

            $query = "UPDATE {$this->table_posts} SET is_pinned = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$isPinned ? 1 : 0, $postId]);

            return [
                'success' => true,
                'message' => $isPinned ? 'Post pinned successfully!' : 'Post unpinned successfully!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update pin status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update post status (activate, pause, resume, archive, etc.)
     */
    public function updateStatus($postId, $status, $userId, $clientId = null)
    {
        try {
            // Validate status
            $validStatuses = ['draft', 'active', 'archived'];
            if (!in_array($status, $validStatuses)) {
                return [
                    'success' => false,
                    'message' => 'Invalid status provided.'
                ];
            }

            // Check if user has permission to update this post
            $checkQuery = "
                SELECT user_id, client_id FROM {$this->table_posts} 
                WHERE id = ? AND deleted_at IS NULL
            ";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([$postId]);
            $post = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                return [
                    'success' => false,
                    'message' => 'Post not found.'
                ];
            }

            // Check permissions (author or super admin can update)
            if (!$this->canEditPost($post['user_id'], $clientId)) {
                return [
                    'success' => false,
                    'message' => 'You do not have permission to update this post.'
                ];
            }

            // Update post status
            $query = "
                UPDATE {$this->table_posts} 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$status, $postId]);

            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Failed to update post status.'
                ];
            }

            return [
                'success' => true,
                'message' => "Post status updated to " . ucfirst($status) . " successfully!"
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update post status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vote on a poll
     */
    public function votePoll($voteData)
    {
        try {
            // Check if user already voted
            $checkQuery = "
                SELECT id FROM {$this->table_poll_votes} 
                WHERE post_id = ? AND user_id = ?
            ";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute([
                $voteData['poll_id'],
                $voteData['user_id']
            ]);

            if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
                return [
                    'success' => false,
                    'message' => 'You have already voted on this poll'
                ];
            }

            // Insert vote
            $voteQuery = "
                INSERT INTO {$this->table_poll_votes} (
                    post_id, user_id, client_id, poll_option_index, created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ";
            $voteStmt = $this->conn->prepare($voteQuery);
            $voteStmt->execute([
                $voteData['poll_id'],
                $voteData['user_id'],
                $voteData['client_id'],
                $voteData['option_id']
            ]);

            return [
                'success' => true,
                'message' => 'Vote recorded successfully!'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to record vote: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get notifications for a user
     */
    public function getNotifications($userId, $clientId = null)
    {
        try {
            $whereConditions = ['n.user_id = ?'];
            $params = [$userId];

            if ($clientId) {
                $whereConditions[] = 'n.client_id = ?';
                $params[] = $clientId;
            }

            $whereClause = implode(' AND ', $whereConditions);

            $query = "
                SELECT 
                    n.*,
                    u.full_name,
                    u.email,
                    u.profile_picture
                FROM {$this->table_notifications} n
                LEFT JOIN users u ON n.triggered_by_user_id = u.id
                WHERE {$whereClause}
                ORDER BY n.created_at DESC
                LIMIT 50
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'notifications' => $notifications
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to load notifications: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing post
     */
    public function updatePost($postData, $mediaFiles = [], $filesToDelete = [], $pollData = null, $linkData = null) {
        try {
            $this->conn->beginTransaction();

            // Update post
            $query = "
                UPDATE {$this->table_posts} 
                SET title = ?, body = ?, tags = ?, post_type = ?, visibility = ?, 
                    link_preview = ?, is_pinned = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ? AND client_id = ?
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $postData['title'] ?? null,
                $postData['content'],
                $postData['tags'] ?? null,
                $postData['post_type'],
                $postData['visibility'],
                $linkData ? json_encode($linkData) : null,
                $postData['is_pinned'],
                $postData['id'],
                $postData['author_id'],
                $postData['client_id']
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Post not found or you do not have permission to edit it');
            }

            // Delete specified media files
            if (!empty($filesToDelete)) {
                foreach ($filesToDelete as $filename) {
                    // Get file info from database
                    $fileQuery = "SELECT * FROM feed_media_files WHERE post_id = ? AND file_name = ?";
                    $fileStmt = $this->conn->prepare($fileQuery);
                    $fileStmt->execute([$postData['id'], $filename]);
                    $fileInfo = $fileStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($fileInfo) {
                        // Delete physical file
                        $filePath = $fileInfo['file_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        
                        // Delete from database
                        $deleteQuery = "DELETE FROM feed_media_files WHERE id = ?";
                        $deleteStmt = $this->conn->prepare($deleteQuery);
                        $deleteStmt->execute([$fileInfo['id']]);
                    }
                }
            }

            // Add new media files
            if (!empty($mediaFiles)) {
                // Handle $_FILES format (array with name, type, tmp_name, error, size keys)
                for ($i = 0; $i < count($mediaFiles['name']); $i++) {
                    error_log('MODEL: Processing mediaFile index ' . $i . ': ' . print_r([
                        'name' => $mediaFiles['name'][$i],
                        'type' => $mediaFiles['type'][$i],
                        'tmp_name' => $mediaFiles['tmp_name'][$i],
                        'size' => $mediaFiles['size'][$i],
                        'error' => $mediaFiles['error'][$i]
                    ], true));
                    
                    if ($mediaFiles['error'][$i] === UPLOAD_ERR_OK) {
                        $uploadDir = 'uploads/social_feed/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $filename = time() . '_' . $mediaFiles['name'][$i];
                        $filePath = $uploadDir . $filename;
                        $fileType = $this->getFileType($mediaFiles['type'][$i]);
                        
                        error_log('MODEL: About to move_uploaded_file from ' . $mediaFiles['tmp_name'][$i] . ' to ' . $filePath);
                        
                        if (move_uploaded_file($mediaFiles['tmp_name'][$i], $filePath)) {
                            error_log('MODEL: move_uploaded_file succeeded');
                            $mediaQuery = "
                                INSERT INTO feed_media_files (
                                    post_id, client_id, file_name, file_path, file_type, 
                                    file_size, mime_type, created_at
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                            ";
                            $mediaStmt = $this->conn->prepare($mediaQuery);
                            $mediaStmt->execute([
                                $postData['id'],
                                $postData['client_id'],
                                $filename,
                                $filePath,
                                $fileType,
                                $mediaFiles['size'][$i],
                                $mediaFiles['type'][$i]
                            ]);
                        } else {
                            error_log('MODEL: move_uploaded_file FAILED');
                        }
                    } else {
                        error_log('MODEL: File upload error: ' . $mediaFiles['error'][$i]);
                    }
                }
            }

            // Update the media_files column in feed_posts table to match feed_media_files
            $this->updatePostMediaFilesColumn($postData['id']);

            // Handle poll data
            if ($pollData) {
                // Delete existing poll if any
                $deletePollQuery = "DELETE FROM feed_poll_options WHERE poll_id IN (SELECT id FROM feed_polls WHERE post_id = ?)";
                $deletePollStmt = $this->conn->prepare($deletePollQuery);
                $deletePollStmt->execute([$postData['id']]);

                $deletePollQuery = "DELETE FROM feed_polls WHERE post_id = ?";
                $deletePollStmt = $this->conn->prepare($deletePollQuery);
                $deletePollStmt->execute([$postData['id']]);

                // Insert new poll
                $pollQuery = "
                    INSERT INTO feed_polls (
                        post_id, question, allow_multiple_votes, client_id, created_at
                    ) VALUES (?, ?, ?, ?, NOW())
                ";
                $pollStmt = $this->conn->prepare($pollQuery);
                $pollStmt->execute([
                    $postData['id'],
                    $pollData['question'],
                    $pollData['allow_multiple_votes'],
                    $postData['client_id']
                ]);

                $pollId = $this->conn->lastInsertId();

                // Insert poll options
                foreach ($pollData['options'] as $option) {
                    $optionQuery = "
                        INSERT INTO feed_poll_options (
                            poll_id, text, votes, created_at
                        ) VALUES (?, ?, 0, NOW())
                    ";
                    $optionStmt = $this->conn->prepare($optionQuery);
                    $optionStmt->execute([$pollId, $option]);
                }
            }

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Post updated successfully!'
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => 'Failed to update post: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update the media_files column in feed_posts to match feed_media_files table
     */
    private function updatePostMediaFilesColumn($postId) {
        try {
            // Get all media files for this post from feed_media_files table
            $query = "SELECT file_name, file_path, file_size, mime_type FROM feed_media_files WHERE post_id = ? ORDER BY created_at ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$postId]);
            $mediaFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Transform to match the JSON format used in media_files column
            $mediaData = [];
            foreach ($mediaFiles as $file) {
                $mediaData[] = [
                    'filename' => $file['file_name'],
                    'filepath' => $file['file_path'],
                    'filesize' => $file['file_size'],
                    'filetype' => $file['mime_type']
                ];
            }
            
            // Update the media_files column in feed_posts table
            $updateQuery = "UPDATE feed_posts SET media_files = ? WHERE id = ?";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->execute([
                !empty($mediaData) ? json_encode($mediaData) : null,
                $postId
            ]);
            
            error_log("SocialFeedModel::updatePostMediaFilesColumn - Updated media_files column for post {$postId} with " . count($mediaData) . " files");
        } catch (Exception $e) {
            error_log("SocialFeedModel::updatePostMediaFilesColumn - Error: " . $e->getMessage());
        }
    }

    // Helper methods

    private function getFileType($mimeType)
    {
        if (strpos($mimeType, 'image/') === 0) {
            return 'image';
        } elseif (strpos($mimeType, 'video/') === 0) {
            return 'video';
        } elseif (strpos($mimeType, 'audio/') === 0) {
            return 'audio';
        } else {
            return 'document';
        }
    }

    /**
     * Get media files for a post
     */
    private function getPostMedia($postId) {
        try {
            error_log("SocialFeedModel::getPostMedia - Getting media files for post ID: {$postId}");
            $query = "SELECT * FROM feed_media_files WHERE post_id = ? ORDER BY created_at ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$postId]);
            $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Transform the data to include url field for display
            foreach ($media as &$item) {
                $item['url'] = $item['file_path'];
                $item['type'] = $item['mime_type'];
                $item['filename'] = $item['file_name'];
            }
            
            error_log("SocialFeedModel::getPostMedia - Found " . count($media) . " media files: " . print_r($media, true));
            return $media;
        } catch (Exception $e) {
            error_log("SocialFeedModel::getPostMedia - Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get poll data for a post
     */
    private function getPostPoll($postId) {
        try {
            $query = "SELECT * FROM feed_polls WHERE post_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$postId]);
            $poll = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($poll) {
                // Get poll options
                $query = "SELECT * FROM feed_poll_options WHERE poll_id = ? ORDER BY id ASC";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$poll['id']]);
                $poll['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Calculate total votes
                $poll['total_votes'] = array_sum(array_column($poll['options'], 'votes'));
            }

            return $poll;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get reactions for a post
     */
    private function getPostReactions($postId) {
        try {
            $query = "SELECT reaction_type, COUNT(*) as count FROM feed_reactions WHERE post_id = ? GROUP BY reaction_type";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$postId]);
            $reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = [];
            foreach ($reactions as $reaction) {
                $result[$reaction['reaction_type']] = (int)$reaction['count'];
            }

            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    private function canEditPost($authorId, $clientId) {
        $currentUserId = $_SESSION['user']['id'] ?? 0;
        $currentUserRole = $_SESSION['user']['system_role'] ?? '';
        $currentClientId = $_SESSION['user']['client_id'] ?? null;

        // Author can edit their own posts
        if ($currentUserId == $authorId) {
            return true;
        }

        // Super admin can edit any post
        if ($currentUserRole === 'super_admin') {
            return true;
        }

        // Admin can edit posts in their organization
        if ($currentUserRole === 'admin' && $currentClientId == $clientId) {
            return true;
        }

        return false;
    }

    private function canDeletePost($authorId, $clientId) {
        return $this->canEditPost($authorId, $clientId);
    }

    private function canPinPost($userId, $clientId) {
        $currentUserRole = $_SESSION['user']['system_role'] ?? '';
        $currentClientId = $_SESSION['user']['client_id'] ?? null;

        // Super admin can pin any post
        if ($currentUserRole === 'super_admin') {
            return true;
        }

        // Admin can pin posts in their organization
        if ($currentUserRole === 'admin' && $currentClientId == $clientId) {
            return true;
        }

        return false;
    }
} 