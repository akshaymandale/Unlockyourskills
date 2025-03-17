<?php
// views/manage_portal.php
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container mt-4">
        <h1 class="page-title text-purple"><?= Localization::translate('manage_portal'); ?></h1>

        <!-- ✅ Tabs Section -->
        <ul class="nav nav-tabs custom-tabs" id="managePortalTabs">
            <li class="nav-item">
                <a class="nav-link active" id="user-details-tab" data-toggle="tab" href="#user-details">
                    <i class="fas fa-users"></i> <?= Localization::translate('user_details'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="course-details-tab" data-toggle="tab" href="#course-details">
                    <i class="fas fa-book"></i> <?= Localization::translate('course_details'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="social-tab" data-toggle="tab" href="#social">
                    <i class="fas fa-share-alt"></i> <?= Localization::translate('social'); ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="settings-tab" data-toggle="tab" href="#settings">
                    <i class="fas fa-cog"></i> <?= Localization::translate('settings'); ?>
                </a>
            </li>
        </ul>

        <!-- ✅ Tab Content Section -->
        <div class="tab-content mt-3">
            <!-- ✅ User Details Tab -->
            <div class="tab-pane fade show active card shadow-lg p-4" id="user-details">
                <h3 class="text-purple"><?= Localization::translate('user_details'); ?></h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=UserManagementController'">
                            <h5><i class="fas fa-user-cog"></i> <?= Localization::translate('user_management'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('create_edit_remove_user'); ?></small></p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=UserSettingsController'">
                            <h5><i class="fas fa-user-shield"></i> <?= Localization::translate('user_settings'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('manage_user_roles_permissions'); ?></small></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ Course Details Tab -->
            <div class="tab-pane fade card shadow-lg p-4" id="course-details">
                <h3 class="text-purple"><?= Localization::translate('course_details'); ?></h3>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=CourseCreationController'">
                            <h5><i class="fas fa-chalkboard-teacher"></i> <?= Localization::translate('course_creation'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('create_courses'); ?></small></p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=CourseModuleController'">
                            <h5><i class="fas fa-book-open"></i> <?= Localization::translate('course_module_creation'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('organize_courses'); ?></small></p>
                        </div>
                    </div>
                </div>

                <h3 class="text-purple mt-4"><?= Localization::translate('course_categories'); ?></h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=CourseCategoryController'">
                            <h5><i class="fas fa-tags"></i> <?= Localization::translate('category'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('manage_categories'); ?></small></p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=SubCategoryController'">
                            <h5><i class="fas fa-layer-group"></i> <?= Localization::translate('sub_category'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('define_sub_categories'); ?></small></p>
                        </div>
                    </div>
                </div>

                <h3 class="text-purple mt-4"><?= Localization::translate('course_content'); ?></h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=VLRController'">
                            <h5><i class="fas fa-file-alt"></i> <?= Localization::translate('vlr'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('manage_vlr'); ?></small></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ Social Tab -->
            <div class="tab-pane fade card shadow-lg p-4" id="social">
                <h3 class="text-purple"><?= Localization::translate('social'); ?></h3>
                <p><?= Localization::translate('social_coming_soon'); ?></p>
            </div>

            <!-- ✅ Settings Tab -->
            <div class="tab-pane fade card shadow-lg p-4" id="settings">
                <h3 class="text-purple"><?= Localization::translate('settings'); ?></h3>
                <p><?= Localization::translate('settings_coming_soon'); ?></p>
            </div>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>
