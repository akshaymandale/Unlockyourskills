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

<div class="main-content" data-my-social-feed-page="true">
    <div class="container mt-4">
        <!-- Modern Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="modern-header-card">
                    <div class="d-flex align-items-center">
                        <div class="header-icon">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <div class="header-content">
                            <h1 class="page-title mb-1">My Social Feed</h1>
                            <p class="text-muted mb-0">Stay connected with the latest posts and updates from your community</p>
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
                                    <input type="text" class="form-control modern-input" id="searchInput" 
                                           placeholder="Search posts...">
                                </div>
                            </div>

                            <!-- Post Type Filter -->
                            <div class="col-md-2">
                                <select class="form-select modern-select" id="postTypeFilter">
                                    <option value="">All Types</option>
                                    <option value="text">📝 General</option>
                                    <option value="media">🖼️ Media</option>
                                    <option value="poll">📊 Poll</option>
                                    <option value="link">🔗 Link</option>
                                </select>
                            </div>

                            <!-- Visibility Filter -->
                            <div class="col-md-2">
                                <select class="form-select modern-select" id="visibilityFilter">
                                    <option value="">All Visibility</option>
                                    <option value="global">🌍 Global</option>
                                    <option value="group_specific">👥 Group Specific</option>
                                </select>
                            </div>

                            <!-- Pinned Filter -->
                            <div class="col-md-2">
                                <select class="form-select modern-select" id="pinnedFilter">
                                    <option value="">All Posts</option>
                                    <option value="1">📌 Pinned Only</option>
                                    <option value="0">📄 Regular Only</option>
                                </select>
                            </div>

                            <!-- Date Range Filter -->
                            <div class="col-md-2">
                                <button type="button" class="btn btn-modern-outline w-100" id="dateRangeBtn">
                                    <i class="fas fa-calendar me-1"></i>Date Range
                                </button>
                            </div>
                        </div>

                        <!-- Date Range Inputs (Hidden by default) -->
                        <div class="row mt-3" id="dateRangeInputs" style="display: none;">
                            <div class="col-md-4">
                                <label for="dateFrom" class="form-label">From Date</label>
                                <input type="date" class="form-control modern-input" id="dateFrom">
                            </div>
                            <div class="col-md-4">
                                <label for="dateTo" class="form-label">To Date</label>
                                <input type="date" class="form-control modern-input" id="dateTo">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-modern-primary me-2" id="applyDateFilter">
                                    <i class="fas fa-check me-1"></i>Apply
                                </button>
                                <button type="button" class="btn btn-modern-outline" id="clearDateFilter">
                                    <i class="fas fa-times me-1"></i>Clear
                                </button>
                            </div>
                        </div>

                        <!-- Filter Actions -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="modern-results-info" id="resultsInfo">
                                        <span class="text-muted">Loading posts...</span>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-modern-outline me-2" id="clearAllFilters">
                                            <i class="fas fa-eraser me-1"></i>Clear All
                                        </button>
                                        <button type="button" class="btn btn-modern-primary" id="refreshPosts">
                                            <i class="fas fa-sync-alt me-1"></i>Refresh
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts Container -->
        <div class="row">
            <div class="col-12">
                <div id="postsContainer">
                    <!-- Posts will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div class="row" id="loadingSpinner" style="display: none;">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading posts...</p>
            </div>
        </div>

        <!-- No Results -->
        <div class="row" id="noResults" style="display: none;">
            <div class="col-12 text-center py-5">
                <div class="modern-empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-newspaper fa-3x text-muted"></i>
                    </div>
                    <h5 class="empty-state-title">No posts found</h5>
                    <p class="empty-state-text">Try adjusting your search criteria or check back later for new posts.</p>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <nav aria-label="Posts pagination" class="mt-4">
            <ul class="pagination justify-content-center" id="pagination"></ul>
        </nav>
    </div>
</div>

<!-- Post Details Modal -->
<div class="modal fade" id="postDetailsModal" tabindex="-1" aria-labelledby="postDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal">
            <div class="modal-header modern-modal-header">
                <div class="header-content">
                    <h5 class="modal-title" id="postDetailsModalLabel">
                        <i class="fas fa-newspaper me-2"></i>Post Details
                    </h5>
                </div>
                <button type="button" class="btn-close modern-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modern-modal-body" id="postDetailsContent">
                <!-- Post details will be loaded here -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading post details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Comments Modal -->
<div class="modal fade" id="commentsModal" tabindex="-1" aria-labelledby="commentsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modern-modal">
            <div class="modal-header modern-modal-header">
                <div class="header-content">
                    <h5 class="modal-title" id="commentsModalLabel">
                        <i class="fas fa-comments me-2"></i>Comments
                    </h5>
                </div>
                <button type="button" class="btn-close modern-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modern-modal-body">
                <div id="commentsContainer">
                    <!-- Comments will be loaded here -->
                </div>
                <div class="mt-3">
                    <div class="input-group">
                        <input type="text" class="form-control" id="commentInput" placeholder="Write a comment...">
                        <button class="btn btn-primary" type="button" id="addCommentBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="js/user-social-feed.js"></script>
