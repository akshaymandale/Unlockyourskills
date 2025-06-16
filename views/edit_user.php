<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Get Client Name from Session
$clientName = $_SESSION['client_code'] ?? 'DEFAULT';
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container add-user-container">
        <h1 class="page-title text-purple">
            <?= Localization::translate('edit_user_title'); ?>
        </h1>
        <form action="index.php?controller=UserManagementController&action=updateUser" id="editUserForm" method="POST"
            enctype="multipart/form-data">
            <!-- ✅ Hidden field for profile ID -->
            <input type="hidden" name="profile_id" value="<?= htmlspecialchars($user['profile_id']); ?>">
            
            <!-- ✅ Tabs Section -->
            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs" id="editUserTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="basic-details-tab" data-bs-toggle="tab" data-bs-target="#basic-details" type="button" role="tab" aria-controls="basic-details" aria-selected="true">
                        <?= Localization::translate('basic_details'); ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="additional-details-tab" data-bs-toggle="tab" data-bs-target="#additional-details" type="button" role="tab" aria-controls="additional-details" aria-selected="false">
                        <?= Localization::translate('additional_details'); ?>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="extra-details-tab" data-bs-toggle="tab" data-bs-target="#extra-details" type="button" role="tab" aria-controls="extra-details" aria-selected="false">
                        <?= Localization::translate('extra_details'); ?>
                    </button>
                </li>
            </ul>

            <!-- Tabs Content -->
            <input type="hidden" name="client_id" id="clientName" value="<?php echo htmlspecialchars($clientName); ?>">
            <div class="tab-content" id="editUserTabsContent">
                <div class="tab-pane fade show active" id="basic-details" role="tabpanel" aria-labelledby="basic-details-tab" tabindex="0">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('profile_id'); ?></label>
                            <input type="text" id="profile_id_display" name="profile_id_display" class="input-field" 
                                   value="<?= htmlspecialchars($user['profile_id']); ?>" readonly>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('full_name'); ?> *</label>
                            <input type="text" id="full_name" name="full_name" class="input-field" 
                                   value="<?= htmlspecialchars($user['full_name']); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('email'); ?> *</label>
                            <input type="text" id="email" name="email" class="input-field" 
                                   value="<?= htmlspecialchars($user['email']); ?>">
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('contact_number'); ?> *</label>
                            <input type="text" id="contact_number" name="contact_number" class="input-field" 
                                   value="<?= htmlspecialchars($user['contact_number']); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('gender'); ?></label>
                            <select name="gender" class="input-field">
                                <option value=""><?= Localization::translate('select_gender'); ?></option>
                                <option value="Male" <?= $user['gender'] == 'Male' ? 'selected' : ''; ?>><?= Localization::translate('male'); ?></option>
                                <option value="Female" <?= $user['gender'] == 'Female' ? 'selected' : ''; ?>><?= Localization::translate('female'); ?></option>
                                <option value="Other" <?= $user['gender'] == 'Other' ? 'selected' : ''; ?>><?= Localization::translate('other'); ?></option>
                            </select>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('dob'); ?></label>
                            <input type="date" id="dob" name="dob" class="input-field" 
                                   value="<?= htmlspecialchars($user['dob']); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('user_role'); ?> *</label>
                            <select id="user_role" name="user_role" class="input-field">
                                <option value=""><?= Localization::translate('select_user_role'); ?></option>
                                <?php
                                // Check if admin role should be disabled
                                $adminDisabled = '';
                                $adminText = Localization::translate('admin');
                                $adminSelected = $user['user_role'] == 'Admin' ? 'selected' : '';

                                // If user is currently admin, always allow (they can change from admin)
                                // If user is not admin and limit reached, disable the option
                                if ($user['user_role'] != 'Admin' && $adminRoleStatus && !$adminRoleStatus['canAdd']) {
                                    $adminDisabled = 'disabled';
                                    $adminText .= ' (Limit Reached)';
                                }
                                ?>
                                <option value="Admin" <?= $adminSelected; ?> <?= $adminDisabled; ?>><?= $adminText; ?></option>
                                <option value="End User" <?= $user['user_role'] == 'End User' ? 'selected' : ''; ?>><?= Localization::translate('end_user'); ?></option>
                                <option value="Instructor" <?= $user['user_role'] == 'Instructor' ? 'selected' : ''; ?>><?= Localization::translate('instructor'); ?></option>
                                <option value="Corporate Manager" <?= $user['user_role'] == 'Corporate Manager' ? 'selected' : ''; ?>><?= Localization::translate('corporate_manager'); ?></option>
                            </select>
                            <?php if ($user['user_role'] != 'Admin' && $adminRoleStatus && !$adminRoleStatus['canAdd']): ?>
                                <small class="text-muted">
                                    Admin limit: <?= $adminRoleStatus['current']; ?>/<?= $adminRoleStatus['limit']; ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('profile_expiry_date'); ?></label>
                            <input type="date" id="profile_expiry" name="profile_expiry" class="input-field" 
                                   value="<?= htmlspecialchars($user['profile_expiry']); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('user_status'); ?> :</label>
                            <div>
                                <label>
                                    <input type="radio" name="user_status" value="Active" <?= $user['user_status'] == 'Active' ? 'checked' : ''; ?>>
                                    <?= Localization::translate('active'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="user_status" value="Inactive" <?= $user['user_status'] == 'Inactive' ? 'checked' : ''; ?>>
                                    <?= Localization::translate('inactive'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('locked_status'); ?> :</label>
                            <div>
                                <label>
                                    <input type="radio" name="locked_status" value="1" <?= $user['locked_status'] == '1' ? 'checked' : ''; ?>>
                                    <?= Localization::translate('locked'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="locked_status" value="0" <?= $user['locked_status'] == '0' ? 'checked' : ''; ?>>
                                    <?= Localization::translate('unlocked'); ?>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('leaderboard'); ?> :</label>
                            <div>
                                <label>
                                    <input type="radio" name="leaderboard" value="Yes" <?= $user['leaderboard'] == 'Yes' ? 'checked' : ''; ?>>
                                    <?= Localization::translate('yes'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="leaderboard" value="No" <?= $user['leaderboard'] == 'No' ? 'checked' : ''; ?>>
                                    <?= Localization::translate('no'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('profile_picture'); ?></label>
                            <input type="file" id="profile_picture" name="profile_picture"
                                accept="image/jpeg, image/png" class="input-field">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <small class="text-muted">Current: <?= basename($user['profile_picture']); ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="additional-details" role="tabpanel" aria-labelledby="additional-details-tab" tabindex="0">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label for="country"><?= Localization::translate('country'); ?></label>
                            <select id="countrySelect" name="country" class="form-control">
                                <option value=""><?= Localization::translate('select_country'); ?></option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?= $country['id']; ?>" <?= $user['country'] == $country['id'] ? 'selected' : ''; ?>>
                                        <?= $country['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label for="state"><?= Localization::translate('state'); ?></label>
                            <select id="stateSelect" name="state" class="form-control">
                                <option value=""><?= Localization::translate('select_state'); ?></option>
                                <!-- States will be populated by JavaScript based on country selection -->
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label for="city"><?= Localization::translate('city'); ?></label>
                            <select id="citySelect" name="city" class="form-control">
                                <option value=""><?= Localization::translate('select_city'); ?></option>
                                <!-- Cities will be populated by JavaScript based on state selection -->
                            </select>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('timezone'); ?></label>
                            <select id="timezoneSelect" name="timezone" class="input-field">
                                <option value=""><?= Localization::translate('select_timezone'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('language'); ?></label>
                            <select name="language" class="input-field">
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
                            <input type="text" name="reports_to" class="input-field" 
                                   value="<?= htmlspecialchars($user['reports_to']); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('joining_date'); ?></label>
                            <input type="date" name="joining_date" class="input-field" 
                                   value="<?= htmlspecialchars($user['joining_date']); ?>">
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('retirement_date'); ?></label>
                            <input type="date" name="retirement_date" class="input-field" 
                                   value="<?= htmlspecialchars($user['retirement_date']); ?>">
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="extra-details" role="tabpanel" aria-labelledby="extra-details-tab" tabindex="0">
                    <?php
                    // Get custom fields for the current client
                    require_once 'models/CustomFieldModel.php';
                    $customFieldModel = new CustomFieldModel();

                    // Determine which client to use for custom fields
                    $targetClientId = null;
                    $currentUser = $_SESSION['user'] ?? null;

                    // If super admin is managing a specific client (from URL parameter)
                    if (isset($_GET['client_id']) && $currentUser && $currentUser['system_role'] === 'super_admin') {
                        $targetClientId = $_GET['client_id'];
                    } else {
                        // Use the user's client ID (the user being edited)
                        $targetClientId = $user['client_id'] ?? null;
                    }

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
                            <a href="index.php?controller=UserManagementController" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i><?= Localization::translate('custom_fields_create_button'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php
                            $fieldCount = 0;
                            foreach ($customFields as $field):
                                if ($fieldCount % 2 === 0 && $fieldCount > 0): ?>
                                    </div><div class="row">
                                <?php endif; ?>

                                <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                                    <label for="custom_field_<?= $field['id']; ?>">
                                        <?= htmlspecialchars($field['field_label']); ?>
                                        <?php if ($field['is_required']): ?><span class="text-danger">*</span><?php endif; ?>
                                    </label>

                                    <?php
                                    $fieldName = "custom_field_{$field['id']}";
                                    $fieldId = "custom_field_{$field['id']}";
                                    $currentValue = $customFieldValues[$field['id']] ?? '';

                                    switch ($field['field_type']):
                                        case 'text':
                                        case 'email':
                                        case 'phone':
                                        case 'number': ?>
                                            <input type="<?= $field['field_type']; ?>"
                                                   id="<?= $fieldId; ?>"
                                                   name="<?= $fieldName; ?>"
                                                   class="input-field"
                                                   value="<?= htmlspecialchars($currentValue); ?>">
                                            <?php break;

                                        case 'textarea': ?>
                                            <textarea id="<?= $fieldId; ?>"
                                                      name="<?= $fieldName; ?>"
                                                      class="input-field"
                                                      rows="3"><?= htmlspecialchars($currentValue); ?></textarea>
                                            <?php break;

                                        case 'select': ?>
                                            <select id="<?= $fieldId; ?>"
                                                    name="<?= $fieldName; ?>"
                                                    class="input-field">
                                                <option value="">Select an option...</option>
                                                <?php if ($field['field_options']): ?>
                                                    <?php foreach ($field['field_options'] as $option): ?>
                                                        <option value="<?= htmlspecialchars($option); ?>"
                                                                <?= ($currentValue === $option) ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($option); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                            <?php break;

                                        case 'radio': ?>
                                            <?php if ($field['field_options']): ?>
                                                <?php foreach ($field['field_options'] as $index => $option): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input"
                                                               type="radio"
                                                               id="<?= $fieldId; ?>_<?= $index; ?>"
                                                               name="<?= $fieldName; ?>"
                                                               value="<?= htmlspecialchars($option); ?>"
                                                               <?= ($currentValue === $option) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="<?= $fieldId; ?>_<?= $index; ?>">
                                                            <?= htmlspecialchars($option); ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <?php break;

                                        case 'checkbox': ?>
                                            <?php
                                            $selectedValues = $currentValue ? explode(',', $currentValue) : [];
                                            if ($field['field_options']): ?>
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
                                            <?php endif; ?>
                                            <?php break;

                                        case 'file': ?>
                                            <input type="file"
                                                   id="<?= $fieldId; ?>"
                                                   name="<?= $fieldName; ?>"
                                                   class="input-field">
                                            <?php if ($currentValue): ?>
                                                <div class="form-text">Current file: <?= htmlspecialchars($currentValue); ?></div>
                                            <?php endif; ?>
                                            <?php break;

                                        case 'date': ?>
                                            <input type="date"
                                                   id="<?= $fieldId; ?>"
                                                   name="<?= $fieldName; ?>"
                                                   class="input-field"
                                                   value="<?= htmlspecialchars($currentValue); ?>">
                                            <?php break;

                                        default: ?>
                                            <input type="text"
                                                   id="<?= $fieldId; ?>"
                                                   name="<?= $fieldName; ?>"
                                                   class="input-field"
                                                   value="<?= htmlspecialchars($currentValue); ?>">
                                            <?php break;
                                    endswitch; ?>
                                </div>

                                <?php $fieldCount++; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12 form-actions">
                        <button type="submit" class="btn btn-primary"><?= Localization::translate('update'); ?></button>
                        <a href="index.php?controller=UserManagementController" class="btn btn-danger"><?= Localization::translate('cancel'); ?></a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ✅ Form Validation Script -->
<script>
    const translations = <?= json_encode([
        "validation.full_name_required" => Localization::translate('validation.full_name_required'),
        "validation.email_required" => Localization::translate('validation.email_required'),
        "validation.email_invalid" => Localization::translate('validation.email_invalid'),
        "validation.contact_required" => Localization::translate('validation.contact_required'),
        "validation.contact_invalid" => Localization::translate('validation.contact_invalid'),
        "validation.dob_required" => Localization::translate('validation.dob_required'),
        "validation.dob_future" => Localization::translate('validation.dob_future'),
        "validation.user_role_required" => Localization::translate('validation.user_role_required'),
        "validation.profile_expiry_invalid" => Localization::translate('validation.profile_expiry_invalid'),
        "validation.image_format" => Localization::translate('validation.image_format'),
        "validation.image_size" => Localization::translate('validation.image_size')
    ]); ?>;
</script>



<script src="public/js/edit_user_validation.js"></script>
<?php include 'includes/footer.php'; ?>
