/**
 * Event Management Confirmation System
 * Handles delete confirmations and status changes for events
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeEventConfirmations();
});

function initializeEventConfirmations() {
    // Delete event confirmation
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-event-btn')) {
            e.preventDefault();
            const button = e.target.closest('.delete-event-btn');
            const eventId = button.dataset.eventId;
            const eventTitle = button.dataset.eventTitle;
            
            showDeleteConfirmation(eventId, eventTitle);
        }
    });

    // Cancel event confirmation
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cancel-event-btn')) {
            e.preventDefault();
            const button = e.target.closest('.cancel-event-btn');
            const eventId = button.dataset.eventId;
            const eventTitle = button.dataset.eventTitle;
            
            showCancelConfirmation(eventId, eventTitle);
        }
    });

    // Complete event confirmation
    document.addEventListener('click', function(e) {
        if (e.target.closest('.complete-event-btn')) {
            e.preventDefault();
            const button = e.target.closest('.complete-event-btn');
            const eventId = button.dataset.eventId;
            const eventTitle = button.dataset.eventTitle;
            
            showCompleteConfirmation(eventId, eventTitle);
        }
    });
}

function showDeleteConfirmation(eventId, eventTitle) {
    const modal = createConfirmationModal({
        title: 'Delete Event',
        message: `Are you sure you want to delete the event "${eventTitle}"?`,
        details: 'This action cannot be undone. All associated RSVPs and data will be permanently removed.',
        confirmText: 'Delete Event',
        confirmClass: 'btn-danger',
        icon: 'fas fa-trash-alt',
        onConfirm: () => deleteEvent(eventId)
    });
    
    modal.show();
}

function showCancelConfirmation(eventId, eventTitle) {
    const modal = createConfirmationModal({
        title: 'Cancel Event',
        message: `Are you sure you want to cancel the event "${eventTitle}"?`,
        details: 'Participants will be notified about the cancellation. You can reactivate the event later if needed.',
        confirmText: 'Cancel Event',
        confirmClass: 'btn-warning',
        icon: 'fas fa-times-circle',
        onConfirm: () => updateEventStatus(eventId, 'cancelled')
    });
    
    modal.show();
}

function showCompleteConfirmation(eventId, eventTitle) {
    const modal = createConfirmationModal({
        title: 'Mark Event as Completed',
        message: `Mark the event "${eventTitle}" as completed?`,
        details: 'This will change the event status to completed and it will appear in the past events section.',
        confirmText: 'Mark Complete',
        confirmClass: 'btn-success',
        icon: 'fas fa-check-circle',
        onConfirm: () => updateEventStatus(eventId, 'completed')
    });
    
    modal.show();
}

function createConfirmationModal(options) {
    const modalId = 'confirmationModal_' + Date.now();
    
    const modalHTML = `
        <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="${options.icon} me-2"></i>${options.title}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <strong>${options.message}</strong>
                        </div>
                        <p class="text-muted">${options.details}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn ${options.confirmClass}" id="confirmAction_${modalId}">
                            <i class="${options.icon} me-2"></i>${options.confirmText}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    const modalElement = document.getElementById(modalId);
    const modal = new bootstrap.Modal(modalElement);
    
    // Add confirm button event listener
    document.getElementById(`confirmAction_${modalId}`).addEventListener('click', function() {
        modal.hide();
        options.onConfirm();
    });
    
    // Clean up modal after it's hidden
    modalElement.addEventListener('hidden.bs.modal', function() {
        modalElement.remove();
    });
    
    return modal;
}

function deleteEvent(eventId) {
    const formData = new FormData();
    formData.append('controller', 'EventController');
    formData.append('action', 'delete');
    formData.append('event_id', eventId);
    formData.append('ajax', '1');
    
    showLoadingState(true);
    
    fetch('index.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', data.message || 'Event deleted successfully!');
            // Reload events if the loadEvents function exists
            if (typeof loadEvents === 'function') {
                loadEvents(typeof currentPage !== 'undefined' ? currentPage : 1);
            } else {
                // Fallback: reload the page
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        } else {
            showToast('error', data.message || 'Failed to delete event. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'An error occurred while deleting the event.');
    })
    .finally(() => {
        showLoadingState(false);
    });
}

function updateEventStatus(eventId, status) {
    const formData = new FormData();
    formData.append('controller', 'EventController');
    formData.append('action', 'update');
    formData.append('event_id', eventId);
    formData.append('status', status);
    formData.append('ajax', '1');
    
    showLoadingState(true);
    
    fetch('index.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const statusText = status.charAt(0).toUpperCase() + status.slice(1);
            showToast('success', data.message || `Event ${statusText.toLowerCase()} successfully!`);
            // Reload events if the loadEvents function exists
            if (typeof loadEvents === 'function') {
                loadEvents(typeof currentPage !== 'undefined' ? currentPage : 1);
            } else {
                // Fallback: reload the page
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        } else {
            showToast('error', data.message || 'Failed to update event status. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'An error occurred while updating the event.');
    })
    .finally(() => {
        showLoadingState(false);
    });
}

function showLoadingState(show) {
    // Disable all action buttons during loading
    const actionButtons = document.querySelectorAll('.delete-event-btn, .cancel-event-btn, .complete-event-btn, .edit-event-btn');
    actionButtons.forEach(button => {
        button.disabled = show;
        if (show) {
            button.classList.add('loading');
        } else {
            button.classList.remove('loading');
        }
    });
}

function showToast(type, message) {
    // Check if a toast container exists, if not create one
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast_' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
    const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
    
    const toastHTML = `
        <div id="${toastId}" class="toast ${bgClass} text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header ${bgClass} text-white border-0">
                <i class="${icon} me-2"></i>
                <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 5000
    });
    
    toast.show();
    
    // Clean up toast after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}
