<?php
// controllers/UserManagementController.php
require_once 'models/UserModel.php';

class UserManagementController {
    
    public function index() {
        include 'views/user_management.php';
    }
    
    public function addUser() {
        include 'views/add_user.php';
    }

    public function storeUser() {
        $userModel = new UserModel();
        
        // Extract values from $_POST
        $client_id = $_POST['client_id'];
        $profile_id = $_POST['profile_id'];
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $contact_number = $_POST['contact_number'];
        $gender = $_POST['gender'];
        $dob = $_POST['dob'];
        $user_role = $_POST['user_role'];
        $profile_expiry = $_POST['profile_expiry'];
        $user_status = isset($_POST['user_status']) ? 1 : 0;
        $locked_status = isset($_POST['locked_status']) ? 1 : 0;
        $leaderboard = isset($_POST['leaderboard']) ? 1 : 0;
    
        $country = $_POST['country'];
        $state = $_POST['state'];
        $city = $_POST['city'];
        $timezone = $_POST['timezone'];
        $language = $_POST['language'];
        $reports_to = $_POST['reports_to'];
        $joining_date = $_POST['joining_date'];
        $retirement_date = $_POST['retirement_date'];
    
        $custom_1 = $_POST['customised_1'];
        $custom_2 = $_POST['customised_2'];
        $custom_3 = $_POST['customised_3'];
        $custom_4 = $_POST['customised_4'];
        $custom_5 = $_POST['customised_5'];
        $custom_6 = $_POST['customised_6'];
        $custom_7 = $_POST['customised_7'];
        $custom_8 = $_POST['customised_8'];
        $custom_9 = $_POST['customised_9'];
        $custom_10 = $_POST['customised_10'];
    
        // Handle File Upload for Profile Picture
        $profile_picture = "";
        if (!empty($_FILES["profile_picture"]["name"])) {
            $target_dir = "uploads/";
            $profile_picture = $target_dir . basename($_FILES["profile_picture"]["name"]);
            move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $profile_picture);
        }
    
        // Call the insertUser() method with correct arguments
        $result = $userModel->insertUser($client_id, $profile_id, $full_name, $email, $contact_number, $gender, $dob, $user_role, $profile_expiry, $user_status, $locked_status, $leaderboard, $profile_picture, $country, $state, $city, $timezone, $language, $reports_to, $joining_date, $retirement_date, $custom_1, $custom_2, $custom_3, $custom_4, $custom_5, $custom_6, $custom_7, $custom_8, $custom_9, $custom_10);
    
        if ($result) {
            echo "<script>alert('User added successfully!'); window.location.href='index.php?controller=UserManagementController&action=index';</script>";
        } else {
            echo "<script>alert('Error adding user!');</script>";
        }
    }
    
    
}
?>
