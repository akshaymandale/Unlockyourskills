<?php
// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php?controller=LoginController');
    exit();
}

require_once 'core/UrlHelper.php';
require_once 'includes/permission_helper.php';

$currentUser = $_SESSION['user'];
?>
<?php include 'includes/header.php'; ?>

<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content" data-my-events-page="true">
    <div class="container mt-4">
        <!-- Modern Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="modern-header-card">
                    <div class="d-flex align-items-center">
                        <div class="header-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="header-content">
                            <h1 class="page-title mb-1">My Events</h1>
                            <p class="text-muted mb-0">Stay updated with your upcoming events and RSVPs</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modern Filters Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="modern-filter-card">
                    <div class="filter-header">
                        <h6 class="filter-title">
                            <i class="fas fa-filter me-2"></i>Filters
                        </h6>
                    </div>
                    <div class="filter-body">
                        <div class="row g-3">
                            <!-- Search -->
                            <div class="col-md-4">
                                <div class="input-group modern-input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="searchInput" 
                                           placeholder="Search events...">
                                </div>
                            </div>

                            <!-- Event Type Filter -->
                            <div class="col-md-3">
                                <select class="form-select modern-select" id="eventTypeFilter">
                                    <option value="">All Types</option>
                                    <option value="live_class">Live Class</option>
                                    <option value="webinar">Webinar</option>
                                    <option value="deadline">Deadline</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="meeting">Meeting</option>
                                    <option value="workshop">Workshop</option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-3">
                                <select class="form-select modern-select" id="statusFilter">
                                    <option value="">All Events</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="today">Today</option>
                                    <option value="past">Past</option>
                                </select>
                            </div>

                            <!-- Date Range -->
                            <div class="col-md-2">
                                <button type="button" class="btn btn-modern-outline w-100" id="dateRangeBtn">
                                    <i class="fas fa-calendar me-2"></i>Date Range
                                </button>
                            </div>

                            <!-- Clear All Filters -->
                            <div class="col-md-2">
                                <button type="button" class="btn btn-modern-danger w-100" id="clearAllFiltersBtn" title="Clear all filters">
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
                                <button type="button" class="btn btn-modern-primary d-block" id="applyDateFilter">
                                    Apply
                                </button>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-modern-outline d-block" id="clearDateFilter">
                                    Clear Date
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modern Results Info -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="modern-results-info">
                    <i class="fas fa-info-circle"></i>
                    <span id="resultsInfo">Loading events...</span>
                </div>
            </div>
        </div>

        <!-- Events List -->
        <div class="row">
            <div class="col-12">
                <div id="eventsContainer">
                    <!-- Events will be loaded here via AJAX -->
                </div>
            </div>
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

        <!-- Modern No Results -->
        <div class="row" id="noResults" style="display: none;">
            <div class="col-12">
                <div class="modern-empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h4 class="empty-state-title">No Events Found</h4>
                    <p class="empty-state-text">Try adjusting your search criteria or check back later for new events.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RSVP Modal -->
<div class="modal fade" id="rsvpModal" tabindex="-1" aria-labelledby="rsvpModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rsvpModalLabel">
                    <i class="fas fa-calendar-check me-2"></i>RSVP for Event
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <h6 id="rsvpEventTitle"></h6>
                    <p class="text-muted" id="rsvpEventDate"></p>
                </div>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success rsvp-btn" data-response="yes">
                        <i class="fas fa-check me-2"></i>Yes, I'll attend
                    </button>
                    <button type="button" class="btn btn-warning rsvp-btn" data-response="maybe">
                        <i class="fas fa-question me-2"></i>Maybe
                    </button>
                    <button type="button" class="btn btn-danger rsvp-btn" data-response="no">
                        <i class="fas fa-times me-2"></i>No, I can't attend
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal">
            <div class="modal-header modern-modal-header">
                <div class="header-content">
                    <h5 class="modal-title" id="eventDetailsModalLabel">
                        <i class="fas fa-calendar-alt me-2"></i>Event Details
                    </h5>
                </div>
                <button type="button" class="btn-close modern-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modern-modal-body" id="eventDetailsContent">
                <!-- Event details will be loaded here -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading event details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer">
    <!-- Toast notifications will be added here -->
</div>

<!-- Include user events JavaScript -->
<script src="js/user-events.js"></script>

<?php require_once 'includes/footer.php'; ?>