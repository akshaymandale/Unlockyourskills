/**
 * User Management Confirmation Handlers
 * Handles all delete confirmations for User Management module
 * 
 * Features:
 * - User deletion confirmations
 * - Role-based confirmations
 * - Bulk action confirmations
 */

class UserConfirmations {
    constructor() {
        this.init();
    }

    init() {
        // User delete confirmations
        document.addEventListener('click', (e) => {
            if (e.target.closest('.delete-user')) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                this.handleUserDelete(e.target.closest('.delete-user'));
            } else if (e.target.closest('.lock-user')) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                this.handleUserLock(e.target.closest('.lock-user'));
            } else if (e.target.closest('.unlock-user')) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                this.handleUserUnlock(e.target.closest('.unlock-user'));
            }
        }, true); // Use capture phase to ensure this runs first
    }

    handleUserDelete(button) {
        const data = this.extractUserData(button, 'delete');

        if (!data.id) {
            console.error('User delete button missing ID:', button);
            return;
        }

        this.showUserConfirmation(data);
    }

    handleUserLock(button) {
        const data = this.extractUserData(button, 'lock');

        if (!data.id) {
            console.error('User lock button missing ID:', button);
            return;
        }

        this.showUserLockConfirmation(data);
    }

    handleUserUnlock(button) {
        const data = this.extractUserData(button, 'unlock');

        if (!data.id) {
            console.error('User unlock button missing ID:', button);
            return;
        }

        this.showUserUnlockConfirmation(data);
    }

    extractUserData(button, actionType = 'delete') {
        const actionMap = {
            'delete': 'deleteUser',
            'lock': 'lockUser',
            'unlock': 'unlockUser'
        };

        return {
            id: button.dataset.id,
            name: button.dataset.name || 'Unknown User',
            email: button.dataset.email || '',
            role: button.dataset.role || '',
            actionType: actionType,
            action: `index.php?controller=UserManagementController&action=${actionMap[actionType]}&id=${button.dataset.id}`
        };
    }

    showUserConfirmation(data) {
        const itemName = `user "${data.name}"`;
        const extraInfo = data.email ? ` (${data.email})` : '';

        if (typeof confirmDelete === 'function') {
            confirmDelete(itemName + extraInfo, () => {
                window.location.href = data.action;
            });
        } else {
            if (confirm(`Are you sure you want to delete ${itemName}${extraInfo}?`)) {
                window.location.href = data.action;
            }
        }
    }

    showUserLockConfirmation(data) {
        const itemName = `user "${data.name}"`;
        const extraInfo = data.email ? ` (${data.email})` : '';

        if (typeof confirmAction === 'function') {
            confirmAction('lock', itemName + extraInfo, () => {
                window.location.href = data.action;
            });
        } else {
            if (confirm(`Are you sure you want to lock ${itemName}${extraInfo}?\n\nThis will prevent the user from logging in.`)) {
                window.location.href = data.action;
            }
        }
    }

    showUserUnlockConfirmation(data) {
        const itemName = `user "${data.name}"`;
        const extraInfo = data.email ? ` (${data.email})` : '';

        if (typeof confirmAction === 'function') {
            confirmAction('unlock', itemName + extraInfo, () => {
                window.location.href = data.action;
            });
        } else {
            if (confirm(`Are you sure you want to unlock ${itemName}${extraInfo}?\n\nThis will allow the user to log in again.`)) {
                window.location.href = data.action;
            }
        }
    }

    // Static helper methods
    static deleteUser(id, name, email = '') {
        const url = `index.php?controller=UserManagementController&action=deleteUser&id=${id}`;
        const itemName = `user "${name}"`;
        const extraInfo = email ? ` (${email})` : '';

        if (typeof confirmDelete === 'function') {
            confirmDelete(itemName + extraInfo, () => {
                window.location.href = url;
            });
        } else {
            if (confirm(`Are you sure you want to delete ${itemName}${extraInfo}?`)) {
                window.location.href = url;
            }
        }
    }

    static lockUser(id, name, email = '') {
        const url = `index.php?controller=UserManagementController&action=lockUser&id=${id}`;
        const itemName = `user "${name}"`;
        const extraInfo = email ? ` (${email})` : '';

        if (typeof confirmAction === 'function') {
            confirmAction('lock', itemName + extraInfo, () => {
                window.location.href = url;
            });
        } else {
            if (confirm(`Are you sure you want to lock ${itemName}${extraInfo}?\n\nThis will prevent the user from logging in.`)) {
                window.location.href = url;
            }
        }
    }

    static unlockUser(id, name, email = '') {
        const url = `index.php?controller=UserManagementController&action=unlockUser&id=${id}`;
        const itemName = `user "${name}"`;
        const extraInfo = email ? ` (${email})` : '';

        if (typeof confirmAction === 'function') {
            confirmAction('unlock', itemName + extraInfo, () => {
                window.location.href = url;
            });
        } else {
            if (confirm(`Are you sure you want to unlock ${itemName}${extraInfo}?\n\nThis will allow the user to log in again.`)) {
                window.location.href = url;
            }
        }
    }

    // Bulk delete confirmation
    static confirmBulkDelete(selectedCount, callback) {
        const message = `Are you sure you want to delete ${selectedCount} selected user(s)?`;
        
        if (typeof confirmDelete === 'function') {
            confirmDelete(`${selectedCount} users`, callback);
        } else {
            if (confirm(message)) {
                callback();
            }
        }
    }
}

// Initialize User confirmations with delay to ensure global scripts are loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add a small delay to ensure all global scripts are loaded
    setTimeout(() => {
        // Only initialize if we're on a user management page
        if (document.querySelector('.user-management, .users-table, [data-user-page]')) {
            window.userConfirmationsInstance = new UserConfirmations();
        }
    }, 100);
});

// Also initialize immediately if DOM is already loaded
if (document.readyState !== 'loading') {
    setTimeout(() => {
        if (document.querySelector('.user-management, .users-table, [data-user-page]')) {
            window.userConfirmationsInstance = new UserConfirmations();
        }
    }, 100);
}

// Global helper functions
window.deleteUser = function(id, name, email) {
    UserConfirmations.deleteUser(id, name, email);
};

window.lockUser = function(id, name, email) {
    UserConfirmations.lockUser(id, name, email);
};

window.unlockUser = function(id, name, email) {
    UserConfirmations.unlockUser(id, name, email);
};

window.confirmBulkUserDelete = function(selectedCount, callback) {
    UserConfirmations.confirmBulkDelete(selectedCount, callback);
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UserConfirmations;
}
