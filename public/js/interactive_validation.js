document.addEventListener("DOMContentLoaded", function () {
    // ✅ Fallback translate function if not available
    if (typeof translate === 'undefined') {
        window.translate = function(key) {
            console.warn('Translation function not available, using key:', key);
            return key;
        };
    }

    // ✅ When Interactive Modal Opens, Attach Validation
    $('#interactiveModal').on('shown.bs.modal', function () {
        attachInteractiveValidation();
    });

    // ✅ When Modal Closes, Reset the Form
    $('#interactiveModal').on('hidden.bs.modal', function () {
        resetInteractiveForm();
    });

    function attachInteractiveValidation() {
        const interactiveForm = document.getElementById("interactiveForm");

        if (!interactiveForm) {
            console.error("Interactive Form NOT found!");
            return;
        }

        // ✅ Prevent duplicate event listeners
        interactiveForm.removeEventListener("submit", interactiveFormSubmitHandler);
        interactiveForm.addEventListener("submit", interactiveFormSubmitHandler);

        // ✅ Attach Blur Validation on Input Fields
        document.querySelectorAll("#interactiveForm input, #interactiveForm select, #interactiveForm textarea").forEach(field => {
            field.removeEventListener("blur", interactiveFieldBlurHandler);
            field.addEventListener("blur", interactiveFieldBlurHandler);
        });

        // ✅ Reset Form on "Clear" Button Click
        document.getElementById("clearInteractiveForm").addEventListener("click", resetInteractiveForm);

        // ✅ Content Type Change Handler
        const contentTypeSelect = document.getElementById("content_type");
        if (contentTypeSelect) {
            contentTypeSelect.addEventListener("change", handleContentTypeChange);
        }
    }

    function interactiveFormSubmitHandler(event) {
        event.preventDefault();
        let isValid = validateInteractiveForm();
        if (isValid) {
            this.submit();
        }
    }

    function interactiveFieldBlurHandler(event) {
        validateInteractiveField(event.target);
    }

    // ✅ Validate Entire Form
    function validateInteractiveForm() {
        let isValid = true;
        document.querySelectorAll("#interactiveForm input, #interactiveForm select, #interactiveForm textarea").forEach(field => {
            if (!validateInteractiveField(field)) {
                isValid = false;
            }
        });
        return isValid;
    }

    // ✅ Validate Single Field
    function validateInteractiveField(field) {
        let isValid = true;
        let value = field.value.trim();
        let fieldName = field.getAttribute("name");

        switch (fieldName) {
            case "interactive_title":
                if (value === "") {
                    showError(field, translate('js.validation.interactive_title_required') || 'Interactive content title is required.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "content_type":
                if (value === "") {
                    showError(field, translate('js.validation.interactive_content_type_required') || 'Content type is required.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "version":
                if (value === "") {
                    showError(field, translate('js.validation.interactive_version_required') || 'Version is required.');
                    isValid = false;
                } else if (!isNumeric(value)) {
                    showError(field, translate('js.validation.interactive_version_numeric') || 'Version must be a number.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "tagList":
                if (value === "") {
                    showError(field, translate('js.validation.interactive_tags_required') || 'Tags are required.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "content_url":
                if (value !== "" && !isValidURL(value)) {
                    showError(field, translate('js.validation.interactive_url_invalid') || 'Please enter a valid URL (e.g., https://example.com).');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "interactive_timeLimit":
                if (value !== "" && (!isNumeric(value) || parseInt(value) < 0)) {
                    showError(field, translate('js.validation.time_limit_numeric') || 'Time limit must be a valid positive number.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;
        }

        return isValid;
    }

    // ✅ Handle Content Type Change
    function handleContentTypeChange() {
        const contentType = this.value;
        
        // Hide all conditional fields
        document.querySelectorAll('.ai-tutoring-fields, .ar-vr-fields, .adaptive-learning-fields').forEach(section => {
            section.style.display = 'none';
        });

        // Show relevant fields based on content type
        switch (contentType) {
            case 'ai_tutoring':
                document.querySelectorAll('.ai-tutoring-fields').forEach(section => {
                    section.style.display = 'block';
                });
                break;
            case 'ar_vr':
                document.querySelectorAll('.ar-vr-fields').forEach(section => {
                    section.style.display = 'block';
                });
                break;
            case 'adaptive_learning':
                document.querySelectorAll('.adaptive-learning-fields').forEach(section => {
                    section.style.display = 'block';
                });
                break;
        }
    }

    // ✅ Reset Form
    function resetInteractiveForm() {
        const form = document.getElementById("interactiveForm");
        if (form) {
            form.reset();
            
            // Clear all error messages
            document.querySelectorAll("#interactiveForm .error-message").forEach(error => {
                error.textContent = "";
            });

            // Remove invalid classes
            document.querySelectorAll("#interactiveForm .is-invalid").forEach(field => {
                field.classList.remove("is-invalid");
            });

            // Hide all conditional fields
            document.querySelectorAll('.ai-tutoring-fields, .ar-vr-fields, .adaptive-learning-fields').forEach(section => {
                section.style.display = 'none';
            });

            // Clear file displays
            document.getElementById("contentFilePreview").innerHTML = "";
            document.getElementById("thumbnailImagePreview").innerHTML = "";
            document.getElementById("metadataFilePreview").innerHTML = "";

            // Clear hidden fields
            document.getElementById("interactive_id").value = "";
            document.getElementById("existing_content_file").value = "";
            document.getElementById("existing_thumbnail_image").value = "";
            document.getElementById("existing_metadata_file").value = "";

            // Reset radio buttons to default
            document.querySelector('input[name="mobileSupport"][value="No"]').checked = true;
            document.querySelector('input[name="progress_tracking"][value="Yes"]').checked = true;
            document.querySelector('input[name="assessment_integration"][value="No"]').checked = true;
        }
    }

    // ✅ URL Validation Function
    function isValidURL(string) {
        try {
            const url = new URL(string);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (_) {
            return false;
        }
    }

    // ✅ Numeric Validation Function
    function isNumeric(value) {
        return !isNaN(value) && !isNaN(parseFloat(value));
    }

    // ✅ Show Error Message (Consistent with other modules)
    function showError(field, message) {
        hideError(field); // Remove existing error first

        let errorElement = field.parentNode.querySelector(".error-message");
        if (!errorElement) {
            errorElement = document.createElement("span");
            errorElement.classList.add("error-message");
            field.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
        errorElement.style.color = "red";
        errorElement.style.marginLeft = "10px";
        errorElement.style.fontSize = "12px";

        field.classList.add("is-invalid");
    }

    // ✅ Hide Error Message
    function hideError(field) {
        let errorElement = field.parentNode.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
        }
        field.classList.remove("is-invalid");
    }
});
