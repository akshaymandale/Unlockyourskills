<?php
require_once 'config/Database.php';

class MyCoursesModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Check if a course has been started by checking progress tables only
     * @param int $courseId
     * @param int $userId
     * @param int $clientId
     * @return bool
     */
    public function isCourseStarted($courseId, $userId, $clientId) {
        try {
            // Check if there are any entries in progress tables only
            // Course is started if there's any user interaction with course content
            $progressCheckStmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM (
                    -- Progress tables only
                    SELECT 1 FROM video_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT 1 FROM audio_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT 1 FROM document_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT 1 FROM image_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT 1 FROM scorm_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT 1 FROM external_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT 1 FROM interactive_progress 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT 1 FROM assignment_submissions 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT 1 FROM course_survey_responses 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                    UNION ALL
                    SELECT 1 FROM course_feedback_responses 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                ) as progress_entries
            ");
            $progressCheckStmt->execute([
                // Progress tables only
                $courseId, $userId, $clientId, // video_progress
                $courseId, $userId, $clientId, // audio_progress
                $courseId, $userId, $clientId, // document_progress
                $courseId, $userId, $clientId, // image_progress
                $courseId, $userId, $clientId, // scorm_progress
                $courseId, $userId, $clientId, // external_progress
                $courseId, $userId, $clientId, // interactive_progress
                $courseId, $userId, $clientId, // assignment_submissions
                $courseId, $userId, $clientId, // course_survey_responses
                $courseId, $userId, $clientId  // course_feedback_responses
            ]);
            $progressResult = $progressCheckStmt->fetch(PDO::FETCH_ASSOC);
            
            // If there are any entries in progress tables, course is started
            if ($progressResult['count'] > 0) {
                return true;
            }
            
            // Fallback: Check if there are any prerequisites
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
                            AND asub.submission_status IN ('submitted', 'graded', 'returned') AND asub.is_deleted = 0
                        ))
                        OR (cp.prerequisite_type = 'external' AND EXISTS (
                            SELECT 1 FROM external_progress ep 
                            WHERE ep.prerequisite_id = cp.id 
                            AND ep.user_id = ? AND ep.course_id = ? AND ep.client_id = ?
                            AND ep.is_completed = 1
                        ))
                        OR (cp.prerequisite_type = 'scorm' AND EXISTS (
                            SELECT 1 FROM scorm_progress sp 
                            WHERE sp.scorm_package_id = cp.prerequisite_id 
                            AND sp.user_id = ? AND sp.course_id = ? AND sp.client_id = ?
                            AND sp.lesson_status IN ('completed', 'passed') AND sp.completed_at IS NOT NULL
                        ))
                        OR (cp.prerequisite_type = 'video' AND EXISTS (
                            SELECT 1 FROM video_progress vp 
                            WHERE vp.prerequisite_id = cp.id 
                            AND vp.user_id = ? AND vp.course_id = ? AND vp.client_id = ?
                            AND vp.is_completed = 1
                        ))
                        OR (cp.prerequisite_type = 'audio' AND EXISTS (
                            SELECT 1 FROM audio_progress ap 
                            WHERE ap.prerequisite_id = cp.id 
                            AND ap.user_id = ? AND ap.course_id = ? AND ap.client_id = ?
                            AND ap.is_completed = 1
                        ))
                        OR (cp.prerequisite_type = 'document' AND EXISTS (
                            SELECT 1 FROM document_progress dp 
                            WHERE dp.prerequisite_id = cp.id 
                            AND dp.user_id = ? AND dp.course_id = ? AND dp.client_id = ?
                            AND dp.is_completed = 1
                        ))
                        OR (cp.prerequisite_type = 'image' AND EXISTS (
                            SELECT 1 FROM image_progress ip 
                            WHERE ip.prerequisite_id = cp.id 
                            AND ip.user_id = ? AND ip.course_id = ? AND ip.client_id = ?
                            AND ip.is_completed = 1
                        ))
                        OR (cp.prerequisite_type = 'interactive' AND EXISTS (
                            SELECT 1 FROM interactive_progress inp 
                            WHERE inp.prerequisite_id = cp.id 
                            AND inp.user_id = ? AND inp.course_id = ? AND inp.client_id = ?
                            AND inp.is_completed = 1
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
                    $userId, $courseId, $clientId, // video
                    $userId, $courseId, $clientId, // audio
                    $userId, $courseId, $clientId, // document
                    $userId, $courseId, $clientId, // image
                    $userId, $courseId, $clientId  // interactive
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
                        AND aa.user_id = ? AND aa.course_id = ? AND (aa.client_id = ? OR aa.client_id IS NULL)
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
                $courseId, $userId, $courseId, $clientId, // assessment
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
        
        // Get user's custom field values for matching
        $userCustomFields = $this->getUserCustomFieldValues($userId, $clientId);

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
                    OR (ca.applicability_type = 'custom_field' AND EXISTS (
                        SELECT 1 FROM custom_field_values cfv 
                        WHERE cfv.user_id = :user_id 
                        AND cfv.custom_field_id = ca.custom_field_id 
                        AND cfv.field_value COLLATE utf8mb4_unicode_ci = ca.custom_field_value COLLATE utf8mb4_unicode_ci
                        AND cfv.is_deleted = 0
                    ))
                )";

        // Note: Status filtering will be done after determining actual course status

        // Search
        if ($search) {
            $sql .= " AND (c.name LIKE :search OR cat.name LIKE :search OR subcat.name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " GROUP BY c.id ORDER BY ca.created_at DESC, c.name ASC";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
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
        
        // Apply pagination after status filtering
        $courses = array_values($courses);
        $totalCourses = count($courses);
        $courses = array_slice($courses, $offset, $perPage);
        
        return $courses;
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
                    OR (ca.applicability_type = 'custom_field' AND EXISTS (
                        SELECT 1 FROM custom_field_values cfv 
                        WHERE cfv.user_id = :user_id 
                        AND cfv.custom_field_id = ca.custom_field_id 
                        AND cfv.field_value COLLATE utf8mb4_unicode_ci = ca.custom_field_value COLLATE utf8mb4_unicode_ci
                        AND cfv.is_deleted = 0
                    ))
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
     * Calculate course progress percentage based on progress tables
     * @param int $courseId
     * @param int $userId
     * @param int $clientId
     * @return int
     */
    private function calculateCourseProgress($courseId, $userId, $clientId) {
        try {
            // Calculate progress from actual progress tables, not completion tables
            return $this->calculateCourseProgressFromProgressTables($userId, $courseId, $clientId);
            
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
     * Calculate course progress from progress tables
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return int
     */
    private function calculateCourseProgressFromProgressTables($userId, $courseId, $clientId) {
        try {
            $totalWeight = 0;
            $completedWeight = 0;
            
            // Get all course content (prerequisites + modules + post-requisites)
            $allContent = $this->getAllCourseContent($courseId);
            
            if (empty($allContent)) {
                return 0;
            }
            
            foreach ($allContent as $content) {
                $totalWeight++;
                $isCompleted = $this->isContentCompletedInProgressTables($content, $userId, $courseId, $clientId);
                if ($isCompleted) {
                    $completedWeight++;
                }
            }
            
            if ($totalWeight > 0) {
                return round(($completedWeight / $totalWeight) * 100);
            }
            
            return 0;
            
        } catch (Exception $e) {
            error_log("Error calculating course progress from progress tables: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all course content (prerequisites + modules + post-requisites)
     * @param int $courseId
     * @return array
     */
    private function getAllCourseContent($courseId) {
        try {
            $allContent = [];
            
            // Get prerequisites
            $stmt = $this->conn->prepare("
                SELECT cp.id, cp.prerequisite_id as content_id, cp.prerequisite_type as content_type, 'prerequisite' as content_category
                FROM course_prerequisites cp
                WHERE cp.course_id = ? AND cp.deleted_at IS NULL
            ");
            $stmt->execute([$courseId]);
            $prerequisites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $allContent = array_merge($allContent, $prerequisites);
            
            // Get module content
            $stmt = $this->conn->prepare("
                SELECT cmc.id as content_id, cmc.content_type, 'module' as content_category
                FROM course_module_content cmc
                INNER JOIN course_modules cm ON cmc.module_id = cm.id
                WHERE cm.course_id = ? AND cmc.deleted_at IS NULL
            ");
            $stmt->execute([$courseId]);
            $moduleContent = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $allContent = array_merge($allContent, $moduleContent);
            
            // Get post-requisites
            $stmt = $this->conn->prepare("
                SELECT cpr.id, cpr.content_id, cpr.content_type, 'post_requisite' as content_category
                FROM course_post_requisites cpr
                WHERE cpr.course_id = ?
            ");
            $stmt->execute([$courseId]);
            $postRequisites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $allContent = array_merge($allContent, $postRequisites);
            
            return $allContent;
            
        } catch (Exception $e) {
            error_log("Error getting all course content: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if content is completed based on progress tables
     * @param array $content
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return bool
     */
    private function isContentCompletedInProgressTables($content, $userId, $courseId, $clientId) {
        try {
            // Handle different field structures based on content category
            if (!isset($content['content_category'])) {
                error_log("Content missing content_category: " . print_r($content, true));
                return false;
            }
            
            if ($content['content_category'] === 'prerequisite') {
                $contentType = $content['content_type'];
                $contentId = $content['content_id']; // Use the prerequisite content ID
            } elseif ($content['content_category'] === 'module') {
                $contentType = $content['content_type'];
                $contentId = $content['content_id']; // Use the module content ID
            } elseif ($content['content_category'] === 'post_requisite') {
                $contentType = $content['content_type'];
                $contentId = $content['content_id']; // Use the post-requisite content ID (assessment ID)
            } else {
                error_log("Unknown content category: " . $content['content_category']);
                return false;
            }
            
            switch ($contentType) {
                case 'scorm':
                    // For SCORM, we need to handle prerequisites vs modules differently
                    if (isset($content['content_category']) && $content['content_category'] === 'prerequisite') {
                        // For prerequisites, use the SCORM package ID
                        $scormPackageId = $content['prerequisite_id'] ?? $content['content_id'];
                        return $this->isScormContentCompleted($scormPackageId, $userId, $courseId, $clientId);
                    } else {
                        // For modules, use the course_module_content.id
                        $moduleContentId = $content['content_id'];
                        return $this->isScormContentCompleted($moduleContentId, $userId, $courseId, $clientId);
                    }
                case 'video':
                    return $this->isVideoContentCompleted($contentId, $userId, $courseId, $clientId);
                case 'audio':
                    return $this->isAudioContentCompleted($contentId, $userId, $courseId, $clientId);
                case 'document':
                    return $this->isDocumentContentCompleted($contentId, $userId, $courseId, $clientId);
                case 'image':
                    return $this->isImageContentCompleted($contentId, $userId, $courseId, $clientId);
                case 'external':
                    return $this->isExternalContentCompleted($contentId, $userId, $courseId, $clientId);
                case 'interactive':
                    return $this->isInteractiveContentCompleted($contentId, $userId, $courseId, $clientId);
                case 'assignment':
                    return $this->isAssignmentCompleted($contentId, $userId, $courseId, $clientId);
                case 'assessment':
                    return $this->isAssessmentCompleted($contentId, $userId, $courseId, $clientId);
                case 'survey':
                    return $this->isSurveyCompleted($contentId, $userId, $courseId, $clientId);
                case 'feedback':
                    return $this->isFeedbackCompleted($contentId, $userId, $courseId, $clientId);
                default:
                    return false;
            }
            
        } catch (Exception $e) {
            error_log("Error checking content completion: " . $e->getMessage());
            return false;
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
                // For these content types, check if there's actual progress
                $progress = $this->getGeneralContentProgress($contentId, $userId, $clientId, $courseId);
                return $progress; // Return actual progress (0 if no progress tracking)
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
            // Check if user has attempted this assessment for this specific course
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM assessment_attempts 
                WHERE assessment_id = ? AND user_id = ? AND course_id = ? AND (client_id = ? OR client_id IS NULL)
            ");
            $stmt->execute([$assessmentId, $userId, $courseId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                // Check if passed for this specific course
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as count FROM assessment_results 
                    WHERE assessment_id = ? AND user_id = ? AND course_id = ? AND (client_id = ? OR client_id IS NULL) AND passed = 1
                ");
                $stmt->execute([$assessmentId, $userId, $courseId, $clientId]);
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
     * Check if SCORM content is completed
     * @param int $contentId
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return bool
     */
    private function isScormContentCompleted($contentId, $userId, $courseId, $clientId) {
        try {
            // Check if this is a prerequisite (SCORM package ID) or module content (course_module_content.id)
            // For prerequisites: contentId is the SCORM package ID, need to check scorm_package_id
            // For module content: contentId is the course_module_content.id, need to check content_id
            
            // First, try direct lookup by content_id (for module content)
            $stmt = $this->conn->prepare("
                SELECT sp.completed_at 
                FROM scorm_progress sp
                WHERE sp.user_id = ? AND sp.course_id = ? AND sp.content_id = ? AND sp.client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['completed_at'])) {
                return true;
            }
            
            // If not found, try lookup by scorm_package_id (for prerequisites)
            $stmt = $this->conn->prepare("
                SELECT sp.completed_at 
                FROM scorm_progress sp
                WHERE sp.user_id = ? AND sp.course_id = ? AND sp.scorm_package_id = ? AND sp.client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && !empty($result['completed_at']);
            
        } catch (Exception $e) {
            error_log("Error checking SCORM content completion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if video content is completed
     * @param int $contentId
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return bool
     */
    private function isVideoContentCompleted($contentId, $userId, $courseId, $clientId) {
        try {
            // First try to find by content_id (for module content)
            $stmt = $this->conn->prepare("
                SELECT is_completed FROM video_progress 
                WHERE content_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$contentId, $userId, $courseId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['is_completed'] == 1;
            }
            
            // If not found by content_id, try to find by video_package_id (for prerequisite content)
            // For prerequisites, the contentId is actually the video package ID
            $stmt = $this->conn->prepare("
                SELECT is_completed FROM video_progress 
                WHERE video_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$contentId, $userId, $courseId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['is_completed'] == 1;
            
        } catch (Exception $e) {
            error_log("Error checking video content completion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if audio content is completed
     * @param int $contentId
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return bool
     */
    private function isAudioContentCompleted($contentId, $userId, $courseId, $clientId) {
        try {
            // For audio content, we need to handle both module and prerequisite cases
            // First try with content_id (for module content)
            $stmt = $this->conn->prepare("
                SELECT is_completed FROM audio_progress 
                WHERE content_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$contentId, $userId, $courseId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If no progress found with content_id, try with prerequisite_id (for prerequisite content)
            if (!$result) {
                $stmt = $this->conn->prepare("
                    SELECT is_completed FROM audio_progress 
                    WHERE prerequisite_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
                ");
                $stmt->execute([$contentId, $userId, $courseId, $clientId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // If still no result, check if this is a prerequisite by looking up the prerequisite record
            if (!$result) {
                $stmt = $this->conn->prepare("
                    SELECT cp.id FROM course_prerequisites cp 
                    WHERE cp.course_id = ? AND cp.prerequisite_id = ? AND cp.prerequisite_type = 'audio'
                ");
                $stmt->execute([$courseId, $contentId]);
                $prereqRecord = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($prereqRecord) {
                    // This is an audio prerequisite, check with the prerequisite record ID
                    $stmt = $this->conn->prepare("
                        SELECT is_completed FROM audio_progress 
                        WHERE prerequisite_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
                    ");
                    $stmt->execute([$prereqRecord['id'], $userId, $courseId, $clientId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
            
            return $result && $result['is_completed'] == 1;
            
        } catch (Exception $e) {
            error_log("Error checking audio content completion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if document content is completed
     * @param int $contentId
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return bool
     */
    private function isDocumentContentCompleted($contentId, $userId, $courseId, $clientId) {
        try {
            // First try to find by content_id (for module content)
            $stmt = $this->conn->prepare("
                SELECT is_completed FROM document_progress 
                WHERE content_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$contentId, $userId, $courseId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['is_completed'] == 1;
            }
            
            // If not found by content_id, check if this is a prerequisite and get the course_prerequisites.id
            $stmt = $this->conn->prepare("
                SELECT id FROM course_prerequisites 
                WHERE course_id = ? AND prerequisite_id = ? AND prerequisite_type = 'document'
            ");
            $stmt->execute([$courseId, $contentId]);
            $prereqResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($prereqResult) {
                // For prerequisites, look for records with prerequisite_id = course_prerequisites.id
                $stmt = $this->conn->prepare("
                    SELECT is_completed FROM document_progress 
                    WHERE prerequisite_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
                ");
                $stmt->execute([$prereqResult['id'], $userId, $courseId, $clientId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                return $result && $result['is_completed'] == 1;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Error checking document content completion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if image content is completed
     * @param int $contentId
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return bool
     */
    private function isImageContentCompleted($contentId, $userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT is_completed FROM image_progress 
                WHERE content_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$contentId, $userId, $courseId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['is_completed'] == 1;
            
        } catch (Exception $e) {
            error_log("Error checking image content completion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if external content is completed
     * @param int $contentId
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return bool
     */
    private function isExternalContentCompleted($contentId, $userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT is_completed FROM external_progress 
                WHERE content_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$contentId, $userId, $courseId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['is_completed'] == 1;
            
        } catch (Exception $e) {
            error_log("Error checking external content completion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if interactive content is completed
     * @param int $contentId
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return bool
     */
    private function isInteractiveContentCompleted($contentId, $userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT is_completed FROM interactive_progress 
                WHERE content_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$contentId, $userId, $courseId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['is_completed'] == 1;
            
        } catch (Exception $e) {
            error_log("Error checking interactive content completion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if assignment is completed
     * @param int $contentId
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return bool
     */
    private function isAssignmentCompleted($contentId, $userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT submitted_at FROM assignment_submissions 
                WHERE assignment_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$contentId, $userId, $courseId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && !empty($result['submitted_at']);
            
        } catch (Exception $e) {
            error_log("Error checking assignment completion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if survey is completed
     * @param int $contentId
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return bool
     */
    private function isSurveyCompleted($contentId, $userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT completed_at FROM course_survey_responses 
                WHERE survey_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$contentId, $userId, $courseId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && !empty($result['completed_at']);
            
        } catch (Exception $e) {
            error_log("Error checking survey completion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if feedback is completed
     * @param int $contentId
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return bool
     */
    private function isFeedbackCompleted($contentId, $userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT completed_at FROM course_feedback_responses 
                WHERE feedback_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$contentId, $userId, $courseId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && !empty($result['completed_at']);
            
        } catch (Exception $e) {
            error_log("Error checking feedback completion: " . $e->getMessage());
            return false;
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
    
    /**
     * Check if assessment is completed
     * @param int $assessmentId
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return bool
     */
    private function isAssessmentCompleted($assessmentId, $userId, $courseId, $clientId) {
        try {
            // For assessments, we need to check the LATEST result to see if it's passed
            // because the same assessment can appear in different contexts
            // Also, we should not filter by client_id for assessments as they may be completed with different client_ids
            $stmt = $this->conn->prepare("
                SELECT 
                    ar.passed,
                    ROW_NUMBER() OVER (ORDER BY ar.completed_at DESC) as rn
                FROM assessment_results ar
                WHERE (ar.assessment_id = ? OR ar.content_id = ?) 
                AND ar.user_id = ? 
                AND ar.course_id = ?
                ORDER BY ar.completed_at DESC
                LIMIT 1
            ");
            $stmt->execute([$assessmentId, $assessmentId, $userId, $courseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Return true only if the latest result exists and is passed
            return $result && $result['passed'] == 1;
        } catch (Exception $e) {
            error_log("Error checking assessment completion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's custom field values for course applicability matching
     */
    private function getUserCustomFieldValues($userId, $clientId = null) {
        try {
            $sql = "SELECT cfv.custom_field_id, cfv.field_value
                    FROM custom_field_values cfv
                    JOIN custom_fields cf ON cfv.custom_field_id = cf.id
                    WHERE cfv.user_id = :user_id AND cfv.is_deleted = 0 AND cf.is_active = 1";
            
            $params = [':user_id' => $userId];
            
            if ($clientId) {
                $sql .= " AND cf.client_id = :client_id";
                $params[':client_id'] = $clientId;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            $values = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $values[$row['custom_field_id']] = $row['field_value'];
            }
            
            return $values;
        } catch (Exception $e) {
            error_log("Error getting user custom field values: " . $e->getMessage());
            return [];
        }
    }
} 