<?php

class CourseSubcategoryModel {
    private $conn;
    private $table = 'course_subcategories';

    public function __construct($db = null) {
        $this->conn = $db ?: (new Database())->connect();
    }

    /**
     * Get all course subcategories for a client
     */
    public function getAllSubcategories($clientId = null, $includeInactive = false) {
        $sql = "SELECT csc.*, 
                       cc.name as category_name,
                       u.full_name as created_by_name
                FROM {$this->table} csc
                LEFT JOIN course_categories cc ON csc.category_id = cc.id
                LEFT JOIN user_profiles u ON csc.created_by = u.id
                WHERE csc.is_deleted = 0";
        
        $params = [];
        
        if ($clientId) {
            $sql .= " AND csc.client_id = ?";
            $params[] = $clientId;
        }
        
        if (!$includeInactive) {
            $sql .= " AND csc.is_active = 1";
        }
        
        $sql .= " ORDER BY cc.sort_order ASC, cc.name ASC, csc.sort_order ASC, csc.name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get subcategory by ID
     */
    public function getSubcategoryById($id, $clientId = null) {
        $sql = "SELECT csc.*, 
                       cc.name as category_name,
                       u.full_name as created_by_name
                FROM {$this->table} csc
                LEFT JOIN course_categories cc ON csc.category_id = cc.id
                LEFT JOIN user_profiles u ON csc.created_by = u.id
                WHERE csc.id = ? AND csc.is_deleted = 0";
        
        $params = [$id];
        
        if ($clientId) {
            $sql .= " AND csc.client_id = ?";
            $params[] = $clientId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new subcategory
     */
    public function createSubcategory($data) {
        $sql = "INSERT INTO {$this->table} 
                (client_id, category_id, name, description, sort_order, is_active, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['client_id'],
            $data['category_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
            $data['created_by']
        ]);
    }

    /**
     * Update subcategory
     */
    public function updateSubcategory($id, $data) {
        $sql = "UPDATE {$this->table} 
                SET category_id = ?, name = ?, description = ?, sort_order = ?, is_active = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['category_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
            $data['updated_by'],
            $id
        ]);
    }

    /**
     * Delete subcategory (soft delete)
     */
    public function deleteSubcategory($id) {
        $sql = "UPDATE {$this->table} SET is_deleted = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Toggle subcategory active status
     */
    public function toggleSubcategoryStatus($id) {
        $sql = "UPDATE {$this->table} SET is_active = NOT is_active WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Check if subcategory name exists within the same category
     */
    public function checkSubcategoryNameExists($name, $categoryId, $clientId, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE name = ? AND category_id = ? AND client_id = ? AND is_deleted = 0";
        
        $params = [$name, $categoryId, $clientId];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get next sort order for a category
     */
    public function getNextSortOrder($categoryId, $clientId) {
        $sql = "SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$this->table} 
                WHERE category_id = ? AND client_id = ? AND is_deleted = 0";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$categoryId, $clientId]);
        return $stmt->fetchColumn();
    }

    /**
     * Search subcategories
     */
    public function searchSubcategories($searchTerm, $clientId = null, $includeInactive = false) {
        $sql = "SELECT csc.*, 
                       cc.name as category_name,
                       u.full_name as created_by_name
                FROM {$this->table} csc
                LEFT JOIN course_categories cc ON csc.category_id = cc.id
                LEFT JOIN user_profiles u ON csc.created_by = u.id
                WHERE csc.is_deleted = 0 
                AND (csc.name LIKE ? OR csc.description LIKE ? OR cc.name LIKE ?)";
        
        $params = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%"];
        
        if ($clientId) {
            $sql .= " AND csc.client_id = ?";
            $params[] = $clientId;
        }
        
        if (!$includeInactive) {
            $sql .= " AND csc.is_active = 1";
        }
        
        $sql .= " ORDER BY cc.sort_order ASC, cc.name ASC, csc.sort_order ASC, csc.name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get subcategories with pagination
     */
    public function getSubcategoriesWithPagination($page, $limit, $clientId, $searchTerm = '', $includeInactive = false) {
        $offset = ($page - 1) * $limit;
        
        // Count total records
        $countSql = "SELECT COUNT(*) FROM {$this->table} csc 
                     LEFT JOIN course_categories cc ON csc.category_id = cc.id 
                     WHERE csc.is_deleted = 0";
        $countParams = [];
        
        if ($clientId) {
            $countSql .= " AND csc.client_id = ?";
            $countParams[] = $clientId;
        }
        
        if (!$includeInactive) {
            $countSql .= " AND csc.is_active = 1";
        }
        
        if ($searchTerm) {
            $countSql .= " AND (csc.name LIKE ? OR csc.description LIKE ? OR cc.name LIKE ?)";
            $countParams[] = "%$searchTerm%";
            $countParams[] = "%$searchTerm%";
            $countParams[] = "%$searchTerm%";
        }
        
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($countParams);
        $totalRecords = $countStmt->fetchColumn();
        
        // Get records
        $sql = "SELECT csc.*, 
                       cc.name as category_name,
                       u.full_name as created_by_name
                FROM {$this->table} csc
                LEFT JOIN course_categories cc ON csc.category_id = cc.id
                LEFT JOIN user_profiles u ON csc.created_by = u.id
                WHERE csc.is_deleted = 0";
        
        $params = [];
        
        if ($clientId) {
            $sql .= " AND csc.client_id = ?";
            $params[] = $clientId;
        }
        
        if (!$includeInactive) {
            $sql .= " AND csc.is_active = 1";
        }
        
        if ($searchTerm) {
            $sql .= " AND (csc.name LIKE ? OR csc.description LIKE ? OR cc.name LIKE ?)";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
        }
        
        $sql .= " ORDER BY cc.sort_order ASC, cc.name ASC, csc.sort_order ASC, csc.name ASC LIMIT ? OFFSET ?";
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
     * Get active subcategories for dropdown
     */
    public function getActiveSubcategoriesForDropdown($clientId = null) {
        $sql = "SELECT csc.id, csc.name, cc.name as category_name 
                FROM {$this->table} csc
                LEFT JOIN course_categories cc ON csc.category_id = cc.id
                WHERE csc.is_active = 1 AND csc.is_deleted = 0";
        
        $params = [];
        
        if ($clientId) {
            $sql .= " AND csc.client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY cc.sort_order ASC, cc.name ASC, csc.sort_order ASC, csc.name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get subcategories by category ID
     */
    public function getSubcategoriesByCategoryId($categoryId, $clientId = null, $includeInactive = false) {
        $sql = "SELECT csc.*, 
                       cc.name as category_name,
                       u.full_name as created_by_name
                FROM {$this->table} csc
                LEFT JOIN course_categories cc ON csc.category_id = cc.id
                LEFT JOIN user_profiles u ON csc.created_by = u.id
                WHERE csc.category_id = ? AND csc.is_deleted = 0";
        
        $params = [$categoryId];
        
        if ($clientId) {
            $sql .= " AND csc.client_id = ?";
            $params[] = $clientId;
        }
        
        if (!$includeInactive) {
            $sql .= " AND csc.is_active = 1";
        }
        
        $sql .= " ORDER BY csc.sort_order ASC, csc.name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 