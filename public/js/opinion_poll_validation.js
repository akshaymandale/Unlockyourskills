/**
 * Opinion Poll Validation JavaScript
 * Follows the same validation patterns as other modules
 * Provides client-side validation with red borders, error symbols, and error text
 */

document.addEventListener("DOMContentLoaded", function () {

    // Check if jQuery and Bootstrap are available
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded!');
        return;
    }

    // Initialize validation when create poll modal is shown
    $('#createPollModal').on('shown.bs.modal', function () {
        attachPollValidation('createPollForm');
        
        // Set up the add question button event listener
        const addQuestionBtn = document.getElementById('addQuestionBtn');
        if (addQuestionBtn) {
            // Remove any existing event listeners to prevent duplicates
            addQuestionBtn.removeEventListener('click', addQuestion);
            addQuestionBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (typeof addQuestion === 'function') {
                    addQuestion();
                } else {
                    console.error('addQuestion function is not available');
                }
            });
        } else {
            console.error('Add Question button not found!');
        }
        
        // Initialize character counting
        initializeCharacterCounting();
        
        // Initialize the create poll form and add initial question
        const questionsContainer = document.getElementById('questionsContainer');
        if (questionsContainer && questionsContainer.children.length === 0) {
            // Reset counters and add initial question
            if (typeof questionCounter !== 'undefined') {
                questionCounter = 0;
            }
            if (typeof optionCounter !== 'undefined') {
                optionCounter = 0;
            }
            if (typeof addQuestion === 'function') {
                addQuestion();
            } else {
                console.error('addQuestion function is not available');
            }
        }
    });

    // Reset form when modal is hidden
    $('#createPollModal').on('hidden.bs.modal', function () {
        resetPollForm('createPollForm');
    });

    // Initialize validation when edit poll modal is shown
    $('#editPollModal').on('shown.bs.modal', function () {
        attachPollValidation('editPollForm');
    });

    // Reset form when edit modal is hidden
    $('#editPollModal').on('hidden.bs.modal', function () {
        resetPollForm('editPollForm');
    });

    // Attach validation immediately if forms exist
    if (document.getElementById('createPollForm')) {
        attachPollValidation('createPollForm');
    }

    if (document.getElementById('editPollForm')) {
        attachPollValidation('editPollForm');
    }

    function attachPollValidation(formId) {
        const pollForm = document.getElementById(formId);

        if (!pollForm) {
            console.error('Form not found:', formId);
            return;
        }


        // Prevent duplicate event listeners
        pollForm.removeEventListener("submit", pollFormSubmitHandler);
        pollForm.addEventListener("submit", pollFormSubmitHandler);

        // Attach blur validation on input fields
        const fields = document.querySelectorAll(`#${formId} input, #${formId} select, #${formId} textarea`);

        fields.forEach(field => {
            field.removeEventListener("blur", pollFieldBlurHandler);
            field.addEventListener("blur", pollFieldBlurHandler);
        });

    }

    function pollFormSubmitHandler(event) {
        event.preventDefault();
        let isValid = validatePollForm(event.target);

        if (isValid) {
            // Handle AJAX submission directly here
            submitPollForm(event.target);
        } else {
        }
    }

    function pollFieldBlurHandler(event) {
        validatePollField(event.target);
    }

    // Validate entire form
    function validatePollForm(form) {
        const formId = form.id;
        let isValid = true;

        // Validate basic poll fields
        document.querySelectorAll(`#${formId} input, #${formId} select, #${formId} textarea`).forEach(field => {
            if (!validatePollField(field)) {
                isValid = false;
            }
        });

        // Validate questions and options
        if (!validateQuestionsAndOptions(formId)) {
            isValid = false;
        }

        return isValid;
    }

    // Validate single field
    function validatePollField(field) {
        let isValid = true;
        let value = field.value.trim();
        let fieldName = field.getAttribute("name");

        switch (fieldName) {
            case "title":
                if (value === "") {
                    showError(field, "Poll title is required");
                    isValid = false;
                } else if (value.length < 3) {
                    showError(field, "Poll title must be at least 3 characters long");
                    isValid = false;
                } else if (value.length > 255) {
                    showError(field, "Poll title cannot exceed 255 characters");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "type":
                if (value === "") {
                    showError(field, "Poll type is required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "target_audience":
                if (value === "") {
                    showError(field, "Target audience is required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "start_datetime":
                if (value === "") {
                    showError(field, "Start date and time is required");
                    isValid = false;
                } else {
                    const startDate = new Date(value);
                    const now = new Date();
                    // Allow 5 minutes buffer for current time
                    const fiveMinutesAgo = new Date(now.getTime() - 5 * 60 * 1000);
                    
                    if (startDate < fiveMinutesAgo) {
                        showError(field, "Start date cannot be in the past");
                        isValid = false;
                    } else {
                        hideError(field);
                        // Also validate end date if it exists in the same form
                        const endDateField = field.form.querySelector('input[name="end_datetime"]');
                        if (endDateField && endDateField.value) {
                            validatePollField(endDateField);
                        }
                    }
                }
                break;

            case "end_datetime":
                if (value === "") {
                    showError(field, "End date and time is required");
                    isValid = false;
                } else {
                    const endDate = new Date(value);
                    const startDateField = field.form.querySelector('input[name="start_datetime"]');

                    if (startDateField && startDateField.value) {
                        const startDate = new Date(startDateField.value);
                        if (endDate <= startDate) {
                            showError(field, "End date must be after start date");
                            isValid = false;
                        } else {
                            hideError(field);
                        }
                    } else {
                        hideError(field);
                    }
                }
                break;

            case "show_results":
                if (value === "") {
                    showError(field, "Show results option is required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "description":
                // Description is optional, but if provided, validate length
                if (value.length > 1000) {
                    showError(field, "Description cannot exceed 1000 characters");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "course_id":
                // Course selection validation removed - course_specific option no longer available
                hideError(field);
                break;

            case "group_id":
                // Only validate if target audience is group_specific
                const targetAudienceGroup = field.form.querySelector('select[name="target_audience"]');
                if (targetAudienceGroup && targetAudienceGroup.value === 'group_specific') {
                    if (value === "") {
                        showError(field, "Group selection is required for group-specific polls");
                        isValid = false;
                    } else {
                        hideError(field);
                    }
                } else {
                    hideError(field);
                }
                break;

            case "custom_field_id":
                // Only validate if target audience is group_specific
                const targetAudienceCustom = field.form.querySelector('select[name="target_audience"]');
                if (targetAudienceCustom && targetAudienceCustom.value === 'group_specific') {
                    if (value === "") {
                        showError(field, "Custom field selection is required for group-specific polls");
                        isValid = false;
                    } else {
                        hideError(field);
                    }
                } else {
                    hideError(field);
                }
                break;

            case "custom_field_value":
                // Only validate if target audience is group_specific
                const targetAudienceValue = field.form.querySelector('select[name="target_audience"]');
                if (targetAudienceValue && targetAudienceValue.value === 'group_specific') {
                    if (value === "") {
                        showError(field, "Custom field value selection is required for group-specific polls");
                        isValid = false;
                    } else {
                        hideError(field);
                    }
                } else {
                    hideError(field);
                }
                break;

            default:
                // For dynamically generated question and option fields
                if (fieldName && fieldName.includes('questions[')) {
                    if (fieldName.includes('][text]') && !fieldName.includes('options[')) {
                        // Question text validation
                        if (value === "") {
                            showError(field, "Question text is required");
                            isValid = false;
                        } else if (value.length < 5) {
                            showError(field, "Question text must be at least 5 characters long");
                            isValid = false;
                        } else {
                            hideError(field);
                        }
                    } else if (fieldName.includes('options[') && fieldName.includes('][text]')) {
                        // Option text validation
                        if (value === "") {
                            showError(field, "Option text is required");
                            isValid = false;
                        } else if (value.length < 1) {
                            showError(field, "Option text cannot be empty");
                            isValid = false;
                        } else {
                            hideError(field);
                        }
                    }
                }
                break;
        }

        return isValid;
    }

    // Validate questions and options structure
    function validateQuestionsAndOptions(formId) {
        let isValid = true;

        // Determine which questions container to use based on form
        let questionsContainer;
        if (formId === 'editPollForm') {
            questionsContainer = document.getElementById('editQuestionsContainer');
        } else {
            questionsContainer = document.getElementById('questionsContainer');
        }

        if (!questionsContainer) {
            return false;
        }

        const questionItems = questionsContainer.querySelectorAll('.question-item');
        
        if (questionItems.length === 0) {
            // Show general error message
            showGeneralError("At least one question is required", formId);
            isValid = false;
        } else {
            hideGeneralError(formId);
            
            questionItems.forEach((questionItem, index) => {
                const questionText = questionItem.querySelector('textarea[name*="[text]"]');
                const optionsContainer = questionItem.querySelector('.options-container');
                
                // Validate question text
                if (questionText && !validatePollField(questionText)) {
                    isValid = false;
                }
                
                // Validate options
                if (optionsContainer) {
                    const optionInputs = optionsContainer.querySelectorAll('input[type="text"]');
                    
                    if (optionInputs.length < 2) {
                        showQuestionError(questionItem, `Question ${index + 1} must have at least 2 options`);
                        isValid = false;
                    } else {
                        hideQuestionError(questionItem);
                        
                        // Validate each option
                        optionInputs.forEach(optionInput => {
                            if (!validatePollField(optionInput)) {
                                isValid = false;
                            }
                        });

                        // Check for duplicate options
                        if (!validateUniqueOptions(optionInputs, questionItem, index + 1)) {
                            isValid = false;
                        }
                    }
                }
            });
        }

        return isValid;
    }

    // Validate unique options within a question
    function validateUniqueOptions(optionInputs, questionItem, questionNumber) {
        const optionValues = [];
        let hasDuplicates = false;

        optionInputs.forEach(input => {
            const value = input.value.trim().toLowerCase();
            if (value && optionValues.includes(value)) {
                hasDuplicates = true;
                showError(input, "Option text must be unique within the question");
            } else if (value) {
                optionValues.push(value);
                hideError(input);
            }
        });

        if (hasDuplicates) {
            showQuestionError(questionItem, `Question ${questionNumber} has duplicate option texts`);
            return false;
        } else {
            hideQuestionError(questionItem);
            return true;
        }
    }

    // Show error messages - following the same pattern as other modules
    function showError(input, message) {
        let errorElement = input.parentNode.querySelector(".error-message");
        if (!errorElement) {
            errorElement = document.createElement("span");
            errorElement.classList.add("error-message");
            input.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
        errorElement.style.color = "#dc3545";
        errorElement.style.fontSize = "0.875rem";
        errorElement.style.marginTop = "0.25rem";
        errorElement.style.display = "block";

        // Bootstrap error styling - red border
        input.classList.add("is-invalid");
    }

    // Hide error messages
    function hideError(input) {
        let errorElement = input.parentNode.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
            errorElement.style.display = "none";
        }

        // Remove error styling
        input.classList.remove("is-invalid");
    }

    // Show general error message
    function showGeneralError(message, formId) {
        const errorContainerId = formId === 'editPollForm' ? 'editGeneralErrorContainer' : 'generalErrorContainer';
        const questionsContainerId = formId === 'editPollForm' ? 'editQuestionsContainer' : 'questionsContainer';

        let errorContainer = document.getElementById(errorContainerId);
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.id = errorContainerId;
            errorContainer.className = 'alert alert-danger mt-3';
            const questionsContainer = document.getElementById(questionsContainerId);
            if (questionsContainer) {
                questionsContainer.parentNode.insertBefore(errorContainer, questionsContainer);
            }
        }
        errorContainer.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${message}`;
        errorContainer.style.display = 'block';
    }

    // Hide general error message
    function hideGeneralError(formId) {
        const errorContainerId = formId === 'editPollForm' ? 'editGeneralErrorContainer' : 'generalErrorContainer';
        const errorContainer = document.getElementById(errorContainerId);
        if (errorContainer) {
            errorContainer.style.display = 'none';
        }
    }

    // Show question-specific error
    function showQuestionError(questionItem, message) {
        let errorElement = questionItem.querySelector('.question-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'question-error alert alert-danger alert-sm mt-2';
            questionItem.appendChild(errorElement);
        }
        errorElement.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${message}`;
        errorElement.style.display = 'block';
    }

    // Hide question-specific error
    function hideQuestionError(questionItem) {
        const errorElement = questionItem.querySelector('.question-error');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    // Reset form and clear all validation errors
    function resetPollForm(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
            
            // Clear all error messages and styling
            form.querySelectorAll('.error-message').forEach(error => {
                error.textContent = '';
                error.style.display = 'none';
            });
            
            form.querySelectorAll('.is-invalid').forEach(field => {
                field.classList.remove('is-invalid');
            });
            
            // Clear general and question errors
            hideGeneralError(formId);
            form.querySelectorAll('.question-error').forEach(error => {
                error.style.display = 'none';
            });

            // Clear questions container for create form
            if (formId === 'createPollForm') {
                const questionsContainer = document.getElementById('questionsContainer');
                if (questionsContainer) {
                    questionsContainer.innerHTML = '';
                }
                // Reset counters
                if (typeof questionCounter !== 'undefined') {
                    questionCounter = 0;
                }
                if (typeof optionCounter !== 'undefined') {
                    optionCounter = 0;
                }
            }
        }
    }

    // Handle AJAX form submission
    function submitPollForm(form) {
        const formData = new FormData(form);
        const isEditForm = form.id === 'editPollForm';

        // Add controller and action if not already present
        if (!formData.has('controller')) {
            formData.append('controller', 'OpinionPollController');
        }
        if (!formData.has('action')) {
            formData.append('action', isEditForm ? 'update' : 'create');
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        const loadingText = isEditForm ?
            '<i class="fas fa-spinner fa-spin me-2"></i>Updating...' :
            '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
        submitBtn.innerHTML = loadingText;
        submitBtn.disabled = true;

        fetch('index.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message, 'success');
                } else {
                    alert(data.message);
                }

                // Close modal and reset form
                const modalId = isEditForm ? 'editPollModal' : 'createPollModal';
                const formId = isEditForm ? 'editPollForm' : 'createPollForm';
                const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                modal.hide();

                // Reset form using validation system
                resetPollForm(formId);

                // Reset questions for create form only
                if (!isEditForm) {
                    const questionsContainer = document.getElementById('questionsContainer');
                    if (questionsContainer) {
                        questionsContainer.innerHTML = '';
                        // Reset counters and add initial question
                        // Access global variables from the main page
                        if (typeof questionCounter !== 'undefined') {
                            questionCounter = 0;
                        }
                        if (typeof optionCounter !== 'undefined') {
                            optionCounter = 0;
                        }
                        if (typeof addQuestion === 'function') {
                            addQuestion();
                        }
                    }
                }

                // Reload polls if function exists
                if (typeof loadPolls === 'function') {
                    loadPolls(typeof currentPage !== 'undefined' ? currentPage : 1);
                }
            } else {
                // Show error message
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message, 'error');
                } else {
                    alert('Error: ' + data.message);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Network error. Please try again.', 'error');
            } else {
                alert('Network error. Please try again.');
            }
        })
        .finally(() => {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }

    // Make functions available globally for the existing AJAX handler
    window.validatePollForm = validatePollForm;
    window.validatePollField = validatePollField;
    window.resetPollForm = resetPollForm;
    window.showError = showError;
    window.hideError = hideError;
});
