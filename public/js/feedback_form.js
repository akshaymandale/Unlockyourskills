document.addEventListener('DOMContentLoaded', function() {
    const feedbackForm = document.getElementById('feedbackForm');
    const submitButton = document.getElementById('submitFeedback');
    const saveDraftButton = document.getElementById('saveDraft');
    const resubmitButton = document.getElementById('resubmitFeedback');
    
    // Initialize rating stars functionality
    initializeRatingStars();
    
    // Form submission handler
    if (feedbackForm) {
        feedbackForm.addEventListener('submit', handleFormSubmission);
    }
    
    // Save draft handler
    if (saveDraftButton) {
        saveDraftButton.addEventListener('click', saveDraft);
    }
    
    // Resubmit feedback handler
    if (resubmitButton) {
        resubmitButton.addEventListener('click', resubmitFeedback);
    }
    
    // Auto-save draft every 30 seconds
    setInterval(autoSaveDraft, 30000);
    
    /**
     * Initialize rating stars functionality
     */
    function initializeRatingStars() {
        const ratingContainers = document.querySelectorAll('.rating-response');
        
        ratingContainers.forEach(container => {
            const stars = container.querySelectorAll('.rating-input');
            const labels = container.querySelectorAll('.rating-label');
            
            stars.forEach((star, index) => {
                star.addEventListener('change', function() {
                    // Remove active class from all labels
                    labels.forEach(label => label.classList.remove('active'));
                    
                    // Add active class to selected and previous stars
                    for (let i = 0; i <= index; i++) {
                        labels[i].classList.add('active');
                    }
                });
                
                // Hover effects
                labels[index].addEventListener('mouseenter', function() {
                    // Highlight stars up to current hover
                    labels.forEach((label, i) => {
                        if (i <= index) {
                            label.classList.add('hover');
                        } else {
                            label.classList.remove('hover');
                        }
                    });
                });
                
                labels[index].addEventListener('mouseleave', function() {
                    // Remove hover effects
                    labels.forEach(label => label.classList.remove('hover'));
                });
            });
        });
    }
    
    /**
     * Handle form submission
     */
    function handleFormSubmission(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        // Show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
        
        // Collect form data
        const formData = new FormData(feedbackForm);
        
        // Send AJAX request
        fetch(window.location.origin + '/unlockyourskills/feedback-response/submit', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessModal(data.message);
                // Clear any saved drafts
                localStorage.removeItem('feedbackDraft_' + getCourseId());
            } else {
                showErrorModal(data.message || 'An error occurred while submitting feedback.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorModal('Network error. Please try again.');
        })
        .finally(() => {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit Feedback';
        });
    }
    
    /**
     * Validate form before submission
     */
    function validateForm() {
        const questions = document.querySelectorAll('.feedback-question');
        let isValid = true;
        let firstInvalidQuestion = null;
        
        questions.forEach((question, index) => {
            const questionId = question.dataset.questionId;
            const responseType = question.querySelector('input[name^="responses"][name$="[type]"]')?.value;
            const responseValue = getResponseValue(question, responseType);
            
            // Check if response is required and provided
            if (isResponseRequired(question) && !responseValue) {
                markQuestionAsInvalid(question, 'This question requires a response.');
                if (!firstInvalidQuestion) {
                    firstInvalidQuestion = question;
                }
                isValid = false;
            } else {
                markQuestionAsValid(question);
            }
        });
        
        // Scroll to first invalid question if any
        if (firstInvalidQuestion) {
            firstInvalidQuestion.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        return isValid;
    }
    
    /**
     * Get response value based on question type
     */
    function getResponseValue(question, responseType) {
        switch (responseType) {
            case 'rating':
                const ratingInput = question.querySelector('input[name^="responses"][name$="[value]"]:checked');
                return ratingInput ? ratingInput.value : null;
                
            case 'choice':
                const choiceInput = question.querySelector('input[name^="responses"][name$="[value]"]:checked');
                return choiceInput ? choiceInput.value : null;
                
            case 'text':
                const textInput = question.querySelector('input[name^="responses"][name$="[value]"], textarea[name^="responses"][name$="[value]"]');
                return textInput ? textInput.value.trim() : null;
                
            case 'file':
                const fileInput = question.querySelector('input[type="file"]');
                return fileInput && fileInput.files.length > 0 ? fileInput.files[0].name : null;
                
            default:
                return null;
        }
    }
    
    /**
     * Check if a question requires a response
     */
    function isResponseRequired(question) {
        // For now, all questions are required
        // This can be enhanced based on question metadata
        return true;
    }
    
    /**
     * Mark question as invalid
     */
    function markQuestionAsInvalid(question, message) {
        question.classList.add('is-invalid');
        
        // Remove existing error message
        const existingError = question.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }
        
        // Add error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback d-block';
        errorDiv.textContent = message;
        question.appendChild(errorDiv);
    }
    
    /**
     * Mark question as valid
     */
    function markQuestionAsValid(question) {
        question.classList.remove('is-invalid');
        const existingError = question.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }
    }
    
    /**
     * Save draft functionality
     */
    function saveDraft() {
        const formData = collectFormData();
        const courseId = getCourseId();
        
        localStorage.setItem('feedbackDraft_' + courseId, JSON.stringify({
            data: formData,
            timestamp: new Date().toISOString()
        }));
        
        // Show success message
        showToast('Draft saved successfully!', 'success');
    }
    
    /**
     * Auto-save draft
     */
    function autoSaveDraft() {
        if (document.querySelector('.feedback-question input:focus, .feedback-question textarea:focus')) {
            return; // Don't auto-save while user is typing
        }
        
        const formData = collectFormData();
        const courseId = getCourseId();
        
        localStorage.setItem('feedbackDraft_' + courseId, JSON.stringify({
            data: formData,
            timestamp: new Date().toISOString()
        }));
    }
    
    /**
     * Load draft if available
     */
    function loadDraft() {
        const courseId = getCourseId();
        const draft = localStorage.getItem('feedbackDraft_' + courseId);
        
        if (draft) {
            try {
                const draftData = JSON.parse(draft);
                const timestamp = new Date(draftData.timestamp);
                const now = new Date();
                const hoursDiff = (now - timestamp) / (1000 * 60 * 60);
                
                // Only load draft if it's less than 24 hours old
                if (hoursDiff < 24) {
                    restoreFormData(draftData.data);
                    showToast('Draft loaded from ' + timestamp.toLocaleString(), 'info');
                } else {
                    // Remove old draft
                    localStorage.removeItem('feedbackDraft_' + courseId);
                }
            } catch (error) {
                console.error('Error loading draft:', error);
                localStorage.removeItem('feedbackDraft_' + courseId);
            }
        }
    }
    
    /**
     * Collect form data for draft
     */
    function collectFormData() {
        const formData = {};
        const questions = document.querySelectorAll('.feedback-question');
        
        questions.forEach(question => {
            const questionId = question.dataset.questionId;
            const responseType = question.querySelector('input[name^="responses"][name$="[type]"]')?.value;
            const responseValue = getResponseValue(question, responseType);
            
            if (responseValue) {
                formData[questionId] = {
                    type: responseType,
                    value: responseValue
                };
            }
        });
        
        return formData;
    }
    
    /**
     * Restore form data from draft
     */
    function restoreFormData(draftData) {
        Object.keys(draftData).forEach(questionId => {
            const question = document.querySelector(`[data-question-id="${questionId}"]`);
            if (!question) return;
            
            const responseData = draftData[questionId];
            const responseType = responseData.type;
            const responseValue = responseData.value;
            
            switch (responseType) {
                case 'rating':
                    const ratingInput = question.querySelector(`input[name="responses[${questionId}][value]"][value="${responseValue}"]`);
                    if (ratingInput) {
                        ratingInput.checked = true;
                        // Trigger change event to update star display
                        ratingInput.dispatchEvent(new Event('change'));
                    }
                    break;
                    
                case 'choice':
                    const choiceInput = question.querySelector(`input[name="responses[${questionId}][value]"][value="${responseValue}"]`);
                    if (choiceInput) {
                        choiceInput.checked = true;
                    }
                    break;
                    
                case 'text':
                    const textInput = question.querySelector(`input[name="responses[${questionId}][value]"], textarea[name="responses[${questionId}][value]"]`);
                    if (textInput) {
                        textInput.value = responseValue;
                    }
                    break;
            }
        });
    }
    
    /**
     * Resubmit feedback (delete old responses and show form)
     */
    function resubmitFeedback() {
        if (confirm('Are you sure you want to submit new feedback? This will replace your previous submission.')) {
            const courseId = getCourseId();
            const feedbackPackageId = getFeedbackPackageId();
            
            // Send delete request
            fetch(window.location.origin + '/unlockyourskills/feedback-response/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `course_id=${encodeURIComponent(courseId)}&feedback_package_id=${encodeURIComponent(feedbackPackageId)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload page to show fresh form
                    window.location.reload();
                } else {
                    showErrorModal(data.message || 'Failed to reset feedback form.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('Network error. Please try again.');
            });
        }
    }
    
    /**
     * Get course ID from form
     */
    function getCourseId() {
        const input = document.querySelector('input[name="course_id"]');
        return input ? input.value : null;
    }
    
    /**
     * Get feedback package ID from form
     */
    function getFeedbackPackageId() {
        const input = document.querySelector('input[name="feedback_package_id"]');
        return input ? input.value : null;
    }
    
    /**
     * Show success modal
     */
    function showSuccessModal(message) {
        const modal = new bootstrap.Modal(document.getElementById('successModal'));
        modal.show();
    }
    
    /**
     * Show error modal
     */
    function showErrorModal(message) {
        const modal = new bootstrap.Modal(document.getElementById('errorModal'));
        document.getElementById('errorMessage').textContent = message;
        modal.show();
    }
    
    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        // Add to page
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        container.appendChild(toast);
        document.body.appendChild(container);
        
        // Show toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove container after toast is hidden
        toast.addEventListener('hidden.bs.toast', () => {
            document.body.removeChild(container);
        });
    }
    
    // Load draft when page loads
    loadDraft();
    
    // Add form change listeners for auto-save
    const formInputs = feedbackForm.querySelectorAll('input, textarea, select');
    formInputs.forEach(input => {
        input.addEventListener('change', () => {
            // Debounce auto-save
            clearTimeout(window.autoSaveTimeout);
            window.autoSaveTimeout = setTimeout(autoSaveDraft, 1000);
        });
    });
});
