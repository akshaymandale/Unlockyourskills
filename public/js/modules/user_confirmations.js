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

    // Helper function to get translation with fallback
    getTranslation(key, replacements = {}) {
        if (typeof translate === 'function') {
            return translate(key, replacements);
        } else if (typeof window.translations === 'object' && window.translations[key]) {
            let text = window.translations[key];
            // Replace placeholders
            Object.keys(replacements).forEach(placeholder => {
                const regex = new RegExp(`\\{${placeholder}\\}`, 'g');
                text = text.replace(regex, replacements[placeholder]);
            });
            return text;
        }
        return key; // Fallback to key if no translation found
    }

    // Get translated item name for user
    getTranslatedItemName(data) {
        const replacements = {
            name: data.name
        };

        if (data.email) {
            replacements.name = `${data.name} (${data.email})`;
        }

        return this.getTranslation('item.user', replacements) || `user "${data.name}"${data.email ? ` (${data.email})` : ''}`;
    }

    init() {
        // User delete confirmations
        document.addEventListener('click', (e) => {
            // Skip if this is a modal button or inside a modal
            if (e.target.closest('[data-bs-toggle="modal"]') ||
                e.target.closest('.modal') ||
                e.target.closest('.add-user-btn') ||
                e.target.closest('.edit-user-btn')) {
                return; // Don't interfere with modal buttons
            }

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
            'delete': `users/${button.dataset.id}/delete`,
            'lock': `users/${button.dataset.id}/lock`,
            'unlock': `users/${button.dataset.id}/unlock`
        };

        return {
            id: button.dataset.id,
            name: button.dataset.name || 'Unknown User',
            email: button.dataset.email || '',
            role: button.dataset.role || '',
            actionType: actionType,
            action: getProjectUrl(actionMap[actionType])
        };
    }

    showUserConfirmation(data) {
        const itemName = this.getTranslatedItemName(data);

        if (typeof confirmDelete === 'function') {
            confirmDelete(itemName, () => {
                this.executeUserDelete(data);
            });
        } else {
            const fallbackMessage = this.getTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete ${itemName}?`;
            if (confirm(fallbackMessage)) {
                this.executeUserDelete(data);
            }
        }
    }

    executeUserDelete(data) {
        console.log('🔥 Executing user delete for:', data.id);

        // Show loading state if possible
        const deleteButton = document.querySelector(`[data-id="${data.id}"].delete-user`);
        if (deleteButton) {
            deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            deleteButton.disabled = true;
        }

        // Use the legacy controller approach for now
        const deleteUrl = getProjectUrl('index.php') + `?controller=UserManagementController&action=deleteUser&id=${data.id}`;
        console.log('🔥 Delete URL:', deleteUrl);

        // Navigate to the delete URL
        window.location.href = deleteUrl;
    }

    showUserLockConfirmation(data) {
        const itemName = this.getTranslatedItemName(data);

        if (typeof confirmAction === 'function') {
            confirmAction('lock', itemName, () => {
                this.executeUserLock(data);
            });
        } else {
            const fallbackMessage = this.getTranslation('confirmation.lock.message', {item: itemName}) || `Are you sure you want to lock ${itemName}?`;
            const fallbackSubtext = this.getTranslation('confirmation.lock.subtext', {}) || 'This will prevent the user from logging in.';
            if (confirm(`${fallbackMessage}\n\n${fallbackSubtext}`)) {
                this.executeUserLock(data);
            }
        }
    }

    executeUserLock(data) {
        console.log('🔥 Executing user lock for:', data.id);

        // Show loading state if possible
        const lockButton = document.querySelector(`[data-id="${data.id}"].lock-user`);
        if (lockButton) {
            lockButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            lockButton.disabled = true;
        }

        // Use the legacy controller approach
        const lockUrl = getProjectUrl('index.php') + `?controller=UserManagementController&action=lockUser&id=${data.id}`;
        console.log('🔥 Lock URL:', lockUrl);

        // Navigate to the lock URL
        window.location.href = lockUrl;
    }

    showUserUnlockConfirmation(data) {
        const itemName = this.getTranslatedItemName(data);

        if (typeof confirmAction === 'function') {
            confirmAction('unlock', itemName, () => {
                this.executeUserUnlock(data);
            });
        } else {
            const fallbackMessage = this.getTranslation('confirmation.unlock.message', {item: itemName}) || `Are you sure you want to unlock ${itemName}?`;
            const fallbackSubtext = this.getTranslation('confirmation.unlock.subtext', {}) || 'This will allow the user to log in again.';
            if (confirm(`${fallbackMessage}\n\n${fallbackSubtext}`)) {
                this.executeUserUnlock(data);
            }
        }
    }

    executeUserUnlock(data) {
        console.log('🔥 Executing user unlock for:', data.id);

        // Show loading state if possible
        const unlockButton = document.querySelector(`[data-id="${data.id}"].unlock-user`);
        if (unlockButton) {
            unlockButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            unlockButton.disabled = true;
        }

        // Use the legacy controller approach
        const unlockUrl = getProjectUrl('index.php') + `?controller=UserManagementController&action=unlockUser&id=${data.id}`;
        console.log('🔥 Unlock URL:', unlockUrl);

        // Navigate to the unlock URL
        window.location.href = unlockUrl;
    }

    // Static helper methods
    static deleteUser(id, name, email = '') {
        const data = { id: id, name: name, email: email };
        const itemName = UserConfirmations.getStaticTranslatedItemName(data);

        if (typeof confirmDelete === 'function') {
            confirmDelete(itemName, () => {
                UserConfirmations.executeStaticUserDelete(data);
            });
        } else {
            const fallbackMessage = UserConfirmations.getStaticTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete ${itemName}?`;
            if (confirm(fallbackMessage)) {
                UserConfirmations.executeStaticUserDelete(data);
            }
        }
    }

    static executeStaticUserDelete(data) {
        console.log('🔥 Executing static user delete for:', data.id);

        // Show loading state if possible
        const deleteButton = document.querySelector(`[data-id="${data.id}"].delete-user`);
        if (deleteButton) {
            deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            deleteButton.disabled = true;
        }

        // Use the legacy controller approach for now
        const deleteUrl = getProjectUrl('index.php') + `?controller=UserManagementController&action=deleteUser&id=${data.id}`;
        console.log('🔥 Static delete URL:', deleteUrl);

        // Navigate to the delete URL
        window.location.href = deleteUrl;
    }

    static lockUser(id, name, email = '') {
        const data = { id: id, name: name, email: email };
        const itemName = UserConfirmations.getStaticTranslatedItemName(data);

        if (typeof confirmAction === 'function') {
            confirmAction('lock', itemName, () => {
                UserConfirmations.executeStaticUserLock(data);
            });
        } else {
            const fallbackMessage = UserConfirmations.getStaticTranslation('confirmation.lock.message', {item: itemName}) || `Are you sure you want to lock ${itemName}?`;
            const fallbackSubtext = UserConfirmations.getStaticTranslation('confirmation.lock.subtext', {}) || 'This will prevent the user from logging in.';
            if (confirm(`${fallbackMessage}\n\n${fallbackSubtext}`)) {
                UserConfirmations.executeStaticUserLock(data);
            }
        }
    }

    static executeStaticUserLock(data) {
        console.log('🔥 Executing static user lock for:', data.id);

        // Show loading state if possible
        const lockButton = document.querySelector(`[data-id="${data.id}"].lock-user`);
        if (lockButton) {
            lockButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            lockButton.disabled = true;
        }

        // Use the legacy controller approach
        const lockUrl = getProjectUrl('index.php') + `?controller=UserManagementController&action=lockUser&id=${data.id}`;
        console.log('🔥 Static lock URL:', lockUrl);

        // Navigate to the lock URL
        window.location.href = lockUrl;
    }

    static unlockUser(id, name, email = '') {
        const data = { id: id, name: name, email: email };
        const itemName = UserConfirmations.getStaticTranslatedItemName(data);

        if (typeof confirmAction === 'function') {
            confirmAction('unlock', itemName, () => {
                UserConfirmations.executeStaticUserUnlock(data);
            });
        } else {
            const fallbackMessage = UserConfirmations.getStaticTranslation('confirmation.unlock.message', {item: itemName}) || `Are you sure you want to unlock ${itemName}?`;
            const fallbackSubtext = UserConfirmations.getStaticTranslation('confirmation.unlock.subtext', {}) || 'This will allow the user to log in again.';
            if (confirm(`${fallbackMessage}\n\n${fallbackSubtext}`)) {
                UserConfirmations.executeStaticUserUnlock(data);
            }
        }
    }

    static executeStaticUserUnlock(data) {
        console.log('🔥 Executing static user unlock for:', data.id);

        // Show loading state if possible
        const unlockButton = document.querySelector(`[data-id="${data.id}"].unlock-user`);
        if (unlockButton) {
            unlockButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            unlockButton.disabled = true;
        }

        // Use the legacy controller approach
        const unlockUrl = getProjectUrl('index.php') + `?controller=UserManagementController&action=unlockUser&id=${data.id}`;
        console.log('🔥 Static unlock URL:', unlockUrl);

        // Navigate to the unlock URL
        window.location.href = unlockUrl;
    }

    // Static helper methods for translations
    static getStaticTranslation(key, replacements = {}) {
        if (typeof translate === 'function') {
            return translate(key, replacements);
        } else if (typeof window.translations === 'object' && window.translations[key]) {
            let text = window.translations[key];
            // Replace placeholders
            Object.keys(replacements).forEach(placeholder => {
                const regex = new RegExp(`\\{${placeholder}\\}`, 'g');
                text = text.replace(regex, replacements[placeholder]);
            });
            return text;
        }
        return key; // Fallback to key if no translation found
    }

    static getStaticTranslatedItemName(data) {
        const replacements = {
            name: data.name
        };

        if (data.email) {
            replacements.name = `${data.name} (${data.email})`;
        }

        return UserConfirmations.getStaticTranslation('item.user', replacements) || `user "${data.name}"${data.email ? ` (${data.email})` : ''}`;
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
