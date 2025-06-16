<?php
require_once 'config/Database.php';

class AuthenticationModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Validate client code exists and is active
     */
    public function validateClientCode($clientCode) {
        $sql = "SELECT id, client_name, status, sso_enabled FROM clients WHERE client_code = ? AND is_deleted = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientCode]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            return ['valid' => false, 'message' => 'Invalid client code'];
        }
        
        if ($client['status'] !== 'active') {
            return ['valid' => false, 'message' => 'Client account is not active'];
        }
        
        return ['valid' => true, 'client' => $client];
    }

    /**
     * Authenticate user with email/profile_id and password
     */
    public function authenticateUser($clientCode, $username, $password) {
        // First validate client code
        $clientValidation = $this->validateClientCode($clientCode);
        if (!$clientValidation['valid']) {
            return $clientValidation;
        }
        
        $client = $clientValidation['client'];
        
        // Find user by email or profile_id
        $sql = "SELECT up.*, c.client_name, c.max_users, c.current_user_count, c.sso_enabled
                FROM user_profiles up
                LEFT JOIN clients c ON up.client_id = c.id
                WHERE c.client_code = ? 
                AND (up.email = ? OR up.profile_id = ?) 
                AND up.is_deleted = 0";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientCode, $username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['valid' => false, 'message' => 'Invalid credentials'];
        }
        
        // Check if user is active
        if ($user['user_status'] !== 'Active') {
            return ['valid' => false, 'message' => 'User account is not active'];
        }
        
        // Check if user is locked
        if ($user['locked_status'] == 1) {
            return ['valid' => false, 'message' => 'User account is locked'];
        }
        
        // Verify password
        if ($password !== $user['current_password']) {
            return ['valid' => false, 'message' => 'Invalid credentials'];
        }
        
        return ['valid' => true, 'user' => $user];
    }

    /**
     * Get SSO configuration for client
     */
    public function getClientSSOConfig($clientId) {
        $sql = "SELECT csc.*, sp.provider_name, sp.provider_type
                FROM client_sso_configurations csc
                JOIN sso_providers sp ON csc.sso_provider_id = sp.id
                WHERE csc.client_id = ? AND csc.is_enabled = 1 AND sp.is_active = 1
                ORDER BY sp.provider_name";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if SSO is enabled for client
     */
    public function isSSOEnabled($clientCode) {
        $sql = "SELECT c.id, c.sso_enabled, COUNT(csc.id) as sso_configs
                FROM clients c
                LEFT JOIN client_sso_configurations csc ON c.id = csc.client_id AND csc.is_enabled = 1
                WHERE c.client_code = ? AND c.is_deleted = 0
                GROUP BY c.id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result && $result['sso_enabled'] == 1 && $result['sso_configs'] > 0;
    }

    /**
     * Create SSO session
     */
    public function createSSOSession($userId, $clientId, $ssoProviderId, $externalUserId, $ssoSessionId, $userAttributes = []) {
        $sessionId = session_id();
        
        $sql = "INSERT INTO sso_sessions (session_id, user_id, client_id, sso_provider_id, external_user_id, sso_session_id, user_attributes)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $sessionId,
            $userId,
            $clientId,
            $ssoProviderId,
            $externalUserId,
            $ssoSessionId,
            json_encode($userAttributes)
        ]);
    }

    /**
     * Update SSO session activity
     */
    public function updateSSOSessionActivity($sessionId) {
        $sql = "UPDATE sso_sessions SET last_activity = NOW() WHERE session_id = ? AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$sessionId]);
    }

    /**
     * End SSO session
     */
    public function endSSOSession($sessionId) {
        $sql = "UPDATE sso_sessions SET logout_time = NOW(), is_active = 0 WHERE session_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$sessionId]);
    }

    /**
     * Get active SSO session
     */
    public function getActiveSSOSession($sessionId) {
        $sql = "SELECT ss.*, sp.provider_name, sp.provider_type
                FROM sso_sessions ss
                JOIN sso_providers sp ON ss.sso_provider_id = sp.id
                WHERE ss.session_id = ? AND ss.is_active = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$sessionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Log authentication attempt
     */
    public function logAuthenticationAttempt($clientCode, $username, $success, $message = '', $ipAddress = '') {
        $sql = "INSERT INTO authentication_logs (client_code, username, success, message, ip_address, attempt_time)
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientCode, $username, $success ? 1 : 0, $message, $ipAddress]);
        } catch (Exception $e) {
            // Log table might not exist, create it
            $this->createAuthenticationLogsTable();
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientCode, $username, $success ? 1 : 0, $message, $ipAddress]);
        }
    }

    /**
     * Create authentication logs table if it doesn't exist
     */
    private function createAuthenticationLogsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS authentication_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_code VARCHAR(50),
            username VARCHAR(255),
            success TINYINT(1) NOT NULL DEFAULT 0,
            message TEXT,
            ip_address VARCHAR(45),
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_client_username (client_code, username),
            INDEX idx_attempt_time (attempt_time)
        )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
    }
}
?>
