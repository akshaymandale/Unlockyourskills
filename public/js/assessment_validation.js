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

        // Validate selected questions
        if (!validateSelectedQuestions()) {
            isValid = false;
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
                    showError(field, translate("assessment.validation.title_required"));
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
                        showError(field, translate("assessment.validation.num_questions_required"));
                        isValid = false;
                    } else {
                        const selectedCount = parseInt(document.getElementById("assessment_selectedQuestionCount").value, 10) || 0;
                        const requestedCount = parseInt(value, 10);
                        if (requestedCount > selectedCount) {
                            const plural = selectedCount !== 1 ? 's' : '';
                            showError(field, translate("assessment.validation.num_questions_exceeds", {
                                count: selectedCount,
                                plural: plural
                            }));
                            isValid = false;
                        } else {
                            hideError(field);
                        }
                    }
                    break;

            case "assessment_passingPercentage":
                const num = parseFloat(value);
                if (value === "" || isNaN(num) || num < 0 || num > 100) {
                    showError(field, translate("assessment.validation.passing_percentage_invalid"));
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "assessment_negativeMarkingPercentage":
                if (value === "") {
                    showError(field, translate("assessment.validation.negative_percentage_required"));
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
            showError(tagInput, translate("assessment.validation.tags_required"));
            return false;
        } else {
            hideError(tagInput);
            return true;
        }
    }

    function validateSelectedQuestions() {
        const selectedQuestionIds = document.getElementById("assessment_selectedQuestionIds");
        const selectedQuestionCount = document.getElementById("assessment_selectedQuestionCount");
        const addQuestionBtn = document.getElementById("assessment_addQuestionBtn");

        // Clear any existing error
        hideSelectedQuestionsError();

        if (!selectedQuestionIds || !selectedQuestionIds.value.trim()) {
            showSelectedQuestionsError("assessment.validation.questions_required");
            return false;
        }

        const questionCount = parseInt(selectedQuestionCount.value) || 0;
        if (questionCount === 0) {
            showSelectedQuestionsError("assessment.validation.questions_required");
            return false;
        }

        return true;
    }

    function showSelectedQuestionsError(translationKey) {
        const addQuestionBtn = document.getElementById("assessment_addQuestionBtn");
        if (!addQuestionBtn) return;

        // Remove any existing error message
        hideSelectedQuestionsError();

        // Get translated message
        const message = translate(translationKey);

        // Create error element
        const errorElement = document.createElement("div");
        errorElement.classList.add("selected-questions-error", "text-danger", "mt-1");
        errorElement.style.fontSize = "12px";
        errorElement.textContent = message;

        // Insert error message after the Add Question button
        addQuestionBtn.parentNode.insertBefore(errorElement, addQuestionBtn.nextSibling);
    }

    function hideSelectedQuestionsError() {
        const existingError = document.querySelector(".selected-questions-error");
        if (existingError) {
            existingError.remove();
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

        // Clear selected questions error
        hideSelectedQuestionsError();

        // Ensure the backdrop is removed correctly
        let backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }

    // Make functions globally accessible
    window.validateAssessmentForm = validateAssessmentForm;
    window.hideSelectedQuestionsError = hideSelectedQuestionsError;
});
