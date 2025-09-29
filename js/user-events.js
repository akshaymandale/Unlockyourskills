/**
 * User Events Management JavaScript
 * Handles user-side event viewing, RSVP functionality, and filtering
 */

class UserEventManager {
    constructor() {
        this.state = {
            currentPage: 1,
            currentSearch: '',
            currentFilters: {
                event_type: '',
                status: '',
                date_from: '',
                date_to: ''
            },
            isLoading: false,
            currentEventId: null
        };

        this.init();
    }

    init() {
        this.bindEvents();
        this.loadEvents(1);
    }

    bindEvents() {
        // Search with debounce
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            const debouncedSearch = this.debounce((searchValue) => {
                this.state.currentSearch = searchValue;
                this.loadEvents(1);
            }, 500);

            searchInput.addEventListener('input', (e) => {
                debouncedSearch(e.target.value.trim());
            });
        }

        // Filter dropdowns
        const eventTypeFilter = document.getElementById('eventTypeFilter');
        if (eventTypeFilter) {
            eventTypeFilter.addEventListener('change', (e) => {
                this.state.currentFilters.event_type = e.target.value;
                this.loadEvents(1);
            });
        }

        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.state.currentFilters.status = e.target.value;
                this.loadEvents(1);
            });
        }

        // Clear all filters
        const clearAllFiltersBtn = document.getElementById('clearAllFiltersBtn');
        if (clearAllFiltersBtn) {
            clearAllFiltersBtn.addEventListener('click', () => {
                this.clearAllFilters();
            });
        }

        // Date range functionality
        const dateRangeBtn = document.getElementById('dateRangeBtn');
        const dateRangeInputs = document.getElementById('dateRangeInputs');
        const applyDateFilter = document.getElementById('applyDateFilter');
        const clearDateFilter = document.getElementById('clearDateFilter');

        if (dateRangeBtn && dateRangeInputs) {
            dateRangeBtn.addEventListener('click', () => {
                dateRangeInputs.classList.toggle('d-none');
            });
        }

        if (applyDateFilter) {
            applyDateFilter.addEventListener('click', () => {
                const dateFrom = document.getElementById('dateFrom').value;
                const dateTo = document.getElementById('dateTo').value;
                
                this.state.currentFilters.date_from = dateFrom;
                this.state.currentFilters.date_to = dateTo;
                
                this.loadEvents(1);
            });
        }

        if (clearDateFilter) {
            clearDateFilter.addEventListener('click', () => {
                const dateFrom = document.getElementById('dateFrom');
                const dateTo = document.getElementById('dateTo');
                
                if (dateFrom) dateFrom.value = '';
                if (dateTo) dateTo.value = '';
                
                this.state.currentFilters.date_from = '';
                this.state.currentFilters.date_to = '';
                
                this.loadEvents(1);
            });
        }

        // Pagination
        document.addEventListener('click', (e) => {
            if (e.target.matches('.page-link[data-page]')) {
                e.preventDefault();
                const page = parseInt(e.target.getAttribute('data-page'));
                this.loadEvents(page);
            }
        });

        // RSVP button functionality
        document.addEventListener('click', (e) => {
            if (e.target.closest('.rsvp-event-btn')) {
                const eventId = e.target.closest('.rsvp-event-btn').dataset.eventId;
                const eventTitle = e.target.closest('.rsvp-event-btn').dataset.eventTitle;
                const eventDate = e.target.closest('.rsvp-event-btn').dataset.eventDate;
                this.showRSVPModal(eventId, eventTitle, eventDate);
            }
        });

        // RSVP response buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.rsvp-btn')) {
                const response = e.target.closest('.rsvp-btn').dataset.response;
                this.submitRSVP(response);
            }
        });

        // View Details button functionality
        document.addEventListener('click', (e) => {
            if (e.target.closest('.view-details-btn')) {
                console.log('View Details button clicked');
                const eventId = e.target.closest('.view-details-btn').dataset.eventId;
                console.log('Event ID:', eventId);
                this.showEventDetailsModal(eventId);
            }
        });
    }

    async loadEvents(page = 1) {
        if (this.state.isLoading) return;

        this.state.currentPage = page;
        this.state.isLoading = true;

        this.showLoading(true);

        try {
            // Build query parameters
            const params = new URLSearchParams({
                page: page,
                limit: 10,
                search: this.state.currentSearch,
                ...this.state.currentFilters
            });

            const response = await fetch(`index.php?controller=UserEventController&action=getUserEvents&${params}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.displayEvents(data.events);
                this.updatePagination(data.pagination);
                this.updateResultsInfo(data.pagination);
            } else {
                this.showError(data.error || data.message || 'Failed to load events. Please try again.');
            }
        } catch (error) {
            this.showError('Network error. Please check your connection and try again.');
        } finally {
            this.state.isLoading = false;
            this.showLoading(false);
        }
    }

    displayEvents(events) {
        const container = document.getElementById('eventsContainer');
        const noResults = document.getElementById('noResults');

        if (!events || events.length === 0) {
            container.innerHTML = '';
            noResults.style.display = 'block';
            return;
        }

        noResults.style.display = 'none';
        container.innerHTML = events.map(event => this.createEventCard(event)).join('');
    }

    createEventCard(event) {
        const eventTypeClass = this.getEventTypeClass(event.event_type);
        const statusBadge = this.getEventStatusBadge(event);
        const eventDate = this.formatEventDate(event.start_datetime);
        const rsvpBadge = this.getRSVPBadge(event.user_rsvp);

        return `
            <div class="modern-announcement-card mb-4 event-type-${event.event_type}">
                <div class="announcement-card-header">
                    <div class="announcement-title-section">
                        <h5 class="announcement-title">${this.escapeHtml(event.title)}</h5>
                        <div class="announcement-meta">
                            <span class="announcement-creator">
                                <i class="fas fa-user me-1"></i>
                                ${this.escapeHtml(event.created_by_name || 'Unknown')}
                            </span>
                            <span class="announcement-divider">•</span>
                            <span class="announcement-date">
                                <i class="fas fa-calendar me-1"></i>
                                ${this.formatDate(event.start_datetime)}
                            </span>
                            <span class="announcement-divider">•</span>
                            <span class="announcement-time">
                                <i class="fas fa-clock me-1"></i>
                                ${eventDate}
                            </span>
                        </div>
                    </div>
                    <div class="announcement-badges">
                        <span class="event-type-badge event-type-${event.event_type}">
                            <i class="fas ${this.getEventTypeIcon(event.event_type)} me-1"></i>
                            ${event.event_type.replace('_', ' ').toUpperCase()}
                        </span>
                        ${statusBadge}
                        ${rsvpBadge}
                    </div>
                </div>
                
                <div class="announcement-card-body">
                    <div class="announcement-description">
                        <p class="description-text">
                            ${this.truncateText(this.stripHtml(event.description), 150)}
                        </p>
                    </div>

                    <div class="event-info-section">
                        ${event.location ? `
                        <div class="info-item-small">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <span>${this.escapeHtml(event.location)}</span>
                        </div>
                        ` : ''}

                        ${event.event_link ? `
                        <div class="info-item-small">
                            <i class="fas fa-link me-2"></i>
                            <a href="${this.escapeHtml(event.event_link)}" target="_blank" class="text-decoration-none">Join Event</a>
                        </div>
                        ` : ''}

                        ${event.enable_rsvp == 1 ? `
                        <div class="info-item-small">
                            <i class="fas fa-users me-2"></i>
                            <span>RSVPs: ${event.yes_count || 0} Yes, ${event.no_count || 0} No, ${event.maybe_count || 0} Maybe</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                <div class="announcement-card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-modern-outline view-details-btn" 
                                data-event-id="${event.id}">
                            <i class="fas fa-eye me-1"></i>View Details
                        </button>
                        ${event.enable_rsvp == 1 ? `
                        <button type="button" class="btn btn-modern-primary rsvp-event-btn" 
                                data-event-id="${event.id}" 
                                data-event-title="${this.escapeHtml(event.title)}"
                                data-event-date="${eventDate}">
                            <i class="fas fa-calendar-check me-1"></i>RSVP
                        </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    getEventTypeClass(eventType) {
        const typeClasses = {
            'live_class': 'text-primary',
            'webinar': 'text-info',
            'deadline': 'text-danger',
            'maintenance': 'text-warning',
            'meeting': 'text-success',
            'workshop': 'text-purple'
        };
        return typeClasses[eventType] || 'text-secondary';
    }

    getEventStatusBadge(event) {
        if (event.is_today) {
            return '<span class="badge bg-warning">Today</span>';
        } else if (event.is_upcoming) {
            return '<span class="badge bg-success">Upcoming</span>';
        } else {
            return '<span class="badge bg-secondary">Past</span>';
        }
    }

    getRSVPBadge(rsvp) {
        if (!rsvp) return '';
        
        const badges = {
            'yes': '<span class="badge bg-success">Attending</span>',
            'no': '<span class="badge bg-danger">Not Attending</span>',
            'maybe': '<span class="badge bg-warning">Maybe</span>'
        };
        return badges[rsvp] || '';
    }

    formatEventDate(dateString) {
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

    showRSVPModal(eventId, eventTitle, eventDate) {
        this.state.currentEventId = eventId;
        document.getElementById('rsvpEventTitle').textContent = eventTitle;
        document.getElementById('rsvpEventDate').textContent = eventDate;
        
        const modal = new bootstrap.Modal(document.getElementById('rsvpModal'));
        modal.show();
    }

    showEventDetailsModal(eventId) {
        console.log('showEventDetailsModal called with eventId:', eventId);
        
        // Show loading state
        const contentElement = document.getElementById('eventDetailsContent');
        if (!contentElement) {
            console.error('eventDetailsContent element not found');
            return;
        }
        
        contentElement.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading event details...</p>
            </div>
        `;
        
        // Show modal
        const modalElement = document.getElementById('eventDetailsModal');
        if (!modalElement) {
            console.error('eventDetailsModal element not found');
            return;
        }
        
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        // Load event details
        this.loadEventDetails(eventId);
    }

    async loadEventDetails(eventId) {
        try {
            const response = await fetch(`index.php?controller=UserEventController&action=getEventDetails&event_id=${eventId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.displayEventDetails(data.event);
            } else {
                this.showError(data.message || 'Failed to load event details.');
                document.getElementById('eventDetailsContent').innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 2rem;"></i>
                        <p class="text-muted">Failed to load event details.</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading event details:', error);
            this.showError('Network error. Please check your connection and try again.');
            document.getElementById('eventDetailsContent').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 2rem;"></i>
                    <p class="text-muted">Network error occurred.</p>
                </div>
            `;
        }
    }

    displayEventDetails(event) {
        const eventTypeClass = this.getEventTypeClass(event.event_type);
        const statusBadge = this.getEventStatusBadge(event);
        const rsvpBadge = this.getRSVPBadge(event.user_rsvp);
        
        // Format dates
        const startDate = new Date(event.start_datetime);
        const endDate = event.end_datetime ? new Date(event.end_datetime) : null;
        
        const startDateFormatted = startDate.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        const startTimeFormatted = startDate.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        const endTimeFormatted = endDate ? endDate.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        }) : null;
        
        // Format reminder text
        const reminderText = event.send_reminder_before > 0 ? 
            this.formatReminderTime(event.send_reminder_before) : 
            'No reminder set';
        
        const content = `
            <div class="modern-event-detail">
                <div class="detail-header event-type-${event.event_type}">
                    <div class="header-badges">
                        <span class="event-type-badge event-type-${event.event_type}">
                            <i class="fas ${this.getEventTypeIcon(event.event_type)} me-1"></i>
                            ${event.event_type.replace('_', ' ').toUpperCase()}
                        </span>
                        ${statusBadge}
                        ${rsvpBadge}
                    </div>
                    <h4 class="detail-title">${this.escapeHtml(event.title)}</h4>
                    <div class="detail-meta">
                        <div class="meta-item">
                            <i class="fas fa-user me-1"></i>
                            <span>Created by ${this.escapeHtml(event.created_by_name || 'Unknown')}</span>
                        </div>
                        <div class="meta-divider">•</div>
                        <div class="meta-item">
                            <i class="fas fa-calendar me-1"></i>
                            <span>${startDateFormatted}</span>
                        </div>
                        <div class="meta-divider">•</div>
                        <div class="meta-item">
                            <i class="fas fa-clock me-1"></i>
                            <span>${startTimeFormatted}${endTimeFormatted ? ' - ' + endTimeFormatted : ''}</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-body">
                    <div class="detail-content">
                        <div class="event-info-grid">
                            ${event.location ? `
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Location</div>
                                    <div class="info-value">${this.escapeHtml(event.location)}</div>
                                </div>
                            </div>
                            ` : ''}
                            
                            ${event.event_link ? `
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-link"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Event Link</div>
                                    <div class="info-value">
                                        <a href="${this.escapeHtml(event.event_link)}" target="_blank" class="event-link">
                                            ${this.escapeHtml(event.event_link)}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                            
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Reminder</div>
                                    <div class="info-value">${reminderText}</div>
                                </div>
                            </div>
                            
                            ${event.audience_type ? `
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Target Audience</div>
                                    <div class="info-value">${event.audience_type === 'global' ? 'All Users' : 'Group Specific'}</div>
                                </div>
                            </div>
                            ` : ''}
                            
                            ${event.enable_rsvp == 1 ? `
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">RSVP Status</div>
                                    <div class="info-value">
                                        ${event.yes_count || 0} Yes, ${event.no_count || 0} No, ${event.maybe_count || 0} Maybe
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                        
                        ${event.description ? `
                        <div class="event-description-section">
                            <h6 class="description-title">
                                <i class="fas fa-align-left me-2"></i>Description
                            </h6>
                            <div class="description-content">
                                ${event.description}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                ${event.enable_rsvp == 1 ? `
                <div class="detail-actions">
                    <button type="button" class="btn btn-modern-primary rsvp-from-details-btn" 
                            data-event-id="${event.id}">
                        <i class="fas fa-calendar-check me-1"></i>RSVP for Event
                    </button>
                </div>
                ` : ''}
            </div>
        `;
        
        document.getElementById('eventDetailsContent').innerHTML = content;
        
        // Add event listener for RSVP button within the details modal
        const rsvpFromDetailsBtn = document.querySelector('.rsvp-from-details-btn');
        if (rsvpFromDetailsBtn) {
            rsvpFromDetailsBtn.addEventListener('click', () => {
                // Close details modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('eventDetailsModal'));
                modal.hide();
                
                // Show RSVP modal
                const eventDate = this.formatEventDate(event.start_datetime);
                this.showRSVPModal(event.id, event.title, eventDate);
            });
        }
    }

    getEventTypeIcon(eventType) {
        const icons = {
            'live_class': 'fa-chalkboard-teacher',
            'webinar': 'fa-video',
            'deadline': 'fa-clock',
            'maintenance': 'fa-tools',
            'meeting': 'fa-users',
            'workshop': 'fa-hammer'
        };
        return icons[eventType] || 'fa-calendar';
    }

    formatReminderTime(minutes) {
        if (minutes < 60) {
            return `${minutes} minutes before`;
        } else if (minutes < 1440) {
            const hours = Math.floor(minutes / 60);
            return `${hours} hour${hours > 1 ? 's' : ''} before`;
        } else {
            const days = Math.floor(minutes / 1440);
            return `${days} day${days > 1 ? 's' : ''} before`;
        }
    }

    async submitRSVP(response) {
        if (!this.state.currentEventId) return;

        const formData = new FormData();
        formData.append('event_id', this.state.currentEventId);
        formData.append('response', response);

        try {
            const response = await fetch('index.php?controller=UserEventController&action=rsvp', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('rsvpModal'));
                modal.hide();

                // Reload events
                this.loadEvents(this.state.currentPage);

                // Show success message
                this.showSuccess(data.message);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            this.showError('An error occurred while submitting RSVP.');
        }
    }

    clearAllFilters() {
        // Clear search
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.value = '';
            this.state.currentSearch = '';
        }

        // Clear filter dropdowns
        const eventTypeFilter = document.getElementById('eventTypeFilter');
        if (eventTypeFilter) eventTypeFilter.value = '';

        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) statusFilter.value = '';

        // Clear date range inputs
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        if (dateFrom) dateFrom.value = '';
        if (dateTo) dateTo.value = '';

        // Hide date range inputs
        const dateRangeInputs = document.getElementById('dateRangeInputs');
        if (dateRangeInputs) dateRangeInputs.classList.add('d-none');

        // Reset filter object
        this.state.currentFilters = {
            event_type: '',
            status: '',
            date_from: '',
            date_to: ''
        };

        // Reload events
        this.loadEvents(1);
    }

    updatePagination(pagination) {
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

    updateResultsInfo(pagination) {
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

    showLoading(show) {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) {
            spinner.style.display = show ? 'block' : 'none';
        }
    }

    showError(message) {
        if (typeof showSimpleToast === 'function') {
            showSimpleToast(message, 'error');
        } else {
            alert('Error: ' + message); // Fallback
        }
    }

    showSuccess(message) {
        if (typeof showSimpleToast === 'function') {
            showSimpleToast(message, 'success');
        } else {
            alert(message); // Fallback
        }
    }

    // Utility functions
    debounce(func, wait) {
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

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? text.replace(/[&<>"']/g, m => map[m]) : '';
    }

    stripHtml(html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    }

    truncateText(text, length) {
        return text.length > length ? text.substring(0, length) + '...' : text;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - User Events JS');
    // Only initialize if we're on the user events page
    if (document.getElementById('eventsContainer')) {
        console.log('Initializing UserEventManager');
        window.userEventManager = new UserEventManager();
        console.log('UserEventManager initialized:', window.userEventManager);
    } else {
        console.log('eventsContainer not found, not initializing UserEventManager');
    }
});
