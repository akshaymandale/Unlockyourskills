<?php
require_once 'config/Database.php';

class PollModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get all polls with pagination and search
     */
    public function getAllPolls($limit = 10, $offset = 0, $search = '', $filters = [], $clientId = null) {
        $sql = "SELECT p.*,
                       up.full_name as created_by_name,
                       (SELECT COUNT(*) FROM poll_votes pv WHERE pv.poll_id = p.id AND pv.is_deleted = 0) as total_votes,
                       (SELECT COUNT(DISTINCT pv.user_id) FROM poll_votes pv WHERE pv.poll_id = p.id AND pv.is_deleted = 0 AND pv.user_id IS NOT NULL) as unique_voters
                FROM polls p
                LEFT JOIN user_profiles up ON p.created_by = up.id
                WHERE p.is_deleted = 0";
        
        $params = [];
        
        if ($clientId !== null) {
            $sql .= " AND p.client_id = ?";
            $params[] = $clientId;
        }
        
        if (!empty($search)) {
            $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND p.type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['target_audience'])) {
            $sql .= " AND p.target_audience = ?";
            $params[] = $filters['target_audience'];
        }

        // Date range filters
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(p.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(p.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        // Legacy date_range filter (for backward compatibility)
        if (!empty($filters['date_range'])) {
            switch ($filters['date_range']) {
                case 'active':
                    $sql .= " AND p.start_datetime <= NOW() AND p.end_datetime >= NOW()";
                    break;
                case 'upcoming':
                    $sql .= " AND p.start_datetime > NOW()";
                    break;
                case 'ended':
                    $sql .= " AND p.end_datetime < NOW()";
                    break;
            }
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $index => $param) {
            $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $polls = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Add RBAC flags if not set by controller
        if (!empty($_SESSION['user'])) {
            require_once 'includes/permission_helper.php';
            $currentUser = $_SESSION['user'];
            foreach ($polls as &$poll) {
                if (!isset($poll['can_edit'])) {
                    $poll['can_edit'] = (canEdit('opinion_polls') && ($currentUser['system_role'] === 'super_admin' || $poll['created_by'] == $currentUser['id'])) ? 1 : 0;
                }
                if (!isset($poll['can_delete'])) {
                    $poll['can_delete'] = (canDelete('opinion_polls') && ($currentUser['system_role'] === 'super_admin' || $poll['created_by'] == $currentUser['id'])) ? 1 : 0;
                }
            }
            unset($poll);
        }
        return $polls;
    }

    /**
     * Get poll by ID
     */
    public function getPollById($id, $clientId = null) {
        $sql = "SELECT p.*,
                       up.full_name as created_by_name,
                       (SELECT COUNT(*) FROM poll_votes pv WHERE pv.poll_id = p.id AND pv.is_deleted = 0) as total_votes,
                       (SELECT COUNT(DISTINCT pv.user_id) FROM poll_votes pv WHERE pv.poll_id = p.id AND pv.is_deleted = 0 AND pv.user_id IS NOT NULL) as unique_voters
                FROM polls p
                LEFT JOIN user_profiles up ON p.created_by = up.id
                WHERE p.id = ? AND p.is_deleted = 0";
        
        $params = [$id];
        
        if ($clientId !== null) {
            $sql .= " AND p.client_id = ?";
            $params[] = $clientId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new poll
     */
    public function createPoll($data) {
        $sql = "INSERT INTO polls (
                    client_id, title, description, type, target_audience, course_id, group_id,
                    custom_field_id, custom_field_value, start_datetime, end_datetime, show_results, 
                    allow_anonymous, allow_vote_change, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            $data['client_id'],
            $data['title'],
            $data['description'],
            $data['type'],
            $data['target_audience'],
            $data['course_id'],
            $data['group_id'],
            $data['custom_field_id'] ?? null,
            $data['custom_field_value'] ?? null,
            $data['start_datetime'],
            $data['end_datetime'],
            $data['show_results'],
            $data['allow_anonymous'] ? 1 : 0,
            $data['allow_vote_change'] ? 1 : 0,
            $data['status'],
            $data['created_by']
        ]);

        return $result ? $this->conn->lastInsertId() : false;
    }

    /**
     * Update poll
     */
    public function updatePoll($id, $data) {
        $sql = "UPDATE polls SET
                    title = ?,
                    description = ?,
                    type = ?,
                    target_audience = ?,
                    course_id = ?,
                    group_id = ?,
                    custom_field_id = ?,
                    custom_field_value = ?,
                    start_datetime = ?,
                    end_datetime = ?,
                    show_results = ?,
                    allow_anonymous = ?,
                    allow_vote_change = ?,
                    status = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id = ? AND client_id = ?";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['type'],
            $data['target_audience'],
            $data['course_id'],
            $data['group_id'],
            $data['custom_field_id'] ?? null,
            $data['custom_field_value'] ?? null,
            $data['start_datetime'],
            $data['end_datetime'],
            $data['show_results'],
            $data['allow_anonymous'] ? 1 : 0,
            $data['allow_vote_change'] ? 1 : 0,
            $data['status'],
            $data['updated_by'],
            $id,
            $data['client_id']
        ]);
    }

    /**
     * Soft delete poll
     */
    public function deletePoll($id, $clientId) {
        $sql = "UPDATE polls SET is_deleted = 1, updated_at = NOW() WHERE id = ? AND client_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id, $clientId]);
    }

    /**
     * Get poll questions
     */
    public function getPollQuestions($pollId, $clientId = null) {
        $sql = "SELECT * FROM poll_questions WHERE poll_id = ? AND is_deleted = 0";
        $params = [$pollId];
        
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY question_order ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get poll options for a question
     */
    public function getPollOptions($questionId, $clientId = null) {
        $sql = "SELECT * FROM poll_options WHERE question_id = ? AND is_deleted = 0";
        $params = [$questionId];
        
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY option_order ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create poll question
     */
    public function createPollQuestion($data) {
        $sql = "INSERT INTO poll_questions (
                    poll_id, client_id, question_text, question_order, media_type, media_path, is_required
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            $data['poll_id'],
            $data['client_id'],
            $data['question_text'],
            $data['question_order'],
            $data['media_type'],
            $data['media_path'],
            $data['is_required'] ? 1 : 0
        ]);

        return $result ? $this->conn->lastInsertId() : false;
    }

    /**
     * Create poll option
     */
    public function createPollOption($data) {
        $sql = "INSERT INTO poll_options (
                    question_id, poll_id, client_id, option_text, option_order, media_type, media_path
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            $data['question_id'],
            $data['poll_id'],
            $data['client_id'],
            $data['option_text'],
            $data['option_order'],
            $data['media_type'],
            $data['media_path']
        ]);

        return $result ? $this->conn->lastInsertId() : false;
    }

    /**
     * Get unique poll statuses for filter dropdown
     */
    public function getUniqueStatuses($clientId = null) {
        $sql = "SELECT DISTINCT status FROM polls WHERE is_deleted = 0";
        $params = [];
        
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY status";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get unique poll types for filter dropdown
     */
    public function getUniqueTypes($clientId = null) {
        $sql = "SELECT DISTINCT type FROM polls WHERE is_deleted = 0";
        $params = [];
        
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY type";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Check if user has already voted in a poll
     */
    public function hasUserVoted($pollId, $userId, $clientId) {
        $sql = "SELECT COUNT(*) FROM poll_votes WHERE poll_id = ? AND user_id = ? AND client_id = ? AND is_deleted = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$pollId, $userId, $clientId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Submit vote
     */
    public function submitVote($data) {
        $sql = "INSERT INTO poll_votes (
                    poll_id, question_id, option_id, client_id, user_id, voter_ip, voter_session, comment
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['poll_id'],
            $data['question_id'],
            $data['option_id'],
            $data['client_id'],
            $data['user_id'],
            $data['voter_ip'],
            $data['voter_session'],
            $data['comment']
        ]);
    }

    /**
     * Check if poll can be edited
     * Poll can be edited only if:
     * 1. Poll is not started (start_datetime > now)
     * 2. Poll has no votes from users
     * 3. Poll status is draft or not active
     */
    public function canEditPoll($pollId, $clientId) {
        // Get poll details
        $poll = $this->getPollById($pollId, $clientId);
        if (!$poll) {
            return ['can_edit' => false, 'reason' => 'Poll not found'];
        }

        $now = new DateTime();
        $startTime = new DateTime($poll['start_datetime']);

        // Check if poll has started
        if ($startTime <= $now && $poll['status'] === 'active') {
            return ['can_edit' => false, 'reason' => 'Poll is currently live and cannot be edited'];
        }

        // Check if poll has any votes
        $voteCount = $this->getPollVoteCount($pollId, $clientId);
        if ($voteCount > 0) {
            return ['can_edit' => false, 'reason' => 'Poll has received votes and cannot be edited'];
        }

        // Check poll status - only draft and paused polls can be edited
        if (!in_array($poll['status'], ['draft', 'paused'])) {
            return ['can_edit' => false, 'reason' => 'Only draft or paused polls can be edited'];
        }

        return ['can_edit' => true, 'reason' => ''];
    }

    /**
     * Check if poll can be deleted
     * Similar restrictions as edit
     */
    public function canDeletePoll($pollId, $clientId) {
        // Get poll details
        $poll = $this->getPollById($pollId, $clientId);
        if (!$poll) {
            return ['can_delete' => false, 'reason' => 'Poll not found'];
        }

        $now = new DateTime();
        $startTime = new DateTime($poll['start_datetime']);

        // Check if poll is currently live
        if ($startTime <= $now && $poll['status'] === 'active') {
            return ['can_delete' => false, 'reason' => 'Poll is currently live and cannot be deleted'];
        }

        return ['can_delete' => true, 'reason' => ''];
    }

    /**
     * Get total vote count for a poll
     */
    public function getPollVoteCount($pollId, $clientId) {
        $sql = "SELECT COUNT(*) FROM poll_votes WHERE poll_id = ? AND client_id = ? AND is_deleted = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$pollId, $clientId]);
        return $stmt->fetchColumn();
    }

    /**
     * Delete poll questions and options
     */
    public function deletePollQuestions($pollId, $clientId) {
        // Soft delete options first
        $sql = "UPDATE poll_options SET is_deleted = 1 WHERE poll_id = ? AND client_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$pollId, $clientId]);

        // Soft delete questions
        $sql = "UPDATE poll_questions SET is_deleted = 1 WHERE poll_id = ? AND client_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$pollId, $clientId]);
    }

    /**
     * Update poll question
     */
    public function updatePollQuestion($id, $data) {
        $sql = "UPDATE poll_questions SET
                    question_text = ?,
                    question_order = ?,
                    media_type = ?,
                    media_path = ?,
                    is_required = ?,
                    updated_at = NOW()
                WHERE id = ? AND poll_id = ? AND client_id = ?";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['question_text'],
            $data['question_order'],
            $data['media_type'],
            $data['media_path'],
            $data['is_required'] ? 1 : 0,
            $id,
            $data['poll_id'],
            $data['client_id']
        ]);
    }

    /**
     * Update poll option
     */
    public function updatePollOption($id, $data) {
        $sql = "UPDATE poll_options SET
                    option_text = ?,
                    option_order = ?,
                    media_type = ?,
                    media_path = ?,
                    updated_at = NOW()
                WHERE id = ? AND question_id = ? AND poll_id = ? AND client_id = ?";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['option_text'],
            $data['option_order'],
            $data['media_type'],
            $data['media_path'],
            $id,
            $data['question_id'],
            $data['poll_id'],
            $data['client_id']
        ]);
    }

    /**
     * Get active polls for a specific user
     */
    public function getActivePollsForUser($userId, $clientId) {
        try {
            $sql = "SELECT p.*,
                           up.full_name as created_by_name,
                           (SELECT COUNT(*) FROM poll_votes pv WHERE pv.poll_id = p.id AND pv.is_deleted = 0) as total_votes,
                           (SELECT COUNT(DISTINCT pv.user_id) FROM poll_votes pv WHERE pv.poll_id = p.id AND pv.is_deleted = 0 AND pv.user_id IS NOT NULL) as unique_voters
                    FROM polls p
                    LEFT JOIN user_profiles up ON p.created_by = up.id
                    WHERE p.is_deleted = 0 
                    AND p.client_id = ? 
                    AND p.status = 'active'
                    AND p.start_datetime <= NOW() 
                    AND p.end_datetime >= NOW()
                    AND (p.target_audience = 'global' 
                         OR (p.target_audience = 'group_specific' AND p.custom_field_id IS NOT NULL AND p.custom_field_value IS NOT NULL))
                    ORDER BY p.created_at DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientId]);
            $polls = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get questions and options for each poll
            foreach ($polls as &$poll) {
                $poll['questions'] = $this->getPollQuestions($poll['id'], $clientId);
                foreach ($poll['questions'] as &$question) {
                    $question['options'] = $this->getPollOptions($question['id'], $clientId);
                }
            }

            return $polls;
        } catch (Exception $e) {
            error_log("PollModel::getActivePollsForUser - Database error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user's voting history
     */
    public function getUserVotes($userId, $clientId) {
        $sql = "SELECT pv.poll_id, pv.question_id, pv.option_id, pv.created_at
                FROM poll_votes pv
                INNER JOIN polls p ON pv.poll_id = p.id
                WHERE pv.user_id = ? 
                AND pv.client_id = ? 
                AND pv.is_deleted = 0
                AND p.is_deleted = 0
                ORDER BY pv.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Submit a vote for a poll (updated method)
     */
    public function submitVoteNew($pollId, $questionId, $optionIds, $userId, $clientId) {
        try {
            $this->conn->beginTransaction();

            // Check if user already voted for this question
            $checkSql = "SELECT id FROM poll_votes 
                        WHERE poll_id = ? AND question_id = ? AND user_id = ? AND client_id = ? AND is_deleted = 0";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute([$pollId, $questionId, $userId, $clientId]);
            
            if ($checkStmt->fetch()) {
                // User already voted, update existing vote
                $updateSql = "UPDATE poll_votes 
                             SET option_id = ?, updated_at = NOW() 
                             WHERE poll_id = ? AND question_id = ? AND user_id = ? AND client_id = ? AND is_deleted = 0";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->execute([$optionIds[0], $pollId, $questionId, $userId, $clientId]);
            } else {
                // Insert new vote
                $insertSql = "INSERT INTO poll_votes (poll_id, question_id, option_id, user_id, client_id, created_at) 
                             VALUES (?, ?, ?, ?, ?, NOW())";
                $insertStmt = $this->conn->prepare($insertSql);
                $insertStmt->execute([$pollId, $questionId, $optionIds[0], $userId, $clientId]);
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Vote submission error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get poll questions with results
     */
    public function getPollQuestionsWithResults($pollId, $clientId) {
        $sql = "SELECT pq.*, 
                       COUNT(pv.id) as total_votes
                FROM poll_questions pq
                LEFT JOIN poll_votes pv ON pq.id = pv.question_id AND pv.is_deleted = 0
                WHERE pq.poll_id = ? AND pq.client_id = ? AND pq.is_deleted = 0
                GROUP BY pq.id
                ORDER BY pq.question_order";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$pollId, $clientId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get options with vote counts for each question
        foreach ($questions as &$question) {
            $question['options'] = $this->getPollOptionsWithResults($question['id'], $clientId);
        }

        return $questions;
    }

    /**
     * Get poll options with vote counts
     */
    public function getPollOptionsWithResults($questionId, $clientId) {
        $sql = "SELECT po.*, 
                       COUNT(pv.id) as vote_count
                FROM poll_options po
                LEFT JOIN poll_votes pv ON po.id = pv.option_id AND pv.is_deleted = 0
                WHERE po.question_id = ? AND po.client_id = ? AND po.is_deleted = 0
                GROUP BY po.id
                ORDER BY po.option_order";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$questionId, $clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user's votes for a specific poll
     */
    public function getUserVotesForPoll($pollId, $userId, $clientId) {
        $sql = "SELECT pv.question_id, pv.option_id, po.option_text
                FROM poll_votes pv
                LEFT JOIN poll_options po ON pv.option_id = po.id
                WHERE pv.poll_id = ? AND pv.user_id = ? AND pv.client_id = ? AND pv.is_deleted = 0";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$pollId, $userId, $clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
