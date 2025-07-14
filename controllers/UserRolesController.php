<?php
require_once 'models/UserRoleModel.php';
require_once 'models/UserModel.php';
require_once 'models/ClientModel.php';
require_once 'controllers/BaseController.php';
require_once 'core/UrlHelper.php';
require_once 'core/IdEncryption.php';
require_once 'config/Localization.php';

class UserRolesController extends BaseController {
    private $userRoleModel;
    private $userModel;
    private $clientModel;

    public function __construct() {
        $this->userRoleModel = new UserRoleModel();
        $this->userModel = new UserModel();
        $this->clientModel = new ClientModel();
    }

    /**
     * Main User Roles & Permissions page
     */
    public function index() {
        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser) {
            UrlHelper::redirect('login');
            return;
        }

        // Only allow admin and super admin to manage user roles
        if (!in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied. You do not have permission to manage user roles.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        // Get client information
        $clientId = null;
        $client = null;
        
        if ($currentUser['system_role'] === 'admin') {
            // Client admin can only manage their own client's roles
            $clientId = $currentUser['client_id'];
            $client = $this->clientModel->getClientById($clientId);
            
            if (!$client) {
                $this->redirectWithToast('Client not found.', 'error', UrlHelper::url('dashboard'));
                return;
            }
        } elseif ($currentUser['system_role'] === 'super_admin') {
            // Super admin can manage roles for any client
            $targetClientId = $_GET['client_id'] ?? null;
            if ($targetClientId) {
                try {
                    $clientId = IdEncryption::getId($targetClientId);
                    $client = $this->clientModel->getClientById($clientId);
                } catch (Exception $e) {
                    $this->redirectWithToast('Invalid client ID.', 'error', UrlHelper::url('user-roles'));
                    return;
                }
            } else {
                // Default to first client if none selected
                $clientsList = $this->clientModel->getAllClients();
                if (!empty($clientsList)) {
                    $clientId = $clientsList[0]['id'];
                    $client = $clientsList[0];
                }
            }
        }
        if (!$clientId) {
            $this->redirectWithToast('No client selected.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        // Get all roles for this client
        $roles = $this->userRoleModel->getAllRoles($clientId);
        
        // Get user count for each role
        foreach ($roles as &$role) {
            $role['user_count'] = $this->userModel->getUserCountByRole($role['role_name'], $clientId);
        }
        unset($role); // Break the reference to avoid view variable scoping issues

        // Get all clients for super admin
        $clients = [];
        if ($currentUser['system_role'] === 'super_admin') {
            $clients = $this->clientModel->getAllClients();
        }

        // Define available modules for permissions
        $modules = [
            'user_management' => [
                'name' => 'User Management',
                'icon' => 'fas fa-users-cog',
                'description' => 'Create, edit, and manage users'
            ],
            'course_management' => [
                'name' => 'Course Management',
                'icon' => 'fas fa-book-open',
                'description' => 'Create and manage courses'
            ],
            'reports' => [
                'name' => 'Reports',
                'icon' => 'fas fa-chart-bar',
                'description' => 'View and generate reports'
            ],
            'social_feed' => [
                'name' => 'Social Feed',
                'icon' => 'fas fa-comments',
                'description' => 'Access to social feed features'
            ],
            'announcements' => [
                'name' => 'Announcements',
                'icon' => 'fas fa-bullhorn',
                'description' => 'Create and manage announcements'
            ],
            'events' => [
                'name' => 'Events',
                'icon' => 'fas fa-calendar-alt',
                'description' => 'Create and manage events'
            ],
            'opinion_polls' => [
                'name' => 'Opinion Polls',
                'icon' => 'fas fa-poll',
                'description' => 'Create and manage opinion polls'
            ],
            'vlr' => [
                'name' => 'VLR Management',
                'icon' => 'fas fa-file-alt',
                'description' => 'Manage VLR content'
            ],
            'settings' => [
                'name' => 'Settings',
                'icon' => 'fas fa-cog',
                'description' => 'Access to system settings'
            ]
        ];

        require 'views/settings/user_roles.php';
    }

    /**
     * Create new role
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithToast('Invalid request method.', 'error', UrlHelper::url('user-roles'));
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        $roleName = trim($_POST['role_name'] ?? '');
        $systemRole = trim($_POST['system_role'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $displayOrder = intval($_POST['display_order'] ?? 999);

        // Validation
        if (empty($roleName) || empty($systemRole)) {
            $this->redirectWithToast('Role name and system role are required.', 'error', UrlHelper::url('user-roles'));
            return;
        }

        $clientId = $currentUser['system_role'] === 'admin' ? $currentUser['client_id'] : null;
        if ($currentUser['system_role'] === 'super_admin') {
            $targetClientId = $_POST['client_id'] ?? ($_GET['client_id'] ?? null);
            if ($targetClientId) {
                $clientId = IdEncryption::getId($targetClientId);
            }
        }
        if (!$clientId) {
            $this->redirectWithToast('No client selected.', 'error', UrlHelper::url('user-roles'));
            return;
        }

        // Check if role name already exists
        if ($this->userRoleModel->getRoleByName($roleName, $clientId)) {
            $this->redirectWithToast('Role name already exists.', 'error', UrlHelper::url('user-roles'));
            return;
        }

        $roleData = [
            'role_name' => $roleName,
            'system_role' => $systemRole,
            'description' => $description,
            'display_order' => $displayOrder
        ];

        if ($this->userRoleModel->createRole($roleData, $clientId)) {
            $this->redirectWithToast('Role created successfully!', 'success', UrlHelper::url('user-roles'));
        } else {
            $this->redirectWithToast('Failed to create role.', 'error', UrlHelper::url('user-roles'));
        }
    }

    /**
     * Update role
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithToast('Invalid request method.', 'error', UrlHelper::url('user-roles'));
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        $roleId = intval($_POST['role_id'] ?? 0);
        $roleName = trim($_POST['role_name'] ?? '');
        $systemRole = trim($_POST['system_role'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $displayOrder = intval($_POST['display_order'] ?? 999);

        if (!$roleId || empty($roleName) || empty($systemRole)) {
            $this->redirectWithToast('Invalid role data.', 'error', UrlHelper::url('user-roles'));
            return;
        }

        $clientId = $currentUser['system_role'] === 'admin' ? $currentUser['client_id'] : null;
        if ($currentUser['system_role'] === 'super_admin') {
            $targetClientId = $_POST['client_id'] ?? ($_GET['client_id'] ?? null);
            if ($targetClientId) {
                $clientId = IdEncryption::getId($targetClientId);
            }
        }
        if (!$clientId) {
            $this->redirectWithToast('No client selected.', 'error', UrlHelper::url('user-roles'));
            return;
        }

        $roleData = [
            'role_name' => $roleName,
            'system_role' => $systemRole,
            'description' => $description,
            'display_order' => $displayOrder
        ];

        if ($this->userRoleModel->updateRole($roleId, $roleData, $clientId)) {
            $this->redirectWithToast('Role updated successfully!', 'success', UrlHelper::url('user-roles'));
        } else {
            $this->redirectWithToast('Failed to update role.', 'error', UrlHelper::url('user-roles'));
        }
    }

    /**
     * Delete role
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithToast('Invalid request method.', 'error', UrlHelper::url('user-roles'));
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        $roleId = intval($_POST['role_id'] ?? 0);
        if (!$roleId) {
            $this->redirectWithToast('Role ID is required.', 'error', UrlHelper::url('user-roles'));
            return;
        }

        $clientId = $currentUser['system_role'] === 'admin' ? $currentUser['client_id'] : null;
        if ($currentUser['system_role'] === 'super_admin') {
            $targetClientId = $_POST['client_id'] ?? ($_GET['client_id'] ?? null);
            if ($targetClientId) {
                $clientId = IdEncryption::getId($targetClientId);
            }
        }
        if (!$clientId) {
            $this->redirectWithToast('No client selected.', 'error', UrlHelper::url('user-roles'));
            return;
        }

        // Check if role is in use
        $role = $this->userRoleModel->getRoleById($roleId, $clientId);
        if (!$role) {
            $this->redirectWithToast('Role not found.', 'error', UrlHelper::url('user-roles'));
            return;
        }

        $userCount = $this->userModel->getUserCountByRole($role['role_name'], $clientId);
        if ($userCount > 0) {
            $this->redirectWithToast("Cannot delete role. It is assigned to {$userCount} user(s).", 'error', UrlHelper::url('user-roles'));
            return;
        }

        if ($this->userRoleModel->deactivateRole($roleId)) {
            $this->redirectWithToast('Role deleted successfully!', 'success', UrlHelper::url('user-roles'));
        } else {
            $this->redirectWithToast('Failed to delete role.', 'error', UrlHelper::url('user-roles'));
        }
    }

    /**
     * Toggle role status
     */
    public function toggleStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $roleId = intval($_POST['role_id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);

        if (!$roleId) {
            $this->jsonResponse(['success' => false, 'message' => 'Role ID is required']);
            return;
        }

        $result = $status ? $this->userRoleModel->activateRole($roleId) : $this->userRoleModel->deactivateRole($roleId);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Role status updated successfully']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update role status']);
        }
    }

    /**
     * Save module permissions
     */
    public function savePermissions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Access denied']);
            return;
        }

        // Get JSON input
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['permissions'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid data format']);
            return;
        }

        $permissions = $data['permissions'];
        
        $clientId = $currentUser['system_role'] === 'admin' ? $currentUser['client_id'] : null;
        if ($currentUser['system_role'] === 'super_admin') {
            $targetClientId = $_GET['client_id'] ?? null;
            if ($targetClientId) {
                $clientId = IdEncryption::getId($targetClientId);
            }
        }
        if (!$clientId) {
            $this->jsonResponse(['success' => false, 'message' => 'No client selected.']);
            return;
        }

        // Save permissions to database
        $result = $this->userRoleModel->saveModulePermissions($permissions, $clientId);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Permissions saved successfully']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to save permissions']);
        }
    }

    /**
     * Get role by ID (AJAX)
     */
    public function getRole() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Access denied']);
            return;
        }

        $roleId = intval($_POST['role_id'] ?? 0);
        if (!$roleId) {
            $this->jsonResponse(['success' => false, 'message' => 'Role ID is required']);
            return;
        }

        $clientId = $currentUser['system_role'] === 'admin' ? $currentUser['client_id'] : null;
        if ($currentUser['system_role'] === 'super_admin') {
            $targetClientId = $_POST['client_id'] ?? ($_GET['client_id'] ?? null);
            if ($targetClientId) {
                $clientId = IdEncryption::getId($targetClientId);
            }
        }
        if (!$clientId) {
            $this->jsonResponse(['success' => false, 'message' => 'No client selected.']);
            return;
        }

        $role = $this->userRoleModel->getRoleById($roleId, $clientId);
        if (!$role) {
            $this->jsonResponse(['success' => false, 'message' => 'Role not found']);
            return;
        }

        $this->jsonResponse(['success' => true, 'role' => $role]);
    }

    /**
     * Helper method to send JSON response
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
} 