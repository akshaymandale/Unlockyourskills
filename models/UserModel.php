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
        try {
            // Extract data from $_POST
          //  $client_id = $_SESSION['client_id'] ?? null;
            $client_id = $postData['client_id'] ?? null;
            $profile_id = $postData['profile_id'] ?? null;
            $full_name = $postData['full_name'] ?? null;
            $email = $postData['email'] ?? null;
            $contact_number = $postData['contact_number'] ?? null;
            $gender = $postData['gender'] ?? null;
            $dob = $postData['dob'] ?? null;
            $user_role = $postData['user_role'] ?? null;
            $profile_expiry = $postData['profile_expiry'] ?? null;
            $user_status = isset($postData['user_status']) ? 1 : 0;
            $locked_status = isset($postData['locked_status']) ? 1 : 0;
            $leaderboard = isset($postData['leaderboard']) ? 1 : 0;
            $profile_picture = $fileData['profile_picture']['name'] ?? null;
            $country = $postData['country'] ?? null;
            $state = $postData['state'] ?? null;
            $city = $postData['city'] ?? null;
            $timezone = $postData['timezone'] ?? null;
            $language = $postData['language'] ?? null;
            $reports_to = $postData['reports_to'] ?? null;
            $joining_date = $postData['joining_date'] ?? null;
            $retirement_date = $postData['retirement_date'] ?? null;
            
            // ✅ Ensure Custom Fields Are Handled Properly
            $custom_1 = $postData['customised_1'] ?? null;
            $custom_2 = $postData['customised_2'] ?? null;
            $custom_3 = $postData['customised_3'] ?? null;
            $custom_4 = $postData['customised_4'] ?? null;
            $custom_5 = $postData['customised_5'] ?? null;
            $custom_6 = $postData['customised_6'] ?? null;
            $custom_7 = $postData['customised_7'] ?? null;
            $custom_8 = $postData['customised_8'] ?? null;
            $custom_9 = $postData['customised_9'] ?? null;
            $custom_10 = $postData['customised_10'] ?? null;
    
            // ✅ SQL Query
            $sql = "INSERT INTO user_profiles 
            (client_id, profile_id, full_name, email, contact_number, gender, dob, user_role, profile_expiry, user_status, locked_status, leaderboard, profile_picture, country, state, city, timezone, language, reports_to, joining_date, retirement_date, customised_1, customised_2, customised_3, customised_4, customised_5, customised_6, customised_7, customised_8, customised_9, customised_10) 
            VALUES 
            (:client_id, :profile_id, :full_name, :email, :contact_number, :gender, :dob, :user_role, :profile_expiry, :user_status, :locked_status, :leaderboard, :profile_picture, :country, :state, :city, :timezone, :language, :reports_to, :joining_date, :retirement_date, :customised_1, :customised_2, :customised_3, :customised_4, :customised_5, :customised_6, :customised_7, :customised_8, :customised_9, :customised_10)";
    
            $stmt = $this->conn->prepare($sql);
    
            // ✅ Bind parameters
            $stmt->bindParam(':client_id', $client_id, PDO::PARAM_INT);
            $stmt->bindParam(':profile_id', $profile_id);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':contact_number', $contact_number);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':dob', $dob);
            $stmt->bindParam(':user_role', $user_role);
            $stmt->bindParam(':profile_expiry', $profile_expiry);
            $stmt->bindParam(':user_status', $user_status, PDO::PARAM_INT);
            $stmt->bindParam(':locked_status', $locked_status, PDO::PARAM_INT);
            $stmt->bindParam(':leaderboard', $leaderboard, PDO::PARAM_INT);
            $stmt->bindParam(':profile_picture', $profile_picture);
            $stmt->bindParam(':country', $country);
            $stmt->bindParam(':state', $state);
            $stmt->bindParam(':city', $city);
            $stmt->bindParam(':timezone', $timezone);
            $stmt->bindParam(':language', $language);
            $stmt->bindParam(':reports_to', $reports_to);
            $stmt->bindParam(':joining_date', $joining_date);
            $stmt->bindParam(':retirement_date', $retirement_date);
            $stmt->bindParam(':customised_1', $custom_1);
            $stmt->bindParam(':customised_2', $custom_2);
            $stmt->bindParam(':customised_3', $custom_3);
            $stmt->bindParam(':customised_4', $custom_4);
            $stmt->bindParam(':customised_5', $custom_5);
            $stmt->bindParam(':customised_6', $custom_6);
            $stmt->bindParam(':customised_7', $custom_7);
            $stmt->bindParam(':customised_8', $custom_8);
            $stmt->bindParam(':customised_9', $custom_9);
            $stmt->bindParam(':customised_10', $custom_10);
    
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            die("Error inserting user: " . $e->getMessage());
        }
    }

}
?>
