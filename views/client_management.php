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
        <div id="searchResultsInfo" class="search-results-info">
            <i class="fas fa-info-circle"></i>
            <span id="resultsText"></span>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="text-center loading-indicator">
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
                                            <strong><?= Localization::translate('clients_client_code'); ?>:</strong>
                                            <span class="text-muted"><code><?= htmlspecialchars($client['client_code']); ?></code></span>
                                        </div>

                                        <div class="info-item">
                                            <strong><?= Localization::translate('clients_users'); ?>:</strong>
                                            <span class="text-muted">
                                                <?= $client['active_users']; ?> / <?= $client['max_users']; ?>
                                            </span>
                                            <div class="progress mt-1 progress-bar-thin">
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
                                <label for="client_code" class="form-label"><?= Localization::translate('clients_client_code_required'); ?></label>
                                <input type="text" class="form-control client-code-input" id="client_code" name="client_code" placeholder="<?= Localization::translate('clients_client_code_placeholder'); ?>">
                                <div class="form-text"><?= Localization::translate('clients_client_code_help'); ?></div>
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

                            <div class="mb-3">
                                <label for="custom_field_creation" class="form-label"><?= Localization::translate('clients_custom_field_creation'); ?></label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="custom_field_creation" name="custom_field_creation" value="1" checked>
                                    <label class="form-check-label" for="custom_field_creation">
                                        <?= Localization::translate('clients_custom_field_creation_help'); ?>
                                    </label>
                                </div>
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
                                <label for="edit_client_code" class="form-label"><?= Localization::translate('clients_client_code_required'); ?></label>
                                <input type="text" class="form-control client-code-input" id="edit_client_code" name="client_code" placeholder="<?= Localization::translate('clients_client_code_placeholder'); ?>">
                                <div class="form-text"><?= Localization::translate('clients_client_code_help'); ?></div>
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

                            <div class="mb-3">
                                <label for="edit_custom_field_creation" class="form-label"><?= Localization::translate('clients_custom_field_creation'); ?></label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="edit_custom_field_creation" name="custom_field_creation" value="1">
                                    <label class="form-check-label" for="edit_custom_field_creation">
                                        <?= Localization::translate('clients_custom_field_creation_help'); ?>
                                    </label>
                                </div>
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
                                        <div id="current_logo_preview" class="current-logo-preview">
                                            <small class="text-muted"><?= Localization::translate('clients_current_logo'); ?></small><br>
                                            <img id="current_logo_img" src="" alt="Current Logo" class="current-logo-img">
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
    'js.validation.client_code_required' => Localization::translate('js.validation.client_code_required'),
    'js.validation.client_code_format' => Localization::translate('js.validation.client_code_format'),
    'clients_create_client' => Localization::translate('clients_create_client'),
    'clients_update_client' => Localization::translate('clients_update_client')
]); ?>;
</script>
<!-- Include Client Form Validation JavaScript -->
<script src="public/js/client_form_validation.js"></script>
<!-- Include Client Management JavaScript -->
<script src="public/js/client_management.js"></script>







<?php include 'includes/footer.php'; ?>
