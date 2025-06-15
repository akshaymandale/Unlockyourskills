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
            action: `index.php?controller=QuestionController&action=delete&id=${button.dataset.id}`
        };
    }

    showQuestionConfirmation(data) {
        const itemName = `assessment question "${data.title}"`;
        
        // Use confirmDelete if available, otherwise fallback to browser confirm
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

    // Static helper method
    static deleteQuestion(id, title) {
        const url = `index.php?controller=QuestionController&action=delete&id=${id}`;
        const itemName = `assessment question "${title}"`;

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
