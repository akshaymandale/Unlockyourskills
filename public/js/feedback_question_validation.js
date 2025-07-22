document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("addFeedbackQuestionForm");
    const titleInput = document.getElementById("feedbackQuestionTitle");
    const typeSelect = document.getElementById("feedbackQuestionType");
    const optionsWrapper = document.getElementById("feedbackOptionsWrapper");
    const tagInput = document.getElementById("feedbackTagInput");
    const tagListInput = document.getElementById("feedbackTagList");

    // Only proceed if the form exists (we're on a feedback question page)
    if (!form) {
        return;
    }

    // Track user interaction with fields
    let titleInteracted = false;
    let tagsInteracted = false;

    // --- Error helpers ---
    function showError(input, message) {
        input.classList.add("is-invalid");
        let feedback = input.parentNode.querySelector(".invalid-feedback");
        if (!feedback) {
            feedback = document.createElement("div");
            feedback.className = "invalid-feedback";
            input.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
    }

    function clearError(input) {
        input.classList.remove("is-invalid");
        const feedback = input.parentNode.querySelector(".invalid-feedback");
        if (feedback) feedback.remove();
    }

    // --- Title validation ---
    function validateTitle() {
        if (!titleInput) return true;
        const value = titleInput.value.trim();
        if (!value && titleInteracted) {
            showError(titleInput, translate('js.validation.feedback_question_title_required'));
            return false;
        }
        clearError(titleInput);
        return true;
    }

    // --- Options validation ---
    function validateOptions() {
        if (!typeSelect || !optionsWrapper) return true;
        const type = typeSelect.value;
        if (["multi_choice", "checkbox", "dropdown"].includes(type)) {
            const optionInputs = optionsWrapper.querySelectorAll("input[type='text']");
            let isValid = true;

            if (optionInputs.length < 1) isValid = false;

            optionInputs.forEach(input => {
                if (input.value.trim() === "") {
                    showError(input, translate('js.validation.option_empty'));
                    isValid = false;
                } else {
                    clearError(input);
                }
            });

            return isValid;
        }
        return true; // No options needed for other types
    }

    // --- Tag validation ---
    function validateTags() {
        if (!tagListInput) return true;
        const tags = tagListInput.value.split(',').filter(tag => tag.trim() !== '');
        if (tags.length === 0 && tagsInteracted) {
            showError(tagInput, translate('js.validation.tags_required'));
            return false;
        }
        clearError(tagInput);
        return true;
    }

    // --- Blur event bindings ---
    if (titleInput) {
        titleInput.addEventListener("blur", function() {
            titleInteracted = true;
            validateTitle();
        });
    }
    
    if (tagInput) {
        tagInput.addEventListener("blur", function() {
            tagsInteracted = true;
            validateTags();
        });
    }

    if (optionsWrapper) {
        optionsWrapper.addEventListener("blur", function (e) {
            if (e.target && e.target.matches("input[type='text']")) {
                if (e.target.value.trim() === "") {
                    showError(e.target, translate('js.validation.option_empty'));
                } else {
                    clearError(e.target);
                }
            }
        }, true);
    }

    // --- Form submit validation ---
    form.addEventListener("submit", function (e) {
        let isValid = true;

        // Mark fields as interacted on form submit
        titleInteracted = true;
        tagsInteracted = true;

        if (!validateTitle()) isValid = false;
        if (!validateOptions()) isValid = false;
        if (!validateTags()) isValid = false;

        if (!isValid) {
            e.preventDefault();
        }
    });
});
