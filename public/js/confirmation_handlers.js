/**
 * Centralized Confirmation Handlers
 * Universal delete confirmation system for the entire application
 * 
 * Usage: Just include this file and add data attributes to delete buttons:
 * <a href="#" class="delete-btn" 
 *    data-type="scorm" 
 *    data-id="123" 
 *    data-title="Package Name"
 *    data-action="index.php?controller=VLRController&action=delete&id=123">
 */

class ConfirmationHandlers {
    constructor() {
        this.init();
    }

    init() {
        // Single event listener for all delete confirmations
        document.addEventListener('click', (e) => {
            const deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                e.preventDefault();
                this.handleDeleteClick(deleteBtn);
            }
        });
    }

    handleDeleteClick(button) {
        // Extract data from button attributes
        const data = this.extractButtonData(button);
        
        if (!data.id || !data.action) {
            console.error('Delete button missing required data attributes:', button);
            return;
        }

        // Show confirmation modal
        this.showDeleteConfirmation(data);
    }

    extractButtonData(button) {
        return {
            type: button.dataset.type || this.getTypeFromClass(button),
            id: button.dataset.id,
            title: button.dataset.title || 'this item',
            action: button.dataset.action || this.buildActionUrl(button),
            controller: button.dataset.controller,
            method: button.dataset.method || 'GET'
        };
    }

    getTypeFromClass(button) {
        // Extract type from class names like 'delete-scorm', 'delete-assessment', etc.
        const classes = button.className.split(' ');
        for (const cls of classes) {
            if (cls.startsWith('delete-')) {
                return cls.replace('delete-', '').replace('-', ' ');
            }
        }
        return 'item';
    }

    buildActionUrl(button) {
        const id = button.dataset.id;
        const controller = button.dataset.controller;
        const action = button.dataset.deleteAction || 'delete';
        
        if (controller && id) {
            return `index.php?controller=${controller}&action=${action}&id=${id}`;
        }
        
        // Fallback: try to extract from href if it's a link
        if (button.tagName === 'A' && button.href && button.href !== '#') {
            return button.href;
        }
        
        return null;
    }

    showDeleteConfirmation(data) {
        const itemName = `${data.type} "${data.title}"`;
        
        if (typeof confirmDelete === 'function') {
            // Use professional modal if available
            confirmDelete(itemName, () => {
                this.executeDelete(data);
            });
        } else {
            // Fallback to browser confirm
            if (confirm(`Are you sure you want to delete ${itemName}?`)) {
                this.executeDelete(data);
            }
        }
    }

    executeDelete(data) {
        if (data.method === 'POST') {
            // Handle POST requests
            this.submitPostRequest(data);
        } else {
            // Handle GET requests (default)
            window.location.href = data.action;
        }
    }

    submitPostRequest(data) {
        // Create a form for POST requests
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = data.action;
        form.style.display = 'none';

        // Add CSRF token if available
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrfToken.getAttribute('content');
            form.appendChild(tokenInput);
        }

        document.body.appendChild(form);
        form.submit();
    }

    // Static method for manual confirmation calls
    static confirm(type, id, title, action, callback) {
        const instance = window.confirmationHandlersInstance || new ConfirmationHandlers();
        const data = { type, id, title, action, method: 'GET' };
        
        if (callback) {
            // Custom callback provided
            const itemName = `${type} "${title}"`;
            if (typeof confirmDelete === 'function') {
                confirmDelete(itemName, callback);
            } else {
                if (confirm(`Are you sure you want to delete ${itemName}?`)) {
                    callback();
                }
            }
        } else {
            // Use default delete execution
            instance.showDeleteConfirmation(data);
        }
    }
}

// Initialize global instance
document.addEventListener('DOMContentLoaded', function() {
    window.confirmationHandlersInstance = new ConfirmationHandlers();
});

// Global helper functions for backward compatibility
window.confirmDelete = window.confirmDelete || function(itemName, onConfirm) {
    if (confirm(`Are you sure you want to delete ${itemName}?`)) {
        onConfirm();
    }
};

window.deleteConfirmation = function(type, id, title, action) {
    ConfirmationHandlers.confirm(type, id, title, action);
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ConfirmationHandlers;
}
