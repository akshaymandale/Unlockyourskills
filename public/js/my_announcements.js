/**
 * My Announcements JavaScript
 * Handles all dynamic functionality for user-facing announcements including:
 * - Announcement listing and pagination
 * - Search and filtering
 * - Announcement acknowledgment
 * - Modal management
 */

// Create namespace for my announcements to prevent variable conflicts
window.myAnnouncementsState = {
    currentPage: 1,
    currentSearch: '',
    currentFilters: {
        urgency: '',
        acknowledged: '',
        date_from: '',
        date_to: ''
    },
    isLoading: false
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeMyAnnouncements();
});

// Initialize my announcements functionality
function initializeMyAnnouncements() {
    // Load initial announcements
    loadAnnouncements(1);

    // Set up event listeners
    setupEventListeners();
}

// Set up event listeners
function setupEventListeners() {
    // Search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                window.myAnnouncementsState.currentSearch = this.value;
                loadAnnouncements(1);
            }, 500);
        });
    }

    // Filter dropdowns
    const urgencyFilter = document.getElementById('urgencyFilter');
    if (urgencyFilter) {
        urgencyFilter.addEventListener('change', function() {
            window.myAnnouncementsState.currentFilters.urgency = this.value;
            loadAnnouncements(1);
        });
    }

    const acknowledgmentFilter = document.getElementById('acknowledgmentFilter');
    if (acknowledgmentFilter) {
        acknowledgmentFilter.addEventListener('change', function() {
            window.myAnnouncementsState.currentFilters.acknowledged = this.value;
            loadAnnouncements(1);
        });
    }

    // Date range toggle
    const dateRangeBtn = document.getElementById('dateRangeBtn');
    if (dateRangeBtn) {
        dateRangeBtn.addEventListener('click', function() {
            const dateRangeInputs = document.getElementById('dateRangeInputs');
            dateRangeInputs.classList.toggle('d-none');
        });
    }

    // Apply date filter
    const applyDateFilter = document.getElementById('applyDateFilter');
    if (applyDateFilter) {
        applyDateFilter.addEventListener('click', function() {
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            window.myAnnouncementsState.currentFilters.date_from = dateFrom;
            window.myAnnouncementsState.currentFilters.date_to = dateTo;
            loadAnnouncements(1);
        });
    }

    // Clear date filter
    const clearDateFilter = document.getElementById('clearDateFilter');
    if (clearDateFilter) {
        clearDateFilter.addEventListener('click', function() {
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            window.myAnnouncementsState.currentFilters.date_from = '';
            window.myAnnouncementsState.currentFilters.date_to = '';
            loadAnnouncements(1);
        });
    }

    // Clear all filters
    const clearAllFiltersBtn = document.getElementById('clearAllFiltersBtn');
    if (clearAllFiltersBtn) {
        clearAllFiltersBtn.addEventListener('click', function() {
            // Reset all filters
            window.myAnnouncementsState.currentSearch = '';
            window.myAnnouncementsState.currentFilters = {
                urgency: '',
                acknowledged: '',
                date_from: '',
                date_to: ''
            };

            // Reset form elements
            document.getElementById('searchInput').value = '';
            document.getElementById('urgencyFilter').value = '';
            document.getElementById('acknowledgmentFilter').value = '';
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            document.getElementById('dateRangeInputs').classList.add('d-none');

            loadAnnouncements(1);
        });
    }

    // Pagination clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.pagination .page-link')) {
            e.preventDefault();
            const pageLink = e.target.closest('.page-link');
            const page = parseInt(pageLink.dataset.page);
            if (page && page !== window.myAnnouncementsState.currentPage) {
                loadAnnouncements(page);
            }
        }

        // View announcement button
        if (e.target.closest('.view-announcement-btn')) {
            const announcementId = e.target.closest('.view-announcement-btn').dataset.announcementId;
            viewAnnouncement(announcementId);
        }

        // Acknowledge announcement button
        if (e.target.closest('.acknowledge-announcement-btn')) {
            const announcementId = e.target.closest('.acknowledge-announcement-btn').dataset.announcementId;
            acknowledgeAnnouncement(announcementId);
        }
    });
}

// Load announcements with pagination and filters
function loadAnnouncements(page = 1) {
    if (window.myAnnouncementsState.isLoading) return;

    window.myAnnouncementsState.isLoading = true;
    window.myAnnouncementsState.currentPage = page;

    // Show loading spinner
    showLoadingSpinner();

    // Build query parameters
    const params = new URLSearchParams({
        page: page,
        limit: 10
    });

    // Add search
    if (window.myAnnouncementsState.currentSearch) {
        params.append('search', window.myAnnouncementsState.currentSearch);
    }

    // Add filters
    Object.keys(window.myAnnouncementsState.currentFilters).forEach(key => {
        const value = window.myAnnouncementsState.currentFilters[key];
        if (value) {
            params.append(key, value);
        }
    });

    // Fetch announcements
    fetch(`index.php?controller=AnnouncementController&action=getUserAnnouncements&${params.toString()}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        window.myAnnouncementsState.isLoading = false;
        hideLoadingSpinner();

        if (data.success) {
            renderAnnouncements(data.announcements);
            renderPagination(data.pagination);
            updateResultsInfo(data.pagination);
        } else {
            showError(data.error || 'Failed to load announcements');
            renderEmptyState();
        }
    })
    .catch(error => {
        window.myAnnouncementsState.isLoading = false;
        hideLoadingSpinner();
        console.error('Error:', error);
        showError('Network error. Please try again.');
        renderEmptyState();
    });
}

// Render announcements list
function renderAnnouncements(announcements) {
    const container = document.getElementById('announcementsContainer');
    if (!container) return;

    if (announcements.length === 0) {
        renderEmptyState();
        return;
    }

    container.innerHTML = announcements.map(announcement => createAnnouncementCard(announcement)).join('');
}

// Create announcement card HTML
function createAnnouncementCard(announcement) {
    const urgencyClass = getUrgencyClass(announcement.urgency);
    const urgencyIcon = getUrgencyIcon(announcement.urgency);
    const isAcknowledged = announcement.user_acknowledged > 0;
    const acknowledgmentBadge = isAcknowledged ? 
        '<span class="status-badge status-read"><i class="fas fa-check-circle me-1"></i>Read</span>' : 
        '<span class="status-badge status-unread"><i class="fas fa-exclamation-circle me-1"></i>Unread</span>';

    return `
        <div class="modern-announcement-card mb-4 ${urgencyClass}">
            <div class="announcement-card-header">
                <div class="announcement-title-section">
                    <h5 class="announcement-title">${escapeHtml(announcement.title)}</h5>
                    <div class="announcement-meta">
                        <span class="announcement-creator">
                            <i class="fas fa-user me-1"></i>
                            ${escapeHtml(announcement.creator_name || 'Unknown')}
                        </span>
                        <span class="announcement-divider">•</span>
                        <span class="announcement-date">
                            <i class="fas fa-calendar me-1"></i>
                            ${formatDate(announcement.created_at)}
                        </span>
                    </div>
                </div>
                <div class="announcement-badges">
                    <span class="urgency-badge urgency-${announcement.urgency}">
                        <i class="${urgencyIcon} me-1"></i>
                        ${announcement.urgency.charAt(0).toUpperCase() + announcement.urgency.slice(1)}
                    </span>
                    ${acknowledgmentBadge}
                </div>
            </div>
            
            <div class="announcement-card-body">
                <div class="announcement-description">
                    <p class="description-text">${truncateText(stripHtml(announcement.body), 150)}</p>
                </div>
                
                ${announcement.require_acknowledgment ? `
                <div class="acknowledgment-info">
                    <i class="fas fa-check-circle me-1"></i>
                    ${announcement.acknowledgment_count || 0} acknowledgments
                </div>
                ` : ''}
            </div>
            
            <div class="announcement-card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-modern-outline view-announcement-btn" 
                            data-announcement-id="${announcement.id}">
                        <i class="fas fa-eye me-1"></i>View Details
                    </button>
                    ${announcement.require_acknowledgment && !isAcknowledged ? `
                    <button type="button" class="btn btn-modern-primary acknowledge-announcement-btn" 
                            data-announcement-id="${announcement.id}">
                        <i class="fas fa-check me-1"></i>Acknowledge
                    </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
}

// View announcement details
function viewAnnouncement(announcementId) {
    if (!announcementId) return;

    const modal = new bootstrap.Modal(document.getElementById('announcementDetailModal'));
    const content = document.getElementById('announcementDetailContent');
    
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading announcement details...</p>
        </div>
    `;
    
    modal.show();

    // Fetch announcement details
    fetch(`index.php?controller=AnnouncementController&action=getAnnouncementById&id=${announcementId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            content.innerHTML = createAnnouncementDetailContent(data.announcement);
        } else {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${data.error || 'Failed to load announcement details'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        content.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Network error. Please try again.
            </div>
        `;
    });
}

// Create announcement detail content
function createAnnouncementDetailContent(announcement) {
    const urgencyClass = getUrgencyClass(announcement.urgency);
    const urgencyIcon = getUrgencyIcon(announcement.urgency);
    const isAcknowledged = announcement.user_acknowledged > 0;

    return `
        <div class="modern-announcement-detail">
            <div class="detail-header ${urgencyClass}">
                <div class="header-badges">
                    <span class="urgency-badge urgency-${announcement.urgency}">
                        <i class="${urgencyIcon} me-1"></i>
                        ${announcement.urgency.charAt(0).toUpperCase() + announcement.urgency.slice(1)}
                    </span>
                    <span class="status-badge ${isAcknowledged ? 'status-read' : 'status-unread'}">
                        <i class="fas ${isAcknowledged ? 'fa-check-circle' : 'fa-exclamation-circle'} me-1"></i>
                        ${isAcknowledged ? 'Read' : 'Unread'}
                    </span>
                </div>
                <h4 class="detail-title">${escapeHtml(announcement.title)}</h4>
                <div class="detail-meta">
                    <div class="meta-item">
                        <i class="fas fa-user me-1"></i>
                        <span>Created by ${escapeHtml(announcement.creator_name || 'Unknown')}</span>
                    </div>
                    <div class="meta-divider">•</div>
                    <div class="meta-item">
                        <i class="fas fa-calendar me-1"></i>
                        <span>${formatDate(announcement.created_at)}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-body">
                <div class="detail-content">
                    ${announcement.body}
                </div>
            </div>
            
            ${announcement.cta_label && announcement.cta_url ? `
            <div class="detail-cta">
                <a href="${announcement.cta_url}" target="_blank" class="btn btn-modern-primary">
                    <i class="fas fa-external-link-alt me-1"></i>
                    ${escapeHtml(announcement.cta_label)}
                </a>
            </div>
            ` : ''}
            
            ${announcement.require_acknowledgment && !isAcknowledged ? `
            <div class="detail-actions">
                <button type="button" class="btn btn-modern-primary acknowledge-announcement-btn" 
                        data-announcement-id="${announcement.id}">
                    <i class="fas fa-check me-1"></i>Acknowledge Announcement
                </button>
            </div>
            ` : ''}
            
            ${announcement.require_acknowledgment && isAcknowledged ? `
            <div class="detail-actions">
                <div class="acknowledgment-confirmation">
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <div>
                            <strong>You have acknowledged this announcement.</strong>
                            <div class="small text-muted mt-1">Thank you for confirming you have read this important information.</div>
                        </div>
                    </div>
                </div>
            </div>
            ` : ''}
        </div>
    `;
}

// Acknowledge announcement
function acknowledgeAnnouncement(announcementId) {
    if (!announcementId) return;

    const formData = new FormData();
    formData.append('announcement_id', announcementId);

    fetch('index.php?controller=AnnouncementController&action=acknowledgeAnnouncement', {
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
            // Reload announcements to update acknowledgment status
            loadAnnouncements(window.myAnnouncementsState.currentPage);
            // Refresh modal content to show acknowledgment confirmation
            const modal = document.getElementById('announcementDetailModal');
            if (modal && modal.style.display !== 'none') {
                // Get the current announcement ID from the modal
                const currentAnnouncementId = document.querySelector('.acknowledge-announcement-btn')?.dataset.announcementId;
                if (currentAnnouncementId) {
                    // Reload the modal content with updated acknowledgment status
                    loadAnnouncementDetails(currentAnnouncementId);
                }
            }
        } else {
            showError(data.error || 'Failed to acknowledge announcement');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Network error. Please try again.');
    });
}

// Utility functions
function getUrgencyClass(urgency) {
    switch (urgency) {
        case 'urgent': return 'urgency-urgent';
        case 'warning': return 'urgency-warning';
        case 'info': return 'urgency-info';
        default: return 'urgency-info';
    }
}

function getUrgencyIcon(urgency) {
    switch (urgency) {
        case 'urgent': return 'fas fa-exclamation-triangle';
        case 'warning': return 'fas fa-exclamation-circle';
        case 'info': return 'fas fa-info-circle';
        default: return 'fas fa-info-circle';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || div.innerText || '';
}

function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function renderPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;

    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '';
    const currentPage = pagination.current_page;
    const totalPages = pagination.total_pages;

    // Previous button
    if (currentPage > 1) {
        html += `<li class="page-item">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                 </li>`;
    }

    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    if (startPage > 1) {
        html += `<li class="page-item">
                    <a class="page-link" href="#" data-page="1">1</a>
                 </li>`;
        if (startPage > 2) {
            html += `<li class="page-item disabled">
                        <span class="page-link">...</span>
                     </li>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                 </li>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<li class="page-item disabled">
                        <span class="page-link">...</span>
                     </li>`;
        }
        html += `<li class="page-item">
                    <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
                 </li>`;
    }

    // Next button
    if (currentPage < totalPages) {
        html += `<li class="page-item">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                 </li>`;
    }

    container.innerHTML = html;
}

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

function showLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    const container = document.getElementById('announcementsContainer');
    const noResults = document.getElementById('noResults');
    
    if (spinner) spinner.style.display = 'block';
    if (container) container.style.display = 'none';
    if (noResults) noResults.style.display = 'none';
}

function hideLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    const container = document.getElementById('announcementsContainer');
    
    if (spinner) spinner.style.display = 'none';
    if (container) container.style.display = 'block';
}

function renderEmptyState() {
    const container = document.getElementById('announcementsContainer');
    const noResults = document.getElementById('noResults');
    
    if (container) container.innerHTML = '';
    if (noResults) noResults.style.display = 'block';
}

function showError(message) {
    if (typeof showSimpleToast === 'function') {
        showSimpleToast(message, 'error');
    } else {
        alert(message);
    }
}

function showSuccess(message) {
    if (typeof showSimpleToast === 'function') {
        showSimpleToast(message, 'success');
    } else {
        alert(message);
    }
}
