<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <!-- Survey Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0">
                                    <i class="fas fa-square-poll-vertical me-2"></i>
                                    <?= htmlspecialchars($survey_package['title']) ?>
                                </h4>
                                <?php if (!empty($survey_package['tags'])): ?>
                                    <small class="opacity-75"><?= htmlspecialchars($survey_package['tags']) ?></small>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($has_submitted): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Completed
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock me-1"></i>Pending
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Survey Form -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <?php if ($has_submitted): ?>
                            <!-- Already Submitted Message -->
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                You have already submitted this survey. Thank you for your feedback!
                            </div>
                            
                            <!-- Show existing responses -->
                            <div class="mt-4">
                                <h5>Your Responses:</h5>
                                <div class="responses-container">
                                    <?php foreach ($existing_responses as $response): ?>
                                        <div class="response-item mb-3 p-3 border rounded">
                                            <h6><?= htmlspecialchars($response['question_title']) ?></h6>
                                            <div class="response-value">
                                                <?php
                                                switch ($response['response_type']) {
                                                    case 'rating':
                                                        echo '<span class="badge bg-primary">Rating: ' . $response['rating_value'] . '</span>';
                                                        break;
                                                    case 'text':
                                                        echo '<p class="mb-0">' . htmlspecialchars($response['text_response']) . '</p>';
                                                        break;
                                                    case 'choice':
                                                        echo '<span class="badge bg-secondary">' . htmlspecialchars($response['option_text']) . '</span>';
                                                        break;
                                                    case 'checkbox':
                                                        // For checkbox, display all selected options
                                                        if (isset($response['checkbox_options']) && is_array($response['checkbox_options'])) {
                                                            foreach ($response['checkbox_options'] as $optionText) {
                                                                echo '<span class="badge bg-info me-1 mb-1">' . htmlspecialchars($optionText) . '</span>';
                                                            }
                                                        } elseif (isset($response['option_text'])) {
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
                                                Submitted: <?= $response['submitted_at'] ? date('M j, Y g:i A', strtotime($response['submitted_at'])) : 'Not submitted' ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Survey Form -->
                            <form id="surveyForm" class="survey-form" method="POST" action="javascript:void(0);" onsubmit="return false;">
                                <input type="hidden" name="course_id" value="<?= htmlspecialchars($encrypted_course_id) ?>">
                                <input type="hidden" name="survey_package_id" value="<?= htmlspecialchars($encrypted_survey_id) ?>">
                                
                                <div class="questions-container">
                                    <?php foreach ($survey_package['questions'] as $index => $question): ?>
                                        <div class="question-item mb-4 p-4 border rounded" data-question-id="<?= $question['id'] ?>">
                                            <div class="question-header mb-3">
                                                <h5 class="question-title">
                                                    <span class="question-number"><?= $index + 1 ?>.</span>
                                                    <?= htmlspecialchars($question['title']) ?>
                                                </h5>
                                                <?php if (!empty($question['tags'])): ?>
                                                    <small class="text-muted"><?= htmlspecialchars($question['tags']) ?></small>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Question Media -->
                                            <?php if (!empty($question['media_path'])): ?>
                                                <div class="question-media mb-3">
                                                    <?php
                                                    $mediaPath = '/unlockyourskills/uploads/survey/' . $question['media_path'];
                                                    $extension = strtolower(pathinfo($question['media_path'], PATHINFO_EXTENSION));
                                                    ?>
                                                    <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                                        <img src="<?= htmlspecialchars($mediaPath) ?>" class="img-fluid rounded" alt="Question Media">
                                                    <?php elseif (in_array($extension, ['mp4', 'webm', 'ogg'])): ?>
                                                        <video controls class="w-100 rounded">
                                                            <source src="<?= htmlspecialchars($mediaPath) ?>" type="video/<?= $extension ?>">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                    <?php else: ?>
                                                        <a href="<?= htmlspecialchars($mediaPath) ?>" target="_blank" class="btn btn-outline-primary">
                                                            <i class="fas fa-download me-1"></i>Download Media
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Question Response Area -->
                                            <div class="question-response">
                                                <?php
                                                $questionType = $question['type'];
                                                $questionId = $question['id'];
                                                $options = $question['options'] ?? [];
                                                ?>

                                                <?php if ($questionType === 'rating'): ?>
                                                    <!-- Rating Question -->
                                                    <div class="rating-response">
                                                        <div class="rating-scale mb-2">
                                                            <span class="text-muted">Rate from 1 to <?= $question['rating_scale'] ?? 5 ?>:</span>
                                                        </div>
                                                        <div class="rating-options">
                                                            <?php for ($i = 1; $i <= ($question['rating_scale'] ?? 5); $i++): ?>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" 
                                                                           name="responses[<?= $questionId ?>][value]" 
                                                                           id="rating_<?= $questionId ?>_<?= $i ?>" 
                                                                           value="<?= $i ?>" required>
                                                                    <label class="form-check-label" for="rating_<?= $questionId ?>_<?= $i ?>">
                                                                        <?= $question['rating_symbol'] ?? $i ?>
                                                                    </label>
                                                                </div>
                                                            <?php endfor; ?>
                                                        </div>
                                                        <input type="hidden" name="responses[<?= $questionId ?>][type]" value="rating">
                                                    </div>

                                                <?php elseif ($questionType === 'text'): ?>
                                                    <!-- Text Question -->
                                                    <div class="text-response">
                                                        <textarea class="form-control" 
                                                                  name="responses[<?= $questionId ?>][value]" 
                                                                  rows="4" 
                                                                  placeholder="Enter your response here..." 
                                                                  required></textarea>
                                                        <input type="hidden" name="responses[<?= $questionId ?>][type]" value="text">
                                                    </div>

                                                <?php elseif (($questionType === 'choice' || $questionType === 'multi_choice' || $questionType === 'checkbox') && !empty($options)): ?>
                                                    <!-- Choice Question -->
                                                    <div class="choice-response">
                                                        <?php foreach ($options as $option): ?>
                                                            <div class="modal-choice-option mb-2">
                                                                <?php if ($questionType === 'checkbox'): ?>
                                                                    <!-- Checkbox: Allow multiple selections -->
                                                                    <input type="checkbox" 
                                                                           name="responses[<?= $questionId ?>][value][]" 
                                                                           id="choice_<?= $questionId ?>_<?= $option['id'] ?>" 
                                                                           value="<?= $option['id'] ?>">
                                                                <?php else: ?>
                                                                    <!-- Radio: Single selection for choice/multi_choice -->
                                                                    <input type="radio" 
                                                                           name="responses[<?= $questionId ?>][value]" 
                                                                           id="choice_<?= $questionId ?>_<?= $option['id'] ?>" 
                                                                           value="<?= $option['id'] ?>" required>
                                                                <?php endif; ?>
                                                                <label for="choice_<?= $questionId ?>_<?= $option['id'] ?>">
                                                                    <?= htmlspecialchars($option['option_text']) ?>
                                                                    <?php if (!empty($option['media_path'])): ?>
                                                                        <div class="option-media mt-2">
                                                                            <?php
                                                                            $optionMediaPath = '/unlockyourskills/uploads/survey/' . $option['media_path'];
                                                                            $optionExtension = strtolower(pathinfo($option['media_path'], PATHINFO_EXTENSION));
                                                                            ?>
                                                                            <?php if (in_array($optionExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                                                                <img src="<?= htmlspecialchars($optionMediaPath) ?>" class="img-thumbnail" style="max-width: 120px;" alt="Option Media">
                                                                            <?php elseif (in_array($optionExtension, ['mp4', 'webm', 'ogg'])): ?>
                                                                                <video controls class="img-thumbnail" style="max-width: 120px; max-height: 90px;">
                                                                                    <source src="<?= htmlspecialchars($optionMediaPath) ?>" type="video/<?= $optionExtension ?>">
                                                                                </video>
                                                                            <?php else: ?>
                                                                                <a href="<?= htmlspecialchars($optionMediaPath) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                                                    <i class="fas fa-download me-1"></i>Media
                                                                                </a>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <input type="hidden" name="responses[<?= $questionId ?>][type]" value="<?= $questionType === 'checkbox' ? 'checkbox' : 'choice' ?>">
                                                    </div>

                                                <?php elseif ($questionType === 'file'): ?>
                                                    <!-- File Upload Question -->
                                                    <div class="file-response">
                                                        <input type="file" class="form-control" 
                                                               name="responses[<?= $questionId ?>][value]" 
                                                               accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif" 
                                                               required>
                                                        <small class="form-text text-muted">
                                                            Accepted formats: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG, GIF
                                                        </small>
                                                        <input type="hidden" name="responses[<?= $questionId ?>][type]" value="file">
                                                    </div>

                                                <?php else: ?>
                                                    <!-- Default/Unknown Question Type -->
                                                    <div class="default-response">
                                                        <textarea class="form-control" 
                                                                  name="responses[<?= $questionId ?>][value]" 
                                                                  rows="3" 
                                                                  placeholder="Enter your response here..." 
                                                                  required></textarea>
                                                        <input type="hidden" name="responses[<?= $questionId ?>][type]" value="text">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Submit Button -->
                                <div class="form-actions mt-4">
                                    <div class="d-flex justify-content-between">
                                        <a href="/unlockyourskills/my-courses/details/<?= htmlspecialchars($encrypted_course_id) ?>" 
                                           class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-1"></i>Back to Course
                                        </a>
                                        <button type="submit" class="btn btn-primary btn-lg" id="submitSurveyBtn">
                                            <i class="fas fa-paper-plane me-1"></i>Submit Survey
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5>Submitting Survey...</h5>
                <p class="text-muted">Please wait while we process your responses.</p>
            </div>
        </div>
    </div>
</div>

<script src="/unlockyourskills/public/js/survey_form.js"></script>

<?php include 'includes/footer.php'; ?>
