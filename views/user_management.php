<?php
// views/user_management.php
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container add-user-container user-management" data-user-page="true">
        <h1 class="page-title text-purple"><?= Localization::translate('user_management_title'); ?></h1>

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
                            <?php
                            // Check if user limit is reached
                            $addUserDisabled = '';
                            $addUserText = Localization::translate('buttons_add_user');
                            $addUserOnclick = "window.location.href='index.php?controller=UserManagementController&action=addUser'";
                            $addUserTitle = Localization::translate('buttons_add_user_tooltip');

                            if ($userLimitStatus && !$userLimitStatus['canAdd']) {
                                $addUserDisabled = 'disabled';
                                $addUserText .= ' (Limit Reached)';
                                $addUserOnclick = '';
                                $addUserTitle = 'User limit reached: ' . $userLimitStatus['current'] . '/' . $userLimitStatus['limit'];
                            }
                            ?>
                            <button type="button"
                                    class="btn btn-sm btn-primary add-user-btn"
                                    onclick="<?= $addUserOnclick; ?>"
                                    title="<?= $addUserTitle; ?>"
                                    <?= $addUserDisabled; ?>>
                                <i class="fas fa-plus me-1"></i><?= $addUserText; ?>
                            </button>
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
                                    <a href="index.php?controller=UserManagementController&action=editUser&id=<?= $user['profile_id']; ?>"
                                        class="btn theme-btn-primary"
                                        title="<?= Localization::translate('user_grid_edit_user'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <!-- ✅ Lock/Unlock Button -->
                                    <?php if ($user['locked_status'] == 1): ?>
                                        <a href="#" class="btn theme-btn-warning unlock-user"
                                            data-id="<?= $user['profile_id']; ?>"
                                            data-name="<?= htmlspecialchars($user['full_name']); ?>"
                                            data-title="<?= htmlspecialchars($user['full_name']); ?>"
                                            title="<?= Localization::translate('user_grid_unlock_user'); ?>">
                                            <i class="fas fa-lock-open"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="#" class="btn theme-btn-danger lock-user"
                                            data-id="<?= $user['profile_id']; ?>"
                                            data-name="<?= htmlspecialchars($user['full_name']); ?>"
                                            data-title="<?= htmlspecialchars($user['full_name']); ?>"
                                            title="<?= Localization::translate('user_grid_lock_user'); ?>">
                                            <i class="fas fa-lock"></i>
                                        </a>
                                    <?php endif; ?>

                                    <!-- ✅ Delete Button -->
                                    <a href="#" class="btn theme-btn-danger delete-user"
                                        data-id="<?= $user['profile_id']; ?>"
                                        data-name="<?= htmlspecialchars($user['full_name']); ?>"
                                        data-title="<?= htmlspecialchars($user['full_name']); ?>"
                                        title="<?= Localization::translate('user_grid_delete_user'); ?>">
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

<!-- ✅ User Management Confirmations -->
<script src="public/js/modules/user_confirmations.js"></script>
<script src="public/js/user_management.js"></script>

<?php include 'includes/footer.php'; ?>
