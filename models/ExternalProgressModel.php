<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/Database.php';

class ExternalProgressModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get or create external content progress record
     */
    public function getOrCreateProgress($userId, $courseId, $contentId, $externalPackageId, $clientId) {
        try {
            // Check if progress record exists
            $sql = "SELECT * FROM external_progress 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$progress) {
                // Get external content URL for the record
                $urlSql = "SELECT 
                    CASE 
                        WHEN audio_source = 'upload' AND audio_file IS NOT NULL THEN 
                            CASE 
                                WHEN audio_file LIKE 'http%' THEN audio_file
                                WHEN audio_file LIKE 'uploads/%' THEN audio_file
                                ELSE CONCAT('uploads/external/audio/', audio_file)
                            END
                        WHEN course_url IS NOT NULL THEN course_url
                        WHEN video_url IS NOT NULL THEN video_url
                        WHEN article_url IS NOT NULL THEN article_url
                        WHEN audio_url IS NOT NULL THEN audio_url
                        ELSE ''
                    END as external_url
                    FROM external_content 
                    WHERE id = ?";
                $urlStmt = $this->conn->prepare($urlSql);
                $urlStmt->execute([$externalPackageId]);
                $urlData = $urlStmt->fetch(PDO::FETCH_ASSOC);
                $externalUrl = $urlData['external_url'] ?? '';

                // Create new progress record
                $sql = "INSERT INTO external_progress 
                        (user_id, course_id, content_id, external_package_id, client_id, 
                         external_url, visit_count, time_spent, is_completed, 
                         last_visited_at, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, NULL, NOW(), NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $contentId, $externalPackageId, $clientId, $externalUrl]);
                
                // Get the newly created record
                $sql = "SELECT * FROM external_progress 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $contentId, $clientId]);
                $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return $progress;
        } catch (Exception $e) {
            error_log("Error in getOrCreateProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update external content progress
     */
    public function updateProgress($userId, $courseId, $contentId, $clientId, $data) {
        try {
            $sql = "UPDATE external_progress SET 
                    visit_count = ?,
                    time_spent = ?,
                    is_completed = ?,
                    completion_notes = ?,
                    last_visited_at = NOW(),
                    updated_at = NOW()
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $data['visit_count'] ?? 1,
                $data['time_spent'] ?? 0,
                $data['is_completed'] ?? 0,
                $data['completion_notes'] ?? null,
                $userId,
                $courseId,
                $contentId,
                $clientId
            ]);

            return $result;
        } catch (Exception $e) {
            error_log("Error in updateProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get external content progress for a specific content
     */
    public function getProgress($userId, $courseId, $contentId, $clientId) {
        try {
            $sql = "SELECT * FROM external_progress 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record visit to external content
     */
    public function recordVisit($userId, $courseId, $contentId, $externalPackageId, $clientId) {
        try {
            // Get or create progress record
            $progress = $this->getOrCreateProgress($userId, $courseId, $contentId, $externalPackageId, $clientId);
            
            if ($progress) {
                // Increment visit count
                $sql = "UPDATE external_progress SET 
                        visit_count = visit_count + 1,
                        last_visited_at = NOW(),
                        updated_at = NOW()
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
                
                $stmt = $this->conn->prepare($sql);
                $result = $stmt->execute([$userId, $courseId, $contentId, $clientId]);
                
                return $result;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error in recordVisit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update time spent on external content
     */
    public function updateTimeSpent($userId, $courseId, $contentId, $clientId, $timeSpent) {
        try {
            $sql = "UPDATE external_progress SET 
                    time_spent = ?,
                    last_visited_at = NOW(),
                    updated_at = NOW()
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$timeSpent, $userId, $courseId, $contentId, $clientId]);
            
            return $result;
        } catch (Exception $e) {
            error_log("Error in updateTimeSpent: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark external content as completed
     */
    public function markCompleted($userId, $courseId, $contentId, $clientId, $completionNotes = null) {
        try {
            $sql = "UPDATE external_progress SET 
                    is_completed = 1,
                    completion_notes = ?,
                    last_visited_at = NOW(),
                    updated_at = NOW()
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$completionNotes, $userId, $courseId, $contentId, $clientId]);
            
            return $result;
        } catch (Exception $e) {
            error_log("Error in markCompleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get external content statistics
     */
    public function getContentStatistics($userId, $courseId, $contentId, $clientId) {
        try {
            $sql = "SELECT 
                        visit_count,
                        time_spent,
                        is_completed,
                        last_visited_at,
                        completion_notes,
                        CASE 
                            WHEN is_completed = 1 THEN 100
                            WHEN visit_count > 0 THEN 50
                            ELSE 0
                        END as progress_percentage,
                        CASE 
                            WHEN is_completed = 1 THEN 'completed'
                            WHEN visit_count > 0 THEN 'in_progress'
                            ELSE 'not_started'
                        END as status
                    FROM external_progress 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'success' => true,
                    'visit_count' => intval($result['visit_count']),
                    'time_spent' => intval($result['time_spent']),
                    'is_completed' => (bool)$result['is_completed'],
                    'progress_percentage' => intval($result['progress_percentage']),
                    'status' => $result['status'],
                    'last_visited_at' => $result['last_visited_at'],
                    'completion_notes' => $result['completion_notes']
                ];
            }
            
            return [
                'success' => true,
                'visit_count' => 0,
                'time_spent' => 0,
                'is_completed' => false,
                'progress_percentage' => 0,
                'status' => 'not_started',
                'last_visited_at' => null,
                'completion_notes' => null
            ];
        } catch (Exception $e) {
            error_log("Error in getContentStatistics: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to get statistics'
            ];
        }
    }

    /**
     * Get user's external content progress for a course
     */
    public function getCourseProgress($userId, $courseId, $clientId) {
        try {
            $sql = "SELECT 
                        ep.*,
                        ec.title,
                        ec.content_type,
                        cmc.title as module_content_title
                    FROM external_progress ep
                    JOIN external_content ec ON ep.external_package_id = ec.id
                    JOIN course_module_content cmc ON ep.content_id = cmc.id
                    WHERE ep.user_id = ? AND ep.course_id = ? AND ep.client_id = ?
                    ORDER BY ep.last_visited_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $clientId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getCourseProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get external content completion rate for a user
     */
    public function getUserCompletionRate($userId, $clientId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_content,
                        SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_content,
                        ROUND((SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as completion_rate
                    FROM external_progress 
                    WHERE user_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $clientId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getUserCompletionRate: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete progress record (for cleanup)
     */
    public function deleteProgress($userId, $courseId, $contentId, $clientId) {
        try {
            $sql = "DELETE FROM external_progress 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            
            return $stmt->execute([$userId, $courseId, $contentId, $clientId]);
        } catch (Exception $e) {
            error_log("Error in deleteProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get external content types and their progress
     */
    public function getContentTypeProgress($userId, $clientId) {
        try {
            $sql = "SELECT 
                        ec.content_type,
                        COUNT(*) as total_content,
                        SUM(CASE WHEN ep.is_completed = 1 THEN 1 ELSE 0 END) as completed_content,
                        AVG(ep.time_spent) as avg_time_spent,
                        SUM(ep.visit_count) as total_visits
                    FROM external_progress ep
                    JOIN external_content ec ON ep.external_package_id = ec.id
                    WHERE ep.user_id = ? AND ep.client_id = ?
                    GROUP BY ec.content_type";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $clientId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getContentTypeProgress: " . $e->getMessage());
            return false;
        }
    }
}
