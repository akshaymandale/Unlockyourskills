<?php include 'views/includes/header.php'; ?>
<?php include 'views/includes/navbar.php'; ?>
<?php include 'views/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container mt-4">
        <h1 class="page-title text-purple mb-4">
            <i class="fas fa-chart-line me-2"></i> <?= Localization::translate('user_progress_report'); ?>
        </h1>
        <p class="text-muted mb-4"><?= Localization::translate('user_progress_report_description'); ?></p>

        <!-- Filters Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= Localization::translate('filters'); ?></h5>
                    </div>
                    <div class="card-body">
                        <form id="reportFilters">
                            <div class="row g-3">
                                <!-- Date Range -->
                                <div class="col-md-2">
                                    <label for="startDate" class="form-label"><?= Localization::translate('start_date'); ?></label>
                                    <input type="date" class="form-control" id="startDate" name="start_date">
                                </div>
                                <div class="col-md-2">
                                    <label for="endDate" class="form-label"><?= Localization::translate('end_date'); ?></label>
                                    <input type="date" class="form-control" id="endDate" name="end_date">
                                </div>

                                <!-- Users -->
                                <div class="col-md-2">
                                    <label class="form-label"><?= Localization::translate('users'); ?></label>
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle w-100 filter-dropdown-btn" type="button" id="userFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span id="userFilterText"><?= Localization::translate('all_users'); ?></span>
                                        </button>
                                        <ul class="dropdown-menu w-100 filter-dropdown-menu" aria-labelledby="userFilterDropdown" style="max-height: 300px; overflow-y: auto;">
                                            <li class="px-3 py-2">
                                                <input type="text" class="form-control form-control-sm filter-search-input" id="userSearchInput" placeholder="Search users..." autocomplete="off">
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <div class="form-check px-3 py-1">
                                                    <input class="form-check-input" type="checkbox" id="userFilterAll" value="all">
                                                    <label class="form-check-label" for="userFilterAll">
                                                        <?= Localization::translate('all_users'); ?>
                                                    </label>
                                                </div>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <div id="userFilterOptions">
                                                <?php foreach ($data['filterOptions']['users'] as $user): ?>
                                                <li class="user-option" data-search="<?= strtolower(htmlspecialchars($user['full_name'] . ' ' . $user['email'])) ?>">
                                                    <div class="form-check px-3 py-1">
                                                        <input class="form-check-input user-filter-checkbox" type="checkbox" id="user_<?= $user['id'] ?>" name="user_ids[]" value="<?= $user['id'] ?>">
                                                        <label class="form-check-label" for="user_<?= $user['id'] ?>">
                                                            <?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                                        </label>
                                                    </div>
                                                </li>
                                                <?php endforeach; ?>
                                            </div>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Custom Field Selection -->
                                <div class="col-md-2">
                                    <label for="customFieldSelect" class="form-label"><?= Localization::translate('custom_field'); ?></label>
                                    <select class="form-select" id="customFieldSelect" name="custom_field_id">
                                        <option value=""><?= Localization::translate('select_custom_field'); ?></option>
                                        <?php foreach ($data['customFields'] as $field): ?>
                                            <option value="<?= $field['id'] ?>" data-field-type="<?= $field['field_type'] ?>">
                                                <?= htmlspecialchars($field['field_label']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Custom Field Values -->
                                <div class="col-md-2">
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
                                                <div class="form-check px-3 py-1">
                                                    <input class="form-check-input" type="checkbox" id="customFieldValueAll" value="all">
                                                    <label class="form-check-label" for="customFieldValueAll">
                                                        <?= Localization::translate('all_values'); ?>
                                                    </label>
                                                </div>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <div id="customFieldValueOptions">
                                                <!-- Options will be populated by JavaScript -->
                                            </div>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Courses -->
                                <div class="col-md-2">
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
                                                <div class="form-check px-3 py-1">
                                                    <input class="form-check-input" type="checkbox" id="courseFilterAll" value="all">
                                                    <label class="form-check-label" for="courseFilterAll">
                                                        <?= Localization::translate('all_courses'); ?>
                                                    </label>
                                                </div>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <div id="courseFilterOptions">
                                                <?php foreach ($data['filterOptions']['courses'] as $course): ?>
                                                <li class="course-option" data-search="<?= strtolower(htmlspecialchars($course['name'])) ?>">
                                                    <div class="form-check px-3 py-1">
                                                        <input class="form-check-input course-filter-checkbox" type="checkbox" id="course_<?= $course['id'] ?>" name="course_ids[]" value="<?= $course['id'] ?>">
                                                        <label class="form-check-label" for="course_<?= $course['id'] ?>">
                                                            <?= htmlspecialchars($course['name']) ?>
                                                        </label>
                                                    </div>
                                                </li>
                                                <?php endforeach; ?>
                                            </div>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="col-md-2">
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
                            </div>

                            <!-- Action Buttons -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary" id="applyFilters">
                                            <i class="fas fa-filter"></i> <?= Localization::translate('apply_filters'); ?>
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="clearFilters">
                                            <i class="fas fa-times"></i> <?= Localization::translate('clear_filters'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row">
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-secondary" id="notStartedCourses">-</h5>
                                <p class="card-text"><?= Localization::translate('not_started_courses'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-warning" id="inProgressCourses">-</h5>
                                <p class="card-text"><?= Localization::translate('in_progress_courses'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-success" id="completedCourses">-</h5>
                                <p class="card-text"><?= Localization::translate('completed_courses'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h5 class="card-title text-info" id="avgCompletion">-</h5>
                                <p class="card-text"><?= Localization::translate('avg_completion'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><?= Localization::translate('completion_status_chart'); ?></h5>
                            </div>
                            <div class="card-body">
                                <canvas id="completionStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><?= Localization::translate('department_progress_chart'); ?></h5>
                            </div>
                            <div class="card-body">
                                <canvas id="departmentProgressChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><?= Localization::translate('detailed_progress_table'); ?></h5>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary" id="exportPdf">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" id="exportExcel">
                                        <i class="fas fa-file-excel"></i> Excel
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" id="exportCsv">
                                        <i class="fas fa-file-csv"></i> CSV
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="progressTable">
                                        <thead>
                                            <tr>
                                                <th><?= Localization::translate('user_name'); ?></th>
                                                <th><?= Localization::translate('email'); ?></th>
                                                <th><?= Localization::translate('department'); ?></th>
                                                <th><?= Localization::translate('course_name'); ?></th>
                                                <th><?= Localization::translate('completion_percentage'); ?></th>
                                                <th><?= Localization::translate('status'); ?></th>
                                                <th><?= Localization::translate('last_accessed'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Data will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/includes/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom CSS for Reports -->
<style>
/* Horizontal Filters Styling */
#reportFilters .form-label {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #495057;
}

#reportFilters .form-control,
#reportFilters .form-select {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

#reportFilters .form-check {
    margin-bottom: 0.25rem;
}

#reportFilters .form-check-label {
    font-size: 0.8rem;
    margin-left: 0.25rem;
}

#reportFilters .form-check-input {
    margin-top: 0.1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #reportFilters .col-md-2 {
        margin-bottom: 1rem;
    }
}

@media (max-width: 576px) {
    #reportFilters .col-md-2 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

/* Action buttons styling */
#reportFilters .btn {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}

#reportFilters .btn i {
    margin-right: 0.25rem;
}
</style>

<!-- Custom JavaScript -->
<script src="/Unlockyourskills/public/js/reports/user_progress_report.js"></script>

<script>
// Pass data from PHP to JavaScript
window.reportData = <?= json_encode($data) ?>;
console.log('Report data loaded from server:', window.reportData);
</script>
