/**
 * User Roles & Permissions Management JavaScript
 * Handles role management, permissions, and user interactions
 */

class UserRolesManager {
    constructor() {
        console.log('🏗️ UserRolesManager initializing...');
        this.initializeEventListeners();
        console.log('✅ UserRolesManager initialized');
    }

    initializeEventListeners() {
        // Client selection for super admin
        const clientSelect = document.getElementById('clientSelect');
        if (clientSelect) {
            clientSelect.addEventListener('change', (e) => this.changeClient(e.target.value));
        }

        // Form submissions
        const deleteRoleForm = document.getElementById('deleteRoleForm');
        if (deleteRoleForm) {
            deleteRoleForm.addEventListener('submit', (e) => this.handleDeleteRole(e));
        }
    }

    // Client selection for super admin
    changeClient(clientId) {
        if (clientId) {
            window.location.href = getUrl('user-roles') + '?client_id=' + clientId;
        } else {
            window.location.href = getUrl('user-roles');
        }
    }

    // Toggle role status
    toggleRoleStatus(roleId, status) {
        console.log('🔄 Toggling role status:', roleId, status);
        
        fetch(getUrl('user-roles/toggle-status'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'role_id=' + roleId + '&status=' + (status ? 1 : 0)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast('success', data.message);
            } else {
                this.showToast('error', data.message);
                // Revert the toggle if failed
                setTimeout(() => location.reload(), 1000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showToast('error', 'Failed to update role status');
            setTimeout(() => location.reload(), 1000);
        });
    }

    // Edit role
    editRole(roleId) {
        console.log('✏️ Editing role:', roleId);
        const body = 'role_id=' + roleId + (window.CURRENT_CLIENT_ID ? '&client_id=' + window.CURRENT_CLIENT_ID : '');
        fetch(getUrl('user-roles/get-role'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.populateEditForm(data.role);
                this.showEditModal();
            } else {
                this.showToast('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showToast('error', 'Failed to load role data');
        });
    }

    // Populate edit form with role data
    populateEditForm(role) {
        document.getElementById('edit_role_id').value = role.id;
        document.getElementById('edit_role_name').value = role.role_name;
        document.getElementById('edit_system_role').value = role.system_role;
        document.getElementById('edit_description').value = role.description || '';
        document.getElementById('edit_display_order').value = role.display_order;
    }

    // Show edit modal
    showEditModal() {
        const editModal = new bootstrap.Modal(document.getElementById('editRoleModal'));
        editModal.show();
    }

    // Delete role using confirmation modal
    deleteRole(roleId) {
        console.log('🗑️ Deleting role:', roleId);
        
        // Use the existing confirmation modal system
        if (typeof ConfirmationModal !== 'undefined') {
            ConfirmationModal.confirm({
                title: 'Delete Role',
                message: 'Are you sure you want to delete this role?',
                subtext: 'This action cannot be undone and will affect all users with this role.',
                confirmText: 'Delete Role',
                confirmClass: 'btn-danger',
                icon: 'fas fa-trash text-danger',
                onConfirm: () => {
                    this.performDeleteRole(roleId);
                }
            });
        } else {
            // Fallback to Bootstrap modal
            document.getElementById('delete_role_id').value = roleId;
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteRoleModal'));
            deleteModal.show();
        }
    }

    // Perform the actual delete operation
    performDeleteRole(roleId) {
        document.getElementById('delete_role_id').value = roleId;
        if (window.CURRENT_CLIENT_ID) {
            let input = document.getElementById('delete_client_id');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'client_id';
                input.id = 'delete_client_id';
                document.getElementById('deleteRoleForm').appendChild(input);
            }
            input.value = window.CURRENT_CLIENT_ID;
        }
        document.getElementById('deleteRoleForm').submit();
    }

    // Save permissions with confirmation
    savePermissions() {
        console.log('💾 Saving permissions...');
        
        // Use confirmation modal before saving
        if (typeof ConfirmationModal !== 'undefined') {
            ConfirmationModal.confirm({
                title: 'Save Permissions',
                message: 'Are you sure you want to save the current permission settings?',
                subtext: 'This will update permissions for all roles and may affect user access.',
                confirmText: 'Save Permissions',
                confirmClass: 'btn-success',
                icon: 'fas fa-save text-success',
                onConfirm: () => {
                    this.performSavePermissions();
                }
            });
        } else {
            // Fallback without confirmation
            this.performSavePermissions();
        }
    }

    // Perform the actual save operation
    performSavePermissions() {
        const permissions = this.collectPermissions();
        
        // Show loading state
        const saveButton = document.querySelector('button[onclick="userRolesManager.savePermissions()"]');
        if (saveButton) {
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            saveButton.disabled = true;
            
            this.submitPermissions(permissions, saveButton, originalText);
        } else {
            this.submitPermissions(permissions);
        }
    }

    // Collect all permission checkboxes
    collectPermissions() {
        const permissions = {};
        
        document.querySelectorAll('input[data-role][data-module][data-permission]').forEach(checkbox => {
            const roleId = checkbox.dataset.role;
            const moduleName = checkbox.dataset.module;
            const permission = checkbox.dataset.permission;
            
            if (!permissions[roleId]) {
                permissions[roleId] = {};
            }
            if (!permissions[roleId][moduleName]) {
                permissions[roleId][moduleName] = {};
            }
            
            if (checkbox.checked) {
                permissions[roleId][moduleName][permission] = true;
            }
        });
        
        return permissions;
    }

    // Submit permissions to server
    submitPermissions(permissions, saveButton = null, originalText = null) {
        const payload = { permissions: permissions };
        if (window.CURRENT_CLIENT_ID) {
            payload.client_id = window.CURRENT_CLIENT_ID;
        }
        fetch(getUrl('user-roles/save-permissions'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast('success', data.message);
            } else {
                this.showToast('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showToast('error', 'Failed to save permissions');
        })
        .finally(() => {
            // Restore button state
            if (saveButton && originalText) {
                saveButton.innerHTML = originalText;
                saveButton.disabled = false;
            }
        });
    }

    // Show toast notification using existing system
    showToast(type, message) {
        if (typeof showSimpleToast === 'function') {
            showSimpleToast(message, type);
        } else if (typeof window.showToast !== 'undefined') {
            window.showToast[type](message);
        } else {
            // Fallback to alert if toast system not available
            alert(message);
        }
    }
}

// Helper function to get URL (similar to other JS files)
function getUrl(path) {
    // Remove leading slash if present
    path = path.replace(/^\//, '');
    
    // Get base URL from current location
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
    
    // Return the full URL
    return baseUrl + '/' + path;
}

// Initialize UserRolesManager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 User Roles page loaded, initializing manager...');
    window.userRolesManager = new UserRolesManager();

    // Add Role form submission debug
    const addRoleForm = document.getElementById('addRoleForm');
    if (addRoleForm) {
        addRoleForm.addEventListener('submit', function(e) {
            console.log('[AddRoleForm] Submitting form...');
            const formData = new FormData(addRoleForm);
            for (let [key, value] of formData.entries()) {
                console.log(`[AddRoleForm] ${key}:`, value);
            }
        });
    }
});

// Global functions for inline onclick handlers (for backward compatibility)
window.changeClient = function(clientId) {
    if (window.userRolesManager) {
        window.userRolesManager.changeClient(clientId);
    }
};

window.toggleRoleStatus = function(roleId, status) {
    if (window.userRolesManager) {
        window.userRolesManager.toggleRoleStatus(roleId, status);
    }
};

window.editRole = function(roleId) {
    if (window.userRolesManager) {
        window.userRolesManager.editRole(roleId);
    }
};

window.deleteRole = function(roleId) {
    if (window.userRolesManager) {
        window.userRolesManager.deleteRole(roleId);
    }
};

window.savePermissions = function() {
    if (window.userRolesManager) {
        window.userRolesManager.savePermissions();
    }
}; 