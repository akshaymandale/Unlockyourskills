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
        const form = document.getElementById('addClientForm');
        if (!form) return;

        // Check if already initialized to prevent duplicates
        if (form.hasAttribute('data-validation-initialized')) {
            return;
        }
        form.setAttribute('data-validation-initialized', 'true');

        form.addEventListener('submit', function(e) {
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
                        // Success - hide modal and redirect with toast message
                        $('#addClientModal').modal('hide');

                        // Use URL-based toast system for reliable display
                        const message = encodeURIComponent(data.message || 'Client created successfully!');
                        const currentUrl = window.location.href.split('?')[0];
                        const separator = currentUrl.includes('?') ? '&' : '?';
                        window.location.href = `${currentUrl}${separator}message=${message}&type=success`;
                    } else {
                        // Error - show error message
                        if (typeof window.showToastOrAlert === 'function') {
                            window.showToastOrAlert(data.message || 'Failed to create client. Please try again.', 'error');
                        } else if (typeof window.showSimpleToast === 'function') {
                            window.showSimpleToast(data.message || 'Failed to create client. Please try again.', 'error');
                        } else {
                            alert(data.message || 'Failed to create client. Please try again.');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (typeof window.showToastOrAlert === 'function') {
                        window.showToastOrAlert('Failed to create client. Please try again.', 'error');
                    } else if (typeof window.showSimpleToast === 'function') {
                        window.showSimpleToast('Failed to create client. Please try again.', 'error');
                    } else {
                        alert('Failed to create client. Please try again.');
                    }
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }
        });

        // Add blur validation for real-time feedback
        const fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            if (field.type === 'file') {
                // File inputs use 'change' event instead of 'blur'
                field.addEventListener('change', function() {
                    validateSingleField(this);
                });
            } else {
                field.addEventListener('blur', function() {
                    validateSingleField(this);
                });
            }
        });
    }

    // Edit form validation
    function initializeEditFormValidation() {
        const form = document.getElementById('editClientForm');
        if (!form) return;

        // Check if already initialized to prevent duplicates
        if (form.hasAttribute('data-validation-initialized')) {
            return;
        }
        form.setAttribute('data-validation-initialized', 'true');

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (validateClientForm(this)) {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';
                submitBtn.disabled = true;

                // Submit form via AJAX
                const formData = new FormData(this);

                // Debug logging for edit form
                console.log('=== CLIENT EDIT DEBUG ===');
                console.log('Form action:', this.action);
                console.log('Form method:', this.method);
                console.log('FormData contents:');
                for (let [key, value] of formData.entries()) {
                    console.log('  ', key, ':', value);
                }

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.text().then(text => {
                        console.log('Response text:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Success - hide modal and redirect with toast message
                        $('#editClientModal').modal('hide');

                        // Use URL-based toast system for reliable display
                        const message = encodeURIComponent(data.message || 'Client updated successfully!');
                        const currentUrl = window.location.href.split('?')[0];
                        const separator = currentUrl.includes('?') ? '&' : '?';
                        window.location.href = `${currentUrl}${separator}message=${message}&type=success`;
                    } else {
                        // Error - show error message
                        if (typeof window.showToastOrAlert === 'function') {
                            window.showToastOrAlert(data.message || 'Failed to update client. Please try again.', 'error');
                        } else if (typeof window.showSimpleToast === 'function') {
                            window.showSimpleToast(data.message || 'Failed to update client. Please try again.', 'error');
                        } else {
                            alert(data.message || 'Failed to update client. Please try again.');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (typeof window.showToastOrAlert === 'function') {
                        window.showToastOrAlert('Failed to update client. Please try again.', 'error');
                    } else if (typeof window.showSimpleToast === 'function') {
                        window.showSimpleToast('Failed to update client. Please try again.', 'error');
                    } else {
                        alert('Failed to update client. Please try again.');
                    }
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }
        });

        // Add blur validation for real-time feedback
        const fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            if (field.type === 'file') {
                // File inputs use 'change' event instead of 'blur'
                field.addEventListener('change', function() {
                    validateSingleField(this);
                });
            } else {
                field.addEventListener('blur', function() {
                    validateSingleField(this);
                });
            }
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

        // Validate logo (required for add form, optional for edit form)
        const logo = form.querySelector('[name="logo"]');
        const isAddForm = form.id === 'addClientForm';

        if (logo) {
            if (isAddForm && logo.files.length === 0) {
                // Logo is required for add form
                showFieldError(logo, getTranslation('js.validation.client_logo_required', 'Client logo is required.'));
                isValid = false;
            } else if (logo.files.length > 0) {
                // Validate file if selected
                const file = logo.files[0];
                const allowedTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/jpg'];
                const allowedExtensions = ['.png', '.jpg', '.jpeg', '.gif'];
                const maxSize = 5 * 1024 * 1024; // 5MB

                // Check both MIME type and file extension for better compatibility
                const fileName = file.name.toLowerCase();
                const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
                const hasValidMimeType = allowedTypes.includes(file.type);

                if (!hasValidMimeType && !hasValidExtension) {
                    showFieldError(logo, getTranslation('js.validation.logo_format_invalid', 'Logo must be PNG, JPG, or GIF format.'));
                    isValid = false;
                } else if (file.size > maxSize) {
                    showFieldError(logo, getTranslation('js.validation.logo_size_exceeded', 'Logo file size must be less than 5MB.'));
                    isValid = false;
                } else {
                    hideFieldError(logo);
                }
            } else {
                // Clear any previous errors if no file is selected (edit form only)
                hideFieldError(logo);
            }
        }

        return isValid;
    }

    // Single field validation function
    function validateSingleField(field) {
        const fieldName = field.name || field.id;
        const value = field.type === 'file' ? '' : field.value.trim();

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
                const isAddForm = field.closest('form').id === 'addClientForm';

                if (isAddForm && field.files.length === 0) {
                    // Logo is required for add form
                    showFieldError(field, getTranslation('js.validation.client_logo_required', 'Client logo is required.'));
                } else if (field.files && field.files.length > 0) {
                    // Validate file if selected
                    const file = field.files[0];
                    const allowedTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/jpg'];
                    const allowedExtensions = ['.png', '.jpg', '.jpeg', '.gif'];
                    const maxSize = 5 * 1024 * 1024; // 5MB

                    // Check both MIME type and file extension for better compatibility
                    const fileName = file.name.toLowerCase();
                    const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));
                    const hasValidMimeType = allowedTypes.includes(file.type);

                    if (!hasValidMimeType && !hasValidExtension) {
                        showFieldError(field, getTranslation('js.validation.logo_format_invalid', 'Logo must be PNG, JPG, or GIF format.'));
                    } else if (file.size > maxSize) {
                        showFieldError(field, getTranslation('js.validation.logo_size_exceeded', 'Logo file size must be less than 5MB.'));
                    } else {
                        hideFieldError(field);
                    }
                } else {
                    // Clear any previous errors if no file is selected (edit form only)
                    hideFieldError(field);
                }
                break;
        }
    }

    // Helper function to get translations
    function getTranslation(key, fallback) {
        return (window.translations && window.translations[key]) ? window.translations[key] : fallback;
    }

    // Make validation functions available globally for modal reinitialization
    window.initializeAddFormValidation = initializeAddFormValidation;
    window.initializeEditFormValidation = initializeEditFormValidation;
    window.validateClientForm = validateClientForm;
    window.validateSingleField = validateSingleField;
});
