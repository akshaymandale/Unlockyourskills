<?php
// Check if user is logged in
if (!isset($_SESSION['user']['client_id'])) {
    header('Location: index.php?controller=LoginController');
    exit;
}

require_once 'core/UrlHelper.php';
require_once 'config/Localization.php';

$systemRole = $_SESSION['user']['system_role'] ?? '';
$canManageAll = in_array($systemRole, ['super_admin', 'admin']);

include 'includes/header.php';
include 'includes/navbar.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <div class="container mt-4 client-management">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="<?= UrlHelper::url('manage-portal') ?>" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-building me-2"></i>
                Client Management
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
                <li class="breadcrumb-item active" aria-current="page">Client Management</li>
            </ol>
        </nav>

        <!-- Page Description -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">Manage client organizations, settings, and configurations</p>
                    </div>
                    <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                        <i class="fas fa-plus me-2"></i>Add Client
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
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" id="searchInput" class="form-control"
                                        placeholder="<?= Localization::translate('clients_search_placeholder'); ?>"
                                        title="<?= Localization::translate('clients_search_title'); ?>">
                                </div>
                            </div>

                            <!-- Client Filter (for super admin) -->
                            <?php if (isset($_SESSION['user']) && $_SESSION['user']['system_role'] === 'super_admin'): ?>
                            <div class="col-md-3">
                                <select id="clientFilter" class="form-select">
                                    <option value=""><?= Localization::translate('all_clients'); ?></option>
                                    <?php foreach ($allClientsForFilter as $clientOption): ?>
                                        <option value="<?= $clientOption['id'] ?>">
                                            <?= htmlspecialchars($clientOption['client_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <!-- Status Filter -->
                            <div class="col-md-3">
                                <select id="statusFilter" class="form-select">
                                    <option value=""><?= Localization::translate('clients_all_statuses'); ?></option>
                                    <option value="active"><?= Localization::translate('clients_active'); ?></option>
                                    <option value="inactive"><?= Localization::translate('clients_inactive'); ?></option>
                                    <option value="suspended"><?= Localization::translate('clients_suspended'); ?></option>
                                </select>
                            </div>

                            <!-- Clear All Filters -->
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger w-100" id="clearFiltersBtn" title="<?= Localization::translate('clients_clear_filters_title'); ?>">
                                    <i class="fas fa-times me-1"></i>Clear
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
                    <span id="resultsInfo">Loading clients...</span>
                </div>
            </div>
        </div>

        <!-- Clients Grid -->
        <div id="clientsContainer">
            <div id="clientsGrid" class="row">
                <!-- Clients will be loaded here via AJAX -->
            </div>
        </div>

        <!-- Pagination -->
        <div class="row">
            <div class="col-12">
                <div id="paginationContainer">
                    <!-- Pagination will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div class="row" id="loadingIndicator" style="display: none;">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading clients...</p>
            </div>
        </div>
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
            <form id="addClientForm" method="POST" action="/clients" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <h6 class="text-purple mb-3"><?= Localization::translate('clients_basic_information'); ?></h6>

                            <div class="mb-3">
                                <label for="client_name" class="form-label"><?= Localization::translate('clients_client_name_required'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="client_name" name="client_name">
                            </div>

                            <div class="mb-3">
                                <label for="client_code" class="form-label"><?= Localization::translate('clients_client_code_required'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control client-code-input" id="client_code" name="client_code" placeholder="<?= Localization::translate('clients_client_code_placeholder'); ?>">
                                <div class="form-text"><?= Localization::translate('clients_client_code_help'); ?></div>
                            </div>

                            <div class="mb-3">
                                <label for="max_users" class="form-label"><?= Localization::translate('clients_maximum_users_required'); ?> <span class="text-danger">*</span></label>
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
                                <label for="admin_role_limit" class="form-label"><?= Localization::translate('clients_admin_role_limit_required'); ?> <span class="text-danger">*</span></label>
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
                                        <label for="logo" class="form-label"><?= Localization::translate('clients_client_logo_required'); ?> <span class="text-danger">*</span></label>
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
            <form id="editClientForm" method="POST" action="" enctype="multipart/form-data">
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

<!-- Toast, Confirmation, and Translation scripts are already loaded in header.php -->
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
<script src="<?= UrlHelper::url('public/js/client_form_validation.js') ?>"></script>
<!-- Include Client Management JavaScript -->
<script src="<?= UrlHelper::url('public/js/client_management.js') ?>"></script>

<script>
// Dynamic client management with AJAX (like social feed)
let currentPage = 1;
let currentSearch = '';
let currentFilters = {
    status: '',
    client_id: ''
};

document.addEventListener('DOMContentLoaded', function() {
    // Initialize search and filter functionality
    initializeClientManagement();

    // Load initial clients
    if (document.getElementById('clientsGrid')) {
        loadClients(1);
    }
});

function initializeClientManagement() {
    // Search functionality with debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        const debouncedSearch = debounce(() => {
            currentSearch = searchInput.value.trim();
            currentPage = 1;
            loadClients();
        }, 500);
        
        searchInput.addEventListener('input', debouncedSearch);
    }

    // Filter functionality
    const statusFilter = document.getElementById('statusFilter');
    const clientFilter = document.getElementById('clientFilter');

    if (statusFilter) {
        statusFilter.addEventListener('change', () => {
            currentFilters.status = statusFilter.value;
            currentPage = 1;
            loadClients();
        });
    }

    if (clientFilter) {
        clientFilter.addEventListener('change', () => {
            currentFilters.client_id = clientFilter.value;
            currentPage = 1;
            loadClients();
        });
    }

    // Clear filters functionality
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearAllFilters);
    }

    // Pagination functionality
    document.addEventListener('click', function(e) {
        if (e.target.matches('.page-link[data-page]')) {
            e.preventDefault();
            const page = parseInt(e.target.getAttribute('data-page'));
            loadClients(page);
        }
    });
}

function clearAllFilters() {
    // Clear search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        currentSearch = '';
    }

    // Clear all filters
    const statusFilter = document.getElementById('statusFilter');
    const clientFilter = document.getElementById('clientFilter');

    if (statusFilter) statusFilter.value = '';
    if (clientFilter) clientFilter.value = '';

    currentFilters = {
        status: '',
        client_id: ''
    };

    currentPage = 1;
    loadClients();
}

function loadClients(page = currentPage) {
    currentPage = page;

    // Show loading indicator
    const loadingIndicator = document.getElementById('loadingIndicator');
    const clientsContainer = document.getElementById('clientsContainer');
    const paginationContainer = document.getElementById('paginationContainer');

    if (loadingIndicator) loadingIndicator.style.display = 'block';
    if (clientsContainer) clientsContainer.style.display = 'none';
    if (paginationContainer) paginationContainer.style.display = 'none';

    // Prepare data for AJAX request
    const formData = new FormData();
    formData.append('controller', 'ClientController');
    formData.append('action', 'ajaxSearch');
    formData.append('page', currentPage);
    formData.append('search', currentSearch);
    formData.append('status', currentFilters.status);
    formData.append('client_id', currentFilters.client_id);

    // Make AJAX request
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateClientsGrid(data.clients);
            updatePagination(data.pagination);
            updateSearchInfo(data.totalClients);
        } else {
            console.error('Error loading clients:', data.message);
            // Show user-friendly message
            const grid = document.getElementById('clientsGrid');
            if (grid) {
                grid.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-building fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No clients found</h5>
                        <p class="text-muted">Try adjusting your search criteria or create a new client.</p>
                        <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                            <i class="fas fa-plus me-2"></i>Create First Client
                        </button>
                    </div>
                `;
            }
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        // Show user-friendly message
        const grid = document.getElementById('clientsGrid');
        if (grid) {
            grid.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>
                            <h5>Network Error</h5>
                            <p>Unable to load clients. Please check your connection and try again.</p>
                        </div>
                    </div>
                </div>
            `;
        }
    })
    .finally(() => {
        // Hide loading indicator and show clients container
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        if (clientsContainer) clientsContainer.style.display = 'block';
    });
}

function updateClientsGrid(clients) {
    const grid = document.getElementById('clientsGrid');
    if (!grid) return;

    grid.innerHTML = '';

    if (clients.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="fas fa-building fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No clients found</h5>
                <p class="text-muted">Try adjusting your search criteria or create a new client.</p>
                <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                    <i class="fas fa-plus me-2"></i>Create First Client
                </button>
            </div>
        `;
        return;
    }

    clients.forEach(client => {
        const clientCard = createClientCard(client);
        grid.appendChild(clientCard);
    });
}

function createClientCard(client) {
    const col = document.createElement('div');
    col.className = 'col-md-6 col-lg-4 mb-4';

    const statusBadgeClass = client.status === 'active' ? 'success' : (client.status === 'suspended' ? 'danger' : 'secondary');
    const progressWidth = client.max_users > 0 ? (client.active_users / client.max_users * 100) : 0;

    // Build features badges
    let featureBadges = '';
    if ((client.reports_enabled ?? 1) == 1) {
        featureBadges += '<span class="badge bg-success me-1">Reports</span>';
    }
    if ((client.theme_settings ?? 1) == 1) {
        featureBadges += '<span class="badge bg-info me-1">Themes</span>';
    }
    if ((client.sso_enabled ?? 0) == 1) {
        featureBadges += '<span class="badge bg-warning me-1">SSO</span>';
    }

    // Logo or placeholder
    const logoHtml = client.logo_path
        ? `<img src="${escapeHtml(client.logo_path)}" alt="${escapeHtml(client.client_name)}" class="client-logo me-2">`
        : `<div class="client-logo-placeholder me-2"><i class="fas fa-building"></i></div>`;

    // Delete button (only if not Super Admin client)
    const deleteButton = client.id != 1
        ? `<a href="#" class="btn btn-sm theme-btn-danger delete-client"
               data-id="${client.id}"
               data-name="${escapeHtml(client.client_name)}"
               title="Delete Client">
             <i class="fas fa-trash-alt"></i>
         </a>`
        : '';

    col.innerHTML = `
        <div class="card h-100 client-card"
             data-client-name="${escapeHtml(client.client_name)}"
             data-client-code="${escapeHtml(client.client_code)}"
             data-status="${escapeHtml(client.status)}"
             data-client-id="${client.id}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    ${logoHtml}
                    <h6 class="mb-0">${escapeHtml(client.client_name)}</h6>
                </div>
                <span class="badge bg-${statusBadgeClass}">
                    ${client.status.charAt(0).toUpperCase() + client.status.slice(1)}
                </span>
            </div>

            <div class="card-body">
                <div class="client-info">
                    <div class="info-item mb-2">
                        <strong>Client ID:</strong> <span class="text-muted">${client.id}</span>
                    </div>

                    <div class="info-item mb-2">
                        <strong>Client Code:</strong> <span class="text-muted"><code>${escapeHtml(client.client_code)}</code></span>
                    </div>

                    <div class="info-item mb-2">
                        <strong>Users:</strong> <span class="text-muted">${client.active_users} / ${client.max_users}</span>
                        <div class="progress mt-1 progress-bar-thin">
                            <div class="progress-bar" role="progressbar" style="width: ${progressWidth}%"></div>
                        </div>
                    </div>

                    <div class="info-item mb-2">
                        <strong>Admin Limit:</strong> <span class="text-muted">${client.admin_role_limit ?? 5} Roles</span>
                    </div>

                    <div class="info-item mb-2">
                        <strong>Features:</strong>
                        <div class="feature-badges mt-1">
                            ${featureBadges}
                        </div>
                    </div>

                    <div class="info-item mb-2">
                        <strong>Created:</strong> <span class="text-muted">${formatDate(client.created_at)}</span>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-sm theme-btn-secondary edit-client-btn"
                            data-client-id="${client.id}"
                            title="Edit Client">
                        <i class="fas fa-edit"></i>
                    </button>
                    <a href="${getProjectUrl('clients/' + client.id + '/users')}"
                       class="btn btn-sm btn-outline-primary" title="Manage Users">
                        <i class="fas fa-users"></i>
                    </a>
                    ${deleteButton}
                </div>
            </div>
        </div>
    `;

    return col;
}

function updatePagination(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;

    // Hide pagination if no clients found
    if (pagination.totalClients === 0) {
        container.style.display = 'none';
        return;
    }

    // Only show pagination if there are more than 10 total clients
    if (pagination.totalClients <= 10) {
        // Show total count when no pagination needed
        const plural = pagination.totalClients !== 1 ? 's' : '';
        container.innerHTML = `
            <div class="text-center text-muted small">
                Showing all ${pagination.totalClients} client${plural}
            </div>
        `;
        container.style.display = 'block';
        return;
    }

    // Generate pagination HTML
    let paginationHtml = '<nav aria-label="Client pagination"><ul class="pagination justify-content-center">';

    // Previous button
    if (pagination.currentPage > 1) {
        paginationHtml += `<li class="page-item">
            <a class="page-link" href="#" data-page="${pagination.currentPage - 1}">Previous</a>
        </li>`;
    }

    // Page numbers
    for (let i = 1; i <= pagination.totalPages; i++) {
        const isActive = i === pagination.currentPage ? 'active' : '';
        paginationHtml += `<li class="page-item ${isActive}">
            <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>`;
    }

    // Next button
    if (pagination.currentPage < pagination.totalPages) {
        paginationHtml += `<li class="page-item">
            <a class="page-link" href="#" data-page="${pagination.currentPage + 1}">Next</a>
        </li>`;
    }

    paginationHtml += '</ul></nav>';
    container.innerHTML = paginationHtml;
    container.style.display = 'block';
}

function updateSearchInfo(totalClients) {
    const resultsInfo = document.getElementById('resultsInfo');
    if (!resultsInfo) return;

    // Check if any filters are applied
    const hasFilters = currentSearch || 
                      currentFilters.status || 
                      currentFilters.client_id;

    if (hasFilters) {
        let infoText = `Showing ${totalClients} result${totalClients !== 1 ? 's' : ''}`;

        if (currentSearch) {
            infoText += ` for search: "<strong>${escapeHtml(currentSearch)}</strong>"`;
        }

        const appliedFilters = [];
        if (currentFilters.status) appliedFilters.push(`Status: ${currentFilters.status}`);
        if (currentFilters.client_id) appliedFilters.push(`Client: ${currentFilters.client_id}`);

        if (appliedFilters.length > 0) {
            infoText += ` with filters: ${appliedFilters.join(', ')}`;
        }

        resultsInfo.innerHTML = infoText;
    } else {
        resultsInfo.innerHTML = `Showing ${totalClients} client${totalClients !== 1 ? 's' : ''}`;
    }
}

// Helper functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Project URL helper function
function getProjectUrl(path) {
    const baseUrl = window.location.origin + '/Unlockyourskills/';
    return baseUrl + path.replace(/^\//, '');
}
</script>

<?php include 'includes/footer.php'; ?>

