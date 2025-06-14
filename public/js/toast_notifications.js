/**
 * Toast Notification System
 * Provides consistent success, warning, and error notifications
 * Replaces alert() calls with professional toast notifications
 */

class ToastNotification {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        // Create toast container if it doesn't exist
        if (!document.getElementById('toast-container')) {
            this.createContainer();
        }
        this.container = document.getElementById('toast-container');
    }

    createContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    show(message, type = 'success', duration = 5000) {
        const toast = this.createToast(message, type, duration);
        this.container.appendChild(toast);

        // Initialize Bootstrap toast
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: duration
        });

        // Show the toast
        bsToast.show();

        // Remove from DOM after hiding
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });

        return bsToast;
    }

    createToast(message, type, duration) {
        const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        const config = this.getTypeConfig(type);
        
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast align-items-center text-bg-${config.bgClass} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center">
                    <i class="${config.icon} me-2"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        return toast;
    }

    getTypeConfig(type) {
        const configs = {
            success: {
                bgClass: 'success',
                icon: 'fas fa-check-circle'
            },
            error: {
                bgClass: 'danger',
                icon: 'fas fa-exclamation-circle'
            },
            warning: {
                bgClass: 'warning',
                icon: 'fas fa-exclamation-triangle'
            },
            info: {
                bgClass: 'info',
                icon: 'fas fa-info-circle'
            }
        };

        return configs[type] || configs.info;
    }

    // Convenience methods
    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 7000) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration = 6000) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }

    // Clear all toasts
    clearAll() {
        const toasts = this.container.querySelectorAll('.toast');
        toasts.forEach(toast => {
            const bsToast = bootstrap.Toast.getInstance(toast);
            if (bsToast) {
                bsToast.hide();
            }
        });
    }
}

// Create global instance
window.toastNotification = new ToastNotification();

// Global convenience functions for easy usage
window.showToast = {
    success: (message, duration) => window.toastNotification.success(message, duration),
    error: (message, duration) => window.toastNotification.error(message, duration),
    warning: (message, duration) => window.toastNotification.warning(message, duration),
    info: (message, duration) => window.toastNotification.info(message, duration)
};

// Store original alert function
window.originalAlert = window.alert;

// Optional alert override (disabled by default to prevent breaking existing code)
window.enableToastAlertOverride = function() {
    window.alert = function(message) {
        // Determine message type based on content
        const lowerMessage = message.toLowerCase();

        if (lowerMessage.includes('success') || lowerMessage.includes('added') ||
            lowerMessage.includes('updated') || lowerMessage.includes('saved') ||
            lowerMessage.includes('deleted') || lowerMessage.includes('imported')) {
            window.showToast.success(message);
        } else if (lowerMessage.includes('error') || lowerMessage.includes('failed') ||
                   lowerMessage.includes('invalid') || lowerMessage.includes('unauthorized')) {
            window.showToast.error(message);
        } else if (lowerMessage.includes('warning') || lowerMessage.includes('note')) {
            window.showToast.warning(message);
        } else {
            window.showToast.info(message);
        }
    };
};

// Function to restore original alert
window.disableToastAlertOverride = function() {
    window.alert = window.originalAlert;
};

// Handle page load messages (for server-side redirects)
document.addEventListener('DOMContentLoaded', function() {
    // Check for URL parameters that indicate messages
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    const messageType = urlParams.get('type') || 'info';
    
    if (message) {
        // Decode the message
        const decodedMessage = decodeURIComponent(message);
        window.toastNotification.show(decodedMessage, messageType);
        
        // Clean up URL parameters
        const newUrl = window.location.pathname + '?' + 
            Array.from(urlParams.entries())
                .filter(([key]) => key !== 'message' && key !== 'type')
                .map(([key, value]) => `${key}=${value}`)
                .join('&');
        
        window.history.replaceState({}, '', newUrl);
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ToastNotification;
}
