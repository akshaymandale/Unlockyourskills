/**
 * Assessment Module Confirmation Handlers
 * Handles all delete confirmations for Assessment Question Management
 * 
 * Features:
 * - Dynamic content support (AJAX-loaded questions)
 * - Question-specific confirmations
 * - Fallback support for different modal systems
 */

class AssessmentConfirmations {
    constructor() {
        this.init();
    }

    init() {
        // Assessment question delete confirmations
        document.addEventListener('click', (e) => {
            if (e.target.closest('.delete-assessment-question')) {
                e.preventDefault();
                this.handleQuestionDelete(e.target.closest('.delete-assessment-question'));
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

    // Get translated item name for questions
    getTranslatedItemName(data) {
        const replacements = { title: data.title };
        return this.getTranslation('item.assessment_question', replacements) || `assessment question "${data.title}"`;
    }

    handleQuestionDelete(button) {
        const data = this.extractQuestionData(button);
        
        if (!data.id) {
            console.error('Assessment question delete button missing ID:', button);
            return;
        }

        this.showQuestionConfirmation(data);
    }

    extractQuestionData(button) {
        return {
            id: button.dataset.id,
            title: button.dataset.title || 'Untitled Question',
            action: `/unlockyourskills/vlr/questions/${button.dataset.id}`
        };
    }

    showQuestionConfirmation(data) {
        const itemName = this.getTranslatedItemName(data);

        // Use window.confirmDelete if available, otherwise fallback to browser confirm
        if (typeof window.confirmDelete === 'function') {
            window.confirmDelete(itemName, () => {
                window.location.href = data.action;
            });
        } else {
            const fallbackMessage = this.getTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete ${itemName}?`;
            if (confirm(fallbackMessage)) {
                window.location.href = data.action;
            }
        }
    }

    // Static helper method
    static deleteQuestion(id, title) {
        const url = `/unlockyourskills/vlr/questions/${id}`;
        const data = { title: title };
        const itemName = AssessmentConfirmations.getStaticTranslatedItemName(data);

        if (typeof window.confirmDelete === 'function') {
            window.confirmDelete(itemName, () => {
                window.location.href = url;
            });
        } else {
            const fallbackMessage = AssessmentConfirmations.getStaticTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete ${itemName}?`;
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
        return AssessmentConfirmations.getStaticTranslation('item.assessment_question', replacements) || `assessment question "${data.title}"`;
    }
}

// Initialize Assessment confirmations
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on an assessment page
    if (document.querySelector('#questionsTableBody, .assessment-container, [data-assessment-page]')) {
        window.assessmentConfirmationsInstance = new AssessmentConfirmations();
    }
});

// Global helper function
window.deleteAssessmentQuestion = function(id, title) {
    AssessmentConfirmations.deleteQuestion(id, title);
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AssessmentConfirmations;
}
