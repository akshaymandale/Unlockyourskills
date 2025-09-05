<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>
<?php require_once 'core/IdEncryption.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-comment-dots text-warning me-2"></i>
                        Course Feedback
                    </h1>
                    <p class="text-muted mb-0">
                        <?= htmlspecialchars($course['name']) ?> - <?= htmlspecialchars($feedbackPackage['title']) ?>
                    </p>
                </div>
                <div>
                    <a href="<?= UrlHelper::url('my-courses/details') ?>?id=<?= urlencode(IdEncryption::encrypt($course['id'])) ?>" 
                       class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Course
                    </a>
                </div>
            </div>

            <?php if ($hasSubmitted): ?>
                <!-- Show existing feedback submission -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    You have already submitted feedback for this course. You can view your responses below or submit new feedback.
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Your Previous Feedback
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-warning" id="resubmitFeedback">
                                    <i class="fas fa-edit me-1"></i>Submit New Feedback
                                </button>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">
                                    Submitted: <?= date('M j, Y \a\t g:i A') ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Feedback Form -->
            <div class="card" id="feedbackFormCard">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>
                        Feedback Questions
                    </h5>
                    <small class="text-muted">
                        <?= htmlspecialchars($feedbackPackage['description'] ?? 'Please provide your feedback to help us improve this course.') ?>
                    </small>
                </div>
                <div class="card-body">
                    <form id="feedbackForm" method="POST">
                        <input type="hidden" name="course_id" value="<?= urlencode(IdEncryption::encrypt($course['id'])) ?>">
                        <input type="hidden" name="feedback_package_id" value="<?= urlencode(IdEncryption::encrypt($feedbackPackage['id'])) ?>">
                        
                        <?php foreach ($feedbackPackage['questions'] as $index => $question): ?>
                            <div class="feedback-question mb-4" data-question-id="<?= $question['id'] ?>">
                                <div class="question-header mb-3">
                                    <h6 class="mb-1">
                                        <span class="question-number"><?= $index + 1 ?>.</span>
                                        <?= htmlspecialchars($question['title']) ?>
                                    </h6>
                                    <?php if (!empty($question['tags'])): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-tags me-1"></i>
                                            <?= htmlspecialchars($question['tags']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($question['media_path'])): ?>
                                    <div class="question-media mb-3">
                                        <?php if (in_array(pathinfo($question['media_path'], PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                            <img src="<?= htmlspecialchars($question['media_path']) ?>" 
                                                 class="img-fluid rounded" 
                                                 alt="Question media"
                                                 style="max-width: 300px;">
                                        <?php elseif (in_array(pathinfo($question['media_path'], PATHINFO_EXTENSION), ['mp4', 'webm', 'ogg'])): ?>
                                            <video controls class="w-100" style="max-width: 400px;">
                                                <source src="<?= htmlspecialchars($question['media_path']) ?>" type="video/<?= pathinfo($question['media_path'], PATHINFO_EXTENSION) ?>">
                                                Your browser does not support the video tag.
                                            </video>
                                        <?php elseif (in_array(pathinfo($question['media_path'], PATHINFO_EXTENSION), ['mp3', 'wav', 'ogg'])): ?>
                                            <audio controls class="w-100">
                                                <source src="<?= htmlspecialchars($question['media_path']) ?>" type="audio/<?= pathinfo($question['media_path'], PATHINFO_EXTENSION) ?>">
                                                Your browser does not support the audio tag.
                                            </audio>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="question-response">
                                    <?php switch ($question['type']): 
                                        case 'rating': ?>
                                            <div class="rating-response">
                                                <div class="rating-stars">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <input type="radio" 
                                                               name="responses[<?= $question['id'] ?>][value]" 
                                                               value="<?= $i ?>" 
                                                               id="rating_<?= $question['id'] ?>_<?= $i ?>"
                                                               class="rating-input">
                                                        <label for="rating_<?= $question['id'] ?>_<?= $i ?>" 
                                                               class="rating-label">
                                                            <i class="fas fa-star"></i>
                                                        </label>
                                                    <?php endfor; ?>
                                                </div>
                                                <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="rating">
                                                <small class="text-muted d-block mt-2">
                                                    <?= $question['rating_scale'] ?? '1 = Poor, 5 = Excellent' ?>
                                                </small>
                                            </div>
                                            <?php break; ?>

                                        <?php case 'multi_choice': ?>
                                            <div class="choice-response">
                                                <?php foreach ($question['options'] as $option): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" 
                                                               type="radio" 
                                                               name="responses[<?= $question['id'] ?>][value]" 
                                                               value="<?= $option['id'] ?>" 
                                                               id="option_<?= $option['id'] ?>">
                                                        <label class="form-check-label" for="option_<?= $option['id'] ?>">
                                                            <?= htmlspecialchars($option['text']) ?>
                                                            <?php if (!empty($option['media_path'])): ?>
                                                                <img src="<?= htmlspecialchars($option['media_path']) ?>" 
                                                                     class="ms-2" 
                                                                     style="max-height: 30px; max-width: 30px;">
                                                            <?php endif; ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                                <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="choice">
                                            </div>
                                            <?php break; ?>

                                        <?php case 'checkbox': ?>
                                            <div class="checkbox-response">
                                                <?php foreach ($question['options'] as $option): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input" 
                                                               type="checkbox" 
                                                               name="responses[<?= $question['id'] ?>][value][]" 
                                                               value="<?= $option['id'] ?>" 
                                                               id="checkbox_<?= $option['id'] ?>">
                                                        <label class="form-check-label" for="checkbox_<?= $option['id'] ?>">
                                                            <?= htmlspecialchars($option['text']) ?>
                                                            <?php if (!empty($option['media_path'])): ?>
                                                                <img src="<?= htmlspecialchars($option['media_path']) ?>" 
                                                                     class="ms-2" 
                                                                     style="max-height: 30px; max-width: 30px;">
                                                            <?php endif; ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                                <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="choice">
                                            </div>
                                            <?php break; ?>

                                        <?php case 'dropdown': ?>
                                            <div class="dropdown-response">
                                                <select class="form-select" name="responses[<?= $question['id'] ?>][value]">
                                                    <option value="">Select an option...</option>
                                                    <?php foreach ($question['options'] as $option): ?>
                                                        <option value="<?= $option['id'] ?>">
                                                            <?= htmlspecialchars($option['text']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="choice">
                                            </div>
                                            <?php break; ?>

                                        <?php case 'short_answer': ?>
                                            <div class="text-response">
                                                <input type="text" 
                                                       class="form-control" 
                                                       name="responses[<?= $question['id'] ?>][value]" 
                                                       placeholder="Enter your answer...">
                                                <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="text">
                                            </div>
                                            <?php break; ?>

                                        <?php case 'long_answer': ?>
                                            <div class="text-response">
                                                <textarea class="form-control" 
                                                          name="responses[<?= $question['id'] ?>][value]" 
                                                          rows="4" 
                                                          placeholder="Enter your detailed answer..."></textarea>
                                                <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="text">
                                            </div>
                                            <?php break; ?>

                                        <?php case 'upload': ?>
                                            <div class="file-response">
                                                <input type="file" 
                                                       class="form-control" 
                                                       name="responses[<?= $question['id'] ?>][value]" 
                                                       accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif">
                                                <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="file">
                                                <small class="text-muted">
                                                    Supported formats: PDF, DOC, DOCX, TXT, JPG, PNG, GIF
                                                </small>
                                            </div>
                                            <?php break; ?>

                                        <?php default: ?>
                                            <div class="text-response">
                                                <textarea class="form-control" 
                                                          name="responses[<?= $question['id'] ?>][value]" 
                                                          rows="3" 
                                                          placeholder="Enter your response..."></textarea>
                                                <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="text">
                                            </div>
                                    <?php endswitch; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="form-actions mt-4">
                            <button type="submit" class="btn btn-warning btn-lg" id="submitFeedback">
                                <i class="fas fa-paper-plane me-2"></i>
                                Submit Feedback
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-lg ms-2" id="saveDraft">
                                <i class="fas fa-save me-2"></i>
                                Save Draft
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Feedback Submitted!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Thank you for your feedback! Your responses have been recorded successfully.</p>
                <p class="mb-0">This helps us improve our courses and provide better learning experiences.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">Close</button>
                <a href="<?= UrlHelper::url('my-courses/details') ?>?id=<?= urlencode(IdEncryption::encrypt($course['id'])) ?>" 
                   class="btn btn-outline-secondary">
                    Back to Course
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="errorModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Error
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="errorMessage">An error occurred while submitting your feedback.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="<?= UrlHelper::url('public/js/feedback_form.js') ?>"></script>
