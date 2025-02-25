<?php
// views/user_management.php
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="container add-user-container">
    <h1>User Management</h1>
    
    <!-- ✅ Filters & Search Section -->
    <div class="user-management-toolbar">
        <select class="filter-multiselect" multiple>
            <option value="profile_id">Profile ID</option>
            <option value="full_name">Full Name</option>
            <option value="email">Email</option>
            <option value="contact_number">Contact Number</option>
            <option value="user_status">User Status</option>
            <option value="locked_status">Locked Status</option>
        </select>
        <div class="search-container">
    <input type="text" id="searchInput" class="search-bar" placeholder="Search by Profile ID, Name, Email, Contact...">
    <button type="submit" id="searchButton" class="search-icon">
        <i class="fas fa-search"></i>
    </button>
</div>
        <button class="add-user-btn">+ Add User</button>
        <button class="import-user-btn">📥 Import</button>
    </div>
    
    <!-- ✅ User Grid View -->
    <table class="table table-bordered">
        <thead class="user-grid">
            <tr>
                <th>Profile ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Contact Number</th>
                <th>User Status</th>
                <th>Locked Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['profile_id']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['contact_number']); ?></td>
                        <td>
                            <?php echo ($user['user_status'] == 1) ? 
                                '<span class="badge bg-success">Active</span>' : 
                                '<span class="badge bg-danger">Inactive</span>'; ?>
                        </td>
                        <td>
                            <?php echo ($user['locked_status'] == 1) ? 
                                '<span class="badge bg-warning">Locked</span>' : 
                                '<span class="badge bg-primary">Unlocked</span>'; ?>
                        </td>
                        <td>
                            <!-- ✅ Edit Button (Consistent Theme) -->
                            <a href="index.php?controller=UserManagementController&action=editUser&id=<?php echo $user['profile_id']; ?>" 
                            class="btn btn-sm theme-btn-primary edit-btn" 
                            title="Edit User">
                                <i class="fas fa-edit"></i>
                            </a>

                            <!-- ✅ Lock/Unlock Button (Consistent Theme) -->
                            <?php if ($user['locked_status'] == 1): ?>
                                <a href="index.php?controller=UserManagementController&action=toggleLock&id=<?php echo $user['profile_id']; ?>&status=0" 
                                class="btn btn-sm theme-btn-warning lock-btn" 
                                title="Unlock User"
                                onclick="return confirm('Are you sure you want to unlock this user?');">
                                    <i class="fas fa-lock-open"></i>
                                </a>
                            <?php else: ?>
                                <a href="index.php?controller=UserManagementController&action=toggleLock&id=<?php echo $user['profile_id']; ?>&status=1" 
                                class="btn btn-sm theme-btn-danger lock-btn" 
                                title="Lock User"
                                onclick="return confirm('Are you sure you want to lock this user?');">
                                    <i class="fas fa-lock"></i>
                                </a>
                            <?php endif; ?>

                            <!-- ✅ Delete Button (Soft Delete) -->
                            <a href="index.php?controller=UserManagementController&action=deleteUser&id=<?php echo $user['profile_id']; ?>" 
                            class="btn btn-sm theme-btn-danger delete-btn" 
                            title="Delete User"
                            onclick="return confirm('Are you sure you want to delete this user? This action is reversible.');">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                                
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No users found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
