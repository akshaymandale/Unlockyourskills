<?php
// views/user_management.php
require_once 'core/UrlHelper.php';
require_once 'core/IdEncryption.php';
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container add-user-container user-management" data-user-page="true">
        <h1 class="page-title text-purple"><?= Localization::translate('user_management_title'); ?></h1>

        <?php if (isset($_GET['client_id'])): ?>
            <?php
            // Get client name for display
            $clientName = 'Unknown Client';
            if (isset($clients) && !empty($clients)) {
                foreach ($clients as $client) {
                    if ($client['id'] == $_GET['client_id']) {
                        $clientName = $client['client_name'];
                        break;
                    }
                }
            }
            ?>
            <div class="alert alert-info mb-3">
                <i class="fas fa-building"></i>
                <strong>Client Management Mode:</strong> Managing users for client <strong><?= htmlspecialchars($clientName); ?></strong>
                <a href="<?= UrlHelper::url('users') ?>" class="btn btn-sm btn-outline-secondary ms-2">
                    <i class="fas fa-arrow-left"></i> Back to All Users
                </a>
            </div>
        <?php endif; ?>

        <!-- ✅ Filters & Search Section -->
        <div class="filter-section">
            <div class="container-fluid">
                <!-- Single Compact Row -->
                <div class="row align-items-center g-2">

                    <!-- Compact Filters -->
                    <div class="col-auto">
                        <select class="form-select form-select-sm compact-filter" id="userStatusFilter">
                            <option value=""><?= Localization::translate('filters_user_status'); ?></option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm compact-filter" id="lockedStatusFilter">
                            <option value=""><?= Localization::translate('filters_locked_status'); ?></option>
                            <option value="1">Locked</option>
                            <option value="0">Unlocked</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm compact-filter" id="userRoleFilter">
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
                    <div class="col-auto">
                        <select class="form-select form-select-sm compact-filter" id="genderFilter">
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

                    <!-- Search -->
                    <div class="col-auto">
                        <div class="input-group input-group-sm compact-search">
                            <input type="text" id="searchInput" class="form-control"
                                placeholder="<?= Localization::translate('filters_search_placeholder'); ?>"
                                title="<?= Localization::translate('filters_search'); ?>">
                            <button type="button" id="searchButton" class="btn btn-outline-secondary"
                                title="<?= Localization::translate('filters_search'); ?>">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Clear Filters -->
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm btn-secondary" id="clearFiltersBtn"
                            title="Clear all filters">
                            <i class="fas fa-times me-1"></i> Clear
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-auto ms-auto">
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-primary" id="importUserBtn"
                                title="Import Users">
                                <i class="fas fa-upload me-1"></i> Import Users
                            </button>
                            <?php if ($customFieldCreationEnabled): ?>
                            <a href="<?= UrlHelper::url('settings/custom-fields') ?>" class="btn btn-sm btn-primary"
                                title="<?= Localization::translate('manage_custom_fields'); ?>">
                                <i class="fas fa-cogs me-1"></i> <?= Localization::translate('manage_custom_fields'); ?>
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
                                <button type="button"
                                        class="btn btn-sm btn-primary add-user-btn"
                                        title="<?= $addUserTitle; ?>"
                                        disabled>
                                    <i class="fas fa-plus me-1"></i><?= $addUserText; ?>
                                </button>
                            <?php else: ?>
                                <button type="button"
                                        class="btn btn-sm btn-primary add-user-btn"
                                        title="<?= $addUserTitle; ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addUserModal"
                                        data-client-id="<?= isset($_GET['client_id']) ? htmlspecialchars($_GET['client_id']) : ''; ?>">
                                    <i class="fas fa-plus me-1"></i><?= $addUserText; ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if ($userLimitStatus && !$userLimitStatus['canAdd']): ?>
                            <small class="text-muted d-block mt-1 text-end">
                                Users: <?= $userLimitStatus['current']; ?>/<?= $userLimitStatus['limit']; ?>
                            </small>
                        <?php endif; ?>
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
            <div class="spinner-border" style="color: #6a0dad;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2" style="color: #6a0dad;">Loading users...</p>
        </div>


        <!-- ✅ User Grid View -->
        <div id="usersContainer" class="fade-transition">
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
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['profile_id']); ?></td>
                                <td><?= htmlspecialchars($user['full_name']); ?></td>
                                <td><?= htmlspecialchars($user['email']); ?></td>
                                <td><?= htmlspecialchars($user['contact_number']); ?></td>
                                <td>
                                    <?= ($user['user_status'] == 1) ?
                                        '<span class="badge bg-success">' . Localization::translate('user_grid_active') . '</span>' :
                                        '<span class="badge bg-danger">' . Localization::translate('user_grid_inactive') . '</span>'; ?>
                                </td>
                                <td>
                                    <?= ($user['locked_status'] == 1) ?
                                        '<span class="badge bg-warning">' . Localization::translate('user_grid_locked') . '</span>' :
                                        '<span class="badge bg-primary">' . Localization::translate('user_grid_unlocked') . '</span>'; ?>
                                </td>
                                <td>
                                    <!-- ✅ Edit Button -->
                                    <?php
                                    // Generate encrypted URL for edit user
                                    $encryptedId = IdEncryption::encrypt($user['profile_id']);
                                    $editUserUrl = UrlHelper::url('users/' . $encryptedId . '/edit');
                                    if (isset($_GET['client_id'])) {
                                        $editUserUrl .= '?client_id=' . urlencode($_GET['client_id']);
                                    }

                                    // Check if user is Super Admin
                                    $isSuperAdmin = ($user['system_role'] === 'super_admin' || $user['user_role'] === 'Super Admin');
                                    $disabledClass = $isSuperAdmin ? 'disabled' : '';
                                    $disabledStyle = $isSuperAdmin ? 'style="pointer-events: none; opacity: 0.5; cursor: not-allowed;"' : '';
                                    $disabledTitle = $isSuperAdmin ? Localization::translate('user_grid_edit_disabled') : Localization::translate('user_grid_edit_user');
                                    ?>
                                    <button type="button"
                                            class="btn theme-btn-primary <?= $disabledClass; ?> <?= $isSuperAdmin ? '' : 'edit-user-btn'; ?>"
                                            <?= $disabledStyle; ?>
                                            title="<?= $disabledTitle; ?>"
                                            <?= $isSuperAdmin ? 'disabled' : 'data-user-id="' . $encryptedId . '"'; ?>>
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- ✅ Lock/Unlock Button -->
                                    <?php if ($user['locked_status'] == 1): ?>
                                        <a href="<?= $isSuperAdmin ? '#' : '#'; ?>"
                                            class="btn theme-btn-warning <?= $isSuperAdmin ? 'disabled' : 'unlock-user'; ?>"
                                            <?= $isSuperAdmin ? $disabledStyle : ''; ?>
                                            <?= $isSuperAdmin ? '' : 'data-id="' . IdEncryption::encrypt($user['profile_id']) . '"'; ?>
                                            <?= $isSuperAdmin ? '' : 'data-name="' . htmlspecialchars($user['full_name']) . '"'; ?>
                                            <?= $isSuperAdmin ? '' : 'data-title="' . htmlspecialchars($user['full_name']) . '"'; ?>
                                            title="<?= $isSuperAdmin ? Localization::translate('user_grid_unlock_disabled') : Localization::translate('user_grid_unlock_user'); ?>">
                                            <i class="fas fa-lock-open"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= $isSuperAdmin ? '#' : '#'; ?>"
                                            class="btn theme-btn-danger <?= $isSuperAdmin ? 'disabled' : 'lock-user'; ?>"
                                            <?= $isSuperAdmin ? $disabledStyle : ''; ?>
                                            <?= $isSuperAdmin ? '' : 'data-id="' . IdEncryption::encrypt($user['profile_id']) . '"'; ?>
                                            <?= $isSuperAdmin ? '' : 'data-name="' . htmlspecialchars($user['full_name']) . '"'; ?>
                                            <?= $isSuperAdmin ? '' : 'data-title="' . htmlspecialchars($user['full_name']) . '"'; ?>
                                            title="<?= $isSuperAdmin ? Localization::translate('user_grid_lock_disabled') : Localization::translate('user_grid_lock_user'); ?>">
                                            <i class="fas fa-lock"></i>
                                        </a>
                                    <?php endif; ?>

                                    <!-- ✅ Delete Button -->
                                    <a href="#"
                                        class="btn theme-btn-danger <?= $isSuperAdmin ? 'disabled' : 'delete-user'; ?>"
                                        <?= $isSuperAdmin ? $disabledStyle : ''; ?>
                                        <?= $isSuperAdmin ? '' : 'data-id="' . IdEncryption::encrypt($user['profile_id']) . '"'; ?>
                                        <?= $isSuperAdmin ? '' : 'data-name="' . htmlspecialchars($user['full_name']) . '"'; ?>
                                        <?= $isSuperAdmin ? '' : 'data-title="' . htmlspecialchars($user['full_name']) . '"'; ?>
                                        title="<?= $isSuperAdmin ? Localization::translate('user_grid_delete_disabled') : Localization::translate('user_grid_delete_user'); ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center"><?= Localization::translate('user_grid_no_users_found'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- ✅ Pagination -->
        <div id="paginationContainer" class="pagination-container">
            <?php if ($totalUsers > 10): ?>
                <nav>
                    <ul class="pagination justify-content-center" id="paginationList">
                        <li class="page-item">
                            <a class="page-link" href="#" data-page="1">«
                                <?= Localization::translate('pagination_prev'); ?></a>
                        </li>

                        <li class="page-item active">
                            <a class="page-link" href="#" data-page="1">1</a>
                        </li>

                        <li class="page-item">
                            <a class="page-link" href="#" data-page="2"><?= Localization::translate('pagination_next'); ?>
                                »</a>
                        </li>
                    </ul>
                </nav>
            <?php elseif ($totalUsers > 0): ?>
                <!-- Show total count when no pagination needed -->
                <div class="text-center text-muted small">
                    Showing all <?= $totalUsers; ?> user<?= $totalUsers != 1 ? 's' : ''; ?>
                </div>
            <?php endif; ?>
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
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modal functionality after DOM is loaded
    initializeUserModals();
});

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
                    if (form && typeof initializeEditUserForm === 'function') {
                        initializeEditUserForm();
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
</script>

<!-- Custom Field Modal removed - now managed in Settings -->

<?php include 'includes/footer.php'; ?>

<!-- ✅ Load JavaScript files AFTER Bootstrap is loaded -->
<script src="<?= UrlHelper::url('public/js/modules/user_confirmations.js') ?>"></script>
<script src="<?= UrlHelper::url('public/js/user_management.js') ?>"></script>
<script src="<?= UrlHelper::url('public/js/add_user_validation.js') ?>"></script>
<script src="<?= UrlHelper::url('public/js/edit_user_validation.js') ?>"></script>
