/**
 * Assignment Package Management
 * Handles assignment package CRUD operations
 */

class AssignmentPackageManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupTagSystem();
    }

    setupEventListeners() {
        // Add assignment button
        const addAssignmentBtn = document.getElementById('addAssignmentBtn');
        if (addAssignmentBtn) {
            addAssignmentBtn.addEventListener('click', () => this.clearForm());
        }

        // Form submission
        const assignmentForm = document.getElementById('assignmentForm');
        if (assignmentForm) {
            assignmentForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // Clear form button
        const clearFormBtn = document.getElementById('clearFormassignment');
        if (clearFormBtn) {
            clearFormBtn.addEventListener('click', () => this.clearForm());
        }

        // File input change
        const assignmentFileInput = document.getElementById('assignmentFileassignment');
        if (assignmentFileInput) {
            assignmentFileInput.addEventListener('change', (e) => this.handleFileChange(e));
        }

        // Edit assignment links
        document.addEventListener('click', (e) => {
            if (e.target.closest('.edit-assignment')) {
                e.preventDefault();
                const assignmentData = JSON.parse(e.target.closest('.edit-assignment').dataset.assignment);
                this.populateForm(assignmentData);
            }
        });

        // Delete assignment links (only if VLR confirmations system is not available)
        document.addEventListener('click', (e) => {
            if (e.target.closest('.delete-assignment')) {
                // Check if VLR confirmations system is available
                if (typeof window.vlrConfirmationsInstance !== 'undefined') {
                    // Let VLR confirmations handle it
                    return;
                }
                
                e.preventDefault();
                const assignmentId = e.target.closest('.delete-assignment').dataset.id;
                const assignmentTitle = e.target.closest('.delete-assignment').dataset.title;
                this.deleteAssignment(assignmentId, assignmentTitle);
            }
        });

        // Allow Late Submission radio change
        const allowLateYes = document.getElementById('allowLateYesassignment');
        const allowLateNo = document.getElementById('allowLateNoassignment');
        const penaltyField = document.getElementById('lateSubmissionPenaltyassignment').closest('.col-md-3');
        const updatePenaltyVisibility = () => {
            if (allowLateYes && allowLateYes.checked) {
                penaltyField.style.display = 'none';
                document.getElementById('lateSubmissionPenaltyassignment').value = '';
            } else {
                penaltyField.style.display = '';
            }
        };
        if (allowLateYes && allowLateNo && penaltyField) {
            allowLateYes.addEventListener('change', updatePenaltyVisibility);
            allowLateNo.addEventListener('change', updatePenaltyVisibility);
            // Initial state
            updatePenaltyVisibility();
        }
    }

    setupTagSystem() {
        const tagInput = document.getElementById('tagInputassignment');
        const tagDisplay = document.getElementById('tagDisplayassignment');
        const tagList = document.getElementById('tagListassignment');
        
        if (!tagInput || !tagDisplay || !tagList) return;

        let tags = [];

        tagInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const tag = tagInput.value.trim();
                if (tag && !tags.includes(tag)) {
                    tags.push(tag);
                    this.updateTagDisplay();
                }
                tagInput.value = '';
            }
        });

        this.updateTagDisplay = () => {
            tagDisplay.innerHTML = tags.map(tag => 
                `<span class="tag">${tag} <i class="fas fa-times" onclick="assignmentManager.removeTag('${tag}')"></i></span>`
            ).join('');
            tagList.value = tags.join(',');
        };

        this.removeTag = (tagToRemove) => {
            tags = tags.filter(tag => tag !== tagToRemove);
            this.updateTagDisplay();
        };
    }

    handleFormSubmit(e) {
        const formData = new FormData(e.target);
        const assignmentId = formData.get('assignment_idassignment');
        
        // Show loading state
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Saving...';
        submitBtn.disabled = true;

        fetch('/unlockyourskills/vlr/assignment', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showToast(data.message, 'success');
                this.clearForm();
                // Reload the page to show updated data
                setTimeout(() => window.location.reload(), 1000);
            } else {
                this.showToast(data.message || 'Failed to save assignment package.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showToast('An error occurred while saving the assignment package.', 'error');
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        });
    }

    handleFileChange(e) {
        const file = e.target.files[0];
        const displayDiv = document.getElementById('existingAssignmentDisplayassignment');
        
        if (file) {
            displayDiv.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-file"></i> Selected file: ${file.name} (${this.formatFileSize(file.size)})
                </div>
            `;
        } else {
            displayDiv.innerHTML = '';
        }
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    populateForm(assignmentData) {
        // Populate form fields
        document.getElementById('assignment_idassignment').value = assignmentData.id || '';
        document.getElementById('assignment_titleassignment').value = assignmentData.title || '';
        document.getElementById('descriptionassignment').value = assignmentData.description || '';
        document.getElementById('instructionsassignment').value = assignmentData.instructions || '';
        document.getElementById('requirementsassignment').value = assignmentData.requirements || '';
        document.getElementById('learningObjectivesassignment').value = assignmentData.learning_objectives || '';
        document.getElementById('prerequisitesassignment').value = assignmentData.prerequisites || '';
        document.getElementById('versionassignment').value = assignmentData.version || '';
        document.getElementById('timeLimitassignment').value = assignmentData.time_limit || '';
        document.getElementById('estimatedDurationassignment').value = assignmentData.estimated_duration || '';
        document.getElementById('maxAttemptsassignment').value = assignmentData.max_attempts || '1';
        document.getElementById('passingScoreassignment').value = assignmentData.passing_score || '';
        document.getElementById('lateSubmissionPenaltyassignment').value = assignmentData.late_submission_penalty || '0';

        // Set dropdown values
        if (assignmentData.assignment_type) {
            document.getElementById('assignmentTypeassignment').value = assignmentData.assignment_type;
        }
        if (assignmentData.difficulty_level) {
            document.getElementById('difficultyLevelassignment').value = assignmentData.difficulty_level;
        }
        if (assignmentData.submission_format) {
            document.getElementById('submissionFormatassignment').value = assignmentData.submission_format;
        }
        if (assignmentData.language) {
            document.getElementById('languageassignment').value = assignmentData.language;
        }

        // Set radio button values
        if (assignmentData.mobile_support) {
            const radioBtn = document.getElementById(`mobile${assignmentData.mobile_support}assignment`);
            if (radioBtn) radioBtn.checked = true;
        }
        if (assignmentData.allow_late_submission) {
            const radioBtn = document.getElementById(`allowLate${assignmentData.allow_late_submission}assignment`);
            if (radioBtn) radioBtn.checked = true;
        }

        // Populate tags
        if (assignmentData.tags) {
            const tags = assignmentData.tags.split(',').map(tag => tag.trim()).filter(tag => tag);
            const tagInput = document.getElementById('tagInputassignment');
            const tagDisplay = document.getElementById('tagDisplayassignment');
            const tagList = document.getElementById('tagListassignment');
            
            tagDisplay.innerHTML = tags.map(tag => 
                `<span class="tag">${tag} <i class="fas fa-times" onclick="assignmentManager.removeTag('${tag}')"></i></span>`
            ).join('');
            tagList.value = tags.join(',');
        }

        // Show existing file if available
        if (assignmentData.assignment_file) {
            document.getElementById('existing_assignmentassignment').value = assignmentData.assignment_file;
            document.getElementById('existingAssignmentDisplayassignment').innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-file"></i> Current file: ${assignmentData.assignment_file}
                </div>
            `;
        }

        // Update modal title
        document.getElementById('assignmentModalLabel').textContent = 'Edit Assignment Package';
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('assignmentModal'));
        modal.show();
    }

    clearForm() {
        const form = document.getElementById('assignmentForm');
        if (form) {
            form.reset();
        }

        // Clear hidden fields
        document.getElementById('assignment_idassignment').value = '';
        document.getElementById('existing_assignmentassignment').value = '';
        document.getElementById('tagListassignment').value = '';

        // Clear tag display
        const tagDisplay = document.getElementById('tagDisplayassignment');
        if (tagDisplay) {
            tagDisplay.innerHTML = '';
        }

        // Clear file display
        const fileDisplay = document.getElementById('existingAssignmentDisplayassignment');
        if (fileDisplay) {
            fileDisplay.innerHTML = '';
        }

        // Reset modal title
        document.getElementById('assignmentModalLabel').textContent = 'Add Assignment Package';

        // Clear validation errors if validation is available
        if (window.assignmentValidation) {
            window.assignmentValidation.clearAllValidation();
        }
    }

    deleteAssignment(assignmentId, assignmentTitle) {
        // Use the VLR confirmation system if available
        if (typeof window.confirmDelete === 'function') {
            const itemName = `assignment "${assignmentTitle}"`;
            window.confirmDelete(itemName, () => {
                this.performAssignmentDelete(assignmentId);
            });
        } else {
            // Fallback to browser confirm
            if (confirm(`Are you sure you want to delete the assignment "${assignmentTitle}"?`)) {
                this.performAssignmentDelete(assignmentId);
            }
        }
    }

    performAssignmentDelete(assignmentId) {
        // Create a form to submit DELETE request (same as VLR confirmations)
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/unlockyourskills/vlr/assignment/${assignmentId}`;
        
        // Add method override for DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        // Add CSRF token if available
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }
        
        // Submit the form
        document.body.appendChild(form);
        form.submit();
    }

    showToast(message, type = 'info') {
        // Use existing toast system if available
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            // Fallback to alert
            alert(message);
        }
    }
}

// Initialize assignment package manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.assignmentManager = new AssignmentPackageManager();
}); 