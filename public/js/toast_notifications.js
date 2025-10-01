/**
 * Bulletproof Toast Notification System
 * Provides consistent success, warning, and error notifications
 * Replaces alert() calls with professional toast notifications
 */

// Immediately override alert to prevent any alerts from showing
(function() {
    'use strict';


    // Store original alert immediately
    window.originalAlert = window.alert;

    // Override alert immediately - before any other scripts can use it
    window.alert = function(message) {

        try {
            // If toast system is ready, use it
            if (typeof window.showSimpleToast === 'function') {
                const messageType = detectMessageType(message);
                window.showSimpleToast(message, messageType);
            } else {
                // Queue the message for when toast system is ready
                if (!window.toastQueue) window.toastQueue = [];
                window.toastQueue.push(message);
            }
        } catch (error) {
            window.originalAlert(message);
        }
    };

})();

// Simple toast function that works immediately
function showSimpleToast(message, type = 'success') {

    // Ensure container exists
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';

        // Wait for body if not available
        if (document.body) {
            document.body.appendChild(container);
        } else {
            // If body not ready, wait and try again
            setTimeout(() => showSimpleToast(message, type), 100);
            return;
        }
    }

    // Create toast element
    const toastId = 'toast-' + Date.now();
    const config = getToastConfig(type);

    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-bg-${config.bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center">
                    <i class="${config.icon} me-2"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = document.getElementById(toastId);

    // Show toast with or without Bootstrap
    if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        const bsToast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: type === 'error' ? 7000 : 5000
        });
        bsToast.show();

        // Remove after hiding
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    } else {
        // Fallback without Bootstrap
        toastElement.style.display = 'block';
        toastElement.classList.add('show');

        setTimeout(() => {
            toastElement.remove();
        }, type === 'error' ? 7000 : 5000);
    }

}

function getToastConfig(type) {
    // Theme-consistent toast configurations
    const configs = {
        success: {
            bgClass: 'success',
            icon: 'fas fa-check-circle'
        },
        error: {
            bgClass: 'danger',
            icon: 'fas fa-times-circle'  // Changed for better theme consistency
        },
        warning: {
            bgClass: 'warning',
            icon: 'fas fa-exclamation-triangle'
        },
        info: {
            bgClass: 'info',
            icon: 'fas fa-info-circle'
        },
        primary: {
            bgClass: 'primary',
            icon: 'fas fa-bell'  // Theme purple notifications
        }
    };
    return configs[type] || configs.info;
}

// Message type detection function
function detectMessageType(message) {
    const lowerMessage = message.toLowerCase();
    console.log('🔍 Detecting message type for:', lowerMessage);

    if (lowerMessage.includes('success') || lowerMessage.includes('added') ||
        lowerMessage.includes('updated') || lowerMessage.includes('saved') ||
        lowerMessage.includes('deleted') || lowerMessage.includes('imported')) {
        console.log('✅ Detected as success message');
        return 'success';
    } else if (lowerMessage.includes('error') || lowerMessage.includes('failed') ||
               lowerMessage.includes('invalid') || lowerMessage.includes('unauthorized')) {
        console.log('❌ Detected as error message');
        return 'error';
    } else if (lowerMessage.includes('warning') || lowerMessage.includes('note')) {
        console.log('⚠️ Detected as warning message');
        return 'warning';
    } else {
        console.log('ℹ️ Detected as info message');
        return 'info';
    }
}

// Make functions globally available
window.showSimpleToast = showSimpleToast;
window.detectMessageType = detectMessageType;

// Global convenience functions
window.showToast = {
    success: (message) => showSimpleToast(message, 'success'),
    error: (message) => showSimpleToast(message, 'error'),
    warning: (message) => showSimpleToast(message, 'warning'),
    info: (message) => showSimpleToast(message, 'info')
};

// Legacy compatibility
window.toastNotification = {
    show: showSimpleToast,
    success: (message) => showSimpleToast(message, 'success'),
    error: (message) => showSimpleToast(message, 'error'),
    warning: (message) => showSimpleToast(message, 'warning'),
    info: (message) => showSimpleToast(message, 'info'),
    initialized: true
};

// Process any queued messages
if (window.toastQueue && window.toastQueue.length > 0) {
    console.log('📋 Processing queued messages:', window.toastQueue.length);
    window.toastQueue.forEach(message => {
        const messageType = detectMessageType(message);
        showSimpleToast(message, messageType);
    });
    window.toastQueue = [];
}

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
        showSimpleToast(decodedMessage, messageType);

        // Clean up URL parameters (remove message and type, keep others)
        urlParams.delete('message');
        urlParams.delete('type');
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, '', newUrl);
    }
});


// Test the system immediately
setTimeout(() => {
    if (typeof showSimpleToast === 'function') {
    } else {
    }
}, 100);
