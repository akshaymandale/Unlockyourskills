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
        const submitButton = document.getElementById("submit_button");
    
        if (!form) {
            console.error("❌ " + (translations["error.form_not_found"] || "External Content Form NOT found!"));
            return;
        }

        if (!submitButton) {
            console.error("❌ " + (translations["error.submit_button_missing"] || "Submit button NOT found! Check your HTML."));
            return;
        }
    
        // ✅ Attach event listener for form submission
        submitButton.removeEventListener("click", formSubmitHandler);
        submitButton.addEventListener("click", formSubmitHandler);
    
        // ✅ Attach real-time validation when users type or leave a field
        document.querySelectorAll("#externalContentForm input, #externalContentForm select").forEach(field => {
            field.removeEventListener("blur", fieldBlurHandler);
            field.addEventListener("blur", fieldBlurHandler);
    
            field.removeEventListener("input", fieldBlurHandler);
            field.addEventListener("input", fieldBlurHandler);
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
        event.preventDefault();  // Prevent form submission by default
    
        let isValid = validateForm();
    
        if (isValid) {
            console.log("✅ " + (translations["form.valid"] || "Form is valid! Submitting..."));
            document.getElementById("externalContentForm").submit();
        } else {
            console.log("❌ " + (translations["form.invalid"] || "Form validation failed. Fix the errors before submitting."));
        }
    }

    function fieldBlurHandler(event) {
        validateField(event.target);
    }

    function validateForm() {
        let isValid = true;
        const selectedType = document.getElementById("contentType")?.value;
        const audioSource = document.getElementById("audioSource")?.value;
        const audioFile = document.getElementById("audioFile");
        const audioUrl = document.getElementById("audioUrl");
    
        // ✅ Define required fields based on content type
        const validationRules = {
            "youtube-vimeo": ["videoUrl"],
            "linkedin-udemy": ["courseUrl", "platformName"],
            "web-links-blogs": ["articleUrl", "author"],
            "podcasts-audio": ["audioSource"], // Only include audioSource initially
        };
    
        let requiredFields = validationRules[selectedType] || [];
    
        // ✅ Ensure audio validation applies only to the selected option
        if (selectedType === "podcasts-audio") {
            if (audioSource === "upload") {
                requiredFields.push("audioFile"); // Validate only file upload
            } else if (audioSource === "url") {
                requiredFields.push("audioUrl"); // Validate only URL
            }
        }
    
        // ✅ Always validate standard required fields
        const standardFields = ["title", "contentType", "versionNumber", "description", "externalTagList"];
        requiredFields = [...new Set([...requiredFields, ...standardFields])]; // Merge required fields
    
        document.querySelectorAll("#externalContentForm input, #externalContentForm select").forEach(field => {
            if (requiredFields.includes(field.id)) {
                if (!validateField(field)) {
                    isValid = false;
                }
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
                isValid = validateRequired(field, value, translations["validation.required.title"] || "Title is required");
                break;

            case "contentType":
                isValid = validateRequired(field, value, translations["validation.required.content_type"] || "Please select a content type");
                break;

            case "versionNumber":
                isValid = validateRequired(field, value, translations["validation.required.version"] || "Version is required");
                break;

            case "externalTagList":
                isValid = validateTags();
                break;

            case "videoUrl":
            case "courseUrl":
            case "articleUrl":
            case "audioUrl":
                isValid = validateURL(field, value);
                break;

            case "platformName":
            case "audioSource":
                isValid = validateRequired(field, value, translations["validation.required.field"] || "This field is required");
                break;

            case "audioFile":
                isValid = validateAudioFile(field);
                break;

            case "thumbnail":
            isValid = validateThumbnail(field);
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

    function validateTags() {
        let tagField = document.getElementById("externalTagList"); // Hidden input storing tags
        let tagContainer = document.getElementById("externalTagDisplay"); // Where tags are shown
        let tags = tagField.value.trim();
    
        if (tags === "") {
            showError(tagContainer, translations["validation.required.tags"] || "Tags/Keywords are required");
            return false;
        } else {
            hideError(tagContainer);
            return true;
        }
    }

    function validateURL(field, value) {
        let urlPattern = /^(https?:\/\/)?(www\.)?([\w-]+\.)+[\w]{2,}(:\d+)?(\/[\w/_-]*(\?\S+)?)?$/i;

        if (value.trim() === "") {
            showError(field, translations["validation.required.url"] || "URL is required");
            return false;
        } else if (!urlPattern.test(value)) {
            showError(field, translations["validation.invalid.url"] || "Enter a valid URL (e.g., https://example.com)");
            return false;
        } else {
            hideError(field);
            return true;
        }
    }

    function validateAudioFile(field) {
        let file = field.files[0];
        if (!file) {
            showError(field, translations["validation.required.audio_file"] || "Please upload an audio file (MP3/WAV)");
            return false;
        }
    
        let allowedTypes = ["audio/mpeg", "audio/wav"];
        if (!allowedTypes.includes(file.type)) {
            showError(field, translations["validation.invalid.audio_file"] || "Invalid file type. Only MP3 and WAV are allowed.");
            return false;
        }
    
        hideError(field);
        return true;
    }
    
    function validateThumbnail(field) {
        let file = field.files[0];
        if (!file) {
            showError(field, translations["validation.required.thumbnail"] || "Please upload an image file (JPG, PNG, GIF, WebP).");
            return false;
        }
    
        let allowedTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
        if (!allowedTypes.includes(file.type)) {
            showError(field, translations["validation.invalid.thumbnail"] || "Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.");
            return false;
        }
    
        if (file.size > 5 * 1024 * 1024) { // 5MB limit
            showError(field, translations["validation.file_size_exceeded"] || "File size should not exceed 5MB.");
            return false;
        }
    
        hideError(field);
        return true;
    }

    function showError(input, message) {
        let formGroup = input.closest(".form-group");
        if (!formGroup) {
            console.error(`❌ ${translations["error.form_group_missing"] || ".form-group NOT found for"} ${input.name}`);
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

        console.log(`🚨 ${translations["error.validation"] || "Error on"} ${input.name}: ${message}`);
    }
    
    function hideError(input) {
        let formGroup = input.closest(".form-group");
        if (!formGroup) return;

        let errorElement = formGroup.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
        }
        input.classList.remove("is-invalid");

        console.log(`✅ ${translations["success.validation_clear"] || "Cleared error for"} ${input.name}`);
    }


     // ✅ Function to Reset Form and Remove Errors
     function resetForm() {
        document.getElementById("externalContentForm").reset();
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    }
    
    
});
