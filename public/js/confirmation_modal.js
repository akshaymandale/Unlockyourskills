/**
 * Professional Confirmation Modal System
 * Replaces browser confirm() dialogs with beautiful Bootstrap modals
 */

class ConfirmationModal {
    constructor() {
        this.modalId = 'confirmationModal';
        this.createModal();
        this.bindEvents();
    }

    createModal() {
        // Remove existing modal if it exists
        const existingModal = document.getElementById(this.modalId);
        if (existingModal) {
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
    }

    bindEvents() {
        // Handle confirm button click
        document.addEventListener('click', (e) => {
            if (e.target.id === 'confirmButton') {
                this.handleConfirm();
            }
        });

        // Handle modal hidden event to clean up
        document.addEventListener('hidden.bs.modal', (e) => {
            if (e.target.id === this.modalId) {
                this.cleanup();
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

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById(this.modalId));
        modal.show();
    }

    handleConfirm() {
        // Hide modal first
        const modal = bootstrap.Modal.getInstance(document.getElementById(this.modalId));
        modal.hide();

        // Execute callback
        if (this.onConfirm && typeof this.onConfirm === 'function') {
            this.onConfirm();
        }
    }

    cleanup() {
        this.onConfirm = null;
        this.onCancel = null;
    }

    // Static method for easy usage
    static confirm(options) {
        if (!window.confirmationModalInstance) {
            window.confirmationModalInstance = new ConfirmationModal();
        }
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

// Helper function for delete confirmations
window.confirmDelete = function(itemName, onConfirm) {
    ConfirmationModal.confirm({
        title: 'Delete Confirmation',
        message: `Are you sure you want to delete this ${itemName}?`,
        subtext: 'This action is not reversible.',
        confirmText: 'Delete',
        confirmClass: 'btn-primary',
        onConfirm: onConfirm
    });
};

// Helper function for general confirmations
window.confirmAction = function(action, itemName, onConfirm, isReversible = true) {
    ConfirmationModal.confirm({
        title: `${action} Confirmation`,
        message: `Are you sure you want to ${action.toLowerCase()} this ${itemName}?`,
        subtext: isReversible ? 'This action is reversible.' : 'This action cannot be undone.',
        confirmText: action,
        confirmClass: 'btn-primary',
        onConfirm: onConfirm
    });
};
