document.addEventListener("DOMContentLoaded", function () {
    console.log("[Validation] Script loaded and DOMContentLoaded fired");
    // Support both main form and modal form
    const form = document.getElementById("assessmentQuestionForm") || document.getElementById("addAssessmentQuestionForm");
    if (form) {
        console.log("[Validation] Found form:", form.id);
    } else {
        console.error("[Validation] No form found with expected IDs");
    }
    const questionTextInput = document.getElementById("questionText");
    const tagsInput = document.getElementById("tagsInput");
    const tagsHidden = document.getElementById("tagsHidden");
    const skillsInput = document.getElementById("skillsInput");
    const skillsHidden = document.getElementById("skillsHidden");
    const answerOptionsContainer = document.getElementById("answerOptionsContainer");
    const questionFormTypeRadios = document.querySelectorAll('input[name="questionFormType"]');

    // --- Error helpers ---
    function showError(input, message) {
        input.classList.add("is-invalid");
        let feedback = input.parentNode.querySelector(".invalid-feedback");
        if (!feedback) {
            feedback = document.createElement("div");
            feedback.className = "invalid-feedback";
            feedback.innerHTML = `<i class=\"fas fa-exclamation-triangle\"></i> ${message}`;
            input.parentNode.appendChild(feedback);
        } else {
            feedback.innerHTML = `<i class=\"fas fa-exclamation-triangle\"></i> ${message}`;
        }
        console.warn(`[Validation] showError: ${input.id || input.name} - ${message}`);
    }

    function clearError(input) {
        input.classList.remove("is-invalid");
        const feedback = input.parentNode.querySelector(".invalid-feedback");
        if (feedback) feedback.remove();
    }

    // --- Question text validation ---
    function validateQuestionText() {
        try {
            const value = questionTextInput.value.trim();
            if (!value) {
                showError(questionTextInput, "Question text is required.");
                return false;
            }
            clearError(questionTextInput);
            console.log("[Validation] validateQuestionText: valid");
            return true;
        } catch (err) {
            console.error("[Validation] Error in validateQuestionText", err);
            return false;
        }
    }

    // --- Tags validation ---
    function validateTags() {
        try {
            const tags = tagsHidden.value.split(',').filter(tag => tag.trim() !== '');
            if (tags.length === 0) {
                showError(tagsInput, "At least one tag is required.");
                return false;
            }
            clearError(tagsInput);
            console.log("[Validation] validateTags: valid");
            return true;
        } catch (err) {
            console.error("[Validation] Error in validateTags", err);
            return false;
        }
    }

    // --- Options validation for objective questions ---
    function validateOptions() {
        try {
            const isObjective = document.getElementById("objective").checked;
            if (!isObjective) {
                return true; // No options needed for subjective questions
            }
            const optionTextareas = answerOptionsContainer.querySelectorAll(".option-textarea:not(.d-none)");
            let isValid = true;
            let hasCorrectAnswer = false;
            let hasFilledOption = false;
            optionTextareas.forEach(textarea => {
                const optionBlock = textarea.closest('.option-block');
                if (!optionBlock.classList.contains('d-none')) {
                    const value = textarea.value.trim();
                    if (value) {
                        hasFilledOption = true;
                        clearError(textarea);
                    } else {
                        showError(textarea, "Option text is required.");
                        isValid = false;
                    }
                    const correctCheckbox = optionBlock.querySelector('input[type="checkbox"]');
                    if (correctCheckbox && correctCheckbox.checked) {
                        hasCorrectAnswer = true;
                    }
                }
            });
            if (!hasFilledOption) {
                isValid = false;
            }
            if (hasFilledOption && !hasCorrectAnswer) {
                const firstOption = answerOptionsContainer.querySelector(".option-textarea");
                if (firstOption) {
                    showError(firstOption, "At least one correct answer must be selected.");
                }
                isValid = false;
            }
            if (isValid) console.log("[Validation] validateOptions: valid");
            return isValid;
        } catch (err) {
            console.error("[Validation] Error in validateOptions", err);
            return false;
        }
    }

    // --- Media validation for non-text types ---
    function validateMedia() {
        try {
            const mediaType = document.getElementById("questionMediaType").value;
            const mediaFile = document.getElementById("mediaFile");
            const questionId = document.getElementById("questionId").value;
            if (mediaType !== 'text') {
                if (!questionId && (!mediaFile.files || mediaFile.files.length === 0)) {
                    showError(mediaFile, "Media file is required for this media type.");
                    return false;
                }
                if (mediaFile.files && mediaFile.files.length > 0) {
                    const file = mediaFile.files[0];
                    const maxSize = 10 * 1024 * 1024;
                    if (file.size > maxSize) {
                        showError(mediaFile, "File size must be less than 10MB.");
                        return false;
                    }
                    const allowedTypes = {
                        'image': ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
                        'audio': ['audio/mp3', 'audio/wav', 'audio/ogg', 'audio/mpeg'],
                        'video': ['video/mp4', 'video/webm', 'video/ogg', 'video/avi']
                    };
                    if (allowedTypes[mediaType] && !allowedTypes[mediaType].includes(file.type)) {
                        let expectedFormats = '';
                        if (mediaType === 'image') expectedFormats = 'JPEG, PNG, GIF, WebP';
                        else if (mediaType === 'audio') expectedFormats = 'MP3, WAV, OGG';
                        else if (mediaType === 'video') expectedFormats = 'MP4, WebM, OGG, AVI';
                        showError(mediaFile, `Invalid file type. Expected: ${expectedFormats}`);
                        return false;
                    }
                }
            }
            clearError(mediaFile);
            console.log("[Validation] validateMedia: valid");
            return true;
        } catch (err) {
            console.error("[Validation] Error in validateMedia", err);
            return false;
        }
    }

    // --- Real-time validation on blur ---
    if (questionTextInput) {
        questionTextInput.addEventListener("blur", function() {
            console.log("[Validation] blur: questionTextInput");
            validateQuestionText();
        });
    }
    if (tagsInput) {
        tagsInput.addEventListener("blur", function() {
            console.log("[Validation] blur: tagsInput");
            validateTags();
        });
    }
    if (answerOptionsContainer) {
        answerOptionsContainer.addEventListener("blur", function (e) {
            if (e.target && e.target.matches(".option-textarea")) {
                console.log("[Validation] blur: option-textarea", e.target.id);
                const value = e.target.value.trim();
                const optionBlock = e.target.closest('.option-block');
                if (!optionBlock.classList.contains('d-none')) {
                    if (!value) {
                        showError(e.target, "Option text is required.");
                    } else {
                        clearError(e.target);
                    }
                }
            }
        }, true);
    }
    if (answerOptionsContainer) {
        answerOptionsContainer.addEventListener("change", function (e) {
            if (e.target && e.target.matches('input[type="checkbox"]')) {
                // Clear any previous correct answer validation errors
                const optionTextareas = answerOptionsContainer.querySelectorAll(".option-textarea");
                optionTextareas.forEach(textarea => {
                    const feedback = textarea.parentNode.querySelector(".invalid-feedback");
                    if (feedback && feedback.textContent.includes("correct answer")) {
                        clearError(textarea);
                    }
                });
                // Validate that at least one correct answer is selected
                validateCorrectAnswers();
            }
        });
    }
    function validateCorrectAnswers() {
        const isObjective = document.getElementById("objective").checked;
        if (!isObjective) return true;
        const visibleOptions = answerOptionsContainer.querySelectorAll(".option-block:not(.d-none)");
        let hasCorrectAnswer = false;
        let hasFilledOption = false;
        visibleOptions.forEach(optionBlock => {
            const textarea = optionBlock.querySelector(".option-textarea");
            const checkbox = optionBlock.querySelector('input[type="checkbox"]');
            if (textarea && textarea.value.trim()) {
                hasFilledOption = true;
                if (checkbox && checkbox.checked) {
                    hasCorrectAnswer = true;
                }
            }
        });
        if (hasFilledOption && !hasCorrectAnswer) {
            const firstOption = answerOptionsContainer.querySelector(".option-textarea");
            if (firstOption) {
                showError(firstOption, "At least one correct answer must be selected.");
            }
            return false;
        }
        return true;
    }
    // Validate media on change
    const mediaFile = document.getElementById("mediaFile");
    if (mediaFile) {
        mediaFile.addEventListener("change", function() {
            clearError(mediaFile);
            if (this.files && this.files.length > 0) {
                console.log("[Validation] change: mediaFile");
                validateMedia();
            }
        });
    }
    questionFormTypeRadios.forEach(radio => {
        radio.addEventListener("change", function() {
            const optionTextareas = answerOptionsContainer.querySelectorAll(".option-textarea");
            optionTextareas.forEach(textarea => {
                clearError(textarea);
            });
        });
    });
    const mediaTypeSelect = document.getElementById("questionMediaType");
    if (mediaTypeSelect) {
        mediaTypeSelect.addEventListener("change", function() {
            const mediaFile = document.getElementById("mediaFile");
            if (mediaFile) {
                clearError(mediaFile);
                mediaFile.value = '';
                const mediaPreview = document.getElementById("mediaPreview");
                if (mediaPreview) {
                    mediaPreview.innerHTML = '';
                }
            }
        });
    }
    // --- Form submit validation ---
    if (form) {
        form.addEventListener("submit", function (e) {
            try {
                console.log("[Validation] form submit: ", form.id);
                let isValid = true;
                const invalidInputs = form.querySelectorAll('.is-invalid');
                invalidInputs.forEach(input => clearError(input));
                if (!validateQuestionText()) isValid = false;
                if (!validateTags()) isValid = false;
                if (!validateOptions()) isValid = false;
                if (!validateMedia()) isValid = false;
                if (!isValid) {
                    e.preventDefault();
                    const firstError = form.querySelector('.is-invalid');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                    console.warn("[Validation] Form submit blocked due to validation errors");
                } else {
                    console.log("[Validation] Form submit: validation passed");
                }
            } catch (err) {
                console.error("[Validation] Error during form submit", err);
                e.preventDefault();
            }
        });
    }
    // --- Helper function to get translation (fallback if not available) ---
    function translate(key) {
        if (typeof translations !== 'undefined' && translations[key]) {
            return translations[key];
        }
        const fallbacks = {
            'js.validation.question_text_required': 'Question text is required.',
            'js.validation.tags_required': 'At least one tag is required.',
            'js.validation.option_empty': 'Option text is required.',
            'js.validation.correct_answer_required': 'At least one correct answer must be selected.',
            'js.validation.media_required': 'Media file is required for this media type.'
        };
        return fallbacks[key] || key;
    }
});
