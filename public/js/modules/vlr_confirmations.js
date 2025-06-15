/**
 * VLR Module Confirmation Handlers
 * Handles all delete confirmations for VLR (Virtual Learning Resources) module
 * 
 * Supports all VLR package types:
 * - SCORM packages
 * - Non-SCORM packages  
 * - Assessment packages
 * - Audio packages
 * - Video packages
 * - Image packages
 * - Document packages
 * - External content
 * - Interactive content
 * - Survey packages
 * - Feedback packages
 */

class VLRConfirmations {
    constructor() {
        this.init();
    }

    init() {
        // VLR-specific delete confirmations
        document.addEventListener('click', (e) => {
            const target = e.target.closest(this.getVLRSelectors());
            if (target) {
                e.preventDefault();
                this.handleVLRDelete(target);
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

    // Get translated item name for VLR packages
    getTranslatedItemName(data) {
        const typeMap = {
            'SCORM package': 'item.scorm_package',
            'non-SCORM package': 'item.non_scorm_package',
            'assessment': 'item.assessment',
            'audio package': 'item.audio_package',
            'video package': 'item.video_package',
            'image package': 'item.image_package',
            'document': 'item.document',
            'external content': 'item.external_content',
            'interactive content': 'item.interactive_content',
            'survey': 'item.survey',
            'feedback': 'item.feedback'
        };

        const translationKey = typeMap[data.type] || 'item.scorm_package';
        const replacements = { title: data.title };

        return this.getTranslation(translationKey, replacements) || `${data.type} "${data.title}"`;
    }

    getVLRSelectors() {
        return [
            '.delete-scorm',
            '.delete-non-scorm', 
            '.delete-assessment',
            '.delete-audio',
            '.delete-video', 
            '.delete-image',
            '.delete-document',
            '.delete-external',
            '.delete-interactive',
            '.delete-survey',
            '.delete-feedback'
        ].join(', ');
    }

    handleVLRDelete(button) {
        const data = this.extractVLRData(button);
        
        if (!data.id) {
            console.error('VLR delete button missing ID:', button);
            return;
        }

        this.showVLRConfirmation(data);
    }

    extractVLRData(button) {
        const classList = button.classList;
        let type = 'package';
        let action = '';

        // Determine type and action from class
        if (classList.contains('delete-scorm')) {
            type = 'SCORM package';
            action = 'index.php?controller=VLRController&action=delete&id=';
        } else if (classList.contains('delete-non-scorm')) {
            type = 'non-SCORM package';
            action = 'index.php?controller=VLRController&action=deleteNonScormPackage&id=';
        } else if (classList.contains('delete-assessment')) {
            type = 'assessment';
            action = 'index.php?controller=VLRController&action=deleteAssessment&id=';
        } else if (classList.contains('delete-audio')) {
            type = 'audio package';
            action = 'index.php?controller=VLRController&action=deleteAudioPackage&id=';
        } else if (classList.contains('delete-video')) {
            type = 'video package';
            action = 'index.php?controller=VLRController&action=deleteVideoPackage&id=';
        } else if (classList.contains('delete-image')) {
            type = 'image package';
            action = 'index.php?controller=VLRController&action=deleteImagePackage&id=';
        } else if (classList.contains('delete-document')) {
            type = 'document';
            action = 'index.php?controller=VLRController&action=deleteDocument&id=';
        } else if (classList.contains('delete-external')) {
            type = 'external content';
            action = 'index.php?controller=VLRController&action=deleteExternal&id=';
        } else if (classList.contains('delete-interactive')) {
            type = 'interactive content';
            action = 'index.php?controller=VLRController&action=deleteInteractiveContent&id=';
        } else if (classList.contains('delete-survey')) {
            type = 'survey';
            action = 'index.php?controller=VLRController&action=deleteSurvey&id=';
        } else if (classList.contains('delete-feedback')) {
            type = 'feedback';
            action = 'index.php?controller=VLRController&action=deleteFeedback&id=';
        }

        return {
            type: type,
            id: button.dataset.id,
            title: button.dataset.title || 'Untitled',
            action: action + button.dataset.id
        };
    }

    showVLRConfirmation(data) {
        const itemName = this.getTranslatedItemName(data);

        if (typeof confirmDelete === 'function') {
            const callback = () => {
                console.log('🔗 VLR Callback executing! Redirecting to:', data.action);
                window.location.href = data.action;
            };
            confirmDelete(itemName, callback);
        } else {
            const fallbackMessage = this.getTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete ${itemName}?`;
            if (confirm(fallbackMessage)) {
                window.location.href = data.action;
            }
        }
    }

    // Static helper methods for VLR
    static deletePackage(type, id, title) {
        const actionMap = {
            'scorm': 'delete',
            'non-scorm': 'deleteNonScormPackage',
            'assessment': 'deleteAssessment',
            'audio': 'deleteAudioPackage',
            'video': 'deleteVideoPackage',
            'image': 'deleteImagePackage',
            'document': 'deleteDocument',
            'external': 'deleteExternal',
            'interactive': 'deleteInteractiveContent',
            'survey': 'deleteSurvey',
            'feedback': 'deleteFeedback'
        };

        const action = actionMap[type];
        if (!action) {
            console.error('Unknown VLR package type:', type);
            return;
        }

        const url = `index.php?controller=VLRController&action=${action}&id=${id}`;
        const itemName = VLRConfirmations.getStaticTranslatedItemName(type, title);

        if (typeof confirmDelete === 'function') {
            confirmDelete(itemName, () => {
                window.location.href = url;
            });
        } else {
            const fallbackMessage = VLRConfirmations.getStaticTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete ${itemName}?`;
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

    static getStaticTranslatedItemName(type, title) {
        const typeMap = {
            'scorm': 'item.scorm_package',
            'non-scorm': 'item.non_scorm_package',
            'assessment': 'item.assessment',
            'audio': 'item.audio_package',
            'video': 'item.video_package',
            'image': 'item.image_package',
            'document': 'item.document',
            'external': 'item.external_content',
            'interactive': 'item.interactive_content',
            'survey': 'item.survey',
            'feedback': 'item.feedback'
        };

        const translationKey = typeMap[type] || 'item.scorm_package';
        const replacements = { title: title };

        return VLRConfirmations.getStaticTranslation(translationKey, replacements) || `${type} package "${title}"`;
    }
}

// Initialize VLR confirmations
document.addEventListener('DOMContentLoaded', function() {
    // Initialize VLR confirmations (now directly included in VLR page)
    window.vlrConfirmationsInstance = new VLRConfirmations();
});

// Also initialize immediately if DOM is already loaded
if (document.readyState !== 'loading') {
    // DOM is already loaded, initialize immediately
    window.vlrConfirmationsInstance = new VLRConfirmations();
}

// Global VLR helper function
window.deleteVLRPackage = function(type, id, title) {
    VLRConfirmations.deletePackage(type, id, title);
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VLRConfirmations;
}
