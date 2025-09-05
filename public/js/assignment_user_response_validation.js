/**
 * Assignment User Response Validation
 * 
 * Client-side validation for assignment submission forms
 * Uses existing CSS classes: .is-invalid and .error-message
 */

class AssignmentUserResponseValidation {
    constructor() {
        this.form = null;
        this.isInitialized = false;
    }

    /**
     * Initialize validation for assignment form
     * @param {HTMLElement} formElement - The form element to validate
     */
    init(formElement) {
        if (!formElement) {
            return;
        }

        this.form = formElement;
        this.isInitialized = true;
        
        // Bind form submission
        this.bindFormSubmission();
        
        // Bind real-time validation
        this.bindRealTimeValidation();
    }

    /**
     * Clear all validation errors
     */
    clearValidationErrors() {
        if (!this.form) return;

        // Remove error classes from all form elements
        this.form.querySelectorAll('.is-invalid').forEach(element => {
            element.classList.remove('is-invalid');
        });
        
        // Remove all error messages
        this.form.querySelectorAll('.error-message').forEach(element => {
            element.remove();
        });
    }

    /**
     * Show field error
     * @param {HTMLElement} field - The field to show error for
     * @param {string} message - Error message to display
     */
    showFieldError(field, message) {
        if (!field) {
            return;
        }

        // Add error class to field
        field.classList.add('is-invalid');
        
        // Create error message element
        const errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.textContent = message;
        
        // Insert error message after the field
        field.parentNode.insertBefore(errorElement, field.nextSibling);
    }

    /**
     * Validate assignment form
     * @returns {boolean} - True if valid, false if invalid
     */
    validateForm() {
        if (!this.form) {
            return false;
        }

        this.clearValidationErrors();
        
        let isValid = true;
        const currentInputs = this.form.querySelectorAll('input[name="submission_type"], input[name="submission_type[]"]');
        const isCheckboxMode = currentInputs.length > 0 && currentInputs[0].type === 'checkbox';
        
        // Get selected submission types
        let selectedTypes = [];
        if (isCheckboxMode) {
            currentInputs.forEach(input => {
                if (input.checked) {
                    selectedTypes.push(input.value);
                }
            });
        } else {
            const checkedInput = this.form.querySelector('input[name="submission_type"]:checked');
            if (checkedInput) {
                selectedTypes.push(checkedInput.value);
            }
        }
        
        // Validate that at least one submission type is selected
        if (selectedTypes.length === 0) {
            const firstInput = currentInputs[0];
            if (firstInput) {
                this.showFieldError(firstInput, 'Please select at least one submission type.');
                isValid = false;
            }
        }
        
        // Validate based on selected submission types
        selectedTypes.forEach(type => {
            switch (type) {
                case 'text_entry':
                    const textArea = this.form.querySelector('#submission_text');
                    if (!textArea || textArea.value.trim() === '') {
                        this.showFieldError(textArea, 'Please provide text content for text entry submission.');
                        isValid = false;
                    }
                    break;
                    
                case 'file_upload':
                    const fileInput = this.form.querySelector('#submission_file');
                    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                        this.showFieldError(fileInput, 'Please upload a file for file upload submission.');
                        isValid = false;
                    } else {
                        // Validate file size (50MB limit)
                        const file = fileInput.files[0];
                        const maxSize = 50 * 1024 * 1024; // 50MB in bytes
                        if (file.size > maxSize) {
                            this.showFieldError(fileInput, 'File size must be less than 50MB.');
                            isValid = false;
                        }
                        
                        // Validate file type
                        const allowedTypes = [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'text/plain',
                            'application/rtf',
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/gif'
                        ];
                        if (!allowedTypes.includes(file.type)) {
                            this.showFieldError(fileInput, 'Please upload a valid file type (PDF, DOC, DOCX, TXT, RTF, JPG, PNG, GIF).');
                            isValid = false;
                        }
                    }
                    break;
                    
                case 'url_submission':
                    const urlInput = this.form.querySelector('#submission_url');
                    if (!urlInput || urlInput.value.trim() === '') {
                        this.showFieldError(urlInput, 'Please provide a URL for URL submission.');
                        isValid = false;
                    } else {
                        // Validate URL format
                        const urlPattern = /^https?:\/\/.+/;
                        if (!urlPattern.test(urlInput.value.trim())) {
                            this.showFieldError(urlInput, 'Please enter a valid URL (starting with http:// or https://).');
                            isValid = false;
                        }
                    }
                    break;
            }
        });
        
        return isValid;
    }

    /**
     * Bind form submission validation
     */
    bindFormSubmission() {
        if (!this.form) {
            return;
        }

        this.form.addEventListener('submit', (e) => {
            // Validate form before submission
            const isValid = this.validateForm();
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // If validation passes, allow the original form submission logic to handle it
            return true;
        });
    }

    /**
     * Bind real-time validation
     */
    bindRealTimeValidation() {
        if (!this.form) return;

        // Clear errors when submission type changes
        this.form.addEventListener('change', (e) => {
            if (e.target.matches('input[name="submission_type"], input[name="submission_type[]"]')) {
                // Clear validation errors when submission type changes
                this.clearValidationErrors();
            }
        });
        
        // Clear errors when user starts typing/selecting
        this.form.addEventListener('input', (e) => {
            if (e.target.matches('#submission_text, #submission_url, #submission_file')) {
                // Clear error for this specific field
                e.target.classList.remove('is-invalid');
                const errorMessage = e.target.parentNode.querySelector('.error-message');
                if (errorMessage) {
                    errorMessage.remove();
                }
            }
        });
    }

    /**
     * Get validation status
     * @returns {boolean} - True if form is valid
     */
    isValid() {
        return this.validateForm();
    }
}

// Global instance
window.AssignmentUserResponseValidation = AssignmentUserResponseValidation;

// Global initialization function for manual calls
window.initializeAssignmentValidation = function(formElement) {
    if (formElement) {
        const validator = new AssignmentUserResponseValidation();
        validator.init(formElement);
        return validator;
    } else {
        return null;
    }
};

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Look for assignment forms and initialize validation
    const assignmentForms = document.querySelectorAll('#assignmentSubmissionForm');
    
    assignmentForms.forEach((form, index) => {
        const validator = new AssignmentUserResponseValidation();
        validator.init(form);
    });
    
    // Also try to initialize after a delay for dynamically loaded content
    setTimeout(() => {
        const delayedForms = document.querySelectorAll('#assignmentSubmissionForm');
        
        delayedForms.forEach((form, index) => {
            if (!form.hasAttribute('data-validation-initialized')) {
                const validator = new AssignmentUserResponseValidation();
                validator.init(form);
                form.setAttribute('data-validation-initialized', 'true');
            }
        });
    }, 1000);
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AssignmentUserResponseValidation;
}
