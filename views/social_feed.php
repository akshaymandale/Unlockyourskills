<?php
// Check if user is logged in
if (!isset($_SESSION['user']['client_id'])) {
    header('Location: index.php?controller=LoginController');
    exit;
}

require_once 'core/UrlHelper.php';
require_once 'config/Localization.php';
require_once 'models/UserRoleModel.php';
$userRoleModel = new UserRoleModel();
$currentUser = $_SESSION['user'] ?? null;
$canAccessSocialFeed = false;
$canCreatePost = false;
$canEditPost = false;
$canDeletePost = false;
if ($currentUser) {
    $canAccessSocialFeed = $userRoleModel->hasPermission($currentUser['id'], 'social_feed', 'access', $currentUser['client_id']);
    $canCreatePost = $userRoleModel->hasPermission($currentUser['id'], 'social_feed', 'create', $currentUser['client_id']);
    $canEditPost = $userRoleModel->hasPermission($currentUser['id'], 'social_feed', 'edit', $currentUser['client_id']);
    $canDeletePost = $userRoleModel->hasPermission($currentUser['id'], 'social_feed', 'delete', $currentUser['client_id']);
}

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
                    <?php if ($canCreatePost): ?>
                    <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#createPostModal" data-action-permission="social_feed:create">
                        <i class="fas fa-plus me-2"></i>Create Post
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
                                    <option value="group_specific">Group Specific</option>
                                </select>
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
                                    <option value="reported">Reported</option>
                                </select>
                            </div>

                            <!-- Pinned Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="pinnedFilter">
                                    <option value="">All Posts</option>
                                    <option value="1">Pinned Only</option>
                                    <option value="0">Unpinned Only</option>
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
                <?php if ($canCreatePost): ?>
                <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#createPostModal">
                    <i class="fas fa-plus me-2"></i>Create First Post
                </button>
                <?php endif; ?>
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
            <form id="createPostForm" enctype="multipart/form-data">
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
                            <div class="mb-3" id="createMediaSection" style="display: none;">
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
                                
                                <!-- Poll Question -->
                                <div class="mb-3">
                                    <label for="pollQuestion" class="form-label">Poll Question *</label>
                                    <input type="text" class="form-control" id="pollQuestion" name="poll_question" 
                                        placeholder="What would you like to ask?" maxlength="200">
                                    <div class="form-text">Maximum 200 characters</div>
                                </div>
                                
                                <!-- Poll Options -->
                                <div class="mb-3">
                                    <label class="form-label">Poll Options *</label>
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
                                </div>
                                
                                <!-- Poll Settings -->
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="allowMultipleVotes" name="allow_multiple_votes">
                                    <label class="form-check-label" for="allowMultipleVotes">
                                        Allow multiple votes
                                    </label>
                                </div>
                            </div>

                            <!-- Link Creation -->
                            <div class="mb-3" id="linkSection" style="display: none;">
                                <label class="form-label">Link Details</label>
                                <div class="mb-3">
                                    <label for="linkUrl" class="form-label">URL *</label>
                                    <input type="url" class="form-control" id="linkUrl" name="link_url" 
                                        placeholder="https://example.com">
                                    <div class="form-text">Enter the full URL including http:// or https://</div>
                                </div>
                                <div class="mb-3">
                                    <label for="linkTitle" class="form-label">Link Title</label>
                                    <input type="text" class="form-control" id="linkTitle" name="link_title" 
                                        placeholder="Enter link title (optional)">
                                    <div class="form-text">Leave empty to use the page title from the URL</div>
                                </div>
                                <div class="mb-3">
                                    <label for="linkDescription" class="form-label">Link Description</label>
                                    <textarea class="form-control" id="linkDescription" name="link_description" 
                                        rows="3" placeholder="Enter link description (optional)"></textarea>
                                    <div class="form-text">Brief description of the link content</div>
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
                                            <option value="group_specific">Group Specific</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <!-- Custom Field Selection (for Group Specific) -->
                                    <div class="mb-3" id="customFieldSelection" style="display: none;">
                                        <div class="mb-3">
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
                                        <div class="mb-3">
                                            <label for="customFieldValue" class="form-label">Select Custom Field Value <span class="text-danger">*</span></label>
                                            <select class="form-select" id="customFieldValue" name="custom_field_value">
                                                <option value="">Select value...</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
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
            <form id="editPostForm" enctype="multipart/form-data">
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
                            <div class="mb-3" id="editMediaSection" style="display: none;">
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
                                
                                <!-- Poll Question -->
                                <div class="mb-3">
                                    <label for="editPollQuestion" class="form-label">Poll Question *</label>
                                    <input type="text" class="form-control" id="editPollQuestion" name="poll_question" 
                                        placeholder="What would you like to ask?" maxlength="200">
                                    <div class="form-text">Maximum 200 characters</div>
                                </div>
                                
                                <!-- Poll Options -->
                                <div class="mb-3">
                                    <label class="form-label">Poll Options *</label>
                                    <div id="editPollOptions">
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" name="edit_poll_options[]" placeholder="Option 1">
                                        </div>
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" name="edit_poll_options[]" placeholder="Option 2">
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="editAddPollOption">
                                        <i class="fas fa-plus me-1"></i>Add Option
                                    </button>
                                </div>
                                
                                <!-- Poll Settings -->
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="editAllowMultipleVotes" name="allow_multiple_votes">
                                    <label class="form-check-label" for="editAllowMultipleVotes">
                                        Allow multiple votes
                                    </label>
                                </div>
                            </div>

                            <!-- Link Creation -->
                            <div class="mb-3" id="editLinkSection" style="display: none;">
                                <label class="form-label">Link Details</label>
                                <div class="mb-3">
                                    <label for="editLinkUrl" class="form-label">URL *</label>
                                    <input type="url" class="form-control" id="editLinkUrl" name="link_url" 
                                        placeholder="https://example.com">
                                    <div class="form-text">Enter the full URL including http:// or https://</div>
                                </div>
                                <div class="mb-3">
                                    <label for="editLinkTitle" class="form-label">Link Title</label>
                                    <input type="text" class="form-control" id="editLinkTitle" name="link_title" 
                                        placeholder="Enter link title (optional)">
                                    <div class="form-text">Leave empty to use the page title from the URL</div>
                                </div>
                                <div class="mb-3">
                                    <label for="editLinkDescription" class="form-label">Link Description</label>
                                    <textarea class="form-control" id="editLinkDescription" name="link_description" 
                                        rows="3" placeholder="Enter link description (optional)"></textarea>
                                    <div class="form-text">Brief description of the link content</div>
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
                                            <option value="group_specific">Group Specific</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <!-- Custom Field Selection (for Group Specific) - Edit Modal -->
                                    <div class="mb-3" id="editCustomFieldSelection" style="display: none;">
                                        <div class="mb-3">
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
                                        <div class="mb-3">
                                            <label for="editCustomFieldValue" class="form-label">Select Custom Field Value <span class="text-danger">*</span></label>
                                            <select class="form-select" id="editCustomFieldValue" name="custom_field_value">
                                                <option value="">Select value...</option>
                                            </select>
                                            <div class="invalid-feedback"></div>
                                        </div>
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
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Post Content -->
                        <div class="mb-4">
                            <div class="post-content"></div>
                        </div>

                        <!-- Media Files -->
                        <div class="mb-4">
                            <h6><i class="fas fa-images me-2"></i>Media Files</h6>
                            <div class="post-media"></div>
                        </div>

                        <!-- Poll Data -->
                        <div class="mb-4">
                            <h6><i class="fas fa-poll me-2"></i>Poll</h6>
                            <div class="post-poll"></div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Post Meta Information -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Post Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Author:</strong>
                                    <span class="post-author"></span>
                                </div>
                                <div class="mb-2">
                                    <strong>Date:</strong>
                                    <span class="post-date"></span>
                                </div>
                                <div class="mb-2">
                                    <strong>Type:</strong>
                                    <span class="post-type"></span>
                                </div>
                                <div class="mb-2">
                                    <strong>Status & Visibility:</strong>
                                    <div class="post-status"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Tags -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-tags me-2"></i>Tags</h6>
                            </div>
                            <div class="card-body">
                                <div class="post-tags"></div>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistics</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Views:</strong>
                                    <span class="post-views"></span>
                                </div>
                                <div class="mb-2">
                                    <strong>Reactions:</strong>
                                    <span class="post-reactions"></span>
                                </div>
                                <div class="mb-2">
                                    <strong>Comments:</strong>
                                    <span class="post-comments"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Reports -->
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-flag me-2"></i>Reports</h6>
                            </div>
                            <div class="card-body">
                                <div class="post-reports"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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

<?php require_once 'includes/footer.php'; ?>

<script src="<?= UrlHelper::url('public/js/social_feed_validation.js') ?>"></script>
<script src="<?= UrlHelper::url('public/js/social_feed.js') ?>"></script>

<script>
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
    console.log('🔄 loadCustomFieldValues called');
    console.log('📝 fieldIdSelect:', fieldIdSelect);
    console.log('📝 fieldValueSelect:', fieldValueSelect);
    
    const selectedOption = fieldIdSelect.options[fieldIdSelect.selectedIndex];
    const optionsData = selectedOption.getAttribute('data-options');
    
    console.log('📊 Selected option:', selectedOption);
    console.log('📊 Options data:', optionsData);
    
    // Clear existing options
    fieldValueSelect.innerHTML = '<option value="">Select value...</option>';
    
    if (optionsData) {
        try {
            const options = JSON.parse(optionsData);
            console.log('📊 Parsed options:', options);
            console.log('📊 Type:', typeof options);
            
            // Check if options is a string (contains \r\n) or an array
            if (typeof options === 'string') {
                console.log('✅ Processing string with line breaks');
                // If it's a string with \r\n, split it
                const splitOptions = options.split(/\r?\n/).filter(opt => opt.trim() !== '');
                console.log('📊 Split options:', splitOptions);
                splitOptions.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option.trim();
                    optionElement.textContent = option.trim();
                    fieldValueSelect.appendChild(optionElement);
                    console.log(`✅ Added string option: ${option.trim()}`);
                });
            } else if (Array.isArray(options)) {
                console.log('✅ Processing array of options');
                // If it's already an array, use it directly
                options.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option;
                    optionElement.textContent = option;
                    fieldValueSelect.appendChild(optionElement);
                    console.log(`✅ Added array option: ${option}`);
                });
            }
            console.log('🎯 Total options added:', fieldValueSelect.options.length - 1);
        } catch (e) {
            console.log('❌ JSON parsing failed, treating as string:', e);
            // If JSON parsing fails, treat as newline-separated string
            const options = optionsData.split(/\r?\n/).filter(opt => opt.trim() !== '');
            console.log('📊 Fallback split options:', options);
            options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option.trim();
                optionElement.textContent = option.trim();
                fieldValueSelect.appendChild(optionElement);
                console.log(`✅ Added fallback option: ${option.trim()}`);
            });
        }
    } else {
        console.log('❌ No options data found');
    }
}

// Initialize custom field functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Initializing custom field functionality...');
    
    // Create form custom field handling
    const createVisibility = document.getElementById('visibility');
    const createCustomFieldSelection = document.getElementById('customFieldSelection');
    const createCustomFieldId = document.getElementById('customFieldId');
    const createCustomFieldValue = document.getElementById('customFieldValue');

    console.log('📝 Create form elements:', {
        visibility: createVisibility,
        customFieldSelection: createCustomFieldSelection,
        customFieldId: createCustomFieldId,
        customFieldValue: createCustomFieldValue
    });

    if (createVisibility && createCustomFieldSelection) {
        console.log('✅ Attaching visibility change listener for create form');
        createVisibility.addEventListener('change', function() {
            console.log('🔄 Visibility changed to:', this.value);
            toggleCustomFieldSelection(this, createCustomFieldSelection);
        });
    } else {
        console.log('❌ Create form visibility elements not found');
    }

    if (createCustomFieldId && createCustomFieldValue) {
        console.log('✅ Attaching custom field change listener for create form');
        createCustomFieldId.addEventListener('change', function() {
            console.log('🔄 Custom field changed to:', this.value);
            loadCustomFieldValues(createCustomFieldId, createCustomFieldValue);
        });
    } else {
        console.log('❌ Create form custom field elements not found');
    }

    // Edit form custom field handling
    const editVisibility = document.getElementById('editVisibility');
    const editCustomFieldSelection = document.getElementById('editCustomFieldSelection');
    const editCustomFieldId = document.getElementById('editCustomFieldId');
    const editCustomFieldValue = document.getElementById('editCustomFieldValue');

    console.log('📝 Edit form elements:', {
        visibility: editVisibility,
        customFieldSelection: editCustomFieldSelection,
        customFieldId: editCustomFieldId,
        customFieldValue: editCustomFieldValue
    });

    if (editVisibility && editCustomFieldSelection) {
        console.log('✅ Attaching visibility change listener for edit form');
        editVisibility.addEventListener('change', function() {
            console.log('🔄 Edit visibility changed to:', this.value);
            toggleCustomFieldSelection(this, editCustomFieldSelection);
        });
    } else {
        console.log('❌ Edit form visibility elements not found');
    }

    if (editCustomFieldId && editCustomFieldValue) {
        console.log('✅ Attaching custom field change listener for edit form');
        editCustomFieldId.addEventListener('change', function() {
            console.log('🔄 Edit custom field changed to:', this.value);
            loadCustomFieldValues(editCustomFieldId, editCustomFieldValue);
        });
    } else {
        console.log('❌ Edit form custom field elements not found');
    }
    
    console.log('🎯 Custom field initialization complete');
});
</script> 