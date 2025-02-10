<?php
// views/manage_portal.php
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="container add-user-container">
    <h1>Manage Portal</h1>
    
    <!-- ✅ Tabs Section -->
    <ul class="nav nav-tabs" id="managePortalTabs">
        <li class="nav-item">
            <a class="nav-link active" id="user-details-tab" data-toggle="tab" href="#user-details">
                <i class="fas fa-users"></i> User Details
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="course-details-tab" data-toggle="tab" href="#course-details">
                <i class="fas fa-book"></i> Course Details
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="social-tab" data-toggle="tab" href="#social">
                <i class="fas fa-share-alt"></i> Social
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="settings-tab" data-toggle="tab" href="#settings">
                <i class="fas fa-cog"></i> Settings
            </a>
        </li>
    </ul>

    <!-- ✅ Tab Content Section -->
    <div class="tab-content mt-3">
        <div class="tab-pane fade show active" id="user-details">
            <h3>User Details</h3>
            
            <div class="user-section">
                <div class="user-box" onclick="location.href='index.php?controller=UserManagementController'">
                    <h5>User Management</h5>
                    <p><small class="text-muted">Create, Edit, Remove User</small></p>
                </div>
                
                <div class="user-box" onclick="location.href='index.php?controller=UserSettingsController'">
                    <h5>User Settings</h5>
                    <p><small class="text-muted">Manage</small></p>
                </div>
            </div>
        </div>
        
        <div class="tab-pane fade" id="course-details">
            <h3>Course Details</h3>
            
            <div class="user-section">
                <div class="user-box" onclick="location.href='index.php?controller=CourseCreationController'">
                    <h5>Course Creation</h5>
                    <p><small class="text-muted">Create eLearning, Classroom, Assessment type courses</small></p>
                </div>
                
                <div class="user-box" onclick="location.href='index.php?controller=CourseModuleController'">
                    <h5>Course Module Creation</h5>
                    <p><small class="text-muted">Create Course sections</small></p>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="social">
            <h3>Social</h3>
            <p>Social settings section coming soon...</p>
        </div>

        <div class="tab-pane fade" id="settings">
            <h3>Settings</h3>
            <p>Settings section coming soon...</p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
