<?php
/**
 * Course Completion Model
 * 
 * Handles course completion tracking for users
 */

require_once 'config/Database.php';

class CourseCompletionModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get or create course completion record
     */
    public function getOrCreateCompletion($userId, $courseId, $clientId) {
        try {
            $sql = "SELECT * FROM course_completion 
                    WHERE user_id = ? AND course_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $clientId]);
            
            $completion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($completion) {
                return $completion;
            }
            
            // Create new completion record
            $sql = "INSERT INTO course_completion (user_id, course_id, client_id) 
                    VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $clientId]);
            
            $completionId = $this->conn->lastInsertId();
            
            // Return the newly created record
            return [
                'id' => $completionId,
                'user_id' => $userId,
                'course_id' => $courseId,
                'client_id' => $clientId,
                'completion_percentage' => 0.00,
                'is_completed' => 0,
                'completed_at' => null,
                'last_accessed_at' => null,
                'prerequisites_completed' => 0,
                'modules_completed' => 0,
                'post_requisites_completed' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Error in getOrCreateCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update course completion
     */
    public function updateCompletion($userId, $courseId, $clientId, $data) {
        try {
            $sql = "UPDATE course_completion SET 
                        completion_percentage = ?,
                        is_completed = ?,
                        last_accessed_at = NOW(),
                        prerequisites_completed = ?,
                        modules_completed = ?,
                        post_requisites_completed = ?,
                        updated_at = CURRENT_TIMESTAMP";
            
            // Set completed_at if course is completed
            if (isset($data['is_completed']) && $data['is_completed'] == 1) {
                $sql .= ", completed_at = NOW()";
            }
            
            $sql .= " WHERE user_id = ? AND course_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $data['completion_percentage'] ?? 0.00,
                $data['is_completed'] ?? 0,
                $data['prerequisites_completed'] ?? 0,
                $data['modules_completed'] ?? 0,
                $data['post_requisites_completed'] ?? 0,
                $userId,
                $courseId,
                $clientId
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error in updateCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark course as completed
     */
    public function markAsCompleted($userId, $courseId, $clientId) {
        try {
            $sql = "UPDATE course_completion SET 
                        completion_percentage = 100.00,
                        is_completed = 1,
                        completed_at = NOW(),
                        last_accessed_at = NOW(),
                        prerequisites_completed = 1,
                        modules_completed = 1,
                        post_requisites_completed = 1,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ? AND course_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$userId, $courseId, $clientId]);
            
        } catch (Exception $e) {
            error_log("Error in markAsCompleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get course completion status
     */
    public function getCompletion($userId, $courseId, $clientId) {
        try {
            $sql = "SELECT * FROM course_completion 
                    WHERE user_id = ? AND course_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $clientId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error in getCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate course completion percentage
     */
    public function calculateCourseCompletion($userId, $courseId, $clientId) {
        try {
            // Check if each component type exists and is completed
            $hasPrerequisites = $this->hasPrerequisites($courseId);
            $hasModules = $this->hasModules($courseId);
            $hasPostRequisites = $this->hasPostRequisites($courseId);
            
            $prerequisitesCompleted = $hasPrerequisites ? $this->arePrerequisitesCompleted($userId, $courseId, $clientId) : false;
            $modulesCompleted = $hasModules ? $this->areModulesCompleted($userId, $courseId, $clientId) : false;
            $postRequisitesCompleted = $hasPostRequisites ? $this->arePostRequisitesCompleted($userId, $courseId, $clientId) : false;
            
            // Calculate overall completion based on existing components only
            $totalComponents = 0;
            $completedComponents = 0;
            
            if ($hasPrerequisites) {
                $totalComponents++;
                if ($prerequisitesCompleted) $completedComponents++;
            }
            if ($hasModules) {
                $totalComponents++;
                if ($modulesCompleted) $completedComponents++;
            }
            if ($hasPostRequisites) {
                $totalComponents++;
                if ($postRequisitesCompleted) $completedComponents++;
            }
            
            $completionPercentage = $totalComponents > 0 ? round(($completedComponents / $totalComponents) * 100, 2) : 0;
            $isCompleted = $completionPercentage >= 100.00;
            
            return [
                'completion_percentage' => $completionPercentage,
                'is_completed' => $isCompleted,
                'prerequisites_completed' => $prerequisitesCompleted ? 1 : 0,
                'modules_completed' => $modulesCompleted ? 1 : 0,
                'post_requisites_completed' => $postRequisitesCompleted ? 1 : 0
            ];
            
        } catch (Exception $e) {
            error_log("Error in calculateCourseCompletion: " . $e->getMessage());
            return [
                'completion_percentage' => 0.00,
                'is_completed' => false,
                'prerequisites_completed' => 0,
                'modules_completed' => 0,
                'post_requisites_completed' => 0
            ];
        }
    }

    /**
     * Check if course has prerequisites
     */
    private function hasPrerequisites($courseId) {
        try {
            $sql = "SELECT COUNT(*) FROM course_prerequisites 
                    WHERE course_id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error in hasPrerequisites: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if course has modules
     */
    private function hasModules($courseId) {
        try {
            $sql = "SELECT COUNT(*) FROM course_modules 
                    WHERE course_id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error in hasModules: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if course has post-requisites
     */
    private function hasPostRequisites($courseId) {
        try {
            $sql = "SELECT COUNT(*) FROM course_post_requisites 
                    WHERE course_id = ? AND is_deleted = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error in hasPostRequisites: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if prerequisites are completed
     */
    private function arePrerequisitesCompleted($userId, $courseId, $clientId) {
        try {
            // Get all prerequisites for the course
            $sql = "SELECT prerequisite_id, prerequisite_type 
                    FROM course_prerequisites 
                    WHERE course_id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            $prerequisites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($prerequisites)) {
                return false; // No prerequisites means they're not completed
            }
            
            // Check if all prerequisites are completed
            foreach ($prerequisites as $prerequisite) {
                if (!$this->isPrerequisiteCompleted($userId, $courseId, $prerequisite['prerequisite_id'], $prerequisite['prerequisite_type'], $clientId)) {
                    return false;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error in arePrerequisitesCompleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if modules are completed
     */
    private function areModulesCompleted($userId, $courseId, $clientId) {
        try {
            // Get all modules for the course
            $sql = "SELECT id FROM course_modules 
                    WHERE course_id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            $modules = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($modules)) {
                return false; // No modules means they're not completed
            }
            
            // Check if all modules are completed
            $sql = "SELECT COUNT(*) FROM module_completion 
                    WHERE user_id = ? AND course_id = ? AND client_id = ? AND is_completed = 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $clientId]);
            $completedModules = $stmt->fetchColumn();
            
            return $completedModules == count($modules);
            
        } catch (Exception $e) {
            error_log("Error in areModulesCompleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if post-requisites are completed
     */
    private function arePostRequisitesCompleted($userId, $courseId, $clientId) {
        try {
            // Get all post-requisites for the course
            $sql = "SELECT content_id, content_type 
                    FROM course_post_requisites 
                    WHERE course_id = ? AND is_deleted = 0";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            $postRequisites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($postRequisites)) {
                return false; // No post-requisites means they're not completed
            }
            
            // Check if all post-requisites are completed
            foreach ($postRequisites as $postRequisite) {
                if (!$this->isPostRequisiteCompleted($userId, $courseId, $postRequisite['content_id'], $postRequisite['content_type'], $clientId)) {
                    return false;
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error in arePostRequisitesCompleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if specific prerequisite is completed
     */
    private function isPrerequisiteCompleted($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
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
                return false;
            }
            
            if ($prerequisiteType === 'course') {
                $sql = "SELECT is_completed FROM $table 
                        WHERE user_id = ? AND course_id = ? AND client_id = ?";
                $params = [$userId, $prerequisiteId, $clientId];
            } elseif ($prerequisiteType === 'survey') {
                // For surveys, check for completed_at IS NOT NULL
                $sql = "SELECT completed_at FROM $table 
                        WHERE user_id = ? AND course_id = ? AND survey_package_id = ? AND client_id = ? AND completed_at IS NOT NULL";
                $params = [$userId, $courseId, $prerequisiteId, $clientId];
            } elseif ($prerequisiteType === 'feedback') {
                // For feedback, check for completed_at IS NOT NULL
                $sql = "SELECT completed_at FROM $table 
                        WHERE user_id = ? AND course_id = ? AND feedback_package_id = ? AND client_id = ? AND completed_at IS NOT NULL";
                $params = [$userId, $courseId, $prerequisiteId, $clientId];
            } elseif ($prerequisiteType === 'scorm') {
                // For SCORM, check lesson_status and completed_at
                $sql = "SELECT lesson_status, completed_at FROM $table 
                        WHERE user_id = ? AND course_id = ? AND scorm_package_id = ? AND client_id = ?";
                $params = [$userId, $courseId, $prerequisiteId, $clientId];
            } else {
                $sql = "SELECT is_completed FROM $table 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
                $params = [$userId, $courseId, $prerequisiteId, $clientId];
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (in_array($prerequisiteType, ['survey', 'feedback'])) {
                return $result && !empty($result['completed_at']);
            } elseif ($prerequisiteType === 'scorm') {
                // For SCORM, check if lesson_status is 'completed' or 'passed' and completed_at is not null
                return $result && 
                       ($result['lesson_status'] === 'completed' || $result['lesson_status'] === 'passed') && 
                       !empty($result['completed_at']);
            } else {
                return $result && $result['is_completed'] == 1;
            }
            
        } catch (Exception $e) {
            error_log("Error in isPrerequisiteCompleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if specific post-requisite is completed
     */
    private function isPostRequisiteCompleted($userId, $courseId, $contentId, $contentType, $clientId) {
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
            
            $sql = "SELECT is_completed FROM $table 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['is_completed'] == 1;
            
        } catch (Exception $e) {
            error_log("Error in isPostRequisiteCompleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update course completion based on all components
     * Only creates completion records when course is actually completed
     */
    public function updateCourseCompletionFromComponents($userId, $courseId, $clientId) {
        try {
            $completionData = $this->calculateCourseCompletion($userId, $courseId, $clientId);
            
            // Only create/update completion record if course is completed
            if ($completionData['is_completed']) {
                // Get or create completion record only when completed
                $completion = $this->getOrCreateCompletion($userId, $courseId, $clientId);
                
                // Update completion
                $this->updateCompletion($userId, $courseId, $clientId, $completionData);
            }
            
            return $completionData;
            
        } catch (Exception $e) {
            error_log("Error in updateCourseCompletionFromComponents: " . $e->getMessage());
            return false;
        }
    }
}
?>
