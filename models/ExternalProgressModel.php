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
     * Get actual content ID from course_prerequisites.id or course_module_content.id
     */
    private function getActualContentId($contentId) {
        try {
            // First check if it's already an actual content ID
            $sql = "SELECT id FROM external_content WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$contentId]);
            if ($stmt->fetchColumn()) {
                return $contentId; // Already an actual content ID
            }
            
            // Check if it's a course_prerequisites.id
            $sql = "SELECT prerequisite_id FROM course_prerequisites WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$contentId]);
            $prerequisiteId = $stmt->fetchColumn();
            
            if ($prerequisiteId) {
                return $prerequisiteId;
            }
            
            // Check if it's a course_module_content.id
            $sql = "SELECT content_id FROM course_module_content 
                    WHERE id = ? AND content_type = 'external' AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$contentId]);
            $moduleContentId = $stmt->fetchColumn();
            
            if ($moduleContentId) {
                return $moduleContentId;
            }
            
            // Return original if not found
            return $contentId;
        } catch (Exception $e) {
            error_log("Error in getActualContentId: " . $e->getMessage());
            return $contentId;
        }
    }

    /**
     * Get or create external content progress record
     */
    public function getOrCreateProgress($userId, $courseId, $contentId, $externalPackageId, $clientId) {
        try {
            // First check if this is a prerequisite by looking for it in course_prerequisites
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'external');
            
            if ($isPrerequisite) {
                // For prerequisites, look for records with prerequisite_id = contentId
                $sql = "SELECT * FROM external_progress 
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $contentId, $clientId]);
                $progress = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$progress) {
                    // Double-check to prevent race conditions - another thread might have created the record
                    $sql = "SELECT * FROM external_progress 
                            WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$userId, $courseId, $contentId, $clientId]);
                    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$progress) {
                        // Get external content URL for the record
                        $actualContentId = $this->getActualContentId($contentId);
                    
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
                        $urlStmt->execute([$actualContentId]);
                        $urlData = $urlStmt->fetch(PDO::FETCH_ASSOC);
                        $externalUrl = $urlData['external_url'] ?? '';

                        // Create new progress record for prerequisite with duplicate prevention
                        try {
                            $sql = "INSERT INTO external_progress 
                                    (user_id, course_id, prerequisite_id, content_id, external_package_id, client_id, 
                                     started_at, external_url, visit_count, time_spent, is_completed, 
                                     last_visited_at, created_at, updated_at) 
                                    VALUES (?, ?, ?, NULL, ?, ?, NOW(), ?, 0, 0, 0, NULL, NOW(), NOW())";
                            $stmt = $this->conn->prepare($sql);
                            $stmt->execute([$userId, $courseId, $contentId, $externalPackageId, $clientId, $externalUrl]);
                            
                            // Get the newly created record
                            return $this->getProgress($userId, $courseId, $contentId, $clientId);
                        } catch (PDOException $e) {
                            // If duplicate key error, try to get the existing record
                            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                                error_log("Duplicate record detected, fetching existing record: " . $e->getMessage());
                                return $this->getProgress($userId, $courseId, $contentId, $clientId);
                            } else {
                                throw $e; // Re-throw if it's not a duplicate key error
                            }
                        }
                    } else {
                        return $progress;
                    }
                } else {
                    return $progress;
                }
            } else {
                // For module content, use the existing logic
                $sql = "SELECT * FROM external_progress 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $contentId, $clientId]);
                $progress = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$progress) {
                    // Get external content URL for the record
                    $actualContentId = $this->getActualContentId($contentId);
                
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
                    $urlStmt->execute([$actualContentId]);
                    $urlData = $urlStmt->fetch(PDO::FETCH_ASSOC);
                    $externalUrl = $urlData['external_url'] ?? '';

                    // Create new progress record for module content
                    // For module content, only set content_id and leave prerequisite_id as NULL
                    $sql = "INSERT INTO external_progress 
                            (user_id, course_id, prerequisite_id, content_id, external_package_id, client_id, 
                             started_at, external_url, visit_count, time_spent, is_completed, 
                             last_visited_at, created_at, updated_at) 
                            VALUES (?, ?, NULL, ?, ?, ?, NOW(), ?, 0, 0, 0, NULL, NOW(), NOW())";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$userId, $courseId, $contentId, $externalPackageId, $clientId, $externalUrl]);
                    
                    return $this->getProgress($userId, $courseId, $contentId, $clientId);
                } else {
                    return $progress;
                }
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
            // Check if this is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'external');
            
            // Set started_at if not already set and external content is being visited
            $setStartedAt = "";
            if (isset($data['visit_count']) && $data['visit_count'] > 0) {
                $setStartedAt = ", started_at = CASE WHEN started_at IS NULL THEN NOW() ELSE started_at END";
            }
            
            // Calculate time_spent based on timestamps when completing (similar to prerequisite_completion logic)
            $timeSpent = $data['time_spent'] ?? 0;
            
            // Check if we should calculate time from timestamps
            $shouldCalculateTime = false;
            
            // Calculate if explicitly completing
            if (isset($data['is_completed']) && $data['is_completed'] == 1) {
                $shouldCalculateTime = true;
            }
            
            // Also calculate if completed_at exists (even if is_completed is 0 due to data inconsistency)
            if (!$shouldCalculateTime) {
                $currentProgress = $this->getProgress($userId, $courseId, $contentId, $clientId);
                if ($currentProgress && $currentProgress['completed_at']) {
                    $shouldCalculateTime = true;
                }
            }
            
            if ($shouldCalculateTime) {
                // When completing, calculate time from started_at to current time
                $calculatedTimeSpent = $this->calculateTimeSpentToNow($userId, $courseId, $contentId, $clientId);
                if ($calculatedTimeSpent > 0) {
                    $timeSpent = $calculatedTimeSpent;
                }
            }
            
            // Set completed_at if external content is completed and not already set
            $setCompletedAt = "";
            if (isset($data['is_completed']) && $data['is_completed'] == 1) {
                // Only set completed_at if it's not already set
                $setCompletedAt = ", completed_at = CASE WHEN completed_at IS NULL THEN NOW() ELSE completed_at END";
            }
            
            if ($isPrerequisite) {
                // For prerequisites, update using prerequisite_id
                $sql = "UPDATE external_progress SET 
                        visit_count = ?,
                        time_spent = ?,
                        is_completed = ?,
                        completion_notes = ?,
                        last_visited_at = NOW(),
                        updated_at = NOW()
                        $setStartedAt
                        $setCompletedAt
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
            } else {
                // For module content, update using content_id
                $sql = "UPDATE external_progress SET 
                        visit_count = ?,
                        time_spent = ?,
                        is_completed = ?,
                        completion_notes = ?,
                        last_visited_at = NOW(),
                        updated_at = NOW()
                        $setStartedAt
                        $setCompletedAt
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            }

            // Set default completion note if completing and none provided
            $completionNotes = $data['completion_notes'] ?? null;
            if (($data['is_completed'] ?? 0) == 1 && $completionNotes === null) {
                $completionNotes = 'User marked as viewed/completed';
            }
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $data['visit_count'] ?? 1,
                $timeSpent,
                $data['is_completed'] ?? 0,
                $completionNotes,
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
            // Check if this is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'external');
            
            if ($isPrerequisite) {
                // For prerequisites, look for records with prerequisite_id = contentId
                $sql = "SELECT * FROM external_progress 
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            } else {
                // For module content, use content_id
                $sql = "SELECT * FROM external_progress WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            }
            
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
                // Check if this is a prerequisite
                $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'external');
                
                if ($isPrerequisite) {
                    // For prerequisites, update using prerequisite_id
                    $sql = "UPDATE external_progress SET 
                            visit_count = visit_count + 1,
                            last_visited_at = NOW(),
                            updated_at = NOW()
                            WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
                } else {
                    // For module content, update using content_id
                    $sql = "UPDATE external_progress SET 
                            visit_count = visit_count + 1,
                            last_visited_at = NOW(),
                            updated_at = NOW()
                            WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
                }
                
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
     * Calculate time spent from started_at to completed_at or current time
     * Used when completing content
     */
    private function calculateTimeSpentToNow($userId, $courseId, $contentId, $clientId) {
        try {
            // Use database calculation to avoid timezone issues
            $sql = "SELECT 
                        started_at, 
                        completed_at,
                        CASE 
                            WHEN completed_at IS NOT NULL THEN 
                                TIMESTAMPDIFF(SECOND, started_at, completed_at)
                            ELSE 
                                TIMESTAMPDIFF(SECOND, started_at, NOW())
                        END as time_diff
                    FROM external_progress 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || !$result['started_at']) {
                return 0;
            }
            
            $timeDiff = (int)$result['time_diff'];
            
            return max(0, $timeDiff); // Return 0 if negative (shouldn't happen)
            
        } catch (Exception $e) {
            error_log("Error in calculateTimeSpentToNow: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate time spent from started_at and completed_at timestamps
     */
    private function calculateTimeSpentFromTimestamps($userId, $courseId, $contentId, $clientId) {
        try {
            $sql = "SELECT started_at, completed_at FROM external_progress 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || !$result['started_at'] || !$result['completed_at']) {
                return 0;
            }
            
            $startedAt = new DateTime($result['started_at']);
            $completedAt = new DateTime($result['completed_at']);
            $timeDiff = $completedAt->getTimestamp() - $startedAt->getTimestamp();
            
            return max(0, $timeDiff); // Return 0 if negative (shouldn't happen)
            
        } catch (Exception $e) {
            error_log("Error in calculateTimeSpentFromTimestamps: " . $e->getMessage());
            return 0;
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
            // Set default completion note if none provided
            if ($completionNotes === null) {
                $completionNotes = 'User marked as viewed/completed';
            }
            
            // Check if this is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'external');
            
            // Get the actual external package ID
            $externalPackageId = null;
            if ($isPrerequisite) {
                // For prerequisites, get the prerequisite_id from course_prerequisites
                $stmt = $this->conn->prepare("SELECT prerequisite_id FROM course_prerequisites WHERE course_id = ? AND id = ? AND prerequisite_type = 'external'");
                $stmt->execute([$courseId, $contentId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $externalPackageId = $result ? $result['prerequisite_id'] : null;
            } else {
                // For module content, get the content_id from course_module_content
                $stmt = $this->conn->prepare("SELECT content_id FROM course_module_content WHERE id = ? AND content_type = 'external'");
                $stmt->execute([$contentId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $externalPackageId = $result ? $result['content_id'] : null;
            }
            
            if (!$externalPackageId) {
                error_log("Could not determine external package ID for contentId: $contentId");
                return false;
            }
            
            // First, check if we have a progress record
            $progress = $this->getProgress($userId, $courseId, $contentId, $clientId);
            
            if (!$progress) {
                // If no record exists, try to create one
                $progress = $this->getOrCreateProgress($userId, $courseId, $contentId, $externalPackageId, $clientId);
                
                if (!$progress) {
                    error_log("Failed to create/get progress record for markCompleted");
                    return false;
                }
            }
            
            // Calculate time_spent from started_at to current time when completing
            $calculatedTimeSpent = $this->calculateTimeSpentToNow($userId, $courseId, $contentId, $clientId);
            
            if ($isPrerequisite) {
                // For prerequisites, update using prerequisite_id
                $sql = "UPDATE external_progress SET 
                        is_completed = 1,
                        completed_at = NOW(),
                        time_spent = ?,
                        completion_notes = ?,
                        last_visited_at = NOW(),
                        updated_at = NOW()
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
            } else {
                // For module content, update using content_id
                $sql = "UPDATE external_progress SET 
                        is_completed = 1,
                        completed_at = NOW(),
                        time_spent = ?,
                        completion_notes = ?,
                        last_visited_at = NOW(),
                        updated_at = NOW()
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            }
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([$calculatedTimeSpent, $completionNotes, $userId, $courseId, $contentId, $clientId]);
            
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
            // Check if this is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'external');
            
            if ($isPrerequisite) {
                // For prerequisites, look for records with prerequisite_id = contentId
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
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
            } else {
                // For module content, use content_id
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
            }
            
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

    /**
     * Check if content is a prerequisite
     */
    private function isContentPrerequisite($courseId, $contentId, $contentType) {
        try {
            $sql = "SELECT COUNT(*) FROM course_prerequisites 
                    WHERE course_id = ? AND id = ? AND prerequisite_type = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $contentId, $contentType]);
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error checking if content is prerequisite: " . $e->getMessage());
            return false;
        }
    }
}
