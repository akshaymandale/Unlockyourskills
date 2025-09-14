<?php
/**
 * Completion Tracking Service
 * 
 * Centralized service for managing all completion tracking
 */

require_once 'ModuleCompletionModel.php';
require_once 'CourseCompletionModel.php';
require_once 'PrerequisiteCompletionModel.php';
require_once 'PostRequisiteCompletionModel.php';

class CompletionTrackingService {
    private $moduleCompletionModel;
    private $courseCompletionModel;
    private $prerequisiteCompletionModel;
    private $postRequisiteCompletionModel;

    public function __construct() {
        $this->moduleCompletionModel = new ModuleCompletionModel();
        $this->courseCompletionModel = new CourseCompletionModel();
        $this->prerequisiteCompletionModel = new PrerequisiteCompletionModel();
        $this->postRequisiteCompletionModel = new PostRequisiteCompletionModel();
    }

    /**
     * Initialize completion tracking for a user in a course
     * NOTE: This method is deprecated - completion entries are now only created when content is actually completed
     */
    public function initializeCourseCompletion($userId, $courseId, $clientId) {
        // No longer initialize completion tracking on course page load
        // Completion entries are only created when content is actually completed
        return true;
    }

    /**
     * Update module completion when content progress changes
     */
    public function updateModuleCompletion($userId, $courseId, $moduleId, $clientId, $contentId = null) {
        try {
            // Update module completion based on content progress
            $result = $this->moduleCompletionModel->updateModuleCompletionFromContent($userId, $courseId, $moduleId, $clientId, $contentId);
            
            if ($result) {
                // Update course completion
                $this->updateCourseCompletion($userId, $courseId, $clientId);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error in updateModuleCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update prerequisite completion when prerequisite progress changes
     */
    public function updatePrerequisiteCompletion($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
        try {
            // Update prerequisite completion based on actual progress
            $result = $this->prerequisiteCompletionModel->updatePrerequisiteCompletionFromProgress($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId);
            
            if ($result) {
                // Update course completion
                $this->updateCourseCompletion($userId, $courseId, $clientId);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error in updatePrerequisiteCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update post-requisite completion when post-requisite progress changes
     */
    public function updatePostRequisiteCompletion($userId, $courseId, $postRequisiteId, $contentType, $clientId) {
        try {
            // Update post-requisite completion based on actual progress
            $result = $this->postRequisiteCompletionModel->updatePostRequisiteCompletionFromProgress($userId, $courseId, $postRequisiteId, $contentType, $clientId);
            
            if ($result) {
                // Update course completion
                $this->updateCourseCompletion($userId, $courseId, $clientId);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error in updatePostRequisiteCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update course completion based on all components
     */
    public function updateCourseCompletion($userId, $courseId, $clientId) {
        try {
            // Update course completion based on all components
            $result = $this->courseCompletionModel->updateCourseCompletionFromComponents($userId, $courseId, $clientId);
            
            return $result;
        } catch (Exception $e) {
            error_log("Error in updateCourseCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get comprehensive completion status for a course
     */
    public function getCourseCompletionStatus($userId, $courseId, $clientId) {
        try {
            $status = [
                'course' => $this->courseCompletionModel->getCompletion($userId, $courseId, $clientId),
                'modules' => $this->moduleCompletionModel->getCourseModuleCompletions($userId, $courseId, $clientId),
                'prerequisites' => $this->prerequisiteCompletionModel->getCoursePrerequisiteCompletions($userId, $courseId, $clientId),
                'post_requisites' => $this->postRequisiteCompletionModel->getCoursePostRequisiteCompletions($userId, $courseId, $clientId)
            ];
            
            return $status;
        } catch (Exception $e) {
            error_log("Error in getCourseCompletionStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle content completion (called when any content is completed)
     * This method creates completion entries only when content is actually completed
     */
    public function handleContentCompletion($userId, $courseId, $contentId, $contentType, $clientId) {
        try {
            // Check if this content is a prerequisite and create prerequisite completion entry if completed
            $this->updatePrerequisiteCompletionIfApplicable($userId, $courseId, $contentId, $contentType, $clientId);
            
            // Check if this content is a post-requisite and create post-requisite completion entry if completed
            $this->updatePostRequisiteCompletionIfApplicable($userId, $courseId, $contentId, $contentType, $clientId);
            
            // Only update module completion if this content is NOT a prerequisite
            // This prevents cross-contamination when the same content serves both roles
            if (!$this->isContentPrerequisite($courseId, $contentId, $contentType)) {
                $moduleId = $this->getModuleIdForContent($contentId, $contentType);
                
                if ($moduleId) {
                    // Update module completion - this will create module completion entry if module is completed
                    $this->updateModuleCompletion($userId, $courseId, $moduleId, $clientId, $contentId);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error in handleContentCompletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get module ID for a content item
     */
    private function getModuleIdForContent($contentId, $contentType) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            // Check if contentId is a course_module_content.id
            $sql = "SELECT module_id FROM course_module_content 
                    WHERE id = ? AND content_type = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$contentId, $contentType]);
            $moduleId = $stmt->fetchColumn();
            
            if ($moduleId) {
                return $moduleId;
            }
            
            // Also check if it's a content_id (for backward compatibility)
            $sql = "SELECT module_id FROM course_module_content 
                    WHERE content_id = ? AND content_type = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$contentId, $contentType]);
            
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Error in getModuleIdForContent: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if content is a prerequisite
     */
    private function isContentPrerequisite($courseId, $contentId, $contentType) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            // Check if contentId is a course_prerequisites.id
            $sql = "SELECT COUNT(*) FROM course_prerequisites 
                    WHERE id = ? AND course_id = ? AND prerequisite_type = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$contentId, $courseId, $contentType]);
            
            if ($stmt->fetchColumn() > 0) {
                return true;
            }
            
            // Also check if it's a prerequisite_id (for backward compatibility)
            $sql = "SELECT COUNT(*) FROM course_prerequisites 
                    WHERE course_id = ? AND prerequisite_id = ? AND prerequisite_type = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId, $contentId, $contentType]);
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error checking if content is prerequisite: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update prerequisite completion if content is a prerequisite
     * Only creates completion records if prerequisite is not already completed
     */
    private function updatePrerequisiteCompletionIfApplicable($userId, $courseId, $contentId, $contentType, $clientId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            // First check if contentId is a course_prerequisites.id
            $sql = "SELECT id, prerequisite_id, prerequisite_type FROM course_prerequisites 
                    WHERE id = ? AND course_id = ? AND prerequisite_type = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$contentId, $courseId, $contentType]);
            $prerequisite = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If not found, check if it's a prerequisite_id (for backward compatibility)
            if (!$prerequisite) {
                $sql = "SELECT id, prerequisite_id, prerequisite_type FROM course_prerequisites 
                        WHERE course_id = ? AND prerequisite_id = ? AND prerequisite_type = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([$courseId, $contentId, $contentType]);
                $prerequisite = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            if ($prerequisite) {
                // Check if prerequisite is already completed
                $existingCompletion = $this->prerequisiteCompletionModel->getCompletion(
                    $userId, 
                    $courseId, 
                    $prerequisite['prerequisite_id'], 
                    $prerequisite['prerequisite_type'], 
                    $clientId
                );
                
                // Only update if prerequisite is not already completed
                if (!$existingCompletion || !isset($existingCompletion['is_completed']) || !$existingCompletion['is_completed']) {
                    $this->updatePrerequisiteCompletion($userId, $courseId, $prerequisite['prerequisite_id'], $prerequisite['prerequisite_type'], $clientId);
                }
            }
        } catch (Exception $e) {
            error_log("Error in updatePrerequisiteCompletionIfApplicable: " . $e->getMessage());
        }
    }

    /**
     * Update post-requisite completion if content is a post-requisite
     * Only creates completion records if post-requisite is not already completed
     */
    private function updatePostRequisiteCompletionIfApplicable($userId, $courseId, $contentId, $contentType, $clientId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT content_id, content_type FROM course_post_requisites 
                    WHERE course_id = ? AND content_id = ? AND content_type = ? AND is_deleted = 0";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId, $contentId, $contentType]);
            
            $postRequisite = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($postRequisite) {
                // Check if post-requisite is already completed
                $existingCompletion = $this->postRequisiteCompletionModel->getCompletion(
                    $userId, 
                    $courseId, 
                    $postRequisite['content_id'], 
                    $postRequisite['content_type'], 
                    $clientId
                );
                
                // Only update if post-requisite is not already completed
                if (!$existingCompletion || !$existingCompletion['is_completed']) {
                    $this->updatePostRequisiteCompletion($userId, $courseId, $postRequisite['content_id'], $postRequisite['content_type'], $clientId);
                }
            }
        } catch (Exception $e) {
            error_log("Error in updatePostRequisiteCompletionIfApplicable: " . $e->getMessage());
        }
    }

    /**
     * Recalculate all completions for a course (useful for maintenance)
     */
    public function recalculateCourseCompletions($userId, $courseId, $clientId) {
        try {
            // Recalculate all module completions
            $this->recalculateModuleCompletions($userId, $courseId, $clientId);
            
            // Recalculate all prerequisite completions
            $this->recalculatePrerequisiteCompletions($userId, $courseId, $clientId);
            
            // Recalculate all post-requisite completions
            $this->recalculatePostRequisiteCompletions($userId, $courseId, $clientId);
            
            // Update course completion
            $this->updateCourseCompletion($userId, $courseId, $clientId);
            
            return true;
        } catch (Exception $e) {
            error_log("Error in recalculateCourseCompletions: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recalculate all module completions for a course
     */
    private function recalculateModuleCompletions($userId, $courseId, $clientId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT id FROM course_modules 
                    WHERE course_id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId]);
            $modules = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($modules as $moduleId) {
                $this->moduleCompletionModel->updateModuleCompletionFromContent($userId, $courseId, $moduleId, $clientId);
            }
        } catch (Exception $e) {
            error_log("Error in recalculateModuleCompletions: " . $e->getMessage());
        }
    }

    /**
     * Recalculate all prerequisite completions for a course
     */
    private function recalculatePrerequisiteCompletions($userId, $courseId, $clientId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT prerequisite_id, prerequisite_type FROM course_prerequisites 
                    WHERE course_id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId]);
            $prerequisites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($prerequisites as $prerequisite) {
                $this->prerequisiteCompletionModel->updatePrerequisiteCompletionFromProgress($userId, $courseId, $prerequisite['prerequisite_id'], $prerequisite['prerequisite_type'], $clientId);
            }
        } catch (Exception $e) {
            error_log("Error in recalculatePrerequisiteCompletions: " . $e->getMessage());
        }
    }

    /**
     * Recalculate all post-requisite completions for a course
     */
    private function recalculatePostRequisiteCompletions($userId, $courseId, $clientId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT content_id, content_type FROM course_post_requisites 
                    WHERE course_id = ? AND is_deleted = 0";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId]);
            $postRequisites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($postRequisites as $postRequisite) {
                $this->postRequisiteCompletionModel->updatePostRequisiteCompletionFromProgress($userId, $courseId, $postRequisite['content_id'], $postRequisite['content_type'], $clientId);
            }
        } catch (Exception $e) {
            error_log("Error in recalculatePostRequisiteCompletions: " . $e->getMessage());
        }
    }

    /**
     * Start tracking prerequisite when user opens it
     */
    public function startPrerequisiteTracking($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
        try {
            return $this->prerequisiteCompletionModel->startTracking($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId);
        } catch (Exception $e) {
            error_log("Error in startPrerequisiteTracking: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Start tracking module when user opens it
     */
    public function startModuleTracking($userId, $courseId, $moduleId, $clientId) {
        try {
            return $this->moduleCompletionModel->getOrCreateCompletion($userId, $courseId, $moduleId, $clientId);
        } catch (Exception $e) {
            error_log("Error in startModuleTracking: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Start module tracking if content belongs to a module
     */
    public function startModuleTrackingIfApplicable($userId, $courseId, $contentId, $contentType, $clientId) {
        try {
            // Get module ID for this content
            $moduleId = $this->getModuleIdForContent($contentId, $contentType);
            
            if ($moduleId) {
                return $this->startModuleTracking($userId, $courseId, $moduleId, $clientId);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error in startModuleTrackingIfApplicable: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark prerequisite as completed
     */
    public function markPrerequisiteComplete($userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId) {
        try {
            // Use progress-derived update to ensure time_spent and completed_at are populated
            return $this->prerequisiteCompletionModel->updatePrerequisiteCompletionFromProgress(
                $userId, $courseId, $prerequisiteId, $prerequisiteType, $clientId
            );
        } catch (Exception $e) {
            error_log("Error in markPrerequisiteComplete: " . $e->getMessage());
            return false;
        }
    }
}
?>
