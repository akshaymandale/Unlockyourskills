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
    public function getUserCourses($userId, $status = '', $search = '', $page = 1, $perPage = 12) {
        $offset = ($page - 1) * $perPage;
        $params = [':user_id' => $userId];
        $where = '1=1';

        // Join course_applicability to get assigned courses
        $sql = "SELECT c.id, c.name, c.category_id, c.subcategory_id, c.thumbnail_image, c.course_status, c.difficulty_level, c.created_by, c.created_at, c.updated_at,
                    cat.name AS category_name, subcat.name AS subcategory_name,
                    uc.progress, uc.status AS user_course_status
                FROM courses c
                INNER JOIN course_applicability ca ON ca.course_id = c.id
                LEFT JOIN user_courses uc ON uc.course_id = c.id AND uc.user_id = :user_id
                LEFT JOIN course_categories cat ON c.category_id = cat.id
                LEFT JOIN course_subcategories subcat ON c.subcategory_id = subcat.id
                WHERE (ca.applicability_type = 'all' OR (ca.applicability_type = 'user' AND ca.user_id = :user_id))";

        // Filter by status
        if ($status === 'not_started') {
            $sql .= " AND (uc.status IS NULL OR uc.status = 'not_started')";
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
} 