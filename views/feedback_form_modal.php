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
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            You have already submitted feedback for this course. Thank you for your feedback!
        </div>
        
        <!-- Show existing responses -->
        <div class="mt-4">
            <h5>Your Responses:</h5>
            <div class="responses-container">
                <?php foreach ($existingResponses as $response): ?>
                    <div class="response-item mb-3 p-3 border rounded">
                        <h6><?= htmlspecialchars($response['question_title']) ?></h6>
                        <div class="response-value">
                            <?php
                            switch ($response['response_type']) {
                                case 'rating':
                                    echo '<span class="badge bg-primary">Rating: ' . $response['rating_value'] . '</span>';
                                    break;
                                case 'text':
                                case 'short_answer':
                                case 'long_answer':
                                    echo '<p class="mb-0">' . htmlspecialchars($response['text_response']) . '</p>';
                                    break;
                                case 'choice':
                                case 'multi_choice':
                                case 'dropdown':
                                    $optionText = $response['option_text'] ?? 'No option selected';
                                    echo '<span class="badge bg-secondary">' . htmlspecialchars($optionText) . '</span>';
                                    break;
                                case 'checkbox':
                                    // For checkbox, display all selected options
                                    if (isset($response['checkbox_options']) && is_array($response['checkbox_options'])) {
                                        foreach ($response['checkbox_options'] as $optionText) {
                                            echo '<span class="badge bg-info me-1 mb-1">' . htmlspecialchars($optionText) . '</span>';
                                        }
                                    } elseif (isset($response['option_text']) && !empty($response['option_text'])) {
                                        echo '<span class="badge bg-info">' . htmlspecialchars($response['option_text']) . '</span>';
                                    } else {
                                        echo '<span class="badge bg-warning">No options selected</span>';
                                    }
                                    break;
                                case 'file':
                                    echo '<a href="' . htmlspecialchars($response['file_response']) . '" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download me-1"></i>Download File
                                          </a>';
                                    break;
                                default:
                                    echo '<span class="text-muted">Response submitted</span>';
                                    break;
                            }
                            ?>
                        </div>
                        <small class="text-muted">
                            Submitted: <?= date('M j, Y g:i A', strtotime($response['submitted_at'])) ?>
                        </small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
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
            <?php
            // Map existing responses by question_id for quick lookup
            $responsesByQuestion = [];
            if (!empty($existingResponses) && is_array($existingResponses)) {
                foreach ($existingResponses as $resp) {
                    if (isset($resp['question_id'])) {
                        $responsesByQuestion[$resp['question_id']] = $resp;
                    }
                }
            }
            ?>
            <form id="feedbackForm" method="POST">
                <input type="hidden" name="course_id" value="<?= IdEncryption::encrypt($course['id']) ?>">
                <input type="hidden" name="feedback_package_id" value="<?= IdEncryption::encrypt($feedbackPackage['id']) ?>">
                
                <?php foreach ($feedbackPackage['questions'] as $index => $question): ?>
                    <?php $saved = $responsesByQuestion[$question['id']] ?? null; ?>
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
                                        <?php 
                                            $savedRating = isset($saved['rating_value']) ? (int)$saved['rating_value'] : 0;
                                            $maxRating = (int)($question['rating_scale'] ?? 5);
                                            for ($i = 1; $i <= $maxRating; $i++): ?>
                                            <span class="modal-rating-star<?= ($savedRating >= $i ? ' active' : '') ?>" data-rating="<?= $i ?>">
                                                <i class="fas fa-star"></i>
                                            </span>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="rating">
                                    <input type="hidden" name="responses[<?= $question['id'] ?>][value]" value="<?= $savedRating ?: '' ?>" class="rating-input">
                                    <small class="text-muted d-block mt-2">
                                        Click on a star to rate (1-<?= $question['rating_scale'] ?? 5 ?>)
                                    </small>
                                </div>
                                <?php break; ?>

                            <?php case 'multi_choice': ?>
                                <div class="choice-response">
                                    <div class="choice-options">
                                        <?php 
                                            $savedChoice = isset($saved['choice_response']) ? (string)$saved['choice_response'] : '';
                                            foreach ($question['options'] as $option): 
                                                $isChecked = ($savedChoice !== '' && (string)$option['id'] === $savedChoice);
                                        ?>
                                        <div class="modal-choice-option">
                                            <input type="radio" 
                                                   name="responses[<?= $question['id'] ?>][value]" 
                                                   value="<?= $option['id'] ?>" 
                                                   id="option_<?= $option['id'] ?>" 
                                                   <?= $isChecked ? 'checked' : '' ?>>
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
                                        <?php 
                                            $savedChoices = [];
                                            if (!empty($saved['choice_response'])) {
                                                $savedChoices = array_filter(array_map('trim', explode(',', (string)$saved['choice_response'])));
                                            }
                                            foreach ($question['options'] as $option): 
                                                $isChecked = in_array((string)$option['id'], $savedChoices, true);
                                        ?>
                                        <div class="modal-choice-option">
                                            <input type="checkbox" 
                                                   name="responses[<?= $question['id'] ?>][value][]" 
                                                   value="<?= $option['id'] ?>" 
                                                   id="checkbox_<?= $option['id'] ?>" <?= $isChecked ? 'checked' : '' ?>>
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
                                    <?php $savedChoice = isset($saved['choice_response']) ? (string)$saved['choice_response'] : ''; ?>
                                    <select name="responses[<?= $question['id'] ?>][value]" class="form-select">
                                        <option value="">Select an option</option>
                                        <?php foreach ($question['options'] as $option): ?>
                                            <option value="<?= $option['id'] ?>" <?= ($savedChoice !== '' && (string)$option['id'] === $savedChoice) ? 'selected' : '' ?> >
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
                                           value="<?= isset($saved['text_response']) ? htmlspecialchars($saved['text_response']) : '' ?>">
                                    <input type="hidden" name="responses[<?= $question['id'] ?>][type]" value="short_answer">
                                </div>
                                <?php break; ?>

                            <?php case 'long_answer': ?>
                                <div class="text-response">
                                    <textarea name="responses[<?= $question['id'] ?>][value]" 
                                              class="modal-textarea" 
                                              placeholder="Enter your detailed feedback"><?= isset($saved['text_response']) ? htmlspecialchars($saved['text_response']) : '' ?></textarea>
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
                                              placeholder="Enter your feedback"></textarea>
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
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// File upload styling for modal form
document.addEventListener('DOMContentLoaded', function() {
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
<?php endif; ?>
