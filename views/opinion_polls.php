<?php
// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php?controller=LoginController');
    exit();
}

require_once 'core/UrlHelper.php';
require_once 'includes/permission_helper.php';
$canCreateOpinionPoll = canCreate('opinion_polls');

$currentUser = $_SESSION['user'];
$systemRole = $_SESSION['user']['system_role'] ?? '';
$canCreateGlobal = in_array($systemRole, ['super_admin', 'admin']);
$canManageAll = in_array($systemRole, ['super_admin', 'admin']);
?>

<?php include 'includes/header.php'; ?>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content" data-opinion-poll-page="true">
    <div class="container add-question-container">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="<?= UrlHelper::url('manage-portal') ?>#social" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-poll me-2"></i>
                <?= Localization::translate('opinion_poll_management'); ?>
            </h1>
        </div>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= UrlHelper::url('dashboard') ?>"><?= Localization::translate('dashboard'); ?></a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= UrlHelper::url('manage-portal') ?>"><?= Localization::translate('manage_portal'); ?></a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= UrlHelper::url('manage-portal') ?>#social"><?= Localization::translate('social'); ?></a>
                </li>
                <li class="breadcrumb-item active" aria-current="page"><?= Localization::translate('opinion_polls'); ?></li>
            </ol>
        </nav>

        <!-- Page Description -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">Create and manage opinion polls for your organization</p>
                    </div>
                    <?php if ($canCreateOpinionPoll): ?>
                    <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#createPollModal">
                        <i class="fas fa-plus me-2"></i>Create Opinion Poll
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Filters and Search -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Search -->
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="searchInput"
                                           placeholder="Search opinion polls...">
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="paused">Paused</option>
                                    <option value="ended">Ended</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>

                            <!-- Type Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="typeFilter">
                                    <option value="">All Types</option>
                                    <option value="single_choice">Single Choice</option>
                                    <option value="multiple_choice">Multiple Choice</option>
                                </select>
                            </div>

                            <!-- Audience Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="audienceFilter">
                                    <option value="">All Audience</option>
                                    <option value="global">Global</option>
                                    <option value="group_specific">Group Specific</option>
                                </select>
                            </div>

                            <!-- Date Range -->
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-secondary w-100" id="dateRangeBtn">
                                    <i class="fas fa-calendar me-2"></i>Date Range
                                </button>
                            </div>

                            <!-- Clear All Filters -->
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100" id="clearAllFiltersBtn" title="Clear all filters">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Date Range Inputs (Hidden by default) -->
                        <div class="row mt-3 d-none" id="dateRangeInputs">
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" class="form-control" id="dateFrom">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" class="form-control" id="dateTo">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn theme-btn-secondary d-block" id="applyDateFilter">
                                    Apply
                                </button>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-outline-secondary d-block" id="clearDateFilter">
                                    Clear Date
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Info -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="search-results-info">
                    <i class="fas fa-info-circle"></i>
                    <span id="resultsInfo">Loading opinion polls...</span>
                </div>
            </div>
        </div>

        <!-- Opinion Polls Grid -->
        <div class="row" id="pollsGrid">
            <!-- Opinion polls will be loaded here via AJAX -->
        </div>

        <!-- Pagination -->
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Opinion polls pagination">
                    <ul class="pagination justify-content-center" id="paginationContainer">
                        <!-- Pagination will be generated here -->
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div class="row" id="loadingSpinner" style="display: none;">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading opinion polls...</p>
            </div>
        </div>

        <!-- No Results -->
        <div class="row" id="noResults" style="display: none;">
            <div class="col-12 text-center py-5">
                <i class="fas fa-poll fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No opinion polls found</h5>
                <p class="text-muted">Try adjusting your search criteria or create a new opinion poll.</p>
                <?php if ($canCreateOpinionPoll): ?>
                <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#createPollModal">
                    <i class="fas fa-plus me-2"></i>Create First Opinion Poll
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Poll Modal -->
<div class="modal fade" id="createPollModal" tabindex="-1" aria-labelledby="createPollModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPollModalLabel">
                    <i class="fas fa-poll me-2"></i>Create New Opinion Poll
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createPollForm" method="POST">
                <div class="modal-body">
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label for="pollTitle" class="form-label">Poll Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="pollTitle" name="title"
                                placeholder="Enter poll title..." maxlength="255">
                            <div class="form-text">
                                <span id="titleCharCount">0</span>/255 characters
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="pollType" class="form-label">Poll Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="pollType" name="type">
                                <option value="">Select type...</option>
                                <option value="single_choice">Single Choice</option>
                                <option value="multiple_choice">Multiple Choice</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="pollDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="pollDescription" name="description" rows="3"
                                placeholder="Enter poll description (optional)..." maxlength="1000"></textarea>
                            <div class="form-text">
                                <span id="descriptionCharCount">0</span>/1000 characters
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Target Audience -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="targetAudience" class="form-label">Target Audience <span class="text-danger">*</span></label>
                            <select class="form-select" id="targetAudience" name="target_audience">
                                <option value="">Select audience...</option>
                                <option value="global">Global (All Users)</option>
                                <option value="group_specific">Group Specific</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="startDatetime" class="form-label">Start Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="startDatetime" name="start_datetime">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="endDatetime" class="form-label">End Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="endDatetime" name="end_datetime">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Custom Field Selection (for Group Specific) -->
                    <div class="row mb-4" id="customFieldSelection" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="customFieldId" class="form-label">Select Custom Field <span class="text-danger">*</span></label>
                            <select class="form-select" id="customFieldId" name="custom_field_id">
                                <option value="">Select custom field...</option>
                                <?php foreach ($customFields as $field): ?>
                                    <option value="<?= $field['id']; ?>" data-options="<?= htmlspecialchars(json_encode($field['field_options'] ?? [])); ?>">
                                        <?= htmlspecialchars($field['field_label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="customFieldValue" class="form-label">Select Custom Field Value <span class="text-danger">*</span></label>
                            <select class="form-select" id="customFieldValue" name="custom_field_value">
                                <option value="">Select value...</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Poll Settings -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="showResults" class="form-label">Show Results <span class="text-danger">*</span></label>
                            <select class="form-select" id="showResults" name="show_results">
                                <option value="">Select when to show results...</option>
                                <option value="after_vote">After Vote</option>
                                <option value="after_end">After Poll Ends</option>
                                <option value="admin_only">Admin Only</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="allowAnonymous" name="allow_anonymous">
                                <label class="form-check-label" for="allowAnonymous">
                                    Allow Anonymous Voting
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="allowVoteChange" name="allow_vote_change">
                                <label class="form-check-label" for="allowVoteChange">
                                    Allow Vote Changes
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Questions Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Poll Questions</h6>
                            <button type="button" class="btn btn-sm theme-btn-secondary" id="addQuestionBtn">
                                <i class="fas fa-plus me-1"></i>Add Question
                            </button>
                        </div>
                        <div id="questionsContainer">
                            <!-- Questions will be added dynamically -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn theme-btn-primary">
                        <i class="fas fa-poll me-2"></i>Create Opinion Poll
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Poll Modal -->
<div class="modal fade" id="editPollModal" tabindex="-1" aria-labelledby="editPollModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPollModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Opinion Poll
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPollForm" method="POST">
                <input type="hidden" name="poll_id" id="edit_poll_id">
                <input type="hidden" name="controller" value="OpinionPollController">
                <input type="hidden" name="action" value="update">
                <div class="modal-body">
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label for="editPollTitle" class="form-label">Poll Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editPollTitle" name="title"
                                placeholder="Enter poll title..." maxlength="255">
                            <div class="form-text">
                                <span id="editTitleCharCount">0</span>/255 characters
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="editPollType" class="form-label">Poll Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="editPollType" name="type">
                                <option value="">Select type...</option>
                                <option value="single_choice">Single Choice</option>
                                <option value="multiple_choice">Multiple Choice</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="editPollDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editPollDescription" name="description" rows="3"
                                placeholder="Enter poll description (optional)..." maxlength="1000"></textarea>
                            <div class="form-text">
                                <span id="editDescriptionCharCount">0</span>/1000 characters
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Target Audience -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="editTargetAudience" class="form-label">Target Audience <span class="text-danger">*</span></label>
                            <select class="form-select" id="editTargetAudience" name="target_audience">
                                <option value="">Select audience...</option>
                                <option value="global">Global (All Users)</option>
                                <option value="group_specific">Group Specific</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="editStartDatetime" class="form-label">Start Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="editStartDatetime" name="start_datetime">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="editEndDatetime" class="form-label">End Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="editEndDatetime" name="end_datetime">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Custom Field Selection (for Group Specific) - Edit Modal -->
                    <div class="row mb-4" id="editCustomFieldSelection" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="editCustomFieldId" class="form-label">Select Custom Field <span class="text-danger">*</span></label>
                            <select class="form-select" id="editCustomFieldId" name="custom_field_id">
                                <option value="">Select custom field...</option>
                                <?php foreach ($customFields as $field): ?>
                                    <option value="<?= $field['id']; ?>" data-options="<?= htmlspecialchars(json_encode($field['field_options'] ?? [])); ?>">
                                        <?= htmlspecialchars($field['field_label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editCustomFieldValue" class="form-label">Select Custom Field Value <span class="text-danger">*</span></label>
                            <select class="form-select" id="editCustomFieldValue" name="custom_field_value">
                                <option value="">Select value...</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Poll Settings -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="editShowResults" class="form-label">Show Results <span class="text-danger">*</span></label>
                            <select class="form-select" id="editShowResults" name="show_results">
                                <option value="">Select when to show results...</option>
                                <option value="after_vote">After Vote</option>
                                <option value="after_end">After Poll Ends</option>
                                <option value="admin_only">Admin Only</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="editAllowAnonymous" name="allow_anonymous">
                                <label class="form-check-label" for="editAllowAnonymous">
                                    Allow Anonymous Voting
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="editAllowVoteChange" name="allow_vote_change">
                                <label class="form-check-label" for="editAllowVoteChange">
                                    Allow Vote Changes
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Questions Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Poll Questions</h6>
                            <button type="button" class="btn btn-sm theme-btn-secondary" id="editAddQuestionBtn">
                                <i class="fas fa-plus me-1"></i>Add Question
                            </button>
                        </div>
                        <div id="editQuestionsContainer">
                            <!-- Questions will be added dynamically -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn theme-btn-primary">
                        <i class="fas fa-save me-2"></i>Update Poll
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include necessary JavaScript files for proper functionality -->
<script src="<?= UrlHelper::url('public/js/opinion_polls.js') ?>"></script>
<script src="public/js/modules/opinion_poll_confirmations.js"></script>
<script src="public/js/opinion_poll_validation.js"></script>

<style>
/* Ensure custom field section displays side by side */
#customFieldSelection .col-md-6,
#editCustomFieldSelection .col-md-6 {
    display: inline-block;
    width: 48%;
    margin-right: 2%;
    vertical-align: top;
}

#customFieldSelection .col-md-6:last-child,
#editCustomFieldSelection .col-md-6:last-child {
    margin-right: 0;
}

/* Responsive design for smaller screens */
@media (max-width: 768px) {
    #customFieldSelection .col-md-6,
    #editCustomFieldSelection .col-md-6 {
        display: block;
        width: 100%;
        margin-right: 0;
        margin-bottom: 15px;
    }
}

/* Fix badge overflow in poll cards */
.poll-card .card-header {
    flex-wrap: wrap;
    min-height: auto;
}

.poll-card .card-header .d-flex.gap-1 {
    flex-wrap: wrap;
    gap: 0.25rem !important;
}

.poll-card .card-header .badge {
    font-size: 0.75rem;
    white-space: nowrap;
    margin-bottom: 0.25rem;
}

/* Ensure badges don't overflow on smaller screens */
@media (max-width: 576px) {
    .poll-card .card-header {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .poll-card .card-header .d-flex.gap-1 {
        margin-top: 0.5rem;
        width: 100%;
        justify-content: flex-start;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle target audience change for create modal
    const targetAudience = document.getElementById('targetAudience');
    const customFieldSelection = document.getElementById('customFieldSelection');
    const customFieldId = document.getElementById('customFieldId');
    const customFieldValue = document.getElementById('customFieldValue');

    // Handle target audience change for edit modal
    const editTargetAudience = document.getElementById('editTargetAudience');
    const editCustomFieldSelection = document.getElementById('editCustomFieldSelection');
    const editCustomFieldId = document.getElementById('editCustomFieldId');
    const editCustomFieldValue = document.getElementById('editCustomFieldValue');

    // Debug: Check if elements are found
    console.log('Elements found:');
    console.log('targetAudience:', targetAudience);
    console.log('customFieldSelection:', customFieldSelection);
    console.log('customFieldId:', customFieldId);
    console.log('customFieldValue:', customFieldValue);

    // Function to toggle custom field selection visibility
    function toggleCustomFieldSelection(audienceSelect, customFieldDiv) {
        if (audienceSelect.value === 'group_specific') {
            customFieldDiv.style.display = 'block';
        } else {
            customFieldDiv.style.display = 'none';
            // Clear selections when hidden
            const fieldIdSelect = customFieldDiv.querySelector('select[name="custom_field_id"]');
            const fieldValueSelect = customFieldDiv.querySelector('select[name="custom_field_value"]');
            if (fieldIdSelect) fieldIdSelect.value = '';
            if (fieldValueSelect) fieldValueSelect.value = '';
        }
    }

    // Function to load custom field values
    function loadCustomFieldValues(fieldIdSelect, fieldValueSelect) {
        console.log('loadCustomFieldValues called');
        console.log('fieldIdSelect:', fieldIdSelect);
        console.log('fieldValueSelect:', fieldValueSelect);
        
        const selectedOption = fieldIdSelect.options[fieldIdSelect.selectedIndex];
        console.log('selectedOption:', selectedOption);
        console.log('selectedOption.dataset.options:', selectedOption?.dataset?.options);
        
        if (selectedOption && selectedOption.dataset.options) {
            let options;
            try {
                options = JSON.parse(selectedOption.dataset.options);
                console.log('parsed options:', options);
            } catch (e) {
                console.log('JSON parse failed, treating as string:', e);
                // If JSON parse fails, treat as a string and split by newlines
                const rawData = selectedOption.dataset.options;
                options = rawData.split(/\r?\n/).filter(option => option.trim() !== '');
                console.log('split options:', options);
            }
            
            fieldValueSelect.innerHTML = '<option value="">Select value...</option>';
            
            // Handle both array and string cases
            if (Array.isArray(options)) {
                options.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option;
                    optionElement.textContent = option;
                    fieldValueSelect.appendChild(optionElement);
                });
            } else if (typeof options === 'string') {
                // If it's a string, split by newlines
                const optionArray = options.split(/\r?\n/).filter(option => option.trim() !== '');
                optionArray.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.trim();
                    optionElement.textContent = option.trim();
                    fieldValueSelect.appendChild(optionElement);
                });
            }
        } else {
            console.log('No options found or invalid data');
            fieldValueSelect.innerHTML = '<option value="">Select value...</option>';
        }
    }

    // Event listeners for create modal
    if (targetAudience) {
        targetAudience.addEventListener('change', function() {
            toggleCustomFieldSelection(targetAudience, customFieldSelection);
        });
    }

    if (customFieldId) {
        customFieldId.addEventListener('change', function() {
            console.log('Custom field changed, calling loadCustomFieldValues');
            loadCustomFieldValues(customFieldId, customFieldValue);
        });
    }

    // Event listeners for edit modal
    if (editTargetAudience) {
        editTargetAudience.addEventListener('change', function() {
            toggleCustomFieldSelection(editTargetAudience, editCustomFieldSelection);
        });
    }

    if (editCustomFieldId) {
        editCustomFieldId.addEventListener('change', function() {
            loadCustomFieldValues(editCustomFieldId, editCustomFieldValue);
        });
    }

    // Initialize custom field selection on modal show
    $('#createPollModal').on('shown.bs.modal', function() {
        toggleCustomFieldSelection(targetAudience, customFieldSelection);
    });

    $('#editPollModal').on('shown.bs.modal', function() {
        toggleCustomFieldSelection(editTargetAudience, editCustomFieldSelection);
    });
    
    // Also initialize on page load
    if (targetAudience && customFieldSelection) {
        toggleCustomFieldSelection(targetAudience, customFieldSelection);
    }
    if (editTargetAudience && editCustomFieldSelection) {
        toggleCustomFieldSelection(editTargetAudience, editCustomFieldSelection);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
