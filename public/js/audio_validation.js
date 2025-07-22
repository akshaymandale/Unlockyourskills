document.addEventListener("DOMContentLoaded", function () {
    // ✅ Fallback translate function if not available
    if (typeof translate === 'undefined') {
        window.translate = function(key) {
            console.warn('Translation function not available, using key:', key);
            return key;
        };
    }

    // ✅ When Audio Modal Opens, Attach Validation
    $('#audioModal').on('shown.bs.modal', function () {
        attachAudioValidation();
    });

    // ✅ When Modal Closes, Reset the Form
    $('#audioModal').on('hidden.bs.modal', function () {
        resetAudioForm();
    });

    function attachAudioValidation() {
        const audioForm = document.getElementById("audioForm");

        if (!audioForm) {
            console.error("Audio Form NOT found!");
            return;
        }

        // ✅ Prevent duplicate event listeners
        audioForm.removeEventListener("submit", audioFormSubmitHandler);
        audioForm.addEventListener("submit", audioFormSubmitHandler);

        // ✅ Attach Blur Validation on Input Fields
        document.querySelectorAll("#audioForm input, #audioForm select, #audioForm textarea").forEach(field => {
            field.removeEventListener("blur", audioFieldBlurHandler);
            field.addEventListener("blur", audioFieldBlurHandler);
        });

        // ✅ Attach Blur Event for Tag Input
        const tagInputaudio = document.getElementById("tagInputaudio");
        if (tagInputaudio) {
            tagInputaudio.addEventListener("blur", function () {
                validateTags();
            });
        }

        // ✅ Reset Form on "Clear" Button Click
        const clearButton = document.getElementById("clearFormaudio");
        if (clearButton) {
            clearButton.addEventListener("click", resetAudioForm);
        }
    }

    function audioFormSubmitHandler(event) {
        event.preventDefault();
        let isValid = validateAudioForm();
        if (isValid) {
            this.submit();
        }
    }

    function audioFieldBlurHandler(event) {
        validateAudioField(event.target);
    }

    // ✅ Validate Entire Form
    function validateAudioForm() {
        let isValid = true;
        document.querySelectorAll("#audioForm input, #audioForm select, #audioForm textarea").forEach(field => {
            if (!validateAudioField(field)) {
                isValid = false;
            }
        });
        // ✅ Validate Tags Field
        if (!validateTags()) {
            isValid = false;
        }
        return isValid;
    }

    // ✅ Validate Single Field
    function validateAudioField(field) {
        let isValid = true;
        let value = field.value.trim();
        let fieldName = field.getAttribute("name");

        switch (fieldName) {
            case "audio_titleaudio":
                if (value === "") {
                    showError(field, translate('js.validation.audio_title_required'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "audioFileaudio":
                const existingAudio = document.getElementById("existing_audioaudio").value;
                if (field.files.length === 0 && existingAudio === "") {
                    showError(field, translate('js.validation.audio_file_required'));
                    isValid = false;
                } else if (field.files.length > 0 && field.files[0].size > 10 * 1024 * 1024) {
                    showError(field, translate('js.validation.audio_file_size_exceeded'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "versionaudio":
                if (value === "" || isNaN(value)) {
                    showError(field, translate('js.validation.version_required_numeric'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "timeLimitaudio":
                if (value !== "" && isNaN(value)) {
                    showError(field, translate('js.validation.time_limit_numeric'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "tagInputaudio":
                if (value === "" && document.getElementById("tagListaudio").value.trim() === "") {
                    showError(field, translate('js.validation.tags_required'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;
        }

        return isValid;
    }

    // ✅ Validate Tags
    function validateTags() {
        const tagInputaudio = document.getElementById("tagInputaudio");
        const hiddenTagListaudio = document.getElementById("tagListaudio");

        if (tagInputaudio.value.trim() === "" && hiddenTagListaudio.value.trim() === "") {
            const tagField = tagInputaudio;
            showError(tagField, translate('js.validation.tags_required'));
            return false;
        } else {
            hideError(tagInputaudio);
            return true;
        }
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
    function resetAudioForm() {
        document.getElementById("audioForm").reset();
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    }

    // Tag management (Add/Remove tags)
    const tagInputaudio = document.getElementById("tagInputaudio");
    const tagContaineraudio = document.getElementById("tagDisplayaudio");
    const hiddenTagListaudio = document.getElementById("tagListaudio");

    let tags = [];

    function addTag(tagText) {
        if (tagText.trim() === "" || tags.includes(tagText)) return;
        tags.push(tagText);

        const tagElement = document.createElement("span");
        tagElement.classList.add("tag");
        tagElement.innerHTML = `${tagText} <button type="button" class="remove-tag" data-tag="${tagText}">&times;</button>`;

        tagContaineraudio.appendChild(tagElement);
        updateHiddenInput();
    }

    function removeTag(tagText) {
        tags = tags.filter(tag => tag !== tagText);
        updateHiddenInput();

        document.querySelectorAll(".tag").forEach(tagEl => {
            if (tagEl.textContent.includes(tagText)) {
                tagEl.remove();
            }
        });
    }

    function updateHiddenInput() {
        hiddenTagListaudio.value = tags.join(",");
    }

    tagInputaudio.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            addTag(tagInputaudio.value.trim());
            tagInputaudio.value = "";
        }
    });

    tagContaineraudio.addEventListener("click", function (event) {
        if (event.target.classList.contains("remove-tag")) {
            const tagText = event.target.getAttribute("data-tag");
            removeTag(tagText);
        }
    });

    tagInputaudio.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && tagInputaudio.value === "" && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });
});
