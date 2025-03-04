document.addEventListener("DOMContentLoaded", function () {
    console.log("Validation JS Loaded Successfully!");

    $('#externalContentModal').on('shown.bs.modal', function () {
        console.log("External Content Modal Opened!");
        attachValidation();
    });

    $('#externalContentModal').on('hidden.bs.modal', function () {
        console.log("External Content Modal Closed. Resetting form...");
        resetForm();
    });
    

    function attachValidation() {
        console.log("Attaching validation events to form inputs...");
        const form = document.getElementById("externalContentForm");
        const submitButton = document.getElementById("submit");
    
        if (!form) {
            console.error("❌ External Content Form NOT found!");
            return;
        }
    
        if (!submitButton) {
            console.error("❌ Submit button NOT found! Check your HTML.");
            return;
        }
    
        submitButton.removeEventListener("click", formSubmitHandler);
        submitButton.addEventListener("click", formSubmitHandler);
    
        document.querySelectorAll("#externalContentForm input, #externalContentForm select").forEach(field => {
            field.removeEventListener("blur", fieldBlurHandler);
            field.addEventListener("blur", fieldBlurHandler);
        });
    
        let clearFormBtn = document.getElementById("clearForm");
        if (clearFormBtn) {
            clearFormBtn.removeEventListener("click", resetForm);
            clearFormBtn.addEventListener("click", resetForm);
        }
    }
    
    // ✅ Ensure the function is available globally
    window.attachValidation = attachValidation;
    
    // Call attachValidation when the DOM is loaded
    document.addEventListener("DOMContentLoaded", function () {
        console.log("Validation JS Loaded Successfully!");
    });
    
    function formSubmitHandler(event) {
        event.preventDefault();  // ✅ Prevent form submission
        let isValid = validateForm();
        
        if (isValid) {
            console.log("✅ Form is valid! Submitting...");
            document.getElementById("externalContentForm").submit();  
        } else {
            console.log("❌ Form validation failed. Please fix errors before submitting.");
        }
    }

    function fieldBlurHandler(event) {
        validateField(event.target);
    }

    function validateForm() {
        let isValid = true;
        document.querySelectorAll("#externalContentForm input, #externalContentForm select").forEach(field => {
            if (!validateField(field)) {
                isValid = false;
            }
        });
        return isValid;
    }

    function validateField(field) {
        let isValid = true;
        let value = field.value.trim();
        let fieldName = field.getAttribute("id");

        switch (fieldName) {
            case "title":
                isValid = validateRequired(field, value, "Title is required");
                break;

            case "contentType":
                isValid = validateRequired(field, value, "Please select a content type");
                break;

            case "versionNumber":
                isValid = validateRequired(field, value, "Version is required");
                break;

            case "tags":
                isValid = validateRequired(field, value, "Tags/Keywords are required");
                break;

            case "videoUrl":
            case "courseUrl":
            case "articleUrl":
            case "audioUrl":
                isValid = validateURL(field, value);
                break;

            case "platformName":
            case "audioSource":
                isValid = validateRequired(field, value, "This field is required");
                break;

            case "audioFile":
                isValid = validateAudioFile(field);
                break;
        }

        return isValid;
    }

    function validateRequired(field, value, message) {
        if (value === "") {
            showError(field, message);
            return false;
        } else {
            hideError(field);
            return true;
        }
    }

    function validateURL(field, value) {
        let urlPattern = /^(https?:\/\/)?(www\.)?([\w-]+\.)+[\w]{2,}(:\d+)?(\/[\w/_-]*(\?\S+)?)?$/i;
    
        if (value.trim() === "") {
            showError(field, "URL is required");
            return false;
        } else if (!urlPattern.test(value)) {
            showError(field, "Enter a valid URL (e.g., https://example.com)");
            return false;
        } else {
            hideError(field);
            return true;
        }
    }

    function validateAudioFile(field) {
        let file = field.files[0];
        if (!file) {
            showError(field, "Please upload an audio file (MP3/WAV)");
            return false;
        }

        let allowedTypes = ["audio/mpeg", "audio/wav"];
        if (!allowedTypes.includes(file.type)) {
            showError(field, "Invalid file type. Only MP3 and WAV are allowed.");
            return false;
        }

        hideError(field);
        return true;
    }

    function showError(input, message) {
        let formGroup = input.closest(".form-group"); // Find the closest form-group
        if (!formGroup) {
            console.error(`❌ .form-group NOT found for ${input.name}`);
            return;
        }
    
        let errorElement = formGroup.querySelector(".error-message");
        if (!errorElement) {
            errorElement = document.createElement("small");
            errorElement.className = "error-message text-danger";
            formGroup.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
        input.classList.add("is-invalid");
    
        console.log(`🚨 Error on ${input.name}: ${message}`);
    }
    
    function hideError(input) {
        let formGroup = input.closest(".form-group");
        if (!formGroup) return;
    
        let errorElement = formGroup.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
        }
        input.classList.remove("is-invalid");
    
        console.log(`✅ Cleared error for ${input.name}`);
    }


     // ✅ Function to Reset Form and Remove Errors
     function resetForm() {
        document.getElementById("externalContentForm").reset();
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    }
    
    
});
