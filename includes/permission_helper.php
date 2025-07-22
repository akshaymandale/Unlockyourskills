<?php
/**
 * Centralized Permission Helper for Role-Based Access Control (RBAC)
 * Handles both backend and frontend permissions consistently
 */

require_once 'models/UserRoleModel.php';

class PermissionHelper {
    private static $instance = null;
    private $userRoleModel;
    private $currentUser;
    private $userPermissions = [];
    private $modules = [];
    
    private function __construct() {
        $this->userRoleModel = new UserRoleModel();
        $this->currentUser = $_SESSION['user'] ?? null;
        $this->initializeModules();
        $this->loadUserPermissions();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize all available modules with their permissions
     */
    private function initializeModules() {
        $this->modules = [
            'user_management' => [
                'name' => 'User Management',
                'description' => 'Manage users, create, edit, delete user accounts',
                'icon' => 'fas fa-user-cog',
                'url' => 'users'
            ],
            'user_roles_permissions' => [
                'name' => 'User Roles & Permissions',
                'description' => 'Manage roles and permissions for users',
                'icon' => 'fas fa-user-shield',
                'url' => 'user-roles'
            ],
            'course_management' => [
                'name' => 'Course Management',
                'description' => 'Create, edit, and manage courses',
                'icon' => 'fas fa-chalkboard-teacher',
                'url' => 'course-management'
            ],
            'course_creation' => [
                'name' => 'Course Creation',
                'description' => 'Create new courses and learning content',
                'icon' => 'fas fa-plus-circle',
                'url' => 'course-creation'
            ],
            'course_categories' => [
                'name' => 'Course Categories',
                'description' => 'Manage course categories and organization',
                'icon' => 'fas fa-tags',
                'url' => 'course-categories'
            ],
            'course_subcategories' => [
                'name' => 'Course Subcategories',
                'description' => 'Manage course subcategories',
                'icon' => 'fas fa-layer-group',
                'url' => 'course-subcategories'
            ],
            'course_applicability' => [
                'name' => 'Course Applicability',
                'description' => 'Define course applicability and prerequisites',
                'icon' => 'fas fa-tasks',
                'url' => 'course-applicability'
            ],
            'vlr' => [
                'name' => 'Virtual Learning Repository',
                'description' => 'Manage SCORM, assessments, videos and content',
                'icon' => 'fas fa-file-alt',
                'url' => 'vlr'
            ],
            'assessments' => [
                'name' => 'Assessments',
                'description' => 'Create and manage assessments and quizzes',
                'icon' => 'fas fa-question-circle',
                'url' => 'assessments'
            ],
            'announcements' => [
                'name' => 'Announcements',
                'description' => 'Create and manage announcements',
                'icon' => 'fas fa-bullhorn',
                'url' => 'announcements'
            ],
            'events' => [
                'name' => 'Events',
                'description' => 'Manage events, webinars, and live sessions',
                'icon' => 'fas fa-calendar-alt',
                'url' => 'events'
            ],
            'opinion_polls' => [
                'name' => 'Opinion Polls',
                'description' => 'Create and manage opinion polls',
                'icon' => 'fas fa-poll',
                'url' => 'opinion-polls'
            ],
            'social_feed' => [
                'name' => 'Social Feed',
                'description' => 'Manage social feed and news wall',
                'icon' => 'fas fa-rss',
                'url' => 'feed'
            ],
            'reports' => [
                'name' => 'Reports',
                'description' => 'View and generate reports',
                'icon' => 'fas fa-chart-bar',
                'url' => 'reports'
            ],
            'custom_fields' => [
                'name' => 'Custom Fields',
                'description' => 'Manage custom fields for users',
                'icon' => 'fas fa-cogs',
                'url' => 'settings/custom-fields'
            ],
            'client_management' => [
                'name' => 'Client Management',
                'description' => 'Manage client organizations',
                'icon' => 'fas fa-building',
                'url' => 'client-management'
            ],
            'settings' => [
                'name' => 'Settings',
                'description' => 'System settings and configuration',
                'icon' => 'fas fa-cog',
                'url' => 'settings'
            ]
        ];
    }
    
    /**
     * Load user permissions from database
     */
    private function loadUserPermissions() {
        if (!$this->currentUser) {
            return;
        }
        
        $userId = $this->currentUser['id'] ?? null;
        $clientId = $this->currentUser['client_id'] ?? null;
        
        if (!$userId || !$clientId) {
            return;
        }
        
        // Load permissions for all modules
        foreach (array_keys($this->modules) as $moduleName) {
            $this->userPermissions[$moduleName] = [
                'access' => $this->userRoleModel->hasPermission($userId, $moduleName, 'access', $clientId),
                'create' => $this->userRoleModel->hasPermission($userId, $moduleName, 'create', $clientId),
                'edit' => $this->userRoleModel->hasPermission($userId, $moduleName, 'edit', $clientId),
                'delete' => $this->userRoleModel->hasPermission($userId, $moduleName, 'delete', $clientId)
            ];
        }
    }
    
    /**
     * Check if user has permission for a specific module and action
     */
    public function can($moduleName, $action = 'access') {
        if (!$this->currentUser) {
            return false;
        }
        
        // Super admin has all permissions
        if (($this->currentUser['system_role'] ?? '') === 'super_admin') {
            return true;
        }
        
        return $this->userPermissions[$moduleName][$action] ?? false;
    }
    
    /**
     * Check if user can access a module
     */
    public function canAccess($moduleName) {
        return $this->can($moduleName, 'access');
    }
    
    /**
     * Check if user can create in a module
     */
    public function canCreate($moduleName) {
        return $this->can($moduleName, 'create');
    }
    
    /**
     * Check if user can edit in a module
     */
    public function canEdit($moduleName) {
        return $this->can($moduleName, 'edit');
    }
    
    /**
     * Check if user can delete in a module
     */
    public function canDelete($moduleName) {
        return $this->can($moduleName, 'delete');
    }
    
    /**
     * Get all user permissions for frontend use
     */
    public function getUserPermissions() {
        return $this->userPermissions;
    }
    
    /**
     * Get all available modules
     */
    public function getModules() {
        return $this->modules;
    }
    
    /**
     * Get modules that user has access to
     */
    public function getAccessibleModules() {
        $accessible = [];
        foreach ($this->modules as $moduleName => $moduleData) {
            if ($this->canAccess($moduleName)) {
                $accessible[$moduleName] = $moduleData;
            }
        }
        return $accessible;
    }
    
    /**
     * Output JavaScript permission variables for frontend
     */
    public function outputFrontendPermissions() {
        if (!$this->currentUser) {
            return;
        }
        
        echo "<script>\n";
        echo "// User Permissions for Frontend RBAC\n";
        echo "window.userPermissions = " . json_encode($this->userPermissions) . ";\n";
        echo "window.currentUser = " . json_encode([
            'id' => $this->currentUser['id'] ?? null,
            'system_role' => $this->currentUser['system_role'] ?? 'user',
            'client_id' => $this->currentUser['client_id'] ?? null
        ]) . ";\n";
        echo "window.availableModules = " . json_encode($this->modules) . ";\n";
        echo "</script>\n";
    }
    
    /**
     * Render permission-based UI element (show/hide based on permissions)
     */
    public function renderIf($moduleName, $action = 'access', $content = '') {
        if ($this->can($moduleName, $action)) {
            echo $content;
        }
    }
    
    /**
     * Get permission status for a module (for debugging)
     */
    public function getPermissionStatus($moduleName) {
        if (!$this->currentUser) {
            return 'No user session';
        }
        
        return [
            'module' => $moduleName,
            'user_id' => $this->currentUser['id'] ?? null,
            'system_role' => $this->currentUser['system_role'] ?? 'user',
            'permissions' => $this->userPermissions[$moduleName] ?? 'Module not found'
        ];
    }
}

// Global helper functions for easy use
function can($moduleName, $action = 'access') {
    return PermissionHelper::getInstance()->can($moduleName, $action);
}

function canAccess($moduleName) {
    return PermissionHelper::getInstance()->canAccess($moduleName);
}

function canCreate($moduleName) {
    return PermissionHelper::getInstance()->canCreate($moduleName);
}

function canEdit($moduleName) {
    return PermissionHelper::getInstance()->canEdit($moduleName);
}

function canDelete($moduleName) {
    return PermissionHelper::getInstance()->canDelete($moduleName);
}

function getUserPermissions() {
    return PermissionHelper::getInstance()->getUserPermissions();
}

function outputFrontendPermissions() {
    PermissionHelper::getInstance()->outputFrontendPermissions();
}
?> 