/**
 * Custom Field Validation JavaScript
 * Handles client-side validation for custom field creation form
 */

document.addEventListener("DOMContentLoaded", function () {
    console.log("🔥 custom_field_validation.js loaded");

    // Set up validation when modal is shown
    const createModal = document.getElementById('createCustomFieldModal');
    if (createModal) {
        createModal.addEventListener('shown.bs.modal', function() {
            console.log("🔥 Create custom field modal opened, setting up validation");
            setupCustomFieldValidation();
        });
    }

    // Also try to set up validation immediately if form exists
    setupCustomFieldValidation();
});

/**
 * Set up custom field validation
 */
function setupCustomFieldValidation() {
    const customFieldForm = document.getElementById("createCustomFieldForm");

    if (customFieldForm) {
        console.log("✅ Custom field form found, setting up validation");

        // Remove existing listeners to avoid duplicates
        const fields = customFieldForm.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            // Clone the field to remove all event listeners
            const newField = field.cloneNode(true);
            field.parentNode.replaceChild(newField, field);
        });

        // Re-get fields after cloning
        const newFields = customFieldForm.querySelectorAll('input, select, textarea');

        // Add blur validation to all form fields
        newFields.forEach(field => {
            field.addEventListener('blur', function() {
                console.log('🔥 Blur validation for:', this.name, 'Value:', this.value);
                validateCustomField(this);
            });

            // Clear errors when user starts typing
            field.addEventListener('input', function() {
                if (this.name === 'field_name' || this.name === 'field_label') {
                    hideCustomFieldError(this);
                    this.removeAttribute('data-validation-error');
                }
            });
        });

        // Set up form submit validation
        const newForm = document.getElementById("createCustomFieldForm");

        // Remove any existing submit listeners to prevent duplicates
        newForm.removeEventListener('submit', handleFormSubmit);
        newForm.addEventListener('submit', handleFormSubmit);

        // Field type change handler for options visibility
        const fieldTypeSelect = document.getElementById('field_type');
        if (fieldTypeSelect) {
            fieldTypeSelect.addEventListener('change', function() {
                handleFieldTypeChange(this.value);
                validateCustomField(this); // Validate when changed
            });
        }
    } else {
        console.log("❌ Custom field form not found");
    }
}

/**
 * Handle form submission
 */
function handleFormSubmit(e) {
    console.log('🔥 Custom field form submitted');

    // Validate the entire form
    const isValid = validateCustomFieldForm();

    if (!isValid) {
        console.log('❌ Custom field validation failed');
        e.preventDefault(); // Prevent submission only if validation fails

        // Show error message only once
        if (!window.validationAlertShown) {
            alert(translate('js.validation.fix_errors_before_submit'));
            window.validationAlertShown = true;

            // Reset flag after 2 seconds
            setTimeout(() => {
                window.validationAlertShown = false;
            }, 2000);
        }
        return false;
    }

    console.log('✅ Custom field validation passed, allowing normal form submission');
    // Let the form submit normally (don't prevent default)
}

/**
 * Validate entire custom field form
 */
function validateCustomFieldForm() {
    let isValid = true;
    const fields = document.querySelectorAll("#createCustomFieldForm input, #createCustomFieldForm select, #createCustomFieldForm textarea");
    
    fields.forEach(field => {
        const fieldValid = validateCustomField(field);
        if (!fieldValid) {
            isValid = false;
        }
    });
    
    return isValid;
}

/**
 * Validate individual custom field
 */
function validateCustomField(field) {
    if (!field) return true;
    
    let isValid = true;
    let value = field.value ? field.value.trim() : '';
    let fieldName = field.getAttribute("name");
    
    console.log('validateCustomField called for:', fieldName, 'value:', value);
    
    // Clear previous error first
    hideCustomFieldError(field);
    field.removeAttribute('data-validation-error');
    
    switch (fieldName) {
        case "field_name":
            // Required validation
            if (value === "") {
                showCustomFieldError(field, translate('validation.field_name_required'));
                isValid = false;
            } else {
                // Format validation (no spaces, use underscores)
                const fieldNamePattern = /^[a-zA-Z][a-zA-Z0-9_]*$/;
                if (!fieldNamePattern.test(value)) {
                    showCustomFieldError(field, translate('js.validation.field_name_format'));
                    isValid = false;
                } else {
                    // Check for existing field names (will be done via AJAX)
                    checkFieldNameExists(field, value);

                    // Check if there's already an error from previous AJAX call
                    if (field.classList.contains('is-invalid') || field.hasAttribute('data-validation-error')) {
                        isValid = false;
                    }
                }
            }
            break;
            
        case "field_label":
            if (value === "") {
                showCustomFieldError(field, translate('validation.field_label_required'));
                isValid = false;
            } else {
                // Check for existing field labels (will be done via AJAX)
                checkFieldLabelExists(field, value);

                // Check if there's already an error from previous AJAX call
                if (field.classList.contains('is-invalid') || field.hasAttribute('data-validation-error')) {
                    isValid = false;
                }
            }
            break;
            
        case "field_type":
            if (value === "") {
                showCustomFieldError(field, translate('validation.field_type_required'));
                isValid = false;
            }
            break;
            
        case "field_options":
            // Only validate if the field is visible (for select, radio, checkbox)
            const optionsContainer = document.getElementById('field_options_container');
            if (optionsContainer && optionsContainer.style.display !== 'none') {
                if (value === "") {
                    showCustomFieldError(field, translate('js.validation.field_options_required'));
                    isValid = false;
                } else {
                    // Validate that options are properly formatted (one per line, non-empty)
                    const options = value.split('\n').map(opt => opt.trim()).filter(opt => opt !== '');
                    if (options.length === 0) {
                        showCustomFieldError(field, translate('js.validation.field_options_one_required'));
                        isValid = false;
                    } else if (options.length < 2) {
                        showCustomFieldError(field, translate('js.validation.field_options_two_required'));
                        isValid = false;
                    }
                }
            }
            break;
    }
    
    return isValid;
}

/**
 * Check if field name already exists
 */
function checkFieldNameExists(field, fieldName) {
    // Debounce the check to avoid too many requests
    clearTimeout(field.checkTimeout);
    field.checkTimeout = setTimeout(() => {
        console.log('🔥 Checking field name exists:', fieldName);

        const url = getProjectUrl('api/custom-fields/check-name.php');
        console.log('🔥 Check name URL:', url);

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `field_name=${encodeURIComponent(fieldName)}`
        })
        .then(response => {
            console.log('🔥 Check name response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('🔥 Check name response data:', data);
            if (data.exists) {
                showCustomFieldError(field, translate('validation.field_name_exists'));
                // Mark field as having validation error
                field.setAttribute('data-validation-error', 'true');
            } else {
                hideCustomFieldError(field);
                // Clear validation error flag
                field.removeAttribute('data-validation-error');
            }
        })
        .catch(error => {
            console.error('❌ Error checking field name:', error);
        });
    }, 500);
}

/**
 * Check if field label already exists
 */
function checkFieldLabelExists(field, fieldLabel) {
    // Debounce the check to avoid too many requests
    clearTimeout(field.checkTimeout);
    field.checkTimeout = setTimeout(() => {
        console.log('🔥 Checking field label exists:', fieldLabel);

        const url = getProjectUrl('api/custom-fields/check-label.php');
        console.log('🔥 Check label URL:', url);

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `field_label=${encodeURIComponent(fieldLabel)}`
        })
        .then(response => {
            console.log('🔥 Check label response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('🔥 Check label response data:', data);
            if (data.exists) {
                showCustomFieldError(field, translate('js.validation.field_label_exists'));
                // Mark field as having validation error
                field.setAttribute('data-validation-error', 'true');
            } else {
                hideCustomFieldError(field);
                // Clear validation error flag
                field.removeAttribute('data-validation-error');
            }
        })
        .catch(error => {
            console.error('❌ Error checking field label:', error);
        });
    }, 500);
}

/**
 * Handle field type change
 */
function handleFieldTypeChange(fieldType) {
    const optionsContainer = document.getElementById('field_options_container');
    const optionsField = document.getElementById('field_options');
    
    if (['select', 'radio', 'checkbox'].includes(fieldType)) {
        optionsContainer.style.display = 'block';
        // Don't set required attribute, we handle validation manually
    } else {
        optionsContainer.style.display = 'none';
        optionsField.value = '';
        hideCustomFieldError(optionsField);
    }
}

/**
 * Show custom field error message
 */
function showCustomFieldError(input, message) {
    let errorElement = input.parentNode.querySelector(".invalid-feedback");
    if (!errorElement) {
        errorElement = document.createElement("div");
        errorElement.classList.add("invalid-feedback");
        input.parentNode.appendChild(errorElement);
    }
    errorElement.textContent = message;
    errorElement.style.display = "block";
    
    // Add Bootstrap error styling
    input.classList.add("is-invalid");
}

/**
 * Hide custom field error message
 */
function hideCustomFieldError(input) {
    let errorElement = input.parentNode.querySelector(".invalid-feedback");
    if (errorElement) {
        errorElement.textContent = "";
        errorElement.style.display = "none";
    }
    input.classList.remove("is-invalid");
}

/**
 * Submit custom field form via AJAX
 */
function submitCustomFieldForm() {
    const form = document.getElementById('createCustomFieldForm');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i>${translate('js.creating')}`;
    submitBtn.disabled = true;
    
    const formData = new FormData(form);
    
    // Debug: Log form data
    console.log('🔥 Custom field form submission data:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    const submitUrl = getProjectUrl('settings/custom-fields');
    console.log('🔥 Submitting custom field to URL:', submitUrl);
    
    fetch(submitUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('🔥 Custom field response status:', response.status);
        console.log('🔥 Custom field response URL:', response.url);
        
        // Check if response is JSON or redirect
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // Handle redirect response
            return response.text().then(text => {
                console.log('🔥 Custom field raw response:', text);
                throw new Error('Server returned non-JSON response');
            });
        }
    })
    .then(data => {
        console.log('🔥 Custom field parsed response data:', data);
        
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createCustomFieldModal'));
            modal.hide();
            
            // Show success message
            showToast('success', data.message || translate('success.custom_field_created'));
            
            // Reload the page to show new custom field
            window.location.reload();
        } else {
            // Handle validation errors
            if (data.field_errors && Object.keys(data.field_errors).length > 0) {
                Object.keys(data.field_errors).forEach(fieldName => {
                    const field = document.querySelector(`[name="${fieldName}"]`);
                    if (field) {
                        showCustomFieldError(field, data.field_errors[fieldName]);
                    }
                });
            } else {
                showToast('error', data.message || translate('error.custom_field_create_failed'));
            }
        }
    })
    .catch(error => {
        console.error('❌ Error submitting custom field form:', error);
        showToast('error', translate('js.network_error'));
    })
    .finally(() => {
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

/**
 * Validate field name format
 */
function isValidFieldName(fieldName) {
    // Field name must start with a letter and contain only letters, numbers, and underscores
    const fieldNameRegex = /^[a-zA-Z][a-zA-Z0-9_]*$/;
    return fieldNameRegex.test(fieldName);
}

/**
 * Helper function to show toast notifications
 */
function showToast(type, message) {
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    // Add to toast container or create one
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);

    // Show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 5000
    });
    toast.show();

    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

/**
 * Initialize validation for edit custom field modal
 */
function initializeEditCustomFieldValidation() {
    const editForm = document.getElementById('editCustomFieldForm');
    const editFieldNameInput = document.getElementById('edit_field_name');
    const editFieldLabelInput = document.getElementById('edit_field_label');
    const editFieldTypeSelect = document.getElementById('edit_field_type');
    const editFieldOptionsTextarea = document.getElementById('edit_field_options');

    if (!editForm || !editFieldNameInput || !editFieldLabelInput) {
        console.log('Edit custom field form elements not found');
        return;
    }

    console.log('🔥 Initializing edit custom field validation');

    // Add validation on focus out for field name
    editFieldNameInput.addEventListener('focusout', function() {
        validateEditCustomField(this, 'field_name');
    });

    // Add validation on focus out for field label
    editFieldLabelInput.addEventListener('focusout', function() {
        validateEditCustomField(this, 'field_label');
    });

    // Add validation on change for field type
    editFieldTypeSelect.addEventListener('change', function() {
        validateEditCustomField(this, 'field_type');
    });

    // Add validation on focus out for field options
    if (editFieldOptionsTextarea) {
        editFieldOptionsTextarea.addEventListener('focusout', function() {
            validateEditCustomField(this, 'field_options');
        });

        editFieldOptionsTextarea.addEventListener('input', function() {
            hideCustomFieldError(this);
        });
    }

    // Clear validation on input
    editFieldNameInput.addEventListener('input', function() {
        hideCustomFieldError(this);
    });

    editFieldLabelInput.addEventListener('input', function() {
        hideCustomFieldError(this);
    });

    editFieldTypeSelect.addEventListener('input', function() {
        hideCustomFieldError(this);
    });

    // Form submission validation
    editForm.addEventListener('submit', function(e) {
        e.preventDefault();

        console.log('🔥 Edit form submitted, validating...');

        // Validate all fields
        const fieldNameValid = validateEditCustomField(editFieldNameInput, 'field_name');
        const fieldLabelValid = validateEditCustomField(editFieldLabelInput, 'field_label');
        const fieldTypeValid = validateEditCustomField(editFieldTypeSelect, 'field_type');

        // Validate field options if needed
        let fieldOptionsValid = true;
        const fieldType = editFieldTypeSelect.value;
        if (['select', 'radio', 'checkbox'].includes(fieldType) && editFieldOptionsTextarea) {
            fieldOptionsValid = validateEditCustomField(editFieldOptionsTextarea, 'field_options');
        }

        // Check if all validations passed
        if (fieldNameValid && fieldLabelValid && fieldTypeValid && fieldOptionsValid) {
            console.log('🔥 All edit validations passed, checking for async validation errors...');

            // Wait longer for async validations to complete
            setTimeout(() => {
                // Check for any remaining validation errors
                const invalidFields = editForm.querySelectorAll('.is-invalid');
                const asyncErrorFields = editForm.querySelectorAll('[data-validation-error="true"]');

                console.log('🔥 Checking final validation state:');
                console.log('🔥 Invalid fields count:', invalidFields.length);
                console.log('🔥 Async error fields count:', asyncErrorFields.length);

                if (invalidFields.length > 0) {
                    console.log('🔥 Invalid fields:', Array.from(invalidFields).map(f => f.name || f.id));
                }
                if (asyncErrorFields.length > 0) {
                    console.log('🔥 Async error fields:', Array.from(asyncErrorFields).map(f => f.name || f.id));
                }

                if (invalidFields.length === 0 && asyncErrorFields.length === 0) {
                    console.log('🔥 No validation errors found, submitting edit form');
                    editForm.submit();
                } else {
                    console.log('🔥 Edit form has validation errors, not submitting');
                }
            }, 1000); // Wait 1 second for async validations
        } else {
            console.log('🔥 Edit validation failed, not submitting');
            console.log('🔥 Field validation results:', {
                fieldName: fieldNameValid,
                fieldLabel: fieldLabelValid,
                fieldType: fieldTypeValid,
                fieldOptions: fieldOptionsValid
            });
        }
    });
}

/**
 * Validate edit custom field
 */
function validateEditCustomField(input, fieldType) {
    const value = input.value.trim();
    const fieldId = document.getElementById('edit_field_id').value;

    // Required field validation
    if (!value && fieldType !== 'field_options') {
        const fieldDisplayName = fieldType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
        showCustomFieldError(input, translate('js.validation.field_required', {field: fieldDisplayName}));
        return false;
    }

    // Field name specific validation
    if (fieldType === 'field_name') {
        if (!isValidFieldName(value)) {
            showCustomFieldError(input, translate('js.validation.field_name_format'));
            input.setAttribute('data-validation-error', 'true');
            return false;
        }

        // Clear format error and check uniqueness
        hideCustomFieldError(input);
        input.removeAttribute('data-validation-error');

        // Check if field name exists (excluding current field)
        checkEditFieldNameExists(value, fieldId);
        return true; // Return true for format validation, async will handle uniqueness
    }

    // Field label specific validation
    if (fieldType === 'field_label') {
        if (value.length < 2) {
            showCustomFieldError(input, translate('js.validation.field_label_min_length'));
            input.setAttribute('data-validation-error', 'true');
            return false;
        }

        // Clear length error and check uniqueness
        hideCustomFieldError(input);
        input.removeAttribute('data-validation-error');

        // Check if field label exists (excluding current field)
        checkEditFieldLabelExists(value, fieldId);
        return true; // Return true for format validation, async will handle uniqueness
    }

    // Field type validation
    if (fieldType === 'field_type') {
        const allowedTypes = ['text', 'textarea', 'select', 'radio', 'checkbox', 'file', 'date', 'number', 'email', 'phone'];
        if (!allowedTypes.includes(value)) {
            showCustomFieldError(input, 'Please select a valid field type');
            return false;
        }
    }

    // Field options validation
    if (fieldType === 'field_options') {
        const selectedFieldType = document.getElementById('edit_field_type').value;
        if (['select', 'radio', 'checkbox'].includes(selectedFieldType)) {
            if (!value) {
                showCustomFieldError(input, 'Field options are required for this field type');
                return false;
            }

            // Validate options format
            const options = value.split('\n').map(opt => opt.trim()).filter(opt => opt.length > 0);
            if (options.length < 2) {
                showCustomFieldError(input, 'Please provide at least 2 options');
                return false;
            }

            // Check for duplicate options
            const uniqueOptions = [...new Set(options)];
            if (uniqueOptions.length !== options.length) {
                showCustomFieldError(input, 'Duplicate options are not allowed');
                return false;
            }
        }
    }

    hideCustomFieldError(input);
    return true;
}

/**
 * Check if edit field name exists
 */
function checkEditFieldNameExists(fieldName, excludeId) {
    const input = document.getElementById('edit_field_name');

    // Clear any existing timeout
    clearTimeout(input.checkTimeout);

    console.log('🔥 Checking edit field name exists:', fieldName, 'excluding ID:', excludeId);

    input.checkTimeout = setTimeout(() => {
        const url = getProjectUrl('api/custom-fields/check-name.php');
        console.log('🔥 Edit check name URL:', url);

        const requestBody = `field_name=${encodeURIComponent(fieldName)}&exclude_id=${encodeURIComponent(excludeId)}`;
        console.log('🔥 Edit check name request body:', requestBody);

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: requestBody
        })
        .then(response => {
            console.log('🔥 Edit check name response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('🔥 Edit check name response data:', data);

            if (data.exists) {
                console.log('🔥 Field name exists, showing error');
                showCustomFieldError(input, 'A field with this name already exists');
                input.setAttribute('data-validation-error', 'true');
            } else {
                console.log('🔥 Field name is unique, clearing error');
                hideCustomFieldError(input);
                input.removeAttribute('data-validation-error');
            }
        })
        .catch(error => {
            console.error('❌ Error checking edit field name:', error);
            // On error, don't block the form but log the issue
            input.removeAttribute('data-validation-error');
        });
    }, 500);
}

/**
 * Check if edit field label exists
 */
function checkEditFieldLabelExists(fieldLabel, excludeId) {
    const input = document.getElementById('edit_field_label');

    // Clear any existing timeout
    clearTimeout(input.checkTimeout);

    console.log('🔥 Checking edit field label exists:', fieldLabel, 'excluding ID:', excludeId);

    input.checkTimeout = setTimeout(() => {
        const url = getProjectUrl('api/custom-fields/check-label.php');
        console.log('🔥 Edit check label URL:', url);

        const requestBody = `field_label=${encodeURIComponent(fieldLabel)}&exclude_id=${encodeURIComponent(excludeId)}`;
        console.log('🔥 Edit check label request body:', requestBody);

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: requestBody
        })
        .then(response => {
            console.log('🔥 Edit check label response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('🔥 Edit check label response data:', data);

            if (data.exists) {
                console.log('🔥 Field label exists, showing error');
                showCustomFieldError(input, 'A field with this label already exists');
                input.setAttribute('data-validation-error', 'true');
            } else {
                console.log('🔥 Field label is unique, clearing error');
                hideCustomFieldError(input);
                input.removeAttribute('data-validation-error');
            }
        })
        .catch(error => {
            console.error('❌ Error checking edit field label:', error);
            // On error, don't block the form but log the issue
            input.removeAttribute('data-validation-error');
        });
    }, 500);
}

// Initialize edit validation when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeEditCustomFieldValidation();
});
