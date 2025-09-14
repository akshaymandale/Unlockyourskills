<?php
/**
 * Assignment Submission Modal
 * 
 * Modal view for assignment submission
 */

if (!isset($assignment) || !isset($course_id) || !isset($assignment_id)) {
    echo '<div class="alert alert-danger">Assignment data not found.</div>';
    return;
}
?>

<?php if ($has_submitted): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i>
        You have already submitted this assignment. Thank you for your submission!
    </div>
    
    <div class="mt-4">
        <h5>Your Submissions:</h5>
        <div class="submissions-container">
            <?php foreach ($existing_submissions as $submission): ?>
                <div class="submission-item mb-3 p-3 border rounded">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="mb-0">Submission #<?= $submission['attempt_number'] ?></h6>
                        <span class="badge bg-<?= $submission['submission_status'] === 'graded' ? 'success' : 'warning' ?>">
                            <?= ucfirst($submission['submission_status']) ?>
                        </span>
                    </div>
                    
                    <div class="submission-details">
                        <p class="mb-2"><strong>Submitted:</strong> <?= date('M j, Y g:i A', strtotime($submission['submitted_at'])) ?></p>
                        
                        <?php if ($submission['submission_type'] === 'file_upload' && $submission['submission_file']): ?>
                            <p class="mb-2"><strong>File:</strong> 
                                <a href="uploads/assignment_submissions/<?= htmlspecialchars($submission['submission_file']) ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download me-1"></i>Download File
                                </a>
                            </p>
                        <?php elseif ($submission['submission_type'] === 'text_entry' && $submission['submission_text']): ?>
                            <div class="mb-2">
                                <strong>Text Submission:</strong>
                                <div class="border rounded p-2 mt-1 bg-light">
                                    <?= nl2br(htmlspecialchars($submission['submission_text'])) ?>
                                </div>
                            </div>
                        <?php elseif ($submission['submission_type'] === 'url_submission' && $submission['submission_url']): ?>
                            <p class="mb-2"><strong>URL:</strong> 
                                <a href="<?= htmlspecialchars($submission['submission_url']) ?>" 
                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-1"></i>View URL
                                </a>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($submission['grade'] !== null): ?>
                            <p class="mb-2"><strong>Grade:</strong> 
                                <span class="badge bg-<?= $submission['grade'] >= ($assignment['passing_score'] ?? 70) ? 'success' : 'danger' ?>">
                                    <?= $submission['grade'] ?>/<?= $submission['max_grade'] ?? 100 ?>
                                </span>
                            </p>
                        <?php endif; ?>
                        
                        <?php if ($submission['feedback']): ?>
                            <div class="mb-2">
                                <strong>Feedback:</strong>
                                <div class="border rounded p-2 mt-1 bg-light">
                                    <?= nl2br(htmlspecialchars($submission['feedback'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($submission['is_late']): ?>
                            <p class="mb-0 text-warning"><i class="fas fa-clock me-1"></i>Late submission</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <!-- Assignment Submission Form -->
    
    <!-- Assignment Details - Moved to top -->
    <div class="assignment-details-header mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="assignment-detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-clock text-primary"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Time Limit</div>
                        <div class="detail-value">
                            <?= $assignment['time_limit'] ? $assignment['time_limit'] . ' minutes' : 'No limit' ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="assignment-detail-item">
                    <div class="detail-icon">
                        <i class="fas fa-redo text-success"></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Max Attempts</div>
                        <div class="detail-value"><?= $assignment['max_attempts'] ?? 1 ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Assignment Content Sections -->
    <div class="assignment-content-sections mb-4">
        <?php if ($assignment['description']): ?>
            <div class="content-section mb-4">
                <div class="content-section-header">
                    <i class="fas fa-info-circle text-info me-2"></i>
                    <h6 class="mb-0">Description</h6>
                </div>
                <div class="content-section-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($assignment['instructions']): ?>
            <div class="content-section mb-4">
                <div class="content-section-header">
                    <i class="fas fa-list-ol text-warning me-2"></i>
                    <h6 class="mb-0">Instructions</h6>
                </div>
                <div class="content-section-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($assignment['instructions'])) ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($assignment['requirements']): ?>
            <div class="content-section mb-4">
                <div class="content-section-header">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <h6 class="mb-0">Requirements</h6>
                </div>
                <div class="content-section-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($assignment['requirements'])) ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
            
            <!-- Submission Form -->
            <form id="assignmentSubmissionForm" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?= htmlspecialchars(IdEncryption::encrypt($course_id)) ?>">
                <input type="hidden" name="assignment_package_id" value="<?= htmlspecialchars(IdEncryption::encrypt($assignment_id)) ?>">
                
                <?php 
                // Check if there's meaningful saved content to continue
                $hasSavedContent = false;
                if ($has_in_progress && !empty($in_progress_submission)) {
                    $hasSavedContent = !empty($in_progress_submission['submission_text']) || 
                                     !empty($in_progress_submission['submission_file']) || 
                                     !empty($in_progress_submission['submission_url']);
                }
                ?>
                
                <?php if ($hasSavedContent): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-edit me-2"></i>
                        <strong>Continue Your Work:</strong> Your previously saved progress has been loaded below. You can continue editing and save your progress or submit when ready.
                        <small class="d-block mt-1">Last saved: <?= date('M j, Y g:i A', strtotime($in_progress_submission['updated_at'])) ?></small>
                    </div>
                <?php endif; ?>
                
                <!-- Submission Type Selection -->
                <div class="submission-type-selection">
                    <label class="form-label"><strong>Submission Type:</strong></label>
                    <?php 
                    $submissionFormat = $assignment['submission_format'] ?? 'file_upload';
                    $isMixed = ($submissionFormat === 'mixed');
                    $inputType = $isMixed ? 'checkbox' : 'radio';
                    $nameAttribute = $isMixed ? 'submission_type[]' : 'submission_type';
                    
                    // Get saved submission types from in-progress submission (only if there's saved content)
                    $savedSubmissionTypes = [];
                    if ($hasSavedContent && isset($in_progress_submission['submission_type']) && !empty($in_progress_submission['submission_type'])) {
                        $savedSubmissionTypes = explode(',', $in_progress_submission['submission_type']);
                        $savedSubmissionTypes = array_map('trim', $savedSubmissionTypes);
                    }
                    ?>
                    
                    <?php if ($isMixed): ?>
                        <!-- Mixed submission - show all options with checkboxes -->
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="submission_type[]" id="text_entry" 
                                   value="text_entry" <?= in_array('text_entry', $savedSubmissionTypes) ? 'checked' : 'checked' ?>>
                            <label class="form-check-label" for="text_entry">
                                <i class="fas fa-keyboard me-1"></i>Text Entry
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="submission_type[]" id="file_upload" 
                                   value="file_upload" <?= in_array('file_upload', $savedSubmissionTypes) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="file_upload">
                                <i class="fas fa-upload me-1"></i>File Upload
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="submission_type[]" id="url_submission" 
                                   value="url_submission" <?= in_array('url_submission', $savedSubmissionTypes) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="url_submission">
                                <i class="fas fa-link me-1"></i>URL Submission
                            </label>
                        </div>
                        
                        <div class="alert alert-info mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Mixed Submission:</strong> You can select multiple submission types. All selected sections will be available for your submission.
                        </div>
                    <?php else: ?>
                        <!-- Single submission type - show only the specific type -->
                        <?php if ($submissionFormat === 'text_entry'): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="submission_type" id="text_entry" 
                                       value="text_entry" <?= (empty($savedSubmissionTypes) || in_array('text_entry', $savedSubmissionTypes)) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="text_entry">
                                    <i class="fas fa-keyboard me-1"></i>Text Entry
                                </label>
                            </div>
                        <?php elseif ($submissionFormat === 'file_upload'): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="submission_type" id="file_upload" 
                                       value="file_upload" <?= (empty($savedSubmissionTypes) || in_array('file_upload', $savedSubmissionTypes)) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="file_upload">
                                    <i class="fas fa-upload me-1"></i>File Upload
                                </label>
                            </div>
                        <?php elseif ($submissionFormat === 'url_submission'): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="submission_type" id="url_submission" 
                                       value="url_submission" <?= (empty($savedSubmissionTypes) || in_array('url_submission', $savedSubmissionTypes)) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="url_submission">
                                    <i class="fas fa-link me-1"></i>URL Submission
                                </label>
                            </div>
                        <?php else: ?>
                            <!-- Fallback to file_upload if unknown format -->
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="submission_type" id="file_upload" 
                                       value="file_upload" <?= (empty($savedSubmissionTypes) || in_array('file_upload', $savedSubmissionTypes)) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="file_upload">
                                    <i class="fas fa-upload me-1"></i>File Upload
                                </label>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Text Entry Section -->
                <div id="text_entry_section" class="submission-section">
                    <div class="mb-3">
                        <label for="submission_text" class="form-label"><strong>Text Submission:</strong></label>
                        <textarea class="form-control" id="submission_text" name="submission_text" rows="10" 
                                  placeholder="Enter your assignment content here..."><?= ($hasSavedContent && isset($in_progress_submission['submission_text'])) ? htmlspecialchars($in_progress_submission['submission_text']) : '' ?></textarea>
                    </div>
                </div>
                
                <!-- File Upload Section -->
                <div id="file_upload_section" class="submission-section" style="display: none;">
                    <div class="mb-3">
                        <label for="submission_file" class="form-label"><strong>Upload File:</strong></label>
                        <input type="file" class="form-control" id="submission_file" name="submission_file" 
                               accept=".pdf,.doc,.docx,.txt,.rtf,.jpg,.jpeg,.png,.gif">
                        <div class="form-text">Max size: 50MB. Allowed formats: PDF, DOC, DOCX, TXT, RTF, JPG, PNG, GIF</div>
                        
                        <?php if ($hasSavedContent && isset($in_progress_submission['submission_file']) && !empty($in_progress_submission['submission_file'])): ?>
                            <div class="mt-2">
                                <div class="alert alert-info">
                                    <i class="fas fa-file me-2"></i>
                                    <strong>Previously uploaded file:</strong>
                                    <a href="uploads/assignment_submissions/<?= htmlspecialchars($in_progress_submission['submission_file']) ?>" 
                                       target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="fas fa-download me-1"></i>Download
                                    </a>
                                    <small class="text-muted d-block mt-1">Upload a new file to replace this one.</small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- URL Submission Section -->
                <div id="url_submission_section" class="submission-section" style="display: none;">
                    <div class="mb-3">
                        <label for="submission_url" class="form-label"><strong>Submission URL:</strong></label>
                        <input type="url" class="form-control" id="submission_url" name="submission_url" 
                               placeholder="https://example.com/your-submission"
                               value="<?= ($hasSavedContent && isset($in_progress_submission['submission_url'])) ? htmlspecialchars($in_progress_submission['submission_url']) : '' ?>">
                        <div class="form-text">Enter the URL where your assignment can be viewed</div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary flex-fill" id="saveProgressBtn">
                        <i class="fas fa-save me-1"></i>Save Progress
                    </button>
                    <button type="submit" class="btn btn-primary flex-fill" id="submitAssignmentBtn">
                        <i class="fas fa-paper-plane me-1"></i>Submit Assignment
                    </button>
                </div>
            </form>
<?php endif; ?>

<!-- Custom styles for assignment modal -->
<style>
.assignment-details-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #dee2e6;
}

.assignment-detail-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
}

.assignment-detail-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.detail-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(13, 110, 253, 0.1);
    border-radius: 50%;
    font-size: 18px;
}

.detail-content {
    flex: 1;
}

.detail-label {
    font-size: 12px;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.detail-value {
    font-size: 16px;
    font-weight: 600;
    color: #212529;
}

.assignment-content-sections {
    border-top: 2px solid #e9ecef;
    padding-top: 20px;
}

.content-section {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.2s ease;
}

.content-section:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.content-section-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 16px 20px;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    font-weight: 600;
    color: #495057;
}

.content-section-body {
    padding: 20px;
}


.submission-type-selection {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.submission-section {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
</style>

<!-- Assignment validation is handled in my_course_details.php -->

<!-- <script>
// Assignment submission form handling
console.log('Assignment submission script loaded');

// Function to initialize assignment submission
function initializeAssignmentSubmission() {
    console.log('initializeAssignmentSubmission called');
    const form = document.getElementById('assignmentSubmissionForm');
    console.log('Form found:', form);
    if (!form) {
        console.log('Form not found, retrying in 100ms');
        setTimeout(initializeAssignmentSubmission, 100);
        return;
    }
    
    // Handle submission type changes (both radio and checkbox)
    const submissionTypeInputs = document.querySelectorAll('input[name="submission_type"], input[name="submission_type[]"]');
    const sections = {
        'file_upload': document.getElementById('file_upload_section'),
        'text_entry': document.getElementById('text_entry_section'),
        'url_submission': document.getElementById('url_submission_section')
    };
    
    console.log('Initializing assignment submission form');
    console.log('Found inputs:', submissionTypeInputs.length);
    console.log('Found sections:', sections);
    
    function updateSubmissionSections() {
        // Re-query inputs in case they weren't available initially
        const currentInputs = document.querySelectorAll('input[name="submission_type"], input[name="submission_type[]"]');
        console.log('updateSubmissionSections called with', currentInputs.length, 'inputs');
        
        // Check if we're dealing with checkboxes (mixed submission)
        const isCheckboxMode = currentInputs.length > 0 && currentInputs[0].type === 'checkbox';
        
        console.log('isCheckboxMode:', isCheckboxMode);
        console.log('currentInputs:', currentInputs);
        
        // First, hide all sections
        Object.values(sections).forEach(section => {
            if (section) {
                section.style.setProperty('display', 'none', 'important');
                console.log('Hiding section:', section.id);
            }
        });
        
        if (isCheckboxMode) {
            // For checkboxes, show all checked sections
            currentInputs.forEach(input => {
                console.log('Checking input:', input.value, 'checked:', input.checked);
                if (input.checked && sections[input.value]) {
                    sections[input.value].style.setProperty('display', 'block', 'important');
                    console.log('Showing section:', input.value);
                }
            });
        } else {
            // For radio buttons, show only the selected section
            const checkedInput = document.querySelector('input[name="submission_type"]:checked');
            console.log('Checked radio input:', checkedInput);
            if (checkedInput && sections[checkedInput.value]) {
                sections[checkedInput.value].style.setProperty('display', 'block', 'important');
                console.log('Showing section:', checkedInput.value);
            }
        }
    }
    
    // Initialize with current selection
    updateSubmissionSections();
    
    // Handle input changes - use event delegation for better reliability
    document.addEventListener('change', function(e) {
        if (e.target.matches('input[name="submission_type"], input[name="submission_type[]"]')) {
            console.log('Checkbox/radio changed:', e.target.value, e.target.checked);
            updateSubmissionSections();
        }
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submitAssignmentBtn');
        const originalText = submitBtn.innerHTML;
        
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
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message
                const modal = document.getElementById('assignmentModal');
                const modalBody = modal.querySelector('.modal-body');
                modalBody.innerHTML = `
                    <div class="text-center">
                        <div class="text-success mb-3">
                            <i class="fas fa-check-circle fa-3x"></i>
                        </div>
                        <h5 class="text-success">Assignment Submitted Successfully!</h5>
                        <p class="text-muted">Thank you for your submission. It has been recorded.</p>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                    </div>
                `;
                
                // Add event listener for modal close to refresh page
                const handleModalClose = () => {
                    window.location.reload();
                    modal.removeEventListener('hidden.bs.modal', handleModalClose);
                    modal.removeAttribute('data-refresh-listener-added');
                };
                modal.addEventListener('hidden.bs.modal', handleModalClose);
                modal.setAttribute('data-refresh-listener-added', 'true');
                
                // Close modal after 3 seconds
                setTimeout(() => {
                    bootstrap.Modal.getInstance(modal).hide();
                }, 3000);
            } else {
                // Show error message
                alert(data.message || 'Failed to submit assignment. Please try again.');
            }
        })
        .catch(error => {
            alert('An error occurred while submitting the assignment. Please try again.');
        })
        .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
}

// Call the initialization function
initializeAssignmentSubmission();

// Also try after a delay as fallback
setTimeout(initializeAssignmentSubmission, 500);
</script> -->
