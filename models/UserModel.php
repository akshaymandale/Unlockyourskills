<?php
require_once 'config/Database.php';

class UserModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getUser($client_code, $username) {
        $stmt = $this->conn->prepare("SELECT up.*, c.client_name, c.max_users, c.current_user_count
                                     FROM user_profiles up
                                     LEFT JOIN clients c ON up.client_id = c.id
                                     WHERE up.client_id = ? AND up.email = ?");
        $stmt->execute([$client_code, $username]);
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
            $uploadDir = "uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
                chmod($uploadDir, 0777); // Ensure proper permissions
            }
            $profile_picture = $uploadDir . basename($fileData['profile_picture']['name']);
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

        // Insert into Database (without custom fields)
        $sql = "INSERT INTO user_profiles
            (client_id, profile_id, full_name, email, contact_number, gender, dob, user_role, system_role, profile_expiry, user_status, locked_status, leaderboard, profile_picture, country, state, city, timezone, language, reports_to, joining_date, retirement_date)
            VALUES
            (:client_id, :profile_id, :full_name, :email, :contact_number, :gender, :dob, :user_role, :system_role, :profile_expiry, :user_status, :locked_status, :leaderboard, :profile_picture, :country, :state, :city, :timezone, :language, :reports_to, :joining_date, :retirement_date)";
    
        $stmt = $this->conn->prepare($sql);

        // Determine system_role based on user_role
        $system_role = 'user'; // default
        if ($user_role === 'Admin') {
            $system_role = 'admin';
        } elseif ($user_role === 'Super Admin') {
            $system_role = 'super_admin';
        }

        try {
            $result = $stmt->execute([
                ':client_id' => $client_id,
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
                ':retirement_date' => $retirement_date
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
     public function softDeleteUser($profile_id) {
        try {
            $query = "UPDATE user_profiles SET is_deleted = 1 WHERE profile_id = :profile_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":profile_id", $profile_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    // Lock and unlock user from actions
    public function updateLockStatus($profile_id, $locked_status) {
        $query = "UPDATE user_profiles SET locked_status = :locked_status WHERE profile_id = :profile_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":locked_status", $locked_status, PDO::PARAM_INT);
        $stmt->bindParam(":profile_id", $profile_id, PDO::PARAM_STR);
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

    public function getDistinctGenders() {
        $stmt = $this->conn->prepare("SELECT DISTINCT gender FROM user_profiles WHERE is_deleted = 0 AND gender IS NOT NULL AND gender != '' ORDER BY gender ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get user by profile ID for editing
    public function getUserById($profile_id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM user_profiles WHERE profile_id = :profile_id AND is_deleted = 0");
            $stmt->bindParam(":profile_id", $profile_id, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("UserModel getUserById error: " . $e->getMessage());
            return false;
        }
    }

    // Update user information
    public function updateUser($profile_id, $postData, $fileData = []) {
        try {
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
                $uploadDir = "uploads/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                    chmod($uploadDir, 0777); // Ensure proper permissions
                }
                $profile_picture = $uploadDir . basename($fileData['profile_picture']['name']);
                if (!move_uploaded_file($fileData['profile_picture']['tmp_name'], $profile_picture)) {
                    throw new Exception("Failed to upload profile picture.");
                }
            }

            // Build update query (without custom fields)
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
                retirement_date = :retirement_date";

            // Only update profile picture if a new one was uploaded
            if ($profile_picture) {
                $sql .= ", profile_picture = :profile_picture";
            }

            $sql .= " WHERE profile_id = :profile_id AND is_deleted = 0";

            $stmt = $this->conn->prepare($sql);

            $params = [
                ':profile_id' => $profile_id,
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
                ':retirement_date' => $retirement_date
            ];

            // Add profile picture parameter if needed
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
                // Get user ID from profile_id
                $userStmt = $this->conn->prepare("SELECT id FROM user_profiles WHERE profile_id = :profile_id");
                $userStmt->execute([':profile_id' => $profile_id]);
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
}
?>
