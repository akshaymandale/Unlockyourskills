<?php
// controllers/UserManagementController.php
require_once 'models/UserModel.php';
require_once 'models/ClientModel.php';
require_once 'controllers/BaseController.php';
require_once 'core/UrlHelper.php';
require_once 'core/IdEncryption.php';

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

        // Sanitize and validate client_id
        if (isset($_GET['client_id']) && is_numeric($_GET['client_id'])) {
            $clientId = (int)$_GET['client_id'];
        } else {
            $clientId = null;
        }
        $currentUser = $_SESSION['user'] ?? null;

        // Set client management context if super admin is navigating from client management
        if ($clientId && $currentUser && $currentUser['system_role'] === 'super_admin') {
            $_SESSION['client_management_context'] = true;
            $_SESSION['target_client_id'] = $clientId;
            $_SESSION['client_management_timestamp'] = time();
        }

        // Clear client management mode if not coming from client management
        if (!isset($_GET['client_id'])) {
            unset($_SESSION['client_management_mode']);
        }

        // Allow all user roles to access user management
        if ($currentUser) {
            $clientId = $currentUser['client_id'];
            $users = $this->userModel->getUsersByClient($clientId, $limit, $offset);
            $totalUsers = count($this->userModel->getUsersByClient($clientId, 999999, 0));
            $client = $this->clientModel->getClientById($clientId);
            $clients = [$client];
        } else {
            // Not logged in
            $this->toastError('Access denied. Please check your login status.', 'index.php');
            return;
        }

        $totalPages = ceil($totalUsers / $limit);

        // Get unique values for filter dropdowns
        $uniqueUserRoles = $this->userModel->getDistinctUserRoles();
        $uniqueGenders = $this->userModel->getDistinctGenders();

        // Get current user's client ID for user limit check
        $currentUserClientId = $_SESSION['user']['client_id'] ?? null;
        $userLimitStatus = null;

        // Use target client ID for user limit check if filtering by specific client
        $targetClientIdForLimit = $clientId ?: $currentUserClientId;
        if ($targetClientIdForLimit) {
            $userLimitStatus = $this->userModel->getUserLimitStatus($targetClientIdForLimit);
        }

        // Get custom field creation setting for current client
        $customFieldCreationEnabled = false;
        if ($client) {
            $customFieldCreationEnabled = $client['custom_field_creation'] == 1;
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
            $this->redirectWithToast('User ID is required.', 'error', UrlHelper::url('users'));
            return;
        }

        try {
            // Handle both encrypted and plain IDs for backward compatibility
            $profile_id = IdEncryption::getId($_GET['id']);
        } catch (InvalidArgumentException $e) {
            $this->redirectWithToast('Invalid user ID.', 'error', UrlHelper::url('users'));
            return;
        }

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
            $this->redirectWithToast('User not found or access denied.', 'error', UrlHelper::url('users'));
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
                $redirectUrl = UrlHelper::url('users/create') . '?client_id=' . $target_client_id;
                $this->redirectWithToast('Super admin can only add Admin role users for client management.', 'error', $redirectUrl);
                return;
            }
        }

        // Check client user limit
        $client = $this->clientModel->getClientById($finalClientId);
        if ($client && !$this->userModel->canClientAddUser($finalClientId)) {
            $redirectUrl = UrlHelper::url('users/create');
            if ($target_client_id) {
                $redirectUrl .= '?client_id=' . $target_client_id;
            }
            $this->redirectWithToast('Cannot add user. Client has reached its user limit.', 'error', $redirectUrl);
            return;
        }

        // Check admin role limit if user role is Admin
        $user_role = trim($_POST['user_role'] ?? '');
        if ($user_role === 'Admin' && $client && !$this->userModel->canClientAddAdmin($finalClientId)) {
            $redirectUrl = UrlHelper::url('users/create');
            if ($target_client_id) {
                $redirectUrl .= '?client_id=' . $target_client_id;
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

        if (empty($reports_to)) {
            die("Error: Report to (email) is required.");
        }

        if (!filter_var($reports_to, FILTER_VALIDATE_EMAIL)) {
            die("Error: Invalid email format for Report to field.");
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
                $redirectUrl = UrlHelper::url('users');
                if ($target_client_id) {
                    $redirectUrl .= '?client_id=' . $target_client_id;
                }
                $this->redirectWithToast('User added successfully!', 'success', $redirectUrl);
            } else {
                $redirectUrl = UrlHelper::url('users');
                if ($target_client_id) {
                    $redirectUrl .= '?client_id=' . $target_client_id;
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
            echo "<script>alert('User ID is required.'); window.location.href='" . UrlHelper::url('users') . "';</script>";
            return;
        }

        try {
            // Handle both encrypted and plain IDs for backward compatibility
            $profile_id = IdEncryption::getId(trim($_POST['profile_id']));
        } catch (InvalidArgumentException $e) {
            echo "<script>alert('Invalid user ID.'); window.location.href='" . UrlHelper::url('users') . "';</script>";
            return;
        }

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
            $this->redirectWithToast('User not found or access denied.', 'error', UrlHelper::url('users'));
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

        // Reports to validation
        $reports_to = trim($_POST['reports_to'] ?? '');
        if (empty($reports_to)) {
            echo "<script>alert('Error: Report to (email) is required.'); window.history.back();</script>";
            return;
        }
        if (!filter_var($reports_to, FILTER_VALIDATE_EMAIL)) {
            echo "<script>alert('Error: Invalid email format for Report to field.'); window.history.back();</script>";
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
            // Remove user_id from POST data since it should never be updated
            $updateData = $_POST;
            unset($updateData['user_id']);
            
            $result = $this->userModel->updateUser($profile_id, $updateData, $_FILES);

            if ($result) {
                $this->redirectWithToast('User updated successfully!', 'success', UrlHelper::url('users'));
            } else {
                $this->redirectWithToast('Error updating user.', 'error', UrlHelper::url('users'));
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
            try {
                // Handle both encrypted and plain IDs for backward compatibility
                $profile_id = IdEncryption::getId($_GET['id']);
            } catch (InvalidArgumentException $e) {
                $this->redirectWithToast('Invalid user ID.', 'error', UrlHelper::url('users'));
                return;
            }
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
                $this->redirectWithToast('User not found or access denied.', 'error', UrlHelper::url('users'));
                return;
            }

            $result = $this->userModel->softDeleteUser($profile_id);

            if ($result) {
                $this->redirectWithToast('User deleted successfully!', 'success', UrlHelper::url('users'));
            } else {
                $this->redirectWithToast('Failed to delete user.', 'error', UrlHelper::url('users'));
            }
        }
    }
    // Lock user
    public function lockUser() {
        if (isset($_GET['id'])) {
            try {
                // Handle both encrypted and plain IDs for backward compatibility
                $profile_id = IdEncryption::getId($_GET['id']);
            } catch (InvalidArgumentException $e) {
                $this->toastError('Invalid user ID.', UrlHelper::url('users'));
                return;
            }
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
                $this->toastError('User not found or access denied.', UrlHelper::url('users'));
                return;
            }

            $result = $this->userModel->updateLockStatus($profile_id, 1); // 1 = locked

            // Determine redirect URL
            $redirectUrl = UrlHelper::url('users');
            $clientContextId = $_GET['client_id'] ?? $_SESSION['target_client_id'] ?? null;
            if ($clientContextId) {
                $redirectUrl = UrlHelper::url('clients/' . $clientContextId . '/users');
            }

            if ($result) {
                $this->toastSuccess('User locked successfully!', $redirectUrl);
            } else {
                $this->toastError('Failed to lock user.', $redirectUrl);
            }
        } else {
            $this->toastError('Invalid request parameters.', UrlHelper::url('users'));
        }
    }

    // Unlock user
    public function unlockUser() {
        if (isset($_GET['id'])) {
            try {
                // Handle both encrypted and plain IDs for backward compatibility
                $profile_id = IdEncryption::getId($_GET['id']);
            } catch (InvalidArgumentException $e) {
                $this->toastError('Invalid user ID.', UrlHelper::url('users'));
                return;
            }
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
                $this->toastError('User not found or access denied.', UrlHelper::url('users'));
                return;
            }

            $result = $this->userModel->updateLockStatus($profile_id, 0); // 0 = unlocked

            // Determine redirect URL
            $redirectUrl = UrlHelper::url('users');
            $clientContextId = $_GET['client_id'] ?? $_SESSION['target_client_id'] ?? null;
            if ($clientContextId) {
                $redirectUrl = UrlHelper::url('clients/' . $clientContextId . '/users');
            }

            if ($result) {
                $this->toastSuccess('User unlocked successfully!', $redirectUrl);
            } else {
                $this->toastError('Failed to unlock user.', $redirectUrl);
            }
        } else {
            $this->toastError('Invalid request parameters.', UrlHelper::url('users'));
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
                $this->toastSuccess('User lock status updated successfully!', UrlHelper::url('users'));
            } else {
                $this->toastError('Failed to update user lock status.', UrlHelper::url('users'));
            }
        } else {
            $this->toastError('Invalid request parameters.', UrlHelper::url('users'));
        }
    }

    public function ajaxSearch($clientId = null) {
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

            // Handle client filtering for super admin
            $currentUser = $_SESSION['user'] ?? null;
            
            // If clientId is passed as URL parameter, use it (for /clients/{id}/users/ajax/search)
            if ($clientId && is_numeric($clientId)) {
                // Client ID from URL parameter - use it directly
                // Set client management mode for this request
                $_SESSION['client_management_mode'] = true;
            } elseif ($currentUser && $currentUser['system_role'] === 'super_admin') {
                // Super admin can filter by client from POST data
                if (!empty($_POST['client_id']) && is_numeric($_POST['client_id'])) {
                    $clientId = $_POST['client_id'];
                }
            } elseif ($currentUser && $currentUser['system_role'] === 'admin') {
                // Client admin can only see users from their client
                $clientId = $currentUser['client_id'];
            }

            // Get users from database based on client context
            if ($clientId) {
                if (!empty($_SESSION['client_management_mode'])) {
                    // In client management mode, show only Admin users for that client
                    $users = $this->userModel->getAdminUsersByClient($clientId, $limit, $offset, $search, $filters);
                    $totalUsers = count($this->userModel->getAdminUsersByClient($clientId, 999999, 0, $search, $filters));
                } else {
                    // Otherwise, show all users for the client
                    $users = $this->userModel->getUsersByClient($clientId, $limit, $offset, $search, $filters);
                    $totalUsers = count($this->userModel->getUsersByClient($clientId, 999999, 0, $search, $filters));
                }
            } else {
                // Super admin viewing their own client's users (not all users)
                $currentUserClientId = $currentUser['client_id'];
                $users = $this->userModel->getUsersByClient($currentUserClientId, $limit, $offset, $search, $filters);
                $totalUsers = count($this->userModel->getUsersByClient($currentUserClientId, 999999, 0, $search, $filters));
            }
            
            $totalPages = ceil($totalUsers / $limit);

            // Add encrypted IDs to users for secure URL generation
            foreach ($users as &$user) {
                if (isset($user['id']) && is_numeric($user['id'])) {
                    $user['encrypted_id'] = IdEncryption::encrypt($user['id']);
                } else {
                    $user['encrypted_id'] = null;
                }
            }

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
        UrlHelper::redirect('users');
    }

    // ===================================
    // ROUTING-COMPATIBLE METHODS
    // ===================================

    /**
     * Add user - routing compatible method
     * Maps to: GET /users/create
     */
    public function add() {
        return $this->addUser();
    }

    /**
     * Save user - routing compatible method
     * Maps to: POST /users
     */
    public function save() {
        return $this->storeUser();
    }

    /**
     * Edit user - routing compatible method
     * Maps to: GET /users/{id}/edit
     */
    public function edit($id = null) {
        if ($id) {
            $_GET['id'] = $id;
        }
        return $this->editUser();
    }

    /**
     * Update user - routing compatible method
     * Maps to: PUT /users/{id}
     */
    public function update($id = null) {
        if ($id) {
            $_POST['profile_id'] = $id;
        }
        return $this->updateUser();
    }

    /**
     * Delete user - routing compatible method
     * Maps to: DELETE /users/{id}
     */
    public function delete($id = null) {
        if ($id) {
            $_GET['id'] = $id;
        }
        return $this->deleteUser();
    }

    /**
     * Lock user - routing compatible method
     * Maps to: POST /users/{id}/lock
     */
    public function lock($id = null) {
        if ($id) {
            $_GET['id'] = $id;
        }
        return $this->lockUser();
    }

    /**
     * Unlock user - routing compatible method
     * Maps to: POST /users/{id}/unlock
     */
    public function unlock($id = null) {
        if ($id) {
            $_GET['id'] = $id;
        }
        return $this->unlockUser();
    }

    /**
     * Client users - for super admin viewing users of specific client
     * Maps to: GET /clients/{id}/users
     */
    public function clientUsers($clientId = null) {
        if ($clientId) {
            $_GET['client_id'] = $clientId;
            $_SESSION['client_management_mode'] = true;
        }
        return $this->index();
    }

    // ===================================
    // MODAL CONTENT METHODS
    // ===================================

    /**
     * Load Add User Modal Content
     */
    public function loadAddUserModal() {
        header('Content-Type: text/html; charset=UTF-8');

        try {
            // Pass any client_id parameter
            if (isset($_GET['client_id'])) {
                $_GET['client_id'] = $_GET['client_id'];
            }

            ob_start();
            include 'views/modals/add_user_modal_content.php';
            $content = ob_get_clean();

            echo $content;

        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error loading form: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        exit();
    }

    /**
     * Load Edit User Modal Content
     */
    public function loadEditUserModal() {
        header('Content-Type: text/html; charset=UTF-8');

        try {
            if (!isset($_GET['user_id'])) {
                throw new Exception('User ID is required');
            }

            // Decrypt the user ID
            $userId = IdEncryption::getId($_GET['user_id']);

            // Get current user for access control
            $sessionUser = $_SESSION['user'] ?? null;
            $clientId = null;
            if ($sessionUser && $sessionUser['system_role'] === 'admin') {
                $clientId = $sessionUser['client_id'];
            }

            // Fetch user data
            $user = $this->userModel->getUserById($userId, $clientId);
            if (!$user) {
                throw new Exception('User not found or access denied');
            }

            // Fetch all countries for the dropdown
            require_once 'config/database.php';
            $database = new Database();
            $db = $database->connect();

            if (!$db) {
                throw new Exception("Database connection failed");
            }

            $stmt = $db->query("SELECT id, name FROM countries");
            $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get user roles
            $userRoles = $this->userModel->getClientUserRoles($user['client_id']);
            $adminRoles = $this->userModel->getAdminUserRoles($user['client_id']);

            // Get admin role status for the user's client
            $adminRoleStatus = null;
            if ($user['client_id']) {
                $adminRoleStatus = $this->userModel->getAdminRoleStatus($user['client_id']);
            }

            // Get languages
            $languages = $this->userModel->getLanguages();

            ob_start();
            include 'views/modals/edit_user_modal_content.php';
            $content = ob_get_clean();

            echo $content;

        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Error loading form: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        exit();
    }

    /**
     * Handle Add User Modal Form Submission
     */
    public function submitAddUserModal() {
        // Debug logging
        error_log("UserManagementController::submitAddUserModal called");
        error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));
        error_log("Session data: " . print_r($_SESSION, true));
        
        header('Content-Type: application/json');

        try {
            // Server-side validation first
            $errors = $this->validateUserData($_POST, $_FILES);
            
            // Specific validation for super admin in client context
            $currentUser = $_SESSION['user'] ?? null;
            if (
                $currentUser && $currentUser['system_role'] === 'super_admin' && 
                !empty($_POST['target_client_id']) && 
                $_POST['user_role'] !== 'Admin'
            ) {
                $errors[] = "Super admin can only create 'Admin' role users from client management.";
                }

            if (!empty($errors)) {
                echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
                exit;
            }

            // If validation passes, attempt to insert the user
            if ($this->userModel->insertUser($_POST, $_FILES)) {
                 echo json_encode(['success' => true, 'message' => 'User added successfully!']);
            } else {
                throw new Exception("Failed to insert user into database.");
            }

        } catch (Exception $e) {
            error_log("Error in submitAddUserModal: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()]);
            }
        exit;
    }

    private function validateUserData($postData, $fileData) {
        $errors = [];
        if (empty($postData['full_name'])) $errors[] = 'Full name is required.';
        if (empty($postData['email'])) $errors[] = 'Email is required.';
        if (!filter_var($postData['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
        if (empty($postData['contact_number'])) $errors[] = 'Contact number is required.';
        if (empty($postData['user_role'])) $errors[] = 'User role is required.';
        
        // Reports to validation
        if (empty($postData['reports_to'])) $errors[] = 'Report to (email) is required.';
        if (!empty($postData['reports_to']) && !filter_var($postData['reports_to'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format for Report to field.';
        }
        
        // Add more validation rules as needed from your storeUser method...

        return $errors;
    }

    /**
     * Handle Edit User Modal Form Submission
     */
    public function submitEditUserModal() {
        // Debug logging
        error_log("UserManagementController::submitEditUserModal called");
        error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));
        error_log("FILES data: " . print_r($_FILES, true));
        error_log("Session data: " . print_r($_SESSION, true));
        
        header('Content-Type: application/json');

        try {
            // Validate required fields
            $fieldErrors = [];
            $errors = [];

            // Get and validate user ID
            if (!isset($_POST['user_id'])) {
                error_log("UserManagementController::submitEditUserModal - user_id not found in POST data");
                echo json_encode([
                    'success' => false,
                    'message' => 'User ID is required.'
                ]);
                exit();
            }

            try {
                // Decrypt the user_id to get the numeric ID
                $userId = IdEncryption::getId(trim($_POST['user_id']));
            } catch (InvalidArgumentException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid user ID.'
                ]);
                exit();
            }

            // Validate required fields
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $contact_number = trim($_POST['contact_number'] ?? '');
            $user_role = trim($_POST['user_role'] ?? '');

            // Full name validation
            if (empty($full_name)) {
                $fieldErrors['full_name'] = 'Full name is required';
                $errors[] = 'Full name is required';
            }

            // Email validation
            if (empty($email)) {
                $fieldErrors['email'] = 'Email is required';
                $errors[] = 'Email is required';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $fieldErrors['email'] = 'Please enter a valid email address';
                $errors[] = 'Please enter a valid email address';
            }

            // Contact number validation
            if (empty($contact_number)) {
                $fieldErrors['contact_number'] = 'Contact number is required';
                $errors[] = 'Contact number is required';
            } elseif (!preg_match('/^[\d\s\-\(\)\+]{10,15}$/', $contact_number)) {
                $fieldErrors['contact_number'] = 'Please enter a valid 10-digit contact number';
                $errors[] = 'Please enter a valid 10-digit contact number';
            }

            // User role validation
            if (empty($user_role)) {
                $fieldErrors['user_role'] = 'User role is required';
                $errors[] = 'User role is required';
            }

            // Reports to validation
            $reports_to = trim($_POST['reports_to'] ?? '');
            if (empty($reports_to)) {
                $fieldErrors['reports_to'] = 'Report to (email) is required';
                $errors[] = 'Report to (email) is required';
            } elseif (!filter_var($reports_to, FILTER_VALIDATE_EMAIL)) {
                $fieldErrors['reports_to'] = 'Please enter a valid email address for Report to';
                $errors[] = 'Please enter a valid email address for Report to';
            }

            // Date of birth validation
            $dob = trim($_POST['dob'] ?? '');
            if (!empty($dob)) {
                $today = date('Y-m-d');
                if ($dob > $today) {
                    $fieldErrors['dob'] = 'Date of birth cannot be in the future';
                    $errors[] = 'Date of birth cannot be in the future';
                }
            }

            // Profile expiry validation
            $profile_expiry = trim($_POST['profile_expiry'] ?? '');
            if (!empty($profile_expiry)) {
                $today = date('Y-m-d');
                if ($profile_expiry < $today) {
                    $fieldErrors['profile_expiry'] = 'Profile expiry date cannot be in the past';
                    $errors[] = 'Profile expiry date cannot be in the past';
                }
            }

            // Check if user exists and get current data
            $sessionUser = $_SESSION['user'] ?? null;
            $clientId = null;
            if ($sessionUser && $sessionUser['system_role'] === 'admin') {
                $clientId = $sessionUser['client_id'];
            }

            $currentUser = $this->userModel->getUserById($userId, $clientId);
            if (!$currentUser) {
                echo json_encode([
                    'success' => false,
                    'message' => 'User not found or access denied.'
                ]);
                exit();
            }

            // Check admin role limit if changing to Admin role
            if ($user_role === 'Admin' && $currentUser['user_role'] !== 'Admin') {
                $clientId = $currentUser['client_id'];
                if (!$this->userModel->canClientAddAdmin($clientId, $userId)) {
                    $fieldErrors['user_role'] = 'Cannot change user role to Admin. Client has reached its admin role limit.';
                    $errors[] = 'Cannot change user role to Admin. Client has reached its admin role limit.';
                }
            }

            // Return validation errors if any
            if (!empty($errors)) {
                echo json_encode([
                    'success' => false,
                    'message' => implode('<br>', $errors),
                    'field_errors' => $fieldErrors
                ]);
                exit();
            }

            // Remove file upload/move logic from controller
            // Only validate file type/size if needed, but do not move or process the file
            if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
                $allowed_types = ["image/jpeg", "image/png", "image/jpg"];
                $max_size = 5 * 1024 * 1024; // 5MB

                if (!in_array($_FILES["profile_picture"]["type"], $allowed_types)) {
                    $fieldErrors['profile_picture'] = 'Only JPG and PNG images are allowed';
                    $errors[] = 'Only JPG and PNG images are allowed';
                } elseif ($_FILES["profile_picture"]["size"] > $max_size) {
                    $fieldErrors['profile_picture'] = 'Image size must be less than 5MB';
                    $errors[] = 'Image size must be less than 5MB';
                }

                // Return validation errors if any from file upload
                if (!empty($errors)) {
                    echo json_encode([
                        'success' => false,
                        'message' => implode('<br>', $errors),
                        'field_errors' => $fieldErrors
                    ]);
                    exit();
                }
            }

            // Update user data
            // Remove user_id from POST data since it should never be updated
            $updateData = $_POST;
            unset($updateData['user_id']);
            
            $result = $this->userModel->updateUser($userId, $updateData, $_FILES);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User updated successfully!'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update user. Please try again.'
                ]);
            }

        } catch (PDOException $e) {
            error_log("UserManagementController submitEditUserModal error: " . $e->getMessage());

            // Map database errors to field-specific errors
            $fieldErrors = [];
            $errorMessage = "Failed to update user. Please check your input and try again.";

            if (strpos($e->getMessage(), 'email') !== false) {
                $fieldErrors['email'] = 'Email address already exists';
                $errorMessage = 'Email address already exists';
            } elseif (strpos($e->getMessage(), 'profile_id') !== false) {
                $fieldErrors['profile_id'] = 'Profile ID already exists';
                $errorMessage = 'Profile ID already exists';
            }

            echo json_encode([
                'success' => false,
                'message' => $errorMessage,
                'field_errors' => $fieldErrors
            ]);
        } catch (Exception $e) {
            error_log("UserManagementController submitEditUserModal error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error updating user: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Map error messages to specific form fields for client-side display
     */
    private function getFieldSpecificErrors($message) {
        $fieldErrors = [];

        // Map common error messages to specific fields
        if (strpos($message, "full_name") !== false || strpos($message, "Full name") !== false) {
            $fieldErrors['full_name'] = $message;
        } elseif (strpos($message, "email") !== false || strpos($message, "Email") !== false) {
            $fieldErrors['email'] = $message;
        } elseif (strpos($message, "contact") !== false || strpos($message, "Contact") !== false) {
            $fieldErrors['contact_number'] = $message;
        } elseif (strpos($message, "user_role") !== false || strpos($message, "User role") !== false || strpos($message, "role") !== false) {
            $fieldErrors['user_role'] = $message;
        } elseif (strpos($message, "Date of Birth") !== false || strpos($message, "dob") !== false) {
            $fieldErrors['dob'] = $message;
        } elseif (strpos($message, "Profile Expiry") !== false || strpos($message, "profile_expiry") !== false) {
            $fieldErrors['profile_expiry'] = $message;
        } elseif (strpos($message, "File size") !== false || strpos($message, "file") !== false || strpos($message, "upload") !== false) {
            $fieldErrors['profile_picture'] = $message;
        }

        return $fieldErrors;
    }



    /**
     * Get user emails for autocomplete search in reports_to field
     */
    public function getUserEmailsForAutocomplete() {
        header('Content-Type: application/json');
        
        try {
            // Get current user for client isolation
            $currentUser = $_SESSION['user'] ?? null;
            if (!$currentUser) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                return;
            }

            // Get search term
            $searchTerm = trim($_GET['q'] ?? '');
            
            // Get client ID for isolation
            $clientId = null;
            if ($currentUser['system_role'] === 'admin') {
                $clientId = $currentUser['client_id'];
            } elseif ($currentUser['system_role'] === 'super_admin') {
                // For super_admin, use their client_id if available, or allow access to all
                $clientId = $currentUser['client_id'] ?? null;
            } else {
                // For other roles, use their client_id
                $clientId = $currentUser['client_id'] ?? null;
            }
            
            // If client_id is passed via GET parameter, use it (for autocomplete context)
            if (isset($_GET['client_id']) && !empty($_GET['client_id'])) {
                $clientId = $_GET['client_id'];
            }

            // Fetch user emails from database (temporarily without performance parameters)
            $emails = $this->userModel->getUserEmailsForAutocomplete($searchTerm, $clientId);
            
            echo json_encode([
                'success' => true,
                'emails' => $emails
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching user emails for autocomplete: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching user emails'
            ]);
        }
    }

    /**
     * Show organizational hierarchy page
     */
    public function showOrganizationalHierarchy() {
        try {
            // Get current user for client isolation
            $currentUser = $_SESSION['user'] ?? null;
            if (!$currentUser) {
                header('Location: ' . UrlHelper::url('login'));
                exit();
            }

            // Get client ID for isolation
            $clientId = null;
            if ($currentUser['system_role'] === 'admin') {
                $clientId = $currentUser['client_id'];
            } elseif ($currentUser['system_role'] === 'super_admin') {
                // For super_admin, require a specific client_id to be provided
                $clientId = $_GET['client_id'] ?? null;
                if (!$clientId) {
                    echo "Error: Client ID is required for super admin users. Please specify a client_id parameter.";
                    exit;
                }
            }

            // Get organizational hierarchy (use lightweight version for better performance)
            $hierarchy = $this->userModel->getLightweightOrganizationalHierarchy($clientId, 200);
            
            // Get current user's reporting chain
            $userReportingChain = $this->userModel->getUserReportingChain($currentUser['email'], $clientId);
            
            // Get current user's direct reports
            $userDirectReports = $this->userModel->getUserDirectReports($currentUser['email'], $clientId);

            // Include the view
            include __DIR__ . '/../views/organizational_hierarchy.php';
            
        } catch (Exception $e) {
            error_log("Error showing organizational hierarchy: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }
    }

    /**
     * Get organizational hierarchy data via AJAX
     */
    public function getOrganizationalHierarchyData() {
        header('Content-Type: application/json');
        
        try {
            // Get current user for client isolation
            $currentUser = $_SESSION['user'] ?? null;
            if (!$currentUser) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                return;
            }

            // Get client ID for isolation
            $clientId = null;
            if ($currentUser['system_role'] === 'admin') {
                $clientId = $currentUser['client_id'];
            } elseif ($currentUser['system_role'] === 'super_admin') {
                // For super_admin, require a specific client_id to be provided
                $clientId = $_GET['client_id'] ?? null;
                if (!$clientId) {
                    echo json_encode(['success' => false, 'message' => 'Client ID is required for super admin users']);
                    return;
                }
            }

            // Debug logging
            error_log("Organizational Hierarchy Debug - User: " . json_encode($currentUser));
            error_log("Organizational Hierarchy Debug - Client ID: " . ($clientId ?? 'null'));

            // Get organizational hierarchy (use lightweight version for better performance)
            $hierarchy = $this->userModel->getLightweightOrganizationalHierarchy($clientId, 200);
            
            // Debug logging
            error_log("Organizational Hierarchy Debug - Hierarchy result: " . json_encode($hierarchy));
            
            echo json_encode([
                'success' => true,
                'hierarchy' => $hierarchy,
                'debug' => [
                    'user_role' => $currentUser['system_role'] ?? 'unknown',
                    'client_id' => $clientId,
                    'hierarchy_count' => count($hierarchy)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching organizational hierarchy: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching organizational hierarchy'
            ]);
        }
    }
}
?>
