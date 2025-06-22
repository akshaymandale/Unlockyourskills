<?php
// Check if user is logged in
if (!isset($_SESSION['user']['client_id'])) {
    header('Location: index.php?controller=LoginController');
    exit;
}

require_once 'core/UrlHelper.php';
require_once 'config/Localization.php';

$systemRole = $_SESSION['user']['system_role'] ?? '';
$canCreateGlobal = in_array($systemRole, ['super_admin', 'admin']);
$canManageAll = in_array($systemRole, ['super_admin', 'admin']);

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/sidebar.php';
?>

<div class="main-content" data-social-feed-page="true">
    <div class="container add-question-container">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="<?= UrlHelper::url('manage-portal') ?>#social" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-newspaper me-2"></i>
                Social Feed Management
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
                <li class="breadcrumb-item active" aria-current="page">Social Feed</li>
            </ol>
        </nav>

        <!-- Page Description -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">Manage posts, announcements, and community engagement</p>
                    </div>
                    <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#createPostModal">
                        <i class="fas fa-plus me-2"></i>Create Post
                    </button>
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
                                           placeholder="Search posts...">
                                </div>
                            </div>

                            <!-- Post Type Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="postTypeFilter">
                                    <option value="">All Types</option>
                                    <option value="text">General</option>
                                    <option value="media">Media</option>
                                    <option value="poll">Poll</option>
                                    <option value="link">Link</option>
                                </select>
                            </div>

                            <!-- Visibility Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="visibilityFilter">
                                    <option value="">All Visibility</option>
                                    <option value="global">Global</option>
                                    <option value="course_specific">Course Specific</option>
                                    <option value="group_specific">Group Specific</option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="pinned">Pinned</option>
                                    <option value="reported">Reported</option>
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
                    <span id="resultsInfo">Loading posts...</span>
                </div>
            </div>
        </div>

        <!-- Social Feed Posts -->
        <div id="socialFeedGrid">
            <!-- Posts will be loaded here via AJAX -->
        </div>

        <!-- Pagination -->
        <div class="row">
            <div class="col-12">
                <nav>
                    <ul class="pagination justify-content-center" id="pagination"></ul>
                </nav>
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
                <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No posts found</h5>
                <p class="text-muted">Try adjusting your search criteria or create a new post.</p>
                <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#createPostModal">
                    <i class="fas fa-plus me-2"></i>Create First Post
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create Post Modal -->
<div class="modal fade" id="createPostModal" tabindex="-1" aria-labelledby="createPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createPostModalLabel">Create New Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createPostForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Post Title -->
                            <div class="mb-3">
                                <label for="postTitle" class="form-label">Post Title *</label>
                                <input type="text" class="form-control" id="postTitle" name="title" 
                                    placeholder="Enter post title..." maxlength="150">
                                <div class="d-flex justify-content-between">
                                    <div class="form-text">Maximum 150 characters</div>
                                    <div class="form-text" id="titleCharCounter">0/150</div>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Post Content -->
                            <div class="mb-3">
                                <label for="postContent" class="form-label">Post Content *</label>
                                <textarea class="form-control" id="postContent" name="content" rows="6" 
                                    placeholder="What's on your mind? Use @ to mention users, # for hashtags..."></textarea>
                                <div class="d-flex justify-content-between">
                                    <div class="form-text">Maximum 2000 characters</div>
                                    <div class="form-text" id="charCounter">0/2000</div>
                                </div>
                            </div>

                            <!-- Media Upload -->
                            <div class="mb-3">
                                <label class="form-label">Media Files</label>
                                <div class="drop-zone" id="mediaDropZone">
                                    <div class="drop-zone-text">
                                        <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                        <p>Drag & drop files here or click to browse</p>
                                        <small class="text-muted">Supports: Images, Videos, Documents (Max 10MB each)</small>
                                    </div>
                                    <input type="file" id="mediaFiles" name="media[]" multiple accept="image/*,video/*,.pdf,.doc,.docx" style="display: none;">
                                </div>
                                <div id="mediaPreview" class="mt-2"></div>
                            </div>

                            <!-- Poll Creation -->
                            <div class="mb-3" id="pollSection" style="display: none;">
                                <label class="form-label">Create Poll</label>
                                <div id="pollOptions">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="poll_options[]" placeholder="Option 1">
                                    </div>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="poll_options[]" placeholder="Option 2">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="addPollOption">
                                    <i class="fas fa-plus me-1"></i>Add Option
                                </button>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="allowMultipleVotes" name="allow_multiple_votes">
                                    <label class="form-check-label" for="allowMultipleVotes">
                                        Allow multiple votes
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Post Settings -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Post Settings</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Post Category -->
                                    <div class="mb-3">
                                        <label for="post_type" class="form-label">Category *</label>
                                        <select class="form-select" id="post_type" name="post_type">
                                            <option value="">Select a category</option>
                                            <option value="text">General</option>
                                            <option value="media">Media</option>
                                            <option value="poll">Poll</option>
                                            <option value="link">Link</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <!-- Post Visibility -->
                                    <div class="mb-3">
                                        <label for="postVisibility" class="form-label">Visibility *</label>
                                        <select class="form-select" id="visibility" name="visibility">
                                            <option value="">Select visibility</option>
                                            <option value="global">Global</option>
                                            <option value="course_specific">Course Specific</option>
                                            <option value="group_specific">Group Specific</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <!-- Tags -->
                                    <div class="mb-3">
                                        <label for="postTags" class="form-label">Tags</label>
                                        <div class="tag-input-container form-control">
                                            <span id="tagDisplay"></span>
                                            <input type="text" id="tagInput" 
                                                placeholder="Type and press Enter to add tags...">
                                        </div>
                                        <input type="hidden" name="tags" id="tagList">
                                        <div class="form-text">Use hashtags for better discoverability</div>
                                    </div>

                                    <!-- Poll Toggle -->
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="includePoll" name="include_poll">
                                            <label class="form-check-label" for="includePoll">
                                                Include Poll
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Pin Post -->
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="pinPost" name="pin_post">
                                            <label class="form-check-label" for="pinPost">
                                                Pin to Top
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Schedule Post -->
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="schedulePost" name="schedule_post">
                                            <label class="form-check-label" for="schedulePost">
                                                Schedule Post
                                            </label>
                                        </div>
                                        <input type="datetime-local" class="form-control mt-2" id="scheduleDateTime" 
                                            name="scheduled_at" style="display: none;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Publish Post
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Post Modal -->
<div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPostModalLabel">Edit Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPostForm">
                <div class="modal-body">
                    <input type="hidden" id="editPostId" name="post_id">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Post Title -->
                            <div class="mb-3">
                                <label for="editPostTitle" class="form-label">Post Title *</label>
                                <input type="text" class="form-control" id="editPostTitle" name="title" 
                                    placeholder="Enter post title..." maxlength="150">
                                <div class="d-flex justify-content-between">
                                    <div class="form-text">Maximum 150 characters</div>
                                    <div class="form-text" id="editTitleCharCounter">0/150</div>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Post Content -->
                            <div class="mb-3">
                                <label for="editPostContent" class="form-label">Post Content *</label>
                                <textarea class="form-control" id="editPostContent" name="content" rows="6" 
                                    placeholder="What's on your mind? Use @ to mention users, # for hashtags..."></textarea>
                                <div class="d-flex justify-content-between">
                                    <div class="form-text">Maximum 2000 characters</div>
                                    <div class="form-text" id="editCharCounter">0/2000</div>
                                </div>
                            </div>

                            <!-- Media Upload -->
                            <div class="mb-3">
                                <label class="form-label">Media Files</label>
                                <div class="drop-zone" id="editMediaDropZone">
                                    <div class="drop-zone-text">
                                        <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                        <p>Drag & drop files here or click to browse</p>
                                        <small class="text-muted">Supports: Images, Videos, Documents (Max 10MB each)</small>
                                    </div>
                                    <input type="file" id="editMediaFiles" name="media[]" multiple accept="image/*,video/*,.pdf,.doc,.docx" style="display: none;">
                                </div>
                                <div id="editMediaPreview" class="mt-2"></div>
                            </div>

                            <!-- Poll Creation -->
                            <div class="mb-3" id="editPollSection" style="display: none;">
                                <label class="form-label">Edit Poll</label>
                                <div id="editPollOptions">
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="poll_options[]" placeholder="Option 1">
                                    </div>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" name="poll_options[]" placeholder="Option 2">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="editAddPollOption">
                                    <i class="fas fa-plus me-1"></i>Add Option
                                </button>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="editAllowMultipleVotes" name="allow_multiple_votes">
                                    <label class="form-check-label" for="editAllowMultipleVotes">
                                        Allow multiple votes
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Post Settings -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Post Settings</h6>
                                </div>
                                <div class="card-body">
                                    <!-- Post Category -->
                                    <div class="mb-3">
                                        <label for="editPostType" class="form-label">Category *</label>
                                        <select class="form-select" id="editPostType" name="post_type">
                                            <option value="">Select a category</option>
                                            <option value="text">General</option>
                                            <option value="media">Media</option>
                                            <option value="poll">Poll</option>
                                            <option value="link">Link</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <!-- Post Visibility -->
                                    <div class="mb-3">
                                        <label for="editVisibility" class="form-label">Visibility *</label>
                                        <select class="form-select" id="editVisibility" name="visibility">
                                            <option value="">Select visibility</option>
                                            <option value="global">Global</option>
                                            <option value="course_specific">Course Specific</option>
                                            <option value="group_specific">Group Specific</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <!-- Tags -->
                                    <div class="mb-3">
                                        <label for="editPostTags" class="form-label">Tags</label>
                                        <div class="tag-input-container form-control">
                                            <span id="editTagDisplay"></span>
                                            <input type="text" id="editTagInput" 
                                                placeholder="Type and press Enter to add tags...">
                                        </div>
                                        <input type="hidden" name="tags" id="editTagList">
                                        <div class="form-text">Use hashtags for better discoverability</div>
                                    </div>

                                    <!-- Poll Toggle -->
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="editIncludePoll" name="include_poll">
                                            <label class="form-check-label" for="editIncludePoll">
                                                Include Poll
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Pin Post -->
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="editPinPost" name="pin_post">
                                            <label class="form-check-label" for="editPinPost">
                                                Pin to Top
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Post
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Post Detail Modal -->
<div class="modal fade" id="postDetailModal" tabindex="-1" aria-labelledby="postDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="postDetailModalLabel">Post Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="postDetailContent">
                <!-- Post details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalLabel">Report Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reportForm">
                <div class="modal-body">
                    <input type="hidden" id="reportPostId" name="post_id">
                    
                    <!-- Report Reason -->
                    <div class="mb-3">
                        <label for="reportReason" class="form-label">Reason for Report *</label>
                        <select class="form-select" id="reportReason" name="reason">
                            <option value="">Select a reason</option>
                            <option value="spam">Spam</option>
                            <option value="inappropriate">Inappropriate Content</option>
                            <option value="harassment">Harassment</option>
                            <option value="fake_news">Fake News/Misinformation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Report Details -->
                    <div class="mb-3">
                        <label for="reportDetails" class="form-label">Additional Details</label>
                        <textarea class="form-control" id="reportDetails" name="details" rows="3" 
                            placeholder="Please provide details about why you're reporting this post..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-flag me-1"></i>Submit Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer">
    <!-- Toast notifications will be added here -->
</div>

<!-- Custom CSS for Social Feed -->
<style>
.drop-zone {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.drop-zone:hover {
    border-color: #007bff;
    background-color: #f8f9fa;
}

.drop-zone.dragover {
    border-color: #007bff;
    background-color: #e3f2fd;
}

.drop-zone-text {
    color: #6c757d;
}

.media-preview-item {
    position: relative;
    display: inline-block;
    margin: 5px;
}

.media-preview-item img,
.media-preview-item video {
    max-width: 100px;
    max-height: 100px;
    border-radius: 4px;
}

.media-preview-remove {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 10px;
    cursor: pointer;
}

.post-card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.post-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.post-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.post-content {
    padding: 1rem;
}

.post-actions {
    padding: 0.75rem 1rem;
    border-top: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

.post-media {
    margin: 1rem 0;
}

.post-media img,
.post-media video {
    max-width: 100%;
    border-radius: 8px;
}

.poll-option {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 0.5rem;
    margin: 0.25rem 0;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.poll-option:hover {
    background-color: #f8f9fa;
}

.poll-option.selected {
    background-color: #e3f2fd;
    border-color: #007bff;
}

.poll-progress {
    height: 8px;
    border-radius: 4px;
    background-color: #e9ecef;
    overflow: hidden;
}

.poll-progress-bar {
    height: 100%;
    background-color: #007bff;
    transition: width 0.3s ease;
}

.comment-section {
    max-height: 300px;
    overflow-y: auto;
}

.comment-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.comment-item:last-child {
    border-bottom: none;
}

.pinned-badge {
    background-color: #ffc107;
    color: #212529;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.reported-badge {
    background-color: #dc3545;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.mention {
    background-color: #e3f2fd;
    color: #1976d2;
    padding: 0.1rem 0.3rem;
    border-radius: 3px;
    font-weight: 500;
}

.hashtag {
    background-color: #f3e5f5;
    color: #7b1fa2;
    padding: 0.1rem 0.3rem;
    border-radius: 3px;
    font-weight: 500;
}

.grid-view .post-card {
    width: calc(50% - 1rem);
    margin: 0.5rem;
}

@media (max-width: 768px) {
    .grid-view .post-card {
        width: 100%;
    }
}

/* Post Detail Modal Styles */
.post-detail {
    max-width: 100%;
}

.post-detail .post-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.post-detail .avatar img {
    border: 3px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.post-detail .post-content {
    background: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.post-detail .post-body {
    line-height: 1.6;
    color: #333;
}

.post-detail .post-stats {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.post-detail .stat-item {
    padding: 1rem;
    border-radius: 6px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.post-detail .stat-item:hover {
    transform: translateY(-2px);
}

.post-detail .stat-item i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.post-detail .comments-section {
    background: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.post-detail .comments-list {
    max-height: 400px;
    overflow-y: auto;
}

.post-detail .comment-item {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid #e9ecef;
}

.post-detail .comment-item:last-child {
    margin-bottom: 0;
}

.post-detail .comment-item img {
    border: 2px solid #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Modal size adjustments */
#postDetailModal .modal-dialog {
    max-width: 800px;
}

#postDetailModal .modal-body {
    max-height: 80vh;
    overflow-y: auto;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .post-detail .post-header {
        padding: 1rem;
    }
    
    .post-detail .post-content {
        padding: 1rem;
    }
    
    .post-detail .post-stats {
        padding: 1rem;
    }
    
    .post-detail .comments-section {
        padding: 1rem;
    }
    
    #postDetailModal .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
    }
}

/* Report Details Styling */
.reports-section {
    border-top: 2px solid #dc3545;
    padding-top: 1rem;
}

.report-item {
    background-color: #fff5f5 !important;
    border-left: 4px solid #dc3545 !important;
}

.report-item .badge {
    font-size: 0.75rem;
}

.report-details {
    background-color: #f8f9fa;
    padding: 0.75rem;
    border-radius: 4px;
    border-left: 3px solid #6c757d;
}

.moderator-notes {
    background-color: #e3f2fd;
    padding: 0.75rem;
    border-radius: 4px;
    border-left: 3px solid #007bff;
}

.post-detail .reports-section h6 {
    color: #dc3545;
    font-weight: 600;
}

.post-detail .report-item strong {
    color: #dc3545;
}

.post-detail .moderator-notes strong {
    color: #007bff;
}

/* Reported Post Styling */
.post-card.reported {
    border-left: 4px solid #dc3545;
    background-color: #fff5f5;
}

.post-card.reported .post-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dc3545;
}

.post-card.reported .reported-badge {
    background-color: #dc3545;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}
</style>

<?php require_once 'includes/footer.php'; ?>

<script src="public/js/social_feed_validation.js"></script>
<script src="public/js/social_feed.js"></script> 