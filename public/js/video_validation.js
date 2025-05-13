document.addEventListener("DOMContentLoaded", function () {
    // ✅ When Video Modal Opens, Attach Validation
    $('#videoModal').on('shown.bs.modal', function () {
        attachVideoValidation();
    });

    // ✅ When Modal Closes, Reset the Form
    $('#videoModal').on('hidden.bs.modal', function () {
        resetVideoForm();
    });

    function attachVideoValidation() {
        const videoForm = document.getElementById("videoForm");

        if (!videoForm) {
            console.error("Video Form NOT found!");
            return;
        }

        videoForm.removeEventListener("submit", videoFormSubmitHandler);
        videoForm.addEventListener("submit", videoFormSubmitHandler);

        document.querySelectorAll("#videoForm input, #videoForm select, #videoForm textarea").forEach(field => {
            field.removeEventListener("blur", videoFieldBlurHandler);
            field.addEventListener("blur", videoFieldBlurHandler);
        });

        const tagInputvideo = document.getElementById("tagInputvideo");
        if (tagInputvideo) {
            tagInputvideo.addEventListener("blur", function () {
                validateTags();
            });
        }

        document.getElementById("clearForm").addEventListener("click", resetVideoForm);
    }

    function videoFormSubmitHandler(event) {
        event.preventDefault();
        let isValid = validateVideoForm();
        if (isValid) {
            this.submit();
        }
    }

    function videoFieldBlurHandler(event) {
        validateVideoField(event.target);
    }

    function validateVideoForm() {
        let isValid = true;
        document.querySelectorAll("#videoForm input, #videoForm select, #videoForm textarea").forEach(field => {
            if (!validateVideoField(field)) {
                isValid = false;
            }
        });
        if (!validateTags()) {
            isValid = false;
        }
        return isValid;
    }

    function validateVideoField(field) {
        let isValid = true;
        let value = field.value.trim();
        let fieldName = field.getAttribute("name");

        switch (fieldName) {
            case "video_titlevideo":
                if (value === "") {
                    showError(field, "validation.video_title_required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

                case "videoFilevideo":
                    const existingVideo = document.getElementById("existing_videovideo").value;
                    if (field.files.length === 0 && existingVideo === "") {
                        showError(field, "validation.video_file_required");
                        isValid = false;
                    } else if (field.files.length > 0 && field.files[0].size > 500 * 1024 * 1024) {
                        showError(field, "validation.video_file_size_exceeded");
                        isValid = false;
                    } else {
                        hideError(field);
                    }
                    break;

            case "versionvideo":
                if (value === "" || isNaN(value)) {
                    showError(field, "validation.version_required_numeric");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "timeLimitvideo":
                if (value !== "" && isNaN(value)) {
                    showError(field, "validation.time_limit_numeric");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "tagInputvideo":
                if (value === "" && document.getElementById("tagListvideo").value.trim() === "") {
                    showError(field, "validation.tags_required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;
        }

        return isValid;
    }

    function validateTags() {
        const tagInputvideo = document.getElementById("tagInputvideo");
        const hiddenTagListvideo = document.getElementById("tagListvideo");

        if (tagInputvideo.value.trim() === "" && hiddenTagListvideo.value.trim() === "") {
            const tagField = tagInputvideo;
            showError(tagField, "validation.tags_required");
            return false;
        } else {
            hideError(tagInputvideo);
            return true;
        }
    }

    function showError(input, key) {
        let message = translations[key] || key;

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
        let errorElement = input.parentNode.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
        }
        input.classList.remove("is-invalid");
    }

    function resetVideoForm() {
        document.getElementById("videoForm").reset();
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    }

    // Tag management
    const tagInputvideo = document.getElementById("tagInputvideo");
    const tagContainervideo = document.getElementById("tagDisplayvideo");
    const hiddenTagListvideo = document.getElementById("tagListvideo");

    let tags = [];

    function addTag(tagText) {
        if (tagText.trim() === "" || tags.includes(tagText)) return;
        tags.push(tagText);

        const tagElement = document.createElement("span");
        tagElement.classList.add("tag");
        tagElement.innerHTML = `${tagText} <button type="button" class="remove-tag" data-tag="${tagText}">&times;</button>`;

        tagContainervideo.appendChild(tagElement);
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
        hiddenTagListvideo.value = tags.join(",");
    }

    tagInputvideo.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            addTag(tagInputvideo.value.trim());
            tagInputvideo.value = "";
        }
    });

    tagContainervideo.addEventListener("click", function (event) {
        if (event.target.classList.contains("remove-tag")) {
            const tagText = event.target.getAttribute("data-tag");
            removeTag(tagText);
        }
    });

    tagInputvideo.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && tagInputvideo.value === "" && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });
});
