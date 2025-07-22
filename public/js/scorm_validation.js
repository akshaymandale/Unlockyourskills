document.addEventListener("DOMContentLoaded", function () {
    //console.log("SCORM Validation Script Loaded!");

    // ✅ When SCORM Modal Opens, Attach Validation
    $('#scormModal').on('shown.bs.modal', function () {
       // console.log("SCORM Modal Opened!");
        attachScormValidation();
    });

    // ✅ When Modal Closes, Reset the Form
    $('#scormModal').on('hidden.bs.modal', function () {
       // console.log("SCORM Modal Closed. Resetting form...");
        resetScormForm();
    });

    function attachScormValidation() {
        const scormForm = document.getElementById("scormForm");

        if (!scormForm) {
            console.error("SCORM Form NOT found!");
            return;
        }

        // ✅ Prevent duplicate event listeners
        scormForm.removeEventListener("submit", scormFormSubmitHandler);
        scormForm.addEventListener("submit", scormFormSubmitHandler);

        // ✅ Attach Blur Validation on Input Fields
        document.querySelectorAll("#scormForm input, #scormForm select, #scormForm textarea").forEach(field => {
            field.removeEventListener("blur", scormFieldBlurHandler);
            field.addEventListener("blur", scormFieldBlurHandler);
        });

        // ✅ Reset Form on "Clear" Button Click
        document.getElementById("clearForm").addEventListener("click", resetScormForm);
    }

    function scormFormSubmitHandler(event) {
        event.preventDefault();
        let isValid = validateScormForm();
        if (isValid) {
            //console.log("SCORM Form is valid! Submitting...");
            this.submit();
        }
    }

    function scormFieldBlurHandler(event) {
        validateScormField(event.target);
    }

    // ✅ Validate Entire Form
    function validateScormForm() {
        let isValid = true;
        document.querySelectorAll("#scormForm input, #scormForm select, #scormForm textarea").forEach(field => {
            if (!validateScormField(field)) {
                isValid = false;
            }
        });
        return isValid;
    }

    // ✅ Validate Single Field
    function validateScormField(field) {
        let isValid = true;
        let value = field.value.trim();
        let fieldName = field.getAttribute("name");

        switch (fieldName) {
            case "scorm_title":
                if (value === "") {
                    showError(field, getValidationMessage('scorm_title_required', 'SCORM title is required.'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "zipFile":
                const existingZip = document.getElementById("existing_zip").value;
                if (field.files.length === 0 && existingZip === "") {
                    showError(field, getValidationMessage('scorm_zip_required', 'ZIP file is required.'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "version":
                if (value === "") {
                    showError(field, getValidationMessage('version_required', 'Version is required.'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "scormCategory":
                if (value === "") {
                    showError(field, getValidationMessage('scorm_category_required', 'SCORM category is required.'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;
        }

        return isValid;
    }

    // ✅ Get validation message with fallback
    function getValidationMessage(key, fallback) {
        if (typeof translate === 'function') {
            const translated = translate('validation.' + key);
            // If translation returns the key itself, use fallback
            return translated !== ('validation.' + key) ? translated : fallback;
        }
        return fallback;
    }

    // ✅ Show Error Messages with Translations
    function showError(input, message) {

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

        // Bootstrap error styling
        input.classList.add("is-invalid");
    }

    // ✅ Hide Error Messages
    function hideError(input) {
        let errorElement = input.parentNode.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
        }

        input.classList.remove("is-invalid");
    }

    // ✅ Reset SCORM Form and Remove Errors
    function resetScormForm() {
        document.getElementById("scormForm").reset();
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    }
});
