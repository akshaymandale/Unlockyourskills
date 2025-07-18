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
                        <select id="courseSelect" name="course_id" class="form-control">
                            <option value=""><?= Localization::translate('select_course'); ?></option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id']; ?>">
                                    <?= htmlspecialchars($course['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                        <select id="userSelect" name="user_ids[]" class="form-control" multiple>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id']; ?>">
                                    <?= htmlspecialchars($user['full_name']); ?> (<?= htmlspecialchars($user['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            <?= Localization::translate('hold_ctrl_to_select_multiple'); ?>
                        </small>
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
<script src="/Unlockyourskills/public/js/course_applicability.js"></script>
<?php include 'includes/footer.php'; ?> 