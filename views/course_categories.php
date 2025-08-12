<?php
// Check if user is logged in
if (!isset($_SESSION['user']['client_id'])) {
    header('Location: index.php?controller=LoginController');
    exit;
}

require_once 'core/UrlHelper.php';
require_once 'config/Localization.php';

// Localization is already initialized in index.php and header.php
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container mt-4 course-categories" data-course-categories-page="true" 
         data-client-id="<?php echo isset($currentUser['system_role']) && $currentUser['system_role'] === 'super_admin' && isset($_GET['client_id']) ? intval($_GET['client_id']) : ($currentUser['client_id'] ?? 1); ?>">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="<?= UrlHelper::url('manage-portal') ?>" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-layer-group me-2"></i>
                <?php echo Localization::translate('course_categories.title'); ?>
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
                <li class="breadcrumb-item active" aria-current="page"><?php echo Localization::translate('course_categories.title'); ?></li>
            </ol>
        </nav>

        <!-- Page Description -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0"><?php echo Localization::translate('course_categories.subtitle'); ?></p>
                    </div>
                    <button type="button" class="btn theme-btn-primary" id="addCategoryBtn" data-action="add-category"
                            title="<?php echo Localization::translate('course_categories.add_category_tooltip'); ?>">
                        <i class="fas fa-plus me-2"></i><?php echo Localization::translate('course_categories.add_category'); ?>
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
                                           placeholder="<?php echo Localization::translate('course_categories.search_placeholder'); ?>" 
                                           title="<?php echo Localization::translate('course_categories.search'); ?>"
                                           value="<?php echo htmlspecialchars($searchTerm ?? ''); ?>">
                                    <button type="button" id="searchButton" class="btn btn-outline-secondary" 
                                            title="<?php echo Localization::translate('course_categories.search'); ?>">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Status Filter -->
                            <div class="col-md-2">
                                <select class="form-select form-select-sm" id="statusFilter">
                                    <option value=""><?php echo Localization::translate('course_categories.all_statuses'); ?></option>
                                    <option value="active" <?php echo ($statusFilter ?? '') === 'active' ? 'selected' : ''; ?>>
                                        <?php echo Localization::translate('course_categories.active'); ?>
                                    </option>
                                    <option value="inactive" <?php echo ($statusFilter ?? '') === 'inactive' ? 'selected' : ''; ?>>
                                        <?php echo Localization::translate('course_categories.inactive'); ?>
                                    </option>
                                </select>
                            </div>
                            <!-- Sort Order Filter -->
                            <div class="col-md-2">
                                <select class="form-select form-select-sm" id="sortOrderFilter">
                                    <option value=""><?php echo Localization::translate('course_categories.all_sort_orders'); ?></option>
                                    <option value="asc"><?php echo Localization::translate('course_categories.sort_asc'); ?></option>
                                    <option value="desc"><?php echo Localization::translate('course_categories.sort_desc'); ?></option>
                                </select>
                            </div>
                            <!-- Subcategory Count Filter -->
                            <div class="col-md-2">
                                <select class="form-select form-select-sm" id="subcategoryFilter">
                                    <option value=""><?php echo Localization::translate('course_categories.all_subcategories'); ?></option>
                                    <option value="0"><?php echo Localization::translate('course_categories.no_subcategories'); ?></option>
                                    <option value="1+"><?php echo Localization::translate('course_categories.has_subcategories'); ?></option>
                                </select>
                            </div>
                            <!-- Clear Filters Button -->
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100 btn-sm" id="clearFiltersBtn" 
                                        title="<?php echo Localization::translate('course_categories.clear_filters'); ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <!-- Import Categories Button -->
                            <div class="col-md-2 text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="importCategoriesBtn" 
                                        title="<?php echo Localization::translate('course_categories.import_categories_tooltip'); ?>">
                                    <i class="fas fa-upload me-1"></i><?php echo Localization::translate('course_categories.import_categories'); ?>
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

        <!-- Categories Table -->
        <div id="categoriesContainer">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="categoriesTable">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo Localization::translate('course_categories.name'); ?></th>
                            <th><?php echo Localization::translate('course_categories.description'); ?></th>
                            <th><?php echo Localization::translate('course_categories.sort_order'); ?></th>
                            <th><?php echo Localization::translate('course_categories.subcategories'); ?></th>
                            <th><?php echo Localization::translate('course_categories.status'); ?></th>
                            <th><?php echo Localization::translate('course_categories.actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="categoriesTableBody">
                        <!-- Content will be loaded dynamically via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div class="row" id="loadingIndicator" style="display: none;">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading categories...</p>
            </div>
        </div>

        <!-- Pagination -->
        <div class="row">
            <div class="col-12">
                <div id="paginationContainer">
                    <!-- Pagination will be loaded dynamically via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals Container -->
<div id="modalContainer"></div>

<!-- Dynamic Category Modal (Add/Edit) -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">
                    <i class="fas fa-plus me-2" id="modalIcon"></i>
                    <span id="modalTitle"><?php echo Localization::translate('course_categories.add_new_category'); ?></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="categoryForm">
                <input type="hidden" id="categoryId" name="category_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="categoryName" class="form-label">
                                    <?php echo Localization::translate('course_categories.name'); ?>
                                </label>
                                <input type="text" class="form-control" id="categoryName" name="name" 
                                       placeholder="<?php echo Localization::translate('course_categories.enter_category_name'); ?>" 
                                       maxlength="100">
                                <div class="invalid-feedback" id="nameError"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="categorySortOrder" class="form-label">
                                    <?php echo Localization::translate('course_categories.sort_order'); ?>
                                </label>
                                <input type="number" class="form-control" id="categorySortOrder" name="sort_order" 
                                       value="0" min="0">
                                <div class="form-text"><?php echo Localization::translate('course_categories.sort_order_help'); ?></div>
                                <div class="invalid-feedback" id="sortOrderError"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="categoryDescription" class="form-label">
                            <?php echo Localization::translate('course_categories.description'); ?>
                        </label>
                        <textarea class="form-control" id="categoryDescription" name="description" rows="4" 
                                  placeholder="<?php echo Localization::translate('course_categories.enter_description'); ?>" 
                                  maxlength="500"></textarea>
                        <div class="form-text">
                            <span id="charCount">0</span> / 500 <?php echo Localization::translate('course_categories.characters'); ?>
                        </div>
                        <div class="invalid-feedback" id="descriptionError"></div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="categoryActive" name="is_active" checked>
                            <label class="form-check-label" for="categoryActive">
                                <?php echo Localization::translate('course_categories.active_category'); ?>
                            </label>
                        </div>
                        <div class="form-text"><?php echo Localization::translate('course_categories.active_help'); ?></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?php echo Localization::translate('common.cancel'); ?>
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save me-2"></i>
                        <span id="submitBtnText"><?php echo Localization::translate('course_categories.create_category'); ?></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmationModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="confirmationModalBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?php echo Localization::translate('common.cancel'); ?>
                </button>
                <button type="button" class="btn btn-danger" id="confirmActionBtn">
                    <?php echo Localization::translate('common.confirm'); ?>
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
<!-- Course Categories JS -->
<script src="<?= UrlHelper::url('public/js/course_categories.js') ?>"></script>
<!-- Course Categories Validation JS -->
<script src="<?= UrlHelper::url('public/js/course_category_validation.js') ?>"></script>

<?php include 'includes/footer.php'; ?>
</body>
</html> 