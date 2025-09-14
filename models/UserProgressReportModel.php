<?php
// models/UserProgressReportModel.phprequire_once __DIR__ . '/../config/Database.php';

class UserProgressReportModel {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Get all custom fields for a client that can be used for filtering
     */
    public function getCustomFieldsForFiltering($clientId) {
        try {
            $sql = "SELECT id, field_name, field_label, field_type, field_options 
                    FROM custom_fields 
                    WHERE client_id = ? AND is_deleted = 0 AND is_active = 1 
                    AND field_type IN ('select', 'radio', 'checkbox')
                    ORDER BY field_order ASC, id ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientId]);
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode field_options JSON and format for frontend
            foreach ($fields as &$field) {
                if ($field['field_options']) {
                    $options = json_decode($field['field_options'], true);
                    if (is_array($options)) {
                        // Clean up options (remove \r\n and empty values)
                        $field['field_options'] = array_filter(array_map('trim', $options));
                    } else {
                        // If JSON decode failed, try to parse as string with line breaks
                        $rawOptions = $field['field_options'];
                        // Remove quotes and split by \r\n
                        $rawOptions = str_replace(['"', "'"], '', $rawOptions);
                        $field['field_options'] = array_filter(array_map('trim', explode("\\r\\n", $rawOptions)));
                    }
                } else {
                    $field['field_options'] = [];
                }
            }
            
            return $fields;
        } catch (Exception $e) {
            error_log("Error getting custom fields for filtering: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get department field ID for a client (legacy method - keeping for backward compatibility)
     */
    private function getDepartmentFieldId($clientId) {
        try {
            $sql = "SELECT id FROM custom_fields WHERE client_id = ? AND field_name = 'department' AND is_deleted = 0 LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['id'] : null;
        } catch (Exception $e) {
            error_log("Error getting department field ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user progress data with filters - using MyCourses logic
     */
    public function getUserProgressData($filters = []) {
        try {
            $clientId = $filters['client_id'] ?? $_SESSION['user']['client_id'];
            $departmentFieldId = $this->getDepartmentFieldId($clientId);
            
            $whereConditions = [];
            $params = [];

            // User filter
            if (!empty($filters['user_ids']) && is_array($filters['user_ids'])) {
                $placeholders = str_repeat('?,', count($filters['user_ids']) - 1) . '?';
                $whereConditions[] = "u.id IN ($placeholders)";
                $params = array_merge($params, $filters['user_ids']);
            }

            // Department filter
            if (!empty($filters['departments']) && is_array($filters['departments']) && $departmentFieldId) {
                $placeholders = str_repeat('?,', count($filters['departments']) - 1) . '?';
                $whereConditions[] = "cfv.field_value IN ($placeholders)";
                $params = array_merge($params, $filters['departments']);
            }

            // Course filter
            if (!empty($filters['course_ids']) && is_array($filters['course_ids'])) {
                $placeholders = str_repeat('?,', count($filters['course_ids']) - 1) . '?';
                $whereConditions[] = "c.id IN ($placeholders)";
                $params = array_merge($params, $filters['course_ids']);
            }

            // Custom field filter
            if (!empty($filters['custom_field_id']) && !empty($filters['custom_field_value'])) {
                if (is_array($filters['custom_field_value'])) {
                    $placeholders = str_repeat('?,', count($filters['custom_field_value']) - 1) . '?';
                    $whereConditions[] = "cfv_custom.field_value IN ($placeholders)";
                    $params = array_merge($params, $filters['custom_field_value']);
                } else {
                    $whereConditions[] = "cfv_custom.field_value = ?";
                    $params[] = $filters['custom_field_value'];
                }
            }

            // Client filter
            $whereConditions[] = "u.client_id = ?";
            $params[] = $clientId;

            $whereClause = !empty($whereConditions) ? "WHERE " . implode(' AND ', $whereConditions) : "";

            // Department field selection
            $departmentSelect = $departmentFieldId ? 
                "cfv.field_value as department," : 
                "NULL as department,";

            // Department join
            $departmentJoin = $departmentFieldId ? 
                "LEFT JOIN custom_field_values cfv ON u.id = cfv.user_id AND cfv.custom_field_id = $departmentFieldId AND cfv.is_deleted = 0" : 
                "";

            // Custom field join
            $customFieldJoin = (!empty($filters['custom_field_id']) && !empty($filters['custom_field_value'])) ? 
                "LEFT JOIN custom_field_values cfv_custom ON u.id = cfv_custom.user_id AND cfv_custom.custom_field_id = {$filters['custom_field_id']} AND cfv_custom.is_deleted = 0" : 
                "";

            $sql = "
                SELECT 
                    CONCAT(u.id, '_', c.id) as id,
                    u.id as user_id,
                    c.id as course_id,
                    0 as completion_percentage,
                    'not_started' as status,
                    NULL as last_accessed_at,
                    NULL as completed_at,
                    0 as total_time_spent,
                    u.full_name as user_name,
                    u.email as user_email,
                    $departmentSelect
                    c.name as course_name,
                    c.description as course_description,
                    'not_started' as progress_status
                FROM user_profiles u
                CROSS JOIN courses c
                $departmentJoin
                $customFieldJoin
                $whereClause
                ORDER BY u.id, c.id
            ";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process each user-course combination to calculate actual progress
            foreach ($result as &$record) {
                $isStarted = $this->isCourseStarted($record['course_id'], $record['user_id'], $clientId);
                
                if ($isStarted) {
                    $progress = $this->calculateCourseProgress($record['course_id'], $record['user_id'], $clientId);
                    $record['completion_percentage'] = $progress;
                    
                    // Calculate last accessed time and total time spent
                    $lastAccessed = $this->getLastAccessedTime($record['course_id'], $record['user_id'], $clientId);
                    $totalTime = $this->getTotalTimeSpent($record['course_id'], $record['user_id'], $clientId);
                    
                    $record['last_accessed_at'] = $lastAccessed;
                    $record['total_time_spent'] = $totalTime;
                    
                    // Debug logging (commented out for production)
                    // error_log("User {$record['user_id']}, Course {$record['course_id']}: Last Accessed = " . ($lastAccessed ?: 'NULL') . ", Total Time = {$totalTime}");
                    
                    if ($progress >= 100) {
                        $record['status'] = 'completed';
                        $record['progress_status'] = 'completed';
                    } else {
                        $record['status'] = 'in_progress';
                        $record['progress_status'] = 'in_progress';
                    }
                } else {
                    $record['status'] = 'not_started';
                    $record['progress_status'] = 'not_started';
                    $record['last_accessed_at'] = null;
                    $record['total_time_spent'] = 0;
                }
            }
            
            // Apply status filter after calculation
            if (!empty($filters['status']) && is_array($filters['status'])) {
                $result = array_filter($result, function($record) use ($filters) {
                    return in_array($record['progress_status'], $filters['status']);
                });
            }
            
            // Apply date range filter after calculation
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                // This would need to be implemented based on actual activity dates
                // For now, we'll skip date filtering as it requires more complex logic
            }
            
            return array_values($result);
        } catch (Exception $e) {
            error_log("Error getting user progress data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if a course has been started by checking for any activity in course content
     * @param int $courseId
     * @param int $userId
     * @param int $clientId
     * @return bool
     */
    public function isCourseStarted($courseId, $userId, $clientId) {
        try {
            // First check if there are any prerequisites
            $prereqCountStmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM course_prerequisites cp
                WHERE cp.course_id = ? AND cp.deleted_at IS NULL
            ");
            $prereqCountStmt->execute([$courseId]);
            $prereqCountResult = $prereqCountStmt->fetch(PDO::FETCH_ASSOC);
            
            // If there are prerequisites, check if they are ALL met
            if ($prereqCountResult['count'] > 0) {
                $prereqMetStmt = $this->conn->prepare("
                    SELECT COUNT(*) as count
                    FROM course_prerequisites cp
                    WHERE cp.course_id = ? AND cp.deleted_at IS NULL
                    AND (
                        (cp.prerequisite_type = 'assessment' AND EXISTS (
                            SELECT 1 FROM assessment_attempts aa 
                            WHERE aa.assessment_id = cp.prerequisite_id 
                            AND aa.user_id = ? AND (aa.client_id = ? OR aa.client_id IS NULL)
                            AND aa.status = 'completed'
                        ))
                        OR (cp.prerequisite_type = 'survey' AND EXISTS (
                            SELECT 1 FROM course_survey_responses csr 
                            WHERE csr.survey_package_id = cp.prerequisite_id 
                            AND csr.user_id = ? AND csr.client_id = ?
                        ))
                        OR (cp.prerequisite_type = 'feedback' AND EXISTS (
                            SELECT 1 FROM course_feedback_responses cfr 
                            WHERE cfr.feedback_package_id = cp.prerequisite_id 
                            AND cfr.user_id = ? AND cfr.client_id = ?
                        ))
                        OR (cp.prerequisite_type = 'assignment' AND EXISTS (
                            SELECT 1 FROM assignment_submissions asub 
                            WHERE asub.assignment_package_id = cp.prerequisite_id 
                            AND asub.user_id = ? AND asub.course_id = ? AND asub.client_id = ?
                        ))
                        OR (cp.prerequisite_type = 'external' AND EXISTS (
                            SELECT 1 FROM external_progress ep 
                            WHERE ep.content_id = cp.prerequisite_id 
                            AND ep.user_id = ? AND ep.course_id = ? AND ep.client_id = ?
                            AND ep.is_completed = 1
                        ))
                        OR (cp.prerequisite_type = 'scorm' AND EXISTS (
                            SELECT 1 FROM scorm_progress sp 
                            WHERE sp.content_id = cp.prerequisite_id 
                            AND sp.user_id = ? AND sp.course_id = ? AND sp.client_id = ?
                            AND sp.is_completed = 1
                        ))
                        OR (cp.prerequisite_type = 'document' AND EXISTS (
                            SELECT 1 FROM document_progress dp 
                            WHERE dp.content_id = cp.prerequisite_id 
                            AND dp.user_id = ? AND dp.course_id = ? AND dp.client_id = ?
                            AND dp.is_completed = 1
                        ))
                        OR (cp.prerequisite_type = 'audio' AND EXISTS (
                            SELECT 1 FROM audio_progress ap 
                            WHERE ap.content_id = cp.prerequisite_id 
                            AND ap.user_id = ? AND ap.course_id = ? AND ap.client_id = ?
                            AND ap.is_completed = 1
                        ))
                        OR (cp.prerequisite_type = 'video' AND EXISTS (
                            SELECT 1 FROM video_progress vp 
                            WHERE vp.content_id = cp.prerequisite_id 
                            AND vp.user_id = ? AND vp.course_id = ? AND vp.client_id = ?
                            AND vp.is_completed = 1
                        ))
                        OR (cp.prerequisite_type = 'image' AND EXISTS (
                            SELECT 1 FROM image_progress ip 
                            WHERE ip.content_id = cp.prerequisite_id 
                            AND ip.user_id = ? AND ip.course_id = ? AND ip.client_id = ?
                            AND ip.is_completed = 1
                        ))
                        OR (cp.prerequisite_type = 'interactive' AND EXISTS (
                            SELECT 1 FROM interactive_progress inp 
                            WHERE inp.content_id = cp.prerequisite_id 
                            AND inp.user_id = ? AND inp.course_id = ? AND inp.client_id = ?
                            AND inp.is_completed = 1
                        ))
                        OR (cp.prerequisite_type = 'non_scorm' AND EXISTS (
                            SELECT 1 FROM non_scorm_progress nsp 
                            WHERE nsp.content_id = cp.prerequisite_id 
                            AND nsp.user_id = ? AND nsp.course_id = ? AND nsp.client_id = ?
                            AND nsp.is_completed = 1
                        ))
                    )
                ");
                $prereqMetStmt->execute([
                    $courseId, $userId, $clientId, // assessment
                    $userId, $clientId, // survey
                    $userId, $clientId, // feedback
                    $userId, $courseId, $clientId, // assignment
                    $userId, $courseId, $clientId, // external
                    $userId, $courseId, $clientId, // scorm
                    $userId, $courseId, $clientId, // document
                    $userId, $courseId, $clientId, // audio
                    $userId, $courseId, $clientId, // video
                    $userId, $courseId, $clientId, // image
                    $userId, $courseId, $clientId, // interactive
                    $userId, $courseId, $clientId  // non_scorm
                ]);
                $prereqMetResult = $prereqMetStmt->fetch(PDO::FETCH_ASSOC);
                
                // If prerequisites exist but are not ALL met, course is not started
                if ($prereqMetResult['count'] < $prereqCountResult['count']) {
                    return false;
                }
            }
            
            // Check module content activity
            $moduleStmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM course_module_content cmc
                JOIN course_modules cm ON cmc.module_id = cm.id
                WHERE cm.course_id = ? AND cmc.deleted_at IS NULL AND cm.deleted_at IS NULL
                AND (
                    (cmc.content_type = 'assessment' AND EXISTS (
                        SELECT 1 FROM assessment_attempts aa 
                        WHERE aa.assessment_id = cmc.content_id 
                        AND aa.user_id = ? AND (aa.client_id = ? OR aa.client_id IS NULL)
                    ))
                    OR (cmc.content_type = 'survey' AND EXISTS (
                        SELECT 1 FROM course_survey_responses csr 
                        WHERE csr.survey_package_id = cmc.content_id 
                        AND csr.user_id = ? AND csr.client_id = ?
                    ))
                    OR (cmc.content_type = 'feedback' AND EXISTS (
                        SELECT 1 FROM course_feedback_responses cfr 
                        WHERE cfr.feedback_package_id = cmc.content_id 
                        AND cfr.user_id = ? AND cfr.client_id = ?
                    ))
                    OR (cmc.content_type = 'assignment' AND EXISTS (
                        SELECT 1 FROM assignment_submissions asub 
                        WHERE asub.assignment_package_id = cmc.content_id 
                        AND asub.user_id = ? AND asub.course_id = ? AND asub.client_id = ?
                    ))
                    OR (cmc.content_type = 'scorm' AND EXISTS (
                        SELECT 1 FROM scorm_progress sp 
                        WHERE sp.content_id = cmc.id 
                        AND sp.user_id = ? AND sp.client_id = ? AND sp.course_id = ?
                    ))
                    OR (cmc.content_type = 'document' AND EXISTS (
                        SELECT 1 FROM document_progress dp 
                        WHERE dp.content_id = cmc.id 
                        AND dp.user_id = ? AND dp.client_id = ? AND dp.course_id = ?
                    ))
                    OR (cmc.content_type = 'video' AND EXISTS (
                        SELECT 1 FROM video_progress vp 
                        WHERE vp.content_id = cmc.id 
                        AND vp.user_id = ? AND vp.client_id = ? AND vp.course_id = ?
                    ))
                    OR (cmc.content_type = 'audio' AND EXISTS (
                        SELECT 1 FROM audio_progress ap 
                        WHERE ap.content_id = cmc.id 
                        AND ap.user_id = ? AND ap.client_id = ? AND ap.course_id = ?
                    ))
                    OR (cmc.content_type = 'image' AND EXISTS (
                        SELECT 1 FROM image_progress ip 
                        WHERE ip.content_id = cmc.id 
                        AND ip.user_id = ? AND ip.client_id = ? AND ip.course_id = ?
                    ))
                    OR (cmc.content_type = 'interactive' AND EXISTS (
                        SELECT 1 FROM interactive_progress ip 
                        WHERE ip.content_id = cmc.id 
                        AND ip.user_id = ? AND ip.client_id = ? AND ip.course_id = ?
                    ))
                    OR (cmc.content_type = 'external' AND EXISTS (
                        SELECT 1 FROM external_progress ep 
                        WHERE ep.content_id = cmc.id 
                        AND ep.user_id = ? AND ep.client_id = ? AND ep.course_id = ?
                    ))
                )
            ");
            $moduleStmt->execute([
                $courseId, $userId, $clientId, // assessment
                $userId, $clientId, // survey
                $userId, $clientId, // feedback
                $userId, $courseId, $clientId, // assignment
                $userId, $clientId, $courseId, // scorm
                $userId, $clientId, $courseId, // document
                $userId, $clientId, $courseId, // video
                $userId, $clientId, $courseId, // audio
                $userId, $clientId, $courseId, // image
                $userId, $clientId, $courseId, // interactive
                $userId, $clientId, $courseId  // external
            ]);
            $moduleResult = $moduleStmt->fetch(PDO::FETCH_ASSOC);
            
            return $moduleResult['count'] > 0;
            
        } catch (Exception $e) {
            error_log("Error checking if course started: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate course progress percentage based on completion tables
     * @param int $courseId
     * @param int $userId
     * @param int $clientId
     * @return int
     */
    public function calculateCourseProgress($courseId, $userId, $clientId) {
        try {
            // Get course completion data from completion tables
            $courseCompletion = $this->getCourseCompletionData($userId, $courseId, $clientId);
            
            if ($courseCompletion) {
                return (int) $courseCompletion['completion_percentage'];
            }
            
            // Fallback: calculate from individual completion tables
            return $this->calculateCourseProgressFromCompletionTables($userId, $courseId, $clientId);
            
        } catch (Exception $e) {
            error_log("Error calculating course progress: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get course completion data from course_completion table
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return array|null
     */
    private function getCourseCompletionData($userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT completion_percentage, is_completed, 
                       prerequisites_completed, modules_completed, post_requisites_completed
                FROM course_completion 
                WHERE user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $clientId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting course completion data: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate course progress from individual completion tables
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return int
     */
    private function calculateCourseProgressFromCompletionTables($userId, $courseId, $clientId) {
        try {
            $totalWeight = 0;
            $completedWeight = 0;
            
            // Get prerequisite completion data
            $prereqData = $this->getPrerequisiteCompletionData($userId, $courseId, $clientId);
            if (!empty($prereqData)) {
                $totalWeight += count($prereqData);
                foreach ($prereqData as $prereq) {
                    if ($prereq['is_completed']) {
                        $completedWeight++;
                    }
                }
            }
            
            // Get module completion data
            $moduleData = $this->getModuleCompletionData($userId, $courseId, $clientId);
            if (!empty($moduleData)) {
                $totalWeight += count($moduleData);
                foreach ($moduleData as $module) {
                    if ($module['is_completed']) {
                        $completedWeight++;
                    }
                }
            }
            
            // Get post-requisite completion data
            $postreqData = $this->getPostRequisiteCompletionData($userId, $courseId, $clientId);
            if (!empty($postreqData)) {
                $totalWeight += count($postreqData);
                foreach ($postreqData as $postreq) {
                    if ($postreq['is_completed']) {
                        $completedWeight++;
                    }
                }
            }
            
            if ($totalWeight > 0) {
                return round(($completedWeight / $totalWeight) * 100);
            }
            
            return 0;
            
        } catch (Exception $e) {
            error_log("Error calculating course progress from completion tables: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get prerequisite completion data
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return array
     */
    private function getPrerequisiteCompletionData($userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT prerequisite_id, prerequisite_type, completion_percentage, is_completed
                FROM prerequisite_completion 
                WHERE user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting prerequisite completion data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get module completion data
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return array
     */
    private function getModuleCompletionData($userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT module_id, completion_percentage, is_completed
                FROM module_completion 
                WHERE user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting module completion data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get post-requisite completion data
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return array
     */
    private function getPostRequisiteCompletionData($userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT post_requisite_id, content_type, completion_percentage, is_completed
                FROM post_requisite_completion 
                WHERE user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting post-requisite completion data: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get course modules with content
     * @param int $courseId
     * @return array
     */
    public function getCourseModules($courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT cm.*
                FROM course_modules cm
                WHERE cm.course_id = ? AND (cm.deleted_at IS NULL OR cm.deleted_at = '0000-00-00 00:00:00') 
                ORDER BY cm.module_order ASC
            ");
            $stmt->execute([$courseId]);
            $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get module content for each module
            foreach ($modules as &$module) {
                $module['content'] = $this->getModuleContent($module['id']);
            }

            return $modules;
        } catch (Exception $e) {
            error_log("Error getting course modules: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get module content
     * @param int $moduleId
     * @return array
     */
    private function getModuleContent($moduleId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT cmc.*, cmc.content_id as content_item_id
                FROM course_module_content cmc
                WHERE cmc.module_id = ? AND (cmc.deleted_at IS NULL OR cmc.deleted_at = '0000-00-00 00:00:00')
                ORDER BY cmc.content_order ASC
            ");
            $stmt->execute([$moduleId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting module content: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get content progress for a specific content item
     * @param array $content
     * @param int $userId
     * @param int $clientId
     * @param int $courseId
     * @return int
     */
    public function getContentProgress($content, $userId, $clientId, $courseId) {
        $contentType = $content['content_type'];
        $contentId = $content['content_item_id'] ?? $content['content_id'];
        
        switch ($contentType) {
            case 'assessment':
                return $this->getAssessmentProgress($contentId, $userId, $clientId, $courseId);
            case 'assignment':
                return $this->getAssignmentProgress($contentId, $userId, $clientId, $courseId);
            case 'scorm':
                return $this->getScormProgress($content['id'], $userId, $clientId, $courseId);
            case 'video':
            case 'audio':
            case 'image':
            case 'interactive':
            case 'non_scorm':
            case 'external':
                // For these content types, check if there's actual progress, otherwise consider completed by default
                $progress = $this->getGeneralContentProgress($contentId, $userId, $clientId, $courseId);
                return $progress > 0 ? $progress : 100; // Default to 100% if no progress tracking
            default:
                return 0;
        }
    }

    /**
     * Get general content progress (video, audio, etc.)
     * @param int $contentId
     * @param int $userId
     * @param int $clientId
     * @param int $courseId
     * @return int
     */
    private function getGeneralContentProgress($contentId, $userId, $clientId, $courseId) {
        try {
            // Check audio progress specifically
            $stmt = $this->conn->prepare("
                SELECT listened_percentage, is_completed FROM audio_progress 
                WHERE content_id = ? AND user_id = ? AND client_id = ? AND course_id = ?
            ");
            $stmt->execute([$contentId, $userId, $clientId, $courseId]);
            $audioProgress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($audioProgress) {
                if ($audioProgress['is_completed']) {
                    return 100;
                } else {
                    return intval($audioProgress['listened_percentage'] ?? 0);
                }
            }
            
            // Check video progress
            $stmt = $this->conn->prepare("
                SELECT watched_percentage, is_completed FROM video_progress 
                WHERE content_id = ? AND user_id = ? AND client_id = ?
            ");
            $stmt->execute([$contentId, $userId, $clientId]);
            $videoProgress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($videoProgress) {
                if ($videoProgress['is_completed']) {
                    return 100;
                } else {
                    return intval($videoProgress['watched_percentage'] ?? 0);
                }
            }
            
            return 0;
        } catch (Exception $e) {
            error_log("Error getting general content progress: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get assessment progress
     * @param int $assessmentId
     * @param int $userId
     * @param int $clientId
     * @param int $courseId
     * @return int
     */
    private function getAssessmentProgress($assessmentId, $userId, $clientId, $courseId) {
        try {
            // Check if user has attempted this assessment (regardless of which course context)
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM assessment_attempts 
                WHERE assessment_id = ? AND user_id = ? AND (client_id = ? OR client_id IS NULL)
            ");
            $stmt->execute([$assessmentId, $userId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                // Check if passed (regardless of which course context)
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as count FROM assessment_results 
                    WHERE assessment_id = ? AND user_id = ? AND (client_id = ? OR client_id IS NULL) AND passed = 1
                ");
                $stmt->execute([$assessmentId, $userId, $clientId]);
                $passed = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $passed['count'] > 0 ? 100 : 50; // 100% if passed, 50% if attempted
            }
            
            return 0;
        } catch (Exception $e) {
            error_log("Error getting assessment progress: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get SCORM progress
     * @param int $contentId
     * @param int $userId
     * @param int $clientId
     * @param int $courseId
     * @return int
     */
    private function getScormProgress($contentId, $userId, $clientId, $courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT lesson_status, score_raw, score_max FROM scorm_progress 
                WHERE content_id = ? AND user_id = ? AND client_id = ? AND course_id = ?
            ");
            $stmt->execute([$contentId, $userId, $clientId, $courseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                if ($result['lesson_status'] === 'completed' || $result['lesson_status'] === 'passed') {
                    return 100;
                } elseif ($result['lesson_status'] === 'incomplete' || $result['lesson_status'] === 'browsed') {
                    // Calculate progress based on score if available
                    if ($result['score_max'] > 0 && $result['score_raw'] !== null) {
                        $percentage = ($result['score_raw'] / $result['score_max']) * 100;
                        return min(100, max(0, intval($percentage)));
                    }
                    return 25; // Started but not completed
                }
            }
            
            return 0;
        } catch (Exception $e) {
            error_log("Error getting SCORM progress: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get course prerequisites
     * @param int $courseId
     * @return array
     */
    public function getCoursePrerequisites($courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM course_prerequisites 
                WHERE course_id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting course prerequisites: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get prerequisite progress
     * @param array $prereq
     * @param int $userId
     * @param int $clientId
     * @param int $courseId
     * @return int
     */
    public function getPrerequisiteProgress($prereq, $userId, $clientId, $courseId) {
        $prereqType = $prereq['prerequisite_type'];
        $prereqId = $prereq['prerequisite_id'];
        
        switch ($prereqType) {
            case 'assessment':
                return $this->getAssessmentProgress($prereqId, $userId, $clientId, $courseId);
            case 'assignment':
                return $this->getAssignmentProgress($prereqId, $userId, $clientId, $courseId);
            case 'survey':
                return $this->getSurveyProgress($prereqId, $userId, $clientId, $courseId);
            case 'feedback':
                return $this->getFeedbackProgress($prereqId, $userId, $clientId, $courseId);
            case 'external':
                return 100; // External prerequisites are considered completed by default
            default:
                return 0;
        }
    }

    /**
     * Get survey progress
     * @param int $surveyId
     * @param int $userId
     * @param int $clientId
     * @param int $courseId
     * @return int
     */
    private function getSurveyProgress($surveyId, $userId, $clientId, $courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM course_survey_responses 
                WHERE survey_package_id = ? AND user_id = ? AND client_id = ?
            ");
            $stmt->execute([$surveyId, $userId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0 ? 100 : 0;
        } catch (Exception $e) {
            error_log("Error getting survey progress: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get feedback progress
     * @param int $feedbackId
     * @param int $userId
     * @param int $clientId
     * @param int $courseId
     * @return int
     */
    private function getFeedbackProgress($feedbackId, $userId, $clientId, $courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM course_feedback_responses 
                WHERE feedback_package_id = ? AND user_id = ? AND client_id = ?
            ");
            $stmt->execute([$feedbackId, $userId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0 ? 100 : 0;
        } catch (Exception $e) {
            error_log("Error getting feedback progress: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get assignment progress
     * @param int $assignmentId
     * @param int $userId
     * @param int $clientId
     * @param int $courseId
     * @return int
     */
    public function getAssignmentProgress($assignmentId, $userId, $clientId, $courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT submission_status FROM assignment_submissions 
                WHERE assignment_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$assignmentId, $userId, $courseId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return in_array($result['submission_status'], ['submitted', 'graded', 'returned', 'resubmitted']) ? 100 : 0;
            }
            
            return 0;
        } catch (Exception $e) {
            error_log("Error getting assignment progress: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get summary statistics - using MyCourses logic
     */
    public function getSummaryStats($filters = []) {
        try {
            // Get the same data as getUserProgressData and calculate summary
            $data = $this->getUserProgressData($filters);
            
            $totalRecords = count($data);
            $uniqueUsers = count(array_unique(array_column($data, 'user_id')));
            $uniqueCourses = count(array_unique(array_column($data, 'course_id')));
            
            $notStarted = 0;
            $inProgress = 0;
            $completed = 0;
            $totalCompletion = 0;
            
            foreach ($data as $record) {
                if ($record['progress_status'] === 'not_started') {
                    $notStarted++;
                } elseif ($record['progress_status'] === 'in_progress') {
                    $inProgress++;
                } elseif ($record['progress_status'] === 'completed') {
                    $completed++;
                }
                
                $totalCompletion += $record['completion_percentage'];
            }
            
            $avgCompletion = $totalRecords > 0 ? round($totalCompletion / $totalRecords, 2) : 0;
            
            return [
                'total_progress_records' => $totalRecords,
                'unique_users' => $uniqueUsers,
                'unique_courses' => $uniqueCourses,
                'avg_completion' => $avgCompletion,
                'not_started_courses' => $notStarted,
                'in_progress_courses' => $inProgress,
                'completed_courses' => $completed
            ];
        } catch (Exception $e) {
            error_log("Error getting summary stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get filter options
     */
    public function getFilterOptions($clientId) {
        try {
            $departmentFieldId = $this->getDepartmentFieldId($clientId);

            $options = [
                'users' => [],
                'courses' => [],
                'departments' => []
            ];

            // Get users
            $userSql = "SELECT id, full_name, email FROM user_profiles WHERE client_id = ? AND is_deleted = 0 ORDER BY full_name";
            $stmt = $this->conn->prepare($userSql);
            $stmt->execute([$clientId]);
            $options['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get courses
            $courseSql = "SELECT id, name FROM courses WHERE client_id = ? AND is_deleted = 0 ORDER BY name";
            $stmt = $this->conn->prepare($courseSql);
            $stmt->execute([$clientId]);
            $options['courses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get departments
            if ($departmentFieldId) {
                $deptSql = "
                    SELECT DISTINCT cfv.field_value as department 
                    FROM custom_field_values cfv 
                    WHERE cfv.custom_field_id = ? AND cfv.is_deleted = 0 AND cfv.field_value IS NOT NULL AND cfv.field_value != ''
                    ORDER BY cfv.field_value
                ";
                $stmt = $this->conn->prepare($deptSql);
                $stmt->execute([$departmentFieldId]);
                $options['departments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            return $options;
        } catch (Exception $e) {
            error_log("Error getting filter options: " . $e->getMessage());
            return ['users' => [], 'courses' => [], 'departments' => []];
        }
    }

    /**
     * Get the last accessed time for a user-course combination from all content progress tables
     * @param int $courseId
     * @param int $userId
     * @param int $clientId
     * @return string|null
     */
    public function getLastAccessedTime($courseId, $userId, $clientId) {
        try {
            // error_log("getLastAccessedTime called for Course: {$courseId}, User: {$userId}, Client: {$clientId}");
            
            $sql = "
                SELECT MAX(last_accessed) as last_accessed
                FROM (
                    SELECT last_watched_at as last_accessed FROM video_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ? AND last_watched_at IS NOT NULL
                    UNION ALL
                    SELECT last_listened_at as last_accessed FROM audio_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ? AND last_listened_at IS NOT NULL
                    UNION ALL
                    SELECT last_viewed_at as last_accessed FROM document_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ? AND last_viewed_at IS NOT NULL
                    UNION ALL
                    SELECT viewed_at as last_accessed FROM image_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ? AND viewed_at IS NOT NULL
                    UNION ALL
                    SELECT last_interaction_at as last_accessed FROM interactive_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ? AND last_interaction_at IS NOT NULL
                    UNION ALL
                    SELECT last_visited_at as last_accessed FROM external_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ? AND last_visited_at IS NOT NULL
                    UNION ALL
                    SELECT updated_at as last_accessed FROM scorm_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ? AND updated_at IS NOT NULL
                ) as all_progress
            ";
            
            $stmt = $this->conn->prepare($sql);
            
            // Interleave the parameters: courseId, userId, clientId for each table
            $allParams = [];
            for ($i = 0; $i < 7; $i++) {
                $allParams[] = $courseId;
                $allParams[] = $userId;
                $allParams[] = $clientId;
            }
            
            // error_log("Executing query with params: " . implode(', ', $allParams));
            $stmt->execute($allParams);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // error_log("Query result: " . print_r($result, true));
            return $result['last_accessed'] ?: null;
        } catch (Exception $e) {
            error_log("Error getting last accessed time: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the total time spent for a user-course combination from all content progress tables
     * @param int $courseId
     * @param int $userId
     * @param int $clientId
     * @return int
     */
    public function getTotalTimeSpent($courseId, $userId, $clientId) {
        try {
            // error_log("getTotalTimeSpent called for Course: {$courseId}, User: {$userId}, Client: {$clientId}");
            
            $sql = "
                SELECT COALESCE(SUM(time_spent), 0) as total_time
                FROM (
                    SELECT COALESCE(current_time, 0) as time_spent FROM video_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT COALESCE(current_time, 0) as time_spent FROM audio_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT COALESCE(time_spent, 0) as time_spent FROM document_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT 0 as time_spent FROM image_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT COALESCE(time_spent, 0) as time_spent FROM interactive_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT COALESCE(time_spent, 0) as time_spent FROM external_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT COALESCE(CAST(SUBSTRING_INDEX(total_time, ':', -1) AS UNSIGNED), 0) as time_spent FROM scorm_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ? AND total_time IS NOT NULL AND total_time != ''
                ) as all_progress
            ";
            
            $stmt = $this->conn->prepare($sql);
            
            // Interleave the parameters: courseId, userId, clientId for each table
            $allParams = [];
            for ($i = 0; $i < 7; $i++) {
                $allParams[] = $courseId;
                $allParams[] = $userId;
                $allParams[] = $clientId;
            }
            
            // error_log("Executing time spent query with params: " . implode(', ', $allParams));
            $stmt->execute($allParams);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // error_log("Time spent query result: " . print_r($result, true));
            return (int)$result['total_time'];
        } catch (Exception $e) {
            error_log("Error getting total time spent: " . $e->getMessage());
            return 0;
        }
    }
}
