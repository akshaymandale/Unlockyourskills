<?php // views/add_survey.php ?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
  <div class="container add-survey-question-container">

    <div class="back-arrow-container">
      <a href="index.php?controller=VLRController&tab=survey" class="back-link">
        <i class="fas fa-arrow-left"></i>
      </a>
      <span class="divider-line"></span>
      <h1 class="page-title text-purple"><?= Localization::translate('survey_question_management_title'); ?></h1>
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

          <!-- Add Survey Question Button on the right -->
          <div class="col-md-auto">
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSurveyQuestionModal"
              title="<?= Localization::translate('buttons_add_survey_question'); ?>">
              <?= Localization::translate('buttons_add_survey_question'); ?>
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

          <!-- Import Survey Button under Add button -->
          <div class="col-md-auto">
            <button type="button" class="btn btn-sm btn-primary" id="importSurveyButton"
              onclick="window.location.href='index.php?controller=UserManagementController&action=import'"
              title="<?= Localization::translate('buttons_import_survey_tooltip'); ?>">
              <i class="fas fa-upload me-1"></i> <?= Localization::translate('buttons_import_survey'); ?>
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

    <!-- ✅ Survey Questions Grid View -->
    <div id="questionsContainer" class="fade-transition">
      <table class="table table-bordered" id="surveyQuestionGrid">
        <thead class="question-grid">
          <tr>
            <th><?= Localization::translate('survey_grid_title'); ?></th>
            <th><?= Localization::translate('survey_grid_type'); ?></th>
            <th><?= Localization::translate('survey_grid_tags'); ?></th>
            <th><?= Localization::translate('survey_grid_actions'); ?></th>
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
                  <button type="button" class="btn theme-btn-primary edit-question-btn" data-bs-toggle="modal"
                    data-bs-target="#addSurveyQuestionModal" data-mode="edit"
                    data-question='<?= htmlspecialchars(json_encode($question, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>'
                    data-options='<?= htmlspecialchars(json_encode($question['options'] ?? [], JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>'
                    title="<?= Localization::translate('survey_grid_edit'); ?>">
                    <i class="fas fa-edit"></i>
                  </button>

                  <a href="#" class="btn theme-btn-danger delete-survey-question"
                    data-id="<?= $question['id']; ?>"
                    data-title="<?= htmlspecialchars($question['question_text']); ?>"
                    title="<?= Localization::translate('survey_grid_delete'); ?>">
                    <i class="fas fa-trash-alt"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="text-center">
                <?= Localization::translate('survey_grid_no_questions_found'); ?>
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

<!-- ✅ Add Survey Question Modal -->
<div class="modal fade" id="addSurveyQuestionModal" tabindex="-1"
  aria-labelledby="addSurveyQuestionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form id="surveyQuestionForm" enctype="multipart/form-data" method="POST"
        action="index.php?controller=SurveyQuestionController&action=save">

        <!-- Hidden ID for editing -->
        <input type="hidden" name="surveyQuestionId" id="surveyQuestionId" value="">

        <div class="modal-header">
          <h5 class="modal-title" id="addSurveyQuestionModalLabel"><?= Localization::translate('buttons_add_survey_question'); ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= Localization::translate('buttons_close'); ?>"></button>
        </div>

        <div class="modal-body">
          <!-- Question Title + Upload -->
          <div class="mb-3">
            <label for="surveyQuestionTitle" class="form-label"><?= Localization::translate('survey_question_title'); ?>
              <span class="text-danger">*</span></label>
            <div class="d-flex align-items-center gap-3">
              <input type="text" id="surveyQuestionTitle" name="surveyQuestionTitle" class="form-control">
              <label for="surveyQuestionMedia" class="btn btn-outline-secondary mb-0"
                title="<?= Localization::translate('upload_image_video_pdf'); ?>">
                <i class="fas fa-upload"></i>
              </label>
              <input type="file" id="surveyQuestionMedia" name="surveyQuestionMedia"
                accept="image/*,video/*,.pdf" class="d-none">
              <input type="hidden" id="existingSurveyQuestionMedia" name="existingSurveyQuestionMedia"
                value="">
            </div>
            <div id="surveyQuestionPreview" class="preview-container question-preview mt-2"></div>
          </div>

          <!-- Question Type -->
          <div class="mb-3">
            <label for="surveyQuestionType" class="form-label"><?= Localization::translate('survey_question_type'); ?></label>
            <select id="surveyQuestionType" name="surveyQuestionType" class="form-select">
              <option value="multi_choice"><?= Localization::translate('question_type_multi_choice'); ?></option>
              <option value="checkbox"><?= Localization::translate('question_type_checkbox'); ?></option>
              <option value="short_answer"><?= Localization::translate('question_type_short_answer'); ?></option>
              <option value="long_answer"><?= Localization::translate('question_type_long_answer'); ?></option>
              <option value="dropdown"><?= Localization::translate('question_type_dropdown'); ?></option>
              <option value="upload"><?= Localization::translate('question_type_upload'); ?></option>
              <option value="rating"><?= Localization::translate('question_type_rating'); ?></option>
            </select>
          </div>

          <!-- Options (Dynamic) -->
          <div id="surveyOptionsWrapper" class="mb-3">
            <label class="form-label">Options</label>
            <div id="surveyOptionsList">
              <!-- Dynamic options will be inserted here by JavaScript -->
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addOptionBtn">Add Option</button>
          </div>

          <!-- Rating -->
          <div id="ratingWrapper" class="mb-3 d-none">
            <label class="form-label"><?= Localization::translate('survey_rating_scale'); ?></label>
            <div class="row g-2">
              <div class="col">
                <select name="ratingScale" id="ratingScale" class="form-select">
                  <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="col">
                <select name="ratingSymbol" id="ratingSymbol" class="form-select">
                  <option value="star">⭐ <?= Localization::translate('symbol_star'); ?></option>
                  <option value="thumb">👍 <?= Localization::translate('symbol_thumb'); ?></option>
                  <option value="heart">❤️ <?= Localization::translate('symbol_heart'); ?></option>
                </select>
              </div>
            </div>
          </div>

          <!-- Tags -->
          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label for="tags"><?= Localization::translate('tags_keywords'); ?>
                  <span class="text-danger">*</span></label>
                <div class="tag-input-container form-control">
                  <span id="tagDisplay"></span>
                  <input type="text" id="tagInput"
                    placeholder="<?= Localization::translate('add_tag_placeholder'); ?>">
                </div>
                <input type="hidden" name="tagList" id="tagList">
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <!-- Submit Button -->
          <button type="submit" class="btn btn-success" id="surveyQuestionSubmitBtn"><?= Localization::translate('buttons_submit_survey_question'); ?></button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= Localization::translate('buttons_cancel'); ?></button>
        </div>

      </form>
    </div>
  </div>
</div>



<script src="public/js/survey_question_validation.js"></script>
<script src="public/js/survey_question.js"></script>
<?php include 'includes/footer.php'; ?>