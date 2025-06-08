<div?php // views/add_question.php ?>

    <?php include 'includes/header.php'; ?>
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>



    <div class="main-content">
        <div class="container add-question-container">

            <div class="back-arrow-container">
                <a href="index.php?controller=VLRController" class="back-link">
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
                            <button type="button" class="btn btn-sm btn-primary"
                                onclick="window.location.href='index.php?controller=QuestionController&action=add'"
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
                                        <a href="index.php?controller=QuestionController&action=edit&id=<?= $question['id']; ?>"
                                            class="btn theme-btn-primary"
                                            title="<?= Localization::translate('question_grid_edit'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>

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

    <script src="public/js/assessment_question.js"></script>

    <?php include 'includes/footer.php'; ?>