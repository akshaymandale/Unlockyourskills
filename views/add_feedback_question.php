<?php // views/add_feedback.php ?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container add-feedback-question-container">

        <div class="back-arrow-container">
            <a href="index.php?controller=VLRController" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple"><?= Localization::translate('feedback_question_management_title'); ?></h1>
        </div>

        <!-- ✅ Filters & Search Section -->
        <div class="container-fluid mb-3">
            <div class="row justify-content-between align-items-center g-3">

                <!-- Filter Multiselect on the left -->
                <div class="col-md-auto">
                    <select class="form-select form-select-sm" multiple name="feedbackFiltersSelect"
                        id="feedbackFiltersSelect" title="<?= Localization::translate('filters_select_options'); ?>">
                        <option value="question_type"><?= Localization::translate('filters_question_type'); ?></option>
                        <option value="tags"><?= Localization::translate('filters_tags'); ?></option>
                    </select>
                </div>

                <!-- Search Bar in the middle -->
                <div class="col-md-auto">
                    <div class="input-group input-group-sm">
                        <input type="text" id="searchFeedbackQuestionInput" name="searchFeedbackQuestionInput"
                            class="form-control"
                            placeholder="<?= Localization::translate('filters_search_placeholder'); ?>"
                            title="<?= Localization::translate('filters_search'); ?>">
                        <button type="submit" id="searchFeedbackQuestionButton" name="searchFeedbackQuestionButton"
                            class="btn btn-outline-secondary" title="<?= Localization::translate('filters_search'); ?>">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <!-- Add Feedback Question Button -->
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                        data-bs-target="#addFeedbackQuestionModal">
                        Add Feedback Question
                    </button>

                    <!-- 📍 Feedback Question Modal -->
                    <div class="modal fade" id="addFeedbackQuestionModal" tabindex="-1"
                        aria-labelledby="addFeedbackQuestionModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <form id="feedbackQuestionForm" enctype="multipart/form-data" method="POST"
                                    action="index.php?controller=FeedbackQuestionController&action=save">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="addFeedbackQuestionModalLabel">Add Feedback Question</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Question Title + Upload -->
                                        <div class="mb-3">
                                            <label for="feedbackQuestionTitle" class="form-label">Feedback Question Title
                                                <span class="text-danger">*</span></label>
                                            <div class="d-flex align-items-center gap-3">
                                                <input type="text" id="feedbackQuestionTitle"
                                                    name="feedbackQuestionTitle" class="form-control">
                                                <label for="feedbackQuestionMedia" class="btn btn-outline-secondary mb-0"
                                                    title="Upload image, video, or PDF">
                                                    <i class="fas fa-upload"></i>
                                                </label>
                                                <input type="file" id="feedbackQuestionMedia" name="feedbackQuestionMedia"
                                                    accept="image/*,video/*,.pdf" class="d-none">
                                            </div>
                                            <div id="feedbackQuestionPreview"
                                                class="preview-container question-preview mt-2"></div>
                                        </div>

                                        <!-- Question Type -->
                                        <div class="mb-3">
                                            <label for="feedbackQuestionType" class="form-label">Type</label>
                                            <select id="feedbackQuestionType" name="feedbackQuestionType"
                                                class="form-select">
                                                <option value="multi_choice">Multi Choice</option>
                                                <option value="checkbox">Checkbox</option>
                                                <option value="short_answer">Short Answer</option>
                                                <option value="long_answer">Long Answer</option>
                                                <option value="dropdown">Dropdown</option>
                                                <option value="upload">Upload</option>
                                                <option value="rating">Rating</option>
                                            </select>
                                        </div>

                                        <!-- Options (Dynamic) -->
                                        <div id="feedbackOptionsWrapper" class="mb-3"></div>

                                        <!-- Rating -->
                                        <div id="feedbackRatingWrapper" class="mb-3 d-none">
                                            <label class="form-label">Rating Scale</label>
                                            <div class="row g-2">
                                                <div class="col">
                                                    <select id="feedbackRatingScale" name="feedbackRatingScale" class="form-select">
                                                        <?php for ($i = 1; $i <= 10; $i++): ?>
                                                            <option value="<?= $i ?>"><?= $i ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                                <div class="col">
                                                    <select id="feedbackRatingSymbol" name="feedbackRatingSymbol"class="form-select">
                                                        <option value="star">⭐ Star</option>
                                                        <option value="thumb">👍 Thumb</option>
                                                        <option value="heart">❤️ Heart</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tags -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="feedbackTags"><?= Localization::translate('tags_keywords'); ?>
                                                        <span class="text-danger">*</span></label>
                                                    <div class="tag-input-container form-control">
                                                        <span id="feedbackTagDisplay"></span>
                                                        <input type="text" id="feedbackTagInput"
                                                            placeholder="<?= Localization::translate('add_tag_placeholder'); ?>">
                                                    </div>
                                                    <input type="hidden" name="feedbackTagList" id="feedbackTagList">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-success">Submit Feedback Question</button>
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Import Feedback Button -->
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-primary" id="importFeedbackButton"
                        onclick="window.location.href='index.php?controller=UserManagementController&action=import'"
                        title="<?= Localization::translate('buttons_import_feedback_tooltip'); ?>">
                        <i class="fas fa-upload me-1"></i> <?= Localization::translate('buttons_import_feedback'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- ✅ Feedback Questions Grid View -->
        <table class="table table-bordered" id="feedbackQuestionGrid">
            <thead class="question-grid">
                <tr>
                    <th><?= Localization::translate('feedback_grid_title'); ?></th>
                    <th><?= Localization::translate('feedback_grid_type'); ?></th>
                    <th><?= Localization::translate('feedback_grid_tags'); ?></th>
                    <th><?= Localization::translate('feedback_grid_actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($questions)): ?>
                    <?php foreach ($questions as $question): ?>
                        <tr>
                            <td><?= htmlspecialchars($question['title']); ?></td>
                            <td><?= htmlspecialchars($question['type']); ?></td>
                            <td><?= htmlspecialchars($question['tags']); ?></td>
                            <td>
                                <a href="index.php?controller=FeedbackQuestionController&action=edit&id=<?= $question['id']; ?>"
                                    class="btn btn-sm theme-btn-primary"
                                    title="<?= Localization::translate('feedback_grid_edit'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="index.php?controller=FeedbackQuestionController&action=delete&id=<?= $question['id']; ?>"
                                    class="btn btn-sm theme-btn-danger"
                                    title="<?= Localization::translate('feedback_grid_delete'); ?>"
                                    onclick="return confirm('<?= Localization::translate('feedback_grid_delete_confirm'); ?>');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">
                            <?= Localization::translate('feedback_grid_no_questions_found'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- ✅ Pagination -->
        <div class="pagination-container">
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?controller=FeedbackQuestionController&page=<?= $page - 1; ?>">«
                                    <?= Localization::translate('pagination_prev'); ?></a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link"
                                    href="index.php?controller=FeedbackQuestionController&page=<?= $i; ?>"><?= $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="index.php?controller=FeedbackQuestionController&page=<?= $page + 1; ?>"><?= Localization::translate('pagination_next'); ?>
                                    »</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="public/js/feedback_question_validation.js"></script>
<script src="public/js/feedback_question.js"></script>
<?php include 'includes/footer.php'; ?>
