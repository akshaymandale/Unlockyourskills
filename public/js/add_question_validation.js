document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("addAssessmentQuestionForm");

    const maxFileSize = 5 * 1024 * 1024; // 5 MB
    const mediaType = document.getElementById("questionMediaType");
    const mediaFile = document.getElementById("mediaFile");

    function showError(input, message) {
        input.classList.add("is-invalid");
        let error = input.parentElement.querySelector(".invalid-feedback");
        if (!error) {
            error = document.createElement("div");
            error.className = "invalid-feedback text-danger";
            input.parentElement.appendChild(error);
        }
        error.textContent = message;
    }

    function clearError(input) {
        input.classList.remove("is-invalid");
        const error = input.parentElement.querySelector(".invalid-feedback");
        if (error) error.remove();
    }

    function getLabelText(input) {
        const label = input.closest("div").querySelector("label");
        return label ? label.textContent.trim().replace("*", "").replace(":", "").trim() : input.name;
    }

    function validateField(input) {
        // Skip validation for skillsInput and tagsInput
        if (input.id === "skillsInput" || input.id === "tagsInput") return true;
        if (input.offsetParent === null) return true; // Hidden fields

        const value = input.value.trim();
        const label = getLabelText(input);

        if (value === "") {
            showError(input, translations["assessment.validation.required_field"]
                ? translations["assessment.validation.required_field"].replace("{field}", label)
                : `${label} is required.`);
            return false;
        } else {
            clearError(input);
            return true;
        }
    }

    function validateCorrectAnswer() {
        const answerCount = parseInt(document.getElementById("answerCount").value);
        let isChecked = false;
        for (let i = 1; i <= answerCount; i++) {
            const checkbox = document.getElementById(`correct_${i}`);
            if (checkbox && checkbox.offsetParent !== null && checkbox.checked) {
                isChecked = true;
                break;
            }
        }

        const container = document.getElementById("answerOptionsContainer");
        let error = container.querySelector(".correct-answer-error");

        if (!isChecked) {
            if (!error) {
                error = document.createElement("div");
                error.className = "text-danger mt-2 correct-answer-error";
                error.textContent = translations["assessment.validation.correct_answer_required"] || 
                    "At least one option must be marked as the correct answer.";
                container.appendChild(error);
            }
            return false;
        } else {
            if (error) error.remove();
            return true;
        }
    }

    function validateMediaFile() {
        clearError(mediaFile);
        if (mediaType.value === "text" || mediaFile.offsetParent === null) return true;

        const file = mediaFile.files[0];
        if (!file) {
            showError(mediaFile, translations["assessment.validation.media_required"] || "Upload Media is required.");
            return false;
        }

        const allowedTypes = {
            image: ['image/jpeg', 'image/png'],
            audio: ['audio/mpeg', 'audio/wav'],
            video: ['video/mp4', 'video/webm']
        };

        const selected = mediaType.value;
        const isValidType = allowedTypes[selected]?.includes(file.type);
        const isValidSize = file.size <= maxFileSize;

        if (!isValidType) {
            const allowed = allowedTypes[selected].map(t => t.split('/')[1].toUpperCase()).join(', ');
            showError(mediaFile, translations["assessment.validation.invalid_media_type"]
                ? translations["assessment.validation.invalid_media_type"]
                    .replace("{type}", selected)
                    .replace("{allowed}", allowed)
                : `Only ${allowed} files are allowed for ${selected}.`);
            return false;
        }

        if (!isValidSize) {
            showError(mediaFile, translations["assessment.validation.media_size_exceeded"] || 
                "File size must be less than or equal to 5MB.");
            return false;
        }

        clearError(mediaFile);
        return true;
    }

    function validateTags() {
        const tagsHidden = document.getElementById("tagsHidden");
        const container = document.getElementById("tagsContainer");
        let existingError = container.querySelector(".keyword-error");

        if (tagsHidden.value.trim() === "") {
            if (!existingError) {
                existingError = document.createElement("div");
                existingError.className = "text-danger mt-1 keyword-error";
                existingError.textContent = translations["assessment.validation.tags_required"] || 
                    "Tags/Keywords is required.";
                container.appendChild(existingError);
            }
            return false;
        } else {
            if (existingError) existingError.remove();
            return true;
        }
    }

    function validateForm() {
        let isValid = true;

        const questionText = document.getElementById("questionText");
        if (!validateField(questionText)) isValid = false;

        // Validate Tags
        if (!validateTags()) isValid = false;

        // Skip skillsHidden validation
        if (!validateMediaFile()) isValid = false;

        if (document.getElementById("objective").checked) {
            const answerCount = parseInt(document.getElementById("answerCount").value);
            for (let i = 1; i <= answerCount; i++) {
                const option = document.getElementById(`option_${i}`);
                if (option && option.offsetParent !== null) {
                    if (!validateField(option)) isValid = false;
                }
            }

            if (!validateCorrectAnswer()) isValid = false;
        }

        return isValid;
    }

    // Submit Handler
    form.addEventListener("submit", function (e) {
        e.preventDefault();
        if (validateForm()) {
            form.submit();
        }
    });

    // Blur handler (exclude skillsInput and tagsInput)
    form.querySelectorAll("input, textarea, select").forEach(input => {
        input.addEventListener("blur", function () {
            if (input.type === "checkbox") return;
            if (input.id === "skillsInput" || input.id === "tagsInput") return;
            validateField(input);
        });

        input.addEventListener("change", function () {
            clearError(input);
            const error = document.querySelector(".correct-answer-error");
            if (error) error.remove();
        });
    });

    // Correct answer checkbox
    const correctCheckboxes = document.querySelectorAll('input[type="checkbox"][id^="correct_"]');
    correctCheckboxes.forEach(cb => {
        cb.addEventListener("change", function () {
            const error = document.querySelector(".correct-answer-error");
            if (error) error.remove();
        });
    });

    mediaFile.addEventListener("change", validateMediaFile);
});
