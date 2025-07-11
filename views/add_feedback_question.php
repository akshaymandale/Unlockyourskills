<?php // views/add_feedback.php ?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container add-feedback-question-container">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="/unlockyourskills/vlr?tab=feedback" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-comments me-2"></i>
                <?= Localization::translate('feedback_question_management_title'); ?>
            </h1>
        </div>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= UrlHelper::url('dashboard') ?>"><?= Localization::translate('dashboard'); ?></a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= UrlHelper::url('vlr') ?>"><?= Localization::translate('vlr'); ?></a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?= Localization::translate('feedback_question_management_title'); ?>
                </li>
            </ol>
        </nav>

        <!-- Page Description and Add Button -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">
                            <?= Localization::translate('feedback_question_page_description'); ?>
                        </p>
                    </div>
                    <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#addFeedbackQuestionModal"
                        title="<?= Localization::translate('buttons_add_feedback_question_tooltip'); ?>">
                        <i class="fas fa-plus me-2"></i><?= Localization::translate('buttons_add_feedback_question'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3 align-items-center">
                            <!-- Search -->
                            <div class="col-md-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" id="searchInput" class="form-control" placeholder="<?= Localization::translate('filters_search_placeholder'); ?>" title="<?= Localization::translate('filters_search'); ?>">
                                    <button type="button" id="searchButton" class="btn btn-outline-secondary" title="<?= Localization::translate('filters_search'); ?>">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Question Type Filter -->
                            <div class="col-md-2">
                                <select class="form-select form-select-sm" id="questionTypeFilter">
                                    <option value=""><?= Localization::translate('filters_question_type'); ?></option>
                                    <?php if (!empty($uniqueQuestionTypes)): ?>
                                        <?php foreach ($uniqueQuestionTypes as $type): ?>
                                            <option value="<?= htmlspecialchars($type); ?>">
                                                <?= htmlspecialchars($type); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <!-- Tags Filter -->
                            <div class="col-md-2">
                                <input type="text" class="form-control form-control-sm" id="tagsFilter" placeholder="<?= Localization::translate('filters_tags'); ?>" title="Type to filter by tags">
                            </div>
                            <!-- Clear Filters Button -->
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100 btn-sm" id="clearFiltersBtn" title="Clear all filters">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <!-- Import Feedback Question Button -->
                            <div class="col-md-2 text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="importFeedbackButton"
                                    onclick="window.location.href='index.php?controller=UserManagementController&action=import'"
                                    title="<?= Localization::translate('buttons_import_feedback_tooltip'); ?>">
                                    <i class="fas fa-upload me-1"></i><?= Localization::translate('buttons_import_feedback'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Results Info -->
        <div class="row mb-3">
            <div class="col-12">
                <div id="searchResultsInfo" class="search-results-info" style="display: none;">
                    <i class="fas fa-info-circle"></i>
                    <span id="resultsText"></span>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="text-center" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading questions...</p>
        </div>

        <!-- ✅ Feedback Questions Grid View -->
        <div id="questionsContainer" class="fade-transition">
            <table class="table table-bordered" id="feedbackQuestionGrid">
                <thead class="question-grid">
                    <tr>
                        <th><?= Localization::translate('feedback_grid_title'); ?></th>
                        <th><?= Localization::translate('feedback_grid_type'); ?></th>
                        <th><?= Localization::translate('feedback_grid_tags'); ?></th>
                        <th><?= Localization::translate('feedback_grid_actions'); ?></th>
                    </tr>
                </thead>
                <tbody id="questionsTableBody">
                    <?php if (!empty($questions)): ?>
                        <?php foreach ($questions as $question): ?>
                            <tr>
                                <td><?= htmlspecialchars($question['title']); ?></td>
                                <td><?= htmlspecialchars($question['type']); ?></td>
                                <td><?= htmlspecialchars($question['tags']); ?></td>
                                <td>
                                    <button type="button" class="btn theme-btn-primary edit-question-btn"
                                        data-question-id="<?= $question['id']; ?>"
                                        title="<?= Localization::translate('feedback_grid_edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="#" class="btn theme-btn-danger delete-feedback-question"
                                        data-id="<?= $question['id']; ?>"
                                        data-title="<?= htmlspecialchars($question['title']); ?>"
                                        title="<?= Localization::translate('feedback_grid_delete'); ?>">
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
        </div>

        <!-- ✅ Pagination -->
        <div id="paginationContainer" class="pagination-container">
            <?php if ($totalQuestions > 10): ?>
                <nav>
                    <ul class="pagination justify-content-center" id="paginationList">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="#" data-page="<?= $page - 1; ?>">«
                                    <?= Localization::translate('pagination_prev'); ?></a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="#" data-page="<?= $i; ?>"><?= $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="#" data-page="<?= $page + 1; ?>"><?= Localization::translate('pagination_next'); ?>
                                    »</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php elseif ($totalQuestions > 0): ?>
                <!-- Show total count when no pagination needed -->
                <div class="text-center text-muted small">
                    Showing all <?= $totalQuestions; ?> question<?= $totalQuestions != 1 ? 's' : ''; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- ✅ Add Feedback Question Modal -->
<div class="modal fade" id="addFeedbackQuestionModal" tabindex="-1"
    aria-labelledby="addFeedbackQuestionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="addFeedbackQuestionForm" method="POST"
                action="/unlockyourskills/feedback" enctype="multipart/form-data" novalidate>

                <input type="hidden" name="feedbackQuestionId" id="feedbackQuestionId">
                <input type="hidden" name="existingFeedbackQuestionMedia" id="existingFeedbackQuestionMedia">

                <div class="modal-header">
                    <h5 class="modal-title" id="addFeedbackQuestionModalLabel">
                        <?= Localization::translate('buttons_add_feedback_question'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="<?= Localization::translate('buttons_close'); ?>"></button>
                </div>
                <div class="modal-body">

                    <!-- Question Title + Upload -->
                    <div class="mb-3">
                        <label for="feedbackQuestionTitle"
                            class="form-label"><?= Localization::translate('feedback_question_title'); ?> <span
                                class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" id="feedbackQuestionTitle" name="feedbackQuestionTitle"
                                class="form-control" required aria-describedby="feedbackQuestionTitleHelp">
                            <label for="feedbackQuestionMedia" class="btn btn-outline-secondary mb-0"
                                title="<?= Localization::translate('upload_image_video_pdf'); ?>">
                                <i class="fas fa-upload"></i>
                            </label>
                            <input type="file" id="feedbackQuestionMedia" name="feedbackQuestionMedia"
                                accept="image/*,video/*,.pdf" class="d-none" />
                        </div>
                        <div id="feedbackQuestionPreview" class="preview-container question-preview mt-2"></div>
                        <div id="feedbackQuestionTitleHelp" class="form-text text-danger d-none">
                            <?= Localization::translate('validation_required'); ?></div>
                    </div>

                    <!-- Question Type -->
                    <div class="mb-3">
                        <label for="feedbackQuestionType"
                            class="form-label"><?= Localization::translate('feedback_question_type'); ?></label>
                        <select id="feedbackQuestionType" name="feedbackQuestionType" class="form-select" required>
                            <option value="multi_choice">
                                <?= Localization::translate('question_type_multi_choice'); ?></option>
                            <option value="checkbox"><?= Localization::translate('question_type_checkbox'); ?>
                            </option>
                            <option value="short_answer">
                                <?= Localization::translate('question_type_short_answer'); ?></option>
                            <option value="long_answer">
                                <?= Localization::translate('question_type_long_answer'); ?></option>
                            <option value="dropdown"><?= Localization::translate('question_type_dropdown'); ?>
                            </option>
                            <option value="upload"><?= Localization::translate('question_type_upload'); ?>
                            </option>
                            <option value="rating"><?= Localization::translate('question_type_rating'); ?>
                            </option>
                        </select>
                        <div class="form-text text-danger d-none" id="feedbackQuestionTypeError">
                            <?= Localization::translate('validation_required'); ?></div>
                    </div>

                    <!-- Options Wrapper -->
                    <div id="feedbackOptionsWrapper" class="mb-3"></div>

                    <!-- Rating Wrapper -->
                    <div id="feedbackRatingWrapper" class="mb-3 d-none">
                        <label class="form-label"><?= Localization::translate('feedback_rating_scale'); ?></label>
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <select id="feedbackRatingScale" name="feedbackRatingScale" class="form-select">
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <select id="feedbackRatingSymbol" name="feedbackRatingSymbol" class="form-select">
                                    <option value="star">⭐ <?= Localization::translate('symbol_star'); ?>
                                    </option>
                                    <option value="thumb">👍 <?= Localization::translate('symbol_thumb'); ?>
                                    </option>
                                    <option value="heart">❤️ <?= Localization::translate('symbol_heart'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="mb-3">
                        <label for="feedbackTags"><?= Localization::translate('tags_keywords'); ?> <span
                                class="text-danger">*</span></label>
                        <div class="tag-input-container form-control" aria-describedby="feedbackTagsHelp" role="list">
                            <span id="feedbackTagDisplay" role="listitem"></span>
                            <input type="text" id="feedbackTagInput"
                                placeholder="<?= Localization::translate('add_tag_placeholder'); ?>"
                                aria-label="<?= Localization::translate('add_tag'); ?>" />
                        </div>
                        <input type="hidden" name="feedbackTagList" id="feedbackTagList" />
                        <div id="feedbackTagsHelp" class="form-text text-danger d-none">
                            <?= Localization::translate('validation_required'); ?></div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit"
                        class="btn btn-success"><?= Localization::translate('buttons_submit_feedback_question'); ?></button>
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal"><?= Localization::translate('buttons_cancel'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>





<script src="public/js/feedback_question_validation.js"></script>
<script src="public/js/feedback_question.js"></script>
<!-- ✅ Feedback Question Delete Confirmations -->
<script src="public/js/modules/survey_confirmations.js"></script>

<?php include 'includes/footer.php'; ?>