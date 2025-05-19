document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("surveyQuestionForm");
    const titleInput = document.getElementById("surveyQuestionTitle");
    const typeSelect = document.getElementById("surveyQuestionType");
    const optionsWrapper = document.getElementById("surveyOptionsWrapper");
    const tagInput = document.getElementById("tagInput");
    const tagDisplay = document.getElementById("tagDisplay");
    const tagListInput = document.getElementById("tagList");

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
        const value = titleInput.value.trim();
        if (!value) {
            showError(titleInput, "Survey Question Title is required.");
            return false;
        }
        clearError(titleInput);
        return true;
    }

    // --- Options validation ---
    function validateOptions() {
        const type = typeSelect.value;
        if (["multi_choice", "checkbox", "dropdown"].includes(type)) {
            const optionInputs = optionsWrapper.querySelectorAll("input[type='text']");
            let isValid = true;

            if (optionInputs.length < 1) isValid = false;

            optionInputs.forEach(input => {
                if (input.value.trim() === "") {
                    showError(input, "Option cannot be empty.");
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
        const tags = tagListInput.value.split(',').filter(tag => tag.trim() !== '');
        if (tags.length === 0) {
            showError(tagInput, "At least one tag is required.");
            return false;
        }
        clearError(tagInput);
        return true;
    }

    // --- Blur event bindings ---
    titleInput.addEventListener("blur", validateTitle);

    tagInput.addEventListener("blur", validateTags); // Validate tags on blur

    optionsWrapper.addEventListener("blur", function (e) {
        if (e.target && e.target.matches("input[type='text']")) {
            if (e.target.value.trim() === "") {
                showError(e.target, "Option cannot be empty.");
            } else {
                clearError(e.target);
            }
        }
    }, true);

    // --- Form submit validation ---
    form.addEventListener("submit", function (e) {
        let isValid = true;

        if (!validateTitle()) isValid = false;
        if (!validateOptions()) isValid = false;
        if (!validateTags()) isValid = false;

        if (!isValid) {
            e.preventDefault();
        }
    });
});
