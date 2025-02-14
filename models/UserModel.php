<?php
include 'config/Database.php';

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
            echo '<pre>' ; print_r($_POST); die;
            $query = "INSERT INTO user_profiles (
                client_id, profile_id, full_name, email, contact_number, gender, dob, user_role, profile_expiry, user_status, locked_status, leaderboard, profile_picture,
                country, state, city, timezone, language, reports_to, joining_date, retirement_date,
                customised_1, customised_2, customised_3, customised_4, customised_5, customised_6, customised_7, customised_8, customised_9, customised_10
            ) VALUES (
                :client_id, :profile_id, :full_name, :email, :contact_number, :gender, :dob, :user_role, :profile_expiry, :user_status, :locked_status, :leaderboard, :profile_picture,
                :country, :state, :city, :timezone, :language, :reports_to, :joining_date, :retirement_date,
                :custom_1, :custom_2, :custom_3, :custom_4, :custom_5, :custom_6, :custom_7, :custom_8, :custom_9, :custom_10
            )";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":client_id", $client_id);
            $stmt->bindParam(":profile_id", $profile_id);
            $stmt->bindParam(":full_name", $full_name);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":contact_number", $contact_number);
            $stmt->bindParam(":gender", $gender);
            $stmt->bindParam(":dob", $dob);
            $stmt->bindParam(":user_role", $user_role);
            $stmt->bindParam(":profile_expiry", $profile_expiry);
            $stmt->bindParam(":user_status", $user_status, PDO::PARAM_BOOL);
            $stmt->bindParam(":locked_status", $locked_status, PDO::PARAM_BOOL);
            $stmt->bindParam(":leaderboard", $leaderboard, PDO::PARAM_BOOL);
            $stmt->bindParam(":profile_picture", $profile_picture);

            // Additional Details
            $stmt->bindParam(":country", $country);
            $stmt->bindParam(":state", $state);
            $stmt->bindParam(":city", $city);
            $stmt->bindParam(":timezone", $timezone);
            $stmt->bindParam(":language", $language);
            $stmt->bindParam(":reports_to", $reports_to);
            $stmt->bindParam(":joining_date", $joining_date);
            $stmt->bindParam(":retirement_date", $retirement_date);

            // Custom Fields 1-10
            $stmt->bindParam(":custom_1", $custom_1);
            $stmt->bindParam(":custom_2", $custom_2);
            $stmt->bindParam(":custom_3", $custom_3);
            $stmt->bindParam(":custom_4", $custom_4);
            $stmt->bindParam(":custom_5", $custom_5);
            $stmt->bindParam(":custom_6", $custom_6);
            $stmt->bindParam(":custom_7", $custom_7);
            $stmt->bindParam(":custom_8", $custom_8);
            $stmt->bindParam(":custom_9", $custom_9);
            $stmt->bindParam(":custom_10", $custom_10);

            if ($stmt->execute()) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            die("Database Insert Error: " . $e->getMessage());
        }
    }
}
?>
