document.addEventListener("DOMContentLoaded", function () {
    console.log("JS Loaded Successfully!");

    // ✅ When the SCORM modal is opened, set up event listeners
    $('#scormModal').on('shown.bs.modal', function () {
        console.log("SCORM Modal Opened!");

        attachValidation(); // Attach validation functions
    });

    // ✅ When the modal is hidden, reset form and errors
    $('#scormModal').on('hidden.bs.modal', function () {
        console.log("SCORM Modal Closed. Resetting form...");
        resetForm();
    });

    function attachValidation() {
        const scormForm = document.getElementById("scormForm");

        if (!scormForm) {
            console.error("SCORM Form NOT found!");
            return;
        }

        // ✅ Prevent duplicate event listeners
        scormForm.removeEventListener("submit", formSubmitHandler);
        scormForm.addEventListener("submit", formSubmitHandler);

        // ✅ Attach blur (focus out) validation
        document.querySelectorAll("#scormForm input, #scormForm select, #scormForm textarea").forEach(field => {
            field.removeEventListener("blur", fieldBlurHandler);
            field.addEventListener("blur", fieldBlurHandler);
        });

        // ✅ Clear form on Cancel button click
        document.getElementById("clearForm").addEventListener("click", resetForm);
    }

    function formSubmitHandler(event) {
        event.preventDefault();
        let isValid = validateScormForm();
        if (isValid) {
            console.log("Form is valid! Submitting...");
            this.submit();
        }
    }

    function fieldBlurHandler(event) {
        validateScormField(event.target);
    }

    // ✅ Function to Validate Entire Form
    function validateScormForm() {
        let isValid = true;
        document.querySelectorAll("#scormForm input, #scormForm select, #scormForm textarea").forEach(field => {
            if (!validateScormField(field)) {
                isValid = false;
            }
        });
        return isValid;
    }

    // ✅ Function to Validate a Single Field
    function validateScormField(field) {
        let isValid = true;
        let value = field.value.trim();
        let fieldName = field.getAttribute("name");

        switch (fieldName) {
            case "scorm_title":
                if (value === "") {
                    showError(field, "SCORM Title is required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "zipFile":
                if (field.files.length === 0) {
                    showError(field, "SCORM Zip File is required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "version":
                if (value === "") {
                    showError(field, "Version is required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "scormCategory":
                if (value === "") {
                    showError(field, "Please select a SCORM Category");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;
        }

        return isValid;
    }

    // ✅ Function to Show Error with Bootstrap Red Border
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

    // ✅ Function to Hide Error
    function hideError(input) {
        let errorElement = input.parentNode.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
        }

        input.classList.remove("is-invalid");
    }

    // ✅ Function to Reset Form and Remove Errors
    function resetForm() {
        document.getElementById("scormForm").reset();
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    }
});
