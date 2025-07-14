
<?php
require_once 'core/UrlHelper.php';
include 'views/includes/header.php';
include 'views/includes/navbar.php';
include 'views/includes/sidebar.php';

if (isset($_SESSION['user']) && $_SESSION['user']['system_role'] === 'super_admin' && isset($client) && $client) {
    echo '<script>window.CURRENT_CLIENT_ID = ' . (int)$client['id'] . ';</script>';
} elseif (isset($_SESSION['user']) && $_SESSION['user']['system_role'] === 'admin' && isset($_SESSION['user']['client_id'])) {
    echo '<script>window.CURRENT_CLIENT_ID = ' . (int)$_SESSION['user']['client_id'] . ';</script>';
}

// Build a lookup array for permissions: $permMatrix[role_id][module_name]['access'|'create'|'edit'|'delete']
$permMatrix = [];
if (!empty($permissions)) {
    foreach ($permissions as $perm) {
        $roleId = $perm['role_id'];
        $module = $perm['module_name'];
        $permMatrix[$roleId][$module] = [
            'access' => !empty($perm['can_access']),
            'create' => !empty($perm['can_create']),
            'edit'   => !empty($perm['can_edit']),
            'delete' => !empty($perm['can_delete'])
        ];
    }
}
?>


<div class="main-content">
    <div class="container mt-4 user-roles-management">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="<?= UrlHelper::url('manage-portal') ?>#settings" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-user-shield me-2"></i>
                User Roles & Permissions
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
                <li class="breadcrumb-item active" aria-current="page">User Roles & Permissions</li>
            </ol>
        </nav>

        <?php if (
    isset($_SESSION['user']) &&
    $_SESSION['user']['system_role'] === 'super_admin' &&
    isset($client) && $client
): ?>
            <div class="alert alert-info mb-3">
                <i class="fas fa-building"></i>
                <strong>Client Management Mode:</strong> Managing roles for client <strong><?= htmlspecialchars($client['client_name']); ?></strong>
                <a href="<?= UrlHelper::url('user-roles') ?>" class="btn btn-sm btn-outline-secondary ms-2">
                    <i class="fas fa-arrow-left"></i> Back to All Clients
                </a>
            </div>
        <?php endif; ?>

        <!-- Client Selection for Super Admin -->
        <?php if (isset($_SESSION['user']) && $_SESSION['user']['system_role'] === 'super_admin' && !empty($clients)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter"></i> Select Client</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <select class="form-select" id="clientSelect" onchange="changeClient(this.value)">
                                <option value="">All Clients</option>
                                <?php foreach ($clients as $clientItem): ?>
                                    <option value="<?= $clientItem['id'] ?>" <?= (isset($client) && $client['id'] == $clientItem['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($clientItem['client_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Roles Management Section -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users-cog"></i> User Roles</h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                    <i class="fas fa-plus"></i> Add New Role
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Role Name</th>
                                <th>System Role</th>
                                <th>Description</th>
                                <th>Users</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
if (!isset($debug_roles)) {
    $debug_roles = $roles;
}
?>
                            <?php foreach ($debug_roles as $role): ?>
                                <tr id="role-row-<?= $role['id'] ?>">
                                    <td><strong><?= htmlspecialchars($role['role_name']) ?></strong></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($role['system_role']) ?></span></td>
                                    <td><?= htmlspecialchars($role['description'] ?? '') ?></td>
                                    <td><span class="badge bg-secondary"><?= $role['user_count'] ?> users</span></td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" 
                                                   onchange="toggleRoleStatus(<?= $role['id'] ?>, this.checked)"
                                                   <?= $role['is_active'] ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="editRole(<?= $role['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($role['user_count'] == 0): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteRole(<?= $role['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Module Permissions Section -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-key"></i> Module Permissions</h5>
                <button type="button" class="btn btn-success btn-sm" onclick="savePermissions()">
                    <i class="fas fa-save"></i> Save Permissions
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th style="min-width: 200px;">Module</th>
                                <?php foreach ($roles as $role): ?>
                                    <th class="text-center" style="min-width: 120px;">
                                        <?= htmlspecialchars($role['role_name']) ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $moduleKey => $module): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="<?= $module['icon'] ?> me-2 text-purple"></i>
                                            <div>
                                                <strong><?= $module['name'] ?></strong>
                                                <br><small class="text-muted"><?= $module['description'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <?php foreach ($roles as $role): ?>
                                        <td class="text-center">
                                            <div class="permission-toggles">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="access_<?= $role['id'] ?>_<?= $moduleKey ?>"
                                                           data-role="<?= $role['id'] ?>" 
                                                           data-module="<?= $moduleKey ?>" 
                                                           data-permission="access"
                                                           <?= (!empty($permMatrix[$role['id']][$moduleKey]['access'])) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="access_<?= $role['id'] ?>_<?= $moduleKey ?>">
                                                        <small>Access</small>
                                                    </label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="create_<?= $role['id'] ?>_<?= $moduleKey ?>"
                                                           data-role="<?= $role['id'] ?>" 
                                                           data-module="<?= $moduleKey ?>" 
                                                           data-permission="create"
                                                           <?= (!empty($permMatrix[$role['id']][$moduleKey]['create'])) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="create_<?= $role['id'] ?>_<?= $moduleKey ?>">
                                                        <small>Create</small>
                                                    </label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="edit_<?= $role['id'] ?>_<?= $moduleKey ?>"
                                                           data-role="<?= $role['id'] ?>" 
                                                           data-module="<?= $moduleKey ?>" 
                                                           data-permission="edit"
                                                           <?= (!empty($permMatrix[$role['id']][$moduleKey]['edit'])) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="edit_<?= $role['id'] ?>_<?= $moduleKey ?>">
                                                        <small>Edit</small>
                                                    </label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="delete_<?= $role['id'] ?>_<?= $moduleKey ?>"
                                                           data-role="<?= $role['id'] ?>" 
                                                           data-module="<?= $moduleKey ?>" 
                                                           data-permission="delete"
                                                           <?= (!empty($permMatrix[$role['id']][$moduleKey]['delete'])) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="delete_<?= $role['id'] ?>_<?= $moduleKey ?>">
                                                        <small>Delete</small>
                                                    </label>
                                                </div>
                                            </div>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRoleModalLabel">
                    <i class="fas fa-plus"></i> Add New Role
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addRoleForm" method="POST" action="<?= UrlHelper::url('user-roles/create') ?>">
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['system_role'] === 'super_admin' && isset($client) && $client): ?>
                    <input type="hidden" name="client_id" value="<?= (int)$client['id'] ?>">
                <?php endif; ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="role_name" class="form-label">Role Name *</label>
                        <input type="text" class="form-control" id="role_name" name="role_name">
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="system_role" class="form-label">System Role *</label>
                        <select class="form-select" id="system_role" name="system_role">
                            <option value="">Select System Role</option>
                            <?php if ($_SESSION['user']['system_role'] === 'super_admin'): ?>
                                <option value="admin">Admin</option>
                            <?php else: ?>
                                <option value="manager">Manager</option>
                                <option value="instructor">Instructor</option>
                                <option value="learner">Learner</option>
                                <option value="guest">Guest</option>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="display_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" value="999" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRoleModalLabel">
                    <i class="fas fa-edit"></i> Edit Role
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editRoleForm" method="POST" action="<?= UrlHelper::url('user-roles/update') ?>">
                <?php if (isset($_SESSION['user']) && $_SESSION['user']['system_role'] === 'super_admin' && isset($client) && $client): ?>
                    <input type="hidden" name="client_id" value="<?= (int)$client['id'] ?>">
                <?php endif; ?>
                <input type="hidden" id="edit_role_id" name="role_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_role_name" class="form-label">Role Name *</label>
                        <input type="text" class="form-control" id="edit_role_name" name="role_name" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_system_role" class="form-label">System Role *</label>
                        <select class="form-select" id="edit_system_role" name="system_role" required>
                            <option value="">Select System Role</option>
                            <?php if ($_SESSION['user']['system_role'] === 'super_admin'): ?>
                                <option value="admin">Admin</option>
                            <?php else: ?>
                                <option value="manager">Manager</option>
                                <option value="instructor">Instructor</option>
                                <option value="learner">Learner</option>
                                <option value="guest">Guest</option>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_display_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="edit_display_order" name="display_order" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Role Confirmation Modal -->
<div class="modal fade" id="deleteRoleModal" tabindex="-1" aria-labelledby="deleteRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteRoleModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger"></i> Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this role? This action cannot be undone.</p>
                <form id="deleteRoleForm" method="POST" action="<?= UrlHelper::url('user-roles/delete') ?>">
                    <input type="hidden" id="delete_role_id" name="role_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="deleteRoleForm" class="btn btn-danger">Delete Role</button>
            </div>
        </div>
    </div>
</div>

<?php include 'views/includes/footer.php'; ?>


<!-- Include User Roles JavaScript -->
<script src="<?= UrlHelper::url('public/js/user_roles.js') ?>"></script>
<script src="<?= UrlHelper::url('public/js/user_roles_validations.js') ?>"></script> 