document.addEventListener("DOMContentLoaded", function () {
    console.log("🔥 edit_user_validation.js loaded");

    const editUserForm = document.getElementById("editUserForm");
    const editUserModalForm = document.getElementById("editUserModalForm");

    // Handle regular edit user form
    if (editUserForm) {
        console.log("✅ Regular edit user form found, setting up validation");
        // ✅ Validate on Focus Out (Blur Event)
        document.querySelectorAll("#editUserForm input, #editUserForm select").forEach(field => {
            field.removeEventListener("blur", userFieldBlurHandler);
            field.addEventListener("blur", userFieldBlurHandler);
        });
    }

    // Handle modal edit user form
    if (editUserModalForm) {
        console.log("✅ Modal edit user form found, setting up validation");
        // ✅ Validate on Focus Out (Blur Event) for modal
        document.querySelectorAll("#editUserModalForm input, #editUserModalForm select").forEach(field => {
            field.removeEventListener("blur", modalEditFieldBlurHandler);
            field.addEventListener("blur", modalEditFieldBlurHandler);
        });
    } else {
        console.log("❌ Modal edit user form not found - will try to set up later");
        // Try to set up modal validation when modal is opened
        setupEditModalValidationLater();
    }
    
    // ✅ Field Blur Handler
    function userFieldBlurHandler(event) {
        validateField(event.target);
    }

    // ✅ Modal Field Blur Handler
    function modalEditFieldBlurHandler(event) {
        console.log("🔥 Modal edit field blur:", event.target.name, "value:", event.target.value);
        validateEditModalField(event.target);
    }

    // Function to set up modal validation when modal is opened
    function setupEditModalValidationLater() {
        // Listen for modal show events
        document.addEventListener('shown.bs.modal', function(e) {
            if (e.target.id === 'editUserModal') {
                console.log("🔥 Edit modal opened, setting up validation");
                const modalForm = document.getElementById("editUserModalForm");
                if (modalForm) {
                    console.log("✅ Modal edit form found, setting up validation");
                    // Set up validation for modal fields
                    document.querySelectorAll("#editUserModalForm input, #editUserModalForm select").forEach(field => {
                        console.log("Adding blur listener to modal edit field:", field.name);
                        field.removeEventListener("blur", modalEditFieldBlurHandler);
                        field.addEventListener("blur", modalEditFieldBlurHandler);
                    });

                    // Set up custom fields validation
                    document.querySelectorAll('#editUserModalForm [name^="custom_field_"]').forEach(field => {
                        console.log("Adding blur listener to custom field:", field.name);
                        field.removeEventListener("blur", modalEditFieldBlurHandler);
                        field.addEventListener("blur", modalEditFieldBlurHandler);
                    });
                } else {
                    console.log("❌ Modal edit form still not found");
                }
            }
        });
    }

    // ✅ Function to Validate Entire Form
    function validateForm() {
        let isValid = true;
        const fields = document.querySelectorAll("#editUserForm input, #editUserForm select, #editUserForm textarea");

        fields.forEach(field => {
            if (!validateField(field)) {
                isValid = false;
            }
        });

        // Update tab highlighting after validation
        updateTabHighlighting();

        return isValid;
    }

    // ✅ Function to Validate a Single Field
    function validateField(field) {
        let isValid = true;
        let value = field.value.trim();
        let fieldName = field.getAttribute("name");

        switch (fieldName) {
            case "full_name":
                if (value === "") {
                    showError(field, "validation.full_name_required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "email":
                let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (value === "") {
                    showError(field, "validation.email_required");
                    isValid = false;
                } else if (!emailPattern.test(value)) {
                    showError(field, "validation.email_invalid");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "contact_number":
                // Allow 10-15 digits, with optional spaces, dashes, or parentheses
                let contactPattern = /^[\d\s\-\(\)\+]{10,15}$/;
                if (value === "") {
                    showError(field, "validation.contact_required");
                    isValid = false;
                } else if (!contactPattern.test(value)) {
                    showError(field, "validation.contact_invalid");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "dob":
                let today = new Date().toISOString().split("T")[0];
                // DOB is optional, only validate if provided
                if (value !== "" && value > today) {
                    showError(field, "validation.dob_future");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "user_role":
                if (value === "") {
                    showError(field, "validation.user_role_required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;
            case "edit_modal_reports_to":
                if (value === "") {
                    showError(field, "validation.reports_to_required");
                    isValid = false;
                } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    showError(field, "validation.reports_to_invalid");
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
                        showError(field, "Please select an email from the suggestions or enter a valid email address.");
                        isValid = false;
                    } else {
                        // No autocomplete selection, but email format is valid
                        hideError(field);
                    }
                }
                break;

            case "profile_expiry":
                let expiryDate = new Date(value);
                let todayDate = new Date();
                if (value !== "" && expiryDate < todayDate) {
                    showError(field, "validation.profile_expiry_invalid");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "profile_picture":
                if (field.files.length > 0) {
                    let file = field.files[0];
                    let allowedExtensions = ["image/jpeg", "image/png"];
                    let maxSize = 5 * 1024 * 1024; // 5MB

                    if (!allowedExtensions.includes(file.type)) {
                        showError(field, "validation.image_format");
                        isValid = false;
                    } else if (file.size > maxSize) {
                        showError(field, "validation.image_size");
                        isValid = false;
                    } else {
                        hideError(field);
                    }
                }
                break;

            default:
                // Handle custom fields (Extra Details tab)
                if (fieldName && fieldName.startsWith("custom_field_")) {
                    const isRequired = field.getAttribute('data-required') === '1';
                    if (isRequired && value === "") {
                        showError(field, "This field is required");
                        isValid = false;
                    } else {
                        hideError(field);
                    }
                }
                break;
        }

        // Update tab highlighting after field validation
        updateTabHighlighting();

        return isValid;
    }

    // ✅ Function to Show Error Beside Label & Add Red Border
    function showError(input, key) {
        // Check if translations object exists
        if (typeof translations === 'undefined') {
            console.warn('⚠️ Translations object not found, using fallback messages');
            window.translations = {
                "validation.full_name_required": "Full name is required",
                "validation.email_required": "Email is required",
                "validation.email_invalid": "Please enter a valid email address",
                "validation.contact_required": "Contact number is required",
                "validation.contact_invalid": "Please enter a valid 10-digit contact number",
                "validation.user_role_required": "User role is required",
                "validation.dob_future": "Date of birth cannot be in the future",
                "validation.profile_expiry_invalid": "Profile expiry date cannot be in the past",
                "validation.image_format": "Only JPG and PNG images are allowed",
                "validation.image_size": "Image size must be less than 5MB"
            };
        }
        
        let message = translations[key] || key; // Use translation or fallback to key

        let errorElement = input.parentNode.querySelector(".error-message");
        if (!errorElement) {
            errorElement = document.createElement("span");
            errorElement.classList.add("error-message");
            input.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
        errorElement.style.color = "red";
        errorElement.style.marginLeft = "10px";
        errorElement.style.fontSize = "12px";

        // Add Bootstrap error styling (like SCORM - red border only)
        input.classList.add("is-invalid");
    }

    // ✅ Function to Hide Error & Remove Red Border
    function hideError(input) {
        let errorElement = input.parentNode.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
        }

        // Remove error styling when valid (like SCORM)
        input.classList.remove("is-invalid");
    }

    // ✅ Function to Update Tab Highlighting Based on Validation Errors
    function updateTabHighlighting() {
        // Define tab mappings
        const tabMappings = {
            'basic-details': ['full_name', 'email', 'contact_number', 'gender', 'dob', 'user_role', 'profile_expiry', 'user_status', 'locked_status', 'leaderboard', 'profile_picture'],
            'additional-details': ['country', 'state', 'city', 'timezone', 'language', 'reports_to', 'joining_date', 'retirement_date'],
            'extra-details': [] // Will be populated with custom fields dynamically
        };

        // Get all custom fields for extra-details tab
        const customFields = document.querySelectorAll('[name^="custom_field_"]');
        customFields.forEach(field => {
            tabMappings['extra-details'].push(field.getAttribute('name'));
        });

        // Check each tab for validation errors
        Object.keys(tabMappings).forEach(tabId => {
            const tabButton = document.querySelector(`#editUserTabs button[data-bs-target="#${tabId}"]`);
            if (!tabButton) return;

            let hasErrors = false;

            // Check if any field in this tab has validation errors
            tabMappings[tabId].forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (field && field.classList.contains('is-invalid')) {
                    hasErrors = true;
                }
            });

            // Update tab styling
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

    // ✅ Add validation to blur events for custom fields
    document.querySelectorAll('[name^="custom_field_"]').forEach(field => {
        field.removeEventListener("blur", userFieldBlurHandler);
        field.addEventListener("blur", userFieldBlurHandler);
    });

    // ✅ Add validation to blur events for modal custom fields
    document.querySelectorAll('#editUserModalForm [name^="custom_field_"]').forEach(field => {
        field.removeEventListener("blur", modalEditFieldBlurHandler);
        field.addEventListener("blur", modalEditFieldBlurHandler);
    });

    // ✅ Add Form Submit Event Handler
    if (editUserForm) {
        editUserForm.addEventListener('submit', function(e) {
            // Validate the entire form
            const isValid = validateForm();

            if (!isValid) {
                e.preventDefault(); // Prevent form submission

                // Show alert to user
                alert('Please fix all validation errors before submitting the form. Check tabs with red borders for errors.');

                // Focus on first tab with errors
                const firstErrorTab = document.querySelector('.nav-tabs .nav-link.tab-error');
                if (firstErrorTab) {
                    firstErrorTab.click();
                }

                return false;
            }

            return true;
        });
    }

});

// ✅ Modal Edit Form Validation Functions (make globally accessible)
window.validateEditModalForm = function() {
        let isValid = true;
        const fields = document.querySelectorAll("#editUserModalForm input, #editUserModalForm select, #editUserModalForm textarea");

        fields.forEach(field => {
            const fieldValid = validateEditModalField(field);
            if (!fieldValid) {
                isValid = false;
            }
        });

        // Update tab highlighting after validation
        updateEditModalTabHighlighting();

        return isValid;
    };

    window.validateEditModalField = function(field) {
        let isValid = true;
        let value = field.value ? field.value.trim() : '';
        let fieldName = field.getAttribute("name");

        console.log('validateEditModalField called for:', fieldName, 'value:', value);

        // Clear previous error first
        hideEditModalError(field);

        switch (fieldName) {
            case "full_name":
                if (value === "") {
                    showEditModalError(field, "validation.full_name_required");
                    isValid = false;
                }
                break;

            case "email":
                let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (value === "") {
                    showEditModalError(field, "validation.email_required");
                    isValid = false;
                } else if (!emailPattern.test(value)) {
                    showEditModalError(field, "validation.email_invalid");
                    isValid = false;
                }
                break;

            case "contact_number":
                let contactPattern = /^[\d\s\-\(\)\+]{10,15}$/;
                if (value === "") {
                    showEditModalError(field, "validation.contact_required");
                    isValid = false;
                } else if (!contactPattern.test(value)) {
                    showEditModalError(field, "validation.contact_invalid");
                    isValid = false;
                }
                break;

            case "user_role":
                if (value === "" || value === "Select Role") {
                    showEditModalError(field, "validation.user_role_required");
                    isValid = false;
                }
                break;

            case "dob":
                if (value !== "") {
                    let today = new Date().toISOString().split("T")[0];
                    if (value > today) {
                        showEditModalError(field, "validation.dob_future");
                        isValid = false;
                    }
                }
                break;

            case "profile_expiry":
                if (value !== "") {
                    let expiryDate = new Date(value);
                    let todayDate = new Date();
                    todayDate.setHours(0, 0, 0, 0);
                    expiryDate.setHours(0, 0, 0, 0);

                    if (expiryDate < todayDate) {
                        showEditModalError(field, "validation.profile_expiry_invalid");
                        isValid = false;
                    }
                }
                break;

            case "profile_picture":
                if (field.files && field.files.length > 0) {
                    let file = field.files[0];
                    let allowedExtensions = ["image/jpeg", "image/png", "image/jpg"];
                    let maxSize = 5 * 1024 * 1024; // 5MB

                    if (!allowedExtensions.includes(file.type)) {
                        showEditModalError(field, "validation.image_format");
                        isValid = false;
                    } else if (file.size > maxSize) {
                        showEditModalError(field, "validation.image_size");
                        isValid = false;
                    }
                }
                break;

            default:
                // Handle custom fields
                if (fieldName && fieldName.startsWith("custom_field_")) {
                    const isRequired = field.getAttribute('data-required') === '1';
                    if (isRequired && value === "") {
                        showEditModalError(field, "This field is required");
                        isValid = false;
                    }
                }
                break;
        }

        // Update tab highlighting after field validation
        updateEditModalTabHighlighting();

        return isValid;
    };

    window.showEditModalError = function(input, key) {
        // Check if translations object exists
        if (typeof translations === 'undefined') {
            console.warn('⚠️ Translations object not found, using fallback messages');
            window.translations = {
                "validation.full_name_required": "Full name is required",
                "validation.email_required": "Email is required",
                "validation.email_invalid": "Please enter a valid email address",
                "validation.contact_required": "Contact number is required",
                "validation.contact_invalid": "Please enter a valid 10-digit contact number",
                "validation.user_role_required": "User role is required",
                "validation.dob_future": "Date of birth cannot be in the future",
                "validation.profile_expiry_invalid": "Profile expiry date cannot be in the past",
                "validation.image_format": "Only JPG and PNG images are allowed",
                "validation.image_size": "Image size must be less than 5MB"
            };
        }

        let message = translations[key] || key;

        let errorElement = input.parentNode.querySelector(".invalid-feedback");
        if (!errorElement) {
            errorElement = document.createElement("div");
            errorElement.classList.add("invalid-feedback");
            input.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
        errorElement.style.display = "block";

        input.classList.add("is-invalid");
    };

    window.hideEditModalError = function(input) {
        let errorElement = input.parentNode.querySelector(".invalid-feedback");
        if (errorElement) {
            errorElement.textContent = "";
            errorElement.style.display = "none";
        }
        input.classList.remove("is-invalid");
    };

    window.updateEditModalTabHighlighting = function() {
        const tabMappings = {
            'edit-modal-basic-details': ['full_name', 'email', 'contact_number', 'gender', 'dob', 'user_role', 'profile_expiry', 'user_status', 'locked_status', 'leaderboard', 'profile_picture'],
            'edit-modal-additional-details': ['country', 'state', 'city', 'timezone', 'language', 'reports_to', 'joining_date', 'retirement_date'],
            'edit-modal-extra-details': []
        };

        // Get all custom fields for extra-details tab
        const customFields = document.querySelectorAll('#editUserModalForm [name^="custom_field_"]');
        customFields.forEach(field => {
            tabMappings['edit-modal-extra-details'].push(field.getAttribute('name'));
        });

        // Check each tab for validation errors
        Object.keys(tabMappings).forEach(tabId => {
            const tabButton = document.querySelector(`#editUserModalTabs button[data-bs-target="#${tabId}"]`);
            if (!tabButton) return;

            let hasErrors = false;

            // Check if any field in this tab has validation errors
            tabMappings[tabId].forEach(fieldName => {
                const field = document.querySelector(`#editUserModalForm [name="${fieldName}"]`);
                if (field && field.classList.contains('is-invalid')) {
                    hasErrors = true;
                }
            });

            // Update tab styling
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
    };

// ✅ Function to initialize edit modal validation (can be called from outside)
window.initializeEditModalValidation = function() {
    console.log('🔥 initializeEditModalValidation called');

    const form = document.getElementById('editUserModalForm');
    if (!form) {
        console.log('❌ Edit user modal form not found in initializeEditModalValidation');
        return;
    }

    console.log('✅ Setting up edit modal validation');

    // Set up validation for all form fields
    const fields = form.querySelectorAll('input, select, textarea');
    console.log('🔥 Found', fields.length, 'fields for validation setup');

    fields.forEach(field => {
        console.log('🔥 Setting up validation for:', field.name, 'type:', field.type);

        // Remove existing listeners to avoid duplicates
        field.removeEventListener('blur', handleEditModalFieldBlur);
        field.addEventListener('blur', handleEditModalFieldBlur);
    });

    // Set up custom fields validation
    const customFields = form.querySelectorAll('[name^="custom_field_"]');
    console.log('🔥 Found', customFields.length, 'custom fields for validation setup');

    customFields.forEach(field => {
        console.log('🔥 Setting up validation for custom field:', field.name);
        field.removeEventListener('blur', handleEditModalFieldBlur);
        field.addEventListener('blur', handleEditModalFieldBlur);
    });

    // Add submit handler to block submission if validation fails
    form.onsubmit = null;
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        if (typeof validateEditModalForm === 'function') {
            if (validateEditModalForm()) {
                submitEditUserModal();
            } else {
                // Optionally, focus the first invalid field or tab
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) firstInvalid.focus();
            }
        } else {
            // If validation function doesn't exist, submit directly
            submitEditUserModal();
        }
    });
};

// ✅ Blur event handler for edit modal fields
function handleEditModalFieldBlur(event) {
    console.log('🔥 Edit modal field blur:', event.target.name, 'value:', event.target.value);
    if (typeof validateEditModalField === 'function') {
        validateEditModalField(event.target);
    } else {
        console.log('❌ validateEditModalField function not available');
    }
}

// Helper function to generate project URLs (if not already defined)
function getProjectUrl(path) {
    // Get the base URL from the current location
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
    return baseUrl + '/' + path.replace(/^\//, '');
}

// ✅ Function to submit edit user modal form
function submitEditUserModal() {
    const form = document.getElementById('editUserModalForm');
    if (!form) {
        console.error('Edit user modal form not found');
        return;
    }

    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    
    // Disable submit button and show loading state
    if (submitButton) {
        submitButton.disabled = true;
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
    }

    fetch(getProjectUrl('users/modal/edit'), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
            if (modal) modal.hide();
            
            // Show success message
            if (typeof showSimpleToast === 'function') {
                showSimpleToast(data.message || 'User updated successfully!', 'success');
            }
            
            // Reload the users table
            if (typeof loadUsers === 'function') {
                loadUsers(1);
            }
        } else {
            // Show error message
            if (typeof showSimpleToast === 'function') {
                showSimpleToast(data.message || 'An error occurred while updating the user.', 'error');
            }
            
            // Handle field-specific errors if any
            if (data.field_errors) {
                Object.keys(data.field_errors).forEach(fieldName => {
                    const field = form.querySelector(`[name="${fieldName}"]`);
                    if (field && typeof showEditModalError === 'function') {
                        showEditModalError(field, data.field_errors[fieldName]);
                    }
                });
            }
        }
    })
    .catch(error => {
        console.error('Edit user submission error:', error);
        if (typeof showSimpleToast === 'function') {
            showSimpleToast('A network error occurred. Please try again.', 'error');
        }
    })
    .finally(() => {
        // Re-enable submit button
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-save me-1"></i>Update';
        }
    });
}

// Make functions globally available
window.submitEditUserModal = submitEditUserModal;
