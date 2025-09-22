<?php
// views/manage_portal.php
require_once 'core/UrlHelper.php';
require_once 'models/UserRoleModel.php';
$userRoleModel = new UserRoleModel();
$currentUser = $_SESSION['user'] ?? null;
$canAccessUserManagement = false;
if ($currentUser) {
    $canAccessUserManagement = $userRoleModel->hasPermission($currentUser['id'], 'user_management', 'access', $currentUser['client_id']);
}
$canAccessCourseManagement = false;
if ($currentUser) {
    $canAccessCourseManagement = $userRoleModel->hasPermission($currentUser['id'], 'course_management', 'access', $currentUser['client_id']);
}
$canAccessSocialFeed = false;
if ($currentUser) {
    $canAccessSocialFeed = $userRoleModel->hasPermission($currentUser['id'], 'social_feed', 'access', $currentUser['client_id']);
}
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
                <a class="nav-link" id="reports-tab" data-toggle="tab" href="#reports">
                    <i class="fas fa-chart-bar"></i> <?= Localization::translate('reports'); ?>
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
                    <?php if ($canAccessUserManagement): ?>
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('users') ?>'">
                            <h5><i class="fas fa-user-cog"></i> <?= Localization::translate('user_management'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('create_edit_remove_user'); ?></small></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" id="userSettingsCard" style="cursor: pointer;">
                            <h5><i class="fas fa-user-cog"></i> <?= Localization::translate('user_settings'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('user_settings_description'); ?></small></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ Course Details Tab -->
            <div class="tab-pane fade card shadow-lg p-4" id="course-details">
                <h3 class="text-purple"><?= Localization::translate('course_details'); ?></h3>
                
                <div class="row">
                    <?php if ($canAccessCourseManagement): ?>
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('course-management') ?>'">
                            <h5><i class="fas fa-chalkboard-teacher"></i> <?= Localization::translate('course_creation.title'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('create_courses'); ?></small></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('course-applicability') ?>'">
                            <h5><i class="fas fa-tasks"></i> <?= Localization::translate('course_applicability'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('define_course_applicability'); ?></small></p>
                        </div>
                    </div>
                </div>

                <h3 class="text-purple mt-4"><?= Localization::translate('course_categories.title'); ?></h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('course-categories') ?>'">
                            <h5><i class="fas fa-tags"></i> <?= Localization::translate('category'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('manage_categories'); ?></small></p>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='<?php echo UrlHelper::url('/course-subcategories'); ?>'">
                            <h5><i class="fas fa-layer-group"></i> <?= Localization::translate('sub_category'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('define_sub_categories'); ?></small></p>
                        </div>
                    </div>
                </div>

                <h3 class="text-purple mt-4"><?= Localization::translate('course_content'); ?></h3>
                <div class="row">
                    <!-- VLR Management -->
                    <?php if (canAccess('vlr')): ?>
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="window.location.href='<?= UrlHelper::url('vlr') ?>'">
                            <h5><i class="fas fa-layer-group text-purple"></i> <?= Localization::translate('vlr_management'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('vlr_management_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-success"><?= Localization::translate('active_badge'); ?></span>
                                <span class="badge bg-light text-dark"><?= Localization::translate('new_feature_badge'); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <h3 class="text-purple mt-4">Assessment Details</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="window.location.href='<?= UrlHelper::url('assessment-details') ?>'">
                            <h5><i class="fas fa-clipboard-check text-purple"></i> Increase Assessment Attempts</h5>
                            <p><small class="text-muted">Manage and increase assessment attempts for users</small></p>
                            <div class="mt-2">
                                <span class="badge bg-success"><?= Localization::translate('active_badge'); ?></span>
                                <span class="badge bg-light text-dark"><?= Localization::translate('new_feature_badge'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ✅ Social Tab -->
            <div class="tab-pane fade card shadow-lg p-4" id="social">
                <h3 class="text-purple"><?= Localization::translate('social'); ?></h3>
                <p class="text-muted mb-4"><?= Localization::translate('social_features_description'); ?></p>

                <div class="row">
                    <!-- Opinion Poll Management -->
                    <?php if (canAccess('opinion_polls')): ?>
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="window.location.href='<?= UrlHelper::url('opinion-polls') ?>'">
                            <h5><i class="fas fa-poll text-purple"></i> Opinion Poll Management</h5>
                            <p><small class="text-muted"><?= Localization::translate('opinion_poll_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-success"><?= Localization::translate('active_badge'); ?></span>
                                <span class="badge bg-light text-dark"><?= Localization::translate('new_feature_badge'); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Announcement Management -->
                    <?php if (canAccess('announcements')): ?>
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="window.location.href='<?= UrlHelper::url('announcements') ?>'">
                            <h5><i class="fas fa-bullhorn text-purple"></i> Announcement Management</h5>
                            <p><small class="text-muted"><?= Localization::translate('announcement_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-success"><?= Localization::translate('active_badge'); ?></span>
                                <span class="badge bg-light text-dark"><?= Localization::translate('new_feature_badge'); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Event Management -->
                    <?php if (canAccess('events')): ?>
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="window.location.href='<?= UrlHelper::url('events') ?>'">
                            <h5><i class="fas fa-calendar-alt text-purple"></i> Event Management</h5>
                            <p><small class="text-muted"><?= Localization::translate('event_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-success"><?= Localization::translate('active_badge'); ?></span>
                                <span class="badge bg-light text-dark"><?= Localization::translate('new_feature_badge'); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Discussion Forums (Coming Soon) -->
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="alert('<?= Localization::translate('discussion_forums'); ?> - <?= Localization::translate('coming_soon'); ?>')">
                            <h5><i class="fas fa-comments text-muted"></i> <?= Localization::translate('discussion_forums'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('discussion_forums_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-secondary"><?= Localization::translate('coming_soon'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Social Learning (Coming Soon) -->
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="alert('<?= Localization::translate('social_learning'); ?> - <?= Localization::translate('coming_soon'); ?>')">
                            <h5><i class="fas fa-users text-muted"></i> <?= Localization::translate('social_learning'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('social_learning_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-secondary"><?= Localization::translate('coming_soon'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Leaderboards (Coming Soon) -->
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="alert('<?= Localization::translate('leaderboards'); ?> - <?= Localization::translate('coming_soon'); ?>')">
                            <h5><i class="fas fa-trophy text-muted"></i> <?= Localization::translate('leaderboards'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('leaderboards_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-secondary"><?= Localization::translate('coming_soon'); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($canAccessSocialFeed): ?>
                    <!-- Social Feed (News Wall) -->
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="window.location.href='<?= UrlHelper::url('feed') ?>'">
                            <h5><i class="fas fa-rss text-purple"></i> Social Feed (News Wall)</h5>
                            <p><small class="text-muted"><?= Localization::translate('social_feed_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-success"><?= Localization::translate('active_badge'); ?></span>
                                <span class="badge bg-light text-dark"><?= Localization::translate('new_feature_badge'); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ✅ Reports Tab -->
            <div class="tab-pane fade card shadow-lg p-4" id="reports">
                <!-- ✅ User Reports Section -->
                <h4 class="text-purple mt-4"><?= Localization::translate('user_reports'); ?></h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('reports/user-progress') ?>'">
                            <h5><i class="fas fa-user-chart text-purple"></i> <?= Localization::translate('user_progress_report'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('user_progress_report_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-success"><?= Localization::translate('active_badge'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('reports/user-activity') ?>'">
                            <h5><i class="fas fa-user-clock text-purple"></i> <?= Localization::translate('user_activity_report'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('user_activity_report_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-success"><?= Localization::translate('active_badge'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✅ Course Reports Section -->
                <h4 class="text-purple mt-4"><?= Localization::translate('course_reports'); ?></h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('reports/course-completion') ?>'">
                            <h5><i class="fas fa-graduation-cap text-purple"></i> <?= Localization::translate('course_completion_report'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('course_completion_report_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-success"><?= Localization::translate('active_badge'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('reports/assessment-results') ?>'">
                            <h5><i class="fas fa-clipboard-check text-purple"></i> <?= Localization::translate('assessment_results_report'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('assessment_results_report_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-success"><?= Localization::translate('active_badge'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ✅ Analytics Section -->
                <h4 class="text-purple mt-4"><?= Localization::translate('analytics'); ?></h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('reports/learning-analytics') ?>'">
                            <h5><i class="fas fa-chart-line text-purple"></i> <?= Localization::translate('learning_analytics'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('learning_analytics_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-success"><?= Localization::translate('active_badge'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('reports/engagement-metrics') ?>'">
                            <h5><i class="fas fa-heart text-purple"></i> <?= Localization::translate('engagement_metrics'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('engagement_metrics_description'); ?></small></p>
                            <div class="mt-2">
                                <span class="badge bg-success"><?= Localization::translate('active_badge'); ?></span>
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
                        <div class="card user-box shadow-sm" onclick="location.href='<?= UrlHelper::url('user-roles') ?>'">
                            <h5><i class="fas fa-user-shield"></i> <?= Localization::translate('user_roles_permissions'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('manage_user_roles_permissions'); ?></small></p>
                        </div>
                    </div>
                </div>

                <!-- ✅ System Configuration Section -->
                <h4 class="text-purple mt-4"><?= Localization::translate('system_configuration'); ?></h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="alert('<?= Localization::translate('general_settings_coming_soon'); ?>')">
                            <h5><i class="fas fa-sliders-h"></i> <?= Localization::translate('general_settings'); ?></h5>
                            <p><small class="text-muted"><?= Localization::translate('system_wide_settings'); ?></small></p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card user-box shadow-sm" onclick="alert('<?= Localization::translate('security_settings_coming_soon'); ?>')">
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

<script>
// User Settings card event listener
document.addEventListener('DOMContentLoaded', function() {
    const userSettingsCard = document.getElementById('userSettingsCard');
    if (userSettingsCard) {
        userSettingsCard.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            alert('<?= Localization::translate('user_settings_coming_soon'); ?>');
        });
    }
});
</script>
