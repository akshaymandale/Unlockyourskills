/**
 * Opinion Polls Management JavaScript
 * Handles all dynamic functionality for opinion polls including:
 * - Poll listing and pagination
 * - Search and filtering
 * - Add/edit question functionality
 * - Modal management
 */

// Create namespace for opinion polls to prevent variable conflicts
window.opinionPollsState = {
    currentPage: 1,
    currentSearch: '',
    currentFilters: {
        status: '',
        type: '',
        audience: '',
        date_from: '',
        date_to: ''
    },
    // Question and option counters
    questionCounter: 0,
    optionCounter: 0,
    editQuestionCounter: 0,
    editOptionCounter: 0
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
    
    // Fallback: Set up add question button if validation script doesn't handle it
    setTimeout(function() {
        const addQuestionBtn = document.getElementById('addQuestionBtn');
        if (addQuestionBtn && !addQuestionBtn.hasAttribute('data-event-attached')) {
            console.log('Fallback: Setting up add question button event listener');
            addQuestionBtn.setAttribute('data-event-attached', 'true');
            addQuestionBtn.addEventListener('click', function(e) {
                console.log('Fallback: Add Question button clicked!');
                e.preventDefault();
                if (typeof addQuestion === 'function') {
                    addQuestion();
                }
            });
        }
    }, 1000);
});

function initializePollManagement() {
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

// Initialize filters and search functionality
function initializeFilters() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const typeFilter = document.getElementById('typeFilter');
    const audienceFilter = document.getElementById('audienceFilter');
    const dateRangeBtn = document.getElementById('dateRangeBtn');
    const applyDateFilter = document.getElementById('applyDateFilter');
    const clearDateFilter = document.getElementById('clearDateFilter');
    const clearAllFiltersBtn = document.getElementById('clearAllFiltersBtn');

    // Search with debounce
    if (searchInput) {
        const debouncedSearch = debounce((searchValue) => {
            window.opinionPollsState.currentSearch = searchValue;
            loadPolls(1);
        }, 500);

        searchInput.addEventListener('input', function() {
            debouncedSearch(this.value.trim());
        });
    }

    // Filter dropdowns
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            window.opinionPollsState.currentFilters.status = this.value;
            loadPolls(1);
        });
    }

    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            window.opinionPollsState.currentFilters.type = this.value;
            loadPolls(1);
        });
    }

    if (audienceFilter) {
        audienceFilter.addEventListener('change', function() {
            window.opinionPollsState.currentFilters.audience = this.value;
            loadPolls(1);
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

            if (dateFrom) window.opinionPollsState.currentFilters.date_from = dateFrom;
            if (dateTo) window.opinionPollsState.currentFilters.date_to = dateTo;

            loadPolls(1);
        });
    }

    // Clear date filter
    if (clearDateFilter) {
        clearDateFilter.addEventListener('click', function() {
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            delete window.opinionPollsState.currentFilters.date_from;
            delete window.opinionPollsState.currentFilters.date_to;
            loadPolls(1);
        });
    }

    // Clear all filters
    if (clearAllFiltersBtn) {
        clearAllFiltersBtn.addEventListener('click', function() {
            // Clear search
            if (searchInput) {
                searchInput.value = '';
                window.opinionPollsState.currentSearch = '';
            }

            // Clear filter dropdowns
            if (statusFilter) statusFilter.value = '';
            if (typeFilter) typeFilter.value = '';
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
            window.opinionPollsState.currentFilters = {
                status: '',
                type: '',
                audience: '',
                date_from: '',
                date_to: ''
            };

            // Reload polls
            loadPolls(1);
        });
    }
}

// Initialize character counting
function initializeCharacterCounting() {
    // Character counting for title
    const titleInput = document.getElementById('pollTitle');
    const titleCharCount = document.getElementById('titleCharCount');

    if (titleInput && titleCharCount) {
        titleInput.addEventListener('input', function() {
            titleCharCount.textContent = this.value.length;
        });
    }

    // Character counting for description
    const descriptionInput = document.getElementById('pollDescription');
    const descriptionCharCount = document.getElementById('descriptionCharCount');

    if (descriptionInput && descriptionCharCount) {
        descriptionInput.addEventListener('input', function() {
            descriptionCharCount.textContent = this.value.length;
        });
    }
}

// Initialize modals
function initializeModals() {
    // Modal event listeners are handled by the validation system
    // We just need to ensure the create poll form is initialized when the page loads
    console.log('Initializing modals - event listeners handled by validation system');
}

// Initialize create poll form
function initializeCreatePollForm() {
    console.log('initializeCreatePollForm called');
    
    // The validation script handles modal events and form initialization
    // We just need to ensure the functions are available globally
    console.log('Create poll form initialization - handled by validation system');
}

// Make functions globally accessible
window.addQuestion = addQuestion;
window.addOption = addOption;
window.removeQuestion = removeQuestion;
window.removeOption = removeOption;
window.updateOptionLabels = updateOptionLabels;
window.initializeCharacterCounting = initializeCharacterCounting;

function performSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        window.opinionPollsState.currentSearch = searchInput.value.trim();
        window.opinionPollsState.currentPage = 1; // Reset to first page
        loadPolls();
    }
}

function applyFilters() {
    const statusFilter = document.getElementById('statusFilter');
    const typeFilter = document.getElementById('typeFilter');
    const audienceFilter = document.getElementById('audienceFilter');
    const dateRangeFilter = document.getElementById('dateRangeFilter');

    window.opinionPollsState.currentFilters.status = statusFilter ? statusFilter.value : '';
    window.opinionPollsState.currentFilters.type = typeFilter ? typeFilter.value : '';
    window.opinionPollsState.currentFilters.target_audience = audienceFilter ? audienceFilter.value : '';
    window.opinionPollsState.currentFilters.date_range = dateRangeFilter ? dateRangeFilter.value : '';

    window.opinionPollsState.currentPage = 1; // Reset to first page
    loadPolls();
}

function clearAllFilters() {
    // Clear search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        window.opinionPollsState.currentSearch = '';
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

    window.opinionPollsState.currentFilters = {
        status: '',
        type: '',
        target_audience: '',
        date_range: ''
    };

    window.opinionPollsState.currentPage = 1;
    loadPolls();
}

function loadPolls(page = window.opinionPollsState.currentPage) {
    window.opinionPollsState.currentPage = page;

    // Show loading indicator
    const loadingSpinner = document.getElementById('loadingSpinner');
    const pollsGrid = document.getElementById('pollsGrid');
    const paginationContainer = document.getElementById('paginationContainer');
    const noResults = document.getElementById('noResults');

    if (loadingSpinner) loadingSpinner.style.display = 'block';
    if (pollsGrid) pollsGrid.innerHTML = '';
    if (noResults) noResults.style.display = 'none';

    // Prepare data for AJAX request
    const formData = new FormData();
    formData.append('controller', 'OpinionPollController');
    formData.append('action', 'ajaxSearch');
    formData.append('page', window.opinionPollsState.currentPage);
    formData.append('search', window.opinionPollsState.currentSearch || '');
    formData.append('status', window.opinionPollsState.currentFilters.status || '');
    formData.append('type', window.opinionPollsState.currentFilters.type || '');
    formData.append('audience', window.opinionPollsState.currentFilters.audience || '');
    if (window.opinionPollsState.currentFilters.date_from) formData.append('date_from', window.opinionPollsState.currentFilters.date_from);
    if (window.opinionPollsState.currentFilters.date_to) formData.append('date_to', window.opinionPollsState.currentFilters.date_to);

    // Make AJAX request
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayPolls(data.polls);
            updatePagination(data.pagination);
            updateResultsInfo(data.pagination);
        } else {
            console.error('Error loading polls:', data.error || data.message || 'Unknown error');
            showError(data.error || data.message || 'Failed to load opinion polls. Please try again.');
        }
    })
    .catch(error => {
        console.error('Network error:', error);
        showError('Network error. Please check your connection and try again.');
    })
    .finally(() => {
        // Hide loading indicator
        if (loadingSpinner) loadingSpinner.style.display = 'none';
    });
}

// Display polls in the grid
function displayPolls(polls) {
    const pollsGrid = document.getElementById('pollsGrid');
    const noResults = document.getElementById('noResults');

    if (!pollsGrid) return;

    pollsGrid.innerHTML = '';

    if (polls.length === 0) {
        noResults.style.display = 'block';
        return;
    }

    noResults.style.display = 'none';

    polls.forEach(poll => {
        const pollCard = createPollCard(poll);
        pollsGrid.appendChild(pollCard);
    });
}

// Update results info
function updateResultsInfo(pagination) {
    const resultsInfo = document.getElementById('resultsInfo');
    if (!resultsInfo) return;

    const { current_page, total_pages, total_count, per_page } = pagination;
    const start = ((current_page - 1) * per_page) + 1;
    const end = Math.min(current_page * per_page, total_count);

    if (total_count === 0) {
        resultsInfo.textContent = 'No opinion polls found';
    } else if (total_count === 1) {
        resultsInfo.textContent = 'Showing 1 opinion poll';
    } else if (total_pages === 1) {
        resultsInfo.textContent = `Showing all ${total_count} opinion polls`;
    } else {
        resultsInfo.textContent = `Showing ${start}-${end} of ${total_count} opinion polls`;
    }
}

// Show error message
function showError(message) {
    const pollsGrid = document.getElementById('pollsGrid');
    const noResults = document.getElementById('noResults');

    if (pollsGrid) {
        pollsGrid.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger text-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>
                        <h5>Error</h5>
                        <p>${message}</p>
                    </div>
                </div>
            </div>
        `;
    }

    if (noResults) {
        noResults.style.display = 'none';
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
    } else {
        pollState = `<span class="badge bg-${statusBadgeClass}">${poll.status.charAt(0).toUpperCase() + poll.status.slice(1)}</span>`;
    }

    col.innerHTML = `
        <div class="card h-100 poll-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="fas fa-poll text-primary me-2"></i>
                    <span class="badge bg-primary">OPINION POLL</span>
                </div>
                <div class="d-flex gap-1">
                    ${pollState}
                    <span class="badge bg-${typeBadgeClass}">${poll.type === 'single_choice' ? 'Single Choice' : 'Multiple Choice'}</span>
                    <span class="badge bg-${audienceBadgeClass}">${poll.target_audience === 'global' ? 'Global' : poll.target_audience === 'course_specific' ? 'Course Specific' : 'Group Specific'}</span>
                </div>
            </div>
            <div class="card-body">
                <h6 class="card-title">${escapeHtml(poll.title)}</h6>
                <p class="card-text text-muted small mb-3">
                    ${poll.description ? escapeHtml(poll.description) : 'No description available'}
                </p>
                
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
                </div>

                <div class="post-meta mt-3 pt-3 border-top">
                    <div class="d-flex justify-content-between text-muted small">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <span>${escapeHtml(poll.created_by_name || 'Unknown')}</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-calendar me-2"></i>
                            <span>${formatDateTime(poll.created_at || poll.start_datetime)}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="btn-group w-100" role="group">
                    ${getEditButton(poll)}
                    <button type="button" class="btn btn-sm btn-outline-info view-results-btn" 
                            data-poll-id="${poll.id}" title="View Results">
                        <i class="fas fa-chart-bar"></i>
                    </button>
                    ${getStatusActionButton(poll)}
                    ${getArchiveButton(poll)}
                    ${getDeleteButton(poll)}
                </div>
            </div>
        </div>
    `;

    return col;
}

// Generate edit button with restrictions
function getEditButton(poll) {
    if (!poll.can_edit) return '';
    const now = new Date();
    const startTime = new Date(poll.start_datetime);
    const isLive = startTime <= now && poll.status === 'active';
    const canEdit = !isLive && ['draft', 'paused'].includes(poll.status);

    if (canEdit) {
        return `
            <button type="button" class="btn btn-sm theme-btn-secondary edit-poll-btn"
                    data-poll-id="${poll.id}" title="Edit Poll">
                <i class="fas fa-edit"></i>
            </button>
        `;
    } else {
        const reason = isLive ? 'Poll is live' : 'Poll cannot be edited';
        return `
            <button type="button" class="btn btn-sm btn-secondary" disabled title="${reason}">
                <i class="fas fa-edit"></i>
            </button>
        `;
    }
}

// Generate delete button with restrictions
function getDeleteButton(poll) {
    if (!poll.can_delete) return '';
    const now = new Date();
    const startTime = new Date(poll.start_datetime);
    const isLive = startTime <= now && poll.status === 'active';

    if (!isLive) {
        return `
            <button type="button" class="btn btn-sm theme-btn-danger delete-poll-btn"
                    data-poll-id="${poll.id}" data-poll-title="${escapeHtml(poll.title)}" title="Delete Poll">
                <i class="fas fa-trash-alt"></i>
            </button>
        `;
    } else {
        return `
            <button type="button" class="btn btn-sm btn-secondary" disabled title="Poll is live and cannot be deleted">
                <i class="fas fa-trash-alt"></i>
            </button>
        `;
    }
}

// Generate status action button
function getStatusActionButton(poll) {
    switch (poll.status) {
        case 'draft':
            return `<button type="button" class="btn btn-sm btn-outline-success activate-poll-btn"
                            data-poll-id="${poll.id}" title="Activate Poll">
                        <i class="fas fa-play"></i>
                    </button>`;
        case 'active':
            return `<button type="button" class="btn btn-sm btn-outline-warning pause-poll-btn"
                            data-poll-id="${poll.id}" title="Pause Poll">
                        <i class="fas fa-pause"></i>
                    </button>`;
        case 'paused':
            return `<button type="button" class="btn btn-sm btn-outline-success resume-poll-btn"
                            data-poll-id="${poll.id}" title="Resume Poll">
                        <i class="fas fa-play"></i>
                    </button>`;
        case 'ended':
            return `<button type="button" class="btn btn-sm btn-outline-secondary archive-poll-btn"
                            data-poll-id="${poll.id}" title="Archive Poll">
                        <i class="fas fa-archive"></i>
                    </button>`;
        default:
            return '';
    }
}

// Generate archive button
function getArchiveButton(poll) {
    const now = new Date();
    const startTime = new Date(poll.start_datetime);
    const isLive = startTime <= now && poll.status === 'active';

    // Don't show archive button for live polls
    if (isLive) {
        return '';
    }

    // Show unarchive button for archived polls
    if (poll.status === 'archived') {
        return `<button type="button" class="btn btn-sm btn-outline-info unarchive-poll-btn"
                        data-poll-id="${poll.id}" data-poll-title="${escapeHtml(poll.title)}" 
                        title="Unarchive Poll">
                    <i class="fas fa-box-open"></i>
                </button>`;
    }

    // Show archive button for other statuses (draft, paused, ended)
    return `<button type="button" class="btn btn-sm btn-outline-secondary archive-poll-btn"
                    data-poll-id="${poll.id}" data-poll-title="${escapeHtml(poll.title)}" 
                    title="Archive Poll">
                <i class="fas fa-archive"></i>
            </button>`;
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper functions
function getStatusBadgeClass(status) {
    switch (status) {
        case 'draft': return 'secondary';
        case 'active': return 'success';
        case 'paused': return 'warning';
        case 'ended': return 'dark';
        case 'archived': return 'info';
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

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

// Update pagination
function updatePagination(pagination) {
    const paginationContainer = document.getElementById('paginationContainer');
    if (!paginationContainer) return;

    const { currentPage, totalPages } = pagination;
    
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    let paginationHTML = '<ul class="pagination">';
    
    // Previous button
    if (currentPage > 1) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
            </li>
        `;
    }

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        const isActive = i === currentPage ? 'active' : '';
        paginationHTML += `
            <li class="page-item ${isActive}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }

    // Next button
    if (currentPage < totalPages) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
            </li>
        `;
    }

    paginationHTML += '</ul>';
    paginationContainer.innerHTML = paginationHTML;
}

// Question management functions
function addQuestion() {
    console.log('addQuestion function called');
    window.opinionPollsState.questionCounter++;
    console.log('Question counter:', window.opinionPollsState.questionCounter);
    
    const questionsContainer = document.getElementById('questionsContainer');
    console.log('Questions container:', questionsContainer);
    
    if (!questionsContainer) {
        console.error('Questions container not found!');
        return;
    }

    const questionDiv = document.createElement('div');
    questionDiv.className = 'question-item border rounded p-3 mb-3';
    questionDiv.dataset.questionIndex = window.opinionPollsState.questionCounter;

    questionDiv.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Question ${window.opinionPollsState.questionCounter}</h6>
            <button type="button" class="btn btn-sm btn-outline-danger remove-question-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="mb-3">
            <label class="form-label">Question Text <span class="text-danger">*</span></label>
            <textarea class="form-control" name="questions[${window.opinionPollsState.questionCounter}][text]" rows="2"
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

function addOption(questionDiv) {
    window.opinionPollsState.optionCounter++;
    const optionsContainer = questionDiv.querySelector('.options-container');
    const questionIndex = questionDiv.dataset.questionIndex;

    const optionDiv = document.createElement('div');
    optionDiv.className = 'input-group mb-2';

    optionDiv.innerHTML = `
        <span class="input-group-text">${String.fromCharCode(65 + optionsContainer.children.length)}</span>
        <input type="text" class="form-control"
               name="questions[${questionIndex}][options][${window.opinionPollsState.optionCounter}][text]"
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

    questions.forEach(question => {
        addEditQuestion(question);
    });
}

// Reset edit poll form
function resetEditPollForm() {
    const form = document.getElementById('editPollForm');
    if (form) {
        form.reset();
        
        // Clear questions container
        const questionsContainer = document.getElementById('editQuestionsContainer');
        if (questionsContainer) {
            questionsContainer.innerHTML = '';
        }
        
        // Reset edit counters
        window.opinionPollsState.editQuestionCounter = 0;
        window.opinionPollsState.editOptionCounter = 0;
    }
}

// Edit mode question and option management
// Add question to edit modal
function addEditQuestion(questionData = null) {
    const questionsContainer = document.getElementById('editQuestionsContainer');
    const questionDiv = document.createElement('div');
    questionDiv.className = 'question-item border rounded p-3 mb-3';

    const questionIndex = window.opinionPollsState.editQuestionCounter++;
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
    const optionIndex = window.opinionPollsState.editOptionCounter++;
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

// Update edit character counts
function updateEditCharacterCounts() {
    const editTitleInput = document.getElementById('editPollTitle');
    const editTitleCharCount = document.getElementById('editTitleCharCount');
    const editDescriptionInput = document.getElementById('editPollDescription');
    const editDescriptionCharCount = document.getElementById('editDescriptionCharCount');

    if (editTitleInput && editTitleCharCount) {
        editTitleCharCount.textContent = editTitleInput.value.length;
    }
    if (editDescriptionInput && editDescriptionCharCount) {
        editDescriptionCharCount.textContent = editDescriptionInput.value.length;
    }
} 