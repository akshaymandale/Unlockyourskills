<?php
/**
 * Assignment Submission View
 * 
 * Standalone page for assignment submission
 */

require_once 'views/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-file-pen me-2"></i>
                    Assignment Submission
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/unlockyourskills/my-courses">My Courses</a></li>
                        <li class="breadcrumb-item"><a href="/unlockyourskills/my-courses/details/<?= htmlspecialchars(IdEncryption::encrypt($course_id)) ?>">Course Details</a></li>
                        <li class="breadcrumb-item active">Assignment Submission</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8 mx-auto">
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
                <div class="card" id="assignmentSubmissionCard">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-file-pen me-2"></i>
                            <?= htmlspecialchars($assignment['title']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($assignment['description']): ?>
                            <div class="mb-3">
                                <h6>Description:</h6>
                                <p class="text-muted"><?= nl2br(htmlspecialchars($assignment['description'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($assignment['instructions']): ?>
                            <div class="mb-3">
                                <h6>Instructions:</h6>
                                <div class="border rounded p-3 bg-light">
                                    <?= nl2br(htmlspecialchars($assignment['instructions'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($assignment['requirements']): ?>
                            <div class="mb-3">
                                <h6>Requirements:</h6>
                                <div class="border rounded p-3 bg-light">
                                    <?= nl2br(htmlspecialchars($assignment['requirements'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Assignment Details -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <strong>Time Limit:</strong> 
                                    <?= $assignment['time_limit'] ? $assignment['time_limit'] . ' minutes' : 'No limit' ?>
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-redo me-1"></i>
                                    <strong>Max Attempts:</strong> <?= $assignment['max_attempts'] ?? 1 ?>
                                </small>
                            </div>
                        </div>
                        
                        <!-- Submission Form -->
                        <form id="assignmentSubmissionForm" enctype="multipart/form-data">
                            <input type="hidden" name="course_id" value="<?= htmlspecialchars(IdEncryption::encrypt($course_id)) ?>">
                            <input type="hidden" name="assignment_package_id" value="<?= htmlspecialchars(IdEncryption::encrypt($assignment_id)) ?>">
                            
                            <!-- Submission Type Selection -->
                            <div class="submission-type-selection">
                                <label class="form-label"><strong>Submission Type:</strong></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="submission_type" id="file_upload" 
                                           value="file_upload" <?= ($assignment['submission_format'] ?? 'file_upload') === 'file_upload' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="file_upload">
                                        <i class="fas fa-upload me-1"></i>File Upload
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="submission_type" id="text_entry" 
                                           value="text_entry" <?= ($assignment['submission_format'] ?? '') === 'text_entry' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="text_entry">
                                        <i class="fas fa-keyboard me-1"></i>Text Entry
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="submission_type" id="url_submission" 
                                           value="url_submission" <?= ($assignment['submission_format'] ?? '') === 'url_submission' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="url_submission">
                                        <i class="fas fa-link me-1"></i>URL Submission
                                    </label>
                                </div>
                            </div>
                            
                            <!-- File Upload Section -->
                            <div id="file_upload_section" class="submission-section">
                                <div class="mb-3">
                                    <label for="submission_file" class="form-label"><strong>Upload File:</strong></label>
                                    <input type="file" class="form-control" id="submission_file" name="submission_file" 
                                           accept=".pdf,.doc,.docx,.txt,.rtf,.jpg,.jpeg,.png,.gif">
                                    <div class="form-text">Max size: 50MB. Allowed formats: PDF, DOC, DOCX, TXT, RTF, JPG, PNG, GIF</div>
                                </div>
                            </div>
                            
                            <!-- Text Entry Section -->
                            <div id="text_entry_section" class="submission-section" style="display: none;">
                                <div class="mb-3">
                                    <label for="submission_text" class="form-label"><strong>Text Submission:</strong></label>
                                    <textarea class="form-control" id="submission_text" name="submission_text" rows="10" 
                                              placeholder="Enter your assignment content here..."></textarea>
                                </div>
                            </div>
                            
                            <!-- URL Submission Section -->
                            <div id="url_submission_section" class="submission-section" style="display: none;">
                                <div class="mb-3">
                                    <label for="submission_url" class="form-label"><strong>Submission URL:</strong></label>
                                    <input type="url" class="form-control" id="submission_url" name="submission_url" 
                                           placeholder="https://example.com/your-submission">
                                    <div class="form-text">Enter the URL where your assignment can be viewed</div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="submitAssignmentBtn">
                                    <i class="fas fa-paper-plane me-1"></i>Submit Assignment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="/unlockyourskills/public/js/assignment_submission.js"></script>

<?php require_once 'views/includes/footer.php'; ?>
