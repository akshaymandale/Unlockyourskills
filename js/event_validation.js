/**
 * Event Management Form Validation
 * Client-side validation for event creation and editing forms
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeEventValidation();
});

function initializeEventValidation() {
    // Initialize validation for create form
    const createForm = document.getElementById('createEventForm');
    if (createForm) {
        initializeFormValidation(createForm, false);
    }

    // Initialize validation for edit form
    const editForm = document.getElementById('editEventForm');
    if (editForm) {
        initializeFormValidation(editForm, true);
    }
}

function initializeFormValidation(form, isEdit) {
    const prefix = isEdit ? 'edit' : '';
    const titleField = document.getElementById(prefix + (isEdit ? 'EventTitle' : 'eventTitle'));
    const descriptionField = document.getElementById(prefix + (isEdit ? 'EventDescription' : 'eventDescription'));
    const eventTypeField = document.getElementById(prefix + (isEdit ? 'EventType' : 'eventType'));
    const audienceTypeField = document.getElementById(prefix + (isEdit ? 'AudienceType' : 'audienceType'));
    const startDatetimeField = document.getElementById(prefix + (isEdit ? 'StartDatetime' : 'startDatetime'));
    const endDatetimeField = document.getElementById(prefix + (isEdit ? 'EndDatetime' : 'endDatetime'));
    const eventLinkField = document.getElementById(prefix + (isEdit ? 'EventLink' : 'eventLink'));

    // Real-time validation on blur
    if (titleField) {
        titleField.addEventListener('blur', () => validateTitle(titleField));
        titleField.addEventListener('input', () => clearFieldError(titleField));
    }

    if (descriptionField) {
        descriptionField.addEventListener('blur', () => validateDescription(descriptionField));
        descriptionField.addEventListener('input', () => clearFieldError(descriptionField));
    }

    if (eventTypeField) {
        eventTypeField.addEventListener('change', () => validateEventType(eventTypeField));
    }

    if (audienceTypeField) {
        audienceTypeField.addEventListener('change', () => validateAudienceType(audienceTypeField, isEdit));
    }

    if (startDatetimeField) {
        startDatetimeField.addEventListener('blur', () => validateStartDatetime(startDatetimeField));
        startDatetimeField.addEventListener('change', () => {
            validateStartDatetime(startDatetimeField);
            if (endDatetimeField && endDatetimeField.value) {
                validateEndDatetime(endDatetimeField, startDatetimeField);
            }
        });
    }

    if (endDatetimeField) {
        endDatetimeField.addEventListener('blur', () => validateEndDatetime(endDatetimeField, startDatetimeField));
        endDatetimeField.addEventListener('change', () => validateEndDatetime(endDatetimeField, startDatetimeField));
    }

    if (eventLinkField) {
        eventLinkField.addEventListener('blur', () => validateEventLink(eventLinkField));
        eventLinkField.addEventListener('input', () => clearFieldError(eventLinkField));
    }

    // Form submission validation
    form.addEventListener('submit', function(e) {
        if (!validateForm(form, isEdit)) {
            e.preventDefault();
            e.stopPropagation();
        }
    });
}

function validateForm(form, isEdit) {
    let isValid = true;
    const prefix = isEdit ? 'edit' : '';
    
    // Get form fields
    const titleField = document.getElementById(prefix + (isEdit ? 'EventTitle' : 'eventTitle'));
    const descriptionField = document.getElementById(prefix + (isEdit ? 'EventDescription' : 'eventDescription'));
    const eventTypeField = document.getElementById(prefix + (isEdit ? 'EventType' : 'eventType'));
    const audienceTypeField = document.getElementById(prefix + (isEdit ? 'AudienceType' : 'audienceType'));
    const startDatetimeField = document.getElementById(prefix + (isEdit ? 'StartDatetime' : 'startDatetime'));
    const endDatetimeField = document.getElementById(prefix + (isEdit ? 'EndDatetime' : 'endDatetime'));
    const eventLinkField = document.getElementById(prefix + (isEdit ? 'EventLink' : 'eventLink'));

    // Validate all fields
    if (titleField && !validateTitle(titleField)) isValid = false;
    if (descriptionField && !validateDescription(descriptionField)) isValid = false;
    if (eventTypeField && !validateEventType(eventTypeField)) isValid = false;
    if (audienceTypeField && !validateAudienceType(audienceTypeField, isEdit)) isValid = false;
    if (startDatetimeField && !validateStartDatetime(startDatetimeField)) isValid = false;
    if (endDatetimeField && !validateEndDatetime(endDatetimeField, startDatetimeField)) isValid = false;
    if (eventLinkField && !validateEventLink(eventLinkField)) isValid = false;

    // Show general error message if validation fails
    if (!isValid) {
        showFormError('Please correct the highlighted errors before submitting.');
    }

    return isValid;
}

function validateTitle(field) {
    const value = field.value.trim();
    
    if (!value) {
        showFieldError(field, 'Event title is required.');
        return false;
    }
    
    if (value.length < 3) {
        showFieldError(field, 'Event title must be at least 3 characters long.');
        return false;
    }
    
    if (value.length > 255) {
        showFieldError(field, 'Event title cannot exceed 255 characters.');
        return false;
    }
    
    clearFieldError(field);
    return true;
}

function validateDescription(field) {
    const value = field.value.trim();
    
    if (!value) {
        showFieldError(field, 'Event description is required.');
        return false;
    }
    
    if (value.length < 10) {
        showFieldError(field, 'Event description must be at least 10 characters long.');
        return false;
    }
    
    if (value.length > 2000) {
        showFieldError(field, 'Event description cannot exceed 2000 characters.');
        return false;
    }
    
    clearFieldError(field);
    return true;
}

function validateEventType(field) {
    const value = field.value;
    const validTypes = ['live_class', 'webinar', 'deadline', 'maintenance', 'meeting', 'workshop'];
    
    if (!value) {
        showFieldError(field, 'Please select an event type.');
        return false;
    }
    
    if (!validTypes.includes(value)) {
        showFieldError(field, 'Please select a valid event type.');
        return false;
    }
    
    clearFieldError(field);
    return true;
}

function validateAudienceType(field, isEdit) {
    const value = field.value;
    const validTypes = ['global', 'course_specific', 'group_specific'];
    
    if (!value) {
        showFieldError(field, 'Please select a target audience.');
        return false;
    }
    
    if (!validTypes.includes(value)) {
        showFieldError(field, 'Please select a valid audience type.');
        return false;
    }
    
    // Validate course selection if course_specific is selected
    if (value === 'course_specific') {
        const courseSelect = document.getElementById(isEdit ? 'editTargetCourses' : 'targetCourses');
        if (courseSelect && courseSelect.selectedOptions.length === 0) {
            showFieldError(courseSelect, 'Please select at least one course.');
            return false;
        }
    }
    
    clearFieldError(field);
    return true;
}

function validateStartDatetime(field) {
    const value = field.value;
    
    if (!value) {
        showFieldError(field, 'Start date and time is required.');
        return false;
    }
    
    const startDate = new Date(value);
    const now = new Date();
    
    // Allow 5 minutes buffer for current time
    const minTime = new Date(now.getTime() - 5 * 60 * 1000);
    
    if (startDate < minTime) {
        showFieldError(field, 'Start date cannot be in the past.');
        return false;
    }
    
    clearFieldError(field);
    return true;
}

function validateEndDatetime(field, startField) {
    const value = field.value;
    
    // End datetime is optional
    if (!value) {
        clearFieldError(field);
        return true;
    }
    
    const endDate = new Date(value);
    
    if (startField && startField.value) {
        const startDate = new Date(startField.value);
        
        if (endDate <= startDate) {
            showFieldError(field, 'End date must be after start date.');
            return false;
        }
    }
    
    clearFieldError(field);
    return true;
}

function validateEventLink(field) {
    const value = field.value.trim();
    
    // Event link is optional
    if (!value) {
        clearFieldError(field);
        return true;
    }
    
    // Basic URL validation
    const urlPattern = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;
    
    if (!urlPattern.test(value)) {
        showFieldError(field, 'Please enter a valid URL.');
        return false;
    }
    
    clearFieldError(field);
    return true;
}

function showFieldError(field, message) {
    // Add error class to field
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
    
    // Find or create error message element
    let errorElement = field.parentNode.querySelector('.invalid-feedback');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'invalid-feedback';
        field.parentNode.appendChild(errorElement);
    }
    
    errorElement.textContent = message;
    errorElement.style.display = 'block';
}

function clearFieldError(field) {
    // Remove error class from field
    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
    
    // Hide error message
    const errorElement = field.parentNode.querySelector('.invalid-feedback');
    if (errorElement) {
        errorElement.style.display = 'none';
    }
}

function showFormError(message) {
    // Create or update form-level error message
    let errorContainer = document.querySelector('.form-error-message');
    
    if (!errorContainer) {
        errorContainer = document.createElement('div');
        errorContainer.className = 'alert alert-danger form-error-message';
        errorContainer.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i><span class="error-text"></span>`;
        
        // Insert at the top of the modal body
        const modalBody = document.querySelector('.modal.show .modal-body');
        if (modalBody) {
            modalBody.insertBefore(errorContainer, modalBody.firstChild);
        }
    }
    
    errorContainer.querySelector('.error-text').textContent = message;
    errorContainer.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        if (errorContainer) {
            errorContainer.style.display = 'none';
        }
    }, 5000);
}

// Export validation functions for external use
window.EventValidation = {
    validateForm,
    validateTitle,
    validateDescription,
    validateEventType,
    validateAudienceType,
    validateStartDatetime,
    validateEndDatetime,
    validateEventLink
};
