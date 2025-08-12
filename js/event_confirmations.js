/**
 * Event Management Confirmation System
 * Handles delete confirmations and status changes for events
 * Updated to use global confirmation system for consistency
 */

// Translation function (fallback if not available)
function translate(key, replacements = {}) {
    // Use global translations object if available
    if (typeof window.translations === 'object' && window.translations[key]) {
        let translation = window.translations[key];
        
        // Apply replacements if provided
        if (replacements && typeof replacements === 'object') {
            Object.keys(replacements).forEach(placeholder => {
                const regex = new RegExp(`{${placeholder}}`, 'g');
                translation = translation.replace(regex, replacements[placeholder]);
            });
        }
        
        return translation;
    }

    // Fallback to English messages if no translation found
    const fallbacks = {
        'confirmation.cancel.event.title': 'Cancel Event',
        'confirmation.cancel.event.message': 'Are you sure you want to cancel the event "{title}"?',
        'confirmation.cancel.event.subtext': 'Participants will be notified about the cancellation. You can reactivate the event later if needed.',
        'confirmation.cancel.event.button': 'Cancel Event',
        
        'confirmation.complete.event.title': 'Mark Event as Completed',
        'confirmation.complete.event.message': 'Mark the event "{title}" as completed?',
        'confirmation.complete.event.subtext': 'This will change the event status to completed and it will appear in the past events section.',
        'confirmation.complete.event.button': 'Mark Complete',
        
        'confirmation.reactivate.event.title': 'Reactivate Event',
        'confirmation.reactivate.event.message': 'Reactivate the event "{title}"?',
        'confirmation.reactivate.event.subtext': 'This will change the event status back to active and make it available to participants.',
        'confirmation.reactivate.event.button': 'Reactivate',
        
        'confirmation.archive.event.title': 'Archive Event',
        'confirmation.archive.event.message': 'Archive the event "{title}"?',
        'confirmation.archive.event.subtext': 'This will permanently archive the event. It will be hidden from the main events list but can be restored later.',
        'confirmation.archive.event.button': 'Archive'
    };

    let fallback = fallbacks[key] || key;
    
    // Apply replacements to fallback
    if (replacements && typeof replacements === 'object') {
        Object.keys(replacements).forEach(placeholder => {
            const regex = new RegExp(`{${placeholder}}`, 'g');
            fallback = fallback.replace(regex, replacements[placeholder]);
        });
    }
    
    return fallback;
}

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
            e.stopPropagation();
            console.log('🛑 Cancel event button clicked');
            const button = e.target.closest('.cancel-event-btn');
            const eventId = button.dataset.eventId;
            const eventTitle = button.dataset.eventTitle;
            console.log(`📋 Event ID: ${eventId}, Title: ${eventTitle}`);
            
            showCancelConfirmation(eventId, eventTitle);
        }
    });

    // Complete event confirmation
    document.addEventListener('click', function(e) {
        if (e.target.closest('.complete-event-btn')) {
            e.preventDefault();
            e.stopPropagation();
            console.log('✅ Complete event button clicked');
            const button = e.target.closest('.complete-event-btn');
            const eventId = button.dataset.eventId;
            const eventTitle = button.dataset.eventTitle;
            console.log(`📋 Event ID: ${eventId}, Title: ${eventTitle}`);
            
            showCompleteConfirmation(eventId, eventTitle);
        }
    });

    // Reactivate event confirmation
    document.addEventListener('click', function(e) {
        if (e.target.closest('.reactivate-event-btn')) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔄 Reactivate event button clicked');
            const button = e.target.closest('.reactivate-event-btn');
            const eventId = button.dataset.eventId;
            const eventTitle = button.dataset.eventTitle;
            console.log(`📋 Event ID: ${eventId}, Title: ${eventTitle}`);
            
            showReactivateConfirmation(eventId, eventTitle);
        }
    });

    // Archive event confirmation
    document.addEventListener('click', function(e) {
        if (e.target.closest('.archive-event-btn')) {
            e.preventDefault();
            e.stopPropagation();
            console.log('📦 Archive event button clicked');
            const button = e.target.closest('.archive-event-btn');
            const eventId = button.dataset.eventId;
            const eventTitle = button.dataset.eventTitle;
            console.log(`📋 Event ID: ${eventId}, Title: ${eventTitle}`);
            
            showArchiveConfirmation(eventId, eventTitle);
        }
    });
}

function showDeleteConfirmation(eventId, eventTitle) {
    const itemName = `event "${eventTitle}"`;

    // Use global confirmDelete if available, otherwise fallback to browser confirm
    if (typeof window.confirmDelete === 'function') {
        window.confirmDelete(itemName, () => {
            deleteEvent(eventId);
        });
    } else {
        const fallbackMessage = `Are you sure you want to delete the event "${eventTitle}"?\n\nThis action cannot be undone. All associated RSVPs and data will be permanently removed.`;
        if (confirm(fallbackMessage)) {
            deleteEvent(eventId);
        }
    }
}

function showCancelConfirmation(eventId, eventTitle) {
    console.log('🛑 Showing cancel confirmation for event:', eventId, eventTitle);
    
    const message = translate('confirmation.cancel.event.message', {title: eventTitle}) || `Are you sure you want to cancel the event "${eventTitle}"?`;
    const subtext = translate('confirmation.cancel.event.subtext') || 'Participants will be notified about the cancellation. You can reactivate the event later if needed.';

    console.log('📝 Cancel message:', message);
    console.log('📝 Cancel subtext:', subtext);

    // Use global confirmAction if available, otherwise fallback to browser confirm
    if (typeof window.confirmAction === 'function') {
        console.log('🎯 Using global confirmAction');
        window.confirmAction('cancel', 'event', () => {
            console.log('✅ Cancel confirmed, updating status...');
            updateEventStatus(eventId, 'cancelled');
        }, message, subtext);
    } else if (typeof window.showConfirmation === 'function') {
        console.log('🎯 Using showConfirmation fallback');
        // Fallback to showConfirmation if confirmAction not available
        window.showConfirmation({
            title: translate('confirmation.cancel.event.title') || 'Cancel Event',
            message: message,
            subtext: subtext,
            confirmText: translate('confirmation.cancel.event.button') || 'Cancel Event',
            confirmClass: 'btn-warning',
            icon: 'fas fa-times-circle',
            onConfirm: () => {
                console.log('✅ Cancel confirmed, updating status...');
                updateEventStatus(eventId, 'cancelled');
            },
            onCancel: () => {
                console.log('❌ Cancel event action cancelled');
            }
        });
    } else {
        console.log('🎯 Using browser confirm fallback');
        // Final fallback to browser confirm
        if (confirm(`${message}\n\n${subtext}`)) {
            console.log('✅ Cancel confirmed, updating status...');
            updateEventStatus(eventId, 'cancelled');
        }
    }
}

function showCompleteConfirmation(eventId, eventTitle) {
    console.log('✅ Showing complete confirmation for event:', eventId, eventTitle);
    
    const message = translate('confirmation.complete.event.message', {title: eventTitle}) || `Mark the event "${eventTitle}" as completed?`;
    const subtext = translate('confirmation.complete.event.subtext') || 'This will change the event status to completed and it will appear in the past events section.';

    console.log('📝 Complete message:', message);
    console.log('📝 Complete subtext:', subtext);

    // Use global confirmAction if available, otherwise fallback to browser confirm
    if (typeof window.confirmAction === 'function') {
        console.log('🎯 Using global confirmAction');
        window.confirmAction('complete', 'event', () => {
            console.log('✅ Complete confirmed, updating status...');
            updateEventStatus(eventId, 'completed');
        }, message, subtext);
    } else if (typeof window.showConfirmation === 'function') {
        console.log('🎯 Using showConfirmation fallback');
        // Fallback to showConfirmation if confirmAction not available
        window.showConfirmation({
            title: translate('confirmation.complete.event.title') || 'Mark Event as Completed',
            message: message,
            subtext: subtext,
            confirmText: translate('confirmation.complete.event.button') || 'Mark Complete',
            confirmClass: 'btn-success',
            icon: 'fas fa-check-circle',
            onConfirm: () => {
                console.log('✅ Complete confirmed, updating status...');
                updateEventStatus(eventId, 'completed');
            },
            onCancel: () => {
                console.log('❌ Mark complete action cancelled');
            }
        });
    } else {
        console.log('🎯 Using browser confirm fallback');
        // Final fallback to browser confirm
        if (confirm(`${message}\n\n${subtext}`)) {
            console.log('✅ Complete confirmed, updating status...');
            updateEventStatus(eventId, 'completed');
        }
    }
}

function showReactivateConfirmation(eventId, eventTitle) {
    const message = translate('confirmation.reactivate.event.message', {title: eventTitle}) || `Reactivate the event "${eventTitle}"?`;
    const subtext = translate('confirmation.reactivate.event.subtext') || 'This will change the event status back to active and make it available to participants.';

    // Use global confirmAction if available, otherwise fallback to browser confirm
    if (typeof window.confirmAction === 'function') {
        window.confirmAction('activate', 'event', () => {
            updateEventStatus(eventId, 'active');
        }, message, subtext);
    } else if (typeof window.showConfirmation === 'function') {
        // Fallback to showConfirmation if confirmAction not available
        window.showConfirmation({
            title: translate('confirmation.reactivate.event.title') || 'Reactivate Event',
            message: message,
            subtext: subtext,
            confirmText: translate('confirmation.reactivate.event.button') || 'Reactivate',
            confirmClass: 'btn-success',
            icon: 'fas fa-play-circle',
            onConfirm: () => {
                updateEventStatus(eventId, 'active');
            },
            onCancel: () => {
                console.log('Reactivate action cancelled');
            }
        });
    } else {
        // Final fallback to browser confirm
        if (confirm(`${message}\n\n${subtext}`)) {
            updateEventStatus(eventId, 'active');
        }
    }
}

function showArchiveConfirmation(eventId, eventTitle) {
    console.log('📦 Showing archive confirmation for event:', eventId, eventTitle);
    
    const message = translate('confirmation.archive.event.message', {title: eventTitle}) || `Archive the event "${eventTitle}"?`;
    const subtext = translate('confirmation.archive.event.subtext') || 'This will permanently archive the event. It will be hidden from the main events list but can be restored later.';

    console.log('📝 Archive message:', message);
    console.log('📝 Archive subtext:', subtext);

    // Use global confirmAction if available, otherwise fallback to browser confirm
    if (typeof window.confirmAction === 'function') {
        console.log('🎯 Using global confirmAction');
        window.confirmAction('archive', 'event', () => {
            console.log('✅ Archive confirmed, updating status...');
            updateEventStatus(eventId, 'archived');
        }, message, subtext);
    } else if (typeof window.showConfirmation === 'function') {
        console.log('🎯 Using showConfirmation fallback');
        // Fallback to showConfirmation if confirmAction not available
        window.showConfirmation({
            title: translate('confirmation.archive.event.title') || 'Archive Event',
            message: message,
            subtext: subtext,
            confirmText: translate('confirmation.archive.event.button') || 'Archive',
            confirmClass: 'btn-secondary',
            icon: 'fas fa-archive',
            onConfirm: () => {
                console.log('✅ Archive confirmed, updating status...');
                updateEventStatus(eventId, 'archived');
            },
            onCancel: () => {
                console.log('❌ Archive action cancelled');
            }
        });
    } else {
        console.log('🎯 Using browser confirm fallback');
        // Final fallback to browser confirm
        if (confirm(`${message}\n\n${subtext}`)) {
            console.log('✅ Archive confirmed, updating status...');
            updateEventStatus(eventId, 'archived');
        }
    }
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
                loadEvents(typeof window.eventState !== 'undefined' ? window.eventState.currentPage : 1);
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
    console.log(`🔄 Updating event ${eventId} status to ${status}`);
    
    const formData = new FormData();
    formData.append('controller', 'EventController');
    formData.append('action', 'updateStatus');
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
        console.log('📡 Server response:', data);
        if (data.success) {
            showToast('success', data.message || 'Event status updated successfully!');
            // Reload events if the loadEvents function exists
            if (typeof loadEvents === 'function') {
                loadEvents(typeof window.eventState !== 'undefined' ? window.eventState.currentPage : 1);
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
        console.error('❌ Error:', error);
        showToast('error', 'An error occurred while updating the event status.');
    })
    .finally(() => {
        showLoadingState(false);
    });
}

function showLoadingState(show) {
    // Show/hide loading spinner or disable buttons
    const buttons = document.querySelectorAll('.delete-event-btn, .cancel-event-btn, .complete-event-btn, .reactivate-event-btn, .archive-event-btn');
    buttons.forEach(button => {
        if (show) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        } else {
            button.disabled = false;
            // Restore original button content (you might need to store this in data attributes)
            const originalText = button.dataset.originalText || 'Delete';
            const originalIcon = button.dataset.originalIcon || 'fas fa-trash-alt';
            button.innerHTML = `<i class="${originalIcon}"></i> ${originalText}`;
        }
    });
}

function showToast(type, message) {
    if (typeof showSimpleToast === 'function') {
        showSimpleToast(message, type);
    } else {
        console.log(`${type.toUpperCase()}: ${message}`);
        alert(message); // Fallback
    }
}
