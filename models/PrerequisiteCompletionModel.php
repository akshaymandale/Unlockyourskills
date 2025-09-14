<?php
/**
 * Prerequisite Completion Model
 * 
 * Handles prerequisite completion tracking for users
 */

require_once 'config/Database.php';

class PrerequisiteCompletionModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get or create prerequisite completion record
     */
    public function getOrCreateCompletion($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
        try {
            $sql = "SELECT * FROM prerequisite_completion 
                    WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND prerequisite_type = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId]);
            
            $completion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($completion) {
                return $completion;
            }
            
            // Create new completion record
            $sql = "INSERT INTO prerequisite_completion (user_id, course_id, prerequisite_id, prerequisite_type, client_id) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId]);
            
            $completionId = $this->conn->lastInsertId();
            
            // Return the newly created record
            return [
                'id' => $completionId,
                'user_id' => $userId,
                'course_id' => $courseId,
                'prerequisite_id' => $prerequisiteId,
                'prerequisite_type' => $prerequisiteType,
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
     * Update prerequisite completion
     */
    public function updateCompletion($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId, $data) {
        try {
            $sql = "UPDATE prerequisite_completion SET 
                        completion_percentage = ?,
                        is_completed = ?,
                        last_accessed_at = NOW(),
                        updated_at = CURRENT_TIMESTAMP";
            
            // Set completed_at if prerequisite is completed
            if (isset($data['is_completed']) && $data['is_completed'] == 1) {
                if (isset($data['completed_at']) && $data['completed_at']) {
                    $sql .= ", completed_at = ?";
                } else {
                    $sql .= ", completed_at = NOW()";
                }
            }
            
            $sql .= " WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND prerequisite_type = ? AND client_id = ?";
            
            $params = [
                $data['completion_percentage'] ?? 0.00,
                $data['is_completed'] ?? 0
            ];
            
            // Add completed_at parameter if it's being set
            if (isset($data['is_completed']) && $data['is_completed'] == 1 && isset($data['completed_at']) && $data['completed_at']) {
                $params[] = $data['completed_at'];
            }
            
            // Add WHERE clause parameters
            $params = array_merge($params, [$userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId]);
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($params);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error in updateCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark prerequisite as completed
     */
    public function markAsCompleted($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
        try {
            $sql = "UPDATE prerequisite_completion SET 
                        completion_percentage = 100.00,
                        is_completed = 1,
                        completed_at = NOW(),
                        last_accessed_at = NOW(),
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND prerequisite_type = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId]);
            
        } catch (Exception $e) {
            error_log("Error in markAsCompleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get prerequisite completion status
     */
    public function getCompletion($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
        try {
            $sql = "SELECT * FROM prerequisite_completion 
                    WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND prerequisite_type = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error in getCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all prerequisite completions for a user in a course
     */
    public function getCoursePrerequisiteCompletions($userId, $courseId, $clientId) {
        try {
            $sql = "SELECT pc.*, cp.prerequisite_description, cp.sort_order
                    FROM prerequisite_completion pc
                    JOIN course_prerequisites cp ON pc.prerequisite_id = cp.prerequisite_id AND pc.prerequisite_type = cp.prerequisite_type COLLATE utf8mb4_unicode_ci
                    WHERE pc.user_id = ? AND pc.course_id = ? AND pc.client_id = ?
                    ORDER BY cp.sort_order ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $clientId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error in getCoursePrerequisiteCompletions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if prerequisite is completed based on its type
     */
    public function isPrerequisiteCompleted($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
        try {
            // Check prerequisite_completion table for all prerequisite types
            $sql = "SELECT is_completed FROM prerequisite_completion 
                    WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND prerequisite_type = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Return true if completion record exists and is_completed = 1
            return $result && $result['is_completed'] == 1;
            
        } catch (Exception $e) {
            error_log("Error in isPrerequisiteCompleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update prerequisite completion based on actual progress
     * Only creates completion records when prerequisite is actually completed
     */
    public function updatePrerequisiteCompletionFromProgress($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
        try {
            // Get completion data from underlying progress tables
            $progressData = $this->getProgressData($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId);
            $completedAt = $progressData['completed_at'] ?? null;

            // Derive completion from progress data to avoid circular dependency on prerequisite_completion
            $isCompleted = !empty($completedAt);
            $completionPercentage = $isCompleted ? 100.00 : 0.00;
            
            // Only create/update completion record if prerequisite is completed
            if ($isCompleted) {
                // Get or create completion record only when completed
                $completion = $this->getOrCreateCompletion($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId);
                
                // Update completion
                $this->updateCompletion($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId, [
                    'completion_percentage' => $completionPercentage,
                    'is_completed' => 1,
                    'completed_at' => $completedAt
                ]);
            }
            
            return [
                'completion_percentage' => $completionPercentage,
                'is_completed' => $isCompleted
            ];
            
        } catch (Exception $e) {
            error_log("Error in updatePrerequisiteCompletionFromProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get progress data from progress table (time_spent, completed_at, etc.)
     */
    private function getProgressData($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
        try {
            $tableMap = [
                'course' => 'course_completion',
                'video' => 'video_progress',
                'audio' => 'audio_progress',
                'document' => 'document_progress',
                'image' => 'image_progress',
                'scorm' => 'scorm_progress',
                'assessment' => 'assessment_results',
                'assignment' => 'assignment_submissions',
                'external' => 'external_progress',
                'interactive' => 'interactive_progress',
                'survey' => 'course_survey_responses',
                'feedback' => 'course_feedback_responses'
            ];
            
            $table = $tableMap[$prerequisiteType] ?? null;
            if (!$table) {
                return ['time_spent' => 0, 'completed_at' => null];
            }
            
            if ($prerequisiteType === 'course') {
                $sql = "SELECT time_spent, completed_at FROM $table 
                        WHERE user_id = ? AND course_id = ? AND client_id = ?";
                $params = [$userId, $prerequisiteId, $clientId];
            } elseif ($prerequisiteType === 'assessment') {
                // Get the latest assessment result to calculate time from timestamps
                $sql = "SELECT started_at, completed_at FROM $table 
                        WHERE user_id = ? AND course_id = ? AND assessment_id = ? AND client_id = ?
                        ORDER BY attempt_number DESC, completed_at DESC LIMIT 1";
                $params = [$userId, $courseId, $prerequisiteId, $clientId];
            } elseif ($prerequisiteType === 'assignment') {
                $sql = "SELECT started_at, completed_at FROM $table 
                        WHERE user_id = ? AND course_id = ? AND assignment_package_id = ? AND client_id = ? AND is_deleted = 0";
                $params = [$userId, $courseId, $prerequisiteId, $clientId];
            } elseif ($prerequisiteType === 'external') {
                // For external prerequisites, we need to find the course_prerequisites.id
                // that corresponds to this prerequisite_id (actual content ID)
                $sql = "SELECT ep.started_at, ep.completed_at, ep.time_spent 
                        FROM $table ep
                        INNER JOIN course_prerequisites cp ON ep.content_id = cp.id
                        WHERE ep.user_id = ? AND ep.course_id = ? AND cp.prerequisite_id = ? AND ep.client_id = ?";
                $params = [$userId, $courseId, $prerequisiteId, $clientId];
            } elseif ($prerequisiteType === 'survey') {
                $sql = "SELECT started_at, completed_at FROM $table 
                        WHERE user_id = ? AND course_id = ? AND survey_package_id = ? AND client_id = ? AND response_type = 'survey_start'";
                $params = [$userId, $courseId, $prerequisiteId, $clientId];
            } elseif ($prerequisiteType === 'feedback') {
                $sql = "SELECT started_at, completed_at FROM $table 
                        WHERE user_id = ? AND course_id = ? AND feedback_package_id = ? AND client_id = ? AND response_type = 'feedback_start'";
                $params = [$userId, $courseId, $prerequisiteId, $clientId];
            } else {
                $sql = "SELECT time_spent, completed_at FROM $table 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
                $params = [$userId, $courseId, $prerequisiteId, $clientId];
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate time spent for assignments, assessments, and external content
            if ($result) {
                if ($prerequisiteType === 'assignment') {
                    $timeSpent = $this->calculateTimeSpent($result, !empty($result['completed_at']));
                } elseif ($prerequisiteType === 'assessment') {
                    // Calculate from started_at and completed_at
                    $timeSpent = $this->calculateTimeSpent($result, !empty($result['completed_at']));
                } elseif ($prerequisiteType === 'external') {
                    // For external content, use calculated time if available, otherwise use stored time_spent
                    if (!empty($result['started_at']) && !empty($result['completed_at'])) {
                        $timeSpent = $this->calculateTimeSpent($result, true);
                    } else {
                        // Fallback to stored time_spent if timestamps are not available
                        $timeSpent = isset($result['time_spent']) ? (int)$result['time_spent'] : 0;
                    }
                } else {
                    $timeSpent = isset($result['time_spent']) ? (int)$result['time_spent'] : 0;
                }
            } else {
                $timeSpent = 0;
            }
            
            return [
                'time_spent' => $timeSpent,
                'completed_at' => $result ? $result['completed_at'] : null
            ];
            
        } catch (Exception $e) {
            error_log("Error in getProgressData: " . $e->getMessage());
            return ['time_spent' => 0, 'completed_at' => null];
        }
    }
    
    /**
     * Get time spent from progress table (legacy method)
     */
    private function getTimeSpentFromProgress($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
        $data = $this->getProgressData($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId);
        return $data['time_spent'];
    }
    
    /**
     * Calculate time spent from started_at and completed_at timestamps
     */
    private function calculateTimeSpent($completion, $isCompleted) {
        try {
            if (!$isCompleted || !$completion['started_at'] || !$completion['completed_at']) {
                return 0;
            }
            
            $startedAt = new DateTime($completion['started_at']);
            $completedAt = new DateTime($completion['completed_at']);
            $timeDiff = $completedAt->getTimestamp() - $startedAt->getTimestamp();
            
            return max(0, $timeDiff); // Return 0 if negative (shouldn't happen)
            
        } catch (Exception $e) {
            error_log("Error in calculateTimeSpent: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Initialize prerequisite completions for a course
     */
    public function initializeCoursePrerequisites($userId, $courseId, $clientId) {
        try {
            // Get all prerequisites for the course
            $sql = "SELECT prerequisite_id, prerequisite_type 
                    FROM course_prerequisites 
                    WHERE course_id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            $prerequisites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($prerequisites as $prerequisite) {
                $this->getOrCreateCompletion(
                    $userId, 
                    $courseId, 
                    $prerequisite['prerequisite_id'], 
                    $prerequisite['prerequisite_type'], 
                    $clientId
                );
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error in initializeCoursePrerequisites: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Start tracking prerequisite when user opens it
     */
    public function startTracking($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
        try {
            error_log("PrerequisiteCompletionModel::startTracking called with: userId=$userId, courseId=$courseId, prerequisiteId=$prerequisiteId, prerequisiteType=$prerequisiteType, clientId=$clientId");
            
            // Check if completion record already exists
            $existingProgress = $this->getProgress($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId);
            
            if ($existingProgress) {
                // Update last_accessed_at
                $sql = "UPDATE prerequisite_completion 
                        SET last_accessed_at = CURRENT_TIMESTAMP
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND prerequisite_type = ? AND client_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId]);
                
                return $this->getProgress($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId);
            }

            // Create new completion record
            $sql = "INSERT INTO prerequisite_completion (
                        user_id, course_id, prerequisite_id, prerequisite_type, client_id,
                        completion_percentage, is_completed, last_accessed_at
                    ) VALUES (?, ?, ?, ?, ?, 0.00, 0, CURRENT_TIMESTAMP)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId
            ]);

            return $this->getProgress($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId);
        } catch (Exception $e) {
            error_log("Error starting prerequisite tracking: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get prerequisite progress
     */
    public function getProgress($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
        try {
            $sql = "SELECT * FROM prerequisite_completion 
                    WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND prerequisite_type = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting prerequisite progress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark prerequisite as completed
     */
    public function markComplete($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
        try {
            $sql = "UPDATE prerequisite_completion 
                    SET is_completed = 1,
                        completion_percentage = 100.00,
                        completed_at = NOW(),
                        last_accessed_at = CURRENT_TIMESTAMP
                    WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND prerequisite_type = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId]);
            
            error_log("markComplete UPDATE result: " . ($result ? 'Success' : 'Failed') . ", rows affected: " . $stmt->rowCount());
            
            $progress = $this->getProgress($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId);
            error_log("markComplete getProgress result: " . json_encode($progress));
            
            return $progress;
        } catch (Exception $e) {
            error_log("Error marking prerequisite complete: " . $e->getMessage());
            throw $e;
        }
    }
}
?>
