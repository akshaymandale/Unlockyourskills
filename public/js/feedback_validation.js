document.addEventListener("DOMContentLoaded", function () {
    console.log("Feedback Validation Script Loaded!");

    const feedbackModal = document.getElementById("feedback_feedbackModal");
    const feedbackForm = document.getElementById("feedback_feedbackForm");
    const tagInput = document.getElementById("feedback_feedback_tagInput");
    const hiddenTagList = document.getElementById("feedback_tagList");
    const selectedQuestionIdsInput = document.getElementById("feedback_selectedQuestionIds");
    const addQuestionBtn = document.getElementById("feedback_addFeedbackQuestionBtn");

    $('#feedback_feedbackModal').on('shown.bs.modal', function () {
        attachFeedbackValidation();
    });

    $('#feedback_feedbackModal').on('hidden.bs.modal', function () {
        resetFeedbackForm();
        $(this).modal('hide');
        $('body').removeClass('modal-open');
        document.body.style.overflow = 'auto';
        let backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    });

    function attachFeedbackValidation() {
        if (!feedbackForm) return;

        feedbackForm.removeEventListener("submit", feedbackFormSubmitHandler);
        feedbackForm.addEventListener("submit", feedbackFormSubmitHandler);

        document.querySelectorAll("#feedback_feedbackForm input, #feedback_feedbackForm select").forEach(field => {
            field.removeEventListener("blur", feedbackFieldBlurHandler);
            field.addEventListener("blur", feedbackFieldBlurHandler);
        });

        const tagDisplay = document.getElementById("feedback_tagDisplay");
        if (tagDisplay) {
            const observer = new MutationObserver(() => validateTagInput());
            observer.observe(tagDisplay, { childList: true });
        }

        if (tagInput) {
            tagInput.removeEventListener("blur", validateTagInput);
            tagInput.addEventListener("blur", validateTagInput);
        }

        // ✅ Observer for question selection changes
        if (selectedQuestionIdsInput) {
            const observer = new MutationObserver(() => {
                removeSelectedQuestionsError();
            });
            observer.observe(selectedQuestionIdsInput, { attributes: true, childList: false, characterData: true, subtree: false });
        }
    }

    function feedbackFormSubmitHandler(e) {
        e.preventDefault();
        let isValid = validateFeedbackForm();
        if (isValid) {
            console.log("Feedback form valid. Submitting...");
            feedbackForm.submit(); // Or handle via AJAX
        }
    }

    function feedbackFieldBlurHandler(e) {
        validateFeedbackField(e.target);
    }

    function validateFeedbackForm() {
        let isValid = true;

        const fields = [
            "feedback_feedbackTitle"
        ];

        fields.forEach(id => {
            const field = document.getElementById(id);
            if (!validateFeedbackField(field)) {
                isValid = false;
            }
        });

        if (!validateTagInput()) {
            isValid = false;
        }

        if (!validateSelectedQuestions()) {
            isValid = false;
        }

        return isValid;
    }

    function validateFeedbackField(field) {
        if (!field) return true;

        const name = field.getAttribute("id");
        const value = field.value.trim();
        let isValid = true;

        switch (name) {
            case "feedback_feedbackTitle":
                if (value === "") {
                    showError(field, "Title is required.");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            default:
                hideError(field);
        }

        return isValid;
    }

    function validateTagInput() {
        const tags = hiddenTagList.value.split(",").filter(Boolean);
        if (tags.length === 0) {
            showError(tagInput, "At least one tag is required.");
            return false;
        } else {
            hideError(tagInput);
            return true;
        }
    }

    function validateSelectedQuestions() {
        const value = selectedQuestionIdsInput?.value?.trim();
        const errorId = "feedback_selectedQuestionsError";

        removeSelectedQuestionsError();

        if (!value) {
            const error = document.createElement("span");
            error.id = errorId;
            error.className = "error-message";
            error.textContent = "Please add at least one feedback question.";
            error.style.color = "red";
            error.style.fontSize = "12px";
            error.style.marginLeft = "10px";

            addQuestionBtn.parentNode.appendChild(error);
            return false;
        }

        return true;
    }

    function removeSelectedQuestionsError() {
        const errorElement = document.getElementById("feedback_selectedQuestionsError");
        if (errorElement) {
            errorElement.remove();
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
        errorElement.style.fontSize = "12px";
        input.classList.add("is-invalid");
    }

    function hideError(input) {
        let errorElement = input.parentNode.querySelector(".error-message");
        if (errorElement) errorElement.textContent = "";
        input.classList.remove("is-invalid");
    }

    function resetFeedbackForm() {
        feedbackForm.reset();
        document.querySelectorAll("#feedback_feedbackForm .error-message").forEach(el => el.remove());
        document.querySelectorAll("#feedback_feedbackForm .is-invalid").forEach(el => el.classList.remove("is-invalid"));
        document.getElementById("feedback_tagList").value = "";

        let backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    }
});
