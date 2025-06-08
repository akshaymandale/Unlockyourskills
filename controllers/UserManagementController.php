<?php
// controllers/UserManagementController.php
require_once 'models/UserModel.php';

class UserManagementController {
    
    private $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    public function index() {
        $limit = 10; // Number of records per page
        $page = 1;
        $offset = 0;

        // Load initial data (no search/filters applied)
        $users = $this->userModel->getAllUsersPaginated($limit, $offset);
        $totalUsers = $this->userModel->getTotalUserCount();
        $totalPages = ceil($totalUsers / $limit);

        // Get unique values for filter dropdowns
        $uniqueUserRoles = $this->userModel->getDistinctUserRoles();
        $uniqueGenders = $this->userModel->getDistinctGenders();

        require 'views/user_management.php';
    }
    
    public function addUser() {
        include 'views/add_user.php';
    }

    public function storeUser() {
        $client_id = trim($_POST['client_id']);
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

        // ✅ Backend Validations
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

        // ✅ Handle Profile Picture Upload
        $profile_picture = "";
        if (!empty($_FILES["profile_picture"]["name"])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $profile_picture = $target_dir . basename($_FILES["profile_picture"]["name"]);
            $imageFileType = strtolower(pathinfo($profile_picture, PATHINFO_EXTENSION));

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

        // ✅ Pass Validated Data to Model
        $result = $this->userModel->insertUser($_POST, $_FILES);

        if ($result) {
            echo "<script>alert('User added successfully!'); window.location.href = 'index.php?controller=UserManagementController';</script>";
        } else {
            die("Error inserting user.");
        }
    }
    
    // ✅ Soft Delete Function in Controller
    public function deleteUser() {
        if (isset($_GET['id'])) {
            $profile_id = $_GET['id'];
            $result = $this->userModel->softDeleteUser($profile_id);

            if ($result) {
                echo "<script>alert('User deleted successfully.'); window.location.href='index.php?controller=UserManagementController';</script>";
            } else {
                echo "<script>alert('Failed to delete user.'); window.location.href='index.php?controller=UserManagementController';</script>";
            }
        }
    }
    // Lock and unlock user from actions 
    public function toggleLock() {
        if (isset($_GET['id']) && isset($_GET['status'])) {
            $profile_id = $_GET['id'];
            $new_status = $_GET['status']; // 1 for Lock, 0 for Unlock
    
            $userModel = new UserModel();
            $result = $userModel->updateLockStatus($profile_id, $new_status);
    
            if ($result) {
                echo "<script>alert('User lock status updated successfully.'); window.location.href='index.php?controller=UserManagementController';</script>";
            } else {
                echo "<script>alert('Failed to update user lock status.'); window.location.href='index.php?controller=UserManagementController';</script>";
            }
        } else {
            echo "<script>alert('Invalid request parameters.'); window.location.href='index.php?controller=UserManagementController';</script>";
        }
    }

    public function ajaxSearch() {
        header('Content-Type: application/json');

        try {
            $limit = 10;
            $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
            $offset = ($page - 1) * $limit;

            // Get search and filter parameters
            $search = trim($_POST['search'] ?? '');
            $filters = [];

            if (!empty($_POST['user_status'])) {
                $filters['user_status'] = $_POST['user_status'];
            }

            if (!empty($_POST['locked_status'])) {
                $filters['locked_status'] = $_POST['locked_status'];
            }

            if (!empty($_POST['user_role'])) {
                $filters['user_role'] = $_POST['user_role'];
            }

            if (!empty($_POST['gender'])) {
                $filters['gender'] = $_POST['gender'];
            }

            // Get users from database
            $users = $this->userModel->getAllUsersPaginated($limit, $offset, $search, $filters);
            $totalUsers = $this->userModel->getTotalUserCount($search, $filters);
            $totalPages = ceil($totalUsers / $limit);

            $response = [
                'success' => true,
                'users' => $users,
                'totalUsers' => $totalUsers,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalUsers' => $totalUsers
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error loading users: ' . $e->getMessage()
            ]);
        }
        exit();
    }
}
?>
