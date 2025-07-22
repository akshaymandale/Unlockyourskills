// ✅ Client Management Validation JavaScript - Following SCORM Pattern

// Translation function (fallback if not available)
function translate(key) {
    // Use global translation function if available
    if (typeof window.translate === 'function') {
        return window.translate(key);
    }

    // Use global translations object if available
    if (typeof window.translations === 'object' && window.translations[key]) {
        return window.translations[key];
    }

    // Fallback to English messages if no translation found
    const fallbacks = {
        'js.validation.client_name_required': 'Client name is required.',
        'js.validation.max_users_required': 'Maximum users is required.',
        'js.validation.max_users_numeric': 'Maximum users must be a number.',
        'js.validation.max_users_minimum': 'Maximum users must be at least 1.',
        'js.validation.admin_role_limit_required': 'Admin role limit is required.',
        'js.validation.admin_role_limit_numeric': 'Admin role limit must be a number.',
        'js.validation.admin_role_limit_minimum': 'Admin role limit must be at least 1.',
        'js.validation.client_logo_required': 'Client logo is required.',
        'js.validation.logo_format_invalid': 'Logo must be PNG, JPG, or GIF format.',
        'js.validation.logo_size_exceeded': 'Logo file size must be less than 5MB.',
        'js.validation.client_form_not_found': 'Client Form NOT found!',
        'clients_create_client': 'Create Client',
        'clients_update_client': 'Update Client'
    };

    return fallbacks[key] || key;
}

document.addEventListener("DOMContentLoaded", function () {
    console.log('Client validation script loaded');

    // Check if jQuery and Bootstrap are available
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded!');
        return;
    }

    // Check if modal exists
    const addModal = document.getElementById('addClientModal');
    if (!addModal) {
        console.error('Add Client Modal not found!');
        return;
    }

    console.log('Setting up modal event handlers');

    // ✅ Attach validation immediately to forms if they exist
    if (document.getElementById('addClientForm')) {
        console.log('Add Client Form found, attaching validation immediately');
        attachClientValidation('addClientForm');
    }

    if (document.getElementById('editClientForm')) {
        console.log('Edit Client Form found, attaching validation immediately');
        attachClientValidation('editClientForm');
    }

    // ✅ When Add Client Modal Opens, Attach Validation (backup)
    $('#addClientModal').on('shown.bs.modal', function () {
        console.log('Add Client Modal shown, re-attaching validation');
        attachClientValidation('addClientForm');
    });

    // ✅ When Add Client Modal Closes, Reset the Form
    $('#addClientModal').on('hidden.bs.modal', function () {
        resetClientForm('addClientForm');
    });

    // ✅ When Edit Client Modal Opens, Attach Validation (backup)
    $('#editClientModal').on('shown.bs.modal', function () {
        console.log('Edit Client Modal shown, re-attaching validation');
        attachClientValidation('editClientForm');
    });

    // ✅ When Edit Client Modal Closes, Reset the Form
    $('#editClientModal').on('hidden.bs.modal', function () {
        resetClientForm('editClientForm');
    });

    function attachClientValidation(formId) {
        console.log('Attempting to attach validation to form:', formId);
        const clientForm = document.getElementById(formId);

        if (!clientForm) {
            console.error('Form not found:', formId);
            console.error(translate('js.validation.client_form_not_found'));
            return;
        }

        console.log('Form found, attaching event listeners');

        // ✅ Prevent duplicate event listeners
        clientForm.removeEventListener("submit", clientFormSubmitHandler);
        clientForm.addEventListener("submit", clientFormSubmitHandler);

        // ✅ Attach Blur Validation on Input Fields
        const fields = document.querySelectorAll(`#${formId} input, #${formId} select, #${formId} textarea`);
        console.log('Found fields for validation:', fields.length);

        fields.forEach(field => {
            field.removeEventListener("blur", clientFieldBlurHandler);
            field.addEventListener("blur", clientFieldBlurHandler);
        });

        console.log('Validation attached successfully to form:', formId);
    }

    function clientFormSubmitHandler(event) {
        console.log('Form submit handler triggered for:', event.target.id);
        event.preventDefault();
        let isValid = validateClientForm(event.target);
        console.log('Form validation result:', isValid);

        if (isValid) {
            console.log('Form is valid, submitting...');
            // Show loading state
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const isAddForm = event.target.id === 'addClientForm';
            const loadingText = isAddForm ? 'Creating...' : 'Updating...';
            submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i>${loadingText}`;
            submitBtn.disabled = true;

            this.submit();
        } else {
            console.log('Form validation failed, preventing submission');
        }
    }

    function clientFieldBlurHandler(event) {
        validateClientField(event.target);
    }

    // ✅ Validate Entire Form
    function validateClientForm(form) {
        const formId = form.id;
        let isValid = true;

        document.querySelectorAll(`#${formId} input, #${formId} select, #${formId} textarea`).forEach(field => {
            if (!validateClientField(field)) {
                isValid = false;
            }
        });
        return isValid;
    }

    // ✅ Validate Single Field
    function validateClientField(field) {
        let isValid = true;
        let value = field.value.trim();
        let fieldName = field.getAttribute("name");

        switch (fieldName) {
            case "client_name":
                if (value === "") {
                    showError(field, translate('js.validation.client_name_required'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "max_users":
                if (value === "") {
                    showError(field, translate('js.validation.max_users_required'));
                    isValid = false;
                } else if (!isNumeric(value)) {
                    showError(field, translate('js.validation.max_users_numeric'));
                    isValid = false;
                } else if (parseInt(value) < 1) {
                    showError(field, translate('js.validation.max_users_minimum'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "admin_role_limit":
                if (value === "") {
                    showError(field, translate('js.validation.admin_role_limit_required'));
                    isValid = false;
                } else if (!isNumeric(value)) {
                    showError(field, translate('js.validation.admin_role_limit_numeric'));
                    isValid = false;
                } else if (parseInt(value) < 1) {
                    showError(field, translate('js.validation.admin_role_limit_minimum'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "logo":
                // For add form, logo is required. For edit form, logo is optional
                const isEditForm = field.closest('#editClientForm') !== null;

                if (field.files.length === 0) {
                    if (!isEditForm) {
                        showError(field, translate('js.validation.client_logo_required'));
                        isValid = false;
                    } else {
                        hideError(field);
                    }
                } else {
                    const file = field.files[0];
                    const allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
                    const maxSize = 5 * 1024 * 1024; // 5MB

                    if (!allowedTypes.includes(file.type)) {
                        showError(field, translate('js.validation.logo_format_invalid'));
                        isValid = false;
                    } else if (file.size > maxSize) {
                        showError(field, translate('js.validation.logo_size_exceeded'));
                        isValid = false;
                    } else {
                        hideError(field);
                    }
                }
                break;

            default:
                // For other fields (dropdowns, description), no validation needed
                hideError(field);
                break;
        }

        return isValid;
    }

    // ✅ Utility function to check if value is numeric
    function isNumeric(value) {
        return !isNaN(value) && !isNaN(parseFloat(value)) && isFinite(value);
    }

    // ✅ Show Error Messages - Following SCORM Pattern
    function showError(input, message) {
        let errorElement = input.parentNode.querySelector(".error-message");
        if (!errorElement) {
            errorElement = document.createElement("span");
            errorElement.classList.add("error-message");
            input.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
        errorElement.style.color = "#dc3545";
        errorElement.style.fontSize = "0.875rem";
        errorElement.style.marginTop = "0.25rem";
        errorElement.style.display = "block";

        // Bootstrap error styling
        input.classList.add("is-invalid");
    }

    // ✅ Hide Error Messages
    function hideError(input) {
        let errorElement = input.parentNode.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
            errorElement.style.display = "none";
        }

        input.classList.remove("is-invalid");
    }

    // ✅ Reset Client Form and Remove Errors
    function resetClientForm(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();

            // Reset admin role limit to default value for add form
            if (formId === 'addClientForm') {
                const adminRoleLimit = document.getElementById("admin_role_limit");
                if (adminRoleLimit) {
                    adminRoleLimit.value = "1";
                }
            }

            // Remove all error messages and styling
            document.querySelectorAll(`#${formId} .error-message`).forEach(el => {
                el.textContent = "";
                el.style.display = "none";
            });
            document.querySelectorAll(`#${formId} .is-invalid`).forEach(el => {
                el.classList.remove("is-invalid");
            });

            // Reset submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                if (formId === 'addClientForm') {
                    submitBtn.innerHTML = translate('clients_create_client') || 'Create Client';
                } else {
                    submitBtn.innerHTML = translate('clients_update_client') || 'Update Client';
                }
                submitBtn.disabled = false;
            }
        }
    }
});

// Make functions globally available
window.attachClientValidation = attachClientValidation;
window.validateClientForm = validateClientForm;
window.validateClientField = validateClientField;
