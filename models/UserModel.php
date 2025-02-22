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

    public function insertUser($client_id, $profile_id, $full_name, $email, $contact_number, $gender, $dob, $user_role, $profile_expiry, $user_status, $locked_status, $leaderboard, $profile_picture, $country, $state, $city, $timezone, $language, $reports_to, $joining_date, $retirement_date, $custom_1, $custom_2, $custom_3, $custom_4, $custom_5, $custom_6, $custom_7, $custom_8, $custom_9, $custom_10) {
        try {
            $query = "INSERT INTO user_profiles (client_id, profile_id, full_name, email, contact_number, gender, dob, user_role, profile_expiry, user_status, locked_status, leaderboard, profile_picture, country, state, city, timezone, language, reports_to, joining_date, retirement_date, customised_1, customised_2, customised_3, customised_4, customised_5, customised_6, customised_7, customised_8, customised_9, customised_10) 
                      VALUES (:client_id, :profile_id, :full_name, :email, :contact_number, :gender, :dob, :user_role, :profile_expiry, :user_status, :locked_status, :leaderboard, :profile_picture, :country, :state, :city, :timezone, :language, :reports_to, :joining_date, :retirement_date, :custom_1, :custom_2, :custom_3, :custom_4, :custom_5, :custom_6, :custom_7, :custom_8, :custom_9, :custom_10)";
    
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":client_id" => $client_id,
                ":profile_id" => $profile_id,
                ":full_name" => $full_name,
                ":email" => $email,
                ":contact_number" => $contact_number,
                ":gender" => $gender,
                ":dob" => $dob,
                ":user_role" => $user_role,
                ":profile_expiry" => $profile_expiry,
                ":user_status" => $user_status,
                ":locked_status" => $locked_status,
                ":leaderboard" => $leaderboard,
                ":profile_picture" => $profile_picture,
                ":country" => $country,
                ":state" => $state,
                ":city" => $city,
                ":timezone" => $timezone,
                ":language" => $language,
                ":reports_to" => $reports_to,
                ":joining_date" => $joining_date,
                ":retirement_date" => $retirement_date,
                ":custom_1" => $custom_1,
                ":custom_2" => $custom_2,
                ":custom_3" => $custom_3,
                ":custom_4" => $custom_4,
                ":custom_5" => $custom_5,
                ":custom_6" => $custom_6,
                ":custom_7" => $custom_7,
                ":custom_8" => $custom_8,
                ":custom_9" => $custom_9,
                ":custom_10" => $custom_10
            ]);
    
            return true;
        } catch (PDOException $e) {
            die("Error inserting user: " . $e->getMessage());
        }
    }
    
}
?>
