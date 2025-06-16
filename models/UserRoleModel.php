<?php
require_once 'config/Database.php';

class UserRoleModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get all active user roles
     */
    public function getAllRoles() {
        $sql = "SELECT * FROM user_roles WHERE is_active = 1 ORDER BY display_order ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get roles by system role
     */
    public function getRolesBySystemRole($systemRole) {
        $sql = "SELECT * FROM user_roles WHERE system_role = ? AND is_active = 1 ORDER BY display_order ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$systemRole]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get role by name
     */
    public function getRoleByName($roleName) {
        $sql = "SELECT * FROM user_roles WHERE role_name = ? AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$roleName]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get system role for a user role
     */
    public function getSystemRoleForUserRole($userRole) {
        $sql = "SELECT system_role FROM user_roles WHERE role_name = ? AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userRole]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['system_role'] : 'user';
    }

    /**
     * Check if role exists and is active
     */
    public function isValidRole($roleName) {
        $sql = "SELECT COUNT(*) as count FROM user_roles WHERE role_name = ? AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$roleName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Get roles excluding super admin (for regular client use)
     */
    public function getClientRoles() {
        $sql = "SELECT * FROM user_roles WHERE system_role != 'super_admin' AND is_active = 1 ORDER BY display_order ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get admin roles only
     */
    public function getAdminRoles() {
        $sql = "SELECT * FROM user_roles WHERE system_role = 'admin' AND is_active = 1 ORDER BY display_order ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create new role
     */
    public function createRole($data) {
        $sql = "INSERT INTO user_roles (role_name, system_role, description, display_order) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['role_name'],
            $data['system_role'],
            $data['description'] ?? '',
            $data['display_order'] ?? 999
        ]);
    }

    /**
     * Update role
     */
    public function updateRole($id, $data) {
        $sql = "UPDATE user_roles SET role_name = ?, system_role = ?, description = ?, display_order = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['role_name'],
            $data['system_role'],
            $data['description'] ?? '',
            $data['display_order'] ?? 999,
            $id
        ]);
    }

    /**
     * Deactivate role (soft delete)
     */
    public function deactivateRole($id) {
        $sql = "UPDATE user_roles SET is_active = 0, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Activate role
     */
    public function activateRole($id) {
        $sql = "UPDATE user_roles SET is_active = 1, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Get role by ID
     */
    public function getRoleById($id) {
        $sql = "SELECT * FROM user_roles WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if role name is unique
     */
    public function isRoleNameUnique($roleName, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM user_roles WHERE role_name = ?";
        $params = [$roleName];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] == 0;
    }
}
?>
