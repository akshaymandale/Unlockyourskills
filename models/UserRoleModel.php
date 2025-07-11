<?php
require_once 'config/Database.php';

class UserRoleModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Get all roles
     */
    public function getAllRoles() {
        try {
            $sql = "SELECT * FROM user_roles WHERE is_active = 1 ORDER BY display_order ASC, role_name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllRoles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get role by ID
     */
    public function getRoleById($id) {
        try {
            $sql = "SELECT * FROM user_roles WHERE id = ? AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getRoleById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get role by name
     */
    public function getRoleByName($roleName) {
        try {
            $sql = "SELECT * FROM user_roles WHERE role_name = ? AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleName]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getRoleByName: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new role
     */
    public function createRole($data) {
        try {
            $sql = "INSERT INTO user_roles (role_name, system_role, description, display_order, is_active, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, 1, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['role_name'],
                $data['system_role'],
                $data['description'],
                $data['display_order']
            ]);
        } catch (PDOException $e) {
            error_log("Error in createRole: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update role
     */
    public function updateRole($id, $data) {
        try {
            $sql = "UPDATE user_roles SET 
                    role_name = ?, 
                    system_role = ?, 
                    description = ?, 
                    display_order = ?, 
                    updated_at = NOW() 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['role_name'],
                $data['system_role'],
                $data['description'],
                $data['display_order'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error in updateRole: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Activate role
     */
    public function activateRole($id) {
        try {
            $sql = "UPDATE user_roles SET is_active = 1, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error in activateRole: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deactivate role
     */
    public function deactivateRole($id) {
        try {
            $sql = "UPDATE user_roles SET is_active = 0, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error in deactivateRole: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get system roles (for dropdown)
     */
    public function getSystemRoles() {
        return [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'manager' => 'Manager',
            'instructor' => 'Instructor',
            'learner' => 'Learner',
            'guest' => 'Guest'
        ];
    }

    /**
     * Save module permissions
     */
    public function saveModulePermissions($permissions) {
        try {
            // First, delete existing permissions
            $sql = "DELETE FROM role_permissions";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            // Insert new permissions
            $sql = "INSERT INTO role_permissions (role_id, module_name, can_access, can_create, can_edit, can_delete, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);

            foreach ($permissions as $roleId => $modules) {
                foreach ($modules as $moduleName => $permission) {
                    $stmt->execute([
                        $roleId,
                        $moduleName,
                        isset($permission['access']) ? 1 : 0,
                        isset($permission['create']) ? 1 : 0,
                        isset($permission['edit']) ? 1 : 0,
                        isset($permission['delete']) ? 1 : 0
                    ]);
                }
            }
            return true;
        } catch (PDOException $e) {
            error_log("Error in saveModulePermissions: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get permissions for a role
     */
    public function getRolePermissions($roleId) {
        try {
            $sql = "SELECT * FROM role_permissions WHERE role_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getRolePermissions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all permissions (for matrix display)
     */
    public function getAllPermissions() {
        try {
            $sql = "SELECT rp.*, ur.role_name 
                    FROM role_permissions rp 
                    JOIN user_roles ur ON rp.role_id = ur.id 
                    WHERE ur.is_active = 1 
                    ORDER BY ur.display_order, ur.role_name, rp.module_name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllPermissions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user has permission for a module
     */
    public function hasPermission($userId, $moduleName, $permission = 'access') {
        try {
            $sql = "SELECT rp.can_access, rp.can_create, rp.can_edit, rp.can_delete 
                    FROM role_permissions rp 
                    JOIN user_roles ur ON rp.role_id = ur.id 
                    JOIN user_profiles up ON up.user_role = ur.role_name 
                    WHERE up.id = ? AND rp.module_name = ? AND ur.is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $moduleName]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return false;
            }

            switch ($permission) {
                case 'access':
                    return (bool)$result['can_access'];
                case 'create':
                    return (bool)$result['can_create'];
                case 'edit':
                    return (bool)$result['can_edit'];
                case 'delete':
                    return (bool)$result['can_delete'];
                default:
                    return false;
            }
        } catch (PDOException $e) {
            error_log("Error in hasPermission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get roles count
     */
    public function getRolesCount() {
        try {
            $sql = "SELECT COUNT(*) as count FROM user_roles WHERE is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error in getRolesCount: " . $e->getMessage());
            return 0;
        }
    }
}
?>
