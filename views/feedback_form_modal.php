<?php
/**
 * Modal-specific feedback form view
 * This version is designed to be loaded in a modal without header/navbar/sidebar
 */
?>

<div class="feedback-form-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="fas fa-comment-dots text-warning me-2"></i>
                Course Feedback
            </h4>
            <p class="text-muted mb-0">
                <?= htmlspecialchars($course['name']) ?> - <?= htmlspecialchars($feedbackPackage['title']) ?>
            </p>
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
                <h6 class="mb-0">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    Your Previous Feedback
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-warning btn-sm" id="resubmitFeedback">
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
            <h6 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i>
                Feedback Questions
            </h6>
            <small class="text-muted">
                <?= htmlspecialchars($feedbackPackage['description'] ?? 'Please provide your feedback to help us improve this course.') ?>
            </small>
        </div>
        <div class="card-body">
            <form id="feedbackForm" method="POST">
                <input type="hidden" name="course_id" value="<?= IdEncryption::encrypt($course['id']) ?>">
                <input type="hidden" name="feedback_package_id" value="<?= IdEncryption::encrypt($feedbackPackage['id']) ?>">
                
                <?php foreach ($feedbackPackage['questions'] as $index => $question): ?>
                    <div class="feedback-question-card mb-4" data-question-id="<?= $question['id'] ?>">
                        <div class="feedback-question-title">
                            <span class="question-number"><?= $index + 1 ?>.</span>
                            <?= htmlspecialchars($question['title']) ?>
                            <span class="feedback-question-required">*</span>
                        </div>
                        
                        <?php if (!empty($question['tags'])): ?>
                            <small class="text-muted mb-3 d-block">
                                <i class="fas fa-tags me-1"></i>
                                <?= htmlspecialchars($question['tags']) ?>
                            </small>
                        <?php endif; ?>

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

                        <!-- Question Response Fields -->
                        <?php switch ($question['type']): 
                            case 'rating': ?>
                                <div class="rating-response">
                                    <div class="modal-rating-stars">
                                        <?php for ($i = 1; $i <= ($question['rating_scale'] ?? 5); $i++): ?>
                                            <span class="modal-rating-star" data-rating="<?= $i ?>">
                                                <i class="fas fa-star"></i>
                                            </span>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="rating">
                                    <input type="hidden" name="responses[<?= $question['id'] ?>][value]" value="" class="rating-input" required>
                                    <small class="text-muted d-block mt-2">
                                        Click on a star to rate (1-<?= $question['rating_scale'] ?? 5 ?>)
                                    </small>
                                </div>
                                <?php break; ?>

                            <?php case 'multi_choice': ?>
                                <div class="choice-response">
                                    <div class="choice-options">
                                        <?php foreach ($question['options'] as $option): ?>
                                            <div class="modal-choice-option">
                                                <input type="radio" 
                                                       name="responses[<?= $question['id'] ?>][value]" 
                                                       value="<?= $option['id'] ?>" 
                                                       id="option_<?= $option['id'] ?>" 
                                                       required>
                                                <label for="option_<?= $option['id'] ?>">
                                                    <?= htmlspecialchars($option['text']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="multi_choice">
                                </div>
                                <?php break; ?>

                            <?php case 'checkbox': ?>
                                <div class="checkbox-response">
                                    <div class="choice-options">
                                        <?php foreach ($question['options'] as $option): ?>
                                            <div class="modal-choice-option">
                                                <input type="checkbox" 
                                                       name="responses[<?= $question['id'] ?>][value][]" 
                                                       value="<?= $option['id'] ?>" 
                                                       id="checkbox_<?= $option['id'] ?>">
                                                <label for="checkbox_<?= $option['id'] ?>">
                                                    <?= htmlspecialchars($option['text']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="checkbox">
                                </div>
                                <?php break; ?>

                            <?php case 'dropdown': ?>
                                <div class="dropdown-response">
                                    <select name="responses[<?= $question['id'] ?>][value]" class="form-select" required>
                                        <option value="">Select an option</option>
                                        <?php foreach ($question['options'] as $option): ?>
                                            <option value="<?= $option['id'] ?>">
                                                <?= htmlspecialchars($option['text']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="dropdown">
                                </div>
                                <?php break; ?>

                            <?php case 'short_answer': ?>
                                <div class="text-response">
                                    <input type="text" 
                                           name="responses[<?= $question['id'] ?>][value]" 
                                           class="form-control" 
                                           placeholder="Enter your answer" 
                                           required>
                                    <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="short_answer">
                                </div>
                                <?php break; ?>

                            <?php case 'long_answer': ?>
                                <div class="text-response">
                                    <textarea name="responses[<?= $question['id'] ?>][value]" 
                                              class="modal-textarea" 
                                              placeholder="Enter your detailed feedback" 
                                              required></textarea>
                                    <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="long_answer">
                                </div>
                                <?php break; ?>

                            <?php case 'file': 
                            case 'upload': ?>
                                <div class="file-response">
                                    <div class="feedback-file-upload">
                                        <input type="file" 
                                               name="responses[<?= $question['id'] ?>][value]" 
                                               class="file-input" 
                                               accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif">
                                        <div class="upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <p class="mb-1">Click to upload or drag and drop</p>
                                        <small class="text-muted">PDF, DOC, TXT, Images (max 5MB)</small>
                                    </div>
                                    <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="file">
                                </div>
                                <?php break; ?>

                            <?php default: ?>
                                <div class="text-response">
                                    <textarea name="responses[<?= $question['id'] ?>][value]" 
                                              class="modal-textarea" 
                                              placeholder="Enter your feedback" 
                                              required></textarea>
                                    <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="text">
                                </div>
                        <?php endswitch; ?>
                    </div>
                <?php endforeach; ?>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn modal-submit-btn">
                        <i class="fas fa-paper-plane me-2"></i>
                        Submit Feedback
                    </button>
                    
                    <?php if ($hasSubmitted): ?>
                        <button type="button" class="btn btn-outline-secondary ms-2" id="deleteFeedback">
                            <i class="fas fa-trash me-2"></i>
                            Delete Previous Feedback
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize rating stars for this modal form
document.addEventListener('DOMContentLoaded', function() {
    // Rating stars functionality
    const ratingStars = document.querySelectorAll('.modal-rating-star');
    ratingStars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
            const questionCard = this.closest('.feedback-question-card');
            const ratingInput = questionCard.querySelector('.rating-input');
            const allStars = questionCard.querySelectorAll('.modal-rating-star');
            
            // Update hidden input value
            ratingInput.value = rating;
            
            // Update star display
            allStars.forEach((s, index) => {
                s.classList.toggle('active', index < rating);
            });
        });
    });
    
    // File upload styling
    const fileInputs = document.querySelectorAll('.file-input');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0]?.name;
            if (fileName) {
                const uploadArea = this.closest('.feedback-file-upload');
                uploadArea.innerHTML = `
                    <div class="text-success">
                        <i class="fas fa-check-circle me-2"></i>
                        File selected: ${fileName}
                    </div>
                `;
            }
        });
    });
});
</script>
