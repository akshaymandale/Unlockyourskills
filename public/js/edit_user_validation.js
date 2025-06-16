document.addEventListener("DOMContentLoaded", function () {
    const editUserForm = document.getElementById("editUserForm");

    if (editUserForm) {
        // ✅ Validate on Focus Out (Blur Event)
        document.querySelectorAll("#editUserForm input, #editUserForm select").forEach(field => {
            field.removeEventListener("blur", userFieldBlurHandler);
            field.addEventListener("blur", userFieldBlurHandler);
        });
    }
    
    // ✅ Field Blur Handler
    function userFieldBlurHandler(event) {
        validateField(event.target);
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
                    // Check if field is required by looking at the label for asterisk
                    const fieldLabel = document.querySelector(`label[for="${field.id}"]`);
                    const isRequired = fieldLabel && fieldLabel.innerHTML.includes('*');

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
