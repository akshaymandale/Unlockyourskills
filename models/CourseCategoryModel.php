<?php

class CourseCategoryModel {
    private $conn;
    private $table = 'course_categories';

    public function __construct($db = null) {
        $this->conn = $db ?: (new Database())->connect();
    }

    /**
     * Get all course categories for a client
     */
    public function getAllCategories($clientId = null, $includeInactive = false) {
        $sql = "SELECT cc.*, 
                       u.full_name as created_by_name,
                       (SELECT COUNT(*) FROM course_subcategories csc WHERE csc.category_id = cc.id AND csc.is_deleted = 0) as subcategory_count
                FROM {$this->table} cc
                LEFT JOIN user_profiles u ON cc.created_by = u.id
                WHERE cc.is_deleted = 0";
        
        $params = [];
        
        if ($clientId) {
            $sql .= " AND cc.client_id = ?";
            $params[] = $clientId;
        }
        
        if (!$includeInactive) {
            $sql .= " AND cc.is_active = 1";
        }
        
        $sql .= " ORDER BY cc.sort_order ASC, cc.name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get category by ID
     */
    public function getCategoryById($id, $clientId = null) {
        $sql = "SELECT cc.*, u.full_name as created_by_name
                FROM {$this->table} cc
                LEFT JOIN user_profiles u ON cc.created_by = u.id
                WHERE cc.id = ? AND cc.is_deleted = 0";
        
        $params = [$id];
        
        if ($clientId) {
            $sql .= " AND cc.client_id = ?";
            $params[] = $clientId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new category
     */
    public function createCategory($data) {
        $sql = "INSERT INTO {$this->table} 
                (client_id, name, description, sort_order, is_active, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['client_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
            $data['created_by']
        ]);
    }

    /**
     * Update category
     */
    public function updateCategory($id, $data) {
        $sql = "UPDATE {$this->table} 
                SET name = ?, description = ?, sort_order = ?, is_active = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
            $data['updated_by'],
            $id
        ]);
    }

    /**
     * Delete category (soft delete)
     */
    public function deleteCategory($id) {
        $sql = "UPDATE {$this->table} SET is_deleted = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Toggle category active status
     */
    public function toggleCategoryStatus($id) {
        $sql = "UPDATE {$this->table} SET is_active = NOT is_active WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Check if category name exists
     */
    public function checkCategoryNameExists($name, $clientId, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE name = ? AND client_id = ? AND is_deleted = 0";
        
        $params = [$name, $clientId];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get next sort order
     */
    public function getNextSortOrder($clientId) {
        $sql = "SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$this->table} 
                WHERE client_id = ? AND is_deleted = 0";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetchColumn();
    }

    /**
     * Search categories
     */
    public function searchCategories($searchTerm, $clientId = null, $includeInactive = false) {
        $sql = "SELECT cc.*, 
                       u.full_name as created_by_name,
                       (SELECT COUNT(*) FROM course_subcategories csc WHERE csc.category_id = cc.id AND csc.is_deleted = 0) as subcategory_count
                FROM {$this->table} cc
                LEFT JOIN user_profiles u ON cc.created_by = u.id
                WHERE cc.is_deleted = 0 
                AND (cc.name LIKE ? OR cc.description LIKE ?)";
        
        $params = ["%$searchTerm%", "%$searchTerm%"];
        
        if ($clientId) {
            $sql .= " AND cc.client_id = ?";
            $params[] = $clientId;
        }
        
        if (!$includeInactive) {
            $sql .= " AND cc.is_active = 1";
        }
        
        $sql .= " ORDER BY cc.sort_order ASC, cc.name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get categories with pagination
     */
    public function getCategoriesWithPagination($page = 1, $limit = 10, $clientId = null, $searchTerm = '', $includeInactive = false) {
        $offset = ($page - 1) * $limit;
        
        // Count total records
        $countSql = "SELECT COUNT(*) FROM {$this->table} cc WHERE cc.is_deleted = 0";
        $countParams = [];
        
        if ($clientId) {
            $countSql .= " AND cc.client_id = ?";
            $countParams[] = $clientId;
        }
        
        if (!$includeInactive) {
            $countSql .= " AND cc.is_active = 1";
        }
        
        if ($searchTerm) {
            $countSql .= " AND (cc.name LIKE ? OR cc.description LIKE ?)";
            $countParams[] = "%$searchTerm%";
            $countParams[] = "%$searchTerm%";
        }
        
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($countParams);
        $totalRecords = $countStmt->fetchColumn();
        
        // Get records
        $sql = "SELECT cc.*, 
                       u.full_name as created_by_name,
                       (SELECT COUNT(*) FROM course_subcategories csc WHERE csc.category_id = cc.id AND csc.is_deleted = 0) as subcategory_count
                FROM {$this->table} cc
                LEFT JOIN user_profiles u ON cc.created_by = u.id
                WHERE cc.is_deleted = 0";
        
        $params = [];
        
        if ($clientId) {
            $sql .= " AND cc.client_id = ?";
            $params[] = $clientId;
        }
        
        if (!$includeInactive) {
            $sql .= " AND cc.is_active = 1";
        }
        
        if ($searchTerm) {
            $sql .= " AND (cc.name LIKE ? OR cc.description LIKE ?)";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
        }
        
        $sql .= " ORDER BY cc.sort_order ASC, cc.name ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($sql);
        
        // Bind parameters with explicit types
        $paramIndex = 1;
        foreach ($params as $param) {
            if (is_int($param)) {
                $stmt->bindValue($paramIndex, $param, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($paramIndex, $param, PDO::PARAM_STR);
            }
            $paramIndex++;
        }
        
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'records' => $records,
            'total' => $totalRecords,
            'pages' => ceil($totalRecords / $limit),
            'current_page' => $page
        ];
    }

    /**
     * Get active categories for dropdown
     */
    public function getActiveCategoriesForDropdown($clientId = null) {
        $sql = "SELECT id, name FROM {$this->table} 
                WHERE is_active = 1 AND is_deleted = 0";
        
        $params = [];
        
        if ($clientId) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY sort_order ASC, name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 