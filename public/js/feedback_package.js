document.addEventListener("DOMContentLoaded", function () {
    const feedbackForm = document.getElementById("feedback_feedbackForm");
    
    // Only proceed if the form exists (we're on the VLR feedback page)
    if (!feedbackForm) {
        return;
    }
    
    const feedbackTitle = document.getElementById("feedback_feedbackTitle");
    const tagInput = document.getElementById("feedback_feedback_tagInput");
    const tagContainer = document.getElementById("feedback_tagDisplay");
    const hiddenTagList = document.getElementById("feedback_tagList");

    const addFeedbackBtn = document.getElementById("addFeedbackBtn");
    const feedbackModalLabel = document.getElementById("feedback_feedbackModalLabel");
    const feedbackModal = new bootstrap.Modal(document.getElementById('feedback_feedbackModal'));

    const selectedQuestionsBody = document.getElementById("feedback_selectedQuestionsBody");
    const selectedQuestionsWrapper = document.getElementById("feedback_selectedQuestionsWrapper");
    const selectedQuestionIdsInput = document.getElementById("feedback_selectedQuestionIds");
    const selectedQuestionCountInput = document.getElementById("feedback_selectedQuestionCount");

    let tags = [];

    // Add field-level validation event listeners
    if (feedbackTitle) {
        feedbackTitle.addEventListener("blur", function() {
            validateFeedbackField(this);
        });
    }

    if (tagInput) {
        tagInput.addEventListener("blur", function() {
            validateTagInput();
        });
    }

    // Add observer for selected questions validation
    if (selectedQuestionIdsInput) {
        const observer = new MutationObserver(() => {
            removeSelectedQuestionsError();
        });
        observer.observe(selectedQuestionIdsInput, { attributes: true, childList: false, characterData: true, subtree: false });
    }

    function addTag(tagText) {
        if (tagText.trim() === "" || tags.includes(tagText)) return;

        tags.push(tagText);

        const tagElement = document.createElement("span");
        tagElement.classList.add("tag");
        tagElement.innerHTML = `${tagText} <button type="button" class="remove-tag" data-tag="${tagText}">&times;</button>`;

        tagContainer.appendChild(tagElement);
        updateHiddenInput();
    }

    function removeTag(tagText) {
        tags = tags.filter(tag => tag !== tagText);
        updateHiddenInput();

        document.querySelectorAll(".tag").forEach(tagEl => {
            if (tagEl.textContent.includes(tagText)) {
                tagEl.remove();
            }
        });
    }

    function updateHiddenInput() {
        hiddenTagList.value = tags.join(",");
    }

    tagInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            addTag(tagInput.value.trim());
            tagInput.value = "";
        }
    });

    tagInput.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && tagInput.value === "" && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });

    tagContainer.addEventListener("click", function (event) {
        if (event.target.classList.contains("remove-tag")) {
            const tagText = event.target.getAttribute("data-tag");
            removeTag(tagText);
        }
    });

    addFeedbackBtn.addEventListener("click", function () {
        if (feedbackForm) {
            feedbackForm.reset();
        }
        tags = [];
        if (tagContainer) {
            tagContainer.innerHTML = "";
        }
        updateHiddenInput();

        if (selectedQuestionsBody) {
            selectedQuestionsBody.innerHTML = "";
        }
        if (selectedQuestionIdsInput) {
            selectedQuestionIdsInput.value = "";
        }
        if (selectedQuestionCountInput) {
            selectedQuestionCountInput.value = "";
        }
        if (selectedQuestionsWrapper) {
            selectedQuestionsWrapper.style.display = "none";
        }

        if (feedbackModalLabel) {
            feedbackModalLabel.textContent = "Add Feedback";
        }
        feedbackModal.show();
    });

    // Edit Feedback Modal Logic (exactly like survey)
    document.querySelectorAll(".edit-feedback").forEach(button => {
        button.addEventListener("click", function () {
            const feedbackData = JSON.parse(this.dataset.feedback);
            const feedbackId = feedbackData.id;
            const feedback_selectedFeedbackQuestionsWrapper = document.getElementById("feedback_selectedFeedbackQuestionsWrapper");

            // Add hidden input for feedback ID
            let feedbackIdInput = document.getElementById('feedbackId');
            if (!feedbackIdInput) {
                feedbackIdInput = document.createElement('input');
                feedbackIdInput.type = 'hidden';
                feedbackIdInput.name = 'feedbackId';
                feedbackIdInput.id = 'feedbackId';
                feedbackForm.appendChild(feedbackIdInput);
            }
            feedbackIdInput.value = feedbackId;

            fetch(`vlr/feedback/${feedbackId}`)
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    feedbackTitle.value = data.title;

                    tagContainer.innerHTML = "";
                    tags = [];
                    if (data.tags) {
                        data.tags.split(",").forEach(tag => addTag(tag.trim()));
                    }

                    // Load and display selected questions
                    selectedQuestionsBody.innerHTML = "";
                    selectedQuestionIdsInput.value = "";
                    if (data.selected_questions && data.selected_questions.length > 0) {
                        data.selected_questions.forEach(question => {
                            const row = document.createElement("tr");
                            row.innerHTML = `
                                <td>${question.title}</td>
                                <td>${question.tags}</td>
                                <td>${question.type}</td>
                            `;
                            selectedQuestionsBody.appendChild(row);
                        });

                        const selectedIds = data.selected_questions.map(q => q.id);
                        selectedQuestionIdsInput.value = selectedIds.join(",");
                        selectedQuestionCountInput.value = selectedIds.length;
                        feedback_selectedFeedbackQuestionsWrapper.style.display = "block";
                    } else {
                        feedback_selectedFeedbackQuestionsWrapper.style.display = "none";
                    }

                    feedbackModalLabel.textContent = "Edit Feedback";
                    feedbackModal.show();
                })
                .catch(error => {
                    console.error("Error fetching feedback:", error);
                    alert("Failed to load feedback data.");
                });
        });
    });

    const dismissButton = document.querySelector('button[data-bs-dismiss="modal"]');
    if (dismissButton) {
        dismissButton.addEventListener('click', function () {
            if (feedbackForm) {
                feedbackForm.reset();
            }
            tags = [];
            if (tagContainer) {
                tagContainer.innerHTML = "";
            }
            updateHiddenInput();

            if (selectedQuestionsBody) {
                selectedQuestionsBody.innerHTML = "";
            }
            if (selectedQuestionIdsInput) {
                selectedQuestionIdsInput.value = "";
            }
            if (selectedQuestionCountInput) {
                selectedQuestionCountInput.value = "";
            }
            if (selectedQuestionsWrapper) {
                selectedQuestionsWrapper.style.display = "none";
            }
        });
    }

    function showSelectedQuestionsGrid() {
        if (selectedQuestionsBody && selectedQuestionsWrapper) {
            if (selectedQuestionsBody.children.length > 0) {
                selectedQuestionsWrapper.style.display = "block";
            } else {
                selectedQuestionsWrapper.style.display = "none";
            }
        }
    }

    // Field-level validation function
    function validateFeedbackField(field) {
        if (!field) return true;

        const name = field.getAttribute("id");
        const value = field.value.trim();
        let isValid = true;

        switch (name) {
            case "feedback_feedbackTitle":
                if (value === "") {
                    showError(field, 'Feedback title is required.');
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

    // Tag validation function
    function validateTagInput() {
        const tags = hiddenTagList.value.split(",").filter(Boolean);
        if (tags.length === 0) {
            showError(tagInput, 'At least one tag is required.');
            return false;
        } else {
            hideError(tagInput);
            return true;
        }
    }

    // Selected questions validation function
    function validateSelectedQuestions() {
        const value = selectedQuestionIdsInput?.value?.trim();
        const errorId = "feedback_selectedQuestionsError";

        // Remove existing error if any
        removeSelectedQuestionsError();

        if (!value) {
            const error = document.createElement("span");
            error.id = errorId;
            error.className = "error-message";
            error.textContent = 'At least one question must be selected.';
            error.style.color = "red";
            error.style.fontSize = "12px";
            error.style.marginLeft = "10px";

            const addQuestionBtn = document.getElementById("feedback_addFeedbackQuestionBtn");
            if (addQuestionBtn && addQuestionBtn.parentNode) {
                addQuestionBtn.parentNode.appendChild(error);
            }
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

    // Error display functions (matching survey validation)
    function showError(input, message) {
        if (!input) return;
        
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
        if (!input) return;
        
        let errorElement = input.parentNode.querySelector(".error-message");
        if (errorElement) errorElement.textContent = "";
        input.classList.remove("is-invalid");
    }

    // Form validation function
    function validateFeedbackForm() {
        let isValid = true;

        // Validate title
        if (!validateFeedbackField(feedbackTitle)) {
            isValid = false;
        }

        // Validate tags
        if (!validateTagInput()) {
            isValid = false;
        }

        // Validate selected questions
        if (!validateSelectedQuestions()) {
            isValid = false;
        }

        return isValid;
    }

    if (feedbackForm) {
        feedbackForm.addEventListener("submit", function (e) {
            e.preventDefault();
            console.log("Feedback form submit event triggered");
            
            const isValid = validateFeedbackForm();
            if (isValid) {
                console.log("Feedback form valid. Submitting...");
                // Form is valid, submit it
                feedbackForm.submit();
            } else {
                console.log("Feedback form validation failed.");
            }
        });
    }

    window.showSelectedQuestionsGrid = showSelectedQuestionsGrid;
});
