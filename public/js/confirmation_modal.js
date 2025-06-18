/**
 * Professional Confirmation Modal System
 * Replaces browser confirm() dialogs with beautiful Bootstrap modals
 */

class ConfirmationModal {
    constructor() {
        console.log('🏗️ ConfirmationModal constructor called');
        this.modalId = 'confirmationModal';
        this.onConfirm = null;
        this.onCancel = null;
        console.log('📝 Creating modal...');
        this.createModal();
        console.log('🔗 Binding events...');
        this.bindEvents();
        console.log('✅ ConfirmationModal initialized');
    }

    createModal() {
        // Remove existing modal if it exists
        const existingModal = document.getElementById(this.modalId);
        if (existingModal) {
            console.log('🗑️ Removing existing modal');
            existingModal.remove();
        }

        // Create modal HTML with theme colors
        const modalHTML = `
            <div class="modal fade" id="${this.modalId}" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content theme-modal">
                        <div class="modal-header theme-modal-header">
                            <h5 class="modal-title" id="confirmationModalLabel">
                                <i class="fas fa-exclamation-triangle me-2" style="color: #ffc107;"></i>
                                Confirm Action
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body theme-modal-body">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-question-circle fa-2x me-3" style="color: #6a0dad;" id="confirmationIcon"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-2 theme-modal-message" id="confirmationMessage">Are you sure you want to proceed?</p>
                                    <small class="theme-modal-subtext" id="confirmationSubtext">This action cannot be undone.</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer theme-modal-footer">
                            <button type="button" class="btn theme-btn-primary" id="confirmButton">
                                Confirm
                            </button>
                            <button type="button" class="btn theme-btn-danger" data-bs-dismiss="modal">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Verify modal was created
        const createdModal = document.getElementById(this.modalId);
        const createdButton = document.getElementById('confirmButton');
        console.log('✅ Modal created:', !!createdModal);
        console.log('✅ Confirm button created:', !!createdButton);
        if (createdButton) {
            console.log('🔍 Button ID:', createdButton.id);
            console.log('🔍 Button classes:', createdButton.className);
        }
    }

    bindEvents() {
        console.log('🔧 Binding events for confirmation modal...');

        // Handle confirm button click with proper context binding
        document.addEventListener('click', (e) => {
            console.log('📱 Click event detected on:', e.target);
            console.log('📱 Target ID:', e.target.id);
            console.log('📱 Target classes:', e.target.className);

            if (e.target.id === 'confirmButton') {
                console.log('🎯 Confirm button clicked! Calling handleConfirm...');
                console.log('🔍 this context:', this);
                // Use arrow function to preserve 'this' context
                this.handleConfirm();
            } else {
                console.log('❌ Not the confirm button');
            }
        });

        // Handle modal hidden event to clean up
        document.addEventListener('hidden.bs.modal', (e) => {
            if (e.target.id === this.modalId) {
                // Delay cleanup to allow callback execution
                setTimeout(() => {
                    this.cleanup();
                }, 200);
            }
        });

        // Handle cancel button and close button clicks
        document.addEventListener('click', (e) => {
            if (e.target.hasAttribute('data-bs-dismiss') && e.target.closest(`#${this.modalId}`)) {
                this.handleCancel();
            }
        });

        // Handle backdrop click
        document.addEventListener('click', (e) => {
            if (e.target.id === this.modalId) {
                this.handleCancel();
            }
        });
    }

    show(options = {}) {
        const {
            title = 'Confirm Action',
            message = 'Are you sure you want to proceed?',
            subtext = 'This action cannot be undone.',
            confirmText = 'Confirm',
            confirmClass = 'btn-primary',
            icon = 'fas fa-question-circle text-primary',
            onConfirm = null,
            onCancel = null
        } = options;

        // Update modal content with theme colors
        document.getElementById('confirmationModalLabel').innerHTML = `
            <i class="fas fa-exclamation-triangle me-2" style="color: #ffc107;"></i>${title}
        `;
        document.getElementById('confirmationMessage').textContent = message;
        document.getElementById('confirmationSubtext').textContent = subtext;

        const confirmBtn = document.getElementById('confirmButton');
        confirmBtn.textContent = confirmText;

        // Apply theme-consistent button classes
        if (confirmClass === 'btn-danger') {
            confirmBtn.className = 'btn theme-btn-danger';
        } else if (confirmClass === 'btn-primary') {
            confirmBtn.className = 'btn theme-btn-primary';
        } else if (confirmClass === 'btn-warning') {
            confirmBtn.className = 'btn theme-btn-warning';
        } else {
            confirmBtn.className = `btn ${confirmClass}`;
        }

        // Store callbacks
        this.onConfirm = onConfirm;
        this.onCancel = onCancel;

        console.log('📦 Stored callbacks in show():');
        console.log('  - onConfirm:', this.onConfirm);
        console.log('  - onCancel:', this.onCancel);

        // Show modal with proper backdrop handling
        const modalElement = document.getElementById(this.modalId);
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true,
            focus: true
        });

        // Clean up any existing backdrops before showing
        this.cleanup();

        // Add direct event listener to the confirm button as backup
        const confirmButton = document.getElementById('confirmButton');
        if (confirmButton) {
            console.log('🔗 Adding direct click listener to confirm button');
            // Remove any existing listeners
            confirmButton.removeEventListener('click', this.directConfirmHandler);
            // Add new listener
            this.directConfirmHandler = () => {
                console.log('🎯 Direct button click handler triggered!');
                this.handleConfirm();
            };
            confirmButton.addEventListener('click', this.directConfirmHandler);
        }

        modal.show();
    }

    handleConfirm() {
        console.log('🔘 CONFIRM BUTTON CLICKED');
        console.log('🔍 Current onConfirm callback:', this.onConfirm);
        console.log('🔍 Callback type:', typeof this.onConfirm);

        // Store callback before hiding modal (to prevent cleanup from clearing it)
        const callback = this.onConfirm;
        console.log('📦 Stored callback:', callback);

        // Hide modal first
        const modalElement = document.getElementById(this.modalId);
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            console.log('🚪 Hiding modal...');
            modal.hide();
        }

        // Execute callback after a short delay to ensure modal is hidden
        if (callback && typeof callback === 'function') {
            console.log('✅ Executing callback in 100ms...');
            setTimeout(() => {
                console.log('🚀 EXECUTING CALLBACK NOW!');
                try {
                    callback();
                    console.log('✅ Callback executed successfully');
                } catch (error) {
                    console.error('❌ Error executing callback:', error);
                }
            }, 100);
        } else {
            console.error('❌ NO VALID CALLBACK FOUND!');
            console.log('Callback value:', callback);
            console.log('Callback type:', typeof callback);
        }
    }

    handleCancel() {
        // Hide modal first
        const modalElement = document.getElementById(this.modalId);
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }

        // Execute cancel callback if provided
        if (this.onCancel && typeof this.onCancel === 'function') {
            this.onCancel();
        }
    }

    cleanup() {
        // Clear callbacks
        this.onConfirm = null;
        this.onCancel = null;

        // Force remove any remaining backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => {
            backdrop.remove();
        });

        // Ensure body classes are cleaned up
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    // Static method for easy usage
    static confirm(options) {
        console.log('🏗️ ConfirmationModal.confirm called');
        console.log('🔍 Current instance:', window.confirmationModalInstance);

        if (!window.confirmationModalInstance) {
            console.log('🆕 Creating new ConfirmationModal instance');
            window.confirmationModalInstance = new ConfirmationModal();
        }

        console.log('📞 Calling show() on instance');
        window.confirmationModalInstance.show(options);
    }
}

// Initialize global instance
document.addEventListener('DOMContentLoaded', function() {
    window.confirmationModalInstance = new ConfirmationModal();
});

// Global function for easy access
window.showConfirmation = function(options) {
    ConfirmationModal.confirm(options);
};

// Override browser confirm() function to use our modal
window.originalConfirm = window.confirm;
window.confirm = function(message) {
    return new Promise((resolve) => {
        ConfirmationModal.confirm({
            message: message,
            onConfirm: () => resolve(true),
            onCancel: () => resolve(false)
        });
    });
};

// Global function to clean up modal backdrops
window.cleanupModalBackdrop = function() {
    // Remove all modal backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => {
        backdrop.remove();
    });

    // Clean up body classes and styles
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

    console.log('🧹 Modal backdrop cleaned up');
};

// Helper function to get translation with fallback
function getTranslation(key, replacements = {}) {
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

// General confirmation function for different actions with internationalization
window.confirmAction = function(actionType, itemName, onConfirm, customMessage = null, customSubtext = null) {
    const actionConfig = {
        'delete': {
            title: getTranslation('confirmation.delete.title', {}) || 'Delete Confirmation',
            icon: 'fas fa-exclamation-triangle',
            iconColor: '#ffc107',
            message: customMessage || getTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete this ${itemName}?`,
            subtext: customSubtext || getTranslation('confirmation.delete.subtext', {}) || 'This action is not reversible.',
            confirmText: getTranslation('confirmation.delete.button', {}) || 'Delete',
            confirmClass: 'theme-btn-primary'
        },
        'lock': {
            title: getTranslation('confirmation.lock.title', {}) || 'Lock Confirmation',
            icon: 'fas fa-lock',
            iconColor: '#dc3545',
            message: customMessage || getTranslation('confirmation.lock.message', {item: itemName}) || `Are you sure you want to lock this ${itemName}?`,
            subtext: customSubtext || getTranslation('confirmation.lock.subtext', {}) || 'This will prevent the user from logging in.',
            confirmText: getTranslation('confirmation.lock.button', {}) || 'Lock',
            confirmClass: 'theme-btn-danger'
        },
        'unlock': {
            title: getTranslation('confirmation.unlock.title', {}) || 'Unlock Confirmation',
            icon: 'fas fa-lock-open',
            iconColor: '#28a745',
            message: customMessage || getTranslation('confirmation.unlock.message', {item: itemName}) || `Are you sure you want to unlock this ${itemName}?`,
            subtext: customSubtext || getTranslation('confirmation.unlock.subtext', {}) || 'This will allow the user to log in again.',
            confirmText: getTranslation('confirmation.unlock.button', {}) || 'Unlock',
            confirmClass: 'theme-btn-warning'
        },
        'activate': {
            title: getTranslation('confirmation.activate.title', {}) || 'Activate Confirmation',
            icon: 'fas fa-eye',
            iconColor: '#28a745',
            message: customMessage || getTranslation('confirmation.activate.message', {item: itemName}) || `Are you sure you want to activate this ${itemName}?`,
            subtext: customSubtext || getTranslation('confirmation.activate.subtext', {}) || 'This will make the field visible in forms again.',
            confirmText: getTranslation('confirmation.activate.button', {}) || 'Activate',
            confirmClass: 'theme-btn-success'
        },
        'deactivate': {
            title: getTranslation('confirmation.deactivate.title', {}) || 'Deactivate Confirmation',
            icon: 'fas fa-eye-slash',
            iconColor: '#ffc107',
            message: customMessage || getTranslation('confirmation.deactivate.message', {item: itemName}) || `Are you sure you want to deactivate this ${itemName}?`,
            subtext: customSubtext || getTranslation('confirmation.deactivate.subtext', {}) || 'This will hide the field from forms but preserve existing data.',
            confirmText: getTranslation('confirmation.deactivate.button', {}) || 'Deactivate',
            confirmClass: 'theme-btn-warning'
        }
    };

    const config = actionConfig[actionType] || actionConfig['delete'];

    // Create modal HTML if it doesn't exist
    if (!document.getElementById('actionConfirmModal')) {
        const modalHTML = `
            <div class="modal fade" id="actionConfirmModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content theme-modal">
                        <div class="modal-header theme-modal-header">
                            <h5 class="modal-title" id="actionConfirmTitle">
                                <i id="actionConfirmIcon" class="me-2"></i>
                                <span id="actionConfirmTitleText">Confirmation</span>
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body theme-modal-body">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <i id="actionConfirmBodyIcon" class="fa-2x me-3"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <p class="mb-2" id="actionConfirmMessage">Are you sure?</p>
                                    <small class="text-muted" id="actionConfirmSubtext">Please confirm your action.</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer theme-modal-footer">
                            <button type="button" class="btn" id="actionConfirmBtn">Confirm</button>
                            <button type="button" class="btn theme-btn-danger" data-bs-dismiss="modal" id="actionCancelBtn">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Add event listeners for modal cleanup
        const modalElement = document.getElementById('actionConfirmModal');

        // Handle modal hidden event for backdrop cleanup
        modalElement.addEventListener('hidden.bs.modal', function() {
            setTimeout(window.cleanupModalBackdrop, 50);
        });

        // Handle close button and cancel button clicks
        modalElement.addEventListener('click', function(e) {
            if (e.target.hasAttribute('data-bs-dismiss') || e.target.closest('[data-bs-dismiss]')) {
                setTimeout(window.cleanupModalBackdrop, 150);
            }
        });

        // Handle backdrop click
        modalElement.addEventListener('click', function(e) {
            if (e.target === modalElement) {
                setTimeout(window.cleanupModalBackdrop, 150);
            }
        });

        // Handle escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalElement.classList.contains('show')) {
                setTimeout(window.cleanupModalBackdrop, 150);
            }
        });
    }

    // Clean up any existing backdrops before showing new modal
    window.cleanupModalBackdrop();

    // Update modal content
    document.getElementById('actionConfirmTitleText').textContent = config.title;
    document.getElementById('actionConfirmIcon').className = config.icon + ' me-2';
    document.getElementById('actionConfirmIcon').style.color = config.iconColor;
    document.getElementById('actionConfirmBodyIcon').className = config.icon + ' fa-2x me-3';
    document.getElementById('actionConfirmBodyIcon').style.color = config.iconColor;
    document.getElementById('actionConfirmMessage').textContent = config.message;
    document.getElementById('actionConfirmSubtext').textContent = config.subtext;

    // Update confirm button
    const confirmBtn = document.getElementById('actionConfirmBtn');
    confirmBtn.textContent = config.confirmText;
    confirmBtn.className = 'btn ' + config.confirmClass;

    // Update cancel button text
    const cancelBtn = document.getElementById('actionCancelBtn');
    cancelBtn.textContent = getTranslation('confirmation.cancel.button', {}) || 'Cancel';

    // Remove any existing event listeners and add new one
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

    // Add click event listener
    newConfirmBtn.addEventListener('click', function() {
        // Hide modal
        const modalElement = document.getElementById('actionConfirmModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }

        // Execute callback
        if (onConfirm && typeof onConfirm === 'function') {
            setTimeout(onConfirm, 100);
        }
    });

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('actionConfirmModal'), {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    modal.show();
};

// Helper function for delete confirmations (backward compatibility)
window.confirmDelete = function(itemName, onConfirm) {
    window.confirmAction('delete', itemName, onConfirm);
};

// Test function to verify modal is working
window.testConfirmationModal = function() {
    console.log('🧪 Testing confirmation modal...');
    window.confirmDelete('test item', function() {
        console.log('🎉 TEST CALLBACK EXECUTED!');
        alert('Test callback worked!');
    });
};


