<?php
/**
 * Post-Requisite Completion Model
 * 
 * Handles post-requisite completion tracking for users
 */

require_once 'config/Database.php';

class PostRequisiteCompletionModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get or create post-requisite completion record
     */
    public function getOrCreateCompletion($userId, $courseId, $postRequisiteId, $contentType, $clientId) {
        try {
            $sql = "SELECT * FROM post_requisite_completion 
                    WHERE user_id = ? AND course_id = ? AND post_requisite_id = ? AND content_type = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $postRequisiteId, $contentType, $clientId]);
            
            $completion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($completion) {
                return $completion;
            }
            
            // Create new completion record
            $sql = "INSERT INTO post_requisite_completion (user_id, course_id, post_requisite_id, content_type, client_id) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $postRequisiteId, $contentType, $clientId]);
            
            $completionId = $this->conn->lastInsertId();
            
            // Return the newly created record
            return [
                'id' => $completionId,
                'user_id' => $userId,
                'course_id' => $courseId,
                'post_requisite_id' => $postRequisiteId,
                'content_type' => $contentType,
                'client_id' => $clientId,
                'completion_percentage' => 0.00,
                'is_completed' => 0,
                'completed_at' => null,
                'last_accessed_at' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Error in getOrCreateCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update post-requisite completion
     */
    public function updateCompletion($userId, $courseId, $postRequisiteId, $contentType, $clientId, $data) {
        try {
            $sql = "UPDATE post_requisite_completion SET 
                        completion_percentage = ?,
                        is_completed = ?,
                        last_accessed_at = NOW(),
                        updated_at = CURRENT_TIMESTAMP";
            
            // Set completed_at if post-requisite is completed
            if (isset($data['is_completed']) && $data['is_completed'] == 1) {
                $sql .= ", completed_at = NOW()";
            }
            
            $sql .= " WHERE user_id = ? AND course_id = ? AND post_requisite_id = ? AND content_type = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $data['completion_percentage'] ?? 0.00,
                $data['is_completed'] ?? 0,
                $userId,
                $courseId,
                $postRequisiteId,
                $contentType,
                $clientId
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error in updateCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark post-requisite as completed
     */
    public function markAsCompleted($userId, $courseId, $postRequisiteId, $contentType, $clientId) {
        try {
            $sql = "UPDATE post_requisite_completion SET 
                        completion_percentage = 100.00,
                        is_completed = 1,
                        completed_at = NOW(),
                        last_accessed_at = NOW(),
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ? AND course_id = ? AND post_requisite_id = ? AND content_type = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$userId, $courseId, $postRequisiteId, $contentType, $clientId]);
            
        } catch (Exception $e) {
            error_log("Error in markAsCompleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get post-requisite completion status
     */
    public function getCompletion($userId, $courseId, $postRequisiteId, $contentType, $clientId) {
        try {
            $sql = "SELECT * FROM post_requisite_completion 
                    WHERE user_id = ? AND course_id = ? AND post_requisite_id = ? AND content_type = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $postRequisiteId, $contentType, $clientId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error in getCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all post-requisite completions for a user in a course
     */
    public function getCoursePostRequisiteCompletions($userId, $courseId, $clientId) {
        try {
            $sql = "SELECT prc.*, cpr.description, cpr.sort_order
                    FROM post_requisite_completion prc
                    JOIN course_post_requisites cpr ON prc.post_requisite_id = cpr.content_id AND prc.content_type = cpr.content_type
                    WHERE prc.user_id = ? AND prc.course_id = ? AND prc.client_id = ?
                    ORDER BY cpr.sort_order ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $clientId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error in getCoursePostRequisiteCompletions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if post-requisite is completed based on its type
     */
    public function isPostRequisiteCompleted($userId, $courseId, $postRequisiteId, $contentType, $clientId) {
        try {
            $tableMap = [
                'assessment' => 'assessment_results',
                'assignment' => 'assignment_submissions',
                'feedback' => 'course_feedback_responses',
                'survey' => 'course_survey_responses'
            ];
            
            $table = $tableMap[$contentType] ?? null;
            if (!$table) {
                return false;
            }
            
            if ($contentType === 'survey') {
                $sql = "SELECT completed_at FROM $table 
                        WHERE user_id = ? AND course_id = ? AND survey_package_id = ? AND client_id = ? AND completed_at IS NOT NULL";
            } elseif ($contentType === 'feedback') {
                $sql = "SELECT completed_at FROM $table 
                        WHERE user_id = ? AND course_id = ? AND feedback_package_id = ? AND client_id = ? AND completed_at IS NOT NULL";
            } else {
                $sql = "SELECT is_completed FROM $table 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $postRequisiteId, $clientId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (in_array($contentType, ['survey', 'feedback'])) {
                return $result && !empty($result['completed_at']);
            } else {
                return $result && $result['is_completed'] == 1;
            }
            
        } catch (Exception $e) {
            error_log("Error in isPostRequisiteCompleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update post-requisite completion based on actual progress
     * Only creates completion records when post-requisite is actually completed
     */
    public function updatePostRequisiteCompletionFromProgress($userId, $courseId, $postRequisiteId, $contentType, $clientId) {
        try {
            $isCompleted = $this->isPostRequisiteCompleted($userId, $courseId, $postRequisiteId, $contentType, $clientId);
            $completionPercentage = $isCompleted ? 100.00 : 0.00;
            
            // Only create/update completion record if post-requisite is completed
            if ($isCompleted) {
                // Get or create completion record only when completed
                $completion = $this->getOrCreateCompletion($userId, $courseId, $postRequisiteId, $contentType, $clientId);
                
                // Update completion
                $this->updateCompletion($userId, $courseId, $postRequisiteId, $contentType, $clientId, [
                    'completion_percentage' => $completionPercentage,
                    'is_completed' => 1
                ]);
            }
            
            return [
                'completion_percentage' => $completionPercentage,
                'is_completed' => $isCompleted
            ];
            
        } catch (Exception $e) {
            error_log("Error in updatePostRequisiteCompletionFromProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Initialize post-requisite completions for a course
     */
    public function initializeCoursePostRequisites($userId, $courseId, $clientId) {
        try {
            // Get all post-requisites for the course
            $sql = "SELECT content_id, content_type 
                    FROM course_post_requisites 
                    WHERE course_id = ? AND is_deleted = 0";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            $postRequisites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($postRequisites as $postRequisite) {
                $this->getOrCreateCompletion(
                    $userId, 
                    $courseId, 
                    $postRequisite['content_id'], 
                    $postRequisite['content_type'], 
                    $clientId
                );
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error in initializeCoursePostRequisites: " . $e->getMessage());
            return false;
        }
    }
}
?>
