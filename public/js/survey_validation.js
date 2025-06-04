document.addEventListener("DOMContentLoaded", function () {
    console.log("Survey Validation Script Loaded!");

    const surveyModal = document.getElementById("survey_surveyModal");
    const surveyForm = document.getElementById("survey_surveyForm");
    const tagInput = document.getElementById("survey_survey_tagInput");
    const hiddenTagList = document.getElementById("survey_tagList");
    const selectedQuestionIdsInput = document.getElementById("survey_selectedQuestionIds");
    const addQuestionBtn = document.getElementById("survey_addSurveyQuestionBtn");

    $('#survey_surveyModal').on('shown.bs.modal', function () {
        attachSurveyValidation();
    });

    $('#survey_surveyModal').on('hidden.bs.modal', function () {
        resetSurveyForm();
        $(this).modal('hide');
        $('body').removeClass('modal-open');
        document.body.style.overflow = 'auto';
        let backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    });

    function attachSurveyValidation() {
        if (!surveyForm) return;

        surveyForm.removeEventListener("submit", surveyFormSubmitHandler);
        surveyForm.addEventListener("submit", surveyFormSubmitHandler);

        document.querySelectorAll("#survey_surveyForm input, #survey_surveyForm select").forEach(field => {
            field.removeEventListener("blur", surveyFieldBlurHandler);
            field.addEventListener("blur", surveyFieldBlurHandler);
        });

        const tagDisplay = document.getElementById("survey_tagDisplay");
        if (tagDisplay) {
            const observer = new MutationObserver(() => validateTagInput());
            observer.observe(tagDisplay, { childList: true });
        }

        if (tagInput) {
            tagInput.removeEventListener("blur", validateTagInput);
            tagInput.addEventListener("blur", validateTagInput);
        }

        // ✅ Listen for change on hidden question ID field
        if (selectedQuestionIdsInput) {
            const observer = new MutationObserver(() => {
                removeSelectedQuestionsError();
            });
            observer.observe(selectedQuestionIdsInput, { attributes: true, childList: false, characterData: true, subtree: false });
        }
    }

    function surveyFormSubmitHandler(e) {
        e.preventDefault();
        let isValid = validateSurveyForm();
        if (isValid) {
            console.log("Survey form valid. Submitting...");
            surveyForm.submit(); // Or handle via AJAX
        }
    }

    function surveyFieldBlurHandler(e) {
        validateSurveyField(e.target);
    }

    function validateSurveyForm() {
        let isValid = true;

        const fields = [
            "survey_surveyTitle"
        ];

        fields.forEach(id => {
            const field = document.getElementById(id);
            if (!validateSurveyField(field)) {
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

    function validateSurveyField(field) {
        if (!field) return true;

        const name = field.getAttribute("id");
        const value = field.value.trim();
        let isValid = true;

        switch (name) {
            case "survey_surveyTitle":
                if (value === "") {
                    showError(field, translate('js.validation.survey_title_required'));
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
            showError(tagInput, translate('js.validation.tags_required'));
            return false;
        } else {
            hideError(tagInput);
            return true;
        }
    }

    function validateSelectedQuestions() {
        const value = selectedQuestionIdsInput?.value?.trim();
        const errorId = "survey_selectedQuestionsError";

        // Remove existing error if any
        removeSelectedQuestionsError();

        if (!value) {
            const error = document.createElement("span");
            error.id = errorId;
            error.className = "error-message";
            error.textContent = translate('js.validation.survey_questions_required');
            error.style.color = "red";
            error.style.fontSize = "12px";
            error.style.marginLeft = "10px";

            addQuestionBtn.parentNode.appendChild(error);
            return false;
        }

        return true;
    }

    function removeSelectedQuestionsError() {
        const errorElement = document.getElementById("survey_selectedQuestionsError");
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

    function resetSurveyForm() {
        surveyForm.reset();
        document.querySelectorAll("#survey_surveyForm .error-message").forEach(el => el.remove());
        document.querySelectorAll("#survey_surveyForm .is-invalid").forEach(el => el.classList.remove("is-invalid"));
        document.getElementById("survey_tagList").value = "";

        let backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    }
});
