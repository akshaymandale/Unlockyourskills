<?php
require_once 'config/Database.php';

class UserModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getUser($client_code, $username) {
        $stmt = $this->conn->prepare("SELECT * FROM user_profiles WHERE client_id = ? AND email = ?");
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
        $gender = $postData['gender'] ?? null;
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
    
        // Custom fields (Ensure NULL if empty)
        $custom_1 = !empty($postData['customised_1']) ? $postData['customised_1'] : null;
        $custom_2 = !empty($postData['customised_2']) ? $postData['customised_2'] : null;
        $custom_3 = !empty($postData['customised_3']) ? $postData['customised_3'] : null;
        $custom_4 = !empty($postData['customised_4']) ? $postData['customised_4'] : null;
        $custom_5 = !empty($postData['customised_5']) ? $postData['customised_5'] : null;
        $custom_6 = !empty($postData['customised_6']) ? $postData['customised_6'] : null;
        $custom_7 = !empty($postData['customised_7']) ? $postData['customised_7'] : null;
        $custom_8 = !empty($postData['customised_8']) ? $postData['customised_8'] : null;
        $custom_9 = !empty($postData['customised_9']) ? $postData['customised_9'] : null;
        $custom_10 = !empty($postData['customised_10']) ? $postData['customised_10'] : null;
    
        // Handling Profile Picture Upload
        $profile_picture = null;
        if (!empty($fileData['profile_picture']['name'])) {
            $uploadDir = "uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $profile_picture = $uploadDir . basename($fileData['profile_picture']['name']);
            if (!move_uploaded_file($fileData['profile_picture']['tmp_name'], $profile_picture)) {
                die("Error: Failed to upload profile picture.");
            }
        }
    
        // Insert into Database
        $sql = "INSERT INTO user_profiles 
            (client_id, profile_id, full_name, email, contact_number, gender, dob, user_role, profile_expiry, user_status, locked_status, leaderboard, profile_picture, country, state, city, timezone, language, reports_to, joining_date, retirement_date, customised_1, customised_2, customised_3, customised_4, customised_5, customised_6, customised_7, customised_8, customised_9, customised_10) 
            VALUES 
            (:client_id, :profile_id, :full_name, :email, :contact_number, :gender, :dob, :user_role, :profile_expiry, :user_status, :locked_status, :leaderboard, :profile_picture, :country, :state, :city, :timezone, :language, :reports_to, :joining_date, :retirement_date, :custom_1, :custom_2, :custom_3, :custom_4, :custom_5, :custom_6, :custom_7, :custom_8, :custom_9, :custom_10)";
    
        $stmt = $this->conn->prepare($sql);
    
        $stmt->execute([
            ':client_id' => $client_id,
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
            ':profile_picture' => $profile_picture,
            ':country' => $country,
            ':state' => $state,
            ':city' => $city,
            ':timezone' => $timezone,
            ':language' => $language,
            ':reports_to' => $reports_to,
            ':joining_date' => $joining_date,
            ':retirement_date' => $retirement_date,
            ':custom_1' => $custom_1,
            ':custom_2' => $custom_2,
            ':custom_3' => $custom_3,
            ':custom_4' => $custom_4,
            ':custom_5' => $custom_5,
            ':custom_6' => $custom_6,
            ':custom_7' => $custom_7,
            ':custom_8' => $custom_8,
            ':custom_9' => $custom_9,
            ':custom_10' => $custom_10
        ]);
    
        return true;
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
}
?>
