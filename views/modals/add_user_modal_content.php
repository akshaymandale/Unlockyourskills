<?php
/**
 * Add User Modal Content
 * This file contains the form content for the Add User modal
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../core/UrlHelper.php';
require_once __DIR__ . '/../../core/IdEncryption.php';

// ✅ Determine target client for user creation
$currentUser = $_SESSION['user'] ?? null;
$targetClientId = null;
$clientName = 'DEFAULT';

// Simplified detection: Check if super admin is adding user for specific client
$isFromClientManagement = false;

// Check if super admin is adding user for specific client (from URL parameter)
if (isset($_GET['client_id']) && $currentUser && $currentUser['system_role'] === 'super_admin') {
    $isFromClientManagement = true;
    $targetClientId = $_GET['client_id'];

    // Get client name from database
    require_once __DIR__ . '/../../models/ClientModel.php';
    $clientModel = new ClientModel();
    $client = $clientModel->getClientById($targetClientId);
    $clientName = $client ? $client['client_name'] : 'Unknown Client';

    // Set session context for future requests
    $_SESSION['client_management_context'] = true;
    $_SESSION['target_client_id'] = $targetClientId;
} else {
    // Use current user's client or clear client management context
    $targetClientId = $currentUser['client_id'] ?? null;
    $clientName = $_SESSION['client_code'] ?? 'DEFAULT';

    // Clear client management context
    unset($_SESSION['client_management_context']);
    unset($_SESSION['target_client_id']);
}

// Fetch all countries for the initial dropdown
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$db = $database->connect();

if (!$db) {
    die("Database connection failed.");
}

$stmt = $db->query("SELECT id, name FROM countries");
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user roles and admin role status
require_once __DIR__ . '/../../models/UserModel.php';
$userModel = new UserModel();
$userRoles = $userModel->getClientUserRoles($targetClientId);
$adminRoles = $userModel->getAdminUserRoles($targetClientId);

// Get admin role status for the target client
$adminRoleStatus = null;
if ($targetClientId) {
    $adminRoleStatus = $userModel->getAdminRoleStatus($targetClientId);
}

// Get languages
$languages = $userModel->getLanguages();

// Check if super admin is adding for specific client
$isSuperAdminForClient = $isFromClientManagement;
?>

<?php if ($isFromClientManagement): ?>
    <div class="alert alert-primary mb-3">
        <i class="fas fa-users-cog"></i>
        <strong>Client Management Mode:</strong> Adding Admin user for client <strong><?= htmlspecialchars($clientName); ?></strong>
        <br><small><i class="fas fa-lock"></i> Only Admin role is available when adding users for client management purposes.</small>
    </div>
<?php endif; ?>

<form id="addUserModalForm" method="POST" enctype="multipart/form-data" action="javascript:void(0);">
    <!-- ✅ Tabs Section -->
    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs" id="addUserModalTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="modal-basic-details-tab" data-bs-toggle="tab" data-bs-target="#modal-basic-details" type="button" role="tab" aria-controls="modal-basic-details" aria-selected="true">
                <?= Localization::translate('basic_details'); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="modal-additional-details-tab" data-bs-toggle="tab" data-bs-target="#modal-additional-details" type="button" role="tab" aria-controls="modal-additional-details" aria-selected="false">
                <?= Localization::translate('additional_details'); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="modal-extra-details-tab" data-bs-toggle="tab" data-bs-target="#modal-extra-details" type="button" role="tab" aria-controls="modal-extra-details" aria-selected="false">
                <?= Localization::translate('extra_details'); ?>
            </button>
        </li>
    </ul>

    <!-- Hidden fields -->
    <input type="hidden" name="client_id" value="<?= htmlspecialchars($targetClientId); ?>">
    <input type="hidden" name="target_client_id" value="<?= htmlspecialchars($targetClientId); ?>">

    <!-- Tabs Content -->
    <div class="tab-content mt-3" id="addUserModalTabsContent">
        <div class="tab-pane fade show active" id="modal-basic-details" role="tabpanel" aria-labelledby="modal-basic-details-tab" tabindex="0">
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('profile_id'); ?></label>
                    <input type="text" id="modal_profile_id" name="profile_id" class="form-control" readonly placeholder="Will be auto-generated">
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('full_name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" id="modal_full_name" name="full_name" class="form-control">
                    <div class="invalid-feedback"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('email'); ?> <span class="text-danger">*</span></label>
                    <input type="email" id="modal_email" name="email" class="form-control">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('contact_number'); ?> <span class="text-danger">*</span></label>
                    <input type="text" id="modal_contact_number" name="contact_number" class="form-control">
                    <div class="invalid-feedback"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('gender'); ?></label>
                    <select name="gender" class="form-control">
                        <option value=""><?= Localization::translate('select_gender'); ?></option>
                        <option value="Male"><?= Localization::translate('male'); ?></option>
                        <option value="Female"><?= Localization::translate('female'); ?></option>
                        <option value="Other"><?= Localization::translate('other'); ?></option>
                    </select>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('dob'); ?></label>
                    <input type="date" id="modal_dob" name="dob" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('user_role'); ?> <span class="text-danger">*</span></label>
                    <select id="modal_user_role" name="user_role" class="form-control" <?= $isSuperAdminForClient ? 'readonly style="background-color: #f8f9fa; cursor: not-allowed;"' : ''; ?>>
                        <?php if ($isSuperAdminForClient): ?>
                            <!-- Super admin adding for specific client - only Admin role -->
                            <?php foreach ($adminRoles as $role): ?>
                                <?php
                                $adminDisabled = '';
                                $adminText = $role['role_name'];
                                if ($adminRoleStatus && !$adminRoleStatus['canAdd']) {
                                    $adminDisabled = 'disabled';
                                    $adminText .= ' (Limit Reached)';
                                }
                                ?>
                                <option value="<?= htmlspecialchars($role['role_name']); ?>" <?= $adminDisabled; ?> selected><?= $adminText; ?></option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Regular admin or super admin not in client context - show all roles -->
                            <option value=""><?= Localization::translate('select_user_role'); ?></option>
                            <?php foreach ($userRoles as $role): ?>
                                <?php
                                $roleDisabled = '';
                                $roleText = $role['role_name'];
                                if ($role['system_role'] === 'admin' && $adminRoleStatus && !$adminRoleStatus['canAdd']) {
                                    $roleDisabled = 'disabled';
                                    $roleText .= ' (Limit Reached)';
                                }
                                ?>
                                <option value="<?= htmlspecialchars($role['role_name']); ?>" <?= $roleDisabled; ?>><?= $roleText; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('profile_expiry_date'); ?></label>
                    <input type="date" id="modal_profile_expiry" name="profile_expiry" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('user_status'); ?>:</label>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="user_status" id="modal_status_active" value="Active" checked>
                            <label class="form-check-label" for="modal_status_active"><?= Localization::translate('active'); ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="user_status" id="modal_status_inactive" value="Inactive">
                            <label class="form-check-label" for="modal_status_inactive"><?= Localization::translate('inactive'); ?></label>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('locked_status'); ?>:</label>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="locked_status" id="modal_locked_yes" value="1">
                            <label class="form-check-label" for="modal_locked_yes"><?= Localization::translate('locked'); ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="locked_status" id="modal_locked_no" value="0" checked>
                            <label class="form-check-label" for="modal_locked_no"><?= Localization::translate('unlocked'); ?></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('leaderboard'); ?>:</label>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="leaderboard" id="modal_leaderboard_yes" value="Yes">
                            <label class="form-check-label" for="modal_leaderboard_yes"><?= Localization::translate('yes'); ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="leaderboard" id="modal_leaderboard_no" value="No" checked>
                            <label class="form-check-label" for="modal_leaderboard_no"><?= Localization::translate('no'); ?></label>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('profile_picture'); ?></label>
                    <input type="file" id="modal_profile_picture" name="profile_picture" accept="image/jpeg, image/png" class="form-control">
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="modal-additional-details" role="tabpanel" aria-labelledby="modal-additional-details-tab" tabindex="0">
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label for="modal_country"><?= Localization::translate('country'); ?></label>
                    <select id="modal_countrySelect" name="country" class="form-control">
                        <option value=""><?= Localization::translate('select_country'); ?></option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= $country['id']; ?>"><?= $country['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label for="modal_state"><?= Localization::translate('state'); ?></label>
                    <select id="modal_stateSelect" name="state" class="form-control" disabled>
                        <option value=""><?= Localization::translate('select_state'); ?></option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label for="modal_city"><?= Localization::translate('city'); ?></label>
                    <select id="modal_citySelect" name="city" class="form-control" disabled>
                        <option value=""><?= Localization::translate('select_city'); ?></option>
                    </select>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('timezone'); ?></label>
                    <select id="modal_timezoneSelect" name="timezone" class="form-control">
                        <option value=""><?= Localization::translate('select_timezone'); ?></option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('language'); ?></label>
                    <select name="language" class="form-control">
                        <option value=""><?= Localization::translate('select_language'); ?></option>
                        <?php if (!empty($languages)): ?>
                            <?php foreach ($languages as $language): ?>
                                <option value="<?= htmlspecialchars($language['language_code']); ?>">
                                    <?= htmlspecialchars($language['language_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('reports_to'); ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="email" id="modal_reports_to" name="reports_to" class="form-control" placeholder="Start typing to search users...">
                        <span class="input-group-text">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                    </div>
                    <div class="invalid-feedback"></div>
                    <small class="form-text text-muted">Type to search and select from existing users</small>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('joining_date'); ?></label>
                    <input type="date" name="joining_date" class="form-control">
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('retirement_date'); ?></label>
                    <input type="date" name="retirement_date" class="form-control">
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="modal-extra-details" role="tabpanel" aria-labelledby="modal-extra-details-tab" tabindex="0">
            <?php
            // Get custom fields for the current client
            require_once __DIR__ . '/../../models/CustomFieldModel.php';
            $customFieldModel = new CustomFieldModel();

            // Only fetch custom fields if we have a valid client ID
            $customFields = [];
            if ($targetClientId && is_numeric($targetClientId)) {
                $customFields = $customFieldModel->getCustomFieldsByClient((int)$targetClientId);
            }

            if (empty($customFields)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-info-circle text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-muted"><?= Localization::translate('custom_fields_no_fields'); ?></h5>
                    <p class="text-muted"><?= Localization::translate('custom_fields_no_fields_description'); ?></p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php
                    $fieldCount = 0;
                    foreach ($customFields as $field):
                        if ($fieldCount % 2 === 0 && $fieldCount > 0): ?>
                            </div><div class="row">
                        <?php endif; ?>

                        <div class="col-lg-6 col-md-6 col-sm-12 form-group mb-3">
                            <label for="modal_custom_field_<?= $field['id']; ?>">
                                <?= htmlspecialchars($field['field_label']); ?>
                                <?php if ($field['is_required']): ?><span class="text-danger">*</span><?php endif; ?>
                            </label>

                            <?php
                            $fieldName = "custom_field_{$field['id']}";
                            $fieldId = "modal_custom_field_{$field['id']}";

                            switch ($field['field_type']):
                                case 'text':
                                case 'email':
                                case 'phone':
                                case 'number': ?>
                                    <input type="<?= $field['field_type']; ?>"
                                           id="<?= $fieldId; ?>"
                                           name="<?= $fieldName; ?>"
                                           class="form-control"
                                           data-required="<?= $field['is_required'] ? '1' : '0'; ?>">
                                    <div class="invalid-feedback"></div>
                                    <?php break;

                                case 'textarea': ?>
                                    <textarea id="<?= $fieldId; ?>"
                                              name="<?= $fieldName; ?>"
                                              class="form-control"
                                              rows="3"
                                              data-required="<?= $field['is_required'] ? '1' : '0'; ?>"></textarea>
                                    <div class="invalid-feedback"></div>
                                    <?php break;

                                case 'select': ?>
                                    <select id="<?= $fieldId; ?>"
                                            name="<?= $fieldName; ?>"
                                            class="form-control"
                                            data-required="<?= $field['is_required'] ? '1' : '0'; ?>">
                                        <option value="">Select an option...</option>
                                        <?php
                                        $options = $field['field_options'];
                                        if (is_string($options)) {
                                            $options = preg_split('/\r\n|\r|\n/', $options);
                                        }
                                        if (is_array($options)):
                                            foreach ($options as $option):
                                                $option = trim($option);
                                                if ($option === '') continue;
                                        ?>
                                            <option value="<?= htmlspecialchars($option); ?>">
                                                <?= htmlspecialchars($option); ?>
                                            </option>
                                        <?php
                                            endforeach;
                                        endif;
                                        ?>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                    <?php break;

                                case 'radio': ?>
                                    <?php if ($field['field_options']): ?>
                                        <div class="mt-2">
                                            <?php foreach ($field['field_options'] as $index => $option): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input"
                                                           type="radio"
                                                           id="<?= $fieldId; ?>_<?= $index; ?>"
                                                           name="<?= $fieldName; ?>"
                                                           value="<?= htmlspecialchars($option); ?>"
                                                           data-required="<?= $field['is_required'] ? '1' : '0'; ?>">
                                                    <label class="form-check-label" for="<?= $fieldId; ?>_<?= $index; ?>">
                                                        <?= htmlspecialchars($option); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php break;

                                case 'checkbox': ?>
                                    <?php if ($field['field_options']): ?>
                                        <div class="mt-2">
                                            <?php foreach ($field['field_options'] as $index => $option): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           id="<?= $fieldId; ?>_<?= $index; ?>"
                                                           name="<?= $fieldName; ?>[]"
                                                           value="<?= htmlspecialchars($option); ?>">
                                                    <label class="form-check-label" for="<?= $fieldId; ?>_<?= $index; ?>">
                                                        <?= htmlspecialchars($option); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php break;

                                case 'file': ?>
                                    <input type="file"
                                           id="<?= $fieldId; ?>"
                                           name="<?= $fieldName; ?>"
                                           class="form-control"
                                           data-required="<?= $field['is_required'] ? '1' : '0'; ?>">
                                    <div class="invalid-feedback"></div>
                                    <?php break;

                                case 'date': ?>
                                    <input type="date"
                                           id="<?= $fieldId; ?>"
                                           name="<?= $fieldName; ?>"
                                           class="form-control"
                                           data-required="<?= $field['is_required'] ? '1' : '0'; ?>">
                                    <div class="invalid-feedback"></div>
                                    <?php break;

                                default: ?>
                                    <input type="text"
                                           id="<?= $fieldId; ?>"
                                           name="<?= $fieldName; ?>"
                                           class="form-control"
                                           data-required="<?= $field['is_required'] ? '1' : '0'; ?>">
                                    <div class="invalid-feedback"></div>
                                    <?php break;
                            endswitch; ?>
                        </div>

                        <?php $fieldCount++; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i><?= Localization::translate('cancel'); ?>
        </button>
        <button type="submit" class="btn btn-primary" id="addUserSubmitButton">
            <i class="fas fa-save me-1"></i><?= Localization::translate('submit'); ?>
        </button>
    </div>
</form>

<script>
// Set required variables for modal validation
window.isSuperAdminForClient = <?= json_encode($isSuperAdminForClient); ?>;
window.addUserSubmitUrl = '<?= UrlHelper::url('users/modal/submit-add') ?>';

// Immediately generate and set profile_id when modal content is loaded
var profileIdField = document.getElementById('modal_profile_id');
if (profileIdField && !profileIdField.value) {
    var prefix = '<?= substr(preg_replace("/[^A-Za-z0-9]/", "", $clientName), 0, 2) ?>'.toUpperCase();
    var timestamp = Date.now().toString().slice(-6);
    var random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    profileIdField.value = prefix + timestamp + random;
}
</script>
