<?php
require_once 'config/Database.php';

class SearchCoursesModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get searchable courses (where show_in_search = 'yes')
     * @param int $userId
     * @param string $search
     * @param int $page
     * @param int $perPage
     * @param int $clientId
     * @return array
     */
    public function getSearchableCourses($userId, $search = '', $page = 1, $perPage = 12, $clientId = null) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        
        // Build base query for searchable courses (excluding courses already applicable to user)
        $sql = "SELECT 
                    c.id, c.name, c.category_id, c.subcategory_id, c.thumbnail_image, c.course_status, c.difficulty_level,
                    c.created_by, c.created_at, c.updated_at, c.client_id, c.description,
                    cat.name AS category_name, subcat.name AS subcategory_name,
                    0 AS progress, 'not_started' AS user_course_status
                FROM courses c
                LEFT JOIN course_categories cat ON c.category_id = cat.id
                LEFT JOIN course_subcategories subcat ON c.subcategory_id = subcat.id
                WHERE c.is_deleted = 0 
                AND c.show_in_search = 'yes'
                AND NOT EXISTS (
                    SELECT 1 FROM course_applicability ca 
                    WHERE ca.course_id = c.id 
                    AND ca.user_id = :user_id";
        
        // Add client_id filter for course_applicability
        if ($clientId) {
            $sql .= " AND ca.client_id = :client_id";
        }
        $sql .= ")";
        
        // Add client_id filter for courses
        if ($clientId) {
            $sql .= " AND c.client_id = :client_id";
            $params[':client_id'] = $clientId;
        }
        
        // Add user_id parameter
        $params[':user_id'] = $userId;
        
        // Search functionality
        if ($search) {
            $sql .= " AND (c.name LIKE :search OR cat.name LIKE :search OR subcat.name LIKE :search OR c.description LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $sql .= " ORDER BY c.created_at DESC, c.name ASC";
        
        // Add pagination
        $sql .= " LIMIT :offset, :per_page";
        $params[':offset'] = $offset;
        $params[':per_page'] = $perPage;
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            if ($k === ':offset' || $k === ':per_page') {
                $stmt->bindValue($k, $v, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($k, $v);
            }
        }
        $stmt->execute();
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process each course - since these are not applicable to user, they are all "not_started"
        foreach ($courses as &$course) {
            // Since these courses are not in course_applicability, they are all "not_started"
            $course['user_course_status'] = 'not_started';
            $course['progress'] = 0;
            
            // Check enrollment status
            $course['enrollment_status'] = $this->getCourseEnrollmentStatus($course['id'], $userId, $clientId);
            
            // Add module count for display
            $course['module_count'] = $this->getCourseModuleCount($course['id']);
        }
        
        return $courses;
    }

    /**
     * Get total count of searchable courses
     * @param int $userId
     * @param string $search
     * @param int $clientId
     * @return int
     */
    public function getSearchableCoursesCount($userId, $search = '', $clientId = null) {
        $params = [];
        
        $sql = "SELECT COUNT(*) as total
                FROM courses c
                LEFT JOIN course_categories cat ON c.category_id = cat.id
                LEFT JOIN course_subcategories subcat ON c.subcategory_id = subcat.id
                WHERE c.is_deleted = 0 
                AND c.show_in_search = 'yes'
                AND NOT EXISTS (
                    SELECT 1 FROM course_applicability ca 
                    WHERE ca.course_id = c.id 
                    AND ca.user_id = :user_id";
        
        // Add client_id filter for course_applicability
        if ($clientId) {
            $sql .= " AND ca.client_id = :client_id";
        }
        $sql .= ")";
        
        // Add client_id filter for courses
        if ($clientId) {
            $sql .= " AND c.client_id = :client_id";
            $params[':client_id'] = $clientId;
        }
        
        // Add user_id parameter
        $params[':user_id'] = $userId;
        
        // Search functionality
        if ($search) {
            $sql .= " AND (c.name LIKE :search OR cat.name LIKE :search OR subcat.name LIKE :search OR c.description LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return intval($result['total']);
    }

    /**
     * Check if a course has been started by checking progress tables
     * @param int $courseId
     * @param int $userId
     * @param int $clientId
     * @return bool
     */
    private function isCourseStarted($courseId, $userId, $clientId) {
        try {
            $progressCheckStmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM (
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
                    SELECT 1 FROM assessment_attempts 
                    WHERE course_id = ? AND user_id = ? AND client_id = ?
                ) as progress_check
            ");
            
            $params = array_fill(0, 18, $courseId);
            for ($i = 0; $i < 18; $i += 3) {
                $params[$i + 1] = $userId;
                $params[$i + 2] = $clientId;
            }
            
            $progressCheckStmt->execute($params);
            $progressResult = $progressCheckStmt->fetch(PDO::FETCH_ASSOC);
            
            return $progressResult['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking if course is started: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate course progress percentage
     * @param int $courseId
     * @param int $userId
     * @param int $clientId
     * @return int
     */
    private function calculateCourseProgress($courseId, $userId, $clientId) {
        try {
            // Get all course content
            $allContent = $this->getAllCourseContent($courseId);
            
            if (empty($allContent)) {
                return 0;
            }
            
            $completedCount = 0;
            
            foreach ($allContent as $content) {
                if ($this->isContentCompletedInProgressTables($content['content_id'], $content['content_type'], $userId, $clientId)) {
                    $completedCount++;
                }
            }
            
            return intval(($completedCount / count($allContent)) * 100);
        } catch (Exception $e) {
            error_log("Error calculating course progress: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all course content (prerequisites and modules)
     * @param int $courseId
     * @return array
     */
    private function getAllCourseContent($courseId) {
        $allContent = [];
        
        try {
            // Get prerequisites
            $stmt = $this->conn->prepare("
                SELECT id as content_id, content_type, 'prerequisite' as content_category
                FROM course_prerequisites 
                WHERE course_id = ? AND deleted_at IS NULL
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
            
        } catch (Exception $e) {
            error_log("Error getting course content: " . $e->getMessage());
        }
        
        return $allContent;
    }

    /**
     * Check if content is completed in progress tables
     * @param int $contentId
     * @param string $contentType
     * @param int $userId
     * @param int $clientId
     * @return bool
     */
    private function isContentCompletedInProgressTables($contentId, $contentType, $userId, $clientId) {
        try {
            switch ($contentType) {
                case 'video':
                    $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM video_progress WHERE content_id = ? AND user_id = ? AND client_id = ? AND is_completed = 1");
                    break;
                case 'audio':
                    $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM audio_progress WHERE content_id = ? AND user_id = ? AND client_id = ? AND is_completed = 1");
                    break;
                case 'document':
                    $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM document_progress WHERE content_id = ? AND user_id = ? AND client_id = ? AND is_completed = 1");
                    break;
                case 'image':
                    $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM image_progress WHERE content_id = ? AND user_id = ? AND client_id = ? AND is_completed = 1");
                    break;
                case 'scorm':
                    $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM scorm_progress WHERE content_id = ? AND user_id = ? AND client_id = ? AND is_completed = 1");
                    break;
                case 'external':
                    $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM external_progress WHERE content_id = ? AND user_id = ? AND client_id = ? AND is_completed = 1");
                    break;
                case 'interactive':
                    $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM interactive_progress WHERE content_id = ? AND user_id = ? AND client_id = ? AND is_completed = 1");
                    break;
                case 'assignment':
                    $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM assignment_submissions WHERE content_id = ? AND user_id = ? AND client_id = ? AND status = 'submitted'");
                    break;
                case 'assessment':
                    $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM assessment_attempts WHERE assessment_package_id = ? AND user_id = ? AND client_id = ? AND status = 'completed'");
                    break;
                default:
                    return false;
            }
            
            $stmt->execute([$contentId, $userId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking content completion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get course module count
     * @param int $courseId
     * @return int
     */
    private function getCourseModuleCount($courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM course_modules 
                WHERE course_id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$courseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return intval($result['count']);
        } catch (Exception $e) {
            error_log("Error getting course module count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get course enrollment status for a user
     * @param int $courseId
     * @param int $userId
     * @param int $clientId
     * @return string|null
     */
    private function getCourseEnrollmentStatus($courseId, $userId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT status 
                FROM course_enrollments 
                WHERE course_id = ? AND user_id = ? AND client_id = ? AND deleted_at IS NULL
                ORDER BY enrollment_date DESC
                LIMIT 1
            ");
            $stmt->execute([$courseId, $userId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['status'] : null;
        } catch (Exception $e) {
            error_log("Error getting course enrollment status: " . $e->getMessage());
            return null;
        }
    }
}
