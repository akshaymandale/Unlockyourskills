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

<div class="main-content" data-my-announcements-page="true">
    <div class="container mt-4">
        <!-- Modern Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="modern-header-card">
                    <div class="d-flex align-items-center">
                        <div class="header-icon">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div class="header-content">
                            <h1 class="page-title mb-1">My Announcements</h1>
                            <p class="text-muted mb-0">Stay updated with the latest announcements and important information</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
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
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search announcements...">
                                </div>
                            </div>

                            <!-- Urgency Filter -->
                            <div class="col-md-2">
                                <select class="form-select modern-select" id="urgencyFilter">
                                    <option value="">All Urgency</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="warning">Warning</option>
                                    <option value="info">Info</option>
                                </select>
                            </div>

                            <!-- Acknowledgment Filter -->
                            <div class="col-md-2">
                                <select class="form-select modern-select" id="acknowledgmentFilter">
                                    <option value="">All Status</option>
                                    <option value="no">Unread</option>
                                    <option value="yes">Read</option>
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

        <!-- Results Info -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="modern-results-info">
                    <i class="fas fa-info-circle"></i>
                    <span id="resultsInfo">Loading announcements...</span>
                </div>
            </div>
        </div>

        <!-- Announcements List -->
        <div class="row">
            <div class="col-12">
                <div id="announcementsContainer">
                    <!-- Announcements will be loaded here via AJAX -->
                </div>
            </div>
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
            <div class="col-12">
                <div class="modern-empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h4 class="empty-state-title">No Announcements Found</h4>
                    <p class="empty-state-text">Try adjusting your search criteria or check back later for new announcements.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Announcement Detail Modal -->
<div class="modal fade" id="announcementDetailModal" tabindex="-1" aria-labelledby="announcementDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal">
            <div class="modal-header modern-modal-header">
                <div class="header-content">
                    <h5 class="modal-title" id="announcementDetailModalLabel">
                        <i class="fas fa-bullhorn me-2"></i>Announcement Details
                    </h5>
                </div>
                <button type="button" class="btn-close modern-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modern-modal-body" id="announcementDetailContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Include JavaScript -->
<script src="public/js/my_announcements.js"></script>

<?php include 'includes/footer.php'; ?>
