<?php
require_once 'config/Database.php';

class MyCoursesModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
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
                    uc.completion_percentage AS progress, uc.status AS user_course_status
                FROM courses c
                INNER JOIN course_applicability ca ON ca.course_id = c.id";
        
        // Add client_id filter to course_applicability join
        if ($clientId) {
            $sql .= " AND ca.client_id = :client_id";
        }
        
        $sql .= " LEFT JOIN course_enrollments uc ON uc.course_id = c.id AND uc.user_id = :user_id
                LEFT JOIN course_categories cat ON c.category_id = cat.id
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

        // Filter by status (map frontend statuses to enrollment statuses)
        if ($status === 'not_started') {
            // Not started = no enrollment yet or still in 'enrolled' state
            $sql .= " AND (uc.status IS NULL OR uc.status IN ('enrolled'))";
        } elseif ($status === 'in_progress') {
            $sql .= " AND uc.status = 'in_progress'";
        } elseif ($status === 'completed') {
            $sql .= " AND uc.status = 'completed'";
        }

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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        
        $sql .= " LEFT JOIN course_enrollments uc ON uc.course_id = c.id AND uc.user_id = :user_id
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
} 