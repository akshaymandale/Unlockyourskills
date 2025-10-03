<?php include 'views/includes/header.php'; ?>
<?php include 'views/includes/navbar.php'; ?>
<?php include 'views/includes/sidebar.php'; ?>

<div class="main-content" data-course-completion-report-page="true">
    <div class="container mt-4">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="<?= UrlHelper::url('manage-portal') ?>" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-graduation-cap me-2"></i>
                <?= Localization::translate('course_completion_report'); ?>
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
                <li class="breadcrumb-item active" aria-current="page"><?= Localization::translate('course_completion_report'); ?></li>
            </ol>
        </nav>

        <!-- Filters Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>
                    <?= Localization::translate('filters'); ?>
                </h5>
            </div>
            <div class="card-body">
                <div id="reportFilters">
                    <div class="row g-3">
                        <!-- Date Range -->
                        <div class="col-md-3">
                            <label for="startDate" class="form-label"><?= Localization::translate('start_date'); ?></label>
                            <input type="date" class="form-control" id="startDate" name="start_date">
                        </div>
                        <div class="col-md-3">
                            <label for="endDate" class="form-label"><?= Localization::translate('end_date'); ?></label>
                            <input type="date" class="form-control" id="endDate" name="end_date">
                        </div>

                        <!-- Courses Filter -->
                        <div class="col-md-3">
                            <label class="form-label"><?= Localization::translate('courses'); ?></label>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100 filter-dropdown-btn" type="button" id="courseFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span id="courseFilterText"><?= Localization::translate('all_courses'); ?></span>
                                </button>
                                <ul class="dropdown-menu w-100 filter-dropdown-menu" aria-labelledby="courseFilterDropdown" style="max-height: 300px; overflow-y: auto;">
                                    <li class="px-3 py-2">
                                        <input type="text" class="form-control form-control-sm filter-search-input" id="courseSearchInput" placeholder="Search courses..." autocomplete="off">
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <div class="form-check py-1">
                                            <input class="form-check-input" type="checkbox" id="courseFilterAll" value="all">
                                            <label class="form-check-label" for="courseFilterAll">
                                                <?= Localization::translate('all_courses'); ?>
                                            </label>
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li id="courseFilterOptions" style="padding: 0; margin: 0;">
                                        <?php foreach ($data['filterOptions']['courses'] as $course): ?>
                                        <div class="course-option" data-search="<?= strtolower(htmlspecialchars($course['name'])) ?>">
                                            <div class="form-check py-1">
                                                <input class="form-check-input course-filter-checkbox" type="checkbox" id="course_<?= $course['id'] ?>" name="course_ids[]" value="<?= $course['id'] ?>">
                                                <label class="form-check-label" for="course_<?= $course['id'] ?>">
                                                    <?= htmlspecialchars($course['name']) ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Custom Field Filter -->
                        <div class="col-md-3">
                            <label class="form-label"><?= Localization::translate('custom_field'); ?></label>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100 filter-dropdown-btn" type="button" id="customFieldDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span id="customFieldText"><?= Localization::translate('select_custom_field'); ?></span>
                                </button>
                                <ul class="dropdown-menu w-100 filter-dropdown-menu" aria-labelledby="customFieldDropdown" style="max-height: 300px; overflow-y: auto;">
                                    <li class="px-3 py-2">
                                        <input type="text" class="form-control form-control-sm filter-search-input" id="customFieldSearchInput" placeholder="Search custom fields..." autocomplete="off">
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li id="customFieldOptions" style="padding: 0; margin: 0;">
                                        <?php foreach ($data['customFields'] as $field): ?>
                                        <div class="custom-field-option" data-search="<?= strtolower(htmlspecialchars($field['field_label'] . ' ' . $field['field_name'])) ?>" data-field-type="<?= $field['field_type'] ?>">
                                            <div class="form-check py-1" style="padding-left: 2rem;">
                                                <label class="form-check-label" style="cursor: pointer;" data-field-id="<?= $field['id'] ?>">
                                                    <?= htmlspecialchars($field['field_label']) ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Custom Field Values -->
                        <div class="col-md-3">
                            <label class="form-label"><?= Localization::translate('field_value'); ?></label>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100 filter-dropdown-btn" type="button" id="customFieldValueDropdown" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                                    <span id="customFieldValueText"><?= Localization::translate('select_field_value'); ?></span>
                                </button>
                                <ul class="dropdown-menu w-100 filter-dropdown-menu" aria-labelledby="customFieldValueDropdown" style="max-height: 300px; overflow-y: auto;">
                                    <li class="px-3 py-2">
                                        <input type="text" class="form-control form-control-sm filter-search-input" id="customFieldValueSearchInput" placeholder="Search values..." autocomplete="off" disabled>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <div class="form-check py-1">
                                            <input class="form-check-input" type="checkbox" id="customFieldValueAll" value="all">
                                            <label class="form-check-label" for="customFieldValueAll">
                                                <?= Localization::translate('all_values'); ?>
                                            </label>
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li id="customFieldValueOptions" style="padding: 0; margin: 0;">
                                        <!-- Options will be populated by JavaScript -->
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-3">
                            <label class="form-label"><?= Localization::translate('status'); ?></label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="statusNotStarted" name="status[]" value="not_started">
                                <label class="form-check-label" for="statusNotStarted">
                                    <?= Localization::translate('not_started'); ?>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="statusInProgress" name="status[]" value="in_progress">
                                <label class="form-check-label" for="statusInProgress">
                                    <?= Localization::translate('in_progress'); ?>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="statusCompleted" name="status[]" value="completed">
                                <label class="form-check-label" for="statusCompleted">
                                    <?= Localization::translate('completed'); ?>
                                </label>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="d-flex gap-2 w-100">
                                <button type="button" class="btn btn-primary flex-fill" id="applyFilters" data-action="apply-filters">
                                    <i class="fas fa-filter"></i> <?= Localization::translate('apply_filters'); ?>
                                </button>
                                <button type="button" class="btn btn-outline-secondary flex-fill" id="clearFilters">
                                    <i class="fas fa-times"></i> <?= Localization::translate('clear_filters'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center border-primary">
                    <div class="card-body">
                        <h5 class="card-title text-primary mb-0" id="totalCourses">-</h5>
                        <p class="card-text text-muted"><?= Localization::translate('total_courses'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-info">
                    <div class="card-body">
                        <h5 class="card-title text-info mb-0" id="totalEnrollments">-</h5>
                        <p class="card-text text-muted"><?= Localization::translate('total_enrollments'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-success">
                    <div class="card-body">
                        <h5 class="card-title text-success mb-0" id="overallCompletionRate">-</h5>
                        <p class="card-text text-muted"><?= Localization::translate('overall_completion_rate'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body">
                        <h5 class="card-title text-warning mb-0" id="avgCompletionPercentage">-</h5>
                        <p class="card-text text-muted"><?= Localization::translate('avg_completion'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            <?= Localization::translate('enrollment_status_distribution'); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="enrollmentStatusChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            <?= Localization::translate('course_completion_rates'); ?>
                        </h5>
                        <small class="text-muted">Top 10 courses by completion rate</small>
                    </div>
                    <div class="card-body">
                        <canvas id="completionRateChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>
                    <?= Localization::translate('course_completion_data'); ?>
                </h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-danger" id="exportPdf" title="Download PDF">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" id="exportExcel" title="Download Excel">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="completionTable">
                        <thead class="table-dark">
                            <tr>
                                <th><?= Localization::translate('course_name'); ?></th>
                                <th><?= Localization::translate('applicable_users'); ?></th>
                                <th><?= Localization::translate('enrolled'); ?></th>
                                <th><?= Localization::translate('completed'); ?></th>
                                <th><?= Localization::translate('in_progress'); ?></th>
                                <th><?= Localization::translate('not_started'); ?></th>
                                <th><?= Localization::translate('completion_rate'); ?></th>
                                <th><?= Localization::translate('avg_completion'); ?></th>
                                <th><?= Localization::translate('last_activity'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="completionTableBody">
                            <!-- Data will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Controls -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="pagination-info">
                        <span id="paginationInfo">Showing 0 to 0 of 0 entries</span>
                    </div>
                    <nav aria-label="Course completion report pagination">
                        <ul class="pagination mb-0" id="paginationControls">
                            <!-- Pagination buttons will be generated here -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/includes/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom JavaScript -->
<script src="/Unlockyourskills/public/js/reports/course_completion_report.js"></script>

<script>
// Pass data from PHP to JavaScript
    window.reportData = <?= json_encode($data) ?>;
</script>

<style>
/* Custom styles for course completion report */
.course-completion-report .filter-section .row {
    margin-bottom: 1rem;
}

.course-completion-report .card-header h5 {
    font-weight: 600;
}

.course-completion-report .table th {
    font-weight: 600;
    border-top: none;
}

.course-completion-report .pagination-info {
    font-size: 0.9rem;
    color: #6c757d;
}

.course-completion-report .border-primary {
    border-color: #8b5cf6 !important;
}

.course-completion-report .border-info {
    border-color: #0dcaf0 !important;
}

.course-completion-report .border-success {
    border-color: #198754 !important;
}

.course-completion-report .border-warning {
    border-color: #ffc107 !important;
}

/* Chart sizing */
.course-completion-report .card-body canvas {
    max-height: 300px;
}
</style>

