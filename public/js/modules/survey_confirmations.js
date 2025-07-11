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

    // Helper function to get translation with fallback
    getTranslation(key, replacements = {}) {
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

    // Get translated item name for survey/feedback questions
    getTranslatedItemName(data) {
        const replacements = { title: data.title };

        if (data.type === 'survey question') {
            return this.getTranslation('item.survey_question', replacements) || `survey question "${data.title}"`;
        } else if (data.type === 'feedback question') {
            return this.getTranslation('item.feedback_question', replacements) || `feedback question "${data.title}"`;
        } else {
            return this.getTranslation('item.question', replacements) || `${data.type} "${data.title}"`;
        }
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
        let action = '';
        
        if (isSurvey) {
            type = 'survey question';
            controller = 'SurveyQuestionController';
            action = `/unlockyourskills/surveys/${button.dataset.id}/delete`;
        } else if (isFeedback) {
            type = 'feedback question';
            controller = 'FeedbackQuestionController';
            action = `/unlockyourskills/feedback/${button.dataset.id}`;
        }

        return {
            type: type,
            id: button.dataset.id,
            title: button.dataset.title || 'Untitled Question',
            action: action
        };
    }

    showSurveyFeedbackConfirmation(data) {
        const itemName = this.getTranslatedItemName(data);

        const doDelete = () => {
            if (data.type === 'feedback question') {
                // Submit a hidden form with _method=DELETE for feedback question
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = data.action;
                form.style.display = 'none';

                // Add the _method=DELETE hidden input
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                form.appendChild(methodInput);

                // Add CSRF token here if required by your app

                document.body.appendChild(form);
                form.submit();
            } else {
                // For survey question, just redirect (existing behavior)
                window.location.href = data.action;
            }
        };

        if (typeof window.confirmDelete === 'function') {
            window.confirmDelete(itemName, doDelete);
        } else {
            const fallbackMessage = this.getTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete ${itemName}?`;
            if (confirm(fallbackMessage)) {
                doDelete();
            }
        }
    }

    // Static helper methods
    static deleteSurveyQuestion(id, title) {
        const url = `/unlockyourskills/surveys/${id}/delete`;
        const data = { title: title };
        const itemName = SurveyConfirmations.getStaticTranslatedItemName(data);

        if (typeof window.confirmDelete === 'function') {
            window.confirmDelete(itemName, () => {
                window.location.href = url;
            });
        } else {
            const fallbackMessage = SurveyConfirmations.getStaticTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete ${itemName}?`;
            if (confirm(fallbackMessage)) {
                window.location.href = url;
            }
        }
    }

    static deleteFeedbackQuestion(id, title) {
        const url = `/unlockyourskills/feedback/${id}`;
        const data = { title: title };
        const replacements = { title: title };
        const itemName = SurveyConfirmations.getStaticTranslation('item.feedback_question', replacements) || `feedback question "${title}"`;

        if (typeof window.confirmDelete === 'function') {
            window.confirmDelete(itemName, () => {
                window.location.href = url;
            });
        } else {
            const fallbackMessage = SurveyConfirmations.getStaticTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete ${itemName}?`;
            if (confirm(fallbackMessage)) {
                window.location.href = url;
            }
        }
    }

    // Static helper methods for translations
    static getStaticTranslation(key, replacements = {}) {
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

    static getStaticTranslatedItemName(data) {
        const replacements = { title: data.title };
        // For static methods, we assume survey questions by default
        return SurveyConfirmations.getStaticTranslation('item.survey_question', replacements) || `survey question "${data.title}"`;
    }
}

// Initialize Survey confirmations
document.addEventListener('DOMContentLoaded', function() {
    // Check for survey/feedback page elements
    const surveyPageElement = document.querySelector(
        '.survey-container, .feedback-container, [data-survey-page], [data-feedback-page], ' +
        '.add-survey-question-container, .add-feedback-question-container, ' +
        '#surveyQuestionGrid, #feedbackQuestionGrid, ' +
        '.delete-survey-question, .delete-feedback-question'
    );

    if (surveyPageElement) {
        window.surveyConfirmationsInstance = new SurveyConfirmations();
    }
});

// Also initialize immediately if DOM is already loaded
if (document.readyState !== 'loading') {
    const surveyPageElement = document.querySelector(
        '.survey-container, .feedback-container, [data-survey-page], [data-feedback-page], ' +
        '.add-survey-question-container, .add-feedback-question-container, ' +
        '#surveyQuestionGrid, #feedbackQuestionGrid, ' +
        '.delete-survey-question, .delete-feedback-question'
    );

    if (surveyPageElement) {
        window.surveyConfirmationsInstance = new SurveyConfirmations();
    }
}

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
