/**
 * Survey & Feedback Confirmation Handlers
 * Handles all delete confirmations for Survey and Feedback modules
 * 
 * Features:
 * - Survey question confirmations
 * - Feedback question confirmations
 * - Survey response confirmations
 */

class SurveyConfirmations {
    constructor() {
        this.init();
    }

    init() {
        // Survey and feedback delete confirmations
        document.addEventListener('click', (e) => {
            const target = e.target.closest('.delete-survey-question, .delete-feedback-question');
            if (target) {
                e.preventDefault();
                this.handleSurveyFeedbackDelete(target);
            }
        });
    }

    handleSurveyFeedbackDelete(button) {
        const data = this.extractSurveyFeedbackData(button);
        
        if (!data.id) {
            console.error('Survey/Feedback delete button missing ID:', button);
            return;
        }

        this.showSurveyFeedbackConfirmation(data);
    }

    extractSurveyFeedbackData(button) {
        const isSurvey = button.classList.contains('delete-survey-question');
        const isFeedback = button.classList.contains('delete-feedback-question');
        
        let type = 'question';
        let controller = '';
        
        if (isSurvey) {
            type = 'survey question';
            controller = 'SurveyQuestionController';
        } else if (isFeedback) {
            type = 'feedback question';
            controller = 'FeedbackQuestionController';
        }

        return {
            type: type,
            id: button.dataset.id,
            title: button.dataset.title || 'Untitled Question',
            action: `index.php?controller=${controller}&action=delete&id=${button.dataset.id}`
        };
    }

    showSurveyFeedbackConfirmation(data) {
        const itemName = `${data.type} "${data.title}"`;
        
        if (typeof confirmDelete === 'function') {
            confirmDelete(itemName, () => {
                window.location.href = data.action;
            });
        } else {
            if (confirm(`Are you sure you want to delete ${itemName}?`)) {
                window.location.href = data.action;
            }
        }
    }

    // Static helper methods
    static deleteSurveyQuestion(id, title) {
        const url = `index.php?controller=SurveyQuestionController&action=delete&id=${id}`;
        const itemName = `survey question "${title}"`;

        if (typeof confirmDelete === 'function') {
            confirmDelete(itemName, () => {
                window.location.href = url;
            });
        } else {
            if (confirm(`Are you sure you want to delete ${itemName}?`)) {
                window.location.href = url;
            }
        }
    }

    static deleteFeedbackQuestion(id, title) {
        const url = `index.php?controller=FeedbackQuestionController&action=delete&id=${id}`;
        const itemName = `feedback question "${title}"`;

        if (typeof confirmDelete === 'function') {
            confirmDelete(itemName, () => {
                window.location.href = url;
            });
        } else {
            if (confirm(`Are you sure you want to delete ${itemName}?`)) {
                window.location.href = url;
            }
        }
    }
}

// Initialize Survey confirmations
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on a survey/feedback page
    if (document.querySelector('.survey-container, .feedback-container, [data-survey-page], [data-feedback-page]')) {
        window.surveyConfirmationsInstance = new SurveyConfirmations();
    }
});

// Global helper functions
window.deleteSurveyQuestion = function(id, title) {
    SurveyConfirmations.deleteSurveyQuestion(id, title);
};

window.deleteFeedbackQuestion = function(id, title) {
    SurveyConfirmations.deleteFeedbackQuestion(id, title);
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SurveyConfirmations;
}
