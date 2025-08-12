/**
 * Announcement Confirmation System
 * Following the same patterns as Opinion Poll confirmations
 */

class AnnouncementConfirmations {
    constructor() {
        this.init();
    }

    init() {
        // Announcement delete confirmations
        document.addEventListener('click', (e) => {
            if (e.target.closest('.delete-announcement-btn')) {
                e.preventDefault();
                this.handleAnnouncementDelete(e.target.closest('.delete-announcement-btn'));
            }
        });

        // Announcement status change confirmations
        document.addEventListener('click', (e) => {
            const statusButtons = [
                '.activate-announcement-btn',
                '.archive-announcement-btn', 
                '.cancel-schedule-btn',
                '.unarchive-announcement-btn'
            ];
            
            const target = e.target.closest(statusButtons.join(', '));
            if (target) {
                e.preventDefault();
                this.handleStatusChange(target);
            }
        });
    }

    handleAnnouncementDelete(button) {
        const announcementId = button.dataset.announcementId;
        const announcementTitle = button.dataset.announcementTitle;
        
        if (!announcementId || !announcementTitle) {
            console.error('Missing announcement ID or title for delete confirmation');
            return;
        }

        const data = {
            id: announcementId,
            title: announcementTitle
        };

        this.showAnnouncementDeleteConfirmation(data);
    }

    handleStatusChange(button) {
        const announcementId = button.dataset.announcementId;
        let action, status, actionText;

        // Determine action based on button class
        if (button.classList.contains('activate-announcement-btn')) {
            action = 'activate';
            status = 'active';
            actionText = 'activate';
        } else if (button.classList.contains('archive-announcement-btn')) {
            action = 'archive';
            status = 'archived';
            actionText = 'archive';
        } else if (button.classList.contains('cancel-schedule-btn')) {
            action = 'cancel_schedule';
            status = 'draft';
            actionText = 'cancel schedule for';
        } else if (button.classList.contains('unarchive-announcement-btn')) {
            action = 'activate';
            status = 'active';
            actionText = 'unarchive';
        }

        if (!announcementId || !action) {
            console.error('Missing announcement ID or action for status change confirmation');
            return;
        }

        this.showStatusChangeConfirmation(announcementId, status, action, actionText);
    }

    showAnnouncementDeleteConfirmation(data) {
        const itemName = this.getTranslatedItemName(data);

        // Use window.confirmDelete if available, otherwise fallback to browser confirm
        if (typeof window.confirmDelete === 'function') {
            window.confirmDelete(itemName, () => {
                this.executeAnnouncementDelete(data.id);
            });
        } else {
            const fallbackMessage = `Are you sure you want to delete the announcement "${data.title}"?\n\nThis action cannot be undone and will remove all associated data.`;
            if (confirm(fallbackMessage)) {
                this.executeAnnouncementDelete(data.id);
            }
        }
    }

    showStatusChangeConfirmation(announcementId, status, action, actionText) {
        const title = `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Announcement`;
        const message = `Are you sure you want to ${actionText} this announcement?`;
        let subtext = '';
        let confirmClass = 'theme-btn-primary';
        let icon = 'fas fa-question-circle';

        // Add specific subtext and styling based on action
        switch (action) {
            case 'activate':
                subtext = 'This will make the announcement visible to users.';
                confirmClass = 'theme-btn-success';
                icon = 'fas fa-play-circle';
                break;
            case 'archive':
                subtext = 'This will hide the announcement from users but preserve its data.';
                confirmClass = 'theme-btn-secondary';
                icon = 'fas fa-archive';
                break;
            case 'cancel_schedule':
                subtext = 'This will cancel the scheduled publication and save as draft.';
                confirmClass = 'theme-btn-warning';
                icon = 'fas fa-times-circle';
                break;
            case 'unarchive':
                subtext = 'This will make the announcement visible to users.';
                confirmClass = 'theme-btn-success';
                icon = 'fas fa-play-circle';
                break;
        }

        // Use window.confirmAction with specific action types for proper styling
        if (typeof window.confirmAction === 'function') {
            const actionType = action === 'cancel_schedule' ? 'pause' : action;
            window.confirmAction(actionType, 'announcement', () => {
                this.executeStatusChange(announcementId, status, action);
            }, message, subtext);
        } else if (typeof window.showConfirmation === 'function') {
            // Fallback to showConfirmation if confirmAction not available
            window.showConfirmation({
                title: title,
                message: message,
                subtext: subtext,
                confirmText: actionText.charAt(0).toUpperCase() + actionText.slice(1),
                confirmClass: confirmClass,
                icon: icon,
                onConfirm: () => {
                    this.executeStatusChange(announcementId, status, action);
                },
                onCancel: () => {
                    console.log(`${actionText} action cancelled`);
                }
            });
        } else {
            // Final fallback to browser confirm
            if (confirm(`${message}\n\n${subtext}`)) {
                this.executeStatusChange(announcementId, status, action);
            }
        }
    }

    executeAnnouncementDelete(announcementId) {
        // Make AJAX request to delete announcement
        const formData = new FormData();
        formData.append('controller', 'AnnouncementController');
        formData.append('action', 'delete');
        formData.append('id', announcementId);

        fetch('index.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message, 'success');
                } else {
                    alert(data.message);
                }
                // Reload announcements
                if (typeof loadAnnouncements === 'function') {
                    loadAnnouncements(typeof window.announcementState !== 'undefined' ? window.announcementState.currentPage : 1);
                }
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message, 'error');
                } else {
                    alert('Error: ' + data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Network error. Please try again.', 'error');
            } else {
                alert('Network error. Please try again.');
            }
        });
    }

    executeStatusChange(announcementId, status, action) {
        // Make AJAX request to update announcement status
        const formData = new FormData();
        formData.append('controller', 'AnnouncementController');
        formData.append('action', 'updateStatus');
        formData.append('announcement_id', announcementId);
        formData.append('status', status);

        fetch('index.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message, 'success');
                } else {
                    alert(data.message);
                }
                // Reload announcements
                if (typeof loadAnnouncements === 'function') {
                    loadAnnouncements(typeof window.announcementState !== 'undefined' ? window.announcementState.currentPage : 1);
                }
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message, 'error');
                } else {
                    alert('Error: ' + data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Network error. Please try again.', 'error');
            } else {
                alert('Network error. Please try again.');
            }
        });
    }

    getTranslatedItemName(data) {
        // Try to get translation, fallback to default format
        const replacements = { title: data.title };
        return this.getTranslation('item.announcement', replacements) || `announcement "${data.title}"`;
    }

    getTranslation(key, replacements = {}) {
        // Try to get translation from global translations object
        if (typeof window.translations !== 'undefined' && window.translations[key]) {
            let translation = window.translations[key];
            // Replace placeholders
            Object.keys(replacements).forEach(placeholder => {
                translation = translation.replace(`{${placeholder}}`, replacements[placeholder]);
            });
            return translation;
        }
        return null;
    }

    // Static helper methods
    static deleteAnnouncement(id, title) {
        const data = { id: id, title: title };
        const itemName = AnnouncementConfirmations.getStaticTranslatedItemName(data);

        if (typeof window.confirmDelete === 'function') {
            window.confirmDelete(itemName, () => {
                AnnouncementConfirmations.staticExecuteAnnouncementDelete(id);
            });
        } else {
            const fallbackMessage = `Are you sure you want to delete the announcement "${title}"?\n\nThis action cannot be undone and will remove all associated data.`;
            if (confirm(fallbackMessage)) {
                AnnouncementConfirmations.staticExecuteAnnouncementDelete(id);
            }
        }
    }

    static staticExecuteAnnouncementDelete(announcementId) {
        // Same as instance method but static
        const formData = new FormData();
        formData.append('controller', 'AnnouncementController');
        formData.append('action', 'delete');
        formData.append('id', announcementId);

        fetch('index.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message, 'success');
                } else {
                    alert(data.message);
                }
                // Reload announcements
                if (typeof loadAnnouncements === 'function') {
                    loadAnnouncements(typeof window.announcementState !== 'undefined' ? window.announcementState.currentPage : 1);
                }
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message, 'error');
                } else {
                    alert('Error: ' + data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Network error. Please try again.', 'error');
            } else {
                alert('Network error. Please try again.');
            }
        });
    }

    static getStaticTranslatedItemName(data) {
        const replacements = { title: data.title };
        return AnnouncementConfirmations.getStaticTranslation('item.announcement', replacements) || `announcement "${data.title}"`;
    }

    static getStaticTranslation(key, replacements = {}) {
        if (typeof window.translations !== 'undefined' && window.translations[key]) {
            let translation = window.translations[key];
            Object.keys(replacements).forEach(placeholder => {
                translation = translation.replace(`{${placeholder}}`, replacements[placeholder]);
            });
            return translation;
        }
        return null;
    }
}

// Initialize Announcement confirmations
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on an announcement page
    if (document.querySelector('#announcementsGrid, .announcement-card, [data-announcement-page]')) {
        window.announcementConfirmationsInstance = new AnnouncementConfirmations();
    }
});

// Also initialize immediately if DOM is already loaded
if (document.readyState !== 'loading') {
    const announcementPageElement = document.querySelector('#announcementsGrid, .announcement-card, [data-announcement-page]');
    if (announcementPageElement) {
        window.announcementConfirmationsInstance = new AnnouncementConfirmations();
    }
}

// Global helper functions
window.deleteAnnouncement = function(id, title) {
    AnnouncementConfirmations.deleteAnnouncement(id, title);
};
