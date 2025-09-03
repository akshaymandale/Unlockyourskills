/**
 * Survey Form JavaScript
 * Handles survey form submission and validation
 */



document.addEventListener('DOMContentLoaded', function() {
    const surveyForm = document.getElementById('surveyForm');
    const submitBtn = document.getElementById('submitSurveyBtn');
    
    let loadingModal;
    try {
        const loadingModalElement = document.getElementById('loadingModal');
        loadingModal = new bootstrap.Modal(loadingModalElement);
    } catch (error) {
        loadingModal = null;
    }

    // Initialize form validation
    initializeFormValidation();

    // Handle form submission
    if (surveyForm) {
        surveyForm.addEventListener('submit', handleFormSubmission);
        
        // Also add click listener to submit button as backup
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                handleFormSubmission(e);
            });
        }
    }

    // Handle file uploads
    handleFileUploads();

    // Handle rating interactions
    handleRatingInteractions();

    /**
     * Initialize form validation
     */
    function initializeFormValidation() {
        const requiredFields = surveyForm.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            field.addEventListener('blur', validateField);
            field.addEventListener('change', validateField);
        });
    }

    /**
     * Validate individual field
     */
    function validateField(event) {
        const field = event.target;
        const questionItem = field.closest('.question-item');
        
        // Remove existing validation classes
        field.classList.remove('is-valid', 'is-invalid');
        questionItem.classList.remove('has-error');
        
        // Validate field
        if (field.hasAttribute('required') && !field.value.trim()) {
            field.classList.add('is-invalid');
            questionItem.classList.add('has-error');
            return false;
        } else {
            field.classList.add('is-valid');
            return true;
        }
    }

    /**
     * Validate entire form
     */
    function validateForm() {
        let isValid = true;
        const requiredFields = surveyForm.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!validateField({ target: field })) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    /**
     * Handle form submission
     */
    async function handleFormSubmission(event) {

        
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        
        // Validate form
        if (!validateForm()) {

            showToast('Please fill in all required fields.', 'error');
            scrollToFirstError();
            return;
        }
        


        // Show loading modal
        if (loadingModal) {

            loadingModal.show();
        } else {

        }
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitting...';

        try {
            // Prepare form data
            const formData = new FormData(surveyForm);
            
            // Convert FormData to regular object for JSON submission
            const responseData = {
                course_id: formData.get('course_id'),
                survey_package_id: formData.get('survey_package_id'),
                responses: {}
            };

            // Process responses
            const responses = {};
            
            // Get all form data entries
            const formEntries = Array.from(formData.entries());
            
            for (const [key, value] of formEntries) {
                if (key.startsWith('responses[')) {
                    const match = key.match(/responses\[(\d+)\]\[(\w+)\](?:\[\])?/);
                    if (match) {
                        const questionId = match[1];
                        const fieldType = match[2];
                        
                        if (!responses[questionId]) {
                            responses[questionId] = {};
                        }
                        
                        if (fieldType === 'value') {
                            // Handle checkbox arrays (multiple values for same question)
                            if (responses[questionId].value === undefined) {
                                responses[questionId].value = [];
                            }
                            if (Array.isArray(responses[questionId].value)) {
                                responses[questionId].value.push(value);
                            } else {
                                // Convert single value to array if we encounter multiple values
                                responses[questionId].value = [responses[questionId].value, value];
                            }
                        } else if (fieldType === 'type') {
                            responses[questionId].type = value;
                        }
                    }
                }
            }
            
            // Convert single values back to strings for non-checkbox questions
            for (const questionId in responses) {
                if (Array.isArray(responses[questionId].value) && responses[questionId].value.length === 1) {
                    responses[questionId].value = responses[questionId].value[0];
                }
            }

            responseData.responses = responses;

            // Submit survey

            
            const response = await fetch('/unlockyourskills/survey/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(responseData),
                credentials: 'same-origin' // Include cookies for session
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                showToast(result.message || 'Survey submitted successfully!', 'success');
                
                // Redirect to course details after a short delay
                setTimeout(() => {
                    const courseId = formData.get('course_id');
                    window.location.href = `/unlockyourskills/my-courses/details/${courseId}`;
                }, 2000);
            } else {
                showToast(result.message || 'Failed to submit survey. Please try again.', 'error');
            }

        } catch (error) {

            showToast('An error occurred while submitting the survey. Please try again.', 'error');
        } finally {
            // Hide loading modal
            if (loadingModal) {
                loadingModal.hide();
            }
            
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Submit Survey';
        }
    }

    /**
     * Handle file uploads
     */
    function handleFileUploads() {
        const fileInputs = surveyForm.querySelectorAll('input[type="file"]');
        
        fileInputs.forEach(input => {
            input.addEventListener('change', function(event) {
                const file = event.target.files[0];
                const questionItem = input.closest('.question-item');
                
                if (file) {
                    // Validate file size (max 10MB)
                    if (file.size > 10 * 1024 * 1024) {
                        showToast('File size must be less than 10MB.', 'error');
                        input.value = '';
                        return;
                    }
                    
                    // Validate file type
                    const allowedTypes = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'text/plain',
                        'image/jpeg',
                        'image/png',
                        'image/gif'
                    ];
                    
                    if (!allowedTypes.includes(file.type)) {
                        showToast('Invalid file type. Please select a valid file.', 'error');
                        input.value = '';
                        return;
                    }
                    
                    // Show file info
                    showFileInfo(questionItem, file);
                }
            });
        });
    }

    /**
     * Show file information
     */
    function showFileInfo(questionItem, file) {
        let fileInfo = questionItem.querySelector('.file-info');
        
        if (!fileInfo) {
            fileInfo = document.createElement('div');
            fileInfo.className = 'file-info mt-2';
            questionItem.querySelector('.file-response').appendChild(fileInfo);
        }
        
        fileInfo.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-file me-1"></i>
                <strong>Selected:</strong> ${file.name} (${formatFileSize(file.size)})
            </div>
        `;
    }

    /**
     * Handle rating interactions
     */
    function handleRatingInteractions() {
        const ratingInputs = surveyForm.querySelectorAll('input[type="radio"][name*="rating"]');
        
        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                const questionItem = input.closest('.question-item');
                const ratingOptions = questionItem.querySelectorAll('input[type="radio"]');
                
                // Update visual feedback
                ratingOptions.forEach(option => {
                    const label = option.nextElementSibling;
                    if (option.checked) {
                        label.classList.add('text-primary', 'fw-bold');
                    } else {
                        label.classList.remove('text-primary', 'fw-bold');
                    }
                });
            });
        });
    }

    /**
     * Scroll to first error
     */
    function scrollToFirstError() {
        const firstError = surveyForm.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    }

    /**
     * Format file size
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        // Add to toast container
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        toastContainer.appendChild(toast);
        
        // Initialize and show toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove toast element after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    /**
     * Auto-save functionality (optional)
     */
    function initializeAutoSave() {
        const formFields = surveyForm.querySelectorAll('input, textarea, select');
        
        formFields.forEach(field => {
            field.addEventListener('change', debounce(autoSave, 2000));
        });
    }

    /**
     * Auto-save form data
     */
    function autoSave() {
        const formData = new FormData(surveyForm);
        const data = Object.fromEntries(formData.entries());
        
        // Save to localStorage
        localStorage.setItem('survey_autosave', JSON.stringify(data));
        
        // Show auto-save indicator
        showAutoSaveIndicator();
    }

    /**
     * Show auto-save indicator
     */
    function showAutoSaveIndicator() {
        let indicator = document.querySelector('.autosave-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'autosave-indicator position-fixed bottom-0 end-0 m-3';
            document.body.appendChild(indicator);
        }
        
        indicator.innerHTML = `
            <div class="alert alert-light border shadow-sm">
                <i class="fas fa-save me-1"></i>
                <small>Auto-saved</small>
            </div>
        `;
        
        // Hide after 3 seconds
        setTimeout(() => {
            indicator.remove();
        }, 3000);
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialize auto-save if needed
    // initializeAutoSave();
});
