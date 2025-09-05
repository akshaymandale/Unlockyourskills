// Course Creation Modal Validation
// This file handles validation for the course creation modal

(function(global) {
    // Global variables for course creation
    global.courseCreationSubmitUrl = global.courseCreationSubmitUrl || null;

    function validateField(field) {
        let isValid = true;
        const value = (field.value || '').trim();
        const required = field.getAttribute('data-required') === '1';
        
        const showError = (f, message) => {
            f.classList.add('is-invalid');
            f.classList.remove('is-valid');
            
            // Remove existing error message
            const existingFeedback = f.parentNode.querySelector('.invalid-feedback');
            if (existingFeedback) {
                existingFeedback.remove();
            }
            
            // Add new error message
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = message;
            f.parentNode.appendChild(feedback);
        };
        
        const hideError = (f) => {
            f.classList.remove('is-invalid');
            f.classList.add('is-valid');
            
            // Remove error message
            const feedback = f.parentNode.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.remove();
            }
        };

        // Hide previous error before validating
        hideError(field);

        // Main fields validation
        switch (field.name) {
            case 'name':
                if (!value) {
                    showError(field, 'Course title is required.');
                    isValid = false;
                } else if (value.length < 3) {
                    showError(field, 'Course title must be at least 3 characters long.');
                    isValid = false;
                }
                break;
                
            case 'category_id':
                if (!value) {
                    showError(field, 'Category is required.');
                    isValid = false;
                }
                break;
                
            case 'subcategory_id':
                if (!value) {
                    showError(field, 'Subcategory is required.');
                    isValid = false;
                }
                break;
                
            case 'course_type':
                if (!value) {
                    showError(field, 'Course type is required.');
                    isValid = false;
                }
                break;
                
            case 'difficulty_level':
                if (!value) {
                    showError(field, 'Difficulty level is required.');
                    isValid = false;
                }
                break;
                
            case 'short_description':
                if (value && value.length > 500) {
                    showError(field, 'Short description must be less than 500 characters.');
                    isValid = false;
                }
                break;
                
            case 'description':
                if (value && value.length > 2000) {
                    showError(field, 'Description must be less than 2000 characters.');
                    isValid = false;
                }
                break;
                
            case 'target_audience':
                if (value && value.length > 1000) {
                    showError(field, 'Target audience must be less than 1000 characters.');
                    isValid = false;
                }
                break;
                
            default:
                // Custom fields validation
                if (required) {
                    if ((field.type === 'checkbox' || field.type === 'radio')) {
                        // For checkbox/radio, check if any in the group is checked
                        const group = document.getElementsByName(field.name);
                        let checked = false;
                        for (let i = 0; i < group.length; i++) {
                            if (group[i].checked) checked = true;
                        }
                        if (!checked) {
                            showError(field, 'This field is required.');
                            isValid = false;
                        }
                    } else if (!value) {
                        showError(field, 'This field is required.');
                        isValid = false;
                    }
                }
                break;
        }
        
        return isValid;
    }

    function showFieldError(field, message) {
        field.classList.add('is-invalid');
        field.classList.remove('is-valid');
        const existingFeedback = field.parentNode.querySelector('.invalid-feedback');
        if (existingFeedback) {
            existingFeedback.remove();
        }
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        field.parentNode.appendChild(feedback);
    }

    function clearFieldError(field) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    }

    function validateDurationMinutes(field) {
        const value = parseInt(field.value, 10);
        if (!isNaN(value) && value > 60) {
            showFieldError(field, 'Duration (Minutes) cannot be more than 60.');
            return false;
        }
        clearFieldError(field);
        return true;
    }

    function validateCourseCreationForm() {
        let isFormValid = true;
        
        // Validate main required fields
        const requiredFields = [
            'name', 'category_id', 'subcategory_id', 'course_type', 'difficulty_level'
        ];
        
        requiredFields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field && !validateField(field)) {
                isFormValid = false;
            }
        });
        
        // Validate custom fields with data-required="1"
        const customFields = document.querySelectorAll('#courseCreationForm [data-required="1"]');
        customFields.forEach(field => {
            if (!validateField(field)) {
                isFormValid = false;
            }
        });

        // Validate Duration (Minutes) does not exceed 60
        const durationMinutesField = document.getElementById('duration_minutes');
        if (durationMinutesField && !validateDurationMinutes(durationMinutesField)) {
            isFormValid = false;
        }

        // Require at least one module
        const modulesContainer = document.getElementById('modulesContainer');
        const modulesTabButton = document.querySelector('#courseCreationTabs button[data-bs-target="#modules"]');
        if (!window.courseManagerState || !Array.isArray(window.courseManagerState.modules) || window.courseManagerState.modules.length === 0) {
            isFormValid = false;
            if (typeof showToast === 'function') {
                showToast('error', 'At least one module is required.');
            } else {
                alert('At least one module is required.');
            }
            // Highlight modules tab
            if (modulesTabButton) {
                modulesTabButton.classList.add('tab-error');
                modulesTabButton.style.borderColor = '#dc3545';
                modulesTabButton.style.borderWidth = '2px';
                modulesTabButton.style.borderStyle = 'solid';
                modulesTabButton.style.color = '#dc3545';
            }
            // Highlight modules container
            if (modulesContainer) {
                modulesContainer.style.border = '2px solid #dc3545';
                modulesContainer.style.borderRadius = '8px';
                modulesContainer.style.boxShadow = '0 0 0 2px #dc354533';
            }
        } else {
            // Remove highlight if valid
            if (modulesTabButton) {
                modulesTabButton.classList.remove('tab-error');
                modulesTabButton.style.borderColor = '';
                modulesTabButton.style.borderWidth = '';
                modulesTabButton.style.borderStyle = '';
                modulesTabButton.style.color = '';
            }
            if (modulesContainer) {
                modulesContainer.style.border = '';
                modulesContainer.style.borderRadius = '';
                modulesContainer.style.boxShadow = '';
            }
        }
        
        // Highlight tabs with errors
        updateCourseCreationTabHighlighting();
        
        return isFormValid;
    }

    function updateCourseCreationTabHighlighting() {
        // Map tab IDs to field selectors
        const tabMappings = {
            'basic-info': ['[name="name"]', '[name="category_id"]', '[name="subcategory_id"]', '[name="course_type"]', '[name="difficulty_level"]', '[name="short_description"]', '[name="description"]', '[name="target_audience"]'],
            'modules': [],
            'prerequisites': [],
            'post-requisite': [],
            'extra-details': []
        };
        
        // Add custom fields to extra-details tab
        const customFields = document.querySelectorAll('#extra-details [data-required="1"]');
        customFields.forEach(field => {
            tabMappings['extra-details'].push('#' + field.id);
        });
        
        // Highlight tabs with errors
        Object.keys(tabMappings).forEach(tabId => {
            const tabButton = document.querySelector(`#courseCreationTabs button[data-bs-target="#${tabId}"]`);
            if (!tabButton) return;
            let hasErrors = false;
            if (tabId === 'modules') {
                // Special: highlight if no modules
                if (!window.courseManagerState || !Array.isArray(window.courseManagerState.modules) || window.courseManagerState.modules.length === 0) {
                    hasErrors = true;
                }
            } else {
                tabMappings[tabId].forEach(selector => {
                    const field = document.querySelector(selector);
                    if (field && field.classList.contains('is-invalid')) {
                        hasErrors = true;
                    }
                });
            }
            if (hasErrors) {
                tabButton.classList.add('tab-error');
                tabButton.style.borderColor = '#dc3545';
                tabButton.style.borderWidth = '2px';
                tabButton.style.borderStyle = 'solid';
                tabButton.style.color = '#dc3545';
            } else {
                tabButton.classList.remove('tab-error');
                tabButton.style.borderColor = '';
                tabButton.style.borderWidth = '';
                tabButton.style.borderStyle = '';
                tabButton.style.color = '';
            }
        });
    }

    function submitCourseCreationForm() {
        console.log('[DEBUG] submitCourseCreationForm called');
        const form = document.getElementById('courseCreationForm');
        const formData = new FormData(form);
        const submitButton = document.getElementById('create_course');
        
        // Disable submit button and show loading
        submitButton.disabled = true;
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
        
        // Add state data to form data
        if (window.courseManagerState) {
            formData.append('modules', JSON.stringify(window.courseManagerState.modules || []));
            
            // Filter out title field from prerequisites since we're no longer storing it in the database
            const prerequisitesForServer = (window.courseManagerState.prerequisites || []).map(item => {
                const { title, ...itemWithoutTitle } = item;
                return itemWithoutTitle;
            });
            formData.append('prerequisites', JSON.stringify(prerequisitesForServer));
            
            // Filter out title field from post_requisites since we're no longer storing it in the database
            const postRequisitesForServer = (window.courseManagerState.post_requisites || []).map(item => {
                const { title, ...itemWithoutTitle } = item;
                return itemWithoutTitle;
            });
            formData.append('post_requisites', JSON.stringify(postRequisitesForServer));
            
            formData.append('tags', JSON.stringify(window.courseManagerState.tags || []));
            formData.append('learning_objectives', JSON.stringify(window.courseManagerState.learningObjectives || []));
        }
        
        // Determine submit URL
        const isEditMode = window.courseManagerState && window.courseManagerState.isEditMode;
        const courseId = window.courseManagerState && window.courseManagerState.courseId;
        
        const url = isEditMode 
            ? `index.php?controller=CourseCreationController&action=updateCourse&id=${courseId}`
            : 'index.php?controller=CourseCreationController&action=createCourse';
        
        fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = isEditMode ? 'Course updated successfully!' : 'Course created successfully!';
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(message, 'success');
                }
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.querySelector('.modal.show'));
                if (modal) {
                    modal.hide();
                }
                
                // Refresh course list if on course management page
                if (typeof loadCourses === 'function') {
                    loadCourses(1);
                }
            } else {
                // Check if this is a session timeout
                if (data.timeout || data.redirect) {
                    if (typeof showSimpleToast === 'function') {
                        showSimpleToast(data.message || 'Session expired. Please log in again.', 'error');
                    }
                    // Redirect to login page after a short delay
                    setTimeout(() => {
                        window.location.href = data.redirect || '/Unlockyourskills/login';
                    }, 2000);
                } else {
                    if (typeof showSimpleToast === 'function') {
                        showSimpleToast(data.message || 'An error occurred.', 'error');
                    }
                }
            }
        })
        .catch(error => {
            console.error('Submission error:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('A network error occurred. Please try again.', 'error');
            }
        })
        .finally(() => {
            // Re-enable submit button
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        });
    }

    // Initialize validation handlers for the Course Creation modal
    function initializeCourseCreationValidation() {
        const form = document.getElementById('courseCreationForm');
        if (!form) return;
        
        // Remove existing event listeners
        form.onsubmit = null;
        
        // Add submit event listener
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            if (typeof validateCourseCreationForm === 'function') {
                if (validateCourseCreationForm()) {
                    submitCourseCreationForm();
                }
            }
        });
        
        // Attach blur handler to all relevant fields
        const fields = form.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            // Remove existing blur handler
            field.removeEventListener('blur', field._courseCreationBlurHandler || (() => {}));
            
            // Add new blur handler
            const handler = function() {
                if (typeof validateField === 'function') {
                    validateField(field);
                    updateCourseCreationTabHighlighting();
                }
            };
            
            field._courseCreationBlurHandler = handler;
            field.addEventListener('blur', handler);
        });

        // Add event listeners for Duration (Minutes) validation
        const durationMinutesField = document.getElementById('duration_minutes');
        if (durationMinutesField) {
            durationMinutesField.addEventListener('blur', function() {
                validateDurationMinutes(durationMinutesField);
            });
            durationMinutesField.addEventListener('input', function() {
                clearFieldError(durationMinutesField);
            });
        }
        
        console.log('[DEBUG] Course creation validation initialized');
    }

    // Export functions to global scope
    global.validateField = validateField;
    global.validateCourseCreationForm = validateCourseCreationForm;
    global.updateCourseCreationTabHighlighting = updateCourseCreationTabHighlighting;
    global.submitCourseCreationForm = submitCourseCreationForm;
    global.initializeCourseCreationValidation = initializeCourseCreationValidation;

})(window); 