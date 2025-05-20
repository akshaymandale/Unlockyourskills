document.addEventListener("DOMContentLoaded", function () {
    console.log("Survey Validation Script Loaded!");

    const surveyModal = document.getElementById("survey_surveyModal");
    const surveyForm = document.getElementById("survey_surveyForm");
    const tagInput = document.getElementById("survey_survey_tagInput");
    const hiddenTagList = document.getElementById("survey_tagList");

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
            "survey_surveyTitle",
            "survey_surveyDescription",
            "survey_surveyType"
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
                    showError(field, "Title is required.");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "survey_surveyDescription":
                if (value === "") {
                    showError(field, "Description is required.");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "survey_surveyType":
                if (value === "") {
                    showError(field, "Type selection is required.");
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
        document.querySelectorAll("#survey_surveyForm .error-message").forEach(el => el.textContent = "");
        document.querySelectorAll("#survey_surveyForm .is-invalid").forEach(el => el.classList.remove("is-invalid"));
        document.getElementById("survey_tagList").value = "";

        let backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) backdrop.remove();
    }
});
