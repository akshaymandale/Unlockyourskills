<?php
require_once 'config/Database.php';

class OrganizationModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get all organizations for super admin
     */
    public function getAllOrganizations($limit = 10, $offset = 0, $search = '', $filters = []) {
        $sql = "SELECT o.*, 
                       COUNT(up.id) as total_users,
                       COUNT(CASE WHEN up.user_status = 'Active' THEN 1 END) as active_users
                FROM organizations o
                LEFT JOIN user_profiles up ON o.id = up.organization_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (o.name LIKE ? OR o.client_code LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND o.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get organization by ID
     */
    public function getOrganizationById($id) {
        $sql = "SELECT * FROM organizations WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get organization by client code
     */
    public function getOrganizationByClientCode($clientCode) {
        $sql = "SELECT * FROM organizations WHERE client_code = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientCode]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new organization
     */
    public function createOrganization($data) {
        $sql = "INSERT INTO organizations (
                    name, slug, client_code, logo_path, primary_color, 
                    secondary_color, accent_color, max_users, status, 
                    subscription_plan, subscription_expires_at,
                    sso_enabled, sso_provider, sso_client_id, sso_client_secret, sso_redirect_url,
                    features_scorm, features_assessments, features_surveys, features_feedback, features_analytics
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['client_code'],
            $data['logo_path'] ?? null,
            $data['primary_color'] ?? '#6f42c1',
            $data['secondary_color'] ?? '#495057',
            $data['accent_color'] ?? '#28a745',
            $data['max_users'] ?? 10,
            $data['status'] ?? 'active',
            $data['subscription_plan'] ?? 'basic',
            $data['subscription_expires_at'] ?? null,
            $data['sso_enabled'] ?? false,
            $data['sso_provider'] ?? null,
            $data['sso_client_id'] ?? null,
            $data['sso_client_secret'] ?? null,
            $data['sso_redirect_url'] ?? null,
            $data['features_scorm'] ?? true,
            $data['features_assessments'] ?? true,
            $data['features_surveys'] ?? true,
            $data['features_feedback'] ?? true,
            $data['features_analytics'] ?? true
        ]);
    }

    /**
     * Update organization
     */
    public function updateOrganization($id, $data) {
        $sql = "UPDATE organizations SET 
                    name = ?, slug = ?, client_code = ?, logo_path = ?, 
                    primary_color = ?, secondary_color = ?, accent_color = ?, 
                    max_users = ?, status = ?, subscription_plan = ?, subscription_expires_at = ?,
                    sso_enabled = ?, sso_provider = ?, sso_client_id = ?, sso_client_secret = ?, sso_redirect_url = ?,
                    features_scorm = ?, features_assessments = ?, features_surveys = ?, features_feedback = ?, features_analytics = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['client_code'],
            $data['logo_path'],
            $data['primary_color'],
            $data['secondary_color'],
            $data['accent_color'],
            $data['max_users'],
            $data['status'],
            $data['subscription_plan'],
            $data['subscription_expires_at'],
            $data['sso_enabled'],
            $data['sso_provider'],
            $data['sso_client_id'],
            $data['sso_client_secret'],
            $data['sso_redirect_url'],
            $data['features_scorm'],
            $data['features_assessments'],
            $data['features_surveys'],
            $data['features_feedback'],
            $data['features_analytics'],
            $id
        ]);
    }

    /**
     * Delete organization
     */
    public function deleteOrganization($id) {
        $sql = "DELETE FROM organizations WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Get organization settings
     */
    public function getOrganizationSettings($organizationId) {
        $sql = "SELECT setting_key, setting_value, setting_type FROM organization_settings WHERE organization_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$organizationId]);
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $value = $row['setting_value'];
            
            // Convert value based on type
            switch ($row['setting_type']) {
                case 'number':
                    $value = (float) $value;
                    break;
                case 'boolean':
                    $value = (bool) $value;
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
            }
            
            $settings[$row['setting_key']] = $value;
        }
        
        return $settings;
    }

    /**
     * Update organization setting
     */
    public function updateOrganizationSetting($organizationId, $key, $value, $type = 'string') {
        $sql = "INSERT INTO organization_settings (organization_id, setting_key, setting_value, setting_type) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?, setting_type = ?, updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$organizationId, $key, $value, $type, $value, $type]);
    }

    /**
     * Check if organization can add more users
     */
    public function canAddUser($organizationId) {
        $sql = "SELECT max_users, current_user_count FROM organizations WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$organizationId]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$org) return false;
        
        return $org['current_user_count'] < $org['max_users'];
    }

    /**
     * Update user count for organization
     */
    public function updateUserCount($organizationId) {
        $sql = "UPDATE organizations SET current_user_count = (
                    SELECT COUNT(*) FROM user_profiles 
                    WHERE organization_id = ? AND user_status = 'Active'
                ) WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$organizationId, $organizationId]);
    }

    /**
     * Get organization statistics
     */
    public function getOrganizationStats($organizationId) {
        $sql = "SELECT 
                    COUNT(CASE WHEN up.user_status = 'Active' THEN 1 END) as active_users,
                    COUNT(CASE WHEN up.user_status = 'Inactive' THEN 1 END) as inactive_users,
                    COUNT(CASE WHEN up.locked_status = 'Locked' THEN 1 END) as locked_users,
                    COUNT(CASE WHEN up.system_role = 'admin' THEN 1 END) as admin_users,
                    o.max_users,
                    o.current_user_count
                FROM organizations o
                LEFT JOIN user_profiles up ON o.id = up.organization_id
                WHERE o.id = ?
                GROUP BY o.id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$organizationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
