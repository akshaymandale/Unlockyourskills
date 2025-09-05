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
        this.initialized = false;
        this.init();
    }

    init() {
        // Prevent multiple initializations
        if (this.initialized) {
            return;
        }
        
        // VLR-specific delete confirmations
        document.addEventListener('click', (e) => {
            const target = e.target.closest(this.getVLRSelectors());
            if (target) {
                e.preventDefault();
                this.handleVLRDelete(target);
            }
        });
        
        this.initialized = true;
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

        // Special handling for all package types - check if they are assigned to applicable courses
        const packageData = this.getPackageData(button, data.type);
        if (packageData && packageData.is_assigned_to_applicable_courses) {
            console.log(`VLRConfirmations: Showing toast for ${data.type}`, data.id);
            const packageType = this.getPackageTypeName(data.type);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast(`Cannot delete ${packageType}: ${packageType} is assigned to courses that are applicable to users and cannot be deleted.`, 'error');
            } else {
                alert(`Cannot delete ${packageType}: ${packageType} is assigned to courses that are applicable to users and cannot be deleted.`);
            }
            return; // Stop here, don't show confirmation
        }

        this.showVLRConfirmation(data);
    }

    getPackageData(button, type) {
        // Get the appropriate dataset attribute based on package type
        const datasetMap = {
            'SCORM package': 'scorm',
            'non-SCORM package': 'nonScorm',
            'assessment': 'assessment',
            'assignment': 'assignment',
            'audio': 'audio',
            'video': 'video',
            'image': 'image',
            'document': 'document',
            'external': 'external',
            'interactive': 'interactive',
            'survey': 'survey',
            'feedback': 'feedback'
        };

        const datasetKey = datasetMap[type];
        if (!datasetKey) return null;

        const dataString = button.dataset[datasetKey];
        if (!dataString) return null;

        try {
            return JSON.parse(dataString);
        } catch (e) {
            console.error(`Error parsing ${type} data:`, e);
            return null;
        }
    }

    getPackageTypeName(type) {
        const typeMap = {
            'SCORM package': 'SCORM package',
            'non-SCORM package': 'Non-SCORM package',
            'assessment': 'Assessment',
            'assignment': 'Assignment',
            'audio': 'Audio package',
            'video': 'Video package',
            'image': 'Image package',
            'document': 'Document',
            'external': 'External content',
            'interactive': 'Interactive content',
            'survey': 'Survey',
            'feedback': 'Feedback'
        };
        return typeMap[type] || type;
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

// Initialize VLR confirmations (prevent multiple instances)
if (!window.vlrConfirmationsInstance) {
    if (document.readyState !== 'loading') {
        window.vlrConfirmationsInstance = new VLRConfirmations();
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            if (!window.vlrConfirmationsInstance) {
                window.vlrConfirmationsInstance = new VLRConfirmations();
            }
        });
    }
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
