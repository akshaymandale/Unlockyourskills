<?php
// ✅ Fix session issue: Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    header('Location: index.php');
    exit();
}

include 'core/UrlHelper.php';
include 'views/includes/header.php';
include 'views/includes/navbar.php';
include 'views/includes/sidebar.php';

// Get user info - these variables are already set by the controller
$user = $_SESSION['user'] ?? null;
$clientId = $_SESSION['user']['client_id'] ?? null;

// Check if user is logged in
if (!isset($_SESSION['user']['client_id'])) {
    header('Location: index.php?controller=LoginController');
    exit;
}

require_once 'core/UrlHelper.php';
?>

<div class="main-content">
    <div class="container mt-4 assessment-details" data-assessment-page="true">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="<?= UrlHelper::url('manage-portal') ?>" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-clipboard-check me-2"></i>
                Assessment Details
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
                <li class="breadcrumb-item active" aria-current="page">Assessment Details</li>
            </ol>
        </nav>

        <!-- Page Description -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">Manage assessment attempts and increase limits for users who have exceeded their allowed attempts</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-secondary" onclick="loadHistory()" title="Refresh History">
                            <i class="fas fa-sync-alt me-2"></i>Refresh History
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="resetProcess()" title="Start New Process">
                            <i class="fas fa-plus me-2"></i>New Process
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Selection -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-graduation-cap me-2 text-purple"></i>
                            Step 1: Select Course
                        </h5>
                        <p class="text-muted mb-3">Select a course where users have exceeded assessment attempts and failed.</p>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="autocomplete-container">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control" 
                                               id="courseSearch" 
                                               placeholder="Search courses..." 
                                               autocomplete="off"
                                               onkeyup="filterCourses()"
                                               onfocus="showCourseDropdown()"
                                               onblur="hideCourseDropdown()">
                                    </div>
                                    <div id="courseDropdown" class="autocomplete-dropdown" style="display: none;">
                                        <div id="courseList">
                                            <?php foreach ($courses as $course): ?>
                                                <div class="course-item" 
                                                     data-course-id="<?= $course['course_id'] ?>"
                                                     data-failed-users="<?= $course['failed_users_count'] ?>"
                                                     data-failed-assessments="<?= $course['failed_assessments_count'] ?>"
                                                     onclick="selectCourse(<?= $course['course_id'] ?>, '<?= htmlspecialchars($course['course_name'], ENT_QUOTES) ?>', <?= $course['failed_users_count'] ?>, <?= $course['failed_assessments_count'] ?>)">
                                                    <div class="course-name"><?= htmlspecialchars($course['course_name']) ?></div>
                                                    <div class="course-stats">
                                                        <span class="badge bg-danger"><?= $course['failed_users_count'] ?> failed users</span>
                                                        <span class="badge bg-warning"><?= $course['failed_assessments_count'] ?> assessments</span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-outline-primary w-100" onclick="refreshCourses()">
                                    <i class="fas fa-sync-alt me-2"></i>Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assessment Context Selection -->
        <div class="row mb-4" id="contextCard" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-list me-2 text-info"></i>
                            Step 2: Select Assessment Context
                        </h5>
                        <p class="text-muted mb-3">Select where the assessment is used (prerequisite, module, or post-requisite).</p>
                        <div id="contextsContainer">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading assessment contexts...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Selection -->
        <div class="row mb-4" id="userCard" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-users me-2 text-warning"></i>
                            Step 3: Select Users
                        </h5>
                        <p class="text-muted mb-3">Search and select users who have exceeded attempts and failed this assessment.</p>
                        
                        <!-- Search Users -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="userSearch" placeholder="Search users by name, email, or employee ID...">
                                    <button class="btn btn-outline-primary" onclick="searchUsers()">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-success" onclick="selectAllUsers()">
                                        <i class="fas fa-check-double me-2"></i>Select All
                                    </button>
                                    <button class="btn btn-outline-secondary" onclick="clearUserSelection()">
                                        <i class="fas fa-times me-2"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Users List -->
                        <div id="usersContainer">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading users...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Increase Attempts -->
        <div class="row mb-4" id="increaseCard" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-plus-circle me-2 text-success"></i>
                            Step 4: Increase Attempts
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="attemptsToAdd" class="form-label">Number of Attempts to Add</label>
                                <input type="number" class="form-control" id="attemptsToAdd" min="1" max="10" value="1">
                            </div>
                            <div class="col-md-6">
                                <label for="reason" class="form-label">Reason (Optional)</label>
                                <input type="text" class="form-control" id="reason" placeholder="Enter reason for increasing attempts...">
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Selected Users:</strong> <span id="selectedUsersCount">0</span> users
                            </div>
                        </div>
                        
                        <div class="mt-4 d-flex gap-2">
                            <button class="btn btn-success" onclick="increaseAttempts()" id="increaseBtn">
                                <i class="fas fa-plus me-2"></i>Increase Attempts
                            </button>
                            <button class="btn btn-outline-secondary" onclick="resetProcess()">
                                <i class="fas fa-undo me-2"></i>Start Over
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2 text-dark"></i>
                            Attempt Increase History
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="historyContainer">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading history...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Increase Attempts Modal -->
<div class="modal fade" id="increaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>
                    Attempts Increased Successfully
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="increaseResults"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="resetProcess()">Start New Process</button>
            </div>
        </div>
    </div>
</div>


<!-- Include Assessment Details JavaScript -->
<script src="<?= UrlHelper::url('public/js/assessment_details.js') ?>"></script>

<?php include 'views/includes/footer.php'; ?>
