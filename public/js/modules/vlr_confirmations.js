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

if (typeof VLRConfirmations === 'undefined') {
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
        // Use the actual package title instead of trying to translate the type
        // This ensures the confirmation shows the real package name
        return `${data.type} "${data.title}"`;
    }

    getVLRSelectors() {
        return [
            '.delete-scorm',
            '.delete-non-scorm', 
            '.delete-assessment',
            '.delete-assignment',
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

        // Special handling for assessment packages - check if they have attempts
        if (data.type === 'assessment') {
            const assessmentData = button.dataset.assessment;
            if (assessmentData) {
                try {
                    const parsedData = JSON.parse(assessmentData);
                    if (parsedData.has_attempts) {
                        // Assessment has attempts, show toaster and don't proceed
                        if (typeof showSimpleToast === 'function') {
                            showSimpleToast('Cannot delete assessment: Assessment has been started by users and cannot be deleted.', 'error');
                        } else {
                            alert('Cannot delete assessment: Assessment has been started by users and cannot be deleted.');
                        }
                        return; // Stop here, don't show confirmation
                    }
                } catch (e) {
                    console.error('Error parsing assessment data:', e);
                }
            }
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
            action = '/unlockyourskills/vlr/scorm/';
        } else if (classList.contains('delete-non-scorm')) {
            type = 'non-SCORM package';
            action = '/unlockyourskills/vlr/non-scorm/';
        } else if (classList.contains('delete-assessment')) {
            type = 'assessment';
            action = '/unlockyourskills/vlr/assessment-packages/';
        } else if (classList.contains('delete-assignment')) {
            type = 'assignment';
            action = '/unlockyourskills/vlr/assignment/';
        } else if (classList.contains('delete-audio')) {
            type = 'audio package';
            action = '/unlockyourskills/vlr/audio/';
        } else if (classList.contains('delete-video')) {
            type = 'video package';
            action = '/unlockyourskills/vlr/video/';
        } else if (classList.contains('delete-image')) {
            type = 'image package';
            action = '/unlockyourskills/vlr/images/';
        } else if (classList.contains('delete-document')) {
            type = 'document';
            action = '/unlockyourskills/vlr/documents/';
        } else if (classList.contains('delete-external')) {
            type = 'external content';
            action = '/unlockyourskills/vlr/external/';
        } else if (classList.contains('delete-interactive')) {
            type = 'interactive content';
            action = '/unlockyourskills/vlr/interactive/';
        } else if (classList.contains('delete-survey')) {
            type = 'survey';
            action = '/unlockyourskills/vlr/surveys/';
        } else if (classList.contains('delete-feedback')) {
            type = 'feedback';
            action = '/unlockyourskills/vlr/feedback/';
        }

        return {
            type: type,
            id: button.dataset.id,
            title: button.dataset.title || 'Untitled',
            action: `${action}${button.dataset.id}`
        };
    }

    showVLRConfirmation(data) {
        const itemName = this.getTranslatedItemName(data);

        if (typeof confirmDelete === 'function') {
            const callback = () => {
                console.log('🔗 VLR Callback executing! Making DELETE request to:', data.action);
                this.performDeleteRequest(data.action);
            };
            confirmDelete(itemName, callback);
        } else {
            const fallbackMessage = this.getTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete ${itemName}?`;
            if (confirm(fallbackMessage)) {
                this.performDeleteRequest(data.action);
            }
        }
    }

    performDeleteRequest(url) {
        // Create a form to submit DELETE request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        
        // Add method override for DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        // Add CSRF token if available
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }
        
        // Submit the form
        document.body.appendChild(form);
        form.submit();
    }

    // Static helper methods for VLR
    static deletePackage(type, id, title) {
        const actionMap = {
            'scorm': '/unlockyourskills/vlr/scorm/',
            'non-scorm': '/unlockyourskills/vlr/non-scorm/',
            'assessment': '/unlockyourskills/vlr/assessment-packages/',
            'assignment': '/unlockyourskills/vlr/assignment/',
            'audio': '/unlockyourskills/vlr/audio/',
            'video': '/unlockyourskills/vlr/video/',
            'image': '/unlockyourskills/vlr/images/',
            'document': '/unlockyourskills/vlr/documents/',
            'external': '/unlockyourskills/vlr/external/',
            'interactive': '/unlockyourskills/vlr/interactive/',
            'survey': '/unlockyourskills/vlr/surveys/',
            'feedback': '/unlockyourskills/vlr/feedback/'
        };

        const action = actionMap[type];
        if (!action) {
            console.error('Unknown VLR package type:', type);
            return;
        }

        const url = `${action}${id}`;
        const itemName = VLRConfirmations.getStaticTranslatedItemName(type, title);

        if (typeof confirmDelete === 'function') {
            confirmDelete(itemName, () => {
                VLRConfirmations.performStaticDeleteRequest(url);
            });
        } else {
            const fallbackMessage = VLRConfirmations.getStaticTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete ${itemName}?`;
            if (confirm(fallbackMessage)) {
                VLRConfirmations.performStaticDeleteRequest(url);
            }
        }
    }

    static performStaticDeleteRequest(url) {
        // Create a form to submit DELETE request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        
        // Add method override for DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        // Add CSRF token if available
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }
        
        // Submit the form
        document.body.appendChild(form);
        form.submit();
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
        // Use the actual package title instead of trying to translate the type
        // This ensures the confirmation shows the real package name
        return `${type} package "${title}"`;
    }
}

// Initialize VLR confirmations
if (document.readyState !== 'loading') {
    window.vlrConfirmationsInstance = new VLRConfirmations();
} else {
    document.addEventListener('DOMContentLoaded', function() {
        window.vlrConfirmationsInstance = new VLRConfirmations();
    });
}

// Global VLR helper function
window.deleteVLRPackage = function(type, id, title) {
    VLRConfirmations.deletePackage(type, id, title);
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VLRConfirmations;
}
}
