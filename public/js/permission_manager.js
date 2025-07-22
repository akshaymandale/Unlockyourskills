/**
 * Centralized Permission Manager for Frontend RBAC
 * Handles all permission checks and UI updates consistently
 */

class PermissionManager {
    constructor() {
        this.permissions = window.userPermissions || {};
        this.currentUser = window.currentUser || {};
        this.modules = window.availableModules || {};
        this.init();
    }

    init() {
        // Initialize permission checks after DOM is loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupPermissionChecks());
        } else {
            this.setupPermissionChecks();
        }
    }

    /**
     * Setup permission checks for the current page
     */
    setupPermissionChecks() {
        this.hideUnauthorizedElements();
        this.disableUnauthorizedButtons();
        this.updateNavigationVisibility();
    }

    /**
     * Check if user has permission for a specific module and action
     */
    can(moduleName, action = 'access') {
        // Super admin has all permissions
        if (this.currentUser.system_role === 'super_admin') {
            return true;
        }

        const modulePermissions = this.permissions[moduleName];
        if (!modulePermissions) {
            return false;
        }

        const value = modulePermissions[action];
        return value === true || value === 1 || value === '1';
    }

    /**
     * Check if user can access a module
     */
    canAccess(moduleName) {
        return this.can(moduleName, 'access');
    }

    /**
     * Check if user can create in a module
     */
    canCreate(moduleName) {
        return this.can(moduleName, 'create');
    }

    /**
     * Check if user can edit in a module
     */
    canEdit(moduleName) {
        return this.can(moduleName, 'edit');
    }

    /**
     * Check if user can delete in a module
     */
    canDelete(moduleName) {
        return this.can(moduleName, 'delete');
    }

    /**
     * Hide elements that user doesn't have permission to access
     */
    hideUnauthorizedElements() {
        // Hide cards/buttons based on data-permission attributes
        const permissionElements = document.querySelectorAll('[data-permission]');
        permissionElements.forEach(element => {
            const permission = element.getAttribute('data-permission');
            const [moduleName, action] = permission.split(':');
            
            if (!this.can(moduleName, action)) {
                element.style.display = 'none';
            }
        });

        // Hide entire sections based on module access
        const moduleElements = document.querySelectorAll('[data-module]');
        moduleElements.forEach(element => {
            const moduleName = element.getAttribute('data-module');
            
            if (!this.canAccess(moduleName)) {
                element.style.display = 'none';
            }
        });
    }

    /**
     * Disable buttons that user doesn't have permission to use
     */
    disableUnauthorizedButtons() {
        const actionButtons = document.querySelectorAll('[data-action-permission]');
        actionButtons.forEach(button => {
            const permission = button.getAttribute('data-action-permission');
            const [moduleName, action] = permission.split(':');
            
            if (!this.can(moduleName, action)) {
                button.disabled = true;
                button.classList.add('disabled');
                button.title = 'You do not have permission to perform this action';
            }
        });
    }

    /**
     * Update navigation visibility based on permissions
     */
    updateNavigationVisibility() {
        // Hide navigation items based on permissions
        const navItems = document.querySelectorAll('[data-nav-permission]');
        navItems.forEach(item => {
            const moduleName = item.getAttribute('data-nav-permission');
            
            if (!this.canAccess(moduleName)) {
                item.style.display = 'none';
            }
        });
    }

    /**
     * Render action buttons based on permissions
     */
    renderActionButtons(moduleName, itemId = null, itemData = {}) {
        const buttons = [];

        if (this.canCreate(moduleName)) {
            buttons.push(`<button class="btn btn-success btn-sm" onclick="add${this.capitalizeFirst(moduleName)}()" title="Add new">
                <i class="fas fa-plus"></i> Add
            </button>`);
        }

        if (this.canEdit(moduleName)) {
            buttons.push(`<button class="btn btn-primary btn-sm" onclick="edit${this.capitalizeFirst(moduleName)}('${itemId}')" title="Edit">
                <i class="fas fa-edit"></i> Edit
            </button>`);
        }

        if (this.canDelete(moduleName)) {
            buttons.push(`<button class="btn btn-danger btn-sm" onclick="delete${this.capitalizeFirst(moduleName)}('${itemId}')" title="Delete">
                <i class="fas fa-trash"></i> Delete
            </button>`);
        }

        return buttons.join(' ');
    }

    /**
     * Check permission before executing an action
     */
    checkPermission(moduleName, action, callback) {
        if (this.can(moduleName, action)) {
            if (typeof callback === 'function') {
                callback();
            }
        } else {
            this.showPermissionDeniedMessage();
        }
    }

    /**
     * Show permission denied message
     */
    showPermissionDeniedMessage() {
        // You can customize this to show a toast or modal
        alert('You do not have permission to perform this action.');
    }

    /**
     * Get all permissions for debugging
     */
    getPermissions() {
        return this.permissions;
    }

    /**
     * Get accessible modules
     */
    getAccessibleModules() {
        const accessible = {};
        Object.keys(this.modules).forEach(moduleName => {
            if (this.canAccess(moduleName)) {
                accessible[moduleName] = this.modules[moduleName];
            }
        });
        return accessible;
    }

    /**
     * Utility function to capitalize first letter
     */
    capitalizeFirst(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    /**
     * Update UI elements dynamically (for AJAX-loaded content)
     */
    updateDynamicContent() {
        this.hideUnauthorizedElements();
        this.disableUnauthorizedButtons();
    }
}

// Global permission manager instance
window.permissionManager = new PermissionManager();

// Global helper functions for easy use
window.can = (moduleName, action = 'access') => window.permissionManager.can(moduleName, action);
window.canAccess = (moduleName) => window.permissionManager.canAccess(moduleName);
window.canCreate = (moduleName) => window.permissionManager.canCreate(moduleName);
window.canEdit = (moduleName) => window.permissionManager.canEdit(moduleName);
window.canDelete = (moduleName) => window.permissionManager.canDelete(moduleName);

// Permission check wrapper for actions
window.checkPermission = (moduleName, action, callback) => {
    window.permissionManager.checkPermission(moduleName, action, callback);
};

// Update permissions after AJAX content loads
window.updatePermissions = () => {
    window.permissionManager.updateDynamicContent();
};

// Debug function to show current permissions
window.showPermissions = () => {
    console.log('Current User:', window.currentUser);
    console.log('User Permissions:', window.permissionManager.getPermissions());
    console.log('Accessible Modules:', window.permissionManager.getAccessibleModules());
}; 