<?php
// views/user_management.php
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container add-user-container">
        <h1 class="page-title text-purple"><?= Localization::translate('user_management_title'); ?></h1>

        <!-- ✅ Filters & Search Section -->
        <div class="container-fluid mb-3">
            <div class="row justify-content-between align-items-center g-3">

                <!-- Filter Multiselect on the left -->
                <div class="col-md-auto">
                    <select class="form-select form-select-sm" multiple
                        title="<?= Localization::translate('filters_select_options'); ?>">
                        <option value="profile_id"><?= Localization::translate('filters_profile_id'); ?></option>
                        <option value="full_name"><?= Localization::translate('filters_full_name'); ?></option>
                        <option value="email"><?= Localization::translate('filters_email'); ?></option>
                        <option value="contact_number"><?= Localization::translate('filters_contact_number'); ?>
                        </option>
                        <option value="user_status"><?= Localization::translate('filters_user_status'); ?></option>
                        <option value="locked_status"><?= Localization::translate('filters_locked_status'); ?></option>
                    </select>
                </div>

                <!-- Search Bar in the middle -->
                <div class="col-md-auto">
                    <div class="input-group input-group-sm">
                        <input type="text" id="searchInput" class="form-control"
                            placeholder="<?= Localization::translate('filters_search_placeholder'); ?>"
                            title="<?= Localization::translate('filters_search'); ?>">
                        <button type="submit" id="searchButton" class="btn btn-outline-secondary"
                            title="<?= Localization::translate('filters_search'); ?>">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>

                <!-- Add User Button -->
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-primary add-user-btn"
                        title="<?= Localization::translate('buttons_add_user_tooltip'); ?>">
                        <?= Localization::translate('buttons_add_user'); ?>
                    </button>
                </div>

                <!-- Import User Button with Icon -->
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-primary"
                        onclick="window.location.href='index.php?controller=UserManagementController&action=import'"
                        title="<?= Localization::translate('buttons_import_user_tooltip'); ?>">
                        <i class="fas fa-upload me-1"></i> <?= Localization::translate('buttons_import_user'); ?>
                    </button>
                </div>

            </div>
        </div>


        <!-- ✅ User Grid View -->
        <table class="table table-bordered">
            <thead class="user-grid">
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
            <tbody>
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
                                <a href="index.php?controller=UserManagementController&action=editUser&id=<?= $user['profile_id']; ?>"
                                    class="btn btn-sm theme-btn-primary edit-btn"
                                    title="<?= Localization::translate('user_grid_edit_user'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <!-- ✅ Lock/Unlock Button -->
                                <?php if ($user['locked_status'] == 1): ?>
                                    <a href="index.php?controller=UserManagementController&action=toggleLock&id=<?= $user['profile_id']; ?>&status=0"
                                        class="btn btn-sm theme-btn-warning lock-btn"
                                        title="<?= Localization::translate('user_grid_unlock_user'); ?>"
                                        onclick="return confirm('<?= Localization::translate('user_grid_unlock_confirm'); ?>');">
                                        <i class="fas fa-lock-open"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="index.php?controller=UserManagementController&action=toggleLock&id=<?= $user['profile_id']; ?>&status=1"
                                        class="btn btn-sm theme-btn-danger lock-btn"
                                        title="<?= Localization::translate('user_grid_lock_user'); ?>"
                                        onclick="return confirm('<?= Localization::translate('user_grid_lock_confirm'); ?>');">
                                        <i class="fas fa-lock"></i>
                                    </a>
                                <?php endif; ?>

                                <!-- ✅ Delete Button -->
                                <a href="index.php?controller=UserManagementController&action=deleteUser&id=<?= $user['profile_id']; ?>"
                                    class="btn btn-sm theme-btn-danger delete-btn"
                                    title="<?= Localization::translate('user_grid_delete_user'); ?>"
                                    onclick="return confirm('<?= Localization::translate('user_grid_delete_confirm'); ?>');">
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

        <!-- ✅ Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($page > 5): ?>
                        <li class="page-item">
                            <a class="page-link" href="index.php?controller=UserManagementController&page=<?= $page - 5; ?>">«
                                <?= Localization::translate('pagination_prev'); ?></a>
                        </li>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $startPage + 4);
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link"
                                href="index.php?controller=UserManagementController&page=<?= $i; ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page + 5 <= $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link"
                                href="index.php?controller=UserManagementController&page=<?= $page + 5; ?>"><?= Localization::translate('pagination_next'); ?>
                                »</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>



<?php include 'includes/footer.php'; ?>