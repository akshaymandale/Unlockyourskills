<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container mt-4 client-management">

        <!-- Page Header -->
        <h1 class="page-title text-purple"><?= Localization::translate('client_management_title'); ?></h1>

        <!-- ✅ Filters & Search Section -->
        <div class="filter-section">
            <div class="container-fluid mb-3">
                <!-- Single Row: All Controls -->
                <div class="row justify-content-between align-items-center g-3">

                    <!-- Status Filter -->
                    <div class="col-auto">
                        <select class="form-select form-select-sm" id="statusFilter">
                            <option value=""><?= Localization::translate('clients_all_statuses'); ?></option>
                            <option value="active" <?= ($filters['status'] === 'active') ? 'selected' : ''; ?>><?= Localization::translate('clients_active'); ?></option>
                            <option value="inactive" <?= ($filters['status'] === 'inactive') ? 'selected' : ''; ?>><?= Localization::translate('clients_inactive'); ?></option>
                            <option value="suspended" <?= ($filters['status'] === 'suspended') ? 'selected' : ''; ?>><?= Localization::translate('clients_suspended'); ?></option>
                        </select>
                    </div>

                    <!-- Search Bar -->
                    <div class="col-auto">
                        <div class="input-group input-group-sm">
                            <input type="text" id="searchInput" class="form-control"
                                value="<?= htmlspecialchars($search); ?>"
                                placeholder="<?= Localization::translate('clients_search_placeholder'); ?>"
                                title="<?= Localization::translate('clients_search_title'); ?>">
                            <button type="button" id="searchButton" class="btn btn-outline-secondary"
                                title="<?= Localization::translate('clients_search_title'); ?>">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Clear Filters Button -->
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm btn-clear-filters" id="clearFiltersBtn"
                            title="<?= Localization::translate('clients_clear_filters_title'); ?>">
                            <i class="fas fa-times me-1"></i> <?= Localization::translate('clients_clear_filters'); ?>
                        </button>
                    </div>

                    <!-- Add Client Button -->
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#addClientModal"
                            title="<?= Localization::translate('clients_add_client_title'); ?>">
                            <i class="fas fa-plus me-1"></i> <?= Localization::translate('clients_add_client'); ?>
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- Search Results Info -->
        <div id="searchResultsInfo" class="search-results-info" style="display: none;">
            <i class="fas fa-info-circle"></i>
            <span id="resultsText"></span>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="text-center" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2"><?= Localization::translate('clients_loading'); ?></p>
        </div>

            <!-- Clients Grid -->
            <div class="row">
                <?php if (empty($clients)): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            <?= Localization::translate('clients_no_clients_found'); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($clients as $client): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 client-card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($client['logo_path'])): ?>
                                            <img src="<?= htmlspecialchars($client['logo_path']); ?>" 
                                                 alt="<?= htmlspecialchars($client['client_name']); ?>" 
                                                 class="client-logo me-2">
                                        <?php else: ?>
                                            <div class="client-logo-placeholder me-2">
                                                <i class="fas fa-building"></i>
                                            </div>
                                        <?php endif; ?>
                                        <h6 class="mb-0"><?= htmlspecialchars($client['client_name']); ?></h6>
                                    </div>
                                    <span class="badge bg-<?= $client['status'] === 'active' ? 'success' : ($client['status'] === 'suspended' ? 'danger' : 'secondary'); ?>">
                                        <?= ucfirst($client['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="card-body">
                                    <div class="client-info">
                                        <div class="info-item">
                                            <strong><?= Localization::translate('clients_client_id'); ?>:</strong>
                                            <span class="text-muted"><?= $client['id']; ?></span>
                                        </div>

                                        <div class="info-item">
                                            <strong><?= Localization::translate('clients_users'); ?>:</strong>
                                            <span class="text-muted">
                                                <?= $client['active_users']; ?> / <?= $client['max_users']; ?>
                                            </span>
                                            <div class="progress mt-1" style="height: 4px;">
                                                <div class="progress-bar" role="progressbar"
                                                     style="width: <?= $client['max_users'] > 0 ? ($client['active_users'] / $client['max_users'] * 100) : 0; ?>%">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <strong><?= Localization::translate('clients_admin_limit'); ?>:</strong>
                                            <span class="text-muted"><?= $client['admin_role_limit'] ?? 5; ?> <?= Localization::translate('clients_roles'); ?></span>
                                        </div>

                                        <div class="info-item">
                                            <strong><?= Localization::translate('clients_features'); ?>:</strong>
                                            <div class="feature-badges mt-1">
                                                <?php if (($client['reports_enabled'] ?? 1) == 1): ?>
                                                    <span class="badge bg-success me-1"><?= Localization::translate('clients_reports'); ?></span>
                                                <?php endif; ?>
                                                <?php if (($client['theme_settings'] ?? 1) == 1): ?>
                                                    <span class="badge bg-info me-1"><?= Localization::translate('clients_themes'); ?></span>
                                                <?php endif; ?>
                                                <?php if (($client['sso_enabled'] ?? 0) == 1): ?>
                                                    <span class="badge bg-warning me-1"><?= Localization::translate('clients_sso'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <strong><?= Localization::translate('clients_created'); ?>:</strong>
                                            <span class="text-muted"><?= date('M j, Y', strtotime($client['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-sm theme-btn-secondary edit-client-btn"
                                                data-client-id="<?= $client['id']; ?>"
                                                title="<?= Localization::translate('clients_edit_title'); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="index.php?controller=UserManagementController&client_id=<?= $client['id']; ?>"
                                           class="btn btn-sm btn-outline-primary" title="<?= Localization::translate('clients_manage_users_title'); ?>">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <a href="index.php?controller=ClientController&action=stats&id=<?= $client['id']; ?>"
                                           class="btn btn-sm btn-outline-info" title="<?= Localization::translate('clients_statistics_title'); ?>">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                        <?php if ($client['id'] != 1): // Don't show delete for Super Admin client ?>
                                            <a href="#" class="btn btn-sm theme-btn-danger delete-client"
                                               data-id="<?= $client['id']; ?>"
                                               data-name="<?= htmlspecialchars($client['client_name']); ?>"
                                               title="<?= Localization::translate('clients_delete_title'); ?>">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="<?= Localization::translate('clients_pagination_label'); ?>">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="index.php?controller=ClientController&page=<?= $i; ?>&search=<?= urlencode($search); ?>&status=<?= urlencode($filters['status']); ?>">
                                    <?= $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
    </div>
</div>

<!-- Add Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addClientModalLabel">
                    <i class="fas fa-building me-2"></i><?= Localization::translate('clients_add_modal_title'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= Localization::translate('clients_close'); ?>"></button>
            </div>
            <form id="addClientForm" method="POST" action="index.php?controller=ClientController&action=store" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <h6 class="text-purple mb-3"><?= Localization::translate('clients_basic_information'); ?></h6>

                            <div class="mb-3">
                                <label for="client_name" class="form-label"><?= Localization::translate('clients_client_name_required'); ?></label>
                                <input type="text" class="form-control" id="client_name" name="client_name">
                            </div>

                            <div class="mb-3">
                                <label for="max_users" class="form-label"><?= Localization::translate('clients_maximum_users_required'); ?></label>
                                <input type="text" class="form-control" id="max_users" name="max_users" placeholder="<?= Localization::translate('clients_maximum_users_placeholder'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label"><?= Localization::translate('clients_status'); ?></label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" selected><?= Localization::translate('clients_active'); ?></option>
                                    <option value="inactive"><?= Localization::translate('clients_inactive'); ?></option>
                                    <option value="suspended"><?= Localization::translate('clients_suspended'); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Configuration Settings -->
                        <div class="col-md-6">
                            <h6 class="text-purple mb-3"><?= Localization::translate('clients_configuration_settings'); ?></h6>

                            <div class="mb-3">
                                <label for="reports_enabled" class="form-label"><?= Localization::translate('clients_reports_enabled'); ?></label>
                                <select class="form-select" id="reports_enabled" name="reports_enabled">
                                    <option value="1" selected><?= Localization::translate('clients_yes'); ?></option>
                                    <option value="0"><?= Localization::translate('clients_no'); ?></option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="theme_settings" class="form-label"><?= Localization::translate('clients_theme_color_setting'); ?></label>
                                <select class="form-select" id="theme_settings" name="theme_settings">
                                    <option value="1" selected><?= Localization::translate('clients_yes'); ?></option>
                                    <option value="0"><?= Localization::translate('clients_no'); ?></option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="sso_enabled" class="form-label"><?= Localization::translate('clients_sso_login'); ?></label>
                                <select class="form-select" id="sso_enabled" name="sso_enabled">
                                    <option value="0" selected><?= Localization::translate('clients_no'); ?></option>
                                    <option value="1"><?= Localization::translate('clients_yes'); ?></option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="admin_role_limit" class="form-label"><?= Localization::translate('clients_admin_role_limit_required'); ?></label>
                                <input type="text" class="form-control" id="admin_role_limit" name="admin_role_limit"
                                       value="1" placeholder="<?= Localization::translate('clients_admin_role_limit_placeholder'); ?>">
                                <div class="form-text"><?= Localization::translate('clients_admin_role_limit_help'); ?></div>
                            </div>
                        </div>

                        <!-- Branding -->
                        <div class="col-12 mt-3">
                            <h6 class="text-purple mb-3"><?= Localization::translate('clients_branding'); ?></h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="logo" class="form-label"><?= Localization::translate('clients_client_logo_required'); ?></label>
                                        <input type="file" class="form-control" id="logo" name="logo" accept="image/png,image/jpeg,image/gif">
                                        <div class="form-text"><?= Localization::translate('clients_logo_help'); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="description" class="form-label"><?= Localization::translate('clients_description'); ?></label>
                                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="<?= Localization::translate('clients_description_placeholder'); ?>"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i><?= Localization::translate('clients_cancel'); ?>
                    </button>
                    <button type="submit" class="btn theme-btn-primary">
                        <?= Localization::translate('clients_create_client'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Client Modal -->
<div class="modal fade" id="editClientModal" tabindex="-1" aria-labelledby="editClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editClientModalLabel">
                    <?= Localization::translate('clients_edit_modal_title'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= Localization::translate('clients_close'); ?>"></button>
            </div>
            <form id="editClientForm" method="POST" action="index.php?controller=ClientController&action=update" enctype="multipart/form-data">
                <input type="hidden" id="edit_client_id" name="client_id">
                <div class="modal-body">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <h6 class="text-purple mb-3"><?= Localization::translate('clients_basic_information'); ?></h6>

                            <div class="mb-3">
                                <label for="edit_client_name" class="form-label"><?= Localization::translate('clients_client_name_required'); ?></label>
                                <input type="text" class="form-control" id="edit_client_name" name="client_name">
                            </div>

                            <div class="mb-3">
                                <label for="edit_max_users" class="form-label"><?= Localization::translate('clients_maximum_users_required'); ?></label>
                                <input type="text" class="form-control" id="edit_max_users" name="max_users" placeholder="<?= Localization::translate('clients_maximum_users_placeholder'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="edit_status" class="form-label"><?= Localization::translate('clients_status'); ?></label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active"><?= Localization::translate('clients_active'); ?></option>
                                    <option value="inactive"><?= Localization::translate('clients_inactive'); ?></option>
                                    <option value="suspended"><?= Localization::translate('clients_suspended'); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Configuration Settings -->
                        <div class="col-md-6">
                            <h6 class="text-purple mb-3"><?= Localization::translate('clients_configuration_settings'); ?></h6>

                            <div class="mb-3">
                                <label for="edit_reports_enabled" class="form-label"><?= Localization::translate('clients_reports_enabled'); ?></label>
                                <select class="form-select" id="edit_reports_enabled" name="reports_enabled">
                                    <option value="1"><?= Localization::translate('clients_yes'); ?></option>
                                    <option value="0"><?= Localization::translate('clients_no'); ?></option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="edit_theme_settings" class="form-label"><?= Localization::translate('clients_theme_color_setting'); ?></label>
                                <select class="form-select" id="edit_theme_settings" name="theme_settings">
                                    <option value="1"><?= Localization::translate('clients_yes'); ?></option>
                                    <option value="0"><?= Localization::translate('clients_no'); ?></option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="edit_sso_enabled" class="form-label"><?= Localization::translate('clients_sso_login'); ?></label>
                                <select class="form-select" id="edit_sso_enabled" name="sso_enabled">
                                    <option value="0"><?= Localization::translate('clients_no'); ?></option>
                                    <option value="1"><?= Localization::translate('clients_yes'); ?></option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="edit_admin_role_limit" class="form-label"><?= Localization::translate('clients_admin_role_limit_required'); ?></label>
                                <input type="text" class="form-control" id="edit_admin_role_limit" name="admin_role_limit"
                                       placeholder="<?= Localization::translate('clients_admin_role_limit_placeholder'); ?>">
                                <div class="form-text"><?= Localization::translate('clients_admin_role_limit_help'); ?></div>
                            </div>
                        </div>

                        <!-- Branding -->
                        <div class="col-12 mt-3">
                            <h6 class="text-purple mb-3"><?= Localization::translate('clients_branding'); ?></h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_logo" class="form-label"><?= Localization::translate('clients_client_logo'); ?></label>
                                        <input type="file" class="form-control" id="edit_logo" name="logo" accept="image/png,image/jpeg,image/gif">
                                        <div class="form-text"><?= Localization::translate('clients_logo_help_edit'); ?></div>
                                        <div id="current_logo_preview" class="mt-2" style="display: none;">
                                            <small class="text-muted"><?= Localization::translate('clients_current_logo'); ?></small><br>
                                            <img id="current_logo_img" src="" alt="Current Logo" style="max-width: 100px; max-height: 50px; border: 1px solid #ddd; border-radius: 4px;">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_description" class="form-label"><?= Localization::translate('clients_description'); ?></label>
                                        <textarea class="form-control" id="edit_description" name="description" rows="3" placeholder="<?= Localization::translate('clients_description_placeholder'); ?>"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i><?= Localization::translate('clients_cancel'); ?>
                    </button>
                    <button type="submit" class="btn theme-btn-primary">
                        <?= Localization::translate('clients_update_client'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include Toast Notification JavaScript -->
<script src="public/js/toast_notifications.js"></script>
<!-- Include Confirmation Modal JavaScript -->
<script src="public/js/confirmation_modal.js"></script>
<!-- Include Translation System -->
<script src="public/js/translations.js"></script>
<script>
// Load translations for JavaScript validation
window.translations = <?= json_encode([
    'js.validation.client_name_required' => Localization::translate('js.validation.client_name_required'),
    'js.validation.max_users_required' => Localization::translate('js.validation.max_users_required'),
    'js.validation.max_users_numeric' => Localization::translate('js.validation.max_users_numeric'),
    'js.validation.max_users_minimum' => Localization::translate('js.validation.max_users_minimum'),
    'js.validation.admin_role_limit_required' => Localization::translate('js.validation.admin_role_limit_required'),
    'js.validation.admin_role_limit_numeric' => Localization::translate('js.validation.admin_role_limit_numeric'),
    'js.validation.admin_role_limit_minimum' => Localization::translate('js.validation.admin_role_limit_minimum'),
    'js.validation.client_logo_required' => Localization::translate('js.validation.client_logo_required'),
    'js.validation.logo_format_invalid' => Localization::translate('js.validation.logo_format_invalid'),
    'js.validation.logo_size_exceeded' => Localization::translate('js.validation.logo_size_exceeded'),
    'js.validation.client_form_not_found' => Localization::translate('js.validation.client_form_not_found'),
    'clients_create_client' => Localization::translate('clients_create_client'),
    'clients_update_client' => Localization::translate('clients_update_client')
]); ?>;
</script>
<!-- Include Client Validation JavaScript -->
<script src="public/js/client_validation.js"></script>

<!-- Client Management JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize client validation

    // Helper functions for validation
    function isNumeric(value) {
        return !isNaN(value) && !isNaN(parseFloat(value));
    }

    function showFieldError(field, message) {
        field.classList.add('is-invalid');

        // Find or create error message element
        let errorElement = field.parentNode.querySelector('.error-message');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message text-danger small mt-1';
            field.parentNode.appendChild(errorElement);
        }

        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }

    function hideFieldError(field) {
        field.classList.remove('is-invalid');

        const errorElement = field.parentNode.querySelector('.error-message');
        if (errorElement) {
            errorElement.style.display = 'none';
            errorElement.textContent = '';
        }
    }

    // Attach comprehensive validation to add form
    const addForm = document.getElementById('addClientForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();

            let isValid = true;

            // Validate client name
            const clientName = document.getElementById('client_name');
            if (!clientName.value.trim()) {
                showFieldError(clientName, window.translations['js.validation.client_name_required'] || 'Client name is required.');
                isValid = false;
            } else {
                hideFieldError(clientName);
            }

            // Validate max users
            const maxUsers = document.getElementById('max_users');
            if (!maxUsers.value.trim()) {
                showFieldError(maxUsers, window.translations['js.validation.max_users_required'] || 'Maximum users is required.');
                isValid = false;
            } else if (!isNumeric(maxUsers.value)) {
                showFieldError(maxUsers, window.translations['js.validation.max_users_numeric'] || 'Maximum users must be a number.');
                isValid = false;
            } else if (parseInt(maxUsers.value) < 1) {
                showFieldError(maxUsers, window.translations['js.validation.max_users_minimum'] || 'Maximum users must be at least 1.');
                isValid = false;
            } else {
                hideFieldError(maxUsers);
            }

            // Validate admin role limit
            const adminRoleLimit = document.getElementById('admin_role_limit');
            if (!adminRoleLimit.value.trim()) {
                showFieldError(adminRoleLimit, window.translations['js.validation.admin_role_limit_required'] || 'Admin role limit is required.');
                isValid = false;
            } else if (!isNumeric(adminRoleLimit.value)) {
                showFieldError(adminRoleLimit, window.translations['js.validation.admin_role_limit_numeric'] || 'Admin role limit must be a number.');
                isValid = false;
            } else if (parseInt(adminRoleLimit.value) < 1) {
                showFieldError(adminRoleLimit, window.translations['js.validation.admin_role_limit_minimum'] || 'Admin role limit must be at least 1.');
                isValid = false;
            } else {
                hideFieldError(adminRoleLimit);
            }

            // Validate logo
            const logo = document.getElementById('logo');
            if (logo.files.length === 0) {
                showFieldError(logo, window.translations['js.validation.client_logo_required'] || 'Client logo is required.');
                isValid = false;
            } else {
                const file = logo.files[0];
                const allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
                const maxSize = 5 * 1024 * 1024; // 5MB

                if (!allowedTypes.includes(file.type)) {
                    showFieldError(logo, window.translations['js.validation.logo_format_invalid'] || 'Logo must be PNG, JPG, or GIF format.');
                    isValid = false;
                } else if (file.size > maxSize) {
                    showFieldError(logo, window.translations['js.validation.logo_size_exceeded'] || 'Logo file size must be less than 5MB.');
                    isValid = false;
                } else {
                    hideFieldError(logo);
                }
            }

            if (isValid) {
                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creating...';
                submitBtn.disabled = true;
                this.submit();
            }
        });

        // Add blur validation for real-time feedback
        const fields = addForm.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            field.addEventListener('blur', function() {
                validateSingleField(this);
            });
        });
    }

    // Single field validation function
    function validateSingleField(field) {
        const fieldName = field.name || field.id;
        const value = field.value.trim();

        switch (fieldName) {
            case 'client_name':
                if (!value) {
                    showFieldError(field, window.translations['js.validation.client_name_required'] || 'Client name is required.');
                } else {
                    hideFieldError(field);
                }
                break;

            case 'max_users':
                if (!value) {
                    showFieldError(field, window.translations['js.validation.max_users_required'] || 'Maximum users is required.');
                } else if (!isNumeric(value)) {
                    showFieldError(field, window.translations['js.validation.max_users_numeric'] || 'Maximum users must be a number.');
                } else if (parseInt(value) < 1) {
                    showFieldError(field, window.translations['js.validation.max_users_minimum'] || 'Maximum users must be at least 1.');
                } else {
                    hideFieldError(field);
                }
                break;

            case 'admin_role_limit':
                if (!value) {
                    showFieldError(field, window.translations['js.validation.admin_role_limit_required'] || 'Admin role limit is required.');
                } else if (!isNumeric(value)) {
                    showFieldError(field, window.translations['js.validation.admin_role_limit_numeric'] || 'Admin role limit must be a number.');
                } else if (parseInt(value) < 1) {
                    showFieldError(field, window.translations['js.validation.admin_role_limit_minimum'] || 'Admin role limit must be at least 1.');
                } else {
                    hideFieldError(field);
                }
                break;

            case 'logo':
                if (field.files && field.files.length > 0) {
                    const file = field.files[0];
                    const allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
                    const maxSize = 5 * 1024 * 1024; // 5MB

                    if (!allowedTypes.includes(file.type)) {
                        showFieldError(field, window.translations['js.validation.logo_format_invalid'] || 'Logo must be PNG, JPG, or GIF format.');
                    } else if (file.size > maxSize) {
                        showFieldError(field, window.translations['js.validation.logo_size_exceeded'] || 'Logo file size must be less than 5MB.');
                    } else {
                        hideFieldError(field);
                    }
                }
                break;
        }
    }

    // Check if validation functions are available for edit form
    if (typeof window.attachClientValidation === 'function') {
        // Attach validation to edit form
        if (document.getElementById('editClientForm')) {
            window.attachClientValidation('editClientForm');
        }
    }

    // Reset add form when modal closes
    $('#addClientModal').on('hidden.bs.modal', function() {
        const form = document.getElementById('addClientForm');
        if (form) {
            form.reset();

            // Remove validation errors
            form.querySelectorAll('.error-message').forEach(el => {
                el.style.display = 'none';
                el.textContent = '';
            });
            form.querySelectorAll('.is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });

            // Reset submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = window.translations['clients_create_client'] || 'Create Client';
                submitBtn.disabled = false;
            }
        }
    });
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const statusFilter = document.getElementById('statusFilter');
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');

    // Search on button click
    searchButton.addEventListener('click', function() {
        performSearch();
    });

    // Search on Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });

    // Filter on status change
    statusFilter.addEventListener('change', function() {
        performSearch();
    });

    // Clear filters
    clearFiltersBtn.addEventListener('click', function() {
        searchInput.value = '';
        statusFilter.value = '';
        window.location.href = 'index.php?controller=ClientController';
    });

    function performSearch() {
        const search = searchInput.value.trim();
        const status = statusFilter.value;

        let url = 'index.php?controller=ClientController';
        const params = [];

        if (search) params.push('search=' + encodeURIComponent(search));
        if (status) params.push('status=' + encodeURIComponent(status));

        if (params.length > 0) {
            url += '&' + params.join('&');
        }

        window.location.href = url;
    }

    // Edit Client Modal Functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-client-btn')) {
            const button = e.target.closest('.edit-client-btn');
            const clientId = button.getAttribute('data-client-id');

            // Show loading state
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

            // Fetch client data
            fetch(`index.php?controller=ClientController&action=edit&id=${clientId}&ajax=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateEditModal(data.client);
                        $('#editClientModal').modal('show');
                    } else {
                        alert('Error: ' + (data.error || 'Failed to load client data'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load client data. Please try again.');
                })
                .finally(() => {
                    // Reset button state
                    button.innerHTML = '<i class="fas fa-edit"></i>';
                    button.disabled = false;
                });
        }
    });

    function populateEditModal(client) {
        // Populate basic information
        document.getElementById('edit_client_id').value = client.id;
        document.getElementById('edit_client_name').value = client.client_name || '';
        document.getElementById('edit_max_users').value = client.max_users || '';
        document.getElementById('edit_status').value = client.status || 'active';

        // Populate configuration settings
        document.getElementById('edit_reports_enabled').value = client.reports_enabled || '1';
        document.getElementById('edit_theme_settings').value = client.theme_settings || '1';
        document.getElementById('edit_sso_enabled').value = client.sso_enabled || '0';
        document.getElementById('edit_admin_role_limit').value = client.admin_role_limit || '1';

        // Populate description
        document.getElementById('edit_description').value = client.description || '';

        // Show current logo if exists
        const logoPreview = document.getElementById('current_logo_preview');
        const logoImg = document.getElementById('current_logo_img');
        if (client.logo_path) {
            logoImg.src = client.logo_path;
            logoPreview.style.display = 'block';
        } else {
            logoPreview.style.display = 'none';
        }
    }

    // Reset edit form when modal is closed
    $('#editClientModal').on('hidden.bs.modal', function() {
        const form = document.getElementById('editClientForm');
        if (form) {
            form.reset();
            document.getElementById('current_logo_preview').style.display = 'none';

            // Remove validation errors
            document.querySelectorAll('#editClientForm .error-message').forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });
            document.querySelectorAll('#editClientForm .is-invalid').forEach(el => {
                el.classList.remove('is-invalid');
            });

            // Reset submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<?= Localization::translate('clients_update_client'); ?>';
                submitBtn.disabled = false;
            }
        }
    });

    // Delete client confirmation using consistent modal
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-client')) {
            e.preventDefault();
            const element = e.target.closest('.delete-client');
            const id = element.dataset.id;
            const name = element.dataset.name;

            // Show loading state
            element.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            element.disabled = true;

            // First check if client can be deleted
            fetch(`index.php?controller=ClientController&action=canDelete&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        // Show error toast
                        if (typeof window.showSimpleToast === 'function') {
                            window.showSimpleToast(data.error, 'error');
                        } else {
                            alert('Error: ' + data.error);
                        }
                    } else if (!data.canDelete) {
                        // Show warning toast for cannot delete
                        if (typeof window.showSimpleToast === 'function') {
                            window.showSimpleToast(data.message, 'warning');
                        } else {
                            alert(data.message);
                        }
                    } else {
                        // Client can be deleted, show confirmation modal
                        if (typeof window.confirmDelete === 'function') {
                            window.confirmDelete(`client "${name}"`, function() {
                                window.location.href = `index.php?controller=ClientController&action=delete&id=${id}`;
                            });
                        } else {
                            // Fallback to browser confirm if modal system not available
                            if (confirm(`Are you sure you want to delete client "${name}"? This action is not reversible.`)) {
                                window.location.href = `index.php?controller=ClientController&action=delete&id=${id}`;
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking client delete status:', error);
                    if (typeof window.showSimpleToast === 'function') {
                        window.showSimpleToast('Failed to check client status. Please try again.', 'error');
                    } else {
                        alert('Failed to check client status. Please try again.');
                    }
                })
                .finally(() => {
                    // Reset button state
                    element.innerHTML = '<i class="fas fa-trash-alt"></i>';
                    element.disabled = false;
                });
        }
    });

    // Handle URL parameters for toast messages
    const urlParams = new URLSearchParams(window.location.search);

    // Handle toast messages from URL parameters
    if (urlParams.has('message') && urlParams.has('type')) {
        const message = decodeURIComponent(urlParams.get('message'));
        const type = urlParams.get('type');

        // Show toast notification
        if (typeof window.showSimpleToast === 'function') {
            window.showSimpleToast(message, type);
        } else {
            // Fallback to alert if toast system not ready
            alert(message);
        }

        // Clean URL by removing message parameters
        const cleanUrl = new URL(window.location);
        cleanUrl.searchParams.delete('message');
        cleanUrl.searchParams.delete('type');
        window.history.replaceState({}, document.title, cleanUrl.toString());
    }

    // Legacy support for old URL parameters (backward compatibility)
    if (urlParams.has('success')) {
        const success = urlParams.get('success');
        let message = '';
        switch(success) {
            case 'client_created':
                message = '<?= Localization::translate('success.client_created'); ?>';
                break;
            case 'client_updated':
                message = '<?= Localization::translate('success.client_updated'); ?>';
                break;
            case 'client_deleted':
                message = '<?= Localization::translate('success.client_deleted'); ?>';
                break;
        }
        if (message && typeof window.showSimpleToast === 'function') {
            window.showSimpleToast(message, 'success');
        }
    }

    if (urlParams.has('error')) {
        const error = decodeURIComponent(urlParams.get('error'));
        if (typeof window.showSimpleToast === 'function') {
            window.showSimpleToast(error, 'error');
        } else {
            alert('Error: ' + error);
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
