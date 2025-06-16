<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    require_once 'models/ClientModel.php';
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
require_once 'config/database.php';

// Instantiate the Database class
$database = new Database();

// Establish the connection
$db = $database->connect();

// Check if the connection was successful
if (!$db) {
    die("Database connection failed.");
}

$stmt = $db->query("SELECT id, name FROM countries");
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container add-user-container">
        <h1 class="page-title text-purple">
            <?= Localization::translate('add_user_title'); ?>
        </h1>

        <?php if ($isFromClientManagement): ?>
            <div class="alert alert-primary mb-3">
                <i class="fas fa-users-cog"></i>
                <strong>Client Management Mode:</strong> Adding Admin user for client <strong><?= htmlspecialchars($clientName); ?></strong>
                <br><small><i class="fas fa-lock"></i> Only Admin role is available when adding users for client management purposes.</small>
            </div>
        <?php endif; ?>

        <form action="index.php?controller=UserManagementController&action=storeUser" id="addUserForm" method="POST"
            enctype="multipart/form-data">
            <!-- ✅ Tabs Section -->
            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs" id="addUserTabs" role="tablist">
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
            <input type="hidden" name="target_client_id" value="<?php echo htmlspecialchars($targetClientId); ?>">
            <div class="tab-content" id="addUserTabsContent">
                <div class="tab-pane fade show active" id="basic-details" role="tabpanel" aria-labelledby="basic-details-tab" tabindex="0">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('profile_id'); ?></label>
                            <input type="text" id="profile_id" name="profile_id" class="input-field" readonly placeholder="Will be auto-generated">
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('full_name'); ?> <span class="text-danger">*</span></label>
                            <input type="text" id="full_name" name="full_name" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('email'); ?> <span class="text-danger">*</span></label>
                            <input type="text" id="email" name="email" class="input-field">
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('contact_number'); ?> <span class="text-danger">*</span></label>
                            <input type="text" id="contact_number" name="contact_number" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('gender'); ?></label>
                            <select name="gender" class="input-field">
                                <option value=""><?= Localization::translate('select_gender'); ?></option>
                                <option value="Male"><?= Localization::translate('male'); ?></option>
                                <option value="Female"><?= Localization::translate('female'); ?></option>
                                <option value="Other"><?= Localization::translate('other'); ?></option>
                            </select>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('dob'); ?></label>
                            <input type="date" id="dob" name="dob" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('user_role'); ?> <span class="text-danger">*</span></label>
                            <select id="user_role" name="user_role" class="input-field" <?= $isSuperAdminForClient ? 'readonly style="background-color: #f8f9fa; cursor: not-allowed;"' : ''; ?>>
                                <?php
                                // Check if super admin is adding user for specific client
                                $isSuperAdminForClient = $isFromClientManagement;

                                if ($isSuperAdminForClient): ?>
                                    <!-- Super admin adding for specific client - only Admin role -->
                                    <?php
                                    // Check if admin role should be disabled
                                    $adminDisabled = '';
                                    $adminText = Localization::translate('admin');

                                    if ($adminRoleStatus && !$adminRoleStatus['canAdd']) {
                                        $adminDisabled = 'disabled';
                                        $adminText .= ' (Limit Reached)';
                                    }
                                    ?>
                                    <option value="Admin" <?= $adminDisabled; ?> selected><?= $adminText; ?></option>
                                <?php else: ?>
                                    <!-- Regular admin or super admin not in client context - show all roles -->
                                    <option value=""><?= Localization::translate('select_user_role'); ?></option>
                                    <?php
                                    // Check if admin role should be disabled
                                    $adminDisabled = '';
                                    $adminText = Localization::translate('admin');

                                    if ($adminRoleStatus && !$adminRoleStatus['canAdd']) {
                                        $adminDisabled = 'disabled';
                                        $adminText .= ' (Limit Reached)';
                                    }
                                    ?>
                                    <option value="Admin" <?= $adminDisabled; ?>><?= $adminText; ?></option>
                                    <option value="End User"><?= Localization::translate('end_user'); ?></option>
                                    <option value="Instructor"><?= Localization::translate('instructor'); ?></option>
                                    <option value="Corporate Manager"><?= Localization::translate('corporate_manager'); ?></option>
                                <?php endif; ?>
                            </select>

                            <?php if ($isSuperAdminForClient): ?>
                                <small class="text-info">
                                    <i class="fas fa-lock"></i> <strong>Client Management Mode:</strong> Only Admin role available for client administration.
                                </small>
                            <?php endif; ?>

                            <?php if ($adminRoleStatus && !$adminRoleStatus['canAdd']): ?>
                                <small class="text-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Admin limit reached: <?= $adminRoleStatus['current']; ?>/<?= $adminRoleStatus['limit']; ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('profile_expiry_date'); ?></label>
                            <input type="date" id="profile_expiry" name="profile_expiry" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('user_status'); ?> :</label>
                            <div>
                                <label>
                                    <input type="radio" name="user_status" value="Active" checked>
                                    <?= Localization::translate('active'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="user_status" value="Inactive">
                                    <?= Localization::translate('inactive'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('locked_status'); ?> :</label>
                            <div>
                                <label>
                                    <input type="radio" name="locked_status" value="1">
                                    <?= Localization::translate('locked'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="locked_status" value="0" checked>
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
                                    <input type="radio" name="leaderboard" value="Yes">
                                    <?= Localization::translate('yes'); ?>
                                </label>
                                <label>
                                    <input type="radio" name="leaderboard" value="No" checked>
                                    <?= Localization::translate('no'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('profile_picture'); ?></label>
                            <input type="file" id="profile_picture" name="profile_picture"
                                accept="image/jpeg, image/png" class="input-field">
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
                                    <option value="<?= $country['id']; ?>"><?= $country['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label for="state"><?= Localization::translate('state'); ?></label>
                            <select id="stateSelect" name="state" class="form-control" disabled>
                                <option value=""><?= Localization::translate('select_state'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label for="city"><?= Localization::translate('city'); ?></label>
                            <select id="citySelect" name="city" class="form-control" disabled>
                                <option value=""><?= Localization::translate('select_city'); ?></option>
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
                                        <option value="<?= htmlspecialchars($language['language_code']); ?>">
                                            <?= htmlspecialchars($language['language_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('reports_to'); ?></label>
                            <input type="text" name="reports_to" class="input-field">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('joining_date'); ?></label>
                            <input type="date" name="joining_date" class="input-field">
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12 form-group">
                            <label><?= Localization::translate('retirement_date'); ?></label>
                            <input type="date" name="retirement_date" class="input-field">
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
                        // Use current user's client ID
                        $targetClientId = $currentUser['client_id'] ?? $_SESSION['client_id'] ?? null;
                    }

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

                                    switch ($field['field_type']):
                                        case 'text':
                                        case 'email':
                                        case 'phone':
                                        case 'number': ?>
                                            <input type="<?= $field['field_type']; ?>"
                                                   id="<?= $fieldId; ?>"
                                                   name="<?= $fieldName; ?>"
                                                   class="input-field">
                                            <?php break;

                                        case 'textarea': ?>
                                            <textarea id="<?= $fieldId; ?>"
                                                      name="<?= $fieldName; ?>"
                                                      class="input-field"
                                                      rows="3"></textarea>
                                            <?php break;

                                        case 'select': ?>
                                            <select id="<?= $fieldId; ?>"
                                                    name="<?= $fieldName; ?>"
                                                    class="input-field">
                                                <option value="">Select an option...</option>
                                                <?php if ($field['field_options']): ?>
                                                    <?php foreach ($field['field_options'] as $option): ?>
                                                        <option value="<?= htmlspecialchars($option); ?>">
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
                                                               value="<?= htmlspecialchars($option); ?>">
                                                        <label class="form-check-label" for="<?= $fieldId; ?>_<?= $index; ?>">
                                                            <?= htmlspecialchars($option); ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <?php break;

                                        case 'checkbox': ?>
                                            <?php if ($field['field_options']): ?>
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
                                            <?php endif; ?>
                                            <?php break;

                                        case 'file': ?>
                                            <input type="file"
                                                   id="<?= $fieldId; ?>"
                                                   name="<?= $fieldName; ?>"
                                                   class="input-field">
                                            <?php break;

                                        case 'date': ?>
                                            <input type="date"
                                                   id="<?= $fieldId; ?>"
                                                   name="<?= $fieldName; ?>"
                                                   class="input-field">
                                            <?php break;

                                        default: ?>
                                            <input type="text"
                                                   id="<?= $fieldId; ?>"
                                                   name="<?= $fieldName; ?>"
                                                   class="input-field">
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
                        <button type="submit" class="btn btn-primary"><?= Localization::translate('submit'); ?></button>
                        <button type="reset" class="btn btn-danger"><?= Localization::translate('cancel'); ?></button>
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

    // Auto-generate Profile ID
    document.addEventListener('DOMContentLoaded', function() {
        // Generate profile ID
        function generateProfileId() {
            const prefix = '<?= htmlspecialchars($clientName); ?>'.substring(0, 2).toUpperCase();
            const timestamp = Date.now().toString().slice(-6);
            const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
            return prefix + timestamp + random;
        }

        const profileIdField = document.getElementById('profile_id');
        if (profileIdField) {
            profileIdField.value = generateProfileId();
        }

        <?php if ($isSuperAdminForClient): ?>
        // Super admin client mode - lock user role to Admin
        const userRoleSelect = document.getElementById('user_role');
        if (userRoleSelect) {
            // Prevent any changes to the dropdown
            userRoleSelect.addEventListener('change', function(e) {
                e.target.value = 'Admin';
            });

            // Add visual indicator
            userRoleSelect.title = 'Role is locked to Admin for client management';
        }
        <?php endif; ?>
    });
</script>



<script src="public/js/add_user_validation.js"></script>
<?php include 'includes/footer.php'; ?>