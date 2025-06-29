<?php
// views/user_management.php
// Check if user is logged in
if (!isset($_SESSION['user']['client_id'])) {
    header('Location: index.php?controller=LoginController');
    exit;
}

require_once 'core/UrlHelper.php';
require_once 'core/IdEncryption.php';
require_once 'config/Localization.php';

$systemRole = $_SESSION['user']['system_role'] ?? '';
$canManageAll = in_array($systemRole, ['super_admin', 'admin']);

// Set default client name from session (current user's client)
$clientName = $_SESSION['user']['client_name'] ?? 'DEFAULT';

// If in client management mode, override with the managed client's name
if (isset($_GET['client_id'])) {
    $clientName = 'Unknown Client';
    if (isset($client) && $client) {
        $clientName = $client['client_name'];
    } elseif (isset($clients) && !empty($clients)) {
        foreach ($clients as $clientItem) {
            if ($clientItem['id'] == $_GET['client_id']) {
                $clientName = $clientItem['client_name'];
                break;
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container mt-4 user-management" data-user-page="true">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="<?= UrlHelper::url('manage-portal') ?>" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-users me-2"></i>
                User Management
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
                <li class="breadcrumb-item active" aria-current="page">User Management</li>
            </ol>
        </nav>

        <?php if (isset($_GET['client_id'])): ?>
            <div class="alert alert-info mb-3">
                <i class="fas fa-building"></i>
                <strong>Client Management Mode:</strong> Managing users for client <strong><?= htmlspecialchars($clientName); ?></strong>
                <a href="<?= UrlHelper::url('users') ?>" class="btn btn-sm btn-outline-secondary ms-2">
                    <i class="fas fa-arrow-left"></i> Back to All Users
                </a>
            </div>
        <?php endif; ?>

        <!-- Page Description -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">Manage user accounts, roles, and permissions</p>
                    </div>
                        <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-primary" id="importUserBtn" title="Import Users">
                            <i class="fas fa-upload me-2"></i>Import Users
                            </button>
                            <?php if ($customFieldCreationEnabled): ?>
                        <a href="<?= UrlHelper::url('settings/custom-fields') ?>" class="btn btn-outline-secondary" title="<?= Localization::translate('manage_custom_fields'); ?>">
                            <i class="fas fa-cogs me-2"></i><?= Localization::translate('manage_custom_fields'); ?>
                            </a>
                            <?php endif; ?>
                            <?php
                            // Check if user limit is reached
                            $addUserDisabled = '';
                            $addUserText = Localization::translate('buttons_add_user');

                            // Preserve client_id parameter if present (for super admin client management)
                            $addUserUrl = UrlHelper::url('users/create');
                            if (isset($_GET['client_id'])) {
                                $addUserUrl .= '?client_id=' . urlencode($_GET['client_id']);
                            }
                            $addUserOnclick = "window.location.href='$addUserUrl'";
                            $addUserTitle = Localization::translate('buttons_add_user_tooltip');

                            if ($userLimitStatus && !$userLimitStatus['canAdd']) {
                                $addUserDisabled = 'disabled';
                                $addUserText .= ' (Limit Reached)';
                                $addUserOnclick = '';
                                $addUserTitle = 'User limit reached: ' . $userLimitStatus['current'] . '/' . $userLimitStatus['limit'];
                            }
                            ?>
                            <?php if ($addUserDisabled): ?>
                            <button type="button" class="btn theme-btn-primary" title="<?= $addUserTitle; ?>" disabled>
                                <i class="fas fa-plus me-2"></i><?= $addUserText; ?>
                                </button>
                            <?php else: ?>
                            <button type="button" class="btn theme-btn-primary" title="<?= $addUserTitle; ?>" data-bs-toggle="modal" data-bs-target="#addUserModal" data-client-id="<?= isset($_GET['client_id']) ? htmlspecialchars($_GET['client_id']) : ''; ?>">
                                <i class="fas fa-plus me-2"></i><?= $addUserText; ?>
                                </button>
                            <?php endif; ?>
                    </div>
                        </div>
                        <?php if ($userLimitStatus && !$userLimitStatus['canAdd']): ?>
                    <small class="text-muted mt-2 d-block">
                                Users: <?= $userLimitStatus['current']; ?>/<?= $userLimitStatus['limit']; ?>
                            </small>
                        <?php endif; ?>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Search -->
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" id="searchInput" class="form-control"
                                        placeholder="<?= Localization::translate('filters_search_placeholder'); ?>"
                                        title="<?= Localization::translate('filters_search'); ?>">
                                </div>
                            </div>

                            <!-- User Status Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="userStatusFilter">
                                    <option value=""><?= Localization::translate('filters_user_status'); ?></option>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>

                            <!-- Locked Status Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="lockedStatusFilter">
                                    <option value=""><?= Localization::translate('filters_locked_status'); ?></option>
                                    <option value="1">Locked</option>
                                    <option value="0">Unlocked</option>
                                </select>
                            </div>

                            <!-- User Role Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="userRoleFilter">
                                    <option value="">User Role</option>
                                    <?php if (!empty($uniqueUserRoles)): ?>
                                        <?php foreach ($uniqueUserRoles as $role): ?>
                                            <option value="<?= htmlspecialchars($role); ?>">
                                                <?= htmlspecialchars($role); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Gender Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="genderFilter">
                                    <option value="">Gender</option>
                                    <?php if (!empty($uniqueGenders)): ?>
                                        <?php foreach ($uniqueGenders as $gender): ?>
                                            <option value="<?= htmlspecialchars($gender); ?>">
                                                <?= htmlspecialchars($gender); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Clear All Filters -->
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100" id="clearFiltersBtn" title="Clear all filters">
                                    <i class="fas fa-times"></i>
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
                    <span id="resultsInfo">Loading users...</span>
        </div>
            </div>
        </div>

        <!-- Users Grid -->
        <div id="usersContainer">
            <table class="table table-bordered" id="userGrid">
                <thead class="question-grid">
                    <tr>
                        <th><?= Localization::translate('user_grid_profile_id'); ?></th>
                        <th><?= Localization::translate('user_grid_full_name'); ?></th>
                        <th><?= Localization::translate('user_grid_email'); ?></th>
                        <th><?= Localization::translate('user_grid_contact_number'); ?></th>
                        <th><?= Localization::translate('user_grid_user_status'); ?></th>
                        <th><?= Localization::translate('user_grid_locked_status'); ?></th>
                        <th><?= Localization::translate('user_grid_action'); ?></th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <!-- Users will be loaded here via AJAX -->
                </tbody>
            </table>
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
                <p class="mt-2 text-muted">Loading users...</p>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">
                    <i class="fas fa-user-plus me-2"></i><?= Localization::translate('add_user_title'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="addUserModalContent">
                    <!-- Content will be loaded dynamically -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading form...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">
                    <i class="fas fa-user-edit me-2"></i><?= Localization::translate('edit_user_title'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="editUserModalContent">
                    <!-- Content will be loaded dynamically -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading form...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Modal Initialization Script -->
<script>
// Pass backend data to JavaScript
const currentUserRole = '<?= $_SESSION['user']['system_role'] ?? 'guest'; ?>';

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔥 User Management: DOM loaded');
    
    // Initialize search and filter functionality
    initializeUserManagement();

    // Load initial users
    if (document.getElementById('usersTableBody')) {
        console.log('🔥 User Management: Loading initial users');
        loadUsers(1);
    } else {
        console.error('🔥 User Management: usersTableBody not found');
    }
});

function initializeUserManagement() {
    // Search functionality with debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        const debouncedSearch = debounce(() => {
            currentSearch = searchInput.value.trim();
            currentPage = 1;
            loadUsers();
        }, 500);
        
        searchInput.addEventListener('input', debouncedSearch);
    }

    // Filter functionality
    const userStatusFilter = document.getElementById('userStatusFilter');
    const lockedStatusFilter = document.getElementById('lockedStatusFilter');
    const userRoleFilter = document.getElementById('userRoleFilter');
    const genderFilter = document.getElementById('genderFilter');

    if (userStatusFilter) {
        userStatusFilter.addEventListener('change', () => {
            currentFilters.user_status = userStatusFilter.value;
            currentPage = 1;
            loadUsers();
        });
    }

    if (lockedStatusFilter) {
        lockedStatusFilter.addEventListener('change', () => {
            currentFilters.locked_status = lockedStatusFilter.value;
            currentPage = 1;
            loadUsers();
        });
    }

    if (userRoleFilter) {
        userRoleFilter.addEventListener('change', () => {
            currentFilters.user_role = userRoleFilter.value;
            currentPage = 1;
            loadUsers();
        });
    }

    if (genderFilter) {
        genderFilter.addEventListener('change', () => {
            currentFilters.gender = genderFilter.value;
            currentPage = 1;
            loadUsers();
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
            loadUsers(page);
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
    const userStatusFilter = document.getElementById('userStatusFilter');
    const lockedStatusFilter = document.getElementById('lockedStatusFilter');
    const userRoleFilter = document.getElementById('userRoleFilter');
    const genderFilter = document.getElementById('genderFilter');

    if (userStatusFilter) userStatusFilter.value = '';
    if (lockedStatusFilter) lockedStatusFilter.value = '';
    if (userRoleFilter) userRoleFilter.value = '';
    if (genderFilter) genderFilter.value = '';

    currentFilters = {
        user_status: '',
        locked_status: '',
        user_role: '',
        gender: ''
    };

    currentPage = 1;
    loadUsers();
}

function loadUsers(page = currentPage) {
    console.log('🔥 User Management: loadUsers called with page:', page);
    currentPage = page;

    // Show loading indicator
    const loadingIndicator = document.getElementById('loadingIndicator');
    const usersContainer = document.getElementById('usersContainer');
    const paginationContainer = document.getElementById('paginationContainer');

    if (loadingIndicator) loadingIndicator.style.display = 'block';
    if (usersContainer) usersContainer.style.display = 'none';
    if (paginationContainer) paginationContainer.style.display = 'none';

    // Prepare data for AJAX request
    const formData = new FormData();
    formData.append('controller', 'UserManagementController');
    formData.append('action', 'ajaxSearch');
    formData.append('page', currentPage);
    formData.append('search', currentSearch);
    formData.append('user_status', currentFilters.user_status);
    formData.append('locked_status', currentFilters.locked_status);
    formData.append('user_role', currentFilters.user_role);
    formData.append('gender', currentFilters.gender);

    // Add client_id if present in URL
    let clientId = null;
    
    // First try to get from URL search parameters (for backward compatibility)
    const urlParams = new URLSearchParams(window.location.search);
    clientId = urlParams.get('client_id');
    
    // If not found in search params, try to extract from URL path /clients/{id}/users
    if (!clientId) {
        const pathParts = window.location.pathname.split('/');
        const clientsIndex = pathParts.indexOf('clients');
        if (clientsIndex !== -1 && clientsIndex + 1 < pathParts.length && pathParts[clientsIndex + 2] === 'users') {
            clientId = pathParts[clientsIndex + 1];
            console.log('🔥 User Management: Extracted client_id from URL path:', clientId);
        }
    }
    
    if (clientId) {
        formData.append('client_id', clientId);
        console.log('🔥 User Management: Adding client_id to AJAX request:', clientId);
    } else {
        console.log('🔥 User Management: No client_id found in URL');
    }

    console.log('🔥 User Management: Making AJAX request');
    console.log('🔥 User Management: FormData contents:');
    for (let [key, value] of formData.entries()) {
        console.log('  ', key, ':', value);
    }

    // Make AJAX request
    fetch(getProjectUrl('users/ajax/search'), {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('🔥 User Management: AJAX response received');
        return response.json();
    })
    .then(data => {
        console.log('🔥 User Management: AJAX data:', data);
        if (data.success) {
            updateUsersTable(data.users);
            updatePagination(data.pagination);
            updateSearchInfo(data.totalUsers);
        } else {
            console.error('Error loading users:', data.message);
            // Show user-friendly message
            const tbody = document.getElementById('usersTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No users found</h5>
                            <p class="text-muted">Try adjusting your search criteria or add a new user.</p>
                            <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-plus me-2"></i>Add First User
                            </button>
                        </td>
                    </tr>
                `;
            }
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        // Show user-friendly message
        const tbody = document.getElementById('usersTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <h5>Network Error</h5>
                                <p>Unable to load users. Please check your connection and try again.</p>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }
    })
    .finally(() => {
        // Hide loading indicator and show users container
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        if (usersContainer) usersContainer.style.display = 'block';
    });
}

function updateUsersTable(users) {
    console.log('🔥 User Management: updateUsersTable called with', users.length, 'users');
    const tbody = document.getElementById('usersTableBody');
    if (!tbody) {
        console.error('🔥 User Management: usersTableBody not found');
        return;
    }

    tbody.innerHTML = '';

    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No users found</h5>
                    <p class="text-muted">Try adjusting your search criteria or add a new user.</p>
                    <button type="button" class="btn theme-btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fas fa-plus me-2"></i>Add First User
                    </button>
                </td>
            </tr>
        `;
        return;
    }

    users.forEach(user => {
        const row = createUserRow(user);
        tbody.appendChild(row);
    });
}

function createUserRow(user) {
    const row = document.createElement('tr');
    
    // Only use encrypted_id for all actions
    const encryptedId = user.encrypted_id;
    
    // Check if user is Super Admin
    const isSuperAdmin = (user.system_role === 'super_admin' || user.user_role === 'Super Admin');
    const disabledClass = isSuperAdmin ? 'disabled' : '';
    const disabledStyle = isSuperAdmin ? 'style="pointer-events: none; opacity: 0.5; cursor: not-allowed;"' : '';
    
    // Only render action buttons if encryptedId is present
    let actionButtons = '';
    if (encryptedId) {
        actionButtons = `
            <button type="button"
                    class="btn theme-btn-primary ${disabledClass} edit-user-btn"
                    ${disabledStyle}
                    title="${isSuperAdmin ? 'Edit disabled for Super Admin' : 'Edit User'}"
                    ${isSuperAdmin ? 'disabled' : `data-user-id="${encryptedId}"`}>
                <i class="fas fa-edit"></i>
            </button>

            ${(user.locked_status == '1') ?
                `<a href="#" class="btn theme-btn-warning ${isSuperAdmin ? 'disabled' : 'unlock-user'}" ${isSuperAdmin ? disabledStyle : ''} ${isSuperAdmin ? '' : `data-id="${encryptedId}" data-name="${escapeHtml(user.full_name)}"`} title="${isSuperAdmin ? 'Unlock disabled for Super Admin' : 'Unlock User'}">
                    <i class="fas fa-lock-open"></i>
                </a>` :
                `<a href="#" class="btn theme-btn-danger ${isSuperAdmin ? 'disabled' : 'lock-user'}" ${isSuperAdmin ? disabledStyle : ''} ${isSuperAdmin ? '' : `data-id="${encryptedId}" data-name="${escapeHtml(user.full_name)}"`} title="${isSuperAdmin ? 'Lock disabled for Super Admin' : 'Lock User'}">
                    <i class="fas fa-lock"></i>
                </a>`
            }

            <a href="#" class="btn theme-btn-danger ${isSuperAdmin ? 'disabled' : 'delete-user'}" ${isSuperAdmin ? disabledStyle : ''} ${isSuperAdmin ? '' : `data-id="${encryptedId}" data-name="${escapeHtml(user.full_name)}"`} title="${isSuperAdmin ? 'Delete disabled for Super Admin' : 'Delete User'}">
                <i class="fas fa-trash-alt"></i>
            </a>
        `;
    } else {
        actionButtons = `<span class="text-danger small">Missing ID</span>`;
    }

    row.innerHTML = `
        <td>${escapeHtml(user.profile_id)}</td>
        <td>${escapeHtml(user.full_name)}</td>
        <td>${escapeHtml(user.email)}</td>
        <td>${escapeHtml(user.contact_number)}</td>
        <td>
            ${(user.user_status == 'Active') ?
                '<span class="badge bg-success">Active</span>' :
                '<span class="badge bg-danger">Inactive</span>'}
        </td>
        <td>
            ${(user.locked_status == '1') ?
                '<span class="badge bg-warning">Locked</span>' :
                '<span class="badge bg-primary">Unlocked</span>'}
        </td>
        <td>
            ${actionButtons}
        </td>
    `;

    return row;
}

function updatePagination(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;

    // Hide pagination if no users found
    if (pagination.totalUsers === 0) {
        container.style.display = 'none';
        return;
    }

    // Only show pagination if there are more than 10 total users
    if (pagination.totalUsers <= 10) {
        // Show total count when no pagination needed
        const plural = pagination.totalUsers !== 1 ? 's' : '';
        container.innerHTML = `
            <div class="text-center text-muted small">
                Showing all ${pagination.totalUsers} user${plural}
            </div>
        `;
        container.style.display = 'block';
        return;
    }

    // Generate pagination HTML
    let paginationHtml = '<nav aria-label="User pagination"><ul class="pagination justify-content-center">';

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

function updateSearchInfo(totalUsers) {
    const resultsInfo = document.getElementById('resultsInfo');
    if (!resultsInfo) return;

    // Check if any filters are applied
    const hasFilters = currentSearch || 
                      currentFilters.user_status || 
                      currentFilters.locked_status || 
                      currentFilters.user_role || 
                      currentFilters.gender;

    if (hasFilters) {
        let infoText = `Showing ${totalUsers} result${totalUsers !== 1 ? 's' : ''}`;

        if (currentSearch) {
            infoText += ` for search: "<strong>${escapeHtml(currentSearch)}</strong>"`;
        }

        const appliedFilters = [];
        if (currentFilters.user_status) appliedFilters.push(`Status: ${currentFilters.user_status === 'Active' ? 'Active' : 'Inactive'}`);
        if (currentFilters.locked_status) appliedFilters.push(`Locked: ${currentFilters.locked_status === '1' ? 'Yes' : 'No'}`);
        if (currentFilters.user_role) appliedFilters.push(`Role: ${currentFilters.user_role}`);
        if (currentFilters.gender) appliedFilters.push(`Gender: ${currentFilters.gender}`);

        if (appliedFilters.length > 0) {
            infoText += ` with filters: ${appliedFilters.join(', ')}`;
        }

        resultsInfo.innerHTML = infoText;
    } else {
        resultsInfo.innerHTML = `Showing ${totalUsers} user${totalUsers !== 1 ? 's' : ''}`;
    }
}

// Helper functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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

// Initialize modal functionality
function initializeUserModals() {
    // Add User Modal
    const addUserModal = document.getElementById('addUserModal');
    if (addUserModal) {
        addUserModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const clientId = button ? button.getAttribute('data-client-id') : '';
            loadAddUserModalContent(clientId);
        });

        addUserModal.addEventListener('hidden.bs.modal', function() {
            console.log('🔥 Add modal hidden event fired');

            // Clear modal content when closed
            const modalContent = document.getElementById('addUserModalContent');
            if (modalContent) {
                modalContent.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading form...</p>
                    </div>
                `;
            }

            // Force cleanup of any remaining backdrop
            setTimeout(() => {
                console.log('🔥 Cleaning up add modal backdrop');
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => {
                    console.log('🔥 Removing remaining backdrop');
                    backdrop.remove();
                });

                // Ensure body classes and styles are reset
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
                document.body.style.overflow = '';
            }, 100);
        });
    }

    // Edit User Modal
    const editUserModal = document.getElementById('editUserModal');
    console.log('🔥 EDIT DEBUG: Edit modal element found:', editUserModal);

    if (editUserModal) {
        console.log('🔥 EDIT DEBUG: Setting up edit modal event listeners...');

        // Note: We handle modal opening manually in the click handler below
        // to avoid conflicts with Bootstrap's automatic modal handling

        editUserModal.addEventListener('hidden.bs.modal', function() {
            console.log('🔥 Edit modal hidden event fired');

            // Clear modal content when closed
            const modalContent = document.getElementById('editUserModalContent');
            if (modalContent) {
                modalContent.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading form...</p>
                    </div>
                `;
            }

            // Force cleanup of any remaining backdrop
            setTimeout(() => {
                console.log('🔥 Cleaning up edit modal backdrop');
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => {
                    console.log('🔥 Removing remaining backdrop');
                    backdrop.remove();
                });

                // Ensure body classes and styles are reset
                document.body.classList.remove('modal-open');
                document.body.style.paddingRight = '';
                document.body.style.overflow = '';
            }, 100);
        });
    }

    // Handle edit button clicks in AJAX-generated content
    document.addEventListener('click', function(e) {
        console.log('🔥 EDIT DEBUG: Click event detected on:', e.target);
        console.log('🔥 EDIT DEBUG: Target classes:', e.target.className);
        console.log('🔥 EDIT DEBUG: Target closest edit-user-btn:', e.target.closest('.edit-user-btn'));

        if (e.target.closest('.edit-user-btn')) {
            e.preventDefault();

            const button = e.target.closest('.edit-user-btn');
            const userId = button.getAttribute('data-user-id');

            // Check if modal already exists and is open
            const existingModal = bootstrap.Modal.getInstance(editUserModal);
            if (existingModal) {
                existingModal.dispose();
            }

            // Clear any existing modal backdrops
            const existingBackdrops = document.querySelectorAll('.modal-backdrop');
            existingBackdrops.forEach(backdrop => backdrop.remove());

            // Remove modal-open class from body
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
            document.body.style.overflow = '';

            // Show modal and load content
            if (editUserModal && typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(editUserModal);
                modal.show();
                loadEditUserModalContent(userId);
            }
        }
    });
}

function loadAddUserModalContent(clientId = '') {
    const modalContent = document.getElementById('addUserModalContent');
    if (!modalContent) return;

    // Show loading state
    modalContent.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading form...</p>
        </div>
    `;

    // Build URL with client_id if provided
    let url = getProjectUrl('users/modal/add');
    if (clientId) {
        url += '?client_id=' + encodeURIComponent(clientId);
    }

    fetch(url)
        .then(response => response.text())
        .then(html => {
            modalContent.innerHTML = html;

            // Initialize form functionality
            if (typeof initializeAddUserForm === 'function') {
                initializeAddUserForm();
            }

            // Initialize location dropdowns for modal (with modal_ prefix)
            // This now includes timezone initialization as well
            if (typeof initializeLocationDropdowns === 'function') {
                initializeLocationDropdowns('modal_');
            }

            if (typeof initializeAddUserModalValidation === 'function') {
                console.log('🔥 About to call initializeAddUserModalValidation');
                initializeAddUserModalValidation();
            }
        })
        .catch(error => {
            console.error('Error loading add user form:', error);
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error loading form. Please try again.
                </div>
            `;
        });
}

function loadEditUserModalContent(userId) {
    const modalContent = document.getElementById('editUserModalContent');
    if (!modalContent) return;

    // Show loading state
    modalContent.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading form...</p>
        </div>
    `;

    const url = getProjectUrl('users/modal/edit') + '?user_id=' + encodeURIComponent(userId);

    fetch(url)
        .then(response => response.text())
        .then(html => {
            modalContent.innerHTML = html;

            // Check if the content looks like a form
            const hasForm = html.includes('<form') && html.includes('editUserModalForm');
            const hasError = html.includes('alert-danger') || html.includes('Error');

            if (hasError) {
                console.error('Error in modal content');
            } else if (hasForm) {
                // Wait for DOM to update, then initialize form
                setTimeout(() => {
                    const form = document.getElementById('editUserModalForm');
                    if (form && typeof initializeEditModalValidation === 'function') {
                        initializeEditModalValidation();
                    }
                }, 200);
            }
        })
        .catch(error => {
            console.error('Error loading edit user form:', error);
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error loading form. Please try again.
                    <br><small>Error: ${error.message}</small>
                </div>
            `;
        });
}

function getProjectUrl(path) {
    const baseUrl = window.location.origin + '/Unlockyourskills/';
    return baseUrl + path.replace(/^\//, '');
}

// Initialize modals after user management
initializeUserModals();

document.addEventListener('shown.bs.modal', function(event) {
    if (event.target && event.target.id === 'addUserModal') {
        var profileIdField = document.getElementById('modal_profile_id');
        if (profileIdField && !profileIdField.value) {
            var prefix = '<?= substr(preg_replace("/[^A-Za-z0-9]/", "", $clientName), 0, 2) ?>'.toUpperCase();
            var timestamp = Date.now().toString().slice(-6);
            var random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            profileIdField.value = prefix + timestamp + random;
        }
    }
});
</script>

<!-- Custom Field Modal removed - now managed in Settings -->

<?php include 'includes/footer.php'; ?>
<script>
window.addUserSubmitUrl = '<?= UrlHelper::url('users/modal/submit-add') ?>';
</script>
<script src="<?= UrlHelper::url('public/js/add_user_modal_validation.js') ?>"></script>
<script src="<?= UrlHelper::url('public/js/edit_user_validation.js') ?>"></script>
<script src="<?= UrlHelper::url('public/js/user_management.js') ?>"></script>
