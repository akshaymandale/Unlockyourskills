/**
 * Client Form Validation JavaScript
 * Handles form validation for client add/edit forms
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form validation
    initializeAddFormValidation();
    initializeEditFormValidation();
    
    // Helper functions for validation
    function isNumeric(value) {
        return !isNaN(value) && !isNaN(parseFloat(value));
    }

    function showFieldError(field, message) {
        field.classList.add('is-invalid');

        // Find or create error message element
        let errorElement = field.parentNode.querySelector('.error-message');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message text-danger small mt-1';
            field.parentNode.appendChild(errorElement);
        }

        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }

    function hideFieldError(field) {
        field.classList.remove('is-invalid');

        const errorElement = field.parentNode.querySelector('.error-message');
        if (errorElement) {
            errorElement.style.display = 'none';
            errorElement.textContent = '';
        }
    }

    // Add form validation
    function initializeAddFormValidation() {
        const addForm = document.getElementById('addClientForm');
        if (!addForm) return;

        addForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (validateClientForm(this)) {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...';
                submitBtn.disabled = true;

                // Submit form via AJAX
                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Success - refresh the client cards
                        refreshClientCards();
                        $('#addClientModal').modal('hide');
                        showToast('success', data.message || 'Client created successfully!');
                    } else {
                        // Error - show error message
                        showToast('error', data.message || 'Failed to create client. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'Failed to create client. Please try again.');
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }
        });

        // Add blur validation for real-time feedback
        const fields = addForm.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            field.addEventListener('blur', function() {
                validateSingleField(this);
            });
        });
    }

    // Edit form validation
    function initializeEditFormValidation() {
        const editForm = document.getElementById('editClientForm');
        if (!editForm) return;

        editForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (validateClientForm(this)) {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';
                submitBtn.disabled = true;

                // Submit form via AJAX
                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Success - refresh the client cards
                        refreshClientCards();
                        $('#editClientModal').modal('hide');
                        showToast('success', data.message || 'Client updated successfully!');
                    } else {
                        // Error - show error message
                        showToast('error', data.message || 'Failed to update client. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('error', 'Failed to update client. Please try again.');
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }
        });

        // Add blur validation for real-time feedback
        const fields = editForm.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            field.addEventListener('blur', function() {
                validateSingleField(this);
            });
        });
    }

    // Main form validation function
    function validateClientForm(form) {
        let isValid = true;

        // Validate client name
        const clientName = form.querySelector('[name="client_name"]');
        if (clientName && !clientName.value.trim()) {
            showFieldError(clientName, getTranslation('js.validation.client_name_required', 'Client name is required.'));
            isValid = false;
        } else if (clientName) {
            hideFieldError(clientName);
        }

        // Validate client code
        const clientCode = form.querySelector('[name="client_code"]');
        if (clientCode) {
            if (!clientCode.value.trim()) {
                showFieldError(clientCode, getTranslation('js.validation.client_code_required', 'Client code is required.'));
                isValid = false;
            } else if (!/^[A-Z0-9_]+$/.test(clientCode.value.trim())) {
                showFieldError(clientCode, getTranslation('js.validation.client_code_format', 'Client code must contain only uppercase letters, numbers, and underscores.'));
                isValid = false;
            } else {
                hideFieldError(clientCode);
            }
        }

        // Validate max users
        const maxUsers = form.querySelector('[name="max_users"]');
        if (maxUsers) {
            if (!maxUsers.value.trim()) {
                showFieldError(maxUsers, getTranslation('js.validation.max_users_required', 'Maximum users is required.'));
                isValid = false;
            } else if (!isNumeric(maxUsers.value)) {
                showFieldError(maxUsers, getTranslation('js.validation.max_users_numeric', 'Maximum users must be a number.'));
                isValid = false;
            } else if (parseInt(maxUsers.value) < 1) {
                showFieldError(maxUsers, getTranslation('js.validation.max_users_minimum', 'Maximum users must be at least 1.'));
                isValid = false;
            } else {
                hideFieldError(maxUsers);
            }
        }

        // Validate admin role limit
        const adminRoleLimit = form.querySelector('[name="admin_role_limit"]');
        if (adminRoleLimit) {
            if (!adminRoleLimit.value.trim()) {
                showFieldError(adminRoleLimit, getTranslation('js.validation.admin_role_limit_required', 'Admin role limit is required.'));
                isValid = false;
            } else if (!isNumeric(adminRoleLimit.value)) {
                showFieldError(adminRoleLimit, getTranslation('js.validation.admin_role_limit_numeric', 'Admin role limit must be a number.'));
                isValid = false;
            } else if (parseInt(adminRoleLimit.value) < 1) {
                showFieldError(adminRoleLimit, getTranslation('js.validation.admin_role_limit_minimum', 'Admin role limit must be at least 1.'));
                isValid = false;
            } else {
                hideFieldError(adminRoleLimit);
            }
        }

        // Validate logo (only for add form)
        const logo = form.querySelector('[name="logo"]');
        if (logo && form.id === 'addClientForm') {
            if (logo.files.length === 0) {
                showFieldError(logo, getTranslation('js.validation.client_logo_required', 'Client logo is required.'));
                isValid = false;
            } else {
                const file = logo.files[0];
                const allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
                const maxSize = 5 * 1024 * 1024; // 5MB

                if (!allowedTypes.includes(file.type)) {
                    showFieldError(logo, getTranslation('js.validation.logo_format_invalid', 'Logo must be PNG, JPG, or GIF format.'));
                    isValid = false;
                } else if (file.size > maxSize) {
                    showFieldError(logo, getTranslation('js.validation.logo_size_exceeded', 'Logo file size must be less than 5MB.'));
                    isValid = false;
                } else {
                    hideFieldError(logo);
                }
            }
        } else if (logo && form.id === 'editClientForm' && logo.files.length > 0) {
            // For edit form, validate logo only if a new file is selected
            const file = logo.files[0];
            const allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (!allowedTypes.includes(file.type)) {
                showFieldError(logo, getTranslation('js.validation.logo_format_invalid', 'Logo must be PNG, JPG, or GIF format.'));
                isValid = false;
            } else if (file.size > maxSize) {
                showFieldError(logo, getTranslation('js.validation.logo_size_exceeded', 'Logo file size must be less than 5MB.'));
                isValid = false;
            } else {
                hideFieldError(logo);
            }
        }

        return isValid;
    }

    // Single field validation function
    function validateSingleField(field) {
        const fieldName = field.name || field.id;
        const value = field.value.trim();

        switch (fieldName) {
            case 'client_name':
                if (!value) {
                    showFieldError(field, getTranslation('js.validation.client_name_required', 'Client name is required.'));
                } else {
                    hideFieldError(field);
                }
                break;

            case 'client_code':
                if (!value) {
                    showFieldError(field, getTranslation('js.validation.client_code_required', 'Client code is required.'));
                } else if (!/^[A-Z0-9_]+$/.test(value)) {
                    showFieldError(field, getTranslation('js.validation.client_code_format', 'Client code must contain only uppercase letters, numbers, and underscores.'));
                } else {
                    hideFieldError(field);
                }
                break;

            case 'max_users':
                if (!value) {
                    showFieldError(field, getTranslation('js.validation.max_users_required', 'Maximum users is required.'));
                } else if (!isNumeric(value)) {
                    showFieldError(field, getTranslation('js.validation.max_users_numeric', 'Maximum users must be a number.'));
                } else if (parseInt(value) < 1) {
                    showFieldError(field, getTranslation('js.validation.max_users_minimum', 'Maximum users must be at least 1.'));
                } else {
                    hideFieldError(field);
                }
                break;

            case 'admin_role_limit':
                if (!value) {
                    showFieldError(field, getTranslation('js.validation.admin_role_limit_required', 'Admin role limit is required.'));
                } else if (!isNumeric(value)) {
                    showFieldError(field, getTranslation('js.validation.admin_role_limit_numeric', 'Admin role limit must be a number.'));
                } else if (parseInt(value) < 1) {
                    showFieldError(field, getTranslation('js.validation.admin_role_limit_minimum', 'Admin role limit must be at least 1.'));
                } else {
                    hideFieldError(field);
                }
                break;

            case 'logo':
                if (field.files && field.files.length > 0) {
                    const file = field.files[0];
                    const allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
                    const maxSize = 5 * 1024 * 1024; // 5MB

                    if (!allowedTypes.includes(file.type)) {
                        showFieldError(field, getTranslation('js.validation.logo_format_invalid', 'Logo must be PNG, JPG, or GIF format.'));
                    } else if (file.size > maxSize) {
                        showFieldError(field, getTranslation('js.validation.logo_size_exceeded', 'Logo file size must be less than 5MB.'));
                    } else {
                        hideFieldError(field);
                    }
                }
                break;
        }
    }

    // Helper function to get translations
    function getTranslation(key, fallback) {
        return (window.translations && window.translations[key]) ? window.translations[key] : fallback;
    }
});
