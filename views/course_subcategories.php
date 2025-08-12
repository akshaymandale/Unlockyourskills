<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="<?php echo UrlHelper::url('manage-portal'); ?>" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-layer-group me-2"></i>
                <?php echo Localization::translate('course_subcategories.title'); ?>
            </h1>
        </div>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo UrlHelper::url('dashboard'); ?>"><?php echo Localization::translate('dashboard'); ?></a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?php echo UrlHelper::url('manage-portal'); ?>"><?php echo Localization::translate('manage_portal'); ?></a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo Localization::translate('course_subcategories.title'); ?>
                </li>
            </ol>
        </nav>

        <!-- Page Description and Add Button -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0"><?php echo Localization::translate('course_subcategories.subtitle'); ?></p>
                    </div>
                    <button type="button" class="btn theme-btn-primary" id="addSubcategoryBtn" data-action="add-subcategory"
                            title="<?php echo Localization::translate('course_subcategories.add_subcategory_tooltip'); ?>">
                        <i class="fas fa-plus me-2"></i><?php echo Localization::translate('course_subcategories.add_subcategory'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3 align-items-center">
                            <!-- Search -->
                            <div class="col-md-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" id="searchInput" class="form-control" 
                                           placeholder="<?php echo Localization::translate('course_subcategories.search_placeholder'); ?>" 
                                           title="<?php echo Localization::translate('course_subcategories.search'); ?>">
                                    <button type="button" id="searchButton" class="btn btn-outline-secondary" 
                                            title="<?php echo Localization::translate('course_subcategories.search'); ?>">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Status Filter -->
                            <div class="col-md-2">
                                <select class="form-select form-select-sm" id="statusFilter">
                                    <option value=""><?php echo Localization::translate('course_subcategories.all_statuses'); ?></option>
                                    <option value="active" <?php echo ($statusFilter ?? '') === 'active' ? 'selected' : ''; ?>>
                                        <?php echo Localization::translate('course_subcategories.active'); ?>
                                    </option>
                                    <option value="inactive" <?php echo ($statusFilter ?? '') === 'inactive' ? 'selected' : ''; ?>>
                                        <?php echo Localization::translate('course_subcategories.inactive'); ?>
                                    </option>
                                </select>
                            </div>
                            <!-- Category Filter -->
                            <div class="col-md-2">
                                <select class="form-select form-select-sm" id="categoryFilter">
                                    <option value=""><?php echo Localization::translate('course_subcategories.all_categories'); ?></option>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo ($categoryFilter ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <!-- Sort Order Filter -->
                            <div class="col-md-2">
                                <select class="form-select form-select-sm" id="sortOrderFilter">
                                    <option value=""><?php echo Localization::translate('course_subcategories.all_sort_orders'); ?></option>
                                    <option value="asc" selected><?php echo Localization::translate('course_subcategories.sort_asc'); ?></option>
                                    <option value="desc"><?php echo Localization::translate('course_subcategories.sort_desc'); ?></option>
                                </select>
                            </div>
                            <!-- Clear Filters Button -->
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100 btn-sm" id="clearFiltersBtn" 
                                        title="<?php echo Localization::translate('course_subcategories.clear_filters'); ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <!-- Import Subcategories Button -->
                            <div class="col-md-2 text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="importSubcategoriesBtn" 
                                        title="<?php echo Localization::translate('course_subcategories.import_subcategories_tooltip'); ?>">
                                    <i class="fas fa-upload me-1"></i><?php echo Localization::translate('course_subcategories.import_subcategories'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Results Info -->
        <div class="row mb-3">
            <div class="col-12">
                <div id="searchResultsInfo" class="search-results-info" style="display: none;">
                    <i class="fas fa-info-circle"></i>
                    <span id="resultsText"></span>
                </div>
            </div>
        </div>

        <!-- Subcategories Table -->
        <div id="subcategoriesContainer">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="subcategoriesTable">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo Localization::translate('course_subcategories.name'); ?></th>
                            <th><?php echo Localization::translate('course_subcategories.category'); ?></th>
                            <th><?php echo Localization::translate('course_subcategories.description'); ?></th>
                            <th><?php echo Localization::translate('course_subcategories.sort_order'); ?></th>
                            <th><?php echo Localization::translate('course_subcategories.status'); ?></th>
                            <th><?php echo Localization::translate('course_subcategories.actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="subcategoriesTableBody">
                        <!-- Content will be loaded dynamically via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="text-center" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2"><?php echo Localization::translate('course_subcategories.loading_subcategories'); ?></p>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer" class="pagination-container">
            <nav>
                <ul class="pagination justify-content-center" id="paginationList">
                    <!-- Pagination will be generated dynamically -->
                </ul>
            </nav>
        </div>

        <!-- No Results Message -->
        <div id="noResultsMessage" class="text-center" style="display: none;">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo Localization::translate('course_subcategories.no_subcategories_found'); ?>
            </div>
            <p class="text-muted"><?php echo Localization::translate('course_subcategories.no_subcategories_message'); ?></p>
            <button type="button" class="btn theme-btn-primary" id="addFirstSubcategoryBtn">
                <i class="fas fa-plus me-2"></i><?php echo Localization::translate('course_subcategories.add_first_subcategory'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Add/Edit Subcategory Modal -->
<div class="modal fade" id="subcategoryModal" tabindex="-1" aria-labelledby="subcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal content will be loaded dynamically -->
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content theme-modal">
            <div class="modal-header theme-modal-header">
                <h5 class="modal-title" id="confirmationModalLabel">
                    <i class="fas fa-exclamation-triangle me-2" style="color: #ffc107;"></i>
                    <?php echo Localization::translate('common.confirm'); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body theme-modal-body">
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-question-circle fa-2x me-3" style="color: #6a0dad;" id="confirmationIcon"></i>
                    </div>
                    <div class="flex-grow-1">
                        <p class="mb-2 theme-modal-message" id="confirmationMessage">
                            <?php echo Localization::translate('course_subcategories.confirm_delete'); ?>
                        </p>
                        <small class="theme-modal-subtext" id="confirmationSubtext">
                            <?php echo Localization::translate('course_subcategories.confirm_delete_subtext'); ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="modal-footer theme-modal-footer">
                <button type="button" class="btn theme-btn-primary" id="confirmActionBtn">
                    <?php echo Localization::translate('common.confirm'); ?>
                </button>
                <button type="button" class="btn theme-btn-danger" data-bs-dismiss="modal">
                    <?php echo Localization::translate('common.cancel'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="<?= UrlHelper::url('public/bootstrap/js/jquery.min.js') ?>"></script>
<script src="<?= UrlHelper::url('public/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<!-- Toast Notifications JS -->
<script src="<?= UrlHelper::url('public/js/toast_notifications.js') ?>"></script>
<!-- Course Subcategories JS -->
<script src="<?= UrlHelper::url('public/js/course_subcategories.js') ?>"></script>
<!-- Course Subcategories Validation JS -->
<script src="<?= UrlHelper::url('public/js/course_subcategory_validation.js') ?>"></script>

<script>
    // Set up base URLs for AJAX and navigation
    window.courseSubcategoriesRoutes = {
        index: '<?php echo UrlHelper::url('/course-subcategories'); ?>',
        ajaxSearch: '<?php echo UrlHelper::url('/course-subcategories/ajax/search'); ?>',
        toggleStatus: '<?php echo UrlHelper::url('/course-subcategories/ajax/toggle-status'); ?>',
        submit: '<?php echo UrlHelper::url('/course-subcategories/submit'); ?>',
        create: '<?php echo UrlHelper::url('/course-subcategories/create'); ?>',
        edit: function(id) { return '<?php echo UrlHelper::url('/course-subcategories'); ?>/' + id + '/edit'; },
        delete: function(id) { return '<?php echo UrlHelper::url('/course-subcategories'); ?>/' + id + '/delete'; },
        get: function(id) { return '<?php echo UrlHelper::url('/course-subcategories'); ?>/' + id; },
        dropdown: '<?php echo UrlHelper::url('/api/course-subcategories/dropdown'); ?>',
        modalAdd: '<?php echo UrlHelper::url('/course-subcategories/modal/add'); ?>',
        modalEdit: '<?php echo UrlHelper::url('/course-subcategories/modal/edit'); ?>'
    };
    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        loadSubcategories(1);
        setupEventListeners();
    });
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html> 