document.addEventListener("DOMContentLoaded", function () {
    // ✅ Fallback translate function if not available
    if (typeof translate === 'undefined') {
        window.translate = function(key) {
            console.warn('Translation function not available, using key:', key);
            return key;
        };
    }

    $('#imageModal').on('shown.bs.modal', function () {
        attachImageValidation();
    });

    $('#imageModal').on('hidden.bs.modal', function () {
        resetImageForm();
    });

    function attachImageValidation() {
        const imageForm = document.getElementById("imageForm");

        if (!imageForm) return;

        imageForm.removeEventListener("submit", imageFormSubmitHandler);
        imageForm.addEventListener("submit", imageFormSubmitHandler);

        document.querySelectorAll("#imageForm input, #imageForm select, #imageForm textarea").forEach(field => {
            field.removeEventListener("blur", imageFieldBlurHandler);
            field.addEventListener("blur", imageFieldBlurHandler);
        });

        const tagInput = document.getElementById("tagInputimage");
        if (tagInput) {
            tagInput.addEventListener("blur", validateTags);
        }

        document.getElementById("clearForm").addEventListener("click", resetImageForm);
    }

    function imageFormSubmitHandler(event) {
        event.preventDefault();
        if (validateImageForm()) {
            this.submit();
        }
    }

    function imageFieldBlurHandler(event) {
        validateImageField(event.target);
    }

    function validateImageForm() {
        let isValid = true;
        document.querySelectorAll("#imageForm input, #imageForm select, #imageForm textarea").forEach(field => {
            if (!validateImageField(field)) isValid = false;
        });

        if (!validateTags()) isValid = false;

        return isValid;
    }

    function validateImageField(field) {
        let isValid = true;
        const value = field.value.trim();
        const fieldName = field.getAttribute("name");

        switch (fieldName) {
            case "image_titleimage":
                if (value === "") {
                    showError(field, translate('js.validation.image_title_required'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "imageFileimage":
                const existingImage = document.getElementById("existing_imageimage").value;
                if (field.files.length === 0 && existingImage === "") {
                    showError(field, translate('js.validation.image_file_required'));
                    isValid = false;
                } else if (field.files.length > 0 && field.files[0].size > 50 * 1024 * 1024) {
                    showError(field, translate('js.validation.image_file_size_exceeded'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "versionimage":
                if (value === "" || isNaN(value)) {
                    showError(field, translate('js.validation.version_required_numeric'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "tagInputimage":
                if (value === "" && document.getElementById("tagListimage").value.trim() === "") {
                    showError(field, translate('js.validation.tags_required'));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;
        }

        return isValid;
    }

    function validateTags() {
        const tagInput = document.getElementById("tagInputimage");
        const hiddenTagList = document.getElementById("tagListimage");

        if (tagInput.value.trim() === "" && hiddenTagList.value.trim() === "") {
            showError(tagInput, translate('js.validation.tags_required'));
            return false;
        } else {
            hideError(tagInput);
            return true;
        }
    }

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

    function hideError(input) {
        const errorElement = input.parentNode.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
        }
        input.classList.remove("is-invalid");
    }

    function resetImageForm() {
        document.getElementById("imageForm").reset();
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    }

    // Tag Management
    const tagInput = document.getElementById("tagInputimage");
    const tagContainer = document.getElementById("tagDisplayimage");
    const hiddenTagList = document.getElementById("tagListimage");

    let tags = [];

    function addTag(tagText) {
        if (tagText.trim() === "" || tags.includes(tagText)) return;
        tags.push(tagText);

        const tagElement = document.createElement("span");
        tagElement.classList.add("tag");
        tagElement.innerHTML = `${tagText} <button type="button" class="remove-tag" data-tag="${tagText}">&times;</button>`;

        tagContainer.appendChild(tagElement);
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
        hiddenTagList.value = tags.join(",");
    }

    tagInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            addTag(tagInput.value.trim());
            tagInput.value = "";
        }
    });

    tagContainer.addEventListener("click", function (event) {
        if (event.target.classList.contains("remove-tag")) {
            const tagText = event.target.getAttribute("data-tag");
            removeTag(tagText);
        }
    });

    tagInput.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && tagInput.value === "" && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });
});
