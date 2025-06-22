<?php
// views/manage_portal.php
require_once 'core/UrlHelper.php';
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
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('users') ?>'">
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
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('vlr') ?>'">
                            <h5><i class="fas fa-file-alt"></i> <?= Localization::translate('vlr'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('manage_vlr'); ?></small></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ Social Tab -->
            <div class="tab-pane fade card shadow-lg p-4" id="social">
                <h3 class="text-purple"><?= Localization::translate('social'); ?></h3>
                <p class="text-muted mb-4">Manage social features and engagement tools for your learners</p>

                <div class="row">
                    <!-- Opinion Poll Management -->
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="window.location.href='<?= UrlHelper::url('opinion-polls') ?>'">
                            <h5><i class="fas fa-poll text-purple"></i> Opinion Poll Management</h5>
                            <p><small class="text-muted">Create and manage opinion polls for learner engagement</small></p>
                            <div class="mt-2">
                                <span class="badge bg-success">Active</span>
                                <span class="badge bg-light text-dark">New Feature</span>
                            </div>
                        </div>
                    </div>

                    <!-- Announcement Management -->
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="window.location.href='<?= UrlHelper::url('announcements') ?>'">
                            <h5><i class="fas fa-bullhorn text-purple"></i> Announcement Management</h5>
                            <p><small class="text-muted">Create and manage announcements for your organization</small></p>
                            <div class="mt-2">
                                <span class="badge bg-success">Active</span>
                                <span class="badge bg-light text-dark">New Feature</span>
                            </div>
                        </div>
                    </div>

                    <!-- Event Management -->
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="window.location.href='<?= UrlHelper::url('events') ?>'">
                            <h5><i class="fas fa-calendar-alt text-purple"></i> Event Management</h5>
                            <p><small class="text-muted">Create and manage events, webinars, and live sessions</small></p>
                            <div class="mt-2">
                                <span class="badge bg-success">Active</span>
                                <span class="badge bg-light text-dark">New Feature</span>
                            </div>
                        </div>
                    </div>

                    <!-- Discussion Forums (Coming Soon) -->
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="alert('Discussion Forums - Coming Soon')">
                            <h5><i class="fas fa-comments text-muted"></i> Discussion Forums</h5>
                            <p><small class="text-muted">Enable course discussions and Q&A forums</small></p>
                            <div class="mt-2">
                                <span class="badge bg-secondary">Coming Soon</span>
                            </div>
                        </div>
                    </div>

                    <!-- Social Learning (Coming Soon) -->
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="alert('Social Learning - Coming Soon')">
                            <h5><i class="fas fa-users text-muted"></i> Social Learning</h5>
                            <p><small class="text-muted">Peer-to-peer learning and collaboration tools</small></p>
                            <div class="mt-2">
                                <span class="badge bg-secondary">Coming Soon</span>
                            </div>
                        </div>
                    </div>

                    <!-- Leaderboards (Coming Soon) -->
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="alert('Leaderboards - Coming Soon')">
                            <h5><i class="fas fa-trophy text-muted"></i> Leaderboards</h5>
                            <p><small class="text-muted">Gamification and achievement tracking</small></p>
                            <div class="mt-2">
                                <span class="badge bg-secondary">Coming Soon</span>
                            </div>
                        </div>
                    </div>

                    <!-- Social Feed (News Wall) -->
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="window.location.href='<?= UrlHelper::url('feed') ?>'">
                            <h5><i class="fas fa-rss text-purple"></i> Social Feed (News Wall)</h5>
                            <p><small class="text-muted">Share updates, media, and discussions in a community feed</small></p>
                            <div class="mt-2">
                                <span class="badge bg-success">Active</span>
                                <span class="badge bg-light text-dark">New Feature</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ Settings Tab -->
            <div class="tab-pane fade card shadow-lg p-4" id="settings">
                <h3 class="text-purple"><?= Localization::translate('settings'); ?></h3>

                <!-- ✅ User Configuration Section -->
                <h4 class="text-purple mt-4"><?= Localization::translate('user_configuration'); ?></h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('settings/custom-fields') ?>'">
                            <h5><i class="fas fa-cogs"></i> <?= Localization::translate('custom_fields_management'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('create_edit_delete_custom_fields'); ?></small></p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='index.php?controller=UserSettingsController'">
                            <h5><i class="fas fa-user-shield"></i> <?= Localization::translate('user_roles_permissions'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('manage_user_roles_permissions'); ?></small></p>
                        </div>
                    </div>
                </div>

                <!-- ✅ System Configuration Section -->
                <h4 class="text-purple mt-4"><?= Localization::translate('system_configuration'); ?></h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="alert('General Settings - Coming Soon')">
                            <h5><i class="fas fa-sliders-h"></i> <?= Localization::translate('general_settings'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('system_wide_settings'); ?></small></p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="alert('Security Settings - Coming Soon')">
                            <h5><i class="fas fa-shield-alt"></i> <?= Localization::translate('security_settings'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('security_configuration'); ?></small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<?php include 'includes/footer.php'; ?>
