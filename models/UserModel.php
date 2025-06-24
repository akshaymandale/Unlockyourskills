<?php
require_once 'config/Database.php';
require_once 'models/UserRoleModel.php';

class UserModel {
    private $conn;
    private $userRoleModel;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
        $this->userRoleModel = new UserRoleModel();
    }

    public function getUser($client_code, $username) {
        $stmt = $this->conn->prepare("SELECT up.*, c.client_name, c.client_code, c.max_users, c.current_user_count, c.sso_enabled
                                     FROM user_profiles up
                                     LEFT JOIN clients c ON up.client_id = c.id
                                     WHERE c.client_code = ? AND (up.email = ? OR up.profile_id = ?) AND up.is_deleted = 0");
        $stmt->execute([$client_code, $username, $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function insertUser($postData, $fileData) {
        if (!is_array($postData)) {
            die("Error: insertUser expects an array but received " . gettype($postData));
        }
    
        // Extract values safely, setting NULL if empty
        $client_id = $postData['client_id'] ?? null;
        $profile_id = $postData['profile_id'] ?? null;
        $full_name = $postData['full_name'] ?? null;
        $email = $postData['email'] ?? null;
        $contact_number = $postData['contact_number'] ?? null;
        $gender = !empty($postData['gender']) ? $postData['gender'] : null;
        $dob = !empty($postData['dob']) ? $postData['dob'] : null;
        $user_role = $postData['user_role'] ?? null;
        $profile_expiry = !empty($postData['profile_expiry']) ? $postData['profile_expiry'] : null;
        $user_status = $postData['user_status'] ?? null;
        $locked_status = $postData['locked_status'] ?? null;
        $leaderboard = $postData['leaderboard'] ?? null;
        $country = $postData['country'] ?? null;
        $state = $postData['state'] ?? null;
        $city = $postData['city'] ?? null;
        $timezone = $postData['timezone'] ?? null;
        $language = $postData['language'] ?? null;
        $reports_to = $postData['reports_to'] ?? null;
        $joining_date = !empty($postData['joining_date']) ? $postData['joining_date'] : null;
        $retirement_date = !empty($postData['retirement_date']) ? $postData['retirement_date'] : null;
    
        // Extract custom field values
        $customFieldValues = [];
        foreach ($postData as $key => $value) {
            if (strpos($key, 'custom_field_') === 0) {
                $fieldId = str_replace('custom_field_', '', $key);
                if (is_array($value)) {
                    // Handle checkbox arrays
                    $customFieldValues[$fieldId] = implode(',', $value);
                } else {
                    $customFieldValues[$fieldId] = !empty($value) ? $value : null;
                }
            }
        }
    
        // Handling Profile Picture Upload
        $profile_picture = null;
        if (!empty($fileData['profile_picture']['name'])) {
            $uploadDir = "uploads/profile_pictures/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($fileData['profile_picture']['name'], PATHINFO_EXTENSION);
            $profile_picture = $uploadDir . uniqid('profile_', true) . '.' . $ext;
            if (!move_uploaded_file($fileData['profile_picture']['tmp_name'], $profile_picture)) {
                die("Error: Failed to upload profile picture.");
            }
        }
    
        // Validate client_id exists
        $clientStmt = $this->conn->prepare("SELECT id FROM clients WHERE id = ?");
        $clientStmt->execute([$client_id]);
        $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
        if (!$client) {
            throw new Exception("Invalid client ID");
        }

        // Get current user ID for audit fields
        $currentUserId = $_SESSION['user']['id'] ?? null;

        // Use target_client_id if provided (for super admin), otherwise use client_id
        $finalClientId = $data['target_client_id'] ?? $client_id;

        // Insert into Database (with audit fields)
        $sql = "INSERT INTO user_profiles
            (client_id, profile_id, full_name, email, contact_number, gender, dob, user_role, system_role, profile_expiry, user_status, locked_status, leaderboard, profile_picture, country, state, city, timezone, language, reports_to, joining_date, retirement_date, created_by, updated_by)
            VALUES
            (:client_id, :profile_id, :full_name, :email, :contact_number, :gender, :dob, :user_role, :system_role, :profile_expiry, :user_status, :locked_status, :leaderboard, :profile_picture, :country, :state, :city, :timezone, :language, :reports_to, :joining_date, :retirement_date, :created_by, :updated_by)";
    
        $stmt = $this->conn->prepare($sql);

        // Determine system_role based on user_role using UserRoleModel
        $system_role = $this->userRoleModel->getSystemRoleForUserRole($user_role);

        try {
            $result = $stmt->execute([
                ':client_id' => $finalClientId,
                ':profile_id' => $profile_id,
                ':full_name' => $full_name,
                ':email' => $email,
                ':contact_number' => $contact_number,
                ':gender' => $gender,
                ':dob' => $dob,
                ':user_role' => $user_role,
                ':system_role' => $system_role,
                ':profile_expiry' => $profile_expiry,
                ':user_status' => $user_status,
                ':locked_status' => $locked_status,
                ':leaderboard' => $leaderboard,
                ':profile_picture' => $profile_picture,
                ':country' => $country,
                ':state' => $state,
                ':city' => $city,
                ':timezone' => $timezone,
                ':language' => $language,
                ':reports_to' => $reports_to,
                ':joining_date' => $joining_date,
                ':retirement_date' => $retirement_date,
                ':created_by' => $currentUserId,
                ':updated_by' => $currentUserId
            ]);

            if ($result) {
                // Get the inserted user ID
                $userId = $this->conn->lastInsertId();

                // Save custom field values if any
                if (!empty($customFieldValues)) {
                    require_once 'models/CustomFieldModel.php';
                    $customFieldModel = new CustomFieldModel();
                    $customFieldModel->saveCustomFieldValues($userId, $customFieldValues);
                }
            }

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new PDOException("Database execution failed: " . $errorInfo[2]);
            }

            return true;

        } catch (PDOException $e) {
            // Log the error for debugging
            error_log("UserModel insertUser error: " . $e->getMessage());
            error_log("Gender value: " . var_export($gender, true));
            error_log("All data: " . var_export($postData, true));

            // Re-throw the exception with more context
            throw new PDOException("Failed to insert user: " . $e->getMessage());
        }
    }

     // ✅ Soft Delete Function
     public function softDeleteUser($id) {
        try {
            // Determine if the ID is numeric (primary key) or string (profile_id)
            $isNumericId = is_numeric($id);
            
            // Get user ID first
            if ($isNumericId) {
                $getUserStmt = $this->conn->prepare("SELECT id FROM user_profiles WHERE id = :id");
                $getUserStmt->execute([':id' => $id]);
            } else {
                $getUserStmt = $this->conn->prepare("SELECT id FROM user_profiles WHERE profile_id = :profile_id");
                $getUserStmt->execute([':profile_id' => $id]);
            }
            
            $user = $getUserStmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Delete custom field values and update usage counts
                require_once 'models/CustomFieldModel.php';
                $customFieldModel = new CustomFieldModel();
                $customFieldModel->deleteUserCustomFieldValues($user['id']);
            }

            // Soft delete the user
            if ($isNumericId) {
                $query = "UPDATE user_profiles SET is_deleted = 1 WHERE id = :id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":id", $id);
            } else {
                $query = "UPDATE user_profiles SET is_deleted = 1 WHERE profile_id = :profile_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":profile_id", $id);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("UserModel softDeleteUser error: " . $e->getMessage());
            return false;
        }
    }

    // Lock and unlock user from actions
    public function updateLockStatus($id, $locked_status) {
        // Determine if the ID is numeric (primary key) or string (profile_id)
        $isNumericId = is_numeric($id);
        
        if ($isNumericId) {
            $query = "UPDATE user_profiles SET locked_status = :locked_status WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":locked_status", $locked_status, PDO::PARAM_INT);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        } else {
            $query = "UPDATE user_profiles SET locked_status = :locked_status WHERE profile_id = :profile_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":locked_status", $locked_status, PDO::PARAM_INT);
            $stmt->bindParam(":profile_id", $id, PDO::PARAM_STR);
        }
        
        return $stmt->execute();
    }

     // Fetch paginated users with search and filters
     public function getAllUsersPaginated($limit, $offset, $search = '', $filters = []) {
        $params = [];
        $sql = "SELECT * FROM user_profiles WHERE is_deleted = 0 ";

        // Add search functionality
        if ($search !== '') {
            $sql .= " AND (profile_id LIKE ? OR full_name LIKE ? OR email LIKE ? OR contact_number LIKE ?) ";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        // Add filter functionality
        if (!empty($filters['user_status'])) {
            $sql .= " AND user_status = ? ";
            $params[] = $filters['user_status'];
        }

        if (!empty($filters['locked_status'])) {
            $sql .= " AND locked_status = ? ";
            $params[] = $filters['locked_status'];
        }

        if (!empty($filters['user_role'])) {
            $sql .= " AND user_role LIKE ? ";
            $params[] = '%' . $filters['user_role'] . '%';
        }

        if (!empty($filters['gender'])) {
            $sql .= " AND gender = ? ";
            $params[] = $filters['gender'];
        }

        $sql .= " ORDER BY full_name ASC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $index => $param) {
            $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get total user count with search and filters
    public function getTotalUserCount($search = '', $filters = []) {
        $params = [];
        $sql = "SELECT COUNT(*) as total FROM user_profiles WHERE is_deleted = 0 ";

        // Add search functionality
        if ($search !== '') {
            $sql .= " AND (profile_id LIKE ? OR full_name LIKE ? OR email LIKE ? OR contact_number LIKE ?) ";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        // Add filter functionality
        if (!empty($filters['user_status'])) {
            $sql .= " AND user_status = ? ";
            $params[] = $filters['user_status'];
        }

        if (!empty($filters['locked_status'])) {
            $sql .= " AND locked_status = ? ";
            $params[] = $filters['locked_status'];
        }

        if (!empty($filters['user_role'])) {
            $sql .= " AND user_role LIKE ? ";
            $params[] = '%' . $filters['user_role'] . '%';
        }

        if (!empty($filters['gender'])) {
            $sql .= " AND gender = ? ";
            $params[] = $filters['gender'];
        }

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $index => $param) {
            $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Get unique values for filter dropdowns
    public function getDistinctUserRoles() {
        $stmt = $this->conn->prepare("SELECT DISTINCT user_role FROM user_profiles WHERE is_deleted = 0 AND user_role IS NOT NULL AND user_role != '' ORDER BY user_role ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get available user roles from user_roles table
     */
    public function getAvailableUserRoles() {
        return $this->userRoleModel->getAllRoles();
    }

    /**
     * Get client-specific user roles (excluding super admin)
     */
    public function getClientUserRoles() {
        return $this->userRoleModel->getClientRoles();
    }

    /**
     * Get admin roles only
     */
    public function getAdminUserRoles() {
        return $this->userRoleModel->getAdminRoles();
    }

    public function getDistinctGenders() {
        $stmt = $this->conn->prepare("SELECT DISTINCT gender FROM user_profiles WHERE is_deleted = 0 AND gender IS NOT NULL AND gender != '' ORDER BY gender ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get user by profile ID for editing
    public function getUserById($profile_id, $clientId = null) {
        return $this->getUserByIdOrProfileId($profile_id, $clientId);
    }

    /**
     * Get user by ID (numeric) or profile_id (string)
     * This method can handle both the primary key ID and the profile_id
     */
    public function getUserByIdOrProfileId($id, $clientId = null) {
        try {
            // Check if the ID is numeric (primary key) or string (profile_id)
            if (is_numeric($id)) {
                // It's a numeric ID (primary key)
                $sql = "SELECT * FROM user_profiles WHERE id = :id AND is_deleted = 0";
                $params = [':id' => $id];
            } else {
                // It's a profile_id (string)
                $sql = "SELECT * FROM user_profiles WHERE profile_id = :profile_id AND is_deleted = 0";
                $params = [':profile_id' => $id];
            }

            if ($clientId !== null) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $clientId;
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("UserModel getUserByIdOrProfileId error: " . $e->getMessage());
            return false;
        }
    }

    // Update user information
    public function updateUser($id, $postData, $fileData = []) {
        try {
            $isNumericId = is_numeric($id);
            // Extract values safely, setting NULL if empty
            $full_name = $postData['full_name'] ?? null;
            $email = $postData['email'] ?? null;
            $contact_number = $postData['contact_number'] ?? null;
            $gender = !empty($postData['gender']) ? $postData['gender'] : null;
            $dob = !empty($postData['dob']) ? $postData['dob'] : null;
            $user_role = $postData['user_role'] ?? null;
            $profile_expiry = !empty($postData['profile_expiry']) ? $postData['profile_expiry'] : null;
            $user_status = $postData['user_status'] ?? null;
            $locked_status = $postData['locked_status'] ?? null;
            $leaderboard = $postData['leaderboard'] ?? null;
            $country = $postData['country'] ?? null;
            $state = $postData['state'] ?? null;
            $city = $postData['city'] ?? null;
            $timezone = $postData['timezone'] ?? null;
            $language = $postData['language'] ?? null;
            $reports_to = $postData['reports_to'] ?? null;
            $joining_date = !empty($postData['joining_date']) ? $postData['joining_date'] : null;
            $retirement_date = !empty($postData['retirement_date']) ? $postData['retirement_date'] : null;

            // Extract custom field values
            $customFieldValues = [];
            foreach ($postData as $key => $value) {
                if (strpos($key, 'custom_field_') === 0) {
                    $fieldId = str_replace('custom_field_', '', $key);
                    if (is_array($value)) {
                        // Handle checkbox arrays
                        $customFieldValues[$fieldId] = implode(',', $value);
                    } else {
                        $customFieldValues[$fieldId] = !empty($value) ? $value : null;
                    }
                }
            }

            // Handle profile picture upload (optional for update)
            $profile_picture = null;
            if (!empty($fileData['profile_picture']['name'])) {
                $uploadDir = "uploads/profile_pictures/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $profile_picture = $uploadDir . uniqid() . "_" . basename($fileData['profile_picture']['name']);
                // Debug logging
                error_log('EditUser $_FILES: ' . print_r($fileData['profile_picture'], true));
                error_log('EditUser file_exists(tmp): ' . (file_exists($fileData['profile_picture']['tmp_name']) ? 'yes' : 'no'));
                error_log('EditUser TMP: ' . $fileData['profile_picture']['tmp_name'] . ' -> ' . $profile_picture);
                error_log('EditUser Is uploaded file: ' . (is_uploaded_file($fileData['profile_picture']['tmp_name']) ? 'yes' : 'no'));
                error_log('EditUser Dir writable: ' . (is_writable($uploadDir) ? 'yes' : 'no'));
                if (!move_uploaded_file($fileData['profile_picture']['tmp_name'], $profile_picture)) {
                    error_log('EditUser move_uploaded_file failed');
                    throw new Exception("Failed to upload profile picture.");
                }
            }

            // Get current user ID for audit fields
            $currentUserId = $_SESSION['user']['id'] ?? null;

            // Build update query (with audit fields)
            $sql = "UPDATE user_profiles SET
                full_name = :full_name,
                email = :email,
                contact_number = :contact_number,
                gender = :gender,
                dob = :dob,
                user_role = :user_role,
                profile_expiry = :profile_expiry,
                user_status = :user_status,
                locked_status = :locked_status,
                leaderboard = :leaderboard,
                country = :country,
                state = :state,
                city = :city,
                timezone = :timezone,
                language = :language,
                reports_to = :reports_to,
                joining_date = :joining_date,
                retirement_date = :retirement_date,
                updated_by = :updated_by";

            if ($profile_picture) {
                $sql .= ", profile_picture = :profile_picture";
            }
            if ($isNumericId) {
                $sql .= " WHERE id = :id AND is_deleted = 0";
            } else {
                $sql .= " WHERE profile_id = :profile_id AND is_deleted = 0";
            }

            $stmt = $this->conn->prepare($sql);

            $params = [
                ':full_name' => $full_name,
                ':email' => $email,
                ':contact_number' => $contact_number,
                ':gender' => $gender,
                ':dob' => $dob,
                ':user_role' => $user_role,
                ':profile_expiry' => $profile_expiry,
                ':user_status' => $user_status,
                ':locked_status' => $locked_status,
                ':leaderboard' => $leaderboard,
                ':country' => $country,
                ':state' => $state,
                ':city' => $city,
                ':timezone' => $timezone,
                ':language' => $language,
                ':reports_to' => $reports_to,
                ':joining_date' => $joining_date,
                ':retirement_date' => $retirement_date,
                ':updated_by' => $currentUserId
            ];
            if ($isNumericId) {
                $params[':id'] = $id;
            } else {
                $params[':profile_id'] = $id;
            }
            if ($profile_picture) {
                $params[':profile_picture'] = $profile_picture;
            }

            $result = $stmt->execute($params);

            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                throw new PDOException("Database execution failed: " . $errorInfo[2]);
            }

            // Update custom field values if any
            if (!empty($customFieldValues)) {
                // Get user ID from the appropriate field
                if ($isNumericId) {
                    $userStmt = $this->conn->prepare("SELECT id FROM user_profiles WHERE id = :id");
                    $userStmt->execute([':id' => $id]);
                } else {
                    $userStmt = $this->conn->prepare("SELECT id FROM user_profiles WHERE profile_id = :profile_id");
                    $userStmt->execute([':profile_id' => $id]);
                }
                
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    require_once 'models/CustomFieldModel.php';
                    $customFieldModel = new CustomFieldModel();
                    $customFieldModel->saveCustomFieldValues($user['id'], $customFieldValues);
                }
            }

            return true;

        } catch (PDOException $e) {
            error_log("UserModel updateUser error: " . $e->getMessage());
            throw new PDOException("Failed to update user: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("UserModel updateUser general error: " . $e->getMessage());
            throw new Exception("Failed to update user: " . $e->getMessage());
        }
    }

    /**
     * Get users by client (for client admins)
     */
    public function getUsersByClient($clientId, $limit = 10, $offset = 0, $search = '', $filters = []) {
        if (empty($clientId) || !is_numeric($clientId)) {
            return [];
        }
        $sql = "SELECT up.*, c.client_name, c.max_users
                FROM user_profiles up
                LEFT JOIN clients c ON up.client_id = c.id
                WHERE up.client_id = ? AND up.is_deleted = 0";

        $params = [$clientId];

        if (!empty($search)) {
            $sql .= " AND (up.full_name LIKE ? OR up.email LIKE ? OR up.profile_id LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($filters['user_status'])) {
            $sql .= " AND up.user_status = ?";
            $params[] = $filters['user_status'];
        }

        if (!empty($filters['locked_status'])) {
            $sql .= " AND up.locked_status = ?";
            $params[] = $filters['locked_status'];
        }

        if (!empty($filters['user_role'])) {
            $sql .= " AND up.user_role = ?";
            $params[] = $filters['user_role'];
        }

        $sql .= " ORDER BY up.id DESC LIMIT ? OFFSET ?";
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
     * Check if client can add more users
     */
    public function canClientAddUser($clientId) {
        $sql = "SELECT c.max_users, COUNT(up.id) as current_count
                FROM clients c
                LEFT JOIN user_profiles up ON c.id = up.client_id AND up.user_status = 'Active' AND up.is_deleted = 0
                WHERE c.id = ?
                GROUP BY c.id";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) return false;

        return $result['current_count'] < $result['max_users'];
    }

    /**
     * Check if client can add more admin users
     */
    public function canClientAddAdmin($clientId, $excludeUserId = null) {
        // Get client's admin role limit
        $clientSql = "SELECT admin_role_limit FROM clients WHERE id = ? AND is_deleted = 0";
        $clientStmt = $this->conn->prepare($clientSql);
        $clientStmt->execute([$clientId]);
        $client = $clientStmt->fetch(PDO::FETCH_ASSOC);

        if (!$client) return false;

        // Count current admin users
        $countSql = "SELECT COUNT(*) as admin_count
                     FROM user_profiles
                     WHERE client_id = ?
                     AND user_role = 'Admin'
                     AND user_status = 'Active'
                     AND is_deleted = 0";

        $params = [$clientId];

        // Exclude specific user (for edit scenarios)
        if ($excludeUserId) {
            $countSql .= " AND id != ?";
            $params[] = $excludeUserId;
        }

        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $result = $countStmt->fetch(PDO::FETCH_ASSOC);

        $currentAdminCount = $result['admin_count'] ?? 0;

        return $currentAdminCount < $client['admin_role_limit'];
    }

    /**
     * Get admin role status for client
     */
    public function getAdminRoleStatus($clientId, $excludeUserId = null) {
        // Get client's admin role limit
        $clientSql = "SELECT admin_role_limit FROM clients WHERE id = ? AND is_deleted = 0";
        $clientStmt = $this->conn->prepare($clientSql);
        $clientStmt->execute([$clientId]);
        $client = $clientStmt->fetch(PDO::FETCH_ASSOC);

        if (!$client) {
            return [
                'canAdd' => false,
                'limit' => 0,
                'current' => 0,
                'message' => 'Client not found'
            ];
        }

        // Count current admin users
        $countSql = "SELECT COUNT(*) as admin_count
                     FROM user_profiles
                     WHERE client_id = ?
                     AND user_role = 'Admin'
                     AND user_status = 'Active'
                     AND is_deleted = 0";

        $params = [$clientId];

        // Exclude specific user (for edit scenarios)
        if ($excludeUserId) {
            $countSql .= " AND id != ?";
            $params[] = $excludeUserId;
        }

        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $result = $countStmt->fetch(PDO::FETCH_ASSOC);

        $currentAdminCount = $result['admin_count'] ?? 0;
        $canAdd = $currentAdminCount < $client['admin_role_limit'];

        return [
            'canAdd' => $canAdd,
            'limit' => $client['admin_role_limit'],
            'current' => $currentAdminCount,
            'message' => $canAdd ? '' : 'Admin limit reached'
        ];
    }

    /**
     * Get user with client details
     */
    public function getUserWithClient($userId) {
        $sql = "SELECT up.*, c.client_name, c.max_users, c.current_user_count
                FROM user_profiles up
                LEFT JOIN clients c ON up.client_id = c.id
                WHERE up.id = ? AND up.is_deleted = 0";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get user limit status for client
     */
    public function getUserLimitStatus($clientId) {
        // Get client's user limit
        $clientSql = "SELECT max_users FROM clients WHERE id = ? AND is_deleted = 0";
        $clientStmt = $this->conn->prepare($clientSql);
        $clientStmt->execute([$clientId]);
        $client = $clientStmt->fetch(PDO::FETCH_ASSOC);

        if (!$client) {
            return [
                'canAdd' => false,
                'limit' => 0,
                'current' => 0,
                'message' => 'Client not found'
            ];
        }

        // Count current active users
        $countSql = "SELECT COUNT(*) as user_count
                     FROM user_profiles
                     WHERE client_id = ?
                     AND user_status = 'Active'
                     AND is_deleted = 0";

        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute([$clientId]);
        $result = $countStmt->fetch(PDO::FETCH_ASSOC);

        $currentUserCount = $result['user_count'] ?? 0;
        $canAdd = $currentUserCount < $client['max_users'];

        return [
            'canAdd' => $canAdd,
            'limit' => $client['max_users'],
            'current' => $currentUserCount,
            'message' => $canAdd ? '' : 'User limit reached'
        ];
    }

    /**
     * Get all languages for dropdown
     */
    public function getLanguages() {
        $sql = "SELECT id, language_name, language_code FROM languages ORDER BY language_name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get Admin users by client
     */
    public function getAdminUsersByClient($clientId, $limit = 10, $offset = 0, $search = '', $filters = []) {
        if (empty($clientId) || !is_numeric($clientId)) {
            return [];
        }
        $sql = "SELECT up.*, c.client_name, c.max_users
                FROM user_profiles up
                LEFT JOIN clients c ON up.client_id = c.id
                WHERE up.client_id = ? AND up.user_role = 'Admin' AND up.is_deleted = 0";

        $params = [$clientId];

        if (!empty($search)) {
            $sql .= " AND (up.full_name LIKE ? OR up.email LIKE ? OR up.profile_id LIKE ? OR up.contact_number LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($filters['user_status'])) {
            $sql .= " AND up.user_status = ?";
            $params[] = $filters['user_status'];
        }

        if (!empty($filters['locked_status'])) {
            $sql .= " AND up.locked_status = ?";
            $params[] = $filters['locked_status'];
        }

        if (!empty($filters['gender'])) {
            $sql .= " AND up.gender = ?";
            $params[] = $filters['gender'];
        }

        $sql .= " ORDER BY up.id DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $index => $param) {
            $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
