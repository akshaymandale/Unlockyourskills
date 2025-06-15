// ✅ Client Management Validation JavaScript - Following SCORM Pattern

document.addEventListener("DOMContentLoaded", function () {
    // ✅ When Add Client Modal Opens, Attach Validation
    $('#addClientModal').on('shown.bs.modal', function () {
        attachClientValidation('addClientForm');
    });

    // ✅ When Add Client Modal Closes, Reset the Form
    $('#addClientModal').on('hidden.bs.modal', function () {
        resetClientForm('addClientForm');
    });

    // ✅ When Edit Client Modal Opens, Attach Validation
    $('#editClientModal').on('shown.bs.modal', function () {
        attachClientValidation('editClientForm');
    });

    // ✅ When Edit Client Modal Closes, Reset the Form
    $('#editClientModal').on('hidden.bs.modal', function () {
        resetClientForm('editClientForm');
    });

    function attachClientValidation(formId) {
        const clientForm = document.getElementById(formId);

        if (!clientForm) {
            console.error(`Client Form ${formId} NOT found!`);
            return;
        }

        // ✅ Prevent duplicate event listeners
        clientForm.removeEventListener("submit", clientFormSubmitHandler);
        clientForm.addEventListener("submit", clientFormSubmitHandler);

        // ✅ Attach Blur Validation on Input Fields
        document.querySelectorAll(`#${formId} input, #${formId} select, #${formId} textarea`).forEach(field => {
            field.removeEventListener("blur", clientFieldBlurHandler);
            field.addEventListener("blur", clientFieldBlurHandler);
        });
    }

    function clientFormSubmitHandler(event) {
        event.preventDefault();
        let isValid = validateClientForm();
        if (isValid) {
            // Show loading state
            const submitBtn = event.target.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...';
            submitBtn.disabled = true;
            
            this.submit();
        }
    }

    function clientFieldBlurHandler(event) {
        validateClientField(event.target);
    }

    // ✅ Validate Entire Form
    function validateClientForm() {
        const form = event.target;
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
                    showError(field, 'Client name is required.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "max_users":
                if (value === "") {
                    showError(field, 'Maximum users is required.');
                    isValid = false;
                } else if (!isNumeric(value)) {
                    showError(field, 'Maximum users must be a number.');
                    isValid = false;
                } else if (parseInt(value) < 1) {
                    showError(field, 'Maximum users must be at least 1.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "admin_role_limit":
                if (value === "") {
                    showError(field, 'Admin role limit is required.');
                    isValid = false;
                } else if (!isNumeric(value)) {
                    showError(field, 'Admin role limit must be a number.');
                    isValid = false;
                } else if (parseInt(value) < 1) {
                    showError(field, 'Admin role limit must be at least 1.');
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
                        showError(field, 'Client logo is required.');
                        isValid = false;
                    } else {
                        hideError(field);
                    }
                } else {
                    const file = field.files[0];
                    const allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
                    const maxSize = 5 * 1024 * 1024; // 5MB

                    if (!allowedTypes.includes(file.type)) {
                        showError(field, 'Logo must be PNG, JPG, or GIF format.');
                        isValid = false;
                    } else if (file.size > maxSize) {
                        showError(field, 'Logo file size must be less than 5MB.');
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
                    submitBtn.innerHTML = 'Create Client';
                } else {
                    submitBtn.innerHTML = 'Update Client';
                }
                submitBtn.disabled = false;
            }
        }
    }
});
