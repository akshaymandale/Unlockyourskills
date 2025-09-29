<?php
// Check if user is logged in
if (!isset($_SESSION['user']['client_id'])) {
    header('Location: index.php?controller=LoginController');
    exit;
}

require_once 'core/UrlHelper.php';
require_once 'includes/permission_helper.php';
$canCreateEvent = canCreate('events');

$systemRole = $_SESSION['user']['system_role'] ?? '';
$canCreateGlobal = in_array($systemRole, ['super_admin', 'admin']);
$canManageAll = in_array($systemRole, ['super_admin', 'admin']);

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/sidebar.php';
?>

<!-- Custom Editor CSS -->
<link rel="stylesheet" href="public/css/custom-editor.css">

<div class="main-content" data-events-page="true">
    <div class="container add-question-container">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="<?= UrlHelper::url('manage-portal') ?>#social" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-calendar-alt me-2"></i>
                Event Management
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
                <li class="breadcrumb-item active" aria-current="page">Event Management</li>
            </ol>
        </nav>

        <!-- Page Description -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">Create and manage events, webinars, and live sessions</p>
                    </div>
                    <?php if ($canCreateEvent): ?>
                    <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
                        <i class="fas fa-plus me-2"></i>Create Event
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
                                           placeholder="Search events...">
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="draft">Draft</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="completed">Completed</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>

                            <!-- Event Type Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="eventTypeFilter">
                                    <option value="">All Types</option>
                                    <option value="live_class">Live Class</option>
                                    <option value="webinar">Webinar</option>
                                    <option value="deadline">Deadline</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="meeting">Meeting</option>
                                    <option value="workshop">Workshop</option>
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
                    <span id="resultsInfo">Loading events...</span>
                </div>
            </div>
        </div>

        <!-- Events Grid -->
        <div class="row" id="eventsGrid">
            <!-- Events will be loaded here via AJAX -->
        </div>

        <!-- Pagination -->
        <div class="row mt-4">
            <div class="col-12">
                <nav aria-label="Events pagination">
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
                <p class="mt-2 text-muted">Loading events...</p>
            </div>
        </div>

        <!-- No Results -->
        <div class="row" id="noResults" style="display: none;">
            <div class="col-12 text-center py-5">
                <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No events found</h5>
                <p class="text-muted">Try adjusting your search criteria or create a new event.</p>
                <?php if ($canCreateEvent): ?>
                <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
                    <i class="fas fa-plus me-2"></i>Create First Event
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEventModalLabel">
                    <i class="fas fa-calendar-plus me-2"></i>Create New Event
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createEventForm" method="POST">
                <input type="hidden" name="controller" value="EventController">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label for="eventTitle" class="form-label">Event Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="eventTitle" name="title"
                                placeholder="Enter event title..." maxlength="255">
                            <div class="form-text">
                                <span id="titleCharCount">0</span>/255 characters
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="eventType" class="form-label">Event Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="eventType" name="event_type">
                                <option value="">Select type...</option>
                                <option value="live_class">Live Class</option>
                                <option value="webinar">Webinar</option>
                                <option value="deadline">Deadline</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="meeting">Meeting</option>
                                <option value="workshop">Workshop</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="eventDescription" class="form-label">Description <span class="text-danger">*</span></label>
                            <?php 
                            // Include the custom editor component
                            require_once 'views/components/custom-editor.php';
                            echo renderCustomEditor('eventDescription', 'description', 'descriptionCharCount', [
                                'placeholder' => 'Enter event description...',
                                'showCharCount' => true,
                                'required' => true
                            ]);
                            ?>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Event Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="eventLink" class="form-label">Event Link</label>
                            <input type="url" class="form-control" id="eventLink" name="event_link"
                                placeholder="https://zoom.us/j/123456789">
                            <div class="form-text">Optional: Zoom, Google Meet, or other meeting link</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="eventLocation" class="form-label">Location</label>
                            <input type="text" class="form-control" id="eventLocation" name="location"
                                placeholder="Room 101, Building A">
                            <div class="form-text">Optional: Physical location or venue</div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Date and Time -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="startDatetime" class="form-label">Start Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="startDatetime" name="start_datetime">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="endDatetime" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control" id="endDatetime" name="end_datetime">
                            <div class="form-text">Optional: Leave empty for open-ended events</div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Audience -->
                    <div class="row mb-4">
                        <div class="col-md-6">
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
                        <div class="col-md-6">
                            <label for="sendReminderBefore" class="form-label">Send Reminder</label>
                            <select class="form-select" id="sendReminderBefore" name="send_reminder_before">
                                <option value="0">No Reminder</option>
                                <option value="15">15 minutes before</option>
                                <option value="30">30 minutes before</option>
                                <option value="60">1 hour before</option>
                                <option value="120">2 hours before</option>
                                <option value="1440">1 day before</option>
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

                    <!-- Custom Field Selection (for Group Specific) -->
                    <div class="row mb-4" id="customFieldSelection" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="customFieldId" class="form-label">Select Custom Field <span class="text-danger">*</span></label>
                            <select class="form-select" id="customFieldId" name="custom_field_id">
                                <option value="">Select custom field...</option>
                                <?php foreach ($customFields as $field): ?>
                                    <option value="<?= $field['id']; ?>" data-options="<?= htmlspecialchars($field['field_options'] ?? ''); ?>">
                                        <?= htmlspecialchars($field['field_label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="customFieldValue" class="form-label">Select Custom Field Value <span class="text-danger">*</span></label>
                            <select class="form-select" id="customFieldValue" name="custom_field_value">
                                <option value="">Select custom field first...</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Event Options -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enableRsvp" name="enable_rsvp">
                                <label class="form-check-label" for="enableRsvp">
                                    Enable RSVP
                                </label>
                                <div class="form-text">Allow users to respond Yes/No/Maybe</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn theme-btn-primary">
                        <i class="fas fa-calendar-plus me-2"></i>Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEventModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Event
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editEventForm" method="POST">
                <input type="hidden" name="event_id" id="edit_event_id">
                <input type="hidden" name="controller" value="EventController">
                <input type="hidden" name="action" value="update">
                <div class="modal-body">
                    <!-- Same form fields as create modal -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <label for="editEventTitle" class="form-label">Event Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editEventTitle" name="title"
                                placeholder="Enter event title..." maxlength="255">
                            <div class="form-text">
                                <span id="editTitleCharCount">0</span>/255 characters
                            </div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="editEventType" class="form-label">Event Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="editEventType" name="event_type">
                                <option value="">Select type...</option>
                                <option value="live_class">Live Class</option>
                                <option value="webinar">Webinar</option>
                                <option value="deadline">Deadline</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="meeting">Meeting</option>
                                <option value="workshop">Workshop</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label for="editEventDescription" class="form-label">Description <span class="text-danger">*</span></label>
                            <?php 
                            // Include the custom editor component
                            echo renderCustomEditor('editEventDescription', 'description', 'editDescriptionCharCount', [
                                'placeholder' => 'Enter event description...',
                                'showCharCount' => true,
                                'required' => true
                            ]);
                            ?>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="editEventLink" class="form-label">Event Link</label>
                            <input type="url" class="form-control" id="editEventLink" name="event_link"
                                placeholder="https://zoom.us/j/123456789">
                            <div class="form-text">Optional: Zoom, Google Meet, or other meeting link</div>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="editEventLocation" class="form-label">Location</label>
                            <input type="text" class="form-control" id="editEventLocation" name="location"
                                placeholder="Room 101, Building A">
                            <div class="form-text">Optional: Physical location or venue</div>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="editStartDatetime" class="form-label">Start Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="editStartDatetime" name="start_datetime">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="editEndDatetime" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control" id="editEndDatetime" name="end_datetime">
                            <div class="form-text">Optional: Leave empty for open-ended events</div>
                            <div class="invalid-feedback"></div>
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
                            <label for="editSendReminderBefore" class="form-label">Send Reminder</label>
                            <select class="form-select" id="editSendReminderBefore" name="send_reminder_before">
                                <option value="0">No Reminder</option>
                                <option value="15">15 minutes before</option>
                                <option value="30">30 minutes before</option>
                                <option value="60">1 hour before</option>
                                <option value="120">2 hours before</option>
                                <option value="1440">1 day before</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="editEventStatus" class="form-label">Status</label>
                            <select class="form-select" id="editEventStatus" name="status">
                                <option value="active">Active</option>
                                <option value="draft">Draft</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="completed">Completed</option>
                                <option value="archived">Archived</option>
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

                    <!-- Edit Custom Field Selection (for Group Specific) -->
                    <div class="row mb-4" id="editCustomFieldSelection" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="editCustomFieldId" class="form-label">Select Custom Field <span class="text-danger">*</span></label>
                            <select class="form-select" id="editCustomFieldId" name="custom_field_id">
                                <option value="">Select custom field...</option>
                                <?php foreach ($customFields as $field): ?>
                                    <option value="<?= $field['id']; ?>" data-options="<?= htmlspecialchars($field['field_options'] ?? ''); ?>">
                                        <?= htmlspecialchars($field['field_label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editCustomFieldValue" class="form-label">Select Custom Field Value <span class="text-danger">*</span></label>
                            <select class="form-select" id="editCustomFieldValue" name="custom_field_value">
                                <option value="">Select custom field first...</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editEnableRsvp" name="enable_rsvp">
                                <label class="form-check-label" for="editEnableRsvp">
                                    Enable RSVP
                                </label>
                                <div class="form-text">Allow users to respond Yes/No/Maybe</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn theme-btn-primary">
                        <i class="fas fa-save me-2"></i>Update Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Namespace for event state
window.eventState = {
    currentPage: 1,
    currentSearch: '',
    currentFilters: {
        status: '',
        event_type: '',
        audience_type: '',
        date_from: '',
        date_to: ''
    },
    isLoading: false
};

document.addEventListener('DOMContentLoaded', function() {
    // Initialize event management functionality
    initializeEventManagement();

    // Load initial events
    if (document.getElementById('eventsGrid')) {
        loadEvents(1);
    }

    // Initialize create event form
    initializeCreateEventForm();
});

function initializeEventManagement() {
    // Initialize filters and search
    initializeFilters();

    // Initialize character counting
    initializeCharacterCounting();

    // Initialize modals
    initializeModals();

    // Pagination functionality
    document.addEventListener('click', function(e) {
        if (e.target.matches('.page-link[data-page]')) {
            e.preventDefault();
            const page = parseInt(e.target.getAttribute('data-page'));
            loadEvents(page);
        }
    });

    // Event action buttons functionality
    document.addEventListener('click', function(e) {
        // Edit event button
        if (e.target.closest('.edit-event-btn')) {
            const eventId = e.target.closest('.edit-event-btn').dataset.eventId;
            editEvent(eventId);
        }

        // View attendees button
        if (e.target.closest('.view-attendees-btn')) {
            const eventId = e.target.closest('.view-attendees-btn').dataset.eventId;
            viewEventAttendees(eventId);
        }
    });
}

// Initialize filters and search functionality
function initializeFilters() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const eventTypeFilter = document.getElementById('eventTypeFilter');
    const audienceFilter = document.getElementById('audienceFilter');
    const dateRangeBtn = document.getElementById('dateRangeBtn');
    const applyDateFilter = document.getElementById('applyDateFilter');
    const clearDateFilter = document.getElementById('clearDateFilter');
    const clearAllFiltersBtn = document.getElementById('clearAllFiltersBtn');

    // Search with debounce
    if (searchInput) {
        const debouncedSearch = debounce((searchValue) => {
            window.eventState.currentSearch = searchValue;
            loadEvents(1);
        }, 500);

        searchInput.addEventListener('input', function() {
            debouncedSearch(this.value.trim());
        });
    }

    // Filter dropdowns
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            window.eventState.currentFilters.status = this.value;
            loadEvents(1);
        });
    }

    if (eventTypeFilter) {
        eventTypeFilter.addEventListener('change', function() {
            window.eventState.currentFilters.event_type = this.value;
            loadEvents(1);
        });
    }

    if (audienceFilter) {
        audienceFilter.addEventListener('change', function() {
            window.eventState.currentFilters.audience_type = this.value;
            loadEvents(1);
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

            if (dateFrom) window.eventState.currentFilters.date_from = dateFrom;
            if (dateTo) window.eventState.currentFilters.date_to = dateTo;

            loadEvents(1);
        });
    }

    // Clear date filter
    if (clearDateFilter) {
        clearDateFilter.addEventListener('click', function() {
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            delete window.eventState.currentFilters.date_from;
            delete window.eventState.currentFilters.date_to;
            loadEvents(1);
        });
    }

    // Clear all filters
    if (clearAllFiltersBtn) {
        clearAllFiltersBtn.addEventListener('click', function() {
            // Clear search
            if (searchInput) {
                searchInput.value = '';
                window.eventState.currentSearch = '';
            }

            // Clear filter dropdowns
            if (statusFilter) statusFilter.value = '';
            if (eventTypeFilter) eventTypeFilter.value = '';
            if (audienceFilter) audienceFilter.value = '';

            // Clear date inputs
            const dateFromInput = document.getElementById('dateFrom');
            const dateToInput = document.getElementById('dateTo');
            if (dateFromInput) dateFromInput.value = '';
            if (dateToInput) dateToInput.value = '';

            // Hide date range inputs
            const dateRangeInputs = document.getElementById('dateRangeInputs');
            if (dateRangeInputs) dateRangeInputs.classList.add('d-none');

            // Reset filter object
            window.eventState.currentFilters = {
                status: '',
                event_type: '',
                audience_type: '',
                date_from: '',
                date_to: ''
            };

            // Reload events
            loadEvents(1);
        });
    }
}

// Initialize character counting
function initializeCharacterCounting() {
    // Create modal character counting
    const titleInput = document.getElementById('eventTitle');
    const titleCharCount = document.getElementById('titleCharCount');
    // Description input is now handled by the custom editor

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

    // Description character counting is now handled by the custom editor

    // Edit modal character counting
    const editTitleInput = document.getElementById('editEventTitle');
    const editTitleCharCount = document.getElementById('editTitleCharCount');
    // Edit description input is now handled by the custom editor

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

    // Edit description character counting is now handled by the custom editor
}

// Initialize modals
function initializeModals() {
    // Audience type change handlers
    const audienceType = document.getElementById('audienceType');
    const editAudienceType = document.getElementById('editAudienceType');

    if (audienceType) {
        audienceType.addEventListener('change', function() {
            toggleCourseSelection(this.value, false);
        });
    }

    if (editAudienceType) {
        editAudienceType.addEventListener('change', function() {
            toggleCourseSelection(this.value, true);
        });
    }

    // Form submissions
    const createForm = document.getElementById('createEventForm');
    const editForm = document.getElementById('editEventForm');

    if (createForm) {
        createForm.addEventListener('submit', handleCreateSubmit);
    }

    if (editForm) {
        editForm.addEventListener('submit', handleEditSubmit);
    }
}

// Toggle course selection based on audience type
function toggleCourseSelection(audienceType, isEdit = false) {
    const courseRow = document.getElementById(isEdit ? 'editCourseSelectionRow' : 'courseSelectionRow');
    const courseSelect = document.getElementById(isEdit ? 'editTargetCourses' : 'targetCourses');

    if (courseRow && courseSelect) {
        // Course selection is no longer needed since course_specific option is removed
        courseRow.classList.add('d-none');
        courseSelect.required = false;
        courseSelect.selectedIndex = 0;
    }
}

// Toggle custom field selection based on audience type
function toggleCustomFieldSelection(audienceType, isEdit = false) {
    const customFieldRow = document.getElementById(isEdit ? 'editCustomFieldSelection' : 'customFieldSelection');
    const customFieldId = document.getElementById(isEdit ? 'editCustomFieldId' : 'customFieldId');
    const customFieldValue = document.getElementById(isEdit ? 'editCustomFieldValue' : 'customFieldValue');

    if (customFieldRow && customFieldId && customFieldValue) {
        if (audienceType === 'group_specific') {
            customFieldRow.style.display = 'block';
            customFieldId.required = true;
            customFieldValue.required = true;
        } else {
            customFieldRow.style.display = 'none';
            customFieldId.required = false;
            customFieldValue.required = false;
            customFieldId.selectedIndex = 0;
            customFieldValue.selectedIndex = 0;
        }
    }
}

// Load custom field values based on selected custom field
function loadCustomFieldValues(fieldIdSelect, fieldValueSelect) {
    const selectedOption = fieldIdSelect.options[fieldIdSelect.selectedIndex];
    
    if (selectedOption && selectedOption.dataset.options) {
        let options;
        try {
            options = JSON.parse(selectedOption.dataset.options);
        } catch (e) {
            // If JSON parse fails, treat as a string and split by newlines
            const rawData = selectedOption.dataset.options;
            options = rawData.split(/\r?\n/).filter(option => option.trim() !== '');
        }
        
        // Clear existing options
        fieldValueSelect.innerHTML = '<option value="">Select value...</option>';
        
        // Add new options
        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option;
            optionElement.textContent = option;
            fieldValueSelect.appendChild(optionElement);
        });
    } else {
        fieldValueSelect.innerHTML = '<option value="">Select custom field first...</option>';
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
        { id: 4, title: 'Web Development' },
        { id: 5, title: 'Mobile App Development' }
    ];

    selectElement.innerHTML = '';
    courses.forEach(course => {
        const option = document.createElement('option');
        option.value = course.id;
        option.textContent = course.title;
        selectElement.appendChild(option);
    });
}

// Initialize create event form
function initializeCreateEventForm() {
    // Set minimum datetime to current time
    const now = new Date();
    const minDateTime = now.toISOString().slice(0, 16);

    const startDatetime = document.getElementById('startDatetime');
    const editStartDatetime = document.getElementById('editStartDatetime');

    if (startDatetime) {
        startDatetime.min = minDateTime;
    }

    if (editStartDatetime) {
        editStartDatetime.min = minDateTime;
    }
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

// Load events with filters and pagination
function loadEvents(page = 1) {
    if (window.eventState.isLoading) return;

    window.eventState.currentPage = page;

    // Show loading spinner
    showLoading(true);

    // Build query parameters
    const params = new URLSearchParams({
        page: page,
        limit: 10,
        search: window.eventState.currentSearch,
        ...window.eventState.currentFilters
    });

    fetch(`index.php?controller=EventController&action=getEvents&${params}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayEvents(data.events);
            updatePagination(data.pagination);
            updateResultsInfo(data.pagination);
        } else {
            showError(data.error || data.message || 'Failed to load events. Please try again.');
        }
    })
    .catch(error => {
        showError('Network error. Please check your connection and try again.');
    })
    .finally(() => {
        window.eventState.isLoading = false;
        showLoading(false);
    });
}

// Display events in grid
function displayEvents(events) {
    const grid = document.getElementById('eventsGrid');
    const noResults = document.getElementById('noResults');

    if (!events || events.length === 0) {
        grid.innerHTML = '';
        noResults.style.display = 'block';
        return;
    }

    noResults.style.display = 'none';

    grid.innerHTML = events.map(event => createEventCard(event)).join('');

    // Add event listeners to action buttons
    addActionListeners();
}

// Create event card HTML
function createEventCard(event) {
    const eventTypeClass = getEventTypeClass(event.event_type);
    const statusBadge = getStatusBadge(event.status);
    const audienceBadge = getAudienceBadge(event.audience_type);
    const eventDate = formatEventDate(event.start_datetime);
    const isUpcoming = new Date(event.start_datetime) > new Date();
    const hasRSVP = event.enable_rsvp == 1;

    return `
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="card h-100 event-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-calendar-alt ${eventTypeClass} me-2"></i>
                        <span class="badge ${eventTypeClass}">${event.event_type.replace('_', ' ').toUpperCase()}</span>
                    </div>
                    <div class="d-flex gap-1">
                        ${statusBadge}
                        ${audienceBadge}
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="card-title">${escapeHtml(event.title)}</h6>
                    <p class="card-text text-muted small">
                        ${truncateText(stripHtml(event.description), 100)}
                    </p>

                    <div class="event-meta">
                        <div class="row text-muted small">
                            <div class="col-12 mb-2">
                                <i class="fas fa-clock me-1"></i>
                                ${eventDate}
                            </div>
                            <div class="col-6">
                                <i class="fas fa-user me-1"></i>
                                ${escapeHtml(event.created_by_name || 'Unknown')}
                            </div>
                            <div class="col-6 text-end">
                                ${isUpcoming ? '<span class="badge bg-info">Upcoming</span>' : '<span class="badge bg-secondary">Past</span>'}
                            </div>
                        </div>

                        ${event.location ? `
                        <div class="row mt-2 text-muted small">
                            <div class="col-12">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                ${escapeHtml(event.location)}
                            </div>
                        </div>
                        ` : ''}

                        ${event.event_link ? `
                        <div class="row mt-2 text-muted small">
                            <div class="col-12">
                                <i class="fas fa-link me-1"></i>
                                <a href="${escapeHtml(event.event_link)}" target="_blank" class="text-decoration-none">Join Event</a>
                            </div>
                        </div>
                        ` : ''}

                        ${hasRSVP ? `
                        <div class="row mt-2 text-muted small">
                            <div class="col-12">
                                <i class="fas fa-users me-1"></i>
                                RSVPs: ${event.yes_count || 0} Yes, ${event.no_count || 0} No, ${event.maybe_count || 0} Maybe
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
                <div class="card-footer">
                    <div class="btn-group w-100" role="group">
                        ${event.can_edit ? `<button type="button" class="btn btn-sm theme-btn-secondary edit-event-btn" data-event-id="${event.id}" title="Edit Event"><i class="fas fa-edit"></i></button>` : ''}
                        ${hasRSVP ? `
                        <button type="button" class="btn btn-sm btn-outline-info view-attendees-btn" data-event-id="${event.id}" title="View Attendees"><i class="fas fa-users"></i></button>
                        ` : ''}
                        ${getStatusActionButtons(event)}
                        ${event.can_delete ? `<button type="button" class="btn btn-sm theme-btn-danger delete-event-btn" data-event-id="${event.id}" data-event-title="${escapeHtml(event.title)}" title="Delete Event"><i class="fas fa-trash-alt"></i></button>` : ''}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Get event type CSS class
function getEventTypeClass(eventType) {
    switch (eventType) {
        case 'live_class': return 'text-primary';
        case 'webinar': return 'text-info';
        case 'deadline': return 'text-danger';
        case 'maintenance': return 'text-warning';
        case 'meeting': return 'text-success';
        case 'workshop': return 'text-purple';
        default: return 'text-secondary';
    }
}

// Get status badge
function getStatusBadge(status) {
    const badges = {
        'active': '<span class="badge bg-success">Active</span>',
        'draft': '<span class="badge bg-secondary">Draft</span>',
        'cancelled': '<span class="badge bg-danger">Cancelled</span>',
        'completed': '<span class="badge bg-dark">Completed</span>',
        'archived': '<span class="badge bg-secondary">Archived</span>'
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

// Format event date
function formatEventDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = date - now;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    const formattedDate = date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    if (diffDays === 0) {
        return `Today at ${date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}`;
    } else if (diffDays === 1) {
        return `Tomorrow at ${date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}`;
    } else if (diffDays > 0 && diffDays <= 7) {
        return `In ${diffDays} days - ${formattedDate}`;
    } else {
        return formattedDate;
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

// Show/hide loading spinner
function showLoading(show) {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.style.display = show ? 'block' : 'none';
    }
}

// Show error message
function showError(message) {
    if (typeof showSimpleToast === 'function') {
        showSimpleToast(message, 'error');
    } else {
        alert('Error: ' + message); // Fallback
    }
}

// Update pagination
function updatePagination(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;

    const { currentPage, totalPages } = pagination;
    let paginationHTML = '';

    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    // Previous button
    if (currentPage > 1) {
        paginationHTML += `<li class="page-item">
            <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
        </li>`;
    }

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            paginationHTML += `<li class="page-item active">
                <span class="page-link">${i}</span>
            </li>`;
        } else {
            paginationHTML += `<li class="page-item">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        }
    }

    // Next button
    if (currentPage < totalPages) {
        paginationHTML += `<li class="page-item">
            <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
        </li>`;
    }

    container.innerHTML = paginationHTML;
}

// Update results info
function updateResultsInfo(pagination) {
    const info = document.getElementById('resultsInfo');
    if (!info) return;

    const { totalEvents } = pagination;

    if (totalEvents === 0) {
        info.textContent = 'No events found';
    } else if (totalEvents === 1) {
        info.textContent = 'Showing 1 event';
    } else {
        info.textContent = `Showing all ${totalEvents} events`;
    }
}

// Add action listeners
function addActionListeners() {
    // This function can be used to add specific event listeners to dynamically created buttons
    // For now, we're using event delegation in the main initialization
}

// Handle create form submission
function handleCreateSubmit(e) {
    e.preventDefault();

    const form = e.target;
    
    // Check client-side validation before submission
    if (typeof EventValidation !== 'undefined' && !EventValidation.validateForm(form, false)) {
        // Validation failed, don't submit to server
        return;
    }
    
    const formData = new FormData(form);

    // Add AJAX flag
    formData.append('ajax', '1');

    fetch('index.php?controller=EventController&action=create', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createEventModal'));
            modal.hide();

            // Reset form
            form.reset();

            // Reload events
            loadEvents(1);

            // Show success message
            showSuccess(data.message);
        } else {
            // Only show toast for non-validation errors (server errors, etc.)
            if (data.message && !data.message.includes('required') && !data.message.includes('Invalid') && !data.message.includes('must be')) {
                showError(data.message);
            } else {
                // For validation errors, let client-side validation handle them
                // Server validation error handled by client-side validation
            }
        }
    })
    .catch(error => {
        showError('An error occurred while creating the event.');
    });
}

// Handle edit form submission
function handleEditSubmit(e) {
    e.preventDefault();

    const form = e.target;
    
    // Check client-side validation before submission
    if (typeof EventValidation !== 'undefined' && !EventValidation.validateForm(form, true)) {
        // Validation failed, don't submit to server
        return;
    }
    
    const formData = new FormData(form);

    // Add AJAX flag
    formData.append('ajax', '1');

    fetch('index.php?controller=EventController&action=update', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editEventModal'));
            modal.hide();

            // Reload events
            loadEvents(window.eventState.currentPage);

            // Show success message
            showSuccess(data.message);
        } else {
            // Only show toast for non-validation errors (server errors, etc.)
            if (data.message && !data.message.includes('required') && !data.message.includes('Invalid') && !data.message.includes('must be')) {
                showError(data.message);
            } else {
                // For validation errors, let client-side validation handle them
                // Server validation error handled by client-side validation
            }
        }
    })
    .catch(error => {
        showError('An error occurred while updating the event.');
    });
}

// Edit event function
function editEvent(eventId) {
    fetch(`index.php?controller=EventController&action=edit&id=${eventId}&ajax=1`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateEditForm(data.event, data.audiences);
            const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
            modal.show();
        } else {
            showError(data.error || 'Failed to load event data');
        }
    })
    .catch(error => {
        showError('An error occurred while loading event data.');
    });
}

// Populate edit form with event data
function populateEditForm(event, audiences) {
    document.getElementById('edit_event_id').value = event.id;
    document.getElementById('editEventTitle').value = event.title;
    document.getElementById('editEventDescription').value = event.description;
    document.getElementById('editEventType').value = event.event_type;
    document.getElementById('editEventLink').value = event.event_link || '';
    document.getElementById('editEventLocation').value = event.location || '';
    document.getElementById('editStartDatetime').value = event.start_datetime;
    document.getElementById('editEndDatetime').value = event.end_datetime || '';
    document.getElementById('editAudienceType').value = event.audience_type;
    document.getElementById('editSendReminderBefore').value = event.send_reminder_before;
    document.getElementById('editEventStatus').value = event.status;
    document.getElementById('editEnableRsvp').checked = event.enable_rsvp == 1;

    // Trigger audience type change to show/hide course selection
    toggleCourseSelection(event.audience_type, true);
    
    // Trigger custom field selection toggle
    toggleCustomFieldSelection(event.audience_type, true);
    
    // Set custom field values if they exist
    if (event.custom_field_id) {
        document.getElementById('editCustomFieldId').value = event.custom_field_id;
        // Trigger change event to load custom field values
        document.getElementById('editCustomFieldId').dispatchEvent(new Event('change'));
        // Set the custom field value after a short delay to ensure options are loaded
        setTimeout(() => {
            if (event.custom_field_value) {
                document.getElementById('editCustomFieldValue').value = event.custom_field_value;
            }
        }, 100);
    }

    // Update character counts
    document.getElementById('editTitleCharCount').textContent = event.title.length;
    // Description character count is now handled by the custom editor
    
    // Set editor content using custom editor function
    if (typeof setEditorContent === 'function') {
        setEditorContent('editEventDescription', event.description || '');
    }
}

// Show success message
function showSuccess(message) {
    if (typeof showSimpleToast === 'function') {
        showSimpleToast(message, 'success');
    } else {
        alert(message); // Fallback
    }
}

// View event attendees
function viewEventAttendees(eventId) {
    fetch(`index.php?controller=EventController&action=attendees&event_id=${eventId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAttendeesModal(data.rsvps);
        } else {
            showError(data.message || 'Failed to load attendees');
        }
    })
    .catch(error => {
        showError('An error occurred while loading attendees.');
    });
}

// Display attendees in modal (you would need to create this modal)
function displayAttendeesModal(rsvps) {
    // Implementation for showing attendees modal
    alert(`Total RSVPs: ${rsvps.length}`); // Temporary fallback
}

// Add status action buttons based on event status
function getStatusActionButtons(event) {
    let buttons = '';

    // Check event timing
    const isUpcoming = new Date(event.start_datetime) > new Date();
    const isPast = !isUpcoming;
    const hasEndDate = event.end_datetime && event.end_datetime !== '';
    const isPastEndDate = hasEndDate && new Date(event.end_datetime) < new Date();
    
    if (isUpcoming) {
        if (event.status === 'active') {
            // Active events can be cancelled or marked as completed
            buttons += `
                <button type="button" class="btn btn-sm btn-outline-warning cancel-event-btn"
                        data-event-id="${event.id}"
                        data-event-title="${escapeHtml(event.title)}"
                        title="Cancel Event">
                    <i class="fas fa-times-circle"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-success complete-event-btn"
                        data-event-id="${event.id}"
                        data-event-title="${escapeHtml(event.title)}"
                        title="Mark as Completed">
                    <i class="fas fa-check-circle"></i>
                </button>
            `;
        } else if (event.status === 'cancelled') {
            // Cancelled events can be reactivated
            buttons += `
                <button type="button" class="btn btn-sm btn-outline-success reactivate-event-btn"
                        data-event-id="${event.id}"
                        data-event-title="${escapeHtml(event.title)}"
                        title="Reactivate Event">
                    <i class="fas fa-play-circle"></i>
                </button>
            `;
        } else if (event.status === 'completed') {
            // Completed events can be reactivated
            buttons += `
                <button type="button" class="btn btn-sm btn-outline-success reactivate-event-btn"
                        data-event-id="${event.id}"
                        data-event-title="${escapeHtml(event.title)}"
                        title="Reactivate Event">
                    <i class="fas fa-play-circle"></i>
                </button>
            `;
        }
    } else {
        // For past events
        if (event.status === 'active') {
            // Past active events can be marked as completed
            buttons += `
                <button type="button" class="btn btn-sm btn-outline-success complete-event-btn"
                        data-event-id="${event.id}"
                        data-event-title="${escapeHtml(event.title)}"
                        title="Mark as Completed">
                    <i class="fas fa-check-circle"></i>
                </button>
            `;
        }
        
        // Archive button for:
        // 1. Past events that have ended (past end date)
        // 2. Completed events
        // 3. Draft events that are past their start date
        if (isPastEndDate || event.status === 'completed' || 
            (event.status === 'draft' && isPast)) {
            buttons += `
                <button type="button" class="btn btn-sm btn-outline-secondary archive-event-btn"
                        data-event-id="${event.id}"
                        data-event-title="${escapeHtml(event.title)}"
                        title="Archive Event">
                    <i class="fas fa-archive"></i>
                </button>
            `;
        }
    }

    return buttons;
}

// Initialize event listeners for custom field functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle target audience change for create modal
    const targetAudience = document.getElementById('audienceType');
    const customFieldSelection = document.getElementById('customFieldSelection');
    const customFieldId = document.getElementById('customFieldId');
    const customFieldValue = document.getElementById('customFieldValue');

    // Handle target audience change for edit modal
    const editTargetAudience = document.getElementById('editAudienceType');
    const editCustomFieldSelection = document.getElementById('editCustomFieldSelection');
    const editCustomFieldId = document.getElementById('editCustomFieldId');
    const editCustomFieldValue = document.getElementById('editCustomFieldValue');

    // Event listeners for create modal
    if (targetAudience) {
        targetAudience.addEventListener('change', function() {
            toggleCustomFieldSelection(this.value, false);
        });
    }

    if (customFieldId) {
        customFieldId.addEventListener('change', function() {
            loadCustomFieldValues(customFieldId, customFieldValue);
            // Clear validation error when field is selected
            if (this.value) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                const errorElement = this.parentNode.querySelector('.invalid-feedback');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }
        });
    }

    if (customFieldValue) {
        customFieldValue.addEventListener('change', function() {
            // Clear validation error when field value is selected
            if (this.value) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                const errorElement = this.parentNode.querySelector('.invalid-feedback');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }
        });
    }

    // Event listeners for edit modal
    if (editTargetAudience) {
        editTargetAudience.addEventListener('change', function() {
            toggleCustomFieldSelection(this.value, true);
        });
    }

    if (editCustomFieldId) {
        editCustomFieldId.addEventListener('change', function() {
            loadCustomFieldValues(editCustomFieldId, editCustomFieldValue);
            // Clear validation error when field is selected
            if (this.value) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                const errorElement = this.parentNode.querySelector('.invalid-feedback');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }
        });
    }

    if (editCustomFieldValue) {
        editCustomFieldValue.addEventListener('change', function() {
            // Clear validation error when field value is selected
            if (this.value) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                const errorElement = this.parentNode.querySelector('.invalid-feedback');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }
        });
    }

    // Initialize custom field selection on modal show
    $('#createEventModal').on('shown.bs.modal', function() {
        if (targetAudience) {
            toggleCustomFieldSelection(targetAudience.value, false);
        }
        // Custom editor is auto-initialized by the custom-editor.js script
    });

    $('#editEventModal').on('shown.bs.modal', function() {
        if (editTargetAudience) {
            toggleCustomFieldSelection(editTargetAudience.value, true);
        }
        // Custom editor is auto-initialized by the custom-editor.js script
    });
});
</script>

<!-- Custom Editor JavaScript -->
<script src="public/js/custom-editor.js"></script>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer">
    <!-- Toast notifications will be added here -->
</div>

<!-- Custom field layout styles -->
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

<!-- Include event validation and confirmation scripts -->
<script src="js/event_validation.js"></script>
<script src="js/event_confirmations.js"></script>

<?php require_once 'includes/footer.php'; ?>
