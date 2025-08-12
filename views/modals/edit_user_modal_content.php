<?php
/**
 * Edit User Modal Content
 * This file contains the form content for the Edit User modal
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../core/UrlHelper.php';
require_once __DIR__ . '/../../core/IdEncryption.php';

// Get user ID from parameter
if (!isset($_GET['user_id'])) {
    echo '<div class="alert alert-danger">User ID is required.</div>';
    exit;
}

try {
    $userId = IdEncryption::getId($_GET['user_id']);
} catch (InvalidArgumentException $e) {
    echo '<div class="alert alert-danger">Invalid user ID.</div>';
    exit;
}

// Get user data
require_once __DIR__ . '/../../models/UserModel.php';
$userModel = new UserModel();

$currentUser = $_SESSION['user'] ?? null;
$clientId = null;
if ($currentUser && $currentUser['system_role'] === 'admin') {
    $clientId = $currentUser['client_id'];
}

$user = $userModel->getUserById($userId, $clientId);
if (!$user) {
    echo '<div class="alert alert-danger">User not found or access denied.</div>';
    exit;
}

// Get admin role status for the user's client
$adminRoleStatus = null;
if ($user['client_id']) {
    $adminRoleStatus = $userModel->getAdminRoleStatus($user['client_id'], $userId);
}

// Fetch countries for dropdown
require_once __DIR__ . '/../../config/database.php';
$database = new Database();
$db = $database->connect();

if (!$db) {
    echo '<div class="alert alert-danger">Database connection failed.</div>';
    exit;
}

$stmt = $db->query("SELECT id, name FROM countries");
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch languages and roles
$languages = $userModel->getLanguages();
$userRoles = $userModel->getClientUserRoles($user['client_id']);

// Get client name
$clientName = $_SESSION['client_code'] ?? 'DEFAULT';
?>

<form id="editUserModalForm" method="POST" action="/Unlockyourskills/users/modal/edit" enctype="multipart/form-data">
    <!-- Hidden field for user ID (encrypted numeric ID) -->
    <input type="hidden" name="user_id" value="<?= htmlspecialchars(IdEncryption::encrypt($user['id'])); ?>">
    <!-- Hidden field for client ID (must be numeric) -->
    <input type="hidden" name="client_id" value="<?= htmlspecialchars($user['client_id']); ?>">
    
    <!-- ✅ Tabs Section -->
    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs" id="editUserModalTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="edit-modal-basic-details-tab" data-bs-toggle="tab" data-bs-target="#edit-modal-basic-details" type="button" role="tab" aria-controls="edit-modal-basic-details" aria-selected="true">
                <?= Localization::translate('basic_details'); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="edit-modal-additional-details-tab" data-bs-toggle="tab" data-bs-target="#edit-modal-additional-details" type="button" role="tab" aria-controls="edit-modal-additional-details" aria-selected="false">
                <?= Localization::translate('additional_details'); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="edit-modal-extra-details-tab" data-bs-toggle="tab" data-bs-target="#edit-modal-extra-details" type="button" role="tab" aria-controls="edit-modal-extra-details" aria-selected="false">
                <?= Localization::translate('extra_details'); ?>
            </button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content mt-3" id="editUserModalTabsContent">
        <div class="tab-pane fade show active" id="edit-modal-basic-details" role="tabpanel" aria-labelledby="edit-modal-basic-details-tab" tabindex="0">
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('profile_id'); ?></label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['profile_id']); ?>" readonly>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('full_name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" id="edit_modal_full_name" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']); ?>">
                    <div class="invalid-feedback"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('email'); ?> <span class="text-danger">*</span></label>
                    <input type="email" id="edit_modal_email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>">
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('contact_number'); ?> <span class="text-danger">*</span></label>
                    <input type="text" id="edit_modal_contact_number" name="contact_number" class="form-control" value="<?= htmlspecialchars($user['contact_number']); ?>">
                    <div class="invalid-feedback"></div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('gender'); ?></label>
                    <select name="gender" class="form-control">
                        <option value=""><?= Localization::translate('select_gender'); ?></option>
                        <option value="Male" <?= $user['gender'] == 'Male' ? 'selected' : ''; ?>><?= Localization::translate('male'); ?></option>
                        <option value="Female" <?= $user['gender'] == 'Female' ? 'selected' : ''; ?>><?= Localization::translate('female'); ?></option>
                        <option value="Other" <?= $user['gender'] == 'Other' ? 'selected' : ''; ?>><?= Localization::translate('other'); ?></option>
                    </select>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('dob'); ?></label>
                    <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars($user['dob'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('user_role'); ?> <span class="text-danger">*</span></label>
                    <select name="user_role" class="form-control">
                        <option value=""><?= Localization::translate('select_user_role'); ?></option>
                        <?php foreach ($userRoles as $role): ?>
                            <?php
                            $roleDisabled = '';
                            $roleText = $role['role_name'];
                            $roleSelected = $user['user_role'] == $role['role_name'] ? 'selected' : '';
                            
                            // Check if admin role should be disabled
                            if ($role['system_role'] === 'admin' && $user['user_role'] != $role['role_name'] && $adminRoleStatus && !$adminRoleStatus['canAdd']) {
                                $roleDisabled = 'disabled';
                                $roleText .= ' (Limit Reached)';
                            }
                            ?>
                            <option value="<?= htmlspecialchars($role['role_name']); ?>" <?= $roleSelected; ?> <?= $roleDisabled; ?>><?= $roleText; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('profile_expiry_date'); ?></label>
                    <input type="date" name="profile_expiry" class="form-control" value="<?= htmlspecialchars($user['profile_expiry'] ?? ''); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('user_status'); ?>:</label>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="user_status" id="edit_modal_status_active" value="Active" <?= $user['user_status'] == 'Active' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="edit_modal_status_active"><?= Localization::translate('active'); ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="user_status" id="edit_modal_status_inactive" value="Inactive" <?= $user['user_status'] == 'Inactive' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="edit_modal_status_inactive"><?= Localization::translate('inactive'); ?></label>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('locked_status'); ?>:</label>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="locked_status" id="edit_modal_locked_yes" value="1" <?= $user['locked_status'] == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="edit_modal_locked_yes"><?= Localization::translate('locked'); ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="locked_status" id="edit_modal_locked_no" value="0" <?= $user['locked_status'] == '0' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="edit_modal_locked_no"><?= Localization::translate('unlocked'); ?></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('leaderboard'); ?>:</label>
                    <div class="mt-2">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="leaderboard" id="edit_modal_leaderboard_yes" value="Yes" <?= $user['leaderboard'] == 'Yes' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="edit_modal_leaderboard_yes"><?= Localization::translate('yes'); ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="leaderboard" id="edit_modal_leaderboard_no" value="No" <?= $user['leaderboard'] == 'No' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="edit_modal_leaderboard_no"><?= Localization::translate('no'); ?></label>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('profile_picture'); ?></label>
                    <input type="file" name="profile_picture" accept="image/jpeg, image/png" class="form-control">
                    <?php if (!empty($user['profile_picture'])): ?>
                        <small class="text-muted">Current: <?= basename($user['profile_picture']); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="edit-modal-additional-details" role="tabpanel" aria-labelledby="edit-modal-additional-details-tab" tabindex="0">
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('country'); ?></label>
                    <select id="edit_modal_countrySelect" name="country" class="form-control">
                        <option value=""><?= Localization::translate('select_country'); ?></option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= $country['id']; ?>" <?= $user['country'] == $country['id'] ? 'selected' : ''; ?>>
                                <?= $country['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('state'); ?></label>
                    <select id="edit_modal_stateSelect" name="state" class="form-control">
                        <option value=""><?= Localization::translate('select_state'); ?></option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('city'); ?></label>
                    <select id="edit_modal_citySelect" name="city" class="form-control">
                        <option value=""><?= Localization::translate('select_city'); ?></option>
                    </select>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('timezone'); ?></label>
                    <select id="edit_modal_timezoneSelect" name="timezone" class="form-control">
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
                                <option value="<?= htmlspecialchars($language['language_code']); ?>"
                                        <?= ($user['language'] == $language['language_code']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($language['language_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('reports_to'); ?></label>
                    <input type="text" name="reports_to" class="form-control" value="<?= htmlspecialchars($user['reports_to']); ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('joining_date'); ?></label>
                    <input type="date" name="joining_date" class="form-control" value="<?= htmlspecialchars($user['joining_date'] ?? ''); ?>">
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                    <label><?= Localization::translate('retirement_date'); ?></label>
                    <input type="date" name="retirement_date" class="form-control" value="<?= htmlspecialchars($user['retirement_date'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="edit-modal-extra-details" role="tabpanel" aria-labelledby="edit-modal-extra-details-tab" tabindex="0">
            <?php
            // Get custom fields for the current client
            require_once __DIR__ . '/../../models/CustomFieldModel.php';
            $customFieldModel = new CustomFieldModel();

            // Determine which client to use for custom fields
            $targetClientId = $user['client_id'] ?? null;

            // Only fetch custom fields if we have a valid client ID
            $customFields = [];
            if ($targetClientId && is_numeric($targetClientId)) {
                $customFields = $customFieldModel->getCustomFieldsByClient((int)$targetClientId);
            }

            // Get existing custom field values for this user
            $customFieldValues = [];
            if (isset($user['id'])) {
                $existingValues = $customFieldModel->getCustomFieldValues($user['id'], $targetClientId);
                foreach ($existingValues as $value) {
                    $customFieldValues[$value['custom_field_id']] = $value['field_value'];
                }
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
                            <label for="edit_modal_custom_field_<?= $field['id']; ?>">
                                <?= htmlspecialchars($field['field_label']); ?>
                                <?php if ($field['is_required']): ?><span class="text-danger">*</span><?php endif; ?>
                            </label>

                            <?php
                            $fieldName = "custom_field_{$field['id']}";
                            $fieldId = "edit_modal_custom_field_{$field['id']}";
                            $currentValue = $customFieldValues[$field['id']] ?? '';

                            switch ($field['field_type']):
                                case 'text':
                                case 'email':
                                case 'phone':
                                case 'number': ?>
                                    <input type="<?= $field['field_type']; ?>"
                                           id="<?= $fieldId; ?>"
                                           name="<?= $fieldName; ?>"
                                           class="form-control"
                                           value="<?= htmlspecialchars($currentValue); ?>"
                                           data-required="<?= $field['is_required'] ? '1' : '0'; ?>">
                                    <div class="invalid-feedback"></div>
                                    <?php break;

                                case 'textarea': ?>
                                    <textarea id="<?= $fieldId; ?>"
                                              name="<?= $fieldName; ?>"
                                              class="form-control"
                                              rows="3"
                                              data-required="<?= $field['is_required'] ? '1' : '0'; ?>"><?= htmlspecialchars($currentValue); ?></textarea>
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
                                            <option value="<?= htmlspecialchars($option); ?>"
                                                    <?= ($currentValue === $option) ? 'selected' : ''; ?>>
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
                                                           <?= ($currentValue === $option) ? 'checked' : ''; ?>
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
                                        <?php
                                        // Handle multiple checkbox values (stored as JSON array or comma-separated)
                                        $selectedValues = [];
                                        if ($currentValue) {
                                            $decoded = json_decode($currentValue, true);
                                            if (is_array($decoded)) {
                                                $selectedValues = $decoded;
                                            } else {
                                                $selectedValues = explode(',', $currentValue);
                                            }
                                        }
                                        ?>
                                        <div class="mt-2">
                                            <?php foreach ($field['field_options'] as $index => $option): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           id="<?= $fieldId; ?>_<?= $index; ?>"
                                                           name="<?= $fieldName; ?>[]"
                                                           value="<?= htmlspecialchars($option); ?>"
                                                           <?= in_array($option, $selectedValues) ? 'checked' : ''; ?>>
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
                                    <?php if ($currentValue): ?>
                                        <small class="text-muted">Current file: <?= htmlspecialchars($currentValue); ?></small>
                                    <?php endif; ?>
                                    <div class="invalid-feedback"></div>
                                    <?php break;

                                case 'date': ?>
                                    <input type="date"
                                           id="<?= $fieldId; ?>"
                                           name="<?= $fieldName; ?>"
                                           class="form-control"
                                           value="<?= htmlspecialchars($currentValue); ?>"
                                           data-required="<?= $field['is_required'] ? '1' : '0'; ?>">
                                    <div class="invalid-feedback"></div>
                                    <?php break;

                                default: ?>
                                    <input type="text"
                                           id="<?= $fieldId; ?>"
                                           name="<?= $fieldName; ?>"
                                           class="form-control"
                                           value="<?= htmlspecialchars($currentValue); ?>"
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
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i><?= Localization::translate('update'); ?>
        </button>
    </div>
</form>

<script>
// Initialize form data for edit modal
document.addEventListener('DOMContentLoaded', function() {
    // Set current values for state and city if they exist
    const currentState = '<?= htmlspecialchars($user['state'] ?? ''); ?>';
    const currentCity = '<?= htmlspecialchars($user['city'] ?? ''); ?>';
    const currentTimezone = '<?= htmlspecialchars($user['timezone'] ?? ''); ?>';
    
    // Load states if country is selected
    const countrySelect = document.getElementById('edit_modal_countrySelect');
    if (countrySelect && countrySelect.value) {
        // Trigger state loading
        loadStatesForEditModal(countrySelect.value, currentState);
    }
    
    // Load timezone
    if (currentTimezone) {
        loadTimezonesForEditModal(currentTimezone);
    }
});

function loadStatesForEditModal(countryId, selectedState = '') {
    // Implementation for loading states in edit modal
    // This will be handled by the main JavaScript file
}

function loadTimezonesForEditModal(selectedTimezone = '') {
    // Implementation for loading timezones in edit modal
    // This will be handled by the main JavaScript file
}
</script>
