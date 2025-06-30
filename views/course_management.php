<?php
require_once '../config/autoload.php';
require_once '../core/middleware/AuthMiddleware.php';

// Check authentication
$auth = new AuthMiddleware();
if (!$auth->isAuthenticated()) {
    header('Location: /login');
    exit;
}

// Get user info
$user = $_SESSION['user'] ?? null;
$clientId = $_SESSION['client_id'] ?? null;

// Load courses
$courseModel = new CourseModel();
$courses = $courseModel->getAllCourses($clientId);

// Load categories for filtering
$categoryModel = new CourseCategoryModel();
$categories = $categoryModel->getAllCategories($clientId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - Unlock Your Skills</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/public/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-purple">
                        <i class="fas fa-graduation-cap me-2"></i>
                        Course Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="/course-creation" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            Create New Course
                        </a>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section mb-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchInput" placeholder="Search courses...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="categoryFilter">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['name']) ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters">
                                <i class="fas fa-times me-1"></i>
                                Clear
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?= count($courses) ?></h4>
                                        <small>Total Courses</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-graduation-cap fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?= count(array_filter($courses, fn($c) => $c['status'] === 'published')) ?></h4>
                                        <small>Published</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?= count(array_filter($courses, fn($c) => $c['status'] === 'draft')) ?></h4>
                                        <small>Drafts</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-edit fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 class="mb-0"><?= array_sum(array_column($courses, 'enrollment_count')) ?></h4>
                                        <small>Total Enrollments</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Courses Grid -->
                <div id="coursesGrid">
                    <?php if (empty($courses)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No courses found</h4>
                            <p class="text-muted">Create your first course to get started</p>
                            <a href="/course-creation" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>
                                Create Course
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row" id="coursesContainer">
                            <?php foreach ($courses as $course): ?>
                                <div class="col-lg-4 col-md-6 mb-4 course-card" 
                                     data-name="<?= htmlspecialchars(strtolower($course['name'])) ?>"
                                     data-category="<?= htmlspecialchars(strtolower($course['category_name'])) ?>"
                                     data-status="<?= htmlspecialchars($course['status']) ?>">
                                    <div class="card h-100 course-card-inner">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <span class="badge bg-<?= $course['status'] === 'published' ? 'success' : ($course['status'] === 'draft' ? 'warning' : 'secondary') ?>">
                                                <?= ucfirst($course['status']) ?>
                                            </span>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="/course-edit/<?= $course['id'] ?>">
                                                            <i class="fas fa-edit me-2"></i>
                                                            Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="/course-preview/<?= $course['id'] ?>">
                                                            <i class="fas fa-eye me-2"></i>
                                                            Preview
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="/course-analytics/<?= $course['id'] ?>">
                                                            <i class="fas fa-chart-bar me-2"></i>
                                                            Analytics
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <?php if ($course['status'] === 'draft'): ?>
                                                        <li>
                                                            <a class="dropdown-item text-success" href="#" onclick="publishCourse(<?= $course['id'] ?>)">
                                                                <i class="fas fa-check me-2"></i>
                                                                Publish
                                                            </a>
                                                        </li>
                                                    <?php elseif ($course['status'] === 'published'): ?>
                                                        <li>
                                                            <a class="dropdown-item text-warning" href="#" onclick="unpublishCourse(<?= $course['id'] ?>)">
                                                                <i class="fas fa-pause me-2"></i>
                                                                Unpublish
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" onclick="deleteCourse(<?= $course['id'] ?>)">
                                                            <i class="fas fa-trash me-2"></i>
                                                            Delete
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($course['name']) ?></h5>
                                            <p class="card-text text-muted">
                                                <?= htmlspecialchars(substr($course['description'], 0, 100)) ?>
                                                <?= strlen($course['description']) > 100 ? '...' : '' ?>
                                            </p>
                                            
                                            <div class="course-meta mb-3">
                                                <div class="row text-center">
                                                    <div class="col-4">
                                                        <div class="course-stat">
                                                            <div class="stat-number"><?= $course['module_count'] ?></div>
                                                            <div class="stat-label">Modules</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="course-stat">
                                                            <div class="stat-number"><?= $course['enrollment_count'] ?></div>
                                                            <div class="stat-label">Enrolled</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="course-stat">
                                                            <div class="stat-number"><?= $course['completion_rate'] ?>%</div>
                                                            <div class="stat-label">Complete</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="course-tags">
                                                <span class="badge bg-light text-dark me-1">
                                                    <i class="fas fa-folder me-1"></i>
                                                    <?= htmlspecialchars($course['category_name']) ?>
                                                </span>
                                                <?php if ($course['subcategory_name']): ?>
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-tag me-1"></i>
                                                        <?= htmlspecialchars($course['subcategory_name']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                Created: <?= date('M j, Y', strtotime($course['created_at'])) ?>
                                            </small>
                                            <?php if ($course['updated_at'] !== $course['created_at']): ?>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-edit me-1"></i>
                                                    Updated: <?= date('M j, Y', strtotime($course['updated_at'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Loading Indicator -->
                <div id="loadingIndicator" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading courses...</p>
                </div>

                <!-- No Results Message -->
                <div id="noResultsMessage" class="text-center py-5" style="display: none;">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No courses found</h4>
                    <p class="text-muted">Try adjusting your search criteria</p>
                </div>
            </main>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="confirmationModalBody">
                    Are you sure you want to perform this action?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmationModalConfirm">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/public/js/toast_notifications.js"></script>
    <script src="/public/js/course_management.js"></script>
</body>
</html> 