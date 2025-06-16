<?php
require_once 'config/Database.php';

class ClientModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get all clients with pagination and search
     */
    public function getAllClients($limit = 10, $offset = 0, $search = '', $filters = []) {
        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM user_profiles up WHERE up.client_id = c.id AND up.user_status = 'Active') as active_users
                FROM clients c
                WHERE c.is_deleted = 0";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND c.client_name LIKE ?";
            $params[] = "%$search%";
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY c.id DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $index => $param) {
            $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get client by ID
     */
    public function getClientById($id) {
        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM user_profiles up WHERE up.client_id = c.id AND up.user_status = 'Active') as active_users
                FROM clients c
                WHERE c.id = ? AND c.is_deleted = 0";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new client
     */
    public function createClient($data) {
        $sql = "INSERT INTO clients (
                    client_name, client_code, logo_path, max_users, status, description,
                    reports_enabled, theme_settings, sso_enabled, admin_role_limit, custom_field_creation
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['client_name'],
            $data['client_code'],
            $data['logo_path'] ?? null,
            $data['max_users'] ?? 10,
            $data['status'] ?? 'active',
            $data['description'] ?? '',
            $data['reports_enabled'] ?? 1,
            $data['theme_settings'] ?? 1,
            $data['sso_enabled'] ?? 0,
            $data['admin_role_limit'] ?? 5,
            $data['custom_field_creation'] ?? 1
        ]);
    }

    /**
     * Update client
     */
    public function updateClient($id, $data) {
        $sql = "UPDATE clients SET
                    client_name = ?,
                    client_code = ?,
                    logo_path = ?,
                    max_users = ?,
                    status = ?,
                    description = ?,
                    reports_enabled = ?,
                    theme_settings = ?,
                    sso_enabled = ?,
                    admin_role_limit = ?,
                    custom_field_creation = ?,
                    updated_at = NOW()
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['client_name'],
            $data['client_code'],
            $data['logo_path'],
            $data['max_users'],
            $data['status'],
            $data['description'] ?? '',
            $data['reports_enabled'] ?? 1,
            $data['theme_settings'] ?? 1,
            $data['sso_enabled'] ?? 0,
            $data['admin_role_limit'] ?? 1,
            $data['custom_field_creation'] ?? 1,
            $id
        ]);
    }



    /**
     * Soft delete client
     */
    public function deleteClient($id) {
        // First check if client has users
        $userCheck = $this->conn->prepare("SELECT COUNT(*) as count FROM user_profiles WHERE client_id = ? AND user_status = 'Active'");
        $userCheck->execute([$id]);
        $userCount = $userCheck->fetch(PDO::FETCH_ASSOC);

        if ($userCount['count'] > 0) {
            return false; // Cannot delete client with active users
        }

        // Soft delete by setting is_deleted = 1
        $sql = "UPDATE clients SET is_deleted = 1, updated_at = NOW() WHERE id = ? AND is_deleted = 0";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Update client user count
     */
    public function updateUserCount($clientId) {
        $sql = "UPDATE clients 
                SET current_user_count = (
                    SELECT COUNT(*) FROM user_profiles 
                    WHERE client_id = ? AND user_status = 'Active'
                )
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$clientId, $clientId]);
    }

    /**
     * Check if client can add more users
     */
    public function canAddUser($clientId) {
        $sql = "SELECT max_users, current_user_count FROM clients WHERE id = ? AND is_deleted = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$client) return false;

        return $client['current_user_count'] < $client['max_users'];
    }

    /**
     * Get client statistics
     */
    public function getClientStats($clientId) {
        $sql = "SELECT
                    c.*,
                    (SELECT COUNT(*) FROM user_profiles WHERE client_id = c.id AND user_status = 'Active') as active_users,
                    (SELECT COUNT(*) FROM scorm_packages WHERE client_id = c.id) as scorm_packages,
                    (SELECT COUNT(*) FROM assessment_package WHERE client_id = c.id) as assessments,
                    (SELECT COUNT(*) FROM survey_package WHERE client_id = c.id) as surveys
                FROM clients c
                WHERE c.id = ? AND c.is_deleted = 0";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Restore deleted client (optional functionality)
     */
    public function restoreClient($id) {
        $sql = "UPDATE clients SET is_deleted = 0, updated_at = NOW() WHERE id = ? AND is_deleted = 1";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Get deleted clients (for admin purposes)
     */
    public function getDeletedClients($limit = 10, $offset = 0) {
        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM user_profiles up WHERE up.client_id = c.id AND up.user_status = 'Active') as active_users
                FROM clients c
                WHERE c.is_deleted = 1
                ORDER BY c.updated_at DESC LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Permanently delete client (hard delete)
     */
    public function permanentlyDeleteClient($id) {
        // First check if client has users
        $userCheck = $this->conn->prepare("SELECT COUNT(*) as count FROM user_profiles WHERE client_id = ?");
        $userCheck->execute([$id]);
        $userCount = $userCheck->fetch(PDO::FETCH_ASSOC);

        if ($userCount['count'] > 0) {
            return false; // Cannot permanently delete client with users
        }

        // Hard delete from database
        $sql = "DELETE FROM clients WHERE id = ? AND is_deleted = 1";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Check if client code is unique
     */
    public function isClientCodeUnique($clientCode, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM clients WHERE client_code = ? AND is_deleted = 0";
        $params = [$clientCode];

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
