// Add User Modal Validation (module-specific)
// This file should be included only on pages where the Add User modal is used.
// It expects window.isSuperAdminForClient and window.addUserSubmitUrl to be set before use.

(function(global) {
    // Allow setting these from outside (e.g., after loading modal content)
    global.isSuperAdminForClient = global.isSuperAdminForClient || false;
    global.addUserSubmitUrl = global.addUserSubmitUrl || null;

    function validateField(field) {
        let isValid = true;
        const value = (field.value || '').trim();
        const isSuperAdmin = global.isSuperAdminForClient;
        const required = field.getAttribute('data-required') === '1';
        const showError = (f, message) => {
            f.classList.add('is-invalid');
            const feedback = f.closest('.form-group').querySelector('.invalid-feedback');
            if (feedback) feedback.textContent = message;
        };
        const hideError = (f) => {
            f.classList.remove('is-invalid');
        };
        // Hide previous error before validating
        hideError(field);
        const feedbackDiv = field.closest('.form-group').querySelector('.invalid-feedback');
        if(feedbackDiv) feedbackDiv.textContent = '';

        // Main fields
        switch (field.id) {
            case 'modal_full_name':
                if (!value) {
                    showError(field, 'Full name is required.');
                    isValid = false;
                }
                break;
            case 'modal_email':
                if (!value) {
                    showError(field, 'Email is required.');
                    isValid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    showError(field, 'Please enter a valid email address.');
                    isValid = false;
                }
                break;
            case 'modal_contact_number':
                if (!value) {
                    showError(field, 'Contact number is required.');
                    isValid = false;
                }
                break;
            case 'modal_user_role':
                if (!value) {
                    showError(field, 'User role is required.');
                    isValid = false;
                } else if (isSuperAdmin && value !== 'Admin') {
                    showError(field, "Super admin can only create 'Admin' role users here.");
                    isValid = false;
                }
                break;
            case 'modal_reports_to':
                if (!value) {
                    showError(field, 'Report to (email) is required.');
                    isValid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    showError(field, 'Please enter a valid email address for Report to.');
                    isValid = false;
                } else {
                    // Check if the email was selected from autocomplete (has data attributes)
                    const selectedEmail = field.getAttribute('data-selected-email');
                    const selectedName = field.getAttribute('data-selected-name');
                    
                    if (selectedEmail && selectedEmail === value) {
                        // Email was selected from autocomplete, so it's valid
                        hideError(field);
                    } else if (selectedEmail && selectedEmail !== value) {
                        // User changed the email after selection, show warning
                        showError(field, 'Please select an email from the suggestions or enter a valid email address.');
                        isValid = false;
                    } else {
                        // No autocomplete selection, but email format is valid
                        hideError(field);
                    }
                }
                break;
            default:
                // Custom fields validation
                if (required) {
                    if ((field.type === 'checkbox' || field.type === 'radio')) {
                        // For checkbox/radio, check if any in the group is checked
                        const group = document.getElementsByName(field.name);
                        let checked = false;
                        for (let i = 0; i < group.length; i++) {
                            if (group[i].checked) checked = true;
                        }
                        if (!checked) {
                            showError(field, 'This field is required.');
                            isValid = false;
                        }
                    } else if (!value) {
                        showError(field, 'This field is required.');
                        isValid = false;
                    }
                }
                break;
        }
        return isValid;
    }

    function validateAddUserModal() {
        let isFormValid = true;
        // Validate main fields
        const fieldsToValidate = document.querySelectorAll('#addUserModalForm #modal_full_name, #addUserModalForm #modal_email, #addUserModalForm #modal_contact_number, #addUserModalForm #modal_user_role, #addUserModalForm #modal_reports_to');
        fieldsToValidate.forEach(field => {
            if (!validateField(field)) {
                isFormValid = false;
            }
        });
        // Validate custom fields (inputs, selects, textareas with data-required="1")
        const customFields = document.querySelectorAll('#addUserModalForm [data-required="1"]');
        customFields.forEach(field => {
            if (!validateField(field)) {
                isFormValid = false;
            }
        });
        // Highlight tabs with errors
        updateAddUserTabHighlighting();
        return isFormValid;
    }

    function updateAddUserTabHighlighting() {
        // Map tab IDs to field selectors
        const tabMappings = {
            'modal-basic-details': ['#modal_full_name', '#modal_email', '#modal_contact_number', '#modal_user_role'],
            'modal-additional-details': ['#modal_countrySelect', '#modal_stateSelect', '#modal_citySelect', '#modal_timezoneSelect', '[name="language"]', '#modal_reports_to', '[name="joining_date"]', '[name="retirement_date"]'],
            'modal-extra-details': []
        };
        // Add all custom fields to extra-details
        const customFields = document.querySelectorAll('#modal-extra-details [data-required="1"]');
        customFields.forEach(field => {
            tabMappings['modal-extra-details'].push('#' + field.id);
        });
        // Highlight tabs with errors
        Object.keys(tabMappings).forEach(tabId => {
            const tabButton = document.querySelector(`#addUserModalTabs button[data-bs-target="#${tabId}"]`);
            if (!tabButton) return;
            let hasErrors = false;
            tabMappings[tabId].forEach(selector => {
                const field = document.querySelector(selector);
                if (field && field.classList.contains('is-invalid')) {
                    hasErrors = true;
                }
            });
            if (hasErrors) {
                tabButton.classList.add('tab-error');
                tabButton.style.borderColor = '#dc3545';
                tabButton.style.borderWidth = '2px';
                tabButton.style.borderStyle = 'solid';
                tabButton.style.color = '#dc3545';
            } else {
                tabButton.classList.remove('tab-error');
                tabButton.style.borderColor = '';
                tabButton.style.borderWidth = '';
                tabButton.style.borderStyle = '';
                tabButton.style.color = '';
            }
        });
    }

    function submitAddUserModal() {
        const form = document.getElementById('addUserModalForm');
        const formData = new FormData(form);
        const submitButton = document.getElementById('addUserSubmitButton');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
        fetch(global.addUserSubmitUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                if (modal) modal.hide();
                if (typeof showSimpleToast === 'function') showSimpleToast(data.message || 'User added successfully!', 'success');
                if (typeof loadUsers === 'function') loadUsers(1);
            } else {
                if (typeof showSimpleToast === 'function') showSimpleToast(data.message || 'An error occurred.', 'error');
            }
        })
        .catch(error => {
            console.error('Submission error:', error);
            if (typeof showSimpleToast === 'function') showSimpleToast('A network error occurred. Please try again.', 'error');
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-save me-1"></i>Submit';
        });
    }

    // Initialize validation handlers for the Add User modal
    function initializeAddUserModalValidation() {
        const form = document.getElementById('addUserModalForm');
        if (!form) return;
        form.onsubmit = null;
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            if (typeof validateAddUserModal === 'function') {
                if (validateAddUserModal()) {
                    submitAddUserModal();
                }
            }
        });
        // Attach blur handler to all relevant fields (main + custom)
        const fields = form.querySelectorAll('#modal_full_name, #modal_email, #modal_contact_number, #modal_user_role, #modal_reports_to, [data-required="1"]');
        
        fields.forEach(field => {
            field.removeEventListener('blur', field._addUserModalBlurHandler || (()=>{}));
            const handler = function() {
                if (typeof validateField === 'function') {
                    validateField(field);
                    updateAddUserTabHighlighting();
                }
            };
            field._addUserModalBlurHandler = handler;
            field.addEventListener('blur', handler);
        });
    }

    // Expose to global scope for event handlers
    global.validateField = validateField;
    global.validateAddUserModal = validateAddUserModal;
    global.submitAddUserModal = submitAddUserModal;
    global.initializeAddUserModalValidation = initializeAddUserModalValidation;

})(window); 