<div?php // views/add_question.php ?>

    <?php include 'includes/header.php'; ?>
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <style>
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .filter-section .form-select,
        .filter-section .form-control {
            min-width: 150px;
        }

        .question-grid th {
            background-color: #6a0dad;
            color: white;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
        }

        .question-grid td {
            vertical-align: middle;
        }

        .btn-clear-filters {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }

        .btn-clear-filters:hover {
            background-color: #5a6268;
            border-color: #545b62;
            color: white;
        }

        .search-results-info {
            font-size: 0.9em;
            color: #6c757d;
            margin-bottom: 15px;
        }

        #loadingIndicator {
            padding: 40px 0;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        .fade-transition {
            transition: opacity 0.3s ease-in-out;
        }

        .filter-section .form-select:focus,
        .filter-section .form-control:focus {
            border-color: #6a0dad;
            box-shadow: 0 0 0 0.2rem rgba(106, 13, 173, 0.25);
        }

        .page-link {
            color: #6a0dad;
        }

        .page-link:hover {
            color: #5a0b8a;
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }

        .page-item.active .page-link {
            background-color: #6a0dad;
            border-color: #6a0dad;
        }

        .no-results-message {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .no-results-message i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #dee2e6;
        }
    </style>

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
                    <div class="row justify-content-between align-items-center g-3">

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
                                    <select class="form-select form-select-sm" id="tagsFilter">
                                        <option value=""><?= Localization::translate('filters_tags'); ?></option>
                                        <?php if (!empty($uniqueTags)): ?>
                                            <?php foreach ($uniqueTags as $tag): ?>
                                                <option value="<?= htmlspecialchars($tag); ?>">
                                                    <?= htmlspecialchars($tag); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
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

                        <!-- Add Question Button in the middle -->
                        <div class="col-md-auto">
                            <button type="button" class="btn btn-sm btn-primary"
                                onclick="window.location.href='index.php?controller=QuestionController&action=add'"
                                title="<?= Localization::translate('buttons_add_question_tooltip'); ?>">
                                <?= Localization::translate('buttons_add_question'); ?>
                            </button>
                        </div>

                        <!-- Clear Filters Button -->
                        <div class="col-md-auto">
                            <button type="button" class="btn btn-sm btn-clear-filters" id="clearFiltersBtn"
                                title="Clear all filters">
                                <i class="fas fa-times me-1"></i> Clear Filters
                            </button>
                        </div>

                        <!-- Import Button on the right with icon -->
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

    <script>
        // Global variables to track current state
        let currentPage = 1;
        let currentSearch = '';
        let currentFilters = {
            question_type: '',
            difficulty: '',
            tags: ''
        };

        // Debounce function for search input
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Initialize event listeners when document is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            const searchButton = document.getElementById('searchButton');

            searchButton.addEventListener('click', performSearch);
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });

            // Add debounced search on input
            const debouncedSearch = debounce(performSearch, 500);
            searchInput.addEventListener('input', debouncedSearch);

            // Filter functionality
            document.getElementById('questionTypeFilter').addEventListener('change', applyFilters);
            document.getElementById('difficultyFilter').addEventListener('change', applyFilters);
            document.getElementById('tagsFilter').addEventListener('change', applyFilters);

            // Clear filters functionality
            document.getElementById('clearFiltersBtn').addEventListener('click', clearAllFilters);

            // Pagination functionality
            document.addEventListener('click', function(e) {
                if (e.target.matches('.page-link[data-page]')) {
                    e.preventDefault();
                    const page = parseInt(e.target.getAttribute('data-page'));
                    loadQuestions(page);
                }
            });
        });

        function performSearch() {
            currentSearch = document.getElementById('searchInput').value.trim();
            currentPage = 1; // Reset to first page
            loadQuestions();
        }

        function applyFilters() {
            currentFilters.question_type = document.getElementById('questionTypeFilter').value;
            currentFilters.difficulty = document.getElementById('difficultyFilter').value;
            currentFilters.tags = document.getElementById('tagsFilter').value;
            currentPage = 1; // Reset to first page
            loadQuestions();
        }

        function clearAllFilters() {
            // Clear search
            document.getElementById('searchInput').value = '';
            currentSearch = '';

            // Clear filters
            document.getElementById('questionTypeFilter').value = '';
            document.getElementById('difficultyFilter').value = '';
            document.getElementById('tagsFilter').value = '';
            currentFilters = {
                question_type: '',
                difficulty: '',
                tags: ''
            };

            currentPage = 1;
            loadQuestions();
        }

        function loadQuestions(page = currentPage) {
            currentPage = page;

            // Show loading indicator
            document.getElementById('loadingIndicator').style.display = 'block';
            document.getElementById('questionsContainer').style.display = 'none';
            document.getElementById('paginationContainer').style.display = 'none';

            // Prepare data for AJAX request
            const formData = new FormData();
            formData.append('controller', 'QuestionController');
            formData.append('action', 'ajaxSearch');
            formData.append('page', currentPage);
            formData.append('search', currentSearch);
            formData.append('question_type', currentFilters.question_type);
            formData.append('difficulty', currentFilters.difficulty);
            formData.append('tags', currentFilters.tags);

            // Make AJAX request
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateQuestionsTable(data.questions);
                    updatePagination(data.pagination);
                    updateSearchInfo(data.totalQuestions);
                } else {
                    console.error('Error loading questions:', data.message);
                    alert('Error loading questions: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                alert('Network error. Please try again.');
            })
            .finally(() => {
                // Hide loading indicator
                document.getElementById('loadingIndicator').style.display = 'none';
                document.getElementById('questionsContainer').style.display = 'block';
                document.getElementById('paginationContainer').style.display = 'block';
            });
        }

        function updateQuestionsTable(questions) {
            const tbody = document.getElementById('questionsTableBody');
            tbody.innerHTML = '';

            if (questions.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="no-results-message">
                            <i class="fas fa-search"></i>
                            <div>
                                <h5>No questions found</h5>
                                <p>Try adjusting your search terms or filters</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            questions.forEach(question => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${escapeHtml(question.title)}</td>
                    <td>${escapeHtml(question.type)}</td>
                    <td>${escapeHtml(question.difficulty)}</td>
                    <td>${escapeHtml(question.tags)}</td>
                    <td>
                        <a href="index.php?controller=QuestionController&action=edit&id=${question.id}"
                            class="btn btn-sm theme-btn-primary"
                            title="<?= Localization::translate('question_grid_edit'); ?>">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="index.php?controller=QuestionController&action=delete&id=${question.id}"
                            class="btn btn-sm theme-btn-danger"
                            title="<?= Localization::translate('question_grid_delete'); ?>"
                            onclick="return confirm('<?= Localization::translate('question_grid_delete_confirm'); ?>');">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function updatePagination(pagination) {
            const container = document.getElementById('paginationContainer');

            // Only show pagination if there are more than 10 total questions
            if (pagination.totalQuestions <= 10) {
                if (pagination.totalQuestions > 0) {
                    // Show total count when no pagination needed
                    const plural = pagination.totalQuestions !== 1 ? 's' : '';
                    container.innerHTML = `
                        <div class="text-center text-muted small">
                            Showing all ${pagination.totalQuestions} question${plural}
                        </div>
                    `;
                    container.style.display = 'block';
                } else {
                    container.style.display = 'none';
                }
                return;
            }

            container.style.display = 'block';

            // Create pagination navigation
            let paginationHTML = '<nav><ul class="pagination justify-content-center" id="paginationList">';

            // Previous button
            if (pagination.currentPage > 1) {
                paginationHTML += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${pagination.currentPage - 1}">«
                            <?= Localization::translate('pagination_prev'); ?></a>
                    </li>
                `;
            }

            // Page numbers
            for (let i = 1; i <= pagination.totalPages; i++) {
                const activeClass = i === pagination.currentPage ? 'active' : '';
                paginationHTML += `
                    <li class="page-item ${activeClass}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }

            // Next button
            if (pagination.currentPage < pagination.totalPages) {
                paginationHTML += `
                    <li class="page-item">
                        <a class="page-link" href="#" data-page="${pagination.currentPage + 1}"><?= Localization::translate('pagination_next'); ?>
                            »</a>
                    </li>
                `;
            }

            paginationHTML += '</ul></nav>';
            container.innerHTML = paginationHTML;
        }

        function updateSearchInfo(totalQuestions) {
            const searchInfo = document.getElementById('searchResultsInfo');
            const resultsText = document.getElementById('resultsText');

            if (currentSearch || currentFilters.question_type || currentFilters.difficulty || currentFilters.tags) {
                let infoText = `Showing ${totalQuestions} result(s)`;

                if (currentSearch) {
                    infoText += ` for search: "<strong>${escapeHtml(currentSearch)}</strong>"`;
                }

                if (currentFilters.question_type || currentFilters.difficulty || currentFilters.tags) {
                    infoText += ' with filters applied';
                }

                resultsText.innerHTML = infoText;
                searchInfo.style.display = 'block';
            } else {
                searchInfo.style.display = 'none';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>

    <?php include 'includes/footer.php'; ?>