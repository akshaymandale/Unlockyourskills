<?php
// views/user_management.php
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="container">
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
        <input type="text" id="searchInput" class="search-bar" placeholder="Search by Profile ID, Name, Email, Contact...">
        <button class="add-user-btn">+ Add User</button>
        <button class="import-user-btn">📥 Import</button>
    </div>
    
    <!-- ✅ User Grid View -->
    <table class="user-grid">
        <thead>
            <tr>
                <th>Profile ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Contact Number</th>
                <th>User Status</th>
                <th>Locked Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1001</td>
                <td>John Doe</td>
                <td>johndoe@example.com</td>
                <td>+1234567890</td>
                <td>Active</td>
                <td>Unlocked</td>
                <td>
                    <button class="edit-btn">✏️</button>
                    <button class="lock-btn">🔒</button>
                    <button class="delete-btn">🗑️</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
