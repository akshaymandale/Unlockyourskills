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
       // session_start();

        // Ensure client_id is set
      //  $client_id = $_SESSION['client_id'];
        $client_id = trim($_POST['client_id']);
        // Extract form data
        $profile_id = trim($_POST['profile_id']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $contact_number = trim($_POST['contact_number']);
        $gender = trim($_POST['gender']);
        $dob = trim($_POST['dob']);
        $user_role = trim($_POST['user_role']);
        $profile_expiry = trim($_POST['profile_expiry']);
        $user_status = trim($_POST['user_status']);
        $locked_status = trim($_POST['locked_status']);
        $leaderboard = trim($_POST['leaderboard']);

        // Additional Details
        $country = trim($_POST['country']);
        $state = trim($_POST['state']);
        $city = trim($_POST['city']);
        $timezone = trim($_POST['timezone']);
        $language = trim($_POST['language']);
        $reports_to = trim($_POST['reports_to']);
        $joining_date = trim($_POST['joining_date']);
        $retirement_date = trim($_POST['retirement_date']);

        // Extra Details
        $custom_1 = trim($_POST['customised_1']);
        $custom_2 = trim($_POST['customised_2']);
        $custom_3 = trim($_POST['customised_3']);
        $custom_4 = trim($_POST['customised_4']);
        $custom_5 = trim($_POST['customised_5']);
        $custom_6 = trim($_POST['customised_6']);
        $custom_7 = trim($_POST['customised_7']);
        $custom_8 = trim($_POST['customised_8']);
        $custom_9 = trim($_POST['customised_9']);
        $custom_10 = trim($_POST['customised_10']);

        // ✅ **Backend Validation Before Inserting**
        if (empty($full_name) || empty($email) || empty($contact_number) || empty($user_role)) {
            die("Error: Required fields are missing.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            die("Error: Invalid email format.");
        }

        if (!ctype_digit($contact_number) || strlen($contact_number) < 10) {
            die("Error: Contact number must be numeric and at least 10 digits.");
        }

        if (!empty($dob) && strtotime($dob) > time()) {
            die("Error: Date of Birth cannot be a future date.");
        }

        if (!empty($profile_expiry) && strtotime($profile_expiry) < time()) {
            die("Error: Profile Expiry Date cannot be in the past.");
        }

        // ✅ **Handle Profile Picture Upload**
        $profile_picture = "";
        if (!empty($_FILES["profile_picture"]["name"])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $profile_picture = $target_dir . basename($_FILES["profile_picture"]["name"]);
            $imageFileType = strtolower(pathinfo($profile_picture, PATHINFO_EXTENSION));

            // Validate file type and size
            if ($_FILES["profile_picture"]["size"] > 5242880) {
                die("Error: File size exceeds 5MB limit.");
            }
            if (!in_array($imageFileType, ["jpg", "png", "jpeg"])) {
                die("Error: Only JPG, PNG, and JPEG formats are allowed.");
            }

            if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $profile_picture)) {
                die("Error: Failed to upload profile picture.");
            }
        }

        // ✅ **Pass Validated Data to Model**
        $userModel = new UserModel();
        $result = $userModel->insertUser($_POST, $_FILES);

        if ($result) {
            echo "<script>alert('User added successfully!'); window.location.href = 'index.php?controller=UserManagementController';</script>";
        } else {
            die("Error inserting user.");
        }
      //  echo "<pre>"; print_r($_POST); echo "</pre>"; exit;
    }
    
    
}
?>
