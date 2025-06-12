<div?php // views/add_question.php ?>

    <?php include 'includes/header.php'; ?>
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>



    <div class="main-content">
        <div class="container add-question-container">

            <div class="back-arrow-container">
                <a href="index.php?controller=VLRController&tab=assessment" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <span class="divider-line"></span>
                <h1 class="page-title text-purple"><?= Localization::translate('question_management_title'); ?></h1>
            </div>
            <!-- ✅ Filters & Search Section -->
            <div class="filter-section">
                <div class="container-fluid mb-3">
                    <!-- First Row: Main Controls -->
                    <div class="row justify-content-between align-items-center g-3 mb-2">

                        <!-- Filter Dropdowns on the left -->
                        <div class="col-md-auto">
                            <div class="row g-2">
                                <div class="col-auto">
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
                                <div class="col-auto">
                                    <select class="form-select form-select-sm" id="difficultyFilter">
                                        <option value=""><?= Localization::translate('filters_difficulty'); ?></option>
                                        <?php if (!empty($uniqueDifficultyLevels)): ?>
                                            <?php foreach ($uniqueDifficultyLevels as $level): ?>
                                                <option value="<?= htmlspecialchars($level); ?>">
                                                    <?= htmlspecialchars($level); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <input type="text" class="form-control form-control-sm" id="tagsFilter"
                                        placeholder="<?= Localization::translate('filters_tags'); ?>"
                                        title="Type to filter by tags">
                                </div>
                            </div>
                        </div>

                        <!-- Search Bar in the middle -->
                        <div class="col-md-auto">
                            <div class="input-group input-group-sm">
                                <input type="text" id="searchInput" class="form-control"
                                    placeholder="<?= Localization::translate('filters_search_placeholder'); ?>"
                                    title="<?= Localization::translate('filters_search'); ?>">
                                <button type="button" id="searchButton" class="btn btn-outline-secondary"
                                    title="<?= Localization::translate('filters_search'); ?>">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Add Question Button on the right -->
                        <div class="col-md-auto">
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAssessmentQuestionModal"
                                title="<?= Localization::translate('buttons_add_question_tooltip'); ?>">
                                <?= Localization::translate('buttons_add_question'); ?>
                            </button>
                        </div>

                    </div>

                    <!-- Second Row: Secondary Controls -->
                    <div class="row justify-content-between align-items-center g-3">

                        <!-- Clear Filters Button under filters -->
                        <div class="col-md-auto">
                            <button type="button" class="btn btn-sm btn-clear-filters" id="clearFiltersBtn"
                                title="Clear all filters">
                                <i class="fas fa-times me-1"></i> Clear Filters
                            </button>
                        </div>

                        <!-- Empty middle space -->
                        <div class="col-md-auto">
                        </div>

                        <!-- Import Button under Add button -->
                        <div class="col-md-auto">
                            <button type="button" class="btn btn-sm btn-primary"
                                onclick="window.location.href='index.php?controller=UserManagementController&action=import'"
                                title="<?= Localization::translate('buttons_import_user_tooltip'); ?>">
                                <i class="fas fa-upload me-1"></i> <?= Localization::translate('buttons_import_user'); ?>
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Search Results Info -->
            <div id="searchResultsInfo" class="search-results-info" style="display: none;">
                <i class="fas fa-info-circle"></i>
                <span id="resultsText"></span>
            </div>

            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="text-center" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading questions...</p>
            </div>

            <!-- ✅ Questions Grid View -->
            <div id="questionsContainer" class="fade-transition">
                <table class="table table-bordered">
                    <thead class="question-grid">
                        <tr>
                            <th><?= Localization::translate('question_grid_title'); ?></th>
                            <th><?= Localization::translate('question_grid_type'); ?></th>
                            <th><?= Localization::translate('question_grid_difficulty'); ?></th>
                            <th><?= Localization::translate('question_grid_tags'); ?></th>
                            <th><?= Localization::translate('question_grid_actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="questionsTableBody">
                        <?php if (!empty($questions)): ?>
                            <?php foreach ($questions as $question): ?>
                                <tr>
                                    <td><?= htmlspecialchars($question['title']); ?></td>
                                    <td><?= htmlspecialchars($question['type']); ?></td>
                                    <td><?= htmlspecialchars($question['difficulty']); ?></td>
                                    <td><?= htmlspecialchars($question['tags']); ?></td>
                                    <td>
                                        <button type="button" class="btn theme-btn-primary edit-question-btn"
                                            data-question-id="<?= $question['id']; ?>"
                                            title="<?= Localization::translate('question_grid_edit'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <a href="index.php?controller=QuestionController&action=delete&id=<?= $question['id']; ?>"
                                            class="btn theme-btn-danger"
                                            title="<?= Localization::translate('question_grid_delete'); ?>"
                                            onclick="return confirm('<?= Localization::translate('question_grid_delete_confirm'); ?>');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <?= Localization::translate('question_grid_no_questions_found'); ?>
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

    <!-- ✅ Add Assessment Question Modal -->
    <div class="modal fade" id="addAssessmentQuestionModal" tabindex="-1"
        aria-labelledby="addAssessmentQuestionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form id="assessmentQuestionForm" enctype="multipart/form-data" method="POST"
                    action="index.php?controller=QuestionController&action=save">

                    <!-- Hidden ID for editing -->
                    <input type="hidden" name="questionId" id="questionId" value="">

                    <div class="modal-header">
                        <h5 class="modal-title" id="addAssessmentQuestionModalLabel"><?= Localization::translate('buttons_add_question'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= Localization::translate('buttons_close'); ?>"></button>
                    </div>

                    <div class="modal-body">
                        <!-- Question Text -->
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="questionText" class="form-label"><?= Localization::translate('question_label'); ?>
                                    <span class="text-danger">*</span></label>
                                <textarea id="questionText" name="questionText" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="col-md-4">
                                <label for="tagsInput" class="form-label"><?= Localization::translate('tags_keywords'); ?> <span
                                        class="text-danger">*</span></label>
                                <div id="tagsContainer" class="tag-container"></div>
                                <input type="text" id="tagsInput" class="form-control"
                                    placeholder="<?= Localization::translate('type_and_press_enter'); ?>">
                                <input type="hidden" name="tags" id="tagsHidden" value="">
                            </div>
                        </div>

                        <!-- Skills, Level, Marks -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="skillsInput" class="form-label"><?= Localization::translate('competency_skills'); ?></label>
                                <div id="skillsContainer" class="tag-container"></div>
                                <input type="text" id="skillsInput" class="form-control"
                                    placeholder="<?= Localization::translate('type_and_press_enter'); ?>">
                                <input type="hidden" name="skills" id="skillsHidden" value="">
                            </div>

                            <div class="col-md-4">
                                <label for="level" class="form-label"><?= Localization::translate('question_level'); ?></label>
                                <select id="level" name="level" class="form-select">
                                    <?php foreach (['Low', 'Medium', 'Hard'] as $level): ?>
                                        <option value="<?= $level ?>">
                                            <?= Localization::translate(strtolower($level)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="marks" class="form-label"><?= Localization::translate('marks_per_question'); ?></label>
                                <select id="marks" name="marks" class="form-select">
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?= $i ?>" <?= $i == 1 ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Status, Question Type, Answer Count -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label d-block"><?= Localization::translate('status'); ?></label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="status" id="active" value="Active" checked>
                                    <label class="form-check-label" for="active"><?= Localization::translate('active'); ?></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="status" id="inactive" value="Inactive">
                                    <label class="form-check-label" for="inactive"><?= Localization::translate('inactive'); ?></label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label d-block"><?= Localization::translate('question_type'); ?></label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="questionFormType" id="objective" value="Objective" checked>
                                    <label class="form-check-label" for="objective"><?= Localization::translate('objective'); ?></label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="questionFormType" id="subjective" value="Subjective">
                                    <label class="form-check-label" for="subjective"><?= Localization::translate('subjective'); ?></label>
                                </div>
                            </div>

                            <div class="col-md-4 objective-only">
                                <label for="answerCount" class="form-label"><?= Localization::translate('how_many_answer_options'); ?></label>
                                <select id="answerCount" name="answerCount" class="form-select">
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?= $i ?>" <?= $i == 1 ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Media Type and Upload -->
                        <div class="row mb-3 objective-only">
                            <div class="col-md-6">
                                <label for="questionMediaType" class="form-label"><?= Localization::translate('question_media_type'); ?></label>
                                <select id="questionMediaType" name="questionMediaType" class="form-select">
                                    <?php foreach (['text', 'image', 'audio', 'video'] as $type): ?>
                                        <option value="<?= $type ?>">
                                            <?= Localization::translate($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 d-none" id="mediaUploadContainer">
                                <label for="mediaFile" class="form-label"><?= Localization::translate('upload_media'); ?> <span class="text-danger">*</span></label>
                                <input type="file" id="mediaFile" name="mediaFile" class="form-control" accept="">
                                <div id="mediaPreview" class="mt-2"></div>
                            </div>
                        </div>

                        <!-- Answer Options -->
                        <div id="answerOptionsContainer" class="objective-only">
                            <h6><?= Localization::translate('answer_options'); ?></h6>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <div class="mb-3 option-block <?= $i > 1 ? 'd-none' : '' ?>" data-index="<?= $i ?>">
                                    <label for="option_<?= $i ?>" class="form-label"><?= Localization::translate('option'); ?> <?= $i ?> <span class="text-danger">*</span></label>
                                    <textarea id="option_<?= $i ?>" name="options[<?= $i ?>][text]" rows="2" maxlength="500" class="form-control option-textarea"></textarea>
                                    <small class="text-muted"><span id="charCount_<?= $i ?>">0</span>/500</small>

                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="options[<?= $i ?>][correct]" id="correct_<?= $i ?>">
                                        <label class="form-check-label" for="correct_<?= $i ?>"><?= Localization::translate('correct_answer'); ?></label>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success" id="assessmentQuestionSubmitBtn"><?= Localization::translate('submit'); ?></button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= Localization::translate('cancel'); ?></button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="public/js/assessment_question_validation.js"></script>
    <script src="public/js/assessment_question.js"></script>

    <?php include 'includes/footer.php'; ?>