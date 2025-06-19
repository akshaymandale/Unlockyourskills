<?php
// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php?controller=LoginController');
    exit();
}

$currentUser = $_SESSION['user'];
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content" data-opinion-poll-page="true">
    <div class="container add-question-container">

        <div class="back-arrow-container">
            <a href="index.php?controller=ManagePortalController#social" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-poll me-2"></i>Opinion Poll Management
            </h1>
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
                                <select class="form-select form-select-sm" id="statusFilter">
                                    <option value="">All Statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="active">Active</option>
                                    <option value="paused">Paused</option>
                                    <option value="ended">Ended</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select form-select-sm" id="typeFilter">
                                    <option value="">All Types</option>
                                    <option value="single_choice">Single Choice</option>
                                    <option value="multiple_choice">Multiple Choice</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select form-select-sm" id="audienceFilter">
                                    <option value="">All Audiences</option>
                                    <option value="global">Global</option>
                                    <option value="course_specific">Course Specific</option>
                                    <option value="group_specific">Group Specific</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <select class="form-select form-select-sm" id="dateRangeFilter">
                                    <option value="">All Dates</option>
                                    <option value="active">Currently Active</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="ended">Ended</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Search Bar in the middle -->
                    <div class="col-md-auto">
                        <div class="input-group input-group-sm">
                            <input type="text" id="searchInput" class="form-control"
                                placeholder="Search by title or description..."
                                title="Search polls by title or description">
                            <button type="button" id="searchButton" class="btn btn-outline-secondary"
                                title="Search polls">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Create Poll Button on the right -->
                    <div class="col-md-auto">
                        <button type="button" class="btn btn-sm theme-btn-primary" data-bs-toggle="modal" data-bs-target="#createPollModal"
                            title="Create New Opinion Poll">
                            <i class="fas fa-plus me-1"></i>Create New Poll
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

                    <!-- Empty space for consistency -->
                    <div class="col-md-auto">
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
                <div id="loadingIndicator" class="text-center loading-indicator" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading polls...</p>
                </div>

        <!-- Polls Container -->
        <div id="pollsContainer" class="fade-transition">
            <!-- Polls Grid -->
            <div id="pollsGrid" class="row">
                <!-- Polls will be loaded dynamically via AJAX -->
            </div>
        </div>

        <!-- ✅ Pagination -->
        <div id="paginationContainer" class="pagination-container" style="display: none;">
            <!-- Pagination will be generated dynamically -->
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
                                <option value="course_specific">Course Specific</option>
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
                        <i class="fas fa-save me-2"></i>Create Poll
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
                                <option value="course_specific">Course Specific</option>
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

<script>
// Dynamic opinion poll management with AJAX
let currentPage = 1;
let currentSearch = '';
let currentFilters = {
    status: '',
    type: '',
    target_audience: '',
    date_range: ''
};

document.addEventListener('DOMContentLoaded', function() {
    // Initialize poll management functionality
    initializePollManagement();

    // Load initial polls
    if (document.getElementById('pollsGrid')) {
        loadPolls(1);
    }

    // Initialize create poll form
    initializeCreatePollForm();
});

function initializePollManagement() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');

    if (searchInput && searchButton) {
        searchButton.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        // Add debounced search on input
        const debouncedSearch = debounce(performSearch, 500);
        searchInput.addEventListener('input', debouncedSearch);
    }

    // Filter functionality
    const statusFilter = document.getElementById('statusFilter');
    const typeFilter = document.getElementById('typeFilter');
    const audienceFilter = document.getElementById('audienceFilter');
    const dateRangeFilter = document.getElementById('dateRangeFilter');

    if (statusFilter) statusFilter.addEventListener('change', applyFilters);
    if (typeFilter) typeFilter.addEventListener('change', applyFilters);
    if (audienceFilter) audienceFilter.addEventListener('change', applyFilters);
    if (dateRangeFilter) dateRangeFilter.addEventListener('change', applyFilters);

    // Clear filters functionality
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearAllFilters);
    }

    // Pagination functionality
    document.addEventListener('click', function(e) {
        if (e.target.matches('.page-link[data-page]')) {
            e.preventDefault();
            const page = parseInt(e.target.getAttribute('data-page'));
            loadPolls(page);
        }
    });

    // Poll action buttons functionality (delete and status changes handled by confirmation module)
    document.addEventListener('click', function(e) {
        // Edit poll button
        if (e.target.closest('.edit-poll-btn')) {
            const pollId = e.target.closest('.edit-poll-btn').dataset.pollId;
            editPoll(pollId);
        }

        // View results button
        if (e.target.closest('.view-results-btn')) {
            const pollId = e.target.closest('.view-results-btn').dataset.pollId;
            viewPollResults(pollId);
        }

        // Note: Delete and status change buttons are handled by opinion_poll_confirmations.js
    });
}

function performSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        currentSearch = searchInput.value.trim();
        currentPage = 1; // Reset to first page
        loadPolls();
    }
}

function applyFilters() {
    const statusFilter = document.getElementById('statusFilter');
    const typeFilter = document.getElementById('typeFilter');
    const audienceFilter = document.getElementById('audienceFilter');
    const dateRangeFilter = document.getElementById('dateRangeFilter');

    currentFilters.status = statusFilter ? statusFilter.value : '';
    currentFilters.type = typeFilter ? typeFilter.value : '';
    currentFilters.target_audience = audienceFilter ? audienceFilter.value : '';
    currentFilters.date_range = dateRangeFilter ? dateRangeFilter.value : '';

    currentPage = 1; // Reset to first page
    loadPolls();
}

function clearAllFilters() {
    // Clear search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        currentSearch = '';
    }

    // Clear filters
    const statusFilter = document.getElementById('statusFilter');
    const typeFilter = document.getElementById('typeFilter');
    const audienceFilter = document.getElementById('audienceFilter');
    const dateRangeFilter = document.getElementById('dateRangeFilter');

    if (statusFilter) statusFilter.value = '';
    if (typeFilter) typeFilter.value = '';
    if (audienceFilter) audienceFilter.value = '';
    if (dateRangeFilter) dateRangeFilter.value = '';

    currentFilters = {
        status: '',
        type: '',
        target_audience: '',
        date_range: ''
    };

    currentPage = 1;
    loadPolls();
}

function loadPolls(page = currentPage) {
    currentPage = page;

    // Show loading indicator
    const loadingIndicator = document.getElementById('loadingIndicator');
    const pollsContainer = document.getElementById('pollsContainer');
    const paginationContainer = document.getElementById('paginationContainer');

    if (loadingIndicator) loadingIndicator.style.display = 'block';
    if (pollsContainer) pollsContainer.style.display = 'none';
    if (paginationContainer) paginationContainer.style.display = 'none';

    // Prepare data for AJAX request
    const formData = new FormData();
    formData.append('controller', 'OpinionPollController');
    formData.append('action', 'ajaxSearch');
    formData.append('page', currentPage);
    formData.append('search', currentSearch);
    formData.append('status', currentFilters.status);
    formData.append('type', currentFilters.type);
    formData.append('target_audience', currentFilters.target_audience);
    formData.append('date_range', currentFilters.date_range);

    // Make AJAX request
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updatePollsGrid(data.polls);
            updatePagination(data.pagination);
            updateSearchInfo(data.totalPolls);
        } else {
            console.error('Error loading polls:', data.message);
            // Show user-friendly message
            const grid = document.getElementById('pollsGrid');
            if (grid) {
                grid.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <h5>Error Loading Polls</h5>
                                <p>${data.message || 'Unknown error occurred'}</p>
                            </div>
                        </div>
                    </div>
                `;
            }
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        // Show user-friendly message
        const grid = document.getElementById('pollsGrid');
        if (grid) {
            grid.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>
                            <h5>Network Error</h5>
                            <p>Unable to load polls. Please check your connection and try again.</p>
                        </div>
                    </div>
                </div>
            `;
        }
    })
    .finally(() => {
        // Hide loading indicator
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        if (pollsContainer) pollsContainer.style.display = 'block';
        if (paginationContainer) paginationContainer.style.display = 'block';
    });
}

function updatePollsGrid(polls) {
    const grid = document.getElementById('pollsGrid');
    if (!grid) return;

    grid.innerHTML = '';

    if (polls.length === 0) {
        grid.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-poll me-2"></i>
                    <div>
                        <h5>No polls found</h5>
                        <p>Try adjusting your search terms or filters, or create a new poll</p>
                    </div>
                </div>
            </div>
        `;
        return;
    }

    polls.forEach(poll => {
        const pollCard = createPollCard(poll);
        grid.appendChild(pollCard);
    });
}

function createPollCard(poll) {
    const col = document.createElement('div');
    col.className = 'col-md-6 col-lg-4 mb-4';

    const statusBadgeClass = getStatusBadgeClass(poll.status);
    const typeBadgeClass = poll.type === 'single_choice' ? 'info' : 'warning';
    const audienceBadgeClass = getAudienceBadgeClass(poll.target_audience);

    // Format dates
    const startDate = formatDateTime(poll.start_datetime);
    const endDate = formatDateTime(poll.end_datetime);

    // Determine poll state
    const now = new Date();
    const startTime = new Date(poll.start_datetime);
    const endTime = new Date(poll.end_datetime);
    let pollState = '';

    if (poll.status === 'active') {
        if (now < startTime) {
            pollState = '<span class="badge bg-secondary">Upcoming</span>';
        } else if (now > endTime) {
            pollState = '<span class="badge bg-dark">Ended</span>';
        } else {
            pollState = '<span class="badge bg-success">Live</span>';
        }
    }

    col.innerHTML = `
        <div class="card h-100 poll-card">
            <div class="card-header d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="mb-1">${escapeHtml(poll.title)}</h6>
                    <div class="d-flex gap-1 flex-wrap">
                        <span class="badge bg-${statusBadgeClass}">${poll.status.charAt(0).toUpperCase() + poll.status.slice(1)}</span>
                        <span class="badge bg-${typeBadgeClass}">${poll.type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>
                        <span class="badge bg-${audienceBadgeClass}">${poll.target_audience.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>
                        ${pollState}
                    </div>
                </div>
            </div>

            <div class="card-body">
                ${poll.description ? `<p class="text-muted small mb-3">${escapeHtml(poll.description)}</p>` : ''}

                <div class="poll-info">
                    <div class="info-item mb-2">
                        <strong>Duration:</strong>
                        <div class="text-muted small">
                            <i class="fas fa-calendar-start me-1"></i>${startDate}<br>
                            <i class="fas fa-calendar-end me-1"></i>${endDate}
                        </div>
                    </div>

                    <div class="info-item mb-2">
                        <strong>Participation:</strong>
                        <div class="text-muted small">
                            <i class="fas fa-vote-yea me-1"></i>${poll.total_votes || 0} votes from ${poll.unique_voters || 0} users
                        </div>
                    </div>

                    <div class="info-item mb-2">
                        <strong>Settings:</strong>
                        <div class="d-flex gap-2 flex-wrap">
                            ${poll.allow_anonymous ? '<span class="badge bg-light text-dark">Anonymous</span>' : ''}
                            ${poll.allow_vote_change ? '<span class="badge bg-light text-dark">Vote Change</span>' : ''}
                            <span class="badge bg-light text-dark">Results: ${poll.show_results.replace('_', ' ')}</span>
                        </div>
                    </div>

                    <div class="info-item">
                        <strong>Created by:</strong> <span class="text-muted">${escapeHtml(poll.created_by_name || 'Unknown')}</span>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="btn-group w-100" role="group">
                    ${getEditButton(poll)}
                    <button type="button" class="btn btn-sm btn-outline-info view-results-btn"
                            data-poll-id="${poll.id}"
                            title="View Results">
                        <i class="fas fa-chart-bar"></i>
                    </button>
                    ${getStatusActionButton(poll)}
                    ${getDeleteButton(poll)}
                </div>
            </div>
        </div>
    `;

    return col;
}

// Generate edit button with restrictions
function getEditButton(poll) {
    const now = new Date();
    const startTime = new Date(poll.start_datetime);
    const isLive = startTime <= now && poll.status === 'active';
    const canEdit = !isLive && ['draft', 'paused'].includes(poll.status);

    if (canEdit) {
        return `
            <button type="button" class="btn btn-sm theme-btn-secondary edit-poll-btn"
                    data-poll-id="${poll.id}"
                    title="Edit Poll">
                <i class="fas fa-edit"></i>
            </button>
        `;
    } else {
        const reason = isLive ? 'Poll is live' : 'Poll cannot be edited';
        return `
            <button type="button" class="btn btn-sm btn-secondary"
                    disabled
                    title="${reason}">
                <i class="fas fa-edit"></i>
            </button>
        `;
    }
}

// Generate delete button with restrictions
function getDeleteButton(poll) {
    const now = new Date();
    const startTime = new Date(poll.start_datetime);
    const isLive = startTime <= now && poll.status === 'active';

    if (!isLive) {
        return `
            <button type="button" class="btn btn-sm theme-btn-danger delete-poll-btn"
                    data-poll-id="${poll.id}"
                    data-poll-title="${escapeHtml(poll.title)}"
                    title="Delete Poll">
                <i class="fas fa-trash-alt"></i>
            </button>
        `;
    } else {
        return `
            <button type="button" class="btn btn-sm btn-secondary"
                    disabled
                    title="Poll is live and cannot be deleted">
                <i class="fas fa-trash-alt"></i>
            </button>
        `;
    }
}

function getStatusBadgeClass(status) {
    switch (status) {
        case 'draft': return 'secondary';
        case 'active': return 'success';
        case 'paused': return 'warning';
        case 'ended': return 'dark';
        case 'archived': return 'light text-dark';
        default: return 'secondary';
    }
}

function getAudienceBadgeClass(audience) {
    switch (audience) {
        case 'global': return 'primary';
        case 'course_specific': return 'info';
        case 'group_specific': return 'warning';
        default: return 'secondary';
    }
}

function getStatusActionButton(poll) {
    switch (poll.status) {
        case 'draft':
            return `<button type="button" class="btn btn-sm btn-outline-success activate-poll-btn"
                            data-poll-id="${poll.id}"
                            title="Activate Poll">
                        <i class="fas fa-play"></i>
                    </button>`;
        case 'active':
            return `<button type="button" class="btn btn-sm btn-outline-warning pause-poll-btn"
                            data-poll-id="${poll.id}"
                            title="Pause Poll">
                        <i class="fas fa-pause"></i>
                    </button>`;
        case 'paused':
            return `<button type="button" class="btn btn-sm btn-outline-success resume-poll-btn"
                            data-poll-id="${poll.id}"
                            title="Resume Poll">
                        <i class="fas fa-play"></i>
                    </button>`;
        case 'ended':
            return `<button type="button" class="btn btn-sm btn-outline-secondary archive-poll-btn"
                            data-poll-id="${poll.id}"
                            title="Archive Poll">
                        <i class="fas fa-archive"></i>
                    </button>`;
        default:
            return '';
    }
}

function updatePagination(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;

    // Hide pagination if no polls found
    if (pagination.totalPolls === 0) {
        container.style.display = 'none';
        return;
    }

    // Only show pagination if there are more than 10 total polls
    if (pagination.totalPolls <= 10) {
        // Show total count when no pagination needed
        const plural = pagination.totalPolls !== 1 ? 's' : '';
        container.innerHTML = `
            <div class="text-center text-muted small">
                Showing all ${pagination.totalPolls} poll${plural}
            </div>
        `;
        container.style.display = 'block';
        return;
    }

    // Generate pagination HTML
    let paginationHtml = '<nav aria-label="Poll pagination"><ul class="pagination justify-content-center">';

    // Previous button
    if (pagination.currentPage > 1) {
        paginationHtml += `<li class="page-item">
            <a class="page-link" href="#" data-page="${pagination.currentPage - 1}">Previous</a>
        </li>`;
    }

    // Page numbers
    for (let i = 1; i <= pagination.totalPages; i++) {
        const isActive = i === pagination.currentPage ? 'active' : '';
        paginationHtml += `<li class="page-item ${isActive}">
            <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>`;
    }

    // Next button
    if (pagination.currentPage < pagination.totalPages) {
        paginationHtml += `<li class="page-item">
            <a class="page-link" href="#" data-page="${pagination.currentPage + 1}">Next</a>
        </li>`;
    }

    paginationHtml += '</ul></nav>';
    container.innerHTML = paginationHtml;
    container.style.display = 'block';
}

function updateSearchInfo(totalPolls) {
    const searchInfo = document.getElementById('searchResultsInfo');
    const resultsText = document.getElementById('resultsText');

    if (!searchInfo || !resultsText) return;

    if (currentSearch || currentFilters.status || currentFilters.type || currentFilters.target_audience || currentFilters.date_range) {
        let infoText = `Showing ${totalPolls} result${totalPolls !== 1 ? 's' : ''}`;

        if (currentSearch) {
            infoText += ` for search: "<strong>${escapeHtml(currentSearch)}</strong>"`;
        }

        if (currentFilters.status || currentFilters.type || currentFilters.target_audience || currentFilters.date_range) {
            infoText += ' with filters applied';
        }

        resultsText.innerHTML = infoText;
        searchInfo.style.display = 'block';
    } else {
        searchInfo.style.display = 'none';
    }
}

// Helper functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Initialize create poll form
function initializeCreatePollForm() {
    const addQuestionBtn = document.getElementById('addQuestionBtn');
    const questionsContainer = document.getElementById('questionsContainer');
    const createPollForm = document.getElementById('createPollForm');

    if (addQuestionBtn) {
        addQuestionBtn.addEventListener('click', addQuestion);
    }

    // Add initial question
    if (questionsContainer) {
        addQuestion();
    }

    // Form submission is handled by the validation system
    // No need to add another event listener here

    // Initialize character counting
    initializeCharacterCounting();

    // Initialize edit modal
    initializeEditPollModal();
}

let questionCounter = 0;

function addQuestion() {
    questionCounter++;
    const questionsContainer = document.getElementById('questionsContainer');

    const questionDiv = document.createElement('div');
    questionDiv.className = 'question-item border rounded p-3 mb-3';
    questionDiv.dataset.questionIndex = questionCounter;

    questionDiv.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Question ${questionCounter}</h6>
            <button type="button" class="btn btn-sm btn-outline-danger remove-question-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="mb-3">
            <label class="form-label">Question Text <span class="text-danger">*</span></label>
            <textarea class="form-control" name="questions[${questionCounter}][text]" rows="2"
                placeholder="Enter your question..."></textarea>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label mb-0">Answer Options</label>
                <button type="button" class="btn btn-sm btn-outline-primary add-option-btn">
                    <i class="fas fa-plus me-1"></i>Add Option
                </button>
            </div>
            <div class="options-container">
                <!-- Options will be added here -->
            </div>
        </div>
    `;

    questionsContainer.appendChild(questionDiv);

    // Add event listeners
    const removeBtn = questionDiv.querySelector('.remove-question-btn');
    const addOptionBtn = questionDiv.querySelector('.add-option-btn');

    removeBtn.addEventListener('click', () => removeQuestion(questionDiv));
    addOptionBtn.addEventListener('click', () => addOption(questionDiv));

    // Add validation event listeners to the question textarea
    const questionTextarea = questionDiv.querySelector('textarea');
    if (questionTextarea) {
        questionTextarea.addEventListener('blur', function() {
            if (typeof window.validatePollField === 'function') {
                window.validatePollField(this);
            }
        });
    }

    // Add initial options
    addOption(questionDiv);
    addOption(questionDiv);
}

function removeQuestion(questionDiv) {
    const questionsContainer = document.getElementById('questionsContainer');
    if (questionsContainer.children.length > 1) {
        questionDiv.remove();
    } else {
        alert('At least one question is required.');
    }
}

let optionCounter = 0;

function addOption(questionDiv) {
    optionCounter++;
    const optionsContainer = questionDiv.querySelector('.options-container');
    const questionIndex = questionDiv.dataset.questionIndex;

    const optionDiv = document.createElement('div');
    optionDiv.className = 'input-group mb-2';

    optionDiv.innerHTML = `
        <span class="input-group-text">${String.fromCharCode(65 + optionsContainer.children.length)}</span>
        <input type="text" class="form-control"
               name="questions[${questionIndex}][options][${optionCounter}][text]"
               placeholder="Enter option text...">
        <button type="button" class="btn btn-outline-danger remove-option-btn" type="button">
            <i class="fas fa-times"></i>
        </button>
    `;

    optionsContainer.appendChild(optionDiv);

    // Add remove option event listener
    const removeBtn = optionDiv.querySelector('.remove-option-btn');
    removeBtn.addEventListener('click', () => removeOption(optionDiv, optionsContainer));

    // Add validation event listener to the option input
    const optionInput = optionDiv.querySelector('input[type="text"]');
    if (optionInput) {
        optionInput.addEventListener('blur', function() {
            if (typeof window.validatePollField === 'function') {
                window.validatePollField(this);
            }
        });
    }
}

function removeOption(optionDiv, optionsContainer) {
    if (optionsContainer.children.length > 2) {
        optionDiv.remove();
        // Update option labels
        updateOptionLabels(optionsContainer);
    } else {
        alert('At least two options are required.');
    }
}

function updateOptionLabels(optionsContainer) {
    const options = optionsContainer.querySelectorAll('.input-group');
    options.forEach((option, index) => {
        const label = option.querySelector('.input-group-text');
        label.textContent = String.fromCharCode(65 + index);
    });
}

// Form submission is now handled by the validation system
// No duplicate function needed here

// Poll action functions
function editPoll(pollId) {
    if (!pollId) {
        console.error('Poll ID is required for editing');
        return;
    }

    // Show loading state
    const editButtons = document.querySelectorAll(`.edit-poll-btn[data-poll-id="${pollId}"]`);
    editButtons.forEach(btn => {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
    });

    // Fetch poll data
    fetch(`index.php?controller=OpinionPollController&action=edit&id=${pollId}&ajax=1`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.can_edit) {
                populateEditModal(data.poll, data.questions);
                const editModal = new bootstrap.Modal(document.getElementById('editPollModal'));
                editModal.show();
            } else {
                const errorMessage = data.error || 'Failed to load poll data for editing';
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(errorMessage, 'error');
                } else {
                    alert('Error: ' + errorMessage);
                }
            }
        })
        .catch(error => {
            console.error('Error loading poll data:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Network error. Please try again.', 'error');
            } else {
                alert('Network error. Please try again.');
            }
        })
        .finally(() => {
            // Reset button states
            editButtons.forEach(btn => {
                btn.innerHTML = '<i class="fas fa-edit"></i>';
                btn.disabled = false;
            });
        });
}

// Populate edit modal with poll data
function populateEditModal(poll, questions) {
    // Reset form first
    resetEditPollForm();

    // Populate basic poll information
    document.getElementById('edit_poll_id').value = poll.id;
    document.getElementById('editPollType').value = poll.type;
    document.getElementById('editPollTitle').value = poll.title;
    document.getElementById('editTargetAudience').value = poll.target_audience;
    document.getElementById('editPollDescription').value = poll.description || '';
    document.getElementById('editStartDatetime').value = poll.start_datetime;
    document.getElementById('editEndDatetime').value = poll.end_datetime;
    document.getElementById('editShowResults').value = poll.show_results;
    document.getElementById('editAllowAnonymous').checked = poll.allow_anonymous == 1;
    document.getElementById('editAllowVoteChange').checked = poll.allow_vote_change == 1;

    // Update character counts
    updateEditCharacterCounts();

    // Populate questions
    const questionsContainer = document.getElementById('editQuestionsContainer');
    questionsContainer.innerHTML = '';

    // Reset counters for edit mode
    editQuestionCounter = 0;
    editOptionCounter = 0;

    if (questions && questions.length > 0) {
        questions.forEach((question, index) => {
            addEditQuestion(question);
        });
    } else {
        // Add one empty question if no questions exist
        addEditQuestion();
    }

    // Initialize edit form validation
    if (typeof window.attachEditPollValidation === 'function') {
        window.attachEditPollValidation('editPollForm');
    }
}

// Reset edit poll form
function resetEditPollForm() {
    const form = document.getElementById('editPollForm');
    if (form) {
        form.reset();

        // Clear all error messages and styling
        form.querySelectorAll('.error-message').forEach(error => {
            error.textContent = '';
            error.style.display = 'none';
        });

        form.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });

        // Clear questions container
        const questionsContainer = document.getElementById('editQuestionsContainer');
        if (questionsContainer) {
            questionsContainer.innerHTML = '';
        }
    }
}

// Update character counts for edit modal
function updateEditCharacterCounts() {
    const titleInput = document.getElementById('editPollTitle');
    const titleCharCount = document.getElementById('editTitleCharCount');
    const descriptionInput = document.getElementById('editPollDescription');
    const descriptionCharCount = document.getElementById('editDescriptionCharCount');

    if (titleInput && titleCharCount) {
        titleCharCount.textContent = titleInput.value.length;
        if (titleInput.value.length > 255) {
            titleCharCount.style.color = '#dc3545';
        } else {
            titleCharCount.style.color = '#6c757d';
        }
    }

    if (descriptionInput && descriptionCharCount) {
        descriptionCharCount.textContent = descriptionInput.value.length;
        if (descriptionInput.value.length > 1000) {
            descriptionCharCount.style.color = '#dc3545';
        } else {
            descriptionCharCount.style.color = '#6c757d';
        }
    }
}

// Edit mode question and option management
let editQuestionCounter = 0;
let editOptionCounter = 0;

// Add question to edit modal
function addEditQuestion(questionData = null) {
    const questionsContainer = document.getElementById('editQuestionsContainer');
    const questionDiv = document.createElement('div');
    questionDiv.className = 'question-item border rounded p-3 mb-3';

    const questionIndex = editQuestionCounter++;
    const questionText = questionData ? questionData.question_text : '';

    questionDiv.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">
                <i class="fas fa-question-circle me-2 text-primary"></i>Question ${questionIndex + 1}
            </h6>
            <button type="button" class="btn btn-sm btn-outline-danger remove-question-btn">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>

        <div class="mb-3">
            <label class="form-label">Question Text <span class="text-danger">*</span></label>
            <textarea class="form-control" name="questions[${questionIndex}][text]" rows="2"
                placeholder="Enter your question..." required>${questionText}</textarea>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="form-label mb-0">Answer Options</label>
                <button type="button" class="btn btn-sm theme-btn-secondary add-option-btn">
                    <i class="fas fa-plus me-1"></i>Add Option
                </button>
            </div>
            <div class="options-container">
                <!-- Options will be added here -->
            </div>
        </div>
    `;

    questionsContainer.appendChild(questionDiv);

    // Add event listeners
    const removeBtn = questionDiv.querySelector('.remove-question-btn');
    const addOptionBtn = questionDiv.querySelector('.add-option-btn');

    removeBtn.addEventListener('click', () => removeEditQuestion(questionDiv));
    addOptionBtn.addEventListener('click', () => addEditOption(questionDiv));

    // Add validation event listeners to the question textarea
    const questionTextarea = questionDiv.querySelector('textarea');
    if (questionTextarea) {
        questionTextarea.addEventListener('blur', function() {
            if (typeof window.validatePollField === 'function') {
                window.validatePollField(this);
            }
        });
    }

    // Add options
    if (questionData && questionData.options && questionData.options.length > 0) {
        questionData.options.forEach(option => {
            addEditOption(questionDiv, option.option_text);
        });
    } else {
        // Add default two options
        addEditOption(questionDiv);
        addEditOption(questionDiv);
    }

    updateEditQuestionNumbers();
}

// Delete and status change functions are now handled by opinion_poll_confirmations.js

// Remove question from edit modal
function removeEditQuestion(questionDiv) {
    const questionsContainer = document.getElementById('editQuestionsContainer');
    if (questionsContainer.children.length > 1) {
        questionDiv.remove();
        updateEditQuestionNumbers();
    } else {
        if (typeof showSimpleToast === 'function') {
            showSimpleToast('At least one question is required.', 'warning');
        } else {
            alert('At least one question is required.');
        }
    }
}

// Add option to edit question
function addEditOption(questionDiv, optionText = '') {
    const optionsContainer = questionDiv.querySelector('.options-container');
    const optionDiv = document.createElement('div');
    optionDiv.className = 'input-group mb-2';

    const questionIndex = Array.from(questionDiv.parentNode.children).indexOf(questionDiv);
    const optionIndex = editOptionCounter++;
    const optionLabel = String.fromCharCode(65 + optionsContainer.children.length);

    optionDiv.innerHTML = `
        <span class="input-group-text">${optionLabel}</span>
        <input type="text" class="form-control" name="questions[${questionIndex}][options][${optionIndex}][text]"
               placeholder="Enter option text..." value="${optionText}" required>
        <button type="button" class="btn btn-outline-danger remove-option-btn" type="button">
            <i class="fas fa-times"></i>
        </button>
    `;

    optionsContainer.appendChild(optionDiv);

    // Add remove option event listener
    const removeBtn = optionDiv.querySelector('.remove-option-btn');
    removeBtn.addEventListener('click', () => removeEditOption(optionDiv, optionsContainer));

    // Add validation event listener to the option input
    const optionInput = optionDiv.querySelector('input[type="text"]');
    if (optionInput) {
        optionInput.addEventListener('blur', function() {
            if (typeof window.validatePollField === 'function') {
                window.validatePollField(this);
            }
        });
    }
}

// Remove option from edit question
function removeEditOption(optionDiv, optionsContainer) {
    if (optionsContainer.children.length > 2) {
        optionDiv.remove();
        updateEditOptionLabels(optionsContainer);
    } else {
        if (typeof showSimpleToast === 'function') {
            showSimpleToast('At least two options are required per question.', 'warning');
        } else {
            alert('At least two options are required per question.');
        }
    }
}

// Update question numbers in edit modal
function updateEditQuestionNumbers() {
    const questionsContainer = document.getElementById('editQuestionsContainer');
    const questions = questionsContainer.querySelectorAll('.question-item');

    questions.forEach((question, index) => {
        const questionTitle = question.querySelector('h6');
        questionTitle.innerHTML = `<i class="fas fa-question-circle me-2 text-primary"></i>Question ${index + 1}`;

        // Update question name attributes
        const textarea = question.querySelector('textarea');
        textarea.name = `questions[${index}][text]`;

        // Update option name attributes
        const options = question.querySelectorAll('.options-container input[type="text"]');
        options.forEach((option, optIndex) => {
            option.name = `questions[${index}][options][${optIndex}][text]`;
        });
    });
}

// Update option labels in edit modal
function updateEditOptionLabels(optionsContainer) {
    const options = optionsContainer.querySelectorAll('.input-group');
    options.forEach((option, index) => {
        const label = option.querySelector('.input-group-text');
        label.textContent = String.fromCharCode(65 + index);
    });
}

function viewPollResults(pollId) {
    // TODO: Implement view results functionality
    alert('View poll results functionality - Coming soon!');
}

// Initialize character counting for form fields
function initializeCharacterCounting() {
    // Poll title character count
    const titleInput = document.getElementById('pollTitle');
    const titleCharCount = document.getElementById('titleCharCount');

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

    // Poll description character count
    const descriptionInput = document.getElementById('pollDescription');
    const descriptionCharCount = document.getElementById('descriptionCharCount');

    if (descriptionInput && descriptionCharCount) {
        descriptionInput.addEventListener('input', function() {
            descriptionCharCount.textContent = this.value.length;
            if (this.value.length > 1000) {
                descriptionCharCount.style.color = '#dc3545';
            } else {
                descriptionCharCount.style.color = '#6c757d';
            }
        });
    }
}

// Initialize edit poll modal
function initializeEditPollModal() {
    const editAddQuestionBtn = document.getElementById('editAddQuestionBtn');
    const editPollForm = document.getElementById('editPollForm');

    if (editAddQuestionBtn) {
        editAddQuestionBtn.addEventListener('click', () => addEditQuestion());
    }

    // Edit form submission is handled by the validation system
    // No need to add another event listener here

    // Initialize character counting for edit modal
    initializeEditCharacterCounting();
}

// Edit form submission is now handled by the validation system
// No duplicate function needed here

// Initialize character counting for edit modal fields
function initializeEditCharacterCounting() {
    // Edit poll title character count
    const editTitleInput = document.getElementById('editPollTitle');
    const editTitleCharCount = document.getElementById('editTitleCharCount');

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

    // Edit poll description character count
    const editDescriptionInput = document.getElementById('editPollDescription');
    const editDescriptionCharCount = document.getElementById('editDescriptionCharCount');

    if (editDescriptionInput && editDescriptionCharCount) {
        editDescriptionInput.addEventListener('input', function() {
            editDescriptionCharCount.textContent = this.value.length;
            if (this.value.length > 1000) {
                editDescriptionCharCount.style.color = '#dc3545';
            } else {
                editDescriptionCharCount.style.color = '#6c757d';
            }
        });
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
</script>

<!-- Include necessary JavaScript files for proper functionality -->
<script src="public/js/modules/assessment_confirmations.js"></script>
<script src="public/js/modules/opinion_poll_confirmations.js"></script>
<script src="public/js/opinion_poll_validation.js"></script>

<?php include 'includes/footer.php'; ?>
