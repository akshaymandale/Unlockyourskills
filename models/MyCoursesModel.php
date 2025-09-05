<?php
require_once 'config/Database.php';

class MyCoursesModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
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
            // First check if prerequisites are met
            $prereqStmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM course_prerequisites cp
                WHERE cp.course_id = ? AND cp.deleted_at IS NULL
                AND (
                    (cp.prerequisite_type = 'assessment' AND EXISTS (
                        SELECT 1 FROM assessment_attempts aa 
                        WHERE aa.assessment_id = cp.prerequisite_id 
                        AND aa.user_id = ? AND (aa.client_id = ? OR aa.client_id IS NULL)
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
                    OR (cp.prerequisite_type = 'external')
                )
            ");
            $prereqStmt->execute([
                $courseId, $userId, $clientId, // assessment
                $userId, $clientId, // survey
                $userId, $clientId, // feedback
                $userId, $courseId, $clientId  // assignment
            ]);
            $prereqResult = $prereqStmt->fetch(PDO::FETCH_ASSOC);
            
            // If there are prerequisites and they are not met, course is not started
            if ($prereqResult['count'] > 0) {
                // There are prerequisites, check if they are met
                $prereqMetStmt = $this->conn->prepare("
                    SELECT COUNT(*) as count
                    FROM course_prerequisites cp
                    WHERE cp.course_id = ? AND cp.deleted_at IS NULL
                    AND (
                        (cp.prerequisite_type = 'assessment' AND EXISTS (
                            SELECT 1 FROM assessment_attempts aa 
                            WHERE aa.assessment_id = cp.prerequisite_id 
                            AND aa.user_id = ? AND (aa.client_id = ? OR aa.client_id IS NULL)
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
                        OR (cp.prerequisite_type = 'external')
                    )
                ");
                $prereqMetStmt->execute([
                    $courseId, $userId, $clientId, // assessment
                    $userId, $clientId, // survey
                    $userId, $clientId, // feedback
                    $userId, $courseId, $clientId  // assignment
                ]);
                $prereqMetResult = $prereqMetStmt->fetch(PDO::FETCH_ASSOC);
                
                // If prerequisites are not met, course is not started
                if ($prereqMetResult['count'] == 0) {
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
                $userId, $clientId, $courseId  // external
            ]);
            $moduleResult = $moduleStmt->fetch(PDO::FETCH_ASSOC);
            
            // If user has interacted with module content, course is started
            if ($moduleResult['count'] > 0) {
                return true;
            }
            
            // Note: Post-requisites are not checked for "started" status
            // They should only be used for "completed" status
            
            // If prerequisites are met but no course content interaction, course is not started
            return false;
            
        } catch (Exception $e) {
            error_log("Error checking if course is started: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get assigned courses for a user with status, search, and pagination
     * @param int $userId
     * @param string $status (not_started, in_progress, completed)
     * @param string $search
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getUserCourses($userId, $status = '', $search = '', $page = 1, $perPage = 12, $clientId = null) {
        
        $offset = ($page - 1) * $perPage;
        $params = [':user_id' => $userId];
        
        // Add client_id parameter if provided
        if ($clientId) {
            $params[':client_id'] = $clientId;
        }
        
        // Derive a reasonable custom field value from session (e.g., department or role)
        $userDepartment = '';
        if (isset($_SESSION['user']['user_role']) && !empty($_SESSION['user']['user_role'])) {
            $userDepartment = $_SESSION['user']['user_role'];
        }
        $params[':user_department'] = $userDepartment;

        // Build base query with client_id filtering
        $sql = "SELECT 
                    c.id, c.name, c.category_id, c.subcategory_id, c.thumbnail_image, c.course_status, c.difficulty_level,
                    c.created_by, c.created_at, c.updated_at, c.client_id,
                    cat.name AS category_name, subcat.name AS subcategory_name,
                    0 AS progress, 'not_started' AS user_course_status
                FROM courses c
                INNER JOIN course_applicability ca ON ca.course_id = c.id";
        
        // Add client_id filter to course_applicability join
        if ($clientId) {
            $sql .= " AND ca.client_id = :client_id";
        }
        
        $sql .= " LEFT JOIN course_categories cat ON c.category_id = cat.id
                LEFT JOIN course_subcategories subcat ON c.subcategory_id = subcat.id
                WHERE c.is_deleted = 0";
        
        // Add client_id filter to courses table as well for double security
        if ($clientId) {
            $sql .= " AND c.client_id = :client_id";
        }
        
        $sql .= " AND (
                    ca.applicability_type = 'all'
                    OR (ca.applicability_type = 'user' AND ca.user_id = :user_id)
                    OR (ca.applicability_type = 'custom_field' AND ca.custom_field_value = :user_department)
                )";

        // Note: Status filtering will be done after determining actual course status

        // Search
        if ($search) {
            $sql .= " AND (c.name LIKE :search OR cat.name LIKE :search OR subcat.name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " GROUP BY c.id ORDER BY c.name ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process each course to determine actual status
        foreach ($courses as &$course) {
            $isStarted = $this->isCourseStarted($course['id'], $userId, $clientId);
            
            if ($isStarted) {
                // Calculate progress percentage based on content completion
                $course['progress'] = $this->calculateCourseProgress($course['id'], $userId, $clientId);
                
                // Set status based on progress
                if ($course['progress'] >= 100) {
                    $course['user_course_status'] = 'completed';
                } else {
                    $course['user_course_status'] = 'in_progress';
                }
            } else {
                $course['user_course_status'] = 'not_started';
                $course['progress'] = 0;
            }
            
            // Add module count for display
            $course['module_count'] = $this->getCourseModuleCount($course['id']);
        }
        
        // Filter by status after determining actual status
        if ($status === 'not_started') {
            $courses = array_filter($courses, function($course) {
                return $course['user_course_status'] === 'not_started';
            });
        } elseif ($status === 'in_progress') {
            $courses = array_filter($courses, function($course) {
                return $course['user_course_status'] === 'in_progress';
            });
        } elseif ($status === 'completed') {
            $courses = array_filter($courses, function($course) {
                return $course['user_course_status'] === 'completed';
            });
        }
        
        return array_values($courses);
    }

    /**
     * Get total count of courses for a user (for pagination)
     * @param int $userId
     * @param string $status
     * @param string $search
     * @param int $clientId
     * @return int
     */
    public function getUserCoursesCount($userId, $status = '', $search = '', $clientId = null) {
        $params = [':user_id' => $userId];
        
        // Add client_id parameter if provided
        if ($clientId) {
            $params[':client_id'] = $clientId;
        }
        
        // Derive a reasonable custom field value from session
        $userDepartment = '';
        if (isset($_SESSION['user']['user_role']) && !empty($_SESSION['user']['user_role'])) {
            $userDepartment = $_SESSION['user']['user_role'];
        }
        $params[':user_department'] = $userDepartment;

        // Build count query with client_id filtering
        $sql = "SELECT COUNT(DISTINCT c.id) as total
                FROM courses c
                INNER JOIN course_applicability ca ON ca.course_id = c.id";
        
        // Add client_id filter to course_applicability join
        if ($clientId) {
            $sql .= " AND ca.client_id = :client_id";
        }
        
        $sql .= " WHERE c.is_deleted = 0";
        
        // Add client_id filter to courses table as well for double security
        if ($clientId) {
            $sql .= " AND c.client_id = :client_id";
        }
        
        $sql .= " AND (
                    ca.applicability_type = 'all'
                    OR (ca.applicability_type = 'user' AND ca.user_id = :user_id)
                    OR (ca.applicability_type = 'custom_field' AND ca.custom_field_value = :user_department)
                )";

        // Filter by status
        if ($status === 'not_started') {
            $sql .= " AND (uc.status IS NULL OR uc.status IN ('enrolled'))";
        } elseif ($status === 'in_progress') {
            $sql .= " AND uc.status = 'in_progress'";
        } elseif ($status === 'completed') {
            $sql .= " AND uc.status = 'completed'";
        }

        // Search
        if ($search) {
            $sql .= " AND (c.name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Calculate course progress percentage based on content completion
     * @param int $courseId
     * @param int $userId
     * @param int $clientId
     * @return int
     */
    private function calculateCourseProgress($courseId, $userId, $clientId) {
        try {
            // Get all modules and their content for this course
            $modules = $this->getCourseModules($courseId);
            $totalItems = 0;
            $completedItems = 0;
            $totalProgress = 0;
            
            foreach ($modules as $module) {
                if (isset($module['content']) && is_array($module['content'])) {
                    foreach ($module['content'] as $content) {
                        $totalItems++;
                        $contentProgress = $this->getContentProgress($content, $userId, $clientId, $courseId);
                        $totalProgress += $contentProgress;
                        
                        if ($contentProgress >= 100) {
                            $completedItems++;
                        }
                    }
                }
            }
            
            // Also count prerequisites as items
            $prerequisites = $this->getCoursePrerequisites($courseId);
            foreach ($prerequisites as $prereq) {
                $totalItems++;
                $prereqProgress = $this->getPrerequisiteProgress($prereq, $userId, $clientId, $courseId);
                $totalProgress += $prereqProgress;
                
                if ($prereqProgress >= 100) {
                    $completedItems++;
                }
            }
            
            if ($totalItems > 0) {
                return round($totalProgress / $totalItems);
            }
            
            return 0;
            
        } catch (Exception $e) {
            error_log("Error calculating course progress: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get course modules with content
     * @param int $courseId
     * @return array
     */
    private function getCourseModules($courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT cm.*, 
                       0 as module_progress,
                       'not_started' as module_status
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
     * Get course prerequisites
     * @param int $courseId
     * @return array
     */
    private function getCoursePrerequisites($courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT cp.*
                FROM course_prerequisites cp
                WHERE cp.course_id = ? AND cp.deleted_at IS NULL
                ORDER BY cp.sort_order ASC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting course prerequisites: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get content progress based on content type
     * @param array $content
     * @param int $userId
     * @param int $clientId
     * @return int
     */
    private function getContentProgress($content, $userId, $clientId, $courseId) {
        $contentType = $content['content_type'];
        $contentId = $content['content_item_id'] ?? $content['content_id'];
        
        switch ($contentType) {
            case 'assessment':
                return $this->getAssessmentProgress($contentId, $userId, $clientId, $courseId);
            case 'assignment':
                return $this->getAssignmentProgress($contentId, $userId, $clientId, $courseId);
            case 'scorm':
                return $this->getScormProgress($content['id'], $userId, $clientId, $courseId);
            case 'document':
                return $this->getDocumentProgress($content['id'], $userId, $clientId, $courseId);
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
     * Get prerequisite progress
     * @param array $prereq
     * @param int $userId
     * @param int $clientId
     * @param int $courseId
     * @return int
     */
    private function getPrerequisiteProgress($prereq, $userId, $clientId, $courseId) {
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
     * Get assessment progress
     * @param int $assessmentId
     * @param int $userId
     * @param int $clientId
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
     * Get assignment progress
     * @param int $assignmentId
     * @param int $userId
     * @param int $clientId
     * @return int
     */
    private function getAssignmentProgress($assignmentId, $userId, $clientId, $courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM assignment_submissions 
                WHERE assignment_package_id = ? AND user_id = ? AND client_id = ? AND course_id = ?
            ");
            $stmt->execute([$assignmentId, $userId, $clientId, $courseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0 ? 100 : 0;
        } catch (Exception $e) {
            error_log("Error getting assignment progress: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get survey progress
     * @param int $surveyId
     * @param int $userId
     * @param int $clientId
     * @return int
     */
    private function getSurveyProgress($surveyId, $userId, $clientId, $courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM course_survey_responses 
                WHERE survey_package_id = ? AND user_id = ? AND client_id = ? AND course_id = ?
            ");
            $stmt->execute([$surveyId, $userId, $clientId, $courseId]);
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
     * @return int
     */
    private function getFeedbackProgress($feedbackId, $userId, $clientId, $courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM course_feedback_responses 
                WHERE feedback_package_id = ? AND user_id = ? AND client_id = ? AND course_id = ?
            ");
            $stmt->execute([$feedbackId, $userId, $clientId, $courseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0 ? 100 : 0;
        } catch (Exception $e) {
            error_log("Error getting feedback progress: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get SCORM progress
     * @param int $contentId
     * @param int $userId
     * @param int $clientId
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
     * Get document progress
     * @param int $contentId
     * @param int $userId
     * @param int $clientId
     * @return int
     */
    private function getDocumentProgress($contentId, $userId, $clientId, $courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT viewed_percentage, is_completed FROM document_progress 
                WHERE content_id = ? AND user_id = ? AND client_id = ? AND course_id = ?
            ");
            $stmt->execute([$contentId, $userId, $clientId, $courseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                if ($result['is_completed']) {
                    return 100;
                } else {
                    return intval($result['viewed_percentage'] ?? 0);
                }
            }
            
            return 0;
        } catch (Exception $e) {
            error_log("Error getting document progress: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get general content progress (video, audio, etc.)
     * @param int $contentId
     * @param int $userId
     * @param int $clientId
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
     * Get module count for a course
     * @param int $courseId
     * @return int
     */
    private function getCourseModuleCount($courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count
                FROM course_modules cm
                WHERE cm.course_id = ? AND (cm.deleted_at IS NULL OR cm.deleted_at = '0000-00-00 00:00:00')
            ");
            $stmt->execute([$courseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            error_log("Error getting course module count: " . $e->getMessage());
            return 0;
        }
    }
} 