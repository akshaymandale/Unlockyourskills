/**
 * Opinion Poll Confirmation Handlers
 * Handles all delete and status change confirmations for Opinion Poll Management
 * 
 * Features:
 * - Poll delete confirmations
 * - Poll status change confirmations
 * - Proper modal-based confirmations
 * - Consistent with other modules
 */

class OpinionPollConfirmations {
    constructor() {
        this.init();
    }

    init() {
        // Poll delete confirmations
        document.addEventListener('click', (e) => {
            if (e.target.closest('.delete-poll-btn')) {
                e.preventDefault();
                this.handlePollDelete(e.target.closest('.delete-poll-btn'));
            }
        });

        // Poll status change confirmations
        document.addEventListener('click', (e) => {
            const statusButtons = [
                '.activate-poll-btn',
                '.pause-poll-btn', 
                '.resume-poll-btn',
                '.archive-poll-btn'
            ];
            
            const target = e.target.closest(statusButtons.join(', '));
            if (target) {
                e.preventDefault();
                this.handleStatusChange(target);
            }
        });
    }

    handlePollDelete(button) {
        const pollId = button.dataset.pollId;
        const pollTitle = button.dataset.pollTitle;
        
        if (!pollId || !pollTitle) {
            console.error('Missing poll ID or title for delete confirmation');
            return;
        }

        const data = { 
            id: pollId, 
            title: pollTitle 
        };
        
        this.showPollDeleteConfirmation(data);
    }

    handleStatusChange(button) {
        const pollId = button.dataset.pollId;
        let action, status, actionText;

        // Determine action based on button class
        if (button.classList.contains('activate-poll-btn')) {
            action = 'activate';
            status = 'active';
            actionText = 'activate';
        } else if (button.classList.contains('pause-poll-btn')) {
            action = 'pause';
            status = 'paused';
            actionText = 'pause';
        } else if (button.classList.contains('resume-poll-btn')) {
            action = 'resume';
            status = 'active';
            actionText = 'resume';
        } else if (button.classList.contains('archive-poll-btn')) {
            action = 'archive';
            status = 'archived';
            actionText = 'archive';
        }

        if (!pollId || !action) {
            console.error('Missing poll ID or action for status change confirmation');
            return;
        }

        this.showStatusChangeConfirmation(pollId, status, action, actionText);
    }

    showPollDeleteConfirmation(data) {
        const itemName = this.getTranslatedItemName(data);

        // Use window.confirmDelete if available, otherwise fallback to browser confirm
        if (typeof window.confirmDelete === 'function') {
            window.confirmDelete(itemName, () => {
                this.executePollDelete(data.id);
            });
        } else {
            const fallbackMessage = `Are you sure you want to delete the poll "${data.title}"?\n\nThis action cannot be undone and will remove all associated votes and data.`;
            if (confirm(fallbackMessage)) {
                this.executePollDelete(data.id);
            }
        }
    }

    showStatusChangeConfirmation(pollId, status, action, actionText) {
        const title = `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Poll`;
        const message = `Are you sure you want to ${actionText} this poll?`;
        let subtext = '';
        let confirmClass = 'theme-btn-primary';
        let icon = 'fas fa-question-circle';

        // Add specific subtext and styling based on action
        switch (action) {
            case 'activate':
                subtext = 'This will make the poll available to users.';
                confirmClass = 'theme-btn-success';
                icon = 'fas fa-play-circle';
                break;
            case 'pause':
                subtext = 'This will temporarily stop users from voting.';
                confirmClass = 'theme-btn-warning';
                icon = 'fas fa-pause-circle';
                break;
            case 'resume':
                subtext = 'This will allow users to vote again.';
                confirmClass = 'theme-btn-success';
                icon = 'fas fa-play-circle';
                break;
            case 'archive':
                subtext = 'This will permanently close the poll and preserve results.';
                confirmClass = 'theme-btn-secondary';
                icon = 'fas fa-archive';
                break;
        }

        // Use window.confirmAction with specific action types for proper styling
        if (typeof window.confirmAction === 'function') {
            window.confirmAction(action, 'poll', () => {
                this.executeStatusChange(pollId, status, action);
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
                    this.executeStatusChange(pollId, status, action);
                },
                onCancel: () => {
                    console.log(`${actionText} action cancelled`);
                }
            });
        } else {
            // Final fallback to browser confirm
            if (confirm(`${message}\n\n${subtext}`)) {
                this.executeStatusChange(pollId, status, action);
            }
        }
    }

    executePollDelete(pollId) {
        // Make AJAX request to delete poll
        const formData = new FormData();
        formData.append('controller', 'OpinionPollController');
        formData.append('action', 'delete');
        formData.append('id', pollId);

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
                // Reload polls
                if (typeof loadPolls === 'function') {
                    loadPolls(typeof currentPage !== 'undefined' ? currentPage : 1);
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

    executeStatusChange(pollId, status, action) {
        // Make AJAX request to update poll status
        const formData = new FormData();
        formData.append('controller', 'OpinionPollController');
        formData.append('action', 'updateStatus');
        formData.append('poll_id', pollId);
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
                // Reload polls
                if (typeof loadPolls === 'function') {
                    loadPolls(typeof currentPage !== 'undefined' ? currentPage : 1);
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
        return this.getTranslation('item.opinion_poll', replacements) || `opinion poll "${data.title}"`;
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
    static deletePoll(id, title) {
        const data = { id: id, title: title };
        const itemName = OpinionPollConfirmations.getStaticTranslatedItemName(data);

        if (typeof window.confirmDelete === 'function') {
            window.confirmDelete(itemName, () => {
                OpinionPollConfirmations.staticExecutePollDelete(id);
            });
        } else {
            const fallbackMessage = `Are you sure you want to delete the poll "${title}"?\n\nThis action cannot be undone and will remove all associated votes and data.`;
            if (confirm(fallbackMessage)) {
                OpinionPollConfirmations.staticExecutePollDelete(id);
            }
        }
    }

    static staticExecutePollDelete(pollId) {
        // Same as instance method but static
        const formData = new FormData();
        formData.append('controller', 'OpinionPollController');
        formData.append('action', 'delete');
        formData.append('id', pollId);

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
                // Reload polls
                if (typeof loadPolls === 'function') {
                    loadPolls(typeof currentPage !== 'undefined' ? currentPage : 1);
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
        return OpinionPollConfirmations.getStaticTranslation('item.opinion_poll', replacements) || `opinion poll "${data.title}"`;
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

// Initialize Opinion Poll confirmations
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on an opinion poll page
    if (document.querySelector('#pollsGrid, .poll-card, [data-opinion-poll-page]')) {
        window.opinionPollConfirmationsInstance = new OpinionPollConfirmations();
    }
});

// Also initialize immediately if DOM is already loaded
if (document.readyState !== 'loading') {
    const pollPageElement = document.querySelector('#pollsGrid, .poll-card, [data-opinion-poll-page]');
    if (pollPageElement) {
        window.opinionPollConfirmationsInstance = new OpinionPollConfirmations();
    }
}

// Global helper functions
window.deleteOpinionPoll = function(id, title) {
    OpinionPollConfirmations.deletePoll(id, title);
};
