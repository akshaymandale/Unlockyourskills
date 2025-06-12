document.addEventListener("DOMContentLoaded", function () {
    console.log("Validation JS Loaded Successfully!");

    // ✅ When External Content Modal Opens, Attach Validation
    $('#externalContentModal').on('shown.bs.modal', function () {
        attachExternalContentValidation();
    });

    // ✅ When Modal Closes, Reset the Form
    $('#externalContentModal').on('hidden.bs.modal', function () {
        resetExternalContentForm();
    });

    function attachExternalContentValidation() {
        console.log("Attaching validation events to form inputs...");
        const form = document.getElementById("externalContentForm");
        const submitButton = document.getElementById("submit_button");

        if (!form) {
            console.error("❌ " + (translate("error.form_not_found") || "External Content Form NOT found!"));
            return;
        }

        if (!submitButton) {
            console.error("❌ " + (translate("error.submit_button_missing") || "Submit button NOT found! Check your HTML."));
            return;
        }

        // ✅ Prevent duplicate event listeners
        form.removeEventListener("submit", externalContentFormSubmitHandler);
        form.addEventListener("submit", externalContentFormSubmitHandler);

        // ✅ Attach Blur Validation on Input Fields
        document.querySelectorAll("#externalContentForm input, #externalContentForm select, #externalContentForm textarea").forEach(field => {
            field.removeEventListener("blur", externalContentFieldBlurHandler);
            field.addEventListener("blur", externalContentFieldBlurHandler);
        });

        // ✅ Attach Blur Event for Tag Input
        const externalTagInput = document.getElementById("externalTagInput");
        if (externalTagInput) {
            externalTagInput.addEventListener("blur", function () {
                validateExternalContentTags();
            });
        }

        // ✅ Reset Form on "Clear" Button Click
        const clearButton = document.getElementById("clearForm");
        if (clearButton) {
            clearButton.addEventListener("click", resetExternalContentForm);
        }
    }


    function externalContentFormSubmitHandler(event) {
        event.preventDefault();
        let isValid = validateExternalContentForm();
        if (isValid) {
            this.submit();
        }
    }

    function externalContentFieldBlurHandler(event) {
        validateExternalContentField(event.target);
    }

    // ✅ Validate Entire Form
    function validateExternalContentForm() {
        let isValid = true;
        document.querySelectorAll("#externalContentForm input, #externalContentForm select, #externalContentForm textarea").forEach(field => {
            if (!validateExternalContentField(field)) {
                isValid = false;
            }
        });
        // ✅ Validate Tags Field
        if (!validateExternalContentTags()) {
            isValid = false;
        }
        return isValid;
    }



    // ✅ Validate Single Field
    function validateExternalContentField(field) {
        let isValid = true;
        let value = field.value.trim();
        let fieldName = field.getAttribute("name") || field.getAttribute("id");

        switch (fieldName) {
            case "title":
                if (value === "") {
                    showError(field, translate("validation.required.title"));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "content_type":
            case "contentType":
                if (value === "") {
                    showError(field, translate("validation.required.content_type"));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "version_number":
            case "versionNumber":
                if (value === "") {
                    showError(field, translate("validation.required.version"));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "video_url":
            case "videoUrl":
            case "course_url":
            case "courseUrl":
            case "article_url":
            case "articleUrl":
            case "audio_url":
            case "audioUrl":
                // ✅ Only validate URLs if they are visible/required for the current content type
                const contentTypeField = document.getElementById("contentType");
                const currentContentType = contentTypeField ? contentTypeField.value : "";

                // Check if this field is required for the current content type
                const isFieldRequired = isFieldRequiredForContentType(fieldName, currentContentType);

                if (isFieldRequired && value === "") {
                    showError(field, translate("validation.required.url"));
                    isValid = false;
                } else if (value !== "" && !isValidURL(value)) {
                    showError(field, translate("validation.invalid.url"));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "platform_name":
            case "platformName":
            case "audio_source":
            case "audioSource":
                // ✅ Only validate if required for the current content type
                const contentTypeForField = document.getElementById("contentType");
                const currentContentTypeForField = contentTypeForField ? contentTypeForField.value : "";
                const isThisFieldRequired = isFieldRequiredForContentType(fieldName, currentContentTypeForField);

                if (isThisFieldRequired && value === "") {
                    showError(field, translate("validation.required.field"));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "audio_file":
            case "audioFile":
                // ✅ Only validate audio file for podcasts-audio content type with upload source
                const contentTypeForAudio = document.getElementById("contentType");
                const audioSourceField = document.getElementById("audioSource");
                const currentContentTypeForAudio = contentTypeForAudio ? contentTypeForAudio.value : "";
                const currentAudioSource = audioSourceField ? audioSourceField.value : "";

                const isAudioFileRequired = currentContentTypeForAudio === "podcasts-audio" && currentAudioSource === "upload";

                if (isAudioFileRequired && field.files.length === 0) {
                    showError(field, translate("validation.required.audio_file"));
                    isValid = false;
                } else if (field.files.length > 0 && !isValidAudioFile(field.files[0])) {
                    showError(field, translate("validation.invalid.audio_file"));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "thumbnail":
                // ✅ Thumbnail is optional, only validate if file is selected
                if (field.files.length > 0 && !isValidThumbnail(field.files[0])) {
                    showError(field, translate("validation.invalid.thumbnail"));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "externalTagInput":
                if (!validateExternalContentTags()) {
                    isValid = false;
                }
                break;
        }

        return isValid;
    }

    // ✅ Validate Tags
    function validateExternalContentTags() {
        const externalTagInput = document.getElementById("externalTagInput");
        const hiddenExternalTagList = document.getElementById("externalTagList");

        if (externalTagInput && hiddenExternalTagList) {
            if (externalTagInput.value.trim() === "" && hiddenExternalTagList.value.trim() === "") {
                const tagField = externalTagInput;
                showError(tagField, translate('validation.required.tags'));
                return false;
            } else {
                hideError(externalTagInput);
                return true;
            }
        }
        return true;
    }

    // ✅ Helper function to validate URL format
    function isValidURL(value) {
        let urlPattern = /^(https?:\/\/)?(www\.)?([\w-]+\.)+[\w]{2,}(:\d+)?(\/[\w/_-]*(\?\S+)?)?$/i;
        return urlPattern.test(value);
    }

    // ✅ Helper function to validate audio file
    function isValidAudioFile(file) {
        let allowedTypes = ["audio/mpeg", "audio/wav"];
        return allowedTypes.includes(file.type);
    }

    // ✅ Helper function to validate thumbnail
    function isValidThumbnail(file) {
        let allowedTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
        return allowedTypes.includes(file.type) && file.size <= 5 * 1024 * 1024; // 5MB limit
    }

    // ✅ Helper function to check if field is required for content type
    function isFieldRequiredForContentType(fieldName, contentType) {
        const fieldRequirements = {
            'youtube-vimeo': ['video_url', 'videoUrl'],
            'linkedin-udemy': ['course_url', 'courseUrl', 'platform_name', 'platformName'],
            'web-links-blogs': ['article_url', 'articleUrl'],
            'podcasts-audio': ['audio_source', 'audioSource']
        };

        // ✅ Special case for audio_url - required when audio_source is "url"
        if ((fieldName === 'audio_url' || fieldName === 'audioUrl') && contentType === 'podcasts-audio') {
            const audioSourceField = document.getElementById("audioSource");
            const currentAudioSource = audioSourceField ? audioSourceField.value : "";
            return currentAudioSource === "url";
        }

        const requiredFields = fieldRequirements[contentType] || [];
        return requiredFields.includes(fieldName);
    }



    // ✅ Show Error Messages
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

    // ✅ Reset Form
    function resetExternalContentForm() {
        document.getElementById("externalContentForm").reset();
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    }


});
