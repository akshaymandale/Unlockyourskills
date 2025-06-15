<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container mt-4 client-management">

        <!-- Page Header -->
        <h1 class="page-title text-purple">Client Management</h1>

        <!-- ✅ Filters & Search Section -->
        <div class="filter-section">
            <div class="container-fluid mb-3">
                <!-- Single Row: All Controls -->
                <div class="row justify-content-between align-items-center g-3">

                    <!-- Status Filter -->
                    <div class="col-auto">
                        <select class="form-select form-select-sm" id="statusFilter">
                            <option value="">All Statuses</option>
                            <option value="active" <?= ($filters['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?= ($filters['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?= ($filters['status'] === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>

                    <!-- Search Bar -->
                    <div class="col-auto">
                        <div class="input-group input-group-sm">
                            <input type="text" id="searchInput" class="form-control"
                                value="<?= htmlspecialchars($search); ?>"
                                placeholder="Search clients..."
                                title="Search clients">
                            <button type="button" id="searchButton" class="btn btn-outline-secondary"
                                title="Search clients">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Clear Filters Button -->
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm btn-clear-filters" id="clearFiltersBtn"
                            title="Clear all filters">
                            <i class="fas fa-times me-1"></i> Clear Filters
                        </button>
                    </div>

                    <!-- Add Client Button -->
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#addClientModal"
                            title="Add new client">
                            <i class="fas fa-plus me-1"></i> Add Client
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
            <p class="mt-2">Loading clients...</p>
        </div>

            <!-- Clients Grid -->
            <div class="row">
                <?php if (empty($clients)): ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i>
                            No clients found
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
                                            <strong>Client ID:</strong>
                                            <span class="text-muted"><?= $client['id']; ?></span>
                                        </div>
                                        
                                        <div class="info-item">
                                            <strong>Users:</strong>
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
                                            <strong>Admin Limit:</strong>
                                            <span class="text-muted"><?= $client['admin_role_limit'] ?? 5; ?> roles</span>
                                        </div>

                                        <div class="info-item">
                                            <strong>Features:</strong>
                                            <div class="feature-badges mt-1">
                                                <?php if (($client['reports_enabled'] ?? 1) == 1): ?>
                                                    <span class="badge bg-success me-1">Reports</span>
                                                <?php endif; ?>
                                                <?php if (($client['theme_settings'] ?? 1) == 1): ?>
                                                    <span class="badge bg-info me-1">Themes</span>
                                                <?php endif; ?>
                                                <?php if (($client['sso_enabled'] ?? 0) == 1): ?>
                                                    <span class="badge bg-warning me-1">SSO</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <strong>Created:</strong>
                                            <span class="text-muted"><?= date('M j, Y', strtotime($client['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <button type="button" class="btn btn-sm theme-btn-secondary edit-client-btn"
                                                data-client-id="<?= $client['id']; ?>"
                                                title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="index.php?controller=UserManagementController&client_id=<?= $client['id']; ?>"
                                           class="btn btn-sm btn-outline-primary" title="Manage Users">
                                            <i class="fas fa-users"></i>
                                        </a>
                                        <a href="index.php?controller=ClientController&action=stats&id=<?= $client['id']; ?>"
                                           class="btn btn-sm btn-outline-info" title="Statistics">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                        <?php if ($client['id'] != 1): // Don't show delete for Super Admin client ?>
                                            <a href="#" class="btn btn-sm theme-btn-danger delete-client"
                                               data-id="<?= $client['id']; ?>"
                                               data-name="<?= htmlspecialchars($client['client_name']); ?>"
                                               title="Delete">
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
                <nav aria-label="Clients pagination">
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
                    <i class="fas fa-building me-2"></i>Add New Client
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addClientForm" method="POST" action="index.php?controller=ClientController&action=store" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <h6 class="text-purple mb-3">Basic Information</h6>

                            <div class="mb-3">
                                <label for="client_name" class="form-label">Client Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="client_name" name="client_name">
                            </div>

                            <div class="mb-3">
                                <label for="max_users" class="form-label">Maximum Users <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="max_users" name="max_users" placeholder="Enter number of users">
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>

                        <!-- Configuration Settings -->
                        <div class="col-md-6">
                            <h6 class="text-purple mb-3">Configuration Settings</h6>

                            <div class="mb-3">
                                <label for="reports_enabled" class="form-label">Reports</label>
                                <select class="form-select" id="reports_enabled" name="reports_enabled">
                                    <option value="1" selected>Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="theme_settings" class="form-label">Theme Color Setting</label>
                                <select class="form-select" id="theme_settings" name="theme_settings">
                                    <option value="1" selected>Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="sso_enabled" class="form-label">SSO Login</label>
                                <select class="form-select" id="sso_enabled" name="sso_enabled">
                                    <option value="0" selected>No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="admin_role_limit" class="form-label">Admin Role Limit <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="admin_role_limit" name="admin_role_limit"
                                       value="1" placeholder="Enter number of admin roles allowed">
                                <div class="form-text">Maximum number of admin users allowed</div>
                            </div>
                        </div>

                        <!-- Branding -->
                        <div class="col-12 mt-3">
                            <h6 class="text-purple mb-3">Branding</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="logo" class="form-label">Client Logo <span class="text-danger">*</span></label>
                                        <input type="file" class="form-control" id="logo" name="logo" accept="image/png,image/jpeg,image/gif">
                                        <div class="form-text">Upload PNG, JPG, or GIF (max 5MB)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief description of the client..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn theme-btn-primary">
                        Create Client
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
                    Edit Client
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editClientForm" method="POST" action="index.php?controller=ClientController&action=update" enctype="multipart/form-data">
                <input type="hidden" id="edit_client_id" name="client_id">
                <div class="modal-body">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <h6 class="text-purple mb-3">Basic Information</h6>

                            <div class="mb-3">
                                <label for="edit_client_name" class="form-label">Client Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_client_name" name="client_name">
                            </div>

                            <div class="mb-3">
                                <label for="edit_max_users" class="form-label">Maximum Users <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_max_users" name="max_users" placeholder="Enter number of users">
                            </div>

                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>

                        <!-- Configuration Settings -->
                        <div class="col-md-6">
                            <h6 class="text-purple mb-3">Configuration Settings</h6>

                            <div class="mb-3">
                                <label for="edit_reports_enabled" class="form-label">Reports</label>
                                <select class="form-select" id="edit_reports_enabled" name="reports_enabled">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="edit_theme_settings" class="form-label">Theme Color Setting</label>
                                <select class="form-select" id="edit_theme_settings" name="theme_settings">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="edit_sso_enabled" class="form-label">SSO Login</label>
                                <select class="form-select" id="edit_sso_enabled" name="sso_enabled">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="edit_admin_role_limit" class="form-label">Admin Role Limit <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_admin_role_limit" name="admin_role_limit"
                                       placeholder="Enter number of admin roles allowed">
                                <div class="form-text">Maximum number of admin users allowed</div>
                            </div>
                        </div>

                        <!-- Branding -->
                        <div class="col-12 mt-3">
                            <h6 class="text-purple mb-3">Branding</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_logo" class="form-label">Client Logo</label>
                                        <input type="file" class="form-control" id="edit_logo" name="logo" accept="image/png,image/jpeg,image/gif">
                                        <div class="form-text">Upload PNG, JPG, or GIF (max 5MB) - Leave empty to keep current logo</div>
                                        <div id="current_logo_preview" class="mt-2" style="display: none;">
                                            <small class="text-muted">Current logo:</small><br>
                                            <img id="current_logo_img" src="" alt="Current Logo" style="max-width: 100px; max-height: 50px; border: 1px solid #ddd; border-radius: 4px;">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="edit_description" class="form-label">Description</label>
                                        <textarea class="form-control" id="edit_description" name="description" rows="3" placeholder="Brief description of the client..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn theme-btn-primary">
                        Update Client
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
<!-- Include Client Validation JavaScript -->
<script src="public/js/client_validation.js"></script>

<!-- Client Management JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
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
                submitBtn.innerHTML = 'Update Client';
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
                message = 'Client created successfully!';
                break;
            case 'client_updated':
                message = 'Client updated successfully!';
                break;
            case 'client_deleted':
                message = 'Client deleted successfully!';
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
