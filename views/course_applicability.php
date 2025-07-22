<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/header.php';
require_once 'includes/navbar.php';
require_once 'includes/sidebar.php';
?>
<div class="main-content container mt-4">
    <h1 class="page-title text-purple mb-4">
        <?= Localization::translate('course_applicability'); ?>
    </h1>
    <div class="card mb-4">
        <div class="card-body">
            <form id="courseApplicabilityForm">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="courseSelect" class="form-label">
                            <?= Localization::translate('select_course'); ?>
                        </label>
                        <input type="text" id="courseAutocomplete" class="form-control" placeholder="<?= Localization::translate('search_courses'); ?>">
                        <input type="hidden" id="courseSelect" name="course_id">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">
                            <?= Localization::translate('applicability_type'); ?>
                        </label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="applicability_type" id="applicabilityAll" value="all" checked>
                                <label class="form-check-label" for="applicabilityAll">
                                    <?= Localization::translate('applicable_to_all_users'); ?>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="applicability_type" id="applicabilityCustomField" value="custom_field">
                                <label class="form-check-label" for="applicabilityCustomField">
                                    <?= Localization::translate('applicability_by_custom_field'); ?>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="applicability_type" id="applicabilityUser" value="user">
                                <label class="form-check-label" for="applicabilityUser">
                                    <?= Localization::translate('applicability_specific_users'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-3 d-none" id="customFieldSection">
                    <div class="col-md-6">
                        <label for="customFieldSelect" class="form-label">
                            <?= Localization::translate('select_custom_field'); ?>
                        </label>
                        <select id="customFieldSelect" name="custom_field_id" class="form-control">
                            <option value=""><?= Localization::translate('select_custom_field'); ?></option>
                            <?php foreach ($customFields as $field): ?>
                                <option value="<?= $field['id']; ?>">
                                    <?= htmlspecialchars($field['field_label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="customFieldValueSelect" class="form-label">
                            <?= Localization::translate('select_custom_field_value'); ?>
                        </label>
                        <select id="customFieldValueSelect" name="custom_field_value" class="form-control">
                            <option value=""><?= Localization::translate('select_custom_field_value'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3 d-none" id="userSection">
                    <div class="col-md-12">
                        <label for="userSelect" class="form-label">
                            <?= Localization::translate('select_users'); ?>
                        </label>
                        <input type="text" id="userAutocomplete" class="form-control mb-2" placeholder="<?= Localization::translate('search_users'); ?>">
                        <div id="userDropdown" class="autocomplete-dropdown list-group position-absolute w-100" style="z-index:1000; display:none;"></div>
                        <div id="userCheckboxList" class="border rounded p-2" style="max-height:220px; overflow-y:auto;"></div>
                        <div id="selectedUsersSummary" class="mt-2"></div>
                        <input type="hidden" id="userSelect" name="user_ids[]">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <button type="submit" class="btn theme-btn-primary">
                            <i class="fas fa-plus me-1"></i> <?= Localization::translate('assign_applicability'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">
                <?= Localization::translate('current_applicability_rules'); ?>
            </h5>
            <div id="applicabilityRulesList">
                <!-- Applicability rules will be loaded here via JS -->
            </div>
        </div>
    </div>
</div>
<?php
// Render courses as a JS array for autocomplete
$jsCourses = array_map(function($c) {
    return [
        'id' => $c['id'],
        'name' => $c['name']
    ];
}, $courses);
?>
<script>
window.coursesListData = <?= json_encode($jsCourses); ?>;
</script>
<?php
// Render users as a JS array for autocomplete
$jsUsers = array_map(function($u) {
    return [
        'id' => $u['id'],
        'name' => $u['full_name'],
        'email' => $u['email']
    ];
}, $users);
?>
<script>
window.usersListData = <?= json_encode($jsUsers); ?>;
</script>
<script src="/Unlockyourskills/public/js/course_applicability.js"></script>
<?php include 'includes/footer.php'; ?> 