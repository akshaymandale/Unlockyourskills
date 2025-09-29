<?php
// Check if user is logged in
if (!isset($_SESSION['user']['client_id'])) {
    header('Location: index.php?controller=LoginController');
    exit;
}

require_once 'core/UrlHelper.php';
require_once 'includes/permission_helper.php';
if (!canAccess('announcements')) {
    header('Location: index.php?controller=DashboardController');
    exit;
}

$systemRole = $_SESSION['user']['system_role'] ?? '';
$canCreateGlobal = in_array($systemRole, ['super_admin', 'admin']);
$canManageAll = in_array($systemRole, ['super_admin', 'admin']);
$canCreateAnnouncement = canCreate('announcements');

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/sidebar.php';

// Include custom editor components
include 'views/components/custom-editor.php';
?>

<!-- Include custom editor CSS -->
<link rel="stylesheet" href="<?= UrlHelper::url('public/css/custom-editor.css') ?>">

<div class="main-content" data-announcement-page="true">
    <div class="container add-question-container">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="<?= UrlHelper::url('manage-portal') ?>#social" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-bullhorn me-2"></i>
                <?= Localization::translate('announcement_management'); ?>
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
                <li class="breadcrumb-item active" aria-current="page"><?= Localization::translate('announcements'); ?></li>
            </ol>
        </nav>

        <!-- Page Description -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">Create and manage announcements for your organization</p>
                    </div>
                    <?php if ($canCreateAnnouncement): ?>
                    <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#createAnnouncementModal">
                        <i class="fas fa-plus me-2"></i>Create Announcement
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
                                           placeholder="Search announcements...">
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="draft">Draft</option>
                                    <option value="expired">Expired</option>
                                    <option value="archived">Archived</option>
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

                            <!-- Urgency Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="urgencyFilter">
                                    <option value="">All Urgency</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="warning">Warning</option>
                                    <option value="info">Info</option>
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
                    <span id="resultsInfo">Loading announcements...</span>
                </div>
            </div>
        </div>

        <!-- Announcements Grid -->
        <div class="row" id="announcementsGrid">
            <!-- Announcements will be loaded here via AJAX -->
        </div>

        <!-- Pagination -->
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Announcements pagination">
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
                <p class="mt-2 text-muted">Loading announcements...</p>
            </div>
        </div>

        <!-- No Results -->
        <div class="row" id="noResults" style="display: none;">
            <div class="col-12 text-center py-5">
                <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No announcements found</h5>
                <p class="text-muted">Try adjusting your search criteria or create a new announcement.</p>
                <?php if ($canCreateAnnouncement): ?>
                <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#createAnnouncementModal">
                    <i class="fas fa-plus me-2"></i>Create First Announcement
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Announcement Modal -->
<div class="modal fade" id="createAnnouncementModal" tabindex="-1" aria-labelledby="createAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createAnnouncementModalLabel">
                    <i class="fas fa-bullhorn me-2"></i>Create New Announcement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createAnnouncementForm" method="POST">
                <input type="hidden" name="controller" value="AnnouncementController">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label for="announcementTitle" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="announcementTitle" name="title"
                                placeholder="Enter announcement title..." maxlength="255">
                            <div class="form-text">
                                <span id="titleCharCount">0</span>/255 characters
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="urgencyLevel" class="form-label">Urgency Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="urgencyLevel" name="urgency">
                                <option value="info">Info</option>
                                <option value="warning">Warning</option>
                                <option value="urgent">Urgent</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="announcementBody" class="form-label">Message <span class="text-danger">*</span></label>
                            
                            <?= renderCustomEditor('announcementBody', 'body', 'bodyCharCount', [
                                'placeholder' => 'Enter your announcement message...',
                                'minHeight' => 200,
                                'maxHeight' => 400,
                                'required' => true
                            ]) ?>
                        </div>
                    </div>

                    <!-- Audience and Timing -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="audienceType" class="form-label">Target Audience <span class="text-danger">*</span></label>
                            <select class="form-select" id="audienceType" name="audience_type">
                                <option value="">Select audience...</option>
                                <?php if ($canCreateGlobal): ?>
                                <option value="global">Global (All Users)</option>
                                <?php endif; ?>
                                <option value="group_specific">Group Specific</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="startDatetime" class="form-label">Start Date & Time</label>
                            <input type="datetime-local" class="form-control" id="startDatetime" name="start_datetime">
                            <div class="form-text">Leave empty to publish immediately</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="endDatetime" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control" id="endDatetime" name="end_datetime">
                            <div class="form-text">Leave empty for no expiration</div>
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

                    <!-- Course Selection (Hidden by default) -->
                    <div class="row mb-4 d-none" id="courseSelectionRow">
                        <div class="col-12">
                            <label for="targetCourses" class="form-label">Select Courses <span class="text-danger">*</span></label>
                            <select class="form-select" id="targetCourses" name="target_courses[]" multiple>
                                <!-- Courses will be loaded via AJAX -->
                            </select>
                            <div class="form-text">Hold Ctrl/Cmd to select multiple courses</div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Additional Options -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="requireAcknowledgment" name="require_acknowledgment">
                                <label class="form-check-label" for="requireAcknowledgment">
                                    Require Acknowledgment
                                </label>
                                <div class="form-text">Users must acknowledge they have read this announcement</div>
                            </div>
                        </div>
                    </div>

                    <!-- Call to Action -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="ctaLabel" class="form-label">Call to Action Label</label>
                            <input type="text" class="form-control" id="ctaLabel" name="cta_label"
                                placeholder="e.g., Learn More, Register Now">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="ctaUrl" class="form-label">Call to Action URL</label>
                            <input type="url" class="form-control" id="ctaUrl" name="cta_url"
                                placeholder="https://example.com">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn theme-btn-primary">
                        <i class="fas fa-bullhorn me-2"></i>Create Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAnnouncementModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Announcement
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editAnnouncementForm" method="POST">
                <input type="hidden" name="announcement_id" id="edit_announcement_id">
                <input type="hidden" name="controller" value="AnnouncementController">
                <input type="hidden" name="action" value="update">
                <div class="modal-body">
                    <!-- Same form fields as create modal -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label for="editAnnouncementTitle" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editAnnouncementTitle" name="title"
                                placeholder="Enter announcement title..." maxlength="255">
                            <div class="form-text">
                                <span id="editTitleCharCount">0</span>/255 characters
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="editUrgencyLevel" class="form-label">Urgency Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="editUrgencyLevel" name="urgency">
                                <option value="info">Info</option>
                                <option value="warning">Warning</option>
                                <option value="urgent">Urgent</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="editAnnouncementBody" class="form-label">Message <span class="text-danger">*</span></label>
                            
                            <?= renderCustomEditor('editAnnouncementBody', 'body', 'editBodyCharCount', [
                                'placeholder' => 'Enter your announcement message...',
                                'minHeight' => 200,
                                'maxHeight' => 400,
                                'required' => true
                            ]) ?>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label for="editAudienceType" class="form-label">Target Audience <span class="text-danger">*</span></label>
                            <select class="form-select" id="editAudienceType" name="audience_type">
                                <option value="">Select audience...</option>
                                <?php if ($canCreateGlobal): ?>
                                <option value="global">Global (All Users)</option>
                                <?php endif; ?>
                                <option value="group_specific">Group Specific</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="editStartDatetime" class="form-label">Start Date & Time</label>
                            <input type="datetime-local" class="form-control" id="editStartDatetime" name="start_datetime">
                            <div class="form-text">Leave empty to publish immediately</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="editEndDatetime" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control" id="editEndDatetime" name="end_datetime">
                            <div class="form-text">Leave empty for no expiration</div>
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


                    <div class="row mb-4 d-none" id="editCourseSelectionRow">
                        <div class="col-12">
                            <label for="editTargetCourses" class="form-label">Select Courses <span class="text-danger">*</span></label>
                            <select class="form-select" id="editTargetCourses" name="target_courses[]" multiple>
                                <!-- Courses will be loaded via AJAX -->
                            </select>
                            <div class="form-text">Hold Ctrl/Cmd to select multiple courses</div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editRequireAcknowledgment" name="require_acknowledgment">
                                <label class="form-check-label" for="editRequireAcknowledgment">
                                    Require Acknowledgment
                                </label>
                                <div class="form-text">Users must acknowledge they have read this announcement</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="editCtaLabel" class="form-label">Call to Action Label</label>
                            <input type="text" class="form-control" id="editCtaLabel" name="cta_label"
                                placeholder="e.g., Learn More, Register Now">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="editCtaUrl" class="form-label">Call to Action URL</label>
                            <input type="url" class="form-control" id="editCtaUrl" name="cta_url"
                                placeholder="https://example.com">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn theme-btn-primary">
                        <i class="fas fa-save me-2"></i>Update Announcement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Namespace for announcement state
window.announcementState = {
    currentPage: 1,
    currentFilters: {},
    isLoading: false
};

// Initialize page
document.addEventListener('DOMContentLoaded', function() {

    // Initialize filters and search
    initializeFilters();

    // Initialize character counting
    initializeCharacterCounting();

    // Initialize modals
    initializeModals();

    // Load initial announcements
    loadAnnouncements(1);
});

// Initialize filters and search functionality
function initializeFilters() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const audienceFilter = document.getElementById('audienceFilter');
    const urgencyFilter = document.getElementById('urgencyFilter');
    const dateRangeBtn = document.getElementById('dateRangeBtn');
    const applyDateFilter = document.getElementById('applyDateFilter');
    const clearDateFilter = document.getElementById('clearDateFilter');
    const clearAllFiltersBtn = document.getElementById('clearAllFiltersBtn');

    // Search with debounce
    if (searchInput) {
        const debouncedSearch = debounce((searchValue) => {
            window.announcementState.currentFilters.search = searchValue;
            loadAnnouncements(1);
        }, 500);

        searchInput.addEventListener('input', function() {
            debouncedSearch(this.value.trim());
        });
    }

    // Filter dropdowns
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            window.announcementState.currentFilters.status = this.value;
            loadAnnouncements(1);
        });
    }

    if (audienceFilter) {
        audienceFilter.addEventListener('change', function() {
            window.announcementState.currentFilters.audience_type = this.value;
            loadAnnouncements(1);
        });
    }

    if (urgencyFilter) {
        urgencyFilter.addEventListener('change', function() {
            window.announcementState.currentFilters.urgency = this.value;
            loadAnnouncements(1);
        });
    }

    // Date range toggle
    if (dateRangeBtn) {
        dateRangeBtn.addEventListener('click', function() {
            const dateRangeInputs = document.getElementById('dateRangeInputs');
            dateRangeInputs.classList.toggle('d-none');
        });
    }

    // Apply date filter
    if (applyDateFilter) {
        applyDateFilter.addEventListener('click', function() {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;

            if (dateFrom) window.announcementState.currentFilters.date_from = dateFrom;
            if (dateTo) window.announcementState.currentFilters.date_to = dateTo;

            loadAnnouncements(1);
        });
    }

    // Clear date filter
    if (clearDateFilter) {
        clearDateFilter.addEventListener('click', function() {
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            delete window.announcementState.currentFilters.date_from;
            delete window.announcementState.currentFilters.date_to;
            loadAnnouncements(1);
        });
    }

    // Clear all filters
    if (clearAllFiltersBtn) {
        clearAllFiltersBtn.addEventListener('click', function() {
            // Clear search
            if (searchInput) {
                searchInput.value = '';
            }

            // Clear filter dropdowns
            if (statusFilter) statusFilter.value = '';
            if (audienceFilter) audienceFilter.value = '';
            if (urgencyFilter) urgencyFilter.value = '';

            // Clear date inputs
            const dateFromInput = document.getElementById('dateFrom');
            const dateToInput = document.getElementById('dateTo');
            if (dateFromInput) dateFromInput.value = '';
            if (dateToInput) dateToInput.value = '';

            // Hide date range inputs
            const dateRangeInputs = document.getElementById('dateRangeInputs');
            if (dateRangeInputs) dateRangeInputs.classList.add('d-none');

            // Reset filter object
            window.announcementState.currentFilters = {};

            // Reload announcements
            loadAnnouncements(1);
        });
    }
}

// Initialize character counting
function initializeCharacterCounting() {
    // Create modal character counting
    const titleInput = document.getElementById('announcementTitle');
    const titleCharCount = document.getElementById('titleCharCount');
    const bodyInput = document.getElementById('announcementBody');
    const bodyCharCount = document.getElementById('bodyCharCount');

    if (titleInput && titleCharCount) {
        titleInput.addEventListener('input', function() {
            titleCharCount.textContent = this.value.length;
            if (this.value.length > 255) {
                titleCharCount.style.color = '#dc3545';
            } else {
                titleCharCount.style.color = '#6c757d';
            }
        });
    }

    // Body character counting is now handled by the custom editor

    // Edit modal character counting
    const editTitleInput = document.getElementById('editAnnouncementTitle');
    const editTitleCharCount = document.getElementById('editTitleCharCount');
    const editBodyInput = document.getElementById('editAnnouncementBody');
    const editBodyCharCount = document.getElementById('editBodyCharCount');

    if (editTitleInput && editTitleCharCount) {
        editTitleInput.addEventListener('input', function() {
            editTitleCharCount.textContent = this.value.length;
            if (this.value.length > 255) {
                editTitleCharCount.style.color = '#dc3545';
            } else {
                editTitleCharCount.style.color = '#6c757d';
            }
        });
    }

    // Edit body character counting is now handled by the custom editor
}

// Function to toggle custom field selection visibility (Global scope)
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

// Function to load custom field values (Global scope)
function loadCustomFieldValues(fieldIdSelect, fieldValueSelect) {
    const selectedOption = fieldIdSelect.options[fieldIdSelect.selectedIndex];
    const optionsData = selectedOption.getAttribute('data-options');
    
    // Clear existing options
    fieldValueSelect.innerHTML = '<option value="">Select value...</option>';
    
    if (optionsData) {
        try {
            const options = JSON.parse(optionsData);
            
            // Check if options is a string (contains \r\n) or an array
            if (typeof options === 'string') {
                // If it's a string with \r\n, split it
                const splitOptions = options.split(/\r?\n/).filter(opt => opt.trim() !== '');
                splitOptions.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.trim();
                    optionElement.textContent = option.trim();
                    fieldValueSelect.appendChild(optionElement);
                });
            } else if (Array.isArray(options)) {
                // If it's already an array, use it directly
                options.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option;
                    optionElement.textContent = option;
                    fieldValueSelect.appendChild(optionElement);
                });
            }
        } catch (e) {
            // If JSON parsing fails, treat as newline-separated string
            const options = optionsData.split(/\r?\n/).filter(opt => opt.trim() !== '');
            options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.trim();
                optionElement.textContent = option.trim();
                fieldValueSelect.appendChild(optionElement);
            });
        }
    }
}

// Initialize modals
function initializeModals() {
    // Audience type change handlers
    const audienceType = document.getElementById('audienceType');
    const editAudienceType = document.getElementById('editAudienceType');
    const customFieldSelection = document.getElementById('customFieldSelection');
    const customFieldId = document.getElementById('customFieldId');
    const customFieldValue = document.getElementById('customFieldValue');
    const editCustomFieldSelection = document.getElementById('editCustomFieldSelection');
    const editCustomFieldId = document.getElementById('editCustomFieldId');
    const editCustomFieldValue = document.getElementById('editCustomFieldValue');


    if (audienceType) {
        audienceType.addEventListener('change', function() {
            toggleCourseSelection(this.value, false);
            toggleCustomFieldSelection(this, customFieldSelection);
        });
    }

    if (editAudienceType) {
        editAudienceType.addEventListener('change', function() {
            toggleCourseSelection(this.value, true);
            toggleCustomFieldSelection(this, editCustomFieldSelection);
        });
    }

    if (customFieldId) {
        customFieldId.addEventListener('change', function() {
            loadCustomFieldValues(customFieldId, customFieldValue);
        });
    }

    if (editCustomFieldId) {
        editCustomFieldId.addEventListener('change', function() {
            loadCustomFieldValues(editCustomFieldId, editCustomFieldValue);
        });
    }

    // Form submissions
    const createForm = document.getElementById('createAnnouncementForm');
    const editForm = document.getElementById('editAnnouncementForm');

    if (createForm) {
        createForm.addEventListener('submit', handleCreateSubmit);
    }

    if (editForm) {
        editForm.addEventListener('submit', handleEditSubmit);
    }

    // Initialize custom field selection on modal show
    $('#createAnnouncementModal').on('shown.bs.modal', function() {
        if (audienceType && customFieldSelection) {
            toggleCustomFieldSelection(audienceType, customFieldSelection);
        }
    });

    $('#editAnnouncementModal').on('shown.bs.modal', function() {
        if (editAudienceType && editCustomFieldSelection) {
            toggleCustomFieldSelection(editAudienceType, editCustomFieldSelection);
        }
    });
    
    // Also initialize on page load
    if (audienceType && customFieldSelection) {
        toggleCustomFieldSelection(audienceType, customFieldSelection);
    }
    if (editAudienceType && editCustomFieldSelection) {
        toggleCustomFieldSelection(editAudienceType, editCustomFieldSelection);
    }
}

// Toggle course selection based on audience type
function toggleCourseSelection(audienceType, isEdit = false) {
    const courseRow = document.getElementById(isEdit ? 'editCourseSelectionRow' : 'courseSelectionRow');
    const courseSelect = document.getElementById(isEdit ? 'editTargetCourses' : 'targetCourses');

    if (courseRow && courseSelect) {
        // Hide course selection for all audience types
        courseRow.classList.add('d-none');
        courseSelect.required = false;
        courseSelect.selectedIndex = 0;
    }
}

// Load courses for course selection
function loadCourses(selectElement) {
    // This would typically load from an API
    // For now, we'll add some placeholder options
    const courses = [
        { id: 1, title: 'Introduction to Programming' },
        { id: 2, title: 'Advanced JavaScript' },
        { id: 3, title: 'Database Design' },
        { id: 4, title: 'Web Development' }
    ];

    selectElement.innerHTML = '';
    courses.forEach(course => {
        const option = document.createElement('option');
        option.value = course.id;
        option.textContent = course.title;
        selectElement.appendChild(option);
    });
}

// Load announcements with filters and pagination
function loadAnnouncements(page = 1) {
    if (window.announcementState.isLoading) return;

    window.announcementState.currentPage = page;

    // Show loading spinner
    showLoading(true);

    // Build query parameters
    const params = new URLSearchParams({
        page: page,
        limit: 10,
        ...window.announcementState.currentFilters
    });

    fetch(`index.php?controller=AnnouncementController&action=getAnnouncements&${params}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAnnouncements(data.announcements);
            updatePagination(data.pagination);
            updateResultsInfo(data.pagination);
        } else {
            showError(data.error || data.message || 'Failed to load announcements. Please try again.');
        }
    })
    .catch(error => {
        showError('Network error. Please check your connection and try again.');
    })
    .finally(() => {
        window.announcementState.isLoading = false;
        showLoading(false);
    });
}

// Display announcements in grid
function displayAnnouncements(announcements) {
    const grid = document.getElementById('announcementsGrid');
    const noResults = document.getElementById('noResults');

    if (!announcements || announcements.length === 0) {
        grid.innerHTML = '';
        noResults.style.display = 'block';
        return;
    }

    noResults.style.display = 'none';

    grid.innerHTML = announcements.map(announcement => createAnnouncementCard(announcement)).join('');

    // Add event listeners to action buttons
    addActionListeners();
}

// Create announcement card HTML
function createAnnouncementCard(announcement) {
    const urgencyClass = getUrgencyClass(announcement.urgency);
    const statusBadge = getStatusBadge(announcement.status);
    const audienceBadge = getAudienceBadge(announcement.audience_type);

    // RBAC: Only show edit/delete if allowed
    let editBtn = '';
    if (announcement.can_edit) {
        editBtn = `
            <button type="button" class="btn btn-sm theme-btn-secondary edit-announcement-btn"
                    data-announcement-id="${announcement.id}"
                    title="Edit Announcement">
                <i class="fas fa-edit"></i>
            </button>
        `;
    }
    let deleteBtn = '';
    if (announcement.can_delete) {
        deleteBtn = `
            <button type="button" class="btn btn-sm theme-btn-danger delete-announcement-btn"
                    data-announcement-id="${announcement.id}"
                    data-announcement-title="${escapeHtml(announcement.title)}"
                    title="Delete Announcement">
                <i class="fas fa-trash-alt"></i>
            </button>
        `;
    }

    return `
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="card h-100 announcement-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-bullhorn ${urgencyClass} me-2"></i>
                        <span class="badge ${urgencyClass}">${announcement.urgency.toUpperCase()}</span>
                    </div>
                    <div class="d-flex gap-1">
                        ${statusBadge}
                        ${audienceBadge}
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="card-title">${escapeHtml(announcement.title)}</h6>
                    <p class="card-text">
                        ${truncateText(stripHtml(announcement.body), 120)}
                    </p>

                    <div class="announcement-meta">
                        <div class="row">
                            <div class="col-6">
                                <i class="fas fa-user me-1"></i>
                                ${escapeHtml(announcement.creator_name || 'Unknown')}
                            </div>
                            <div class="col-6 text-end">
                                <i class="fas fa-calendar me-1"></i>
                                ${formatDate(announcement.created_at)}
                            </div>
                        </div>

                        ${announcement.require_acknowledgment ? `
                        <div class="row mt-2">
                            <div class="col-12">
                                <i class="fas fa-check-circle me-1"></i>
                                ${announcement.acknowledgment_count || 0} acknowledgments
                            </div>
                        </div>
                        ` : ''}

                        ${announcement.view_count ? `
                        <div class="row mt-1">
                            <div class="col-12">
                                <i class="fas fa-eye me-1"></i>
                                ${announcement.view_count} views
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
                <div class="card-footer">
                    <div class="btn-group w-100" role="group">
                        ${editBtn}
                        <button type="button" class="btn btn-sm btn-outline-info view-stats-btn"
                                data-announcement-id="${announcement.id}"
                                title="View Statistics">
                            <i class="fas fa-chart-bar"></i>
                        </button>
                        ${getStatusActionButton(announcement)}
                        ${deleteBtn}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Get urgency CSS class
function getUrgencyClass(urgency) {
    switch (urgency) {
        case 'urgent': return 'text-danger';
        case 'warning': return 'text-warning';
        case 'info':
        default: return 'text-info';
    }
}

// Get status badge
function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge bg-success">Active</span>',
        'scheduled': '<span class="badge bg-primary">Scheduled</span>',
        'draft': '<span class="badge bg-secondary">Draft</span>',
        'expired': '<span class="badge bg-warning">Expired</span>',
        'archived': '<span class="badge bg-dark">Archived</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
}

// Get audience badge
function getAudienceBadge(audienceType) {
    const badges = {
        'global': '<span class="badge bg-info">Global</span>',
        'group_specific': '<span class="badge bg-warning">Group</span>'
    };
    return badges[audienceType] || '<span class="badge bg-secondary">Unknown</span>';
}

// Get status action button
function getStatusActionButton(announcement) {
    switch (announcement.status) {
        case 'draft':
            return `
                <button type="button" class="btn btn-sm theme-btn-success activate-announcement-btn"
                        data-announcement-id="${announcement.id}"
                        title="Activate Announcement">
                    <i class="fas fa-play"></i>
                </button>
            `;
        case 'active':
            // Active announcements should not have action buttons - they are live and visible
            return `
                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Active - No actions available">
                    <i class="fas fa-check"></i>
                </button>
            `;
        case 'scheduled':
            return `
                <button type="button" class="btn btn-sm theme-btn-warning cancel-schedule-btn"
                        data-announcement-id="${announcement.id}"
                        title="Cancel Schedule">
                    <i class="fas fa-times"></i>
                </button>
            `;
        case 'expired':
            return `
                <button type="button" class="btn btn-sm theme-btn-secondary archive-announcement-btn"
                        data-announcement-id="${announcement.id}"
                        title="Archive Announcement">
                    <i class="fas fa-archive"></i>
                </button>
            `;
        case 'archived':
            return `
                <button type="button" class="btn btn-sm theme-btn-success unarchive-announcement-btn"
                        data-announcement-id="${announcement.id}"
                        title="Unarchive Announcement">
                    <i class="fas fa-box-open"></i>
                </button>
            `;
        default:
            return `
                <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                    <i class="fas fa-minus"></i>
                </button>
            `;
    }
}

// Utility functions
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
}

function stripHtml(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent || tmp.innerText || '';
}

function truncateText(text, length) {
    return text.length > length ? text.substring(0, length) + '...' : text;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

// Show/hide loading spinner
function showLoading(show) {
    const spinner = document.getElementById('loadingSpinner');
    const grid = document.getElementById('announcementsGrid');

    if (show) {
        spinner.style.display = 'block';
        grid.style.opacity = '0.5';
    } else {
        spinner.style.display = 'none';
        grid.style.opacity = '1';
    }
}

// Update pagination
function updatePagination(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;

    const { current_page, total_pages } = pagination;

    if (total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let paginationHTML = '';

    // Previous button
    if (current_page > 1) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadAnnouncements(${current_page - 1}); return false;">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
    }

    // Page numbers
    const startPage = Math.max(1, current_page - 2);
    const endPage = Math.min(total_pages, current_page + 2);

    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <li class="page-item ${i === current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadAnnouncements(${i}); return false;">
                    ${i}
                </a>
            </li>
        `;
    }

    // Next button
    if (current_page < total_pages) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadAnnouncements(${current_page + 1}); return false;">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }

    container.innerHTML = paginationHTML;
}

// Update results info
function updateResultsInfo(pagination) {
    const info = document.getElementById('resultsInfo');
    if (!info) return;

    const { current_page, total_count, per_page } = pagination;
    const start = ((current_page - 1) * per_page) + 1;
    const end = Math.min(current_page * per_page, total_count);

    if (total_count === 0) {
        info.innerHTML = 'No announcements found';
    } else {
        info.innerHTML = `Showing ${start}-${end} of ${total_count} announcements`;
    }
}

// Show error message
function showError(message) {
    if (typeof showSimpleToast === 'function') {
        showSimpleToast(message, 'error');
    } else {
        alert(message);
    }
}

// Show success message
function showSuccess(message) {
    if (typeof showSimpleToast === 'function') {
        showSimpleToast(message, 'success');
    } else {
        alert(message);
    }
}

// Add action listeners to buttons
function addActionListeners() {
    // Edit buttons
    document.querySelectorAll('.edit-announcement-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const announcementId = this.dataset.announcementId;
            editAnnouncement(announcementId);
        });
    });

    // Delete buttons
    document.querySelectorAll('.delete-announcement-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const announcementId = this.dataset.announcementId;
            const announcementTitle = this.dataset.announcementTitle;
            deleteAnnouncement(announcementId, announcementTitle);
        });
    });

    // Status action buttons
    document.querySelectorAll('.activate-announcement-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const announcementId = this.dataset.announcementId;
            updateAnnouncementStatus(announcementId, 'active');
        });
    });

    document.querySelectorAll('.archive-announcement-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const announcementId = this.dataset.announcementId;
            updateAnnouncementStatus(announcementId, 'archived');
        });
    });

    document.querySelectorAll('.cancel-schedule-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const announcementId = this.dataset.announcementId;
            updateAnnouncementStatus(announcementId, 'draft');
        });
    });
}

// Handle create form submission
function handleCreateSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
    submitBtn.disabled = true;

    fetch('index.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);

            // Close modal and reset form
            const modal = bootstrap.Modal.getInstance(document.getElementById('createAnnouncementModal'));
            modal.hide();
            form.reset();

            // Reset character counts
            document.getElementById('titleCharCount').textContent = '0';
            document.getElementById('bodyCharCount').textContent = '0';

            // Hide course selection if visible
            document.getElementById('courseSelectionRow').classList.add('d-none');

            // Reload announcements
            loadAnnouncements(window.announcementState.currentPage);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        // Error handling - console.error removed for clean console('Error:', error);
        showError('Network error. Please try again.');
    })
    .finally(() => {
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Handle edit form submission
function handleEditSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
    submitBtn.disabled = true;

    fetch('index.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);

            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editAnnouncementModal'));
            modal.hide();

            // Reload announcements
            loadAnnouncements(window.announcementState.currentPage);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        // Error handling - console.error removed for clean console('Error:', error);
        showError('Network error. Please try again.');
    })
    .finally(() => {
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Edit announcement
function editAnnouncement(announcementId) {
    fetch(`index.php?controller=AnnouncementController&action=edit&id=${announcementId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateEditForm(data.announcement, data.courses);

            // Show edit modal
            const modal = new bootstrap.Modal(document.getElementById('editAnnouncementModal'));
            modal.show();
        } else {
            showError(data.error || 'Failed to load announcement data.');
        }
    })
    .catch(error => {
        // Error handling - console.error removed for clean console('Error:', error);
        showError('Network error. Please try again.');
    });
}

// Populate edit form with announcement data
function populateEditForm(announcement, courses = []) {
    document.getElementById('edit_announcement_id').value = announcement.id;
    document.getElementById('editAnnouncementTitle').value = announcement.title || '';
    
    // Set editor content using custom editor function
    setEditorContent('editAnnouncementBody', announcement.body || '');
    
    document.getElementById('editUrgencyLevel').value = announcement.urgency || 'info';
    document.getElementById('editAudienceType').value = announcement.audience_type || '';

    // Update character counts
    document.getElementById('editTitleCharCount').textContent = (announcement.title || '').length;

    // Handle datetime fields
    if (announcement.start_datetime) {
        const startDate = new Date(announcement.start_datetime);
        document.getElementById('editStartDatetime').value = formatDateTimeLocal(startDate);
    }

    if (announcement.end_datetime) {
        const endDate = new Date(announcement.end_datetime);
        document.getElementById('editEndDatetime').value = formatDateTimeLocal(endDate);
    }

    // Handle checkboxes
    document.getElementById('editRequireAcknowledgment').checked = announcement.require_acknowledgment == 1;

    // Handle CTA fields
    document.getElementById('editCtaLabel').value = announcement.cta_label || '';
    document.getElementById('editCtaUrl').value = announcement.cta_url || '';

    // Handle course selection - hide for all audience types
    toggleCourseSelection(announcement.audience_type, true);
    
    // Handle custom field selection
    toggleCustomFieldSelection(document.getElementById('editAudienceType'), document.getElementById('editCustomFieldSelection'));
    
    // Populate custom field values if group_specific
    if (announcement.audience_type === 'group_specific') {
        if (announcement.custom_field_id) {
            document.getElementById('editCustomFieldId').value = announcement.custom_field_id;
            // Trigger change event to load custom field values
            document.getElementById('editCustomFieldId').dispatchEvent(new Event('change'));
            
            // Set custom field value after a short delay to ensure options are loaded
            setTimeout(() => {
                if (announcement.custom_field_value) {
                    document.getElementById('editCustomFieldValue').value = announcement.custom_field_value;
                }
            }, 100);
        }
    }
}

// Format date for datetime-local input
function formatDateTimeLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// Delete announcement
function deleteAnnouncement(announcementId, announcementTitle) {
    const itemName = `announcement "${announcementTitle}"`;

    if (typeof window.confirmDelete === 'function') {
        window.confirmDelete(itemName, () => {
            executeAnnouncementDelete(announcementId);
        });
    } else {
        if (confirm(`Are you sure you want to delete the ${itemName}?\n\nThis action cannot be undone and will remove all associated data.`)) {
            executeAnnouncementDelete(announcementId);
        }
    }
}

// Execute announcement deletion
function executeAnnouncementDelete(announcementId) {
    const formData = new FormData();
    formData.append('controller', 'AnnouncementController');
    formData.append('action', 'delete');
    formData.append('id', announcementId);

    fetch('index.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            loadAnnouncements(window.announcementState.currentPage);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        // Error handling - console.error removed for clean console('Error:', error);
        showError('Network error. Please try again.');
    });
}

// Update announcement status
function updateAnnouncementStatus(announcementId, status) {
    const statusLabels = {
        'active': 'activate',
        'archived': 'archive',
        'draft': 'cancel schedule for'
    };

    const action = statusLabels[status] || 'update';
    const message = `Are you sure you want to ${action} this announcement?`;

    if (typeof window.confirmAction === 'function') {
        const actionType = status === 'active' ? 'activate' : (status === 'archived' ? 'archive' : 'pause');
        window.confirmAction(actionType, 'announcement', () => {
            executeStatusUpdate(announcementId, status);
        }, message);
    } else {
        if (confirm(message)) {
            executeStatusUpdate(announcementId, status);
        }
    }
}

// Execute status update
function executeStatusUpdate(announcementId, status) {
    const formData = new FormData();
    formData.append('controller', 'AnnouncementController');
    formData.append('action', 'updateStatus');
    formData.append('announcement_id', announcementId);
    formData.append('status', status);

    fetch('index.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            loadAnnouncements(window.announcementState.currentPage);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        // Error handling - console.error removed for clean console('Error:', error);
        showError('Network error. Please try again.');
    });
}

// Debounce function
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
</script>

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
</style>

<!-- Include necessary JavaScript files for proper functionality -->
<script src="<?= UrlHelper::url('public/js/announcement_validation.js') ?>"></script>
<script src="<?= UrlHelper::url('public/js/modules/announcement_confirmations.js') ?>"></script>
<script src="<?= UrlHelper::url('public/js/custom-editor.js') ?>"></script>

<?php include 'includes/footer.php'; ?>
