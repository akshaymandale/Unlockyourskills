<?php
/**
 * Module Completion Model
 * 
 * Handles module completion tracking for users
 */

require_once 'config/Database.php';

class ModuleCompletionModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get or create module completion record
     */
    public function getOrCreateCompletion($userId, $courseId, $moduleId, $clientId, $contentId = null) {
        try {
            $sql = "SELECT * FROM module_completion 
                    WHERE user_id = ? AND course_id = ? AND module_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $moduleId, $clientId]);
            
            $completion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($completion) {
                return $completion;
            }
            
            // Create new completion record
            $sql = "INSERT INTO module_completion (user_id, course_id, module_id, content_id, client_id) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $moduleId, $contentId, $clientId]);
            
            $completionId = $this->conn->lastInsertId();
            
            // Return the newly created record
            return [
                'id' => $completionId,
                'user_id' => $userId,
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'content_id' => $contentId,
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
     * Update module completion
     */
    public function updateCompletion($userId, $courseId, $moduleId, $clientId, $data) {
        try {
            $sql = "UPDATE module_completion SET 
                        completion_percentage = ?,
                        is_completed = ?,
                        last_accessed_at = NOW(),
                        updated_at = CURRENT_TIMESTAMP";
            
            // Set completed_at if module is completed
            if (isset($data['is_completed']) && $data['is_completed'] == 1) {
                $sql .= ", completed_at = NOW()";
            }
            
            // Update content_id if provided
            if (isset($data['content_id'])) {
                $sql .= ", content_id = ?";
            }
            
            $sql .= " WHERE user_id = ? AND course_id = ? AND module_id = ? AND client_id = ?";
            
            $params = [
                $data['completion_percentage'] ?? 0.00,
                $data['is_completed'] ?? 0
            ];
            
            // Add content_id to parameters if provided
            if (isset($data['content_id'])) {
                $params[] = $data['content_id'];
            }
            
            // Add WHERE clause parameters
            $params[] = $userId;
            $params[] = $courseId;
            $params[] = $moduleId;
            $params[] = $clientId;
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($params);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error in updateCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark module as completed
     */
    public function markAsCompleted($userId, $courseId, $moduleId, $clientId) {
        try {
            $sql = "UPDATE module_completion SET 
                        completion_percentage = 100.00,
                        is_completed = 1,
                        completed_at = NOW(),
                        last_accessed_at = NOW(),
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ? AND course_id = ? AND module_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$userId, $courseId, $moduleId, $clientId]);
            
        } catch (Exception $e) {
            error_log("Error in markAsCompleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get module completion status
     */
    public function getCompletion($userId, $courseId, $moduleId, $clientId) {
        try {
            $sql = "SELECT * FROM module_completion 
                    WHERE user_id = ? AND course_id = ? AND module_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $moduleId, $clientId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error in getCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all module completions for a user in a course
     */
    public function getCourseModuleCompletions($userId, $courseId, $clientId) {
        try {
            $sql = "SELECT mc.*, cm.title as module_title, cm.module_order
                    FROM module_completion mc
                    JOIN course_modules cm ON mc.module_id = cm.id
                    WHERE mc.user_id = ? AND mc.course_id = ? AND mc.client_id = ?
                    ORDER BY cm.module_order ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $clientId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error in getCourseModuleCompletions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate module completion percentage based on content progress
     */
    public function calculateModuleCompletion($userId, $courseId, $moduleId, $clientId) {
        try {
            // Get all content in the module
            $sql = "SELECT cmc.id, cmc.content_id, cmc.content_type, cmc.is_required
                    FROM course_module_content cmc
                    WHERE cmc.module_id = ? AND (cmc.deleted_at IS NULL OR cmc.deleted_at = '0000-00-00 00:00:00')";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$moduleId]);
            $contentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($contentItems)) {
                return 0.00;
            }
            
            $totalContent = count($contentItems);
            $completedContent = 0;
            $requiredContent = 0;
            $completedRequiredContent = 0;
            
            foreach ($contentItems as $content) {
                if ($content['is_required']) {
                    $requiredContent++;
                }
                
                // Check if content is completed based on content type
                $isCompleted = $this->isContentCompleted($userId, $courseId, $content['content_id'], $content['content_type'], $clientId);
                
                if ($isCompleted) {
                    $completedContent++;
                    if ($content['is_required']) {
                        $completedRequiredContent++;
                    }
                }
            }
            
            // Calculate completion percentage
            // If all required content is completed, module is 100% complete
            if ($requiredContent > 0 && $completedRequiredContent == $requiredContent) {
                return 100.00;
            }
            
            // Otherwise, calculate based on all content
            return round(($completedContent / $totalContent) * 100, 2);
            
        } catch (Exception $e) {
            error_log("Error in calculateModuleCompletion: " . $e->getMessage());
            return 0.00;
        }
    }

    /**
     * Check if specific content is completed
     */
    private function isContentCompleted($userId, $courseId, $contentId, $contentType, $clientId) {
        try {
            $tableMap = [
                'video' => 'video_progress',
                'audio' => 'audio_progress',
                'document' => 'document_progress',
                'image' => 'image_progress',
                'scorm' => 'scorm_progress',
                'assessment' => 'assessment_results',
                'assignment' => 'assignment_submissions',
                'external' => 'external_progress',
                'interactive' => 'interactive_progress'
            ];
            
            $table = $tableMap[$contentType] ?? null;
            if (!$table) {
                return false;
            }
            
            if ($contentType === 'external') {
                // For external content, we need to find the course_module_content.id
                // that corresponds to this content_id (actual content ID)
                $sql = "SELECT ep.is_completed 
                        FROM $table ep
                        INNER JOIN course_module_content cmc ON ep.content_id = cmc.id
                        WHERE ep.user_id = ? AND ep.course_id = ? AND cmc.content_id = ? AND ep.client_id = ?";
            } else {
                $sql = "SELECT is_completed FROM $table 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['is_completed'] == 1;
            
        } catch (Exception $e) {
            error_log("Error in isContentCompleted: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update module completion based on content progress
     * Only creates completion records when module is actually completed
     */
    public function updateModuleCompletionFromContent($userId, $courseId, $moduleId, $clientId, $contentId = null) {
        try {
            $completionPercentage = $this->calculateModuleCompletion($userId, $courseId, $moduleId, $clientId);
            $isCompleted = $completionPercentage >= 100.00;
            
            // Only create/update completion record if module is completed
            if ($isCompleted) {
                // Get or create completion record only when completed
                $completion = $this->getOrCreateCompletion($userId, $courseId, $moduleId, $clientId, $contentId);
                
                // Update completion
                $updateData = [
                    'completion_percentage' => $completionPercentage,
                    'is_completed' => 1
                ];
                
                // Include content_id if provided
                if ($contentId) {
                    $updateData['content_id'] = $contentId;
                }
                
                $this->updateCompletion($userId, $courseId, $moduleId, $clientId, $updateData);
            }
            
            return [
                'completion_percentage' => $completionPercentage,
                'is_completed' => $isCompleted
            ];
            
        } catch (Exception $e) {
            error_log("Error in updateModuleCompletionFromContent: " . $e->getMessage());
            return false;
        }
    }
}
?>
