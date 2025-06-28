/**
 * Assignment Package Validation
 * Handles client-side validation for assignment package forms
 */

class AssignmentValidation {
    constructor() {
        this.init();
    }

    init() {
        this.setupValidation();
        this.setupRealTimeValidation();
    }

    setupValidation() {
        const form = document.getElementById('assignmentForm');
        if (!form) return;

        // Form submission validation
        form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                this.showToast('Please fix the validation errors before submitting.', 'error');
                return false;
            }
        });

        // Clear validation on form reset
        const clearBtn = document.getElementById('clearFormassignment');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                this.clearAllValidation();
            });
        }
    }

    setupRealTimeValidation() {
        // Title validation
        const titleInput = document.getElementById('assignment_titleassignment');
        if (titleInput) {
            titleInput.addEventListener('blur', () => this.validateTitle());
            titleInput.addEventListener('input', () => this.clearFieldError(titleInput));
        }

        // File validation
        const fileInput = document.getElementById('assignmentFileassignment');
        if (fileInput) {
            fileInput.addEventListener('change', () => this.validateFile());
        }

        // Version validation
        const versionInput = document.getElementById('versionassignment');
        if (versionInput) {
            versionInput.addEventListener('blur', () => this.validateVersion());
            versionInput.addEventListener('input', () => this.clearFieldError(versionInput));
        }

        // Time limit validation
        const timeLimitInput = document.getElementById('timeLimitassignment');
        if (timeLimitInput) {
            timeLimitInput.addEventListener('blur', () => this.validateTimeLimit());
            timeLimitInput.addEventListener('input', () => this.clearFieldError(timeLimitInput));
        }

        // Estimated duration validation
        const durationInput = document.getElementById('estimatedDurationassignment');
        if (durationInput) {
            durationInput.addEventListener('blur', () => this.validateDuration());
            durationInput.addEventListener('input', () => this.clearFieldError(durationInput));
        }

        // Max attempts validation
        const maxAttemptsInput = document.getElementById('maxAttemptsassignment');
        if (maxAttemptsInput) {
            maxAttemptsInput.addEventListener('blur', () => this.validateMaxAttempts());
            maxAttemptsInput.addEventListener('input', () => this.clearFieldError(maxAttemptsInput));
        }

        // Passing score validation
        const passingScoreInput = document.getElementById('passingScoreassignment');
        if (passingScoreInput) {
            passingScoreInput.addEventListener('blur', () => this.validatePassingScore());
            passingScoreInput.addEventListener('input', () => this.clearFieldError(passingScoreInput));
        }

        // Late submission penalty validation
        const penaltyInput = document.getElementById('lateSubmissionPenaltyassignment');
        if (penaltyInput) {
            penaltyInput.addEventListener('blur', () => this.validatePenalty());
            penaltyInput.addEventListener('input', () => this.clearFieldError(penaltyInput));
        }

        // Tags validation
        const tagInput = document.getElementById('tagInputassignment');
        if (tagInput) {
            tagInput.addEventListener('blur', () => this.validateTags());
        }
    }

    validateForm() {
        const isTitleValid = this.validateTitle();
        const isFileValid = this.validateFile();
        const isVersionValid = this.validateVersion();
        const isTimeLimitValid = this.validateTimeLimit();
        const isDurationValid = this.validateDuration();
        const isMaxAttemptsValid = this.validateMaxAttempts();
        const isPassingScoreValid = this.validatePassingScore();
        const isPenaltyValid = this.validatePenalty();
        const isTagsValid = this.validateTags();

        return isTitleValid && isFileValid && isVersionValid && 
               isTimeLimitValid && isDurationValid && isMaxAttemptsValid && 
               isPassingScoreValid && isPenaltyValid && isTagsValid;
    }

    validateTitle() {
        const titleInput = document.getElementById('assignment_titleassignment');
        const title = titleInput.value.trim();
        
        if (!title) {
            this.showFieldError(titleInput, this.translate('assignment.validation.title_required') || 'Assignment title is required.');
            return false;
        }
        
        if (title.length < 3) {
            this.showFieldError(titleInput, this.translate('assignment.validation.title_min_length') || 'Assignment title must be at least 3 characters long.');
            return false;
        }
        
        if (title.length > 255) {
            this.showFieldError(titleInput, this.translate('assignment.validation.title_max_length') || 'Assignment title cannot exceed 255 characters.');
            return false;
        }
        
        this.clearFieldError(titleInput);
        return true;
    }

    validateFile() {
        const fileInput = document.getElementById('assignmentFileassignment');
        const file = fileInput.files[0];
        const existingFile = document.getElementById('existing_assignmentassignment').value;
        
        if (!file && !existingFile) {
            this.showFieldError(fileInput, this.translate('assignment.validation.file_required') || 'Assignment file is required.');
            return false;
        }
        
        if (file) {
            const allowedTypes = ['.pdf', '.doc', '.docx', '.txt', '.rtf'];
            const fileName = file.name.toLowerCase();
            const isValidType = allowedTypes.some(type => fileName.endsWith(type));
            
            if (!isValidType) {
                this.showFieldError(fileInput, this.translate('assignment.validation.file_type_invalid') || 'Please select a valid file type (PDF, DOC, DOCX, TXT, RTF).');
                return false;
            }
            
            if (file.size > 50 * 1024 * 1024) { // 50MB
                this.showFieldError(fileInput, this.translate('assignment.validation.file_size_exceeded') || 'File size must be less than 50MB.');
                return false;
            }
        }
        
        this.clearFieldError(fileInput);
        return true;
    }

    validateVersion() {
        const versionInput = document.getElementById('versionassignment');
        const version = versionInput.value.trim();
        
        if (!version) {
            this.showFieldError(versionInput, this.translate('validation.version_required') || 'Version number is required.');
            return false;
        }
        
        if (isNaN(version) || parseFloat(version) < 0) {
            this.showFieldError(versionInput, this.translate('assignment.validation.version_positive') || 'Version must be a positive number.');
            return false;
        }
        
        if (parseFloat(version) > 999.99) {
            this.showFieldError(versionInput, this.translate('assignment.validation.version_max') || 'Version number cannot exceed 999.99.');
            return false;
        }
        
        this.clearFieldError(versionInput);
        return true;
    }

    validateTimeLimit() {
        const timeLimitInput = document.getElementById('timeLimitassignment');
        const timeLimit = timeLimitInput.value.trim();
        
        if (timeLimit && (isNaN(timeLimit) || parseInt(timeLimit) < 1)) {
            this.showFieldError(timeLimitInput, this.translate('assignment.validation.time_limit_positive') || 'Time limit must be a positive number.');
            return false;
        }
        
        if (timeLimit && parseInt(timeLimit) > 1440) { // 24 hours in minutes
            this.showFieldError(timeLimitInput, this.translate('assignment.validation.time_limit_max') || 'Time limit cannot exceed 24 hours (1440 minutes).');
            return false;
        }
        
        this.clearFieldError(timeLimitInput);
        return true;
    }

    validateDuration() {
        const durationInput = document.getElementById('estimatedDurationassignment');
        const duration = durationInput.value.trim();
        
        if (duration && (isNaN(duration) || parseInt(duration) < 1)) {
            this.showFieldError(durationInput, this.translate('assignment.validation.duration_positive') || 'Estimated duration must be a positive number.');
            return false;
        }
        
        if (duration && parseInt(duration) > 1440) { // 24 hours in minutes
            this.showFieldError(durationInput, this.translate('assignment.validation.duration_max') || 'Estimated duration cannot exceed 24 hours (1440 minutes).');
            return false;
        }
        
        this.clearFieldError(durationInput);
        return true;
    }

    validateMaxAttempts() {
        const maxAttemptsInput = document.getElementById('maxAttemptsassignment');
        const maxAttempts = maxAttemptsInput.value.trim();
        
        if (maxAttempts && (isNaN(maxAttempts) || parseInt(maxAttempts) < 1)) {
            this.showFieldError(maxAttemptsInput, this.translate('assignment.validation.max_attempts_positive') || 'Max attempts must be a positive number.');
            return false;
        }
        
        if (maxAttempts && parseInt(maxAttempts) > 100) {
            this.showFieldError(maxAttemptsInput, this.translate('assignment.validation.max_attempts_max') || 'Max attempts cannot exceed 100.');
            return false;
        }
        
        this.clearFieldError(maxAttemptsInput);
        return true;
    }

    validatePassingScore() {
        const passingScoreInput = document.getElementById('passingScoreassignment');
        const passingScore = passingScoreInput.value.trim();
        
        if (passingScore && (isNaN(passingScore) || parseFloat(passingScore) < 0)) {
            this.showFieldError(passingScoreInput, this.translate('assignment.validation.passing_score_range') || 'Passing score must be a non-negative number.');
            return false;
        }
        
        if (passingScore && parseFloat(passingScore) > 100) {
            this.showFieldError(passingScoreInput, this.translate('assignment.validation.passing_score_max') || 'Passing score cannot exceed 100%.');
            return false;
        }
        
        this.clearFieldError(passingScoreInput);
        return true;
    }

    validatePenalty() {
        const penaltyInput = document.getElementById('lateSubmissionPenaltyassignment');
        const penalty = penaltyInput.value.trim();
        
        if (penalty && (isNaN(penalty) || parseFloat(penalty) < 0)) {
            this.showFieldError(penaltyInput, this.translate('assignment.validation.penalty_range') || 'Late submission penalty must be a non-negative number.');
            return false;
        }
        
        if (penalty && parseFloat(penalty) > 100) {
            this.showFieldError(penaltyInput, this.translate('assignment.validation.penalty_max') || 'Late submission penalty cannot exceed 100%.');
            return false;
        }
        
        this.clearFieldError(penaltyInput);
        return true;
    }

    validateTags() {
        const tagList = document.getElementById('tagListassignment');
        const tags = tagList.value.trim();
        
        if (!tags) {
            this.showFieldError(document.getElementById('tagInputassignment'), this.translate('validation.tags_required') || 'At least one tag is required.');
            return false;
        }
        
        const tagArray = tags.split(',').map(tag => tag.trim()).filter(tag => tag);
        
        if (tagArray.length === 0) {
            this.showFieldError(document.getElementById('tagInputassignment'), this.translate('validation.tags_required') || 'At least one tag is required.');
            return false;
        }
        
        // Check for duplicate tags
        const uniqueTags = [...new Set(tagArray)];
        if (uniqueTags.length !== tagArray.length) {
            this.showFieldError(document.getElementById('tagInputassignment'), this.translate('assignment.validation.tags_duplicate') || 'Duplicate tags are not allowed.');
            return false;
        }
        
        // Check tag length
        for (let tag of tagArray) {
            if (tag.length < 2) {
                this.showFieldError(document.getElementById('tagInputassignment'), this.translate('assignment.validation.tags_min_length') || 'Each tag must be at least 2 characters long.');
                return false;
            }
            if (tag.length > 50) {
                this.showFieldError(document.getElementById('tagInputassignment'), this.translate('assignment.validation.tags_max_length') || 'Each tag cannot exceed 50 characters.');
                return false;
            }
        }
        
        this.clearFieldError(document.getElementById('tagInputassignment'));
        return true;
    }

    showFieldError(field, message) {
        this.clearFieldError(field);
        field.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    clearAllValidation() {
        const form = document.getElementById('assignmentForm');
        if (!form) return;

        const invalidFields = form.querySelectorAll('.is-invalid');
        invalidFields.forEach(field => this.clearFieldError(field));
    }

    translate(key) {
        // Use existing translation system if available
        if (typeof translate === 'function') {
            return translate(key);
        }
        
        // Fallback to window.translations if available
        if (window.translations && window.translations[key]) {
            return window.translations[key];
        }
        
        return key;
    }

    showToast(message, type = 'info') {
        // Use existing toast system if available
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            // Fallback to alert
            alert(message);
        }
    }
}

// Initialize assignment validation when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.assignmentValidation = new AssignmentValidation();
}); 