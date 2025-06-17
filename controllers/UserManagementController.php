<?php
// controllers/UserManagementController.php
require_once 'models/UserModel.php';
require_once 'models/ClientModel.php';
require_once 'controllers/BaseController.php';

class UserManagementController extends BaseController {

    private $userModel;
    private $clientModel;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->clientModel = new ClientModel();
    }

    public function index() {
        $limit = 10; // Number of records per page
        $page = 1;
        $offset = 0;

        // Check if filtering by client (for super admin)
        $clientId = $_GET['client_id'] ?? null;
        $currentUser = $_SESSION['user'] ?? null;

        // Set client management context if super admin is navigating from client management
        if ($clientId && $currentUser && $currentUser['system_role'] === 'super_admin') {
            $_SESSION['client_management_context'] = true;
            $_SESSION['target_client_id'] = $clientId;
            $_SESSION['client_management_timestamp'] = time();
        }

        // Determine user scope based on role
        if ($currentUser && $currentUser['system_role'] === 'super_admin') {
            // Super admin can see all users or filter by client
            if ($clientId) {
                $users = $this->userModel->getUsersByClient($clientId, $limit, $offset);
                $totalUsers = count($this->userModel->getUsersByClient($clientId, 999999, 0));
                $client = $this->clientModel->getClientById($clientId);
            } else {
                $users = $this->userModel->getAllUsersPaginated($limit, $offset);
                $totalUsers = $this->userModel->getTotalUserCount();
                $client = null;
            }
            $clients = $this->clientModel->getAllClients(999999, 0);
        } elseif ($currentUser && $currentUser['system_role'] === 'admin') {
            // Client admin can only see users from their client
            $clientId = $currentUser['client_id'];
            $users = $this->userModel->getUsersByClient($clientId, $limit, $offset);
            $totalUsers = count($this->userModel->getUsersByClient($clientId, 999999, 0));
            $client = $this->clientModel->getClientById($clientId);
            $clients = [$client]; // Only their client
        } else {
            // For debugging: show what we have in session
            error_log("UserManagementController access denied. Session data: " . print_r($_SESSION, true));
            error_log("Current user: " . print_r($currentUser, true));

            // Regular users shouldn't access user management
            $this->toastError('Access denied. Please check your login status and permissions.', 'index.php');
            return;
        }

        $totalPages = ceil($totalUsers / $limit);

        // Get unique values for filter dropdowns
        $uniqueUserRoles = $this->userModel->getDistinctUserRoles();
        $uniqueGenders = $this->userModel->getDistinctGenders();

        // Get current user's client ID for user limit check
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $userLimitStatus = null;

        if ($clientId) {
            $userLimitStatus = $this->userModel->getUserLimitStatus($clientId);
        }

        // Get custom field creation setting for current client
        $customFieldCreationEnabled = false;
        if ($currentUser && $currentUser['system_role'] === 'super_admin') {
            // For super admin, check if filtering by specific client
            if ($clientId && $client) {
                $customFieldCreationEnabled = $client['custom_field_creation'] == 1;
            } else {
                // Super admin viewing all users - enable custom fields by default
                $customFieldCreationEnabled = true;
            }
        } elseif ($currentUser && $currentUser['system_role'] === 'admin') {
            // For client admin, check their client's setting
            if ($client) {
                $customFieldCreationEnabled = $client['custom_field_creation'] == 1;
            }
        }

        require 'views/user_management.php';
    }
    
    public function addUser() {
        $currentUser = $_SESSION['user'] ?? null;

        // Determine target client for user creation
        $targetClientId = null;
        if (isset($_GET['client_id']) && $currentUser && $currentUser['system_role'] === 'super_admin') {
            // Super admin adding user for specific client
            $targetClientId = $_GET['client_id'];
        } else {
            // Regular admin adding user for their own client
            $targetClientId = $currentUser['client_id'] ?? null;
        }

        // Get admin role status for the target client
        $adminRoleStatus = null;
        if ($targetClientId) {
            $adminRoleStatus = $this->userModel->getAdminRoleStatus($targetClientId);
        }

        // Fetch languages for dropdown
        $languages = $this->userModel->getLanguages();

        // Fetch user roles from database
        $userRoles = $this->userModel->getClientUserRoles();
        $adminRoles = $this->userModel->getAdminUserRoles();

        include 'views/add_user.php';
    }

    public function editUser() {
        if (!isset($_GET['id'])) {
            $this->redirectWithToast('User ID is required.', 'error', 'index.php?controller=UserManagementController');
            return;
        }

        $profile_id = $_GET['id'];
        $currentUser = $_SESSION['user'] ?? null;

        // Determine client filtering based on user role
        $clientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            // Client admin can only edit users from their client
            $clientId = $currentUser['client_id'];
        }
        // Super admin can edit users from any client (no client filtering)

        $user = $this->userModel->getUserById($profile_id, $clientId);

        if (!$user) {
            $this->redirectWithToast('User not found or access denied.', 'error', 'index.php?controller=UserManagementController');
            return;
        }

        // Get admin role status for the user's client (excluding current user for edit)
        $clientId = $user['client_id'] ?? null;
        $adminRoleStatus = null;

        if ($clientId) {
            $adminRoleStatus = $this->userModel->getAdminRoleStatus($clientId, $profile_id);
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

        // Fetch languages for dropdown
        $languages = $this->userModel->getLanguages();

        // Fetch user roles from database
        $userRoles = $this->userModel->getClientUserRoles();
        $adminRoles = $this->userModel->getAdminUserRoles();

        include 'views/edit_user.php';
    }

    public function storeUser() {
        $client_id = trim($_POST['client_id'] ?? '');
        $target_client_id = trim($_POST['target_client_id'] ?? '');
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

        // Use target_client_id if provided (for super admin), otherwise use client_id
        $finalClientId = $target_client_id ?: $client_id;

        // Get current user for validation
        $currentUser = $_SESSION['user'] ?? null;

        // Validate: If super admin is adding for specific client, only Admin role is allowed
        if ($target_client_id && $currentUser && $currentUser['system_role'] === 'super_admin') {
            if ($user_role !== 'Admin') {
                $redirectUrl = 'index.php?controller=UserManagementController&action=addUser&client_id=' . $target_client_id;
                $this->redirectWithToast('Super admin can only add Admin role users for client management.', 'error', $redirectUrl);
                return;
            }
        }

        // Check client user limit
        $client = $this->clientModel->getClientById($finalClientId);
        if ($client && !$this->userModel->canClientAddUser($finalClientId)) {
            $redirectUrl = 'index.php?controller=UserManagementController&action=addUser';
            if ($target_client_id) {
                $redirectUrl .= '&client_id=' . $target_client_id;
            }
            $this->redirectWithToast('Cannot add user. Client has reached its user limit.', 'error', $redirectUrl);
            return;
        }

        // Check admin role limit if user role is Admin
        $user_role = trim($_POST['user_role'] ?? '');
        if ($user_role === 'Admin' && $client && !$this->userModel->canClientAddAdmin($finalClientId)) {
            $redirectUrl = 'index.php?controller=UserManagementController&action=addUser';
            if ($target_client_id) {
                $redirectUrl .= '&client_id=' . $target_client_id;
            }
            $this->redirectWithToast('Cannot add admin user. Client has reached its admin role limit.', 'error', $redirectUrl);
            return;
        }
        
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
                // Update client user count
                if ($client) {
                    $this->clientModel->updateUserCount($client['id']);
                }

                // Redirect back to appropriate user management view
                $redirectUrl = 'index.php?controller=UserManagementController';
                if ($target_client_id) {
                    $redirectUrl .= '&client_id=' . $target_client_id;
                }
                $this->redirectWithToast('User added successfully!', 'success', $redirectUrl);
            } else {
                $redirectUrl = 'index.php?controller=UserManagementController';
                if ($target_client_id) {
                    $redirectUrl .= '&client_id=' . $target_client_id;
                }
                $this->redirectWithToast('Error inserting user.', 'error', $redirectUrl);
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

        // Get current user data to check role change
        $sessionUser = $_SESSION['user'] ?? null;

        // Determine client filtering based on user role
        $clientId = null;
        if ($sessionUser && $sessionUser['system_role'] === 'admin') {
            // Client admin can only update users from their client
            $clientId = $sessionUser['client_id'];
        }

        $currentUser = $this->userModel->getUserById($profile_id, $clientId);
        if (!$currentUser) {
            $this->redirectWithToast('User not found or access denied.', 'error', 'index.php?controller=UserManagementController');
            return;
        }

        // Check admin role limit if changing to Admin role
        if ($user_role === 'Admin' && $currentUser['user_role'] !== 'Admin') {
            $clientId = $currentUser['client_id'];
            if (!$this->userModel->canClientAddAdmin($clientId, $profile_id)) {
                $this->redirectWithToast('Cannot change user role to Admin. Client has reached its admin role limit.', 'error', 'javascript:history.back()');
                return;
            }
        }

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
            $currentUser = $_SESSION['user'] ?? null;

            // Determine client filtering based on user role
            $clientId = null;
            if ($currentUser && $currentUser['system_role'] === 'admin') {
                // Client admin can only delete users from their client
                $clientId = $currentUser['client_id'];
            }

            // Verify user exists and belongs to the correct client
            $userToDelete = $this->userModel->getUserById($profile_id, $clientId);
            if (!$userToDelete) {
                $this->redirectWithToast('User not found or access denied.', 'error', 'index.php?controller=UserManagementController');
                return;
            }

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
            $currentUser = $_SESSION['user'] ?? null;

            // Determine client filtering based on user role
            $clientId = null;
            if ($currentUser && $currentUser['system_role'] === 'admin') {
                // Client admin can only lock users from their client
                $clientId = $currentUser['client_id'];
            }

            // Verify user exists and belongs to the correct client
            $userToLock = $this->userModel->getUserById($profile_id, $clientId);
            if (!$userToLock) {
                $this->toastError('User not found or access denied.', 'index.php?controller=UserManagementController');
                return;
            }

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
            $currentUser = $_SESSION['user'] ?? null;

            // Determine client filtering based on user role
            $clientId = null;
            if ($currentUser && $currentUser['system_role'] === 'admin') {
                // Client admin can only unlock users from their client
                $clientId = $currentUser['client_id'];
            }

            // Verify user exists and belongs to the correct client
            $userToUnlock = $this->userModel->getUserById($profile_id, $clientId);
            if (!$userToUnlock) {
                $this->toastError('User not found or access denied.', 'index.php?controller=UserManagementController');
                return;
            }

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

    /**
     * Clear client management context
     */
    public function clearClientContext() {
        unset($_SESSION['client_management_context']);
        unset($_SESSION['target_client_id']);
        unset($_SESSION['client_management_timestamp']);

        // Redirect to regular user management
        header('Location: index.php?controller=UserManagementController');
        exit();
    }

}
?>
