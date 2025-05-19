document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("surveyQuestionForm");
    const titleInput = document.getElementById("surveyQuestionTitle");
    const typeSelect = document.getElementById("surveyQuestionType");
    const optionsWrapper = document.getElementById("surveyOptionsWrapper");

    // Helper to show error
    function showError(input, message) {
        input.classList.add("is-invalid");

        // Remove existing error if any
        let feedback = input.parentNode.querySelector(".invalid-feedback");
        if (!feedback) {
            feedback = document.createElement("div");
            feedback.className = "invalid-feedback";
            input.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
    }

    // Helper to clear error
    function clearError(input) {
        input.classList.remove("is-invalid");
        const feedback = input.parentNode.querySelector(".invalid-feedback");
        if (feedback) {
            feedback.remove();
        }
    }

    // Validate Title
    function validateTitle() {
        const value = titleInput.value.trim();
        if (!value) {
            showError(titleInput, "Survey Question Title is required.");
            return false;
        }
        clearError(titleInput);
        return true;
    }

    // Validate Options
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
        return true; // No options required
    }

    // Attach on blur
    titleInput.addEventListener("blur", validateTitle);
    // Delegated blur handler for all option inputs inside optionsWrapper
optionsWrapper.addEventListener("blur", function (e) {
    if (e.target && e.target.matches("input[type='text']")) {
        if (e.target.value.trim() === "") {
            showError(e.target, "Option cannot be empty.");
        } else {
            clearError(e.target);
        }
    }
}, true); // useCapture = true so it catches blur

    // Form submit validation
    form.addEventListener("submit", function (e) {
        let isValid = true;

        if (!validateTitle()) isValid = false;
        if (!validateOptions()) isValid = false;

        if (!isValid) {
            e.preventDefault();
            return;
        }

        // Form is valid
       // alert("Form submitted (connect your backend)");
       // e.preventDefault(); // Remove this line once backend is connected
    });
});
