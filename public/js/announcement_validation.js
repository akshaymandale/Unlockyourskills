/**
 * Announcement Form Validation System
 * Following the same patterns as Opinion Poll validation
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Announcement validation system loaded');
    
    // Initialize validation for both create and edit forms
    initializeAnnouncementValidation();
});

function initializeAnnouncementValidation() {
    const createForm = document.getElementById('createAnnouncementForm');
    const editForm = document.getElementById('editAnnouncementForm');
    
    if (createForm) {
        console.log('Initializing create form validation');
        setupFormValidation(createForm, 'create');
    }
    
    if (editForm) {
        console.log('Initializing edit form validation');
        setupFormValidation(editForm, 'edit');
    }
}

function setupFormValidation(form, formType) {
    const formId = form.id;
    
    // Add form submit handler
    form.addEventListener('submit', function(e) {
        console.log(`Form submit handler triggered for: ${formId}`);
        
        if (!validateAnnouncementForm(form)) {
            console.log('Form validation failed, preventing submission');
            e.preventDefault();
            return false;
        }
        
        console.log('Form is valid, proceeding with submission...');
        // Form will submit normally via the existing handlers
    });
    
    // Add real-time validation on field blur
    const fields = form.querySelectorAll('input, textarea, select');
    fields.forEach(field => {
        field.addEventListener('blur', function() {
            validateField(this, formType);
        });
        
        // Special handling for datetime fields
        if (field.type === 'datetime-local') {
            field.addEventListener('change', function() {
                validateField(this, formType);
                validateDateRange(form);
            });
        }
    });
    
    // CTA field dependencies
    const ctaLabel = form.querySelector('input[name="cta_label"]');
    const ctaUrl = form.querySelector('input[name="cta_url"]');
    
    if (ctaLabel && ctaUrl) {
        ctaLabel.addEventListener('input', function() {
            if (this.value.trim() && !ctaUrl.value.trim()) {
                showFieldError(ctaUrl, 'CTA URL is required when CTA label is provided.');
            } else {
                clearFieldError(ctaUrl);
            }
        });
        
        ctaUrl.addEventListener('input', function() {
            if (ctaLabel.value.trim() && !this.value.trim()) {
                showFieldError(this, 'CTA URL is required when CTA label is provided.');
            } else {
                clearFieldError(this);
            }
        });
    }
}

function validateAnnouncementForm(form) {
    console.log('Validating announcement form:', form.id);
    
    let isValid = true;
    const formType = form.id.includes('edit') ? 'edit' : 'create';
    
    // Clear previous errors
    clearAllErrors(form);
    
    // Validate all fields
    const fields = form.querySelectorAll('input[required], textarea[required], select[required]');
    fields.forEach(field => {
        if (!validateField(field, formType)) {
            isValid = false;
        }
    });
    
    // Validate non-required but important fields
    const title = form.querySelector('input[name="title"]');
    const body = form.querySelector('textarea[name="body"]');
    const audienceType = form.querySelector('select[name="audience_type"]');
    const ctaLabel = form.querySelector('input[name="cta_label"]');
    const ctaUrl = form.querySelector('input[name="cta_url"]');
    
    if (title && !validateField(title, formType)) isValid = false;
    if (body && !validateField(body, formType)) isValid = false;
    if (audienceType && !validateField(audienceType, formType)) isValid = false;
    
    // Validate CTA fields
    if (ctaLabel && ctaUrl) {
        const hasLabel = ctaLabel.value.trim();
        const hasUrl = ctaUrl.value.trim();
        
        if (hasLabel && !hasUrl) {
            showFieldError(ctaUrl, 'CTA URL is required when CTA label is provided.');
            isValid = false;
        }
        
        if (hasUrl && !isValidUrl(hasUrl)) {
            showFieldError(ctaUrl, 'Please enter a valid URL.');
            isValid = false;
        }
    }
    
    // Validate date range
    if (!validateDateRange(form)) {
        isValid = false;
    }
    
    // Validate course selection for course-specific announcements
    if (!validateCourseSelection(form)) {
        isValid = false;
    }
    
    console.log('Form validation result:', isValid);
    return isValid;
}

function validateField(field, formType) {
    const fieldName = field.name;
    const fieldValue = field.value.trim();
    
    // Clear previous error
    clearFieldError(field);
    
    switch (fieldName) {
        case 'title':
            if (!fieldValue) {
                showFieldError(field, 'Announcement title is required.');
                return false;
            }
            if (fieldValue.length > 255) {
                showFieldError(field, 'Title cannot exceed 255 characters.');
                return false;
            }
            break;
            
        case 'body':
            // Handle custom editor content
            let bodyContent = fieldValue;
            const editor = document.getElementById(field.id.replace('Hidden', ''));
            if (editor && editor.contentEditable === 'true') {
                bodyContent = editor.textContent || editor.innerText || '';
            }
            
            if (!bodyContent.trim()) {
                showFieldError(field, 'Announcement message is required.');
                return false;
            }
            break;
            
        case 'audience_type':
            if (!fieldValue) {
                showFieldError(field, 'Please select target audience.');
                return false;
            }
            break;
            
        case 'urgency':
            if (!fieldValue || !['info', 'warning', 'urgent'].includes(fieldValue)) {
                showFieldError(field, 'Please select urgency level.');
                return false;
            }
            break;
            
        case 'start_datetime':
        case 'end_datetime':
            if (fieldValue && !isValidDateTime(fieldValue)) {
                showFieldError(field, 'Please enter a valid date and time.');
                return false;
            }
            break;
            
        case 'cta_url':
            if (fieldValue && !isValidUrl(fieldValue)) {
                showFieldError(field, 'Please enter a valid URL.');
                return false;
            }
            break;
    }
    
    return true;
}

function validateDateRange(form) {
    const startDatetime = form.querySelector('input[name="start_datetime"]');
    const endDatetime = form.querySelector('input[name="end_datetime"]');
    
    if (!startDatetime || !endDatetime) return true;
    
    const startValue = startDatetime.value;
    const endValue = endDatetime.value;
    
    if (startValue && endValue) {
        const startDate = new Date(startValue);
        const endDate = new Date(endValue);
        
        if (startDate >= endDate) {
            showFieldError(endDatetime, 'End date must be after start date.');
            return false;
        }
    }
    
    return true;
}

function validateCourseSelection(form) {
    const audienceType = form.querySelector('select[name="audience_type"]');
    const targetCourses = form.querySelector('select[name="target_courses[]"]');
    
    if (!audienceType || !targetCourses) return true;
    
    if (audienceType.value === 'course_specific') {
        const selectedCourses = Array.from(targetCourses.selectedOptions);
        if (selectedCourses.length === 0) {
            showFieldError(targetCourses, 'Please select at least one course.');
            return false;
        }
    }
    
    return true;
}

function isValidDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date instanceof Date && !isNaN(date);
}

function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

function showFieldError(field, message) {
    // Add error class to field
    field.classList.add('is-invalid');
    
    // Find or create error message element
    let errorElement = field.parentNode.querySelector('.invalid-feedback');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'invalid-feedback';
        field.parentNode.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
    errorElement.style.display = 'block';
    
    // Add error icon if not present
    if (!field.parentNode.querySelector('.error-icon')) {
        const icon = document.createElement('i');
        icon.className = 'fas fa-exclamation-triangle error-icon text-danger';
        icon.style.position = 'absolute';
        icon.style.right = '10px';
        icon.style.top = '50%';
        icon.style.transform = 'translateY(-50%)';
        icon.style.zIndex = '10';
        
        if (field.parentNode.style.position !== 'relative') {
            field.parentNode.style.position = 'relative';
        }
        
        field.parentNode.appendChild(icon);
    }
}

function clearFieldError(field) {
    // Remove error class
    field.classList.remove('is-invalid');
    
    // Hide error message
    const errorElement = field.parentNode.querySelector('.invalid-feedback');
    if (errorElement) {
        errorElement.style.display = 'none';
    }
    
    // Remove error icon
    const errorIcon = field.parentNode.querySelector('.error-icon');
    if (errorIcon) {
        errorIcon.remove();
    }
}

function clearAllErrors(form) {
    // Remove all error classes
    form.querySelectorAll('.is-invalid').forEach(field => {
        field.classList.remove('is-invalid');
    });
    
    // Hide all error messages
    form.querySelectorAll('.invalid-feedback').forEach(error => {
        error.style.display = 'none';
    });
    
    // Remove all error icons
    form.querySelectorAll('.error-icon').forEach(icon => {
        icon.remove();
    });
}

// Export functions for global access
window.validateAnnouncementForm = validateAnnouncementForm;
window.validateField = validateField;
window.clearAllErrors = clearAllErrors;
