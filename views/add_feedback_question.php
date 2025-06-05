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
            <h1 class="page-title text-purple"><?= Localization::translate('feedback_question_management_title'); ?>
            </h1>
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

                    <!-- Add Feedback Question Button in the middle -->
                    <div class="col-md-auto">
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#addFeedbackQuestionModal"
                            title="<?= Localization::translate('buttons_add_feedback_question'); ?>">
                            <?= Localization::translate('buttons_add_feedback_question'); ?>
                        </button>
                    </div>

                    <!-- Clear Filters Button -->
                    <div class="col-md-auto">
                        <button type="button" class="btn btn-sm btn-clear-filters" id="clearFiltersBtn"
                            title="Clear all filters">
                            <i class="fas fa-times me-1"></i> Clear Filters
                        </button>
                    </div>

                    <!-- Import Feedback Button on the right with icon -->
                    <div class="col-md-auto">
                        <button type="button" class="btn btn-sm btn-primary" id="importFeedbackButton"
                            onclick="window.location.href='index.php?controller=UserManagementController&action=import'"
                            title="<?= Localization::translate('buttons_import_feedback_tooltip'); ?>">
                            <i class="fas fa-upload me-1"></i> <?= Localization::translate('buttons_import_feedback'); ?>
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
                                    <button type="button" class="btn btn-sm theme-btn-primary edit-question-btn"
                                        data-question-id="<?= $question['id']; ?>"
                                        title="<?= Localization::translate('feedback_grid_edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
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
            <form id="feedbackQuestionForm" enctype="multipart/form-data" method="POST"
                action="index.php?controller=FeedbackQuestionController&action=save" novalidate>

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

<script>
  // Global variables to track current state
  let currentPage = 1;
  let currentSearch = '';
  let currentFilters = {
    question_type: '',
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
    document.getElementById('tagsFilter').value = '';
    currentFilters = {
      question_type: '',
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
    formData.append('controller', 'FeedbackQuestionController');
    formData.append('action', 'ajaxSearch');
    formData.append('page', currentPage);
    formData.append('search', currentSearch);
    formData.append('type', currentFilters.question_type);
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
          <td colspan="4" class="no-results-message">
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
        <td>${escapeHtml(question.tags)}</td>
        <td>
          <button type="button" class="btn btn-sm theme-btn-primary edit-question-btn"
            data-question-id="${question.id}"
            title="<?= Localization::translate('feedback_grid_edit'); ?>">
            <i class="fas fa-edit"></i>
          </button>
          <a href="index.php?controller=FeedbackQuestionController&action=delete&id=${question.id}"
            class="btn btn-sm theme-btn-danger"
            title="<?= Localization::translate('feedback_grid_delete'); ?>"
            onclick="return confirm('<?= Localization::translate('feedback_grid_delete_confirm'); ?>');">
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

    if (currentSearch || currentFilters.question_type || currentFilters.tags) {
      let infoText = `Showing ${totalQuestions} result(s)`;

      if (currentSearch) {
        infoText += ` for search: "<strong>${escapeHtml(currentSearch)}</strong>"`;
      }

      if (currentFilters.question_type || currentFilters.tags) {
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

<script src="public/js/feedback_question_validation.js"></script>
<script src="public/js/feedback_question.js"></script>

<?php include 'includes/footer.php'; ?>