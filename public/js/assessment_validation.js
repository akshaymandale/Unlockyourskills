document.addEventListener("DOMContentLoaded", function () {
    console.log("Assessment Validation Script Loaded!");

    const assessmentModal = document.getElementById("assessment_assessmentModal");
    const assessmentForm = document.getElementById("assessment_assessmentForm");
    const tagInput = document.getElementById("assessment_assessment_tagInput");
    const hiddenTagList = document.getElementById("assessment_tagList");

    // Show validation when modal opens
    $('#assessment_assessmentModal').on('shown.bs.modal', function () {
        attachAssessmentValidation();
    });

    // Reset form on modal close, hide backdrop, and re-enable scrolling
    $('#assessment_assessmentModal').on('hidden.bs.modal', function () {
        resetAssessmentForm(); // Add this line
        $(this).modal('hide');  // Explicitly hide the modal

        // Ensure modal-open class is removed from body and restore scroll
        $('body').removeClass('modal-open'); // Ensure the modal-open class is removed from body
        document.body.style.overflow = 'auto'; // Ensure body scroll is enabled

        // Remove any lingering backdrop
        let backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    });

    function attachAssessmentValidation() {
        if (!assessmentForm) return;

        assessmentForm.removeEventListener("submit", assessmentFormSubmitHandler);
        assessmentForm.addEventListener("submit", assessmentFormSubmitHandler);

        document.querySelectorAll("#assessment_assessmentForm input, #assessment_assessmentForm select").forEach(field => {
            field.removeEventListener("blur", assessmentFieldBlurHandler);
            field.addEventListener("blur", assessmentFieldBlurHandler);
        });

        // Observe tag list changes and re-validate
        const tagDisplay = document.getElementById("assessment_tagDisplay");
        if (tagDisplay) {
            const observer = new MutationObserver(() => validateTagInput());
            observer.observe(tagDisplay, { childList: true });
        }

        // Optional: validate tags on blur of tag input
        if (tagInput) {
            tagInput.removeEventListener("blur", validateTagInput);
            tagInput.addEventListener("blur", validateTagInput);
        }
    }

    function assessmentFormSubmitHandler(e) {
        e.preventDefault();
        let isValid = validateAssessmentForm();
        if (isValid) {
            console.log("Assessment form valid. Submitting...");
            assessmentForm.submit(); // Or handle with AJAX
        }
    }

    function assessmentFieldBlurHandler(e) {
        validateAssessmentField(e.target);
    }

    function validateAssessmentForm() {
        let isValid = true;
        const fields = [
            "assessment_assessmentTitle",
            "assessment_numAttempts",
            "assessment_passingPercentage",
            "assessment_timeLimit"
        ];

        fields.forEach(id => {
            const field = document.getElementById(id);
            if (!validateAssessmentField(field)) {
                isValid = false;
            }
        });

        if (!validateTagInput()) {
            isValid = false;
        }

        if (document.getElementById("assessment_negativeMarkingYes").checked) {
            const percField = document.getElementById("assessment_negativeMarkingPercentage");
            if (!validateAssessmentField(percField)) isValid = false;
        }

        if (document.getElementById("assessment_assessmentTypeDynamic").checked) {
            const questionsField = document.getElementById("assessment_numberOfQuestions");
            if (!validateAssessmentField(questionsField)) isValid = false;
        }

        return isValid;
    }

    function validateAssessmentField(field) {
        if (!field) return true;

        const name = field.getAttribute("id");
        const value = field.value.trim();
        let isValid = true;

        switch (name) {
            case "assessment_assessmentTitle":
                if (value === "") {
                    showError(field, "Title is required.");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "assessment_numAttempts":
                //do nothing
                break;
            case "assessment_timeLimit":
                //do nothing
                break;
            case "assessment_numberOfQuestions":
                    if (value === "" || isNaN(value)) {
                        showError(field, "This field requires a numeric value.");
                        isValid = false;
                    } else {
                        const selectedCount = parseInt(document.getElementById("assessment_selectedQuestionCount").value, 10) || 0;
                        const requestedCount = parseInt(value, 10);
                        if (requestedCount > selectedCount) {
                            showError(field, `Cannot exceed ${selectedCount} selected question${selectedCount !== 1 ? 's' : ''}.`);
                            isValid = false;
                        } else {
                            hideError(field);
                        }
                    }
                    break;

            case "assessment_passingPercentage":
                const num = parseFloat(value);
                if (value === "" || isNaN(num) || num < 0 || num > 100) {
                    showError(field, "Passing percentage must be between 0 and 100.");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "assessment_negativeMarkingPercentage":
                if (value === "") {
                    showError(field, "Percentage is required.");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;
        }

        return isValid;
    }

    function validateTagInput() {
        const tags = hiddenTagList.value.split(",").filter(Boolean);
        if (tags.length === 0) {
            showError(tagInput, "Tags/keywords are required.");
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

    // Add the missing reset function for the assessment form
    function resetAssessmentForm() {
        assessmentForm.reset();
        document.querySelectorAll("#assessment_assessmentForm .error-message").forEach(el => el.textContent = "");
        document.querySelectorAll("#assessment_assessmentForm .is-invalid").forEach(el => el.classList.remove("is-invalid"));
        document.getElementById("assessment_tagList").value = "";

        // Ensure the backdrop is removed correctly
        let backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }
});
