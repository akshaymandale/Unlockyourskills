/**
 * Assignment Submission JavaScript
 * Handles assignment submission form functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('assignmentSubmissionForm');
    if (!form) return;
    
    // Handle submission type changes (both radio and checkbox)
    const submissionTypeInputs = document.querySelectorAll('input[name="submission_type"], input[name="submission_type[]"]');
    const sections = {
        'file_upload': document.getElementById('file_upload_section'),
        'text_entry': document.getElementById('text_entry_section'),
        'url_submission': document.getElementById('url_submission_section')
    };
    
    function updateSubmissionSections() {
        // Check if we're dealing with checkboxes (mixed submission)
        const isCheckboxMode = submissionTypeInputs.length > 0 && submissionTypeInputs[0].type === 'checkbox';
        
        if (isCheckboxMode) {
            // For checkboxes, show all checked sections
            submissionTypeInputs.forEach(input => {
                if (input.checked && sections[input.value]) {
                    sections[input.value].style.display = 'block';
                } else if (sections[input.value]) {
                    sections[input.value].style.display = 'none';
                }
            });
        } else {
            // For radio buttons, show only the selected section
            const checkedInput = document.querySelector('input[name="submission_type"]:checked');
            if (checkedInput && sections[checkedInput.value]) {
                // Hide all sections first
                Object.values(sections).forEach(section => {
                    if (section) section.style.display = 'none';
                });
                // Show selected section
                sections[checkedInput.value].style.display = 'block';
            }
        }
    }
    
    // Initialize with current selection
    updateSubmissionSections();
    
    // Handle input changes
    submissionTypeInputs.forEach(input => {
        input.addEventListener('change', function() {
            updateSubmissionSections();
        });
    });
    
    // Form validation
    function validateForm() {
        // Check if we're dealing with checkboxes (mixed submission)
        const isCheckboxMode = submissionTypeInputs.length > 0 && submissionTypeInputs[0].type === 'checkbox';
        
        let selectedTypes = [];
        if (isCheckboxMode) {
            // For checkboxes, get all checked inputs
            submissionTypeInputs.forEach(input => {
                if (input.checked) {
                    selectedTypes.push(input.value);
                }
            });
        } else {
            // For radio buttons, get the checked input
            const submissionType = document.querySelector('input[name="submission_type"]:checked');
            if (submissionType) {
                selectedTypes.push(submissionType.value);
            }
        }
        
        if (selectedTypes.length === 0) {
            showToast('Please select a submission type.', 'error');
            return false;
        }
        
        // Validate based on selected submission types
        for (const type of selectedTypes) {
            if (type === 'file_upload') {
                const fileInput = document.getElementById('submission_file');
                if (!fileInput.files || fileInput.files.length === 0) {
                    showToast('Please select a file to upload.', 'error');
                    fileInput.focus();
                    return false;
                }
                
                // Check file size (50MB max)
                const file = fileInput.files[0];
                if (file.size > 50 * 1024 * 1024) {
                    showToast('File size cannot exceed 50MB.', 'error');
                    return false;
                }
                
                // Check file type
                const allowedTypes = ['pdf', 'doc', 'docx', 'txt', 'rtf', 'jpg', 'jpeg', 'png', 'gif'];
                const fileExtension = file.name.split('.').pop().toLowerCase();
                if (!allowedTypes.includes(fileExtension)) {
                    showToast('Invalid file type. Allowed types: ' + allowedTypes.join(', '), 'error');
                    return false;
                }
            } else if (type === 'text_entry') {
                const textArea = document.getElementById('submission_text');
                if (!textArea.value.trim()) {
                    showToast('Please enter text content for your submission.', 'error');
                    textArea.focus();
                    return false;
                }
            } else if (type === 'url_submission') {
                const urlInput = document.getElementById('submission_url');
                if (!urlInput.value.trim()) {
                    showToast('Please enter a URL for your submission.', 'error');
                    urlInput.focus();
                    return false;
                }
                
                // Basic URL validation
                try {
                    new URL(urlInput.value);
                } catch (e) {
                    showToast('Please enter a valid URL.', 'error');
                    urlInput.focus();
                    return false;
                }
            }
        }
        
        return true;
    }
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        const submitBtn = document.getElementById('submitAssignmentBtn');
        const originalText = submitBtn.innerHTML;
        
        // Prevent double submission by checking if already submitting
        if (submitBtn.disabled) {
            return;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Submitting...';
        
        // Collect form data
        const formData = new FormData(form);
        
        // Submit assignment
        fetch('/Unlockyourskills/assignment-submission/submit', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Update assignment button to show "View Submitted Assignment"
                const assignmentId = formData.get('assignment_package_id');
                const courseId = formData.get('course_id');
                if (window.updateAssignmentButtonAfterSubmission) {
                    window.updateAssignmentButtonAfterSubmission(assignmentId, courseId);
                }
                
                showToast(data.message || 'Assignment submitted successfully!', 'success');
                
                // Refresh the page to show updated button states after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showToast(data.message || 'Failed to submit assignment. Please try again.', 'error');
            }
        })
        .catch(error => {
            showToast('An error occurred while submitting the assignment. Please try again.', 'error');
        })
        .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
    
    // File input change handler
    const fileInput = document.getElementById('submission_file');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Show file info
                const fileInfo = document.createElement('div');
                fileInfo.className = 'mt-2 text-muted';
                fileInfo.innerHTML = `
                    <small>
                        <i class="fas fa-file me-1"></i>
                        Selected: ${file.name} (${formatFileSize(file.size)})
                    </small>
                `;
                
                // Remove existing file info
                const existingInfo = this.parentNode.querySelector('.file-info');
                if (existingInfo) {
                    existingInfo.remove();
                }
                
                fileInfo.className += ' file-info';
                this.parentNode.appendChild(fileInfo);
            }
        });
    }
    
    // Helper function to format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Helper function to show toast notifications
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
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        // Add to toast container
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        toastContainer.appendChild(toast);
        
        // Initialize and show toast
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 5000
        });
        bsToast.show();
        
        // Remove toast element after it's hidden
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    }
});
