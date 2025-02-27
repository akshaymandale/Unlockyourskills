<?php
// views/manage_portal.php
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
<div class="container mt-4">
    <h1 class="page-title text-purple">Manage Portal</h1>

    <!-- ✅ Tabs Section -->
    <ul class="nav nav-tabs custom-tabs" id="managePortalTabs">
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
        <!-- ✅ User Details Tab -->
        <div class="tab-pane fade show active card shadow-lg p-4" id="user-details">
            <h3 class="text-purple">User Details</h3>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=UserManagementController'">
                        <h5><i class="fas fa-user-cog"></i> User Management</h5>
                        <p><small class="text-muted">Create, Edit, Remove User</small></p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=UserSettingsController'">
                        <h5><i class="fas fa-user-shield"></i> User Settings</h5>
                        <p><small class="text-muted">Manage User Roles & Permissions</small></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ✅ Course Details Tab -->
        <div class="tab-pane fade card shadow-lg p-4" id="course-details">
            <h3 class="text-purple">Course Details</h3>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=CourseCreationController'">
                        <h5><i class="fas fa-chalkboard-teacher"></i> Course Creation</h5>
                        <p><small class="text-muted">Create eLearning, Classroom, and Assessment Courses</small></p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=CourseModuleController'">
                        <h5><i class="fas fa-book-open"></i> Course Module Creation</h5>
                        <p><small class="text-muted">Organize Courses into Sections</small></p>
                    </div>
                </div>
            </div>

            <h3 class="text-purple mt-4">Course Categories</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=CourseCategoryController'">
                        <h5><i class="fas fa-tags"></i> Category</h5>
                        <p><small class="text-muted">Add and Manage Course Categories</small></p>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=SubCategoryController'">
                        <h5><i class="fas fa-layer-group"></i> Sub-Category</h5>
                        <p><small class="text-muted">Define Course Sub-Categories</small></p>
                    </div>
                </div>
            </div>

            <h3 class="text-purple mt-4">Course Content</h3>
            <div class="row">

                <div class="col-md-6">
                    <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=VLRController'">
                        <h5><i class="fas fa-file-alt"></i> VLR - Virtual Learning Repository</h5>
                        <p><small class="text-muted">Manage SCORM, Assessments, Videos & More</small></p>
                    </div>
                </div>
            </div>
            
        </div>

        <!-- ✅ Social Tab -->
        <div class="tab-pane fade card shadow-lg p-4" id="social">
            <h3 class="text-purple">Social</h3>
            <p>Social settings section coming soon...</p>
        </div>

        <!-- ✅ Settings Tab -->
        <div class="tab-pane fade card shadow-lg p-4" id="settings">
            <h3 class="text-purple">Settings</h3>
            <p>Settings section coming soon...</p>
        </div>
    </div>
</div>
</div>

<?php include 'includes/footer.php'; ?>
