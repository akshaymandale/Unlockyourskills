<?php
require_once 'config/Database.php';

class UserRoleModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Get all roles for a client
     */
    public function getAllRoles($clientId) {
        try {
            $sql = "SELECT * FROM user_roles WHERE is_active = 1 AND client_id = ? ORDER BY display_order ASC, role_name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllRoles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get role by ID for a client
     */
    public function getRoleById($id, $clientId) {
        try {
            $sql = "SELECT * FROM user_roles WHERE id = ? AND client_id = ? AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id, $clientId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getRoleById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get role by name for a client
     */
    public function getRoleByName($roleName, $clientId) {
        try {
            $sql = "SELECT * FROM user_roles WHERE role_name = ? AND client_id = ? AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleName, $clientId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getRoleByName: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create new role for a client
     */
    public function createRole($data, $clientId) {
        error_log("[createRole] TEST LOG ENTRY");
        try {
            error_log("[createRole] Attempting to insert: " . json_encode(['client_id' => $clientId, 'data' => $data]));
            $sql = "INSERT INTO user_roles (client_id, role_name, system_role, description, display_order, is_active, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $clientId,
                $data['role_name'],
                $data['system_role'],
                $data['description'],
                $data['display_order']
            ]);
            error_log("[createRole] Insert result: " . var_export($result, true));
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("[createRole] SQLSTATE: " . $errorInfo[0] . " | Error: " . $errorInfo[2]);
            }
            return $result;
        } catch (PDOException $e) {
            error_log("[createRole] Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update role for a client
     */
    public function updateRole($id, $data, $clientId) {
        try {
            $sql = "UPDATE user_roles SET 
                    role_name = ?, 
                    system_role = ?, 
                    description = ?, 
                    display_order = ?, 
                    updated_at = NOW() 
                    WHERE id = ? AND client_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['role_name'],
                $data['system_role'],
                $data['description'],
                $data['display_order'],
                $id,
                $clientId
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
     * Get system role for user role
     */
    public function getSystemRoleForUserRole($userRole) {
        // Reverse mapping from display name to system role
        $roleMapping = [
            'Super Admin' => 'super_admin',
            'Admin' => 'admin',
            'Manager' => 'manager',
            'Instructor' => 'instructor',
            'Learner' => 'learner',
            'User' => 'user',
            'Guest' => 'guest'
        ];
        return $roleMapping[$userRole] ?? 'user'; // Default to 'user' if not found
    }

    /**
     * Save module permissions for a client
     */
    public function saveModulePermissions($permissions, $clientId) {
        try {
            // First, delete existing permissions for this client
            $sql = "DELETE FROM role_permissions WHERE client_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$clientId]);

            // Insert new permissions
            $sql = "INSERT INTO role_permissions (client_id, role_id, module_name, can_access, can_create, can_edit, can_delete, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);

            foreach ($permissions as $roleId => $modules) {
                foreach ($modules as $moduleName => $permission) {
                    $stmt->execute([
                        $clientId,
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
     * Get permissions for a role for a client
     */
    public function getRolePermissions($roleId, $clientId) {
        try {
            $sql = "SELECT * FROM role_permissions WHERE role_id = ? AND client_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId, $clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getRolePermissions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all permissions for a client (for matrix display)
     */
    public function getAllPermissions($clientId) {
        try {
            $sql = "SELECT rp.*, ur.role_name 
                    FROM role_permissions rp 
                    JOIN user_roles ur ON rp.role_id = ur.id 
                    WHERE ur.is_active = 1 AND rp.client_id = ? 
                    ORDER BY ur.display_order, ur.role_name, rp.module_name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllPermissions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user has permission for a module (client-specific)
     */
    public function hasPermission($userId, $moduleName, $clientId, $permission = 'access') {
        try {
            $sql = "SELECT rp.can_access, rp.can_create, rp.can_edit, rp.can_delete 
                    FROM role_permissions rp 
                    JOIN user_roles ur ON rp.role_id = ur.id 
                    JOIN user_profiles up ON up.user_role = ur.role_name AND up.client_id = ur.client_id 
                    WHERE up.id = ? AND rp.module_name = ? AND ur.is_active = 1 AND rp.client_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $moduleName, $clientId]);
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

    public function getClientRoles($clientId) {
        try {
            $sql = "SELECT * FROM user_roles WHERE client_id = ? AND is_active = 1 AND system_role != 'super_admin' ORDER BY display_order ASC, role_name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error in getClientRoles: ' . $e->getMessage());
            return [];
        }
    }

    public function getAdminRoles($clientId) {
        try {
            $sql = "SELECT * FROM user_roles WHERE client_id = ? AND is_active = 1 AND system_role = 'admin' ORDER BY display_order ASC, role_name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error in getAdminRoles: ' . $e->getMessage());
            return [];
        }
    }
}
?>
