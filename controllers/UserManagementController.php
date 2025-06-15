<?php
// controllers/UserManagementController.php
require_once 'models/UserModel.php';
require_once 'controllers/BaseController.php';

class UserManagementController extends BaseController {
    
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

    public function editUser() {
        if (!isset($_GET['id'])) {
            $this->redirectWithToast('User ID is required.', 'error', 'index.php?controller=UserManagementController');
            return;
        }

        $profile_id = $_GET['id'];
        $user = $this->userModel->getUserById($profile_id);

        if (!$user) {
            $this->redirectWithToast('User not found.', 'error', 'index.php?controller=UserManagementController');
            return;
        }

        // Fetch countries for dropdown
        require_once 'config/database.php';
        $database = new Database();
        $db = $database->connect();

        if ($db) {
            $stmt = $db->query("SELECT id, name FROM countries");
            $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $countries = [];
        }

        include 'views/edit_user.php';
    }

    public function storeUser() {
        $client_id = trim($_POST['client_id'] ?? '');
        $profile_id = trim($_POST['profile_id'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        $user_role = trim($_POST['user_role'] ?? '');
        $profile_expiry = trim($_POST['profile_expiry'] ?? '');
        $user_status = trim($_POST['user_status'] ?? '');
        $locked_status = trim($_POST['locked_status'] ?? '');
        $leaderboard = trim($_POST['leaderboard'] ?? '');
        
        // Additional Details (with null coalescing and safe trim)
        $country = trim($_POST['country'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $timezone = trim($_POST['timezone'] ?? '');
        $language = trim($_POST['language'] ?? '');
        $reports_to = trim($_POST['reports_to'] ?? '');
        $joining_date = trim($_POST['joining_date'] ?? '');
        $retirement_date = trim($_POST['retirement_date'] ?? '');

        // Extra Details (with null coalescing and safe trim)
        $custom_1 = trim($_POST['customised_1'] ?? '');
        $custom_2 = trim($_POST['customised_2'] ?? '');
        $custom_3 = trim($_POST['customised_3'] ?? '');
        $custom_4 = trim($_POST['customised_4'] ?? '');
        $custom_5 = trim($_POST['customised_5'] ?? '');
        $custom_6 = trim($_POST['customised_6'] ?? '');
        $custom_7 = trim($_POST['customised_7'] ?? '');
        $custom_8 = trim($_POST['customised_8'] ?? '');
        $custom_9 = trim($_POST['customised_9'] ?? '');
        $custom_10 = trim($_POST['customised_10'] ?? '');

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

        // Validate gender field
        if (!empty($gender) && !in_array($gender, ['Male', 'Female', 'Other'])) {
            die("Error: Invalid gender value.");
        }

        // ✅ Handle Profile Picture Upload
        $profile_picture = "";
        if (!empty($_FILES["profile_picture"]["name"])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
                chmod($target_dir, 0777); // Ensure proper permissions
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
        try {
            $result = $this->userModel->insertUser($_POST, $_FILES);

            if ($result) {
                $this->redirectWithToast('User added successfully!', 'success', 'index.php?controller=UserManagementController');
            } else {
                $this->redirectWithToast('Error inserting user.', 'error', 'index.php?controller=UserManagementController');
            }
        } catch (PDOException $e) {
            // Log the error
            error_log("UserManagementController storeUser error: " . $e->getMessage());

            // Show user-friendly error message
            $errorMessage = "Failed to add user. Please check your input and try again.";
            if (strpos($e->getMessage(), 'gender') !== false) {
                $errorMessage = "Invalid gender value. Please select a valid gender option.";
            } elseif (strpos($e->getMessage(), 'email') !== false) {
                $errorMessage = "Email address is invalid or already exists.";
            } elseif (strpos($e->getMessage(), 'profile_id') !== false) {
                $errorMessage = "Profile ID already exists. Please use a different ID.";
            }

            $this->redirectWithToast($errorMessage, 'error', 'javascript:history.back()');
        } catch (Exception $e) {
            error_log("UserManagementController storeUser general error: " . $e->getMessage());
            $this->redirectWithToast('An unexpected error occurred. Please try again.', 'error', 'javascript:history.back()');
        }
    }

    public function updateUser() {
        if (!isset($_POST['profile_id'])) {
            echo "<script>alert('User ID is required.'); window.location.href='index.php?controller=UserManagementController';</script>";
            return;
        }

        $profile_id = trim($_POST['profile_id']);

        // Basic validation for required fields
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contact_number = trim($_POST['contact_number'] ?? '');
        $user_role = trim($_POST['user_role'] ?? '');

        // ✅ Backend Validations
        if (empty($full_name) || empty($email) || empty($contact_number) || empty($user_role)) {
            echo "<script>alert('Error: Required fields are missing.'); window.history.back();</script>";
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<script>alert('Error: Invalid email format.'); window.history.back();</script>";
            return;
        }

        if (!ctype_digit($contact_number) || strlen($contact_number) < 10) {
            echo "<script>alert('Error: Contact number must be numeric and at least 10 digits.'); window.history.back();</script>";
            return;
        }

        // Validate gender field
        $gender = trim($_POST['gender'] ?? '');
        if (!empty($gender) && !in_array($gender, ['Male', 'Female', 'Other'])) {
            echo "<script>alert('Error: Invalid gender value.'); window.history.back();</script>";
            return;
        }

        // Validate dates
        $dob = trim($_POST['dob'] ?? '');
        if (!empty($dob) && strtotime($dob) > time()) {
            echo "<script>alert('Error: Date of Birth cannot be a future date.'); window.history.back();</script>";
            return;
        }

        $profile_expiry = trim($_POST['profile_expiry'] ?? '');
        if (!empty($profile_expiry) && strtotime($profile_expiry) < time()) {
            echo "<script>alert('Error: Profile Expiry Date cannot be in the past.'); window.history.back();</script>";
            return;
        }

        // ✅ Handle Profile Picture Upload (optional for update)
        if (!empty($_FILES["profile_picture"]["name"])) {
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
                chmod($target_dir, 0777); // Ensure proper permissions
            }

            $profile_picture = $target_dir . basename($_FILES["profile_picture"]["name"]);
            $imageFileType = strtolower(pathinfo($profile_picture, PATHINFO_EXTENSION));

            if ($_FILES["profile_picture"]["size"] > 5242880) {
                echo "<script>alert('Error: File size exceeds 5MB limit.'); window.history.back();</script>";
                return;
            }
            if (!in_array($imageFileType, ["jpg", "png", "jpeg"])) {
                echo "<script>alert('Error: Only JPG, PNG, and JPEG formats are allowed.'); window.history.back();</script>";
                return;
            }

            if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $profile_picture)) {
                echo "<script>alert('Error: Failed to upload profile picture.'); window.history.back();</script>";
                return;
            }
        }

        // ✅ Update user data
        try {
            $result = $this->userModel->updateUser($profile_id, $_POST, $_FILES);

            if ($result) {
                $this->redirectWithToast('User updated successfully!', 'success', 'index.php?controller=UserManagementController');
            } else {
                $this->redirectWithToast('Error updating user.', 'error', 'index.php?controller=UserManagementController');
            }
        } catch (PDOException $e) {
            // Log the error
            error_log("UserManagementController updateUser error: " . $e->getMessage());

            // Show user-friendly error message
            $errorMessage = "Failed to update user. Please check your input and try again.";
            if (strpos($e->getMessage(), 'gender') !== false) {
                $errorMessage = "Invalid gender value. Please select a valid gender option.";
            } elseif (strpos($e->getMessage(), 'email') !== false) {
                $errorMessage = "Email address is invalid or already exists.";
            } elseif (strpos($e->getMessage(), 'profile_id') !== false) {
                $errorMessage = "Profile ID already exists. Please use a different ID.";
            }

            $this->redirectWithToast($errorMessage, 'error', 'javascript:history.back()');
        } catch (Exception $e) {
            error_log("UserManagementController updateUser general error: " . $e->getMessage());
            $this->redirectWithToast('An unexpected error occurred. Please try again.', 'error', 'javascript:history.back()');
        }
    }

    // ✅ Soft Delete Function in Controller
    public function deleteUser() {
        if (isset($_GET['id'])) {
            $profile_id = $_GET['id'];
            $result = $this->userModel->softDeleteUser($profile_id);

            if ($result) {
                $this->redirectWithToast('User deleted successfully!', 'success', 'index.php?controller=UserManagementController');
            } else {
                $this->redirectWithToast('Failed to delete user.', 'error', 'index.php?controller=UserManagementController');
            }
        }
    }
    // Lock user
    public function lockUser() {
        if (isset($_GET['id'])) {
            $profile_id = $_GET['id'];
            $result = $this->userModel->updateLockStatus($profile_id, 1); // 1 = locked

            if ($result) {
                $this->toastSuccess('User locked successfully!', 'index.php?controller=UserManagementController');
            } else {
                $this->toastError('Failed to lock user.', 'index.php?controller=UserManagementController');
            }
        } else {
            $this->toastError('Invalid request parameters.', 'index.php?controller=UserManagementController');
        }
    }

    // Unlock user
    public function unlockUser() {
        if (isset($_GET['id'])) {
            $profile_id = $_GET['id'];
            $result = $this->userModel->updateLockStatus($profile_id, 0); // 0 = unlocked

            if ($result) {
                $this->toastSuccess('User unlocked successfully!', 'index.php?controller=UserManagementController');
            } else {
                $this->toastError('Failed to unlock user.', 'index.php?controller=UserManagementController');
            }
        } else {
            $this->toastError('Invalid request parameters.', 'index.php?controller=UserManagementController');
        }
    }

    // Legacy method - Lock and unlock user from actions
    public function toggleLock() {
        if (isset($_GET['id']) && isset($_GET['status'])) {
            $profile_id = $_GET['id'];
            $new_status = $_GET['status']; // 1 for Lock, 0 for Unlock

            $userModel = new UserModel();
            $result = $userModel->updateLockStatus($profile_id, $new_status);

            if ($result) {
                $this->toastSuccess('User lock status updated successfully!', 'index.php?controller=UserManagementController');
            } else {
                $this->toastError('Failed to update user lock status.', 'index.php?controller=UserManagementController');
            }
        } else {
            $this->toastError('Invalid request parameters.', 'index.php?controller=UserManagementController');
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
