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
            <div class="container-fluid mb-3">
                <div class="row justify-content-between align-items-center g-3">

                    <!-- Filter Multiselect on the left -->
                    <div class="col-md-auto">
                        <select class="form-select form-select-sm" multiple
                            title="<?= Localization::translate('filters_select_options'); ?>">
                            <option value="question_type"><?= Localization::translate('filters_question_type'); ?>
                            </option>
                            <option value="difficulty"><?= Localization::translate('filters_difficulty'); ?></option>
                            <option value="tags"><?= Localization::translate('filters_tags'); ?></option>
                            <option value="created_by"><?= Localization::translate('filters_created_by'); ?></option>
                        </select>
                    </div>

                    <!-- Search Bar in the middle -->
                    <div class="col-md-auto">
                        <div class="input-group input-group-sm">
                            <input type="text" id="searchQuestionInput" class="form-control"
                                placeholder="<?= Localization::translate('filters_search_placeholder'); ?>"
                                title="<?= Localization::translate('filters_search'); ?>">
                            <button type="submit" id="searchQuestionButton" class="btn btn-outline-secondary"
                                title="<?= Localization::translate('filters_search'); ?>">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Add Question Button in the middle -->
                    <div class="col-md-auto">
                        <button class="btn btn-sm btn-primary"
                            onclick="window.location.href='index.php?controller=QuestionController&action=add'"
                            title="<?= Localization::translate('buttons_add_question_tooltip'); ?>">
                            <?= Localization::translate('buttons_add_question'); ?>
                        </button>
                    </div>

                    <!-- Import Button on the right with icon -->
                    <!-- Import User Button with Icon -->
                    <div class="col-md-auto">
                        <button class="btn btn-sm btn-primary"
                            onclick="window.location.href='index.php?controller=UserManagementController&action=import'"
                            title="<?= Localization::translate('buttons_import_user_tooltip'); ?>">
                            <i class="fas fa-upload me-1"></i> <?= Localization::translate('buttons_import_user'); ?>
                        </button>
                    </div>

                </div>
            </div>


            <!-- ✅ Questions Grid View -->
            <table class="table table-bordered">
                <thead class="question-grid">
                    <tr>
                        <th><?= Localization::translate('question_grid_title'); ?></th>
                        <th><?= Localization::translate('question_grid_type'); ?></th>
                        <th><?= Localization::translate('question_grid_difficulty'); ?></th>
                        <th><?= Localization::translate('question_grid_tags'); ?></th>
                        <th><?= Localization::translate('question_grid_created_by'); ?></th>
                        <th><?= Localization::translate('question_grid_actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($questions)): ?>
                        <?php foreach ($questions as $question): ?>
                            <tr>
                                <td><?= htmlspecialchars($question['title']); ?></td>
                                <td><?= htmlspecialchars($question['type']); ?></td>
                                <td><?= htmlspecialchars($question['difficulty']); ?></td>
                                <td><?= htmlspecialchars($question['tags']); ?></td>
                                <td><?= htmlspecialchars($question['created_by']); ?></td>
                                <td>
                                    <a href="index.php?controller=QuestionController&action=edit&id=<?= $question['id']; ?>"
                                        class="btn btn-sm theme-btn-primary"
                                        title="<?= Localization::translate('question_grid_edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <a href="index.php?controller=QuestionController&action=delete&id=<?= $question['id']; ?>"
                                        class="btn btn-sm theme-btn-danger"
                                        title="<?= Localization::translate('question_grid_delete'); ?>"
                                        onclick="return confirm('<?= Localization::translate('question_grid_delete_confirm'); ?>');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                <?= Localization::translate('question_grid_no_questions_found'); ?>
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
                                    <a class="page-link" href="index.php?controller=QuestionController&page=<?= $page - 1; ?>">«
                                        <?= Localization::translate('pagination_prev'); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link"
                                        href="index.php?controller=QuestionController&page=<?= $i; ?>"><?= $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="index.php?controller=QuestionController&page=<?= $page + 1; ?>"><?= Localization::translate('pagination_next'); ?>
                                        »</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>