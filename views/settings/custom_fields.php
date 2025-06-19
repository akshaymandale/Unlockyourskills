<?php
// views/settings/custom_fields.php
require_once 'core/UrlHelper.php';
require_once 'core/IdEncryption.php';
?>

<?php include 'views/includes/header.php'; ?>
<?php include 'views/includes/navbar.php'; ?>
<?php include 'views/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container add-user-container custom-fields-management" data-custom-fields-page="true">

        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="<?= UrlHelper::url('manage-portal') ?>#settings" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-cogs me-2"></i>
                <?= Localization::translate('custom_fields_management'); ?>
            </h1>
        </div>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= UrlHelper::url('manage-portal') ?>"><?= Localization::translate('manage_portal'); ?></a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= UrlHelper::url('manage-portal') ?>#settings"><?= Localization::translate('settings'); ?></a>
                </li>
                <li class="breadcrumb-item active" aria-current="page"><?= Localization::translate('custom_fields_management'); ?></li>
            </ol>
        </nav>

        <!-- ✅ Filters & Search Section -->
        <div class="filter-section">
            <div class="container-fluid">
                <!-- Single Compact Row -->
                <div class="row align-items-center g-2">

                    <!-- Client Filter for Super Admin -->
                    <?php if ($currentUser['system_role'] === 'super_admin'): ?>
                    <div class="col-auto">
                        <select id="clientFilter" class="form-select form-select-sm compact-filter" onchange="filterByClient(this.value)">
                            <option value=""><?= Localization::translate('all_clients'); ?></option>
                            <?php foreach ($clients as $clientOption): ?>
                                <option value="<?= IdEncryption::encrypt($clientOption['id']) ?>"
                                        <?= ($client && $client['id'] == $clientOption['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($clientOption['client_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <!-- Type Filter -->
                    <div class="col-auto">
                        <select id="typeFilter" class="form-select form-select-sm compact-filter" onchange="filterFields()">
                            <option value=""><?= Localization::translate('all_types'); ?></option>
                            <option value="text"><?= Localization::translate('custom_fields_field_type_text'); ?></option>
                            <option value="textarea"><?= Localization::translate('custom_fields_field_type_textarea'); ?></option>
                            <option value="select"><?= Localization::translate('custom_fields_field_type_select'); ?></option>
                            <option value="radio"><?= Localization::translate('custom_fields_field_type_radio'); ?></option>
                            <option value="checkbox"><?= Localization::translate('custom_fields_field_type_checkbox'); ?></option>
                            <option value="file"><?= Localization::translate('custom_fields_field_type_file'); ?></option>
                            <option value="date"><?= Localization::translate('custom_fields_field_type_date'); ?></option>
                            <option value="number"><?= Localization::translate('custom_fields_field_type_number'); ?></option>
                            <option value="email"><?= Localization::translate('custom_fields_field_type_email'); ?></option>
                            <option value="phone"><?= Localization::translate('custom_fields_field_type_phone'); ?></option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-auto">
                        <select id="statusFilter" class="form-select form-select-sm compact-filter" onchange="filterFields()">
                            <option value=""><?= Localization::translate('all_status'); ?></option>
                            <option value="required"><?= Localization::translate('required_fields'); ?></option>
                            <option value="optional"><?= Localization::translate('optional_fields'); ?></option>
                            <option value="used"><?= Localization::translate('fields_in_use'); ?></option>
                            <option value="unused"><?= Localization::translate('unused_fields'); ?></option>
                        </select>
                    </div>

                    <!-- Active/Inactive Filter -->
                    <div class="col-auto">
                        <select id="activeFilter" class="form-select form-select-sm compact-filter" onchange="filterFields()">
                            <option value=""><?= Localization::translate('all_fields'); ?></option>
                            <option value="active"><?= Localization::translate('active_fields'); ?></option>
                            <option value="inactive"><?= Localization::translate('inactive_fields'); ?></option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="col-auto">
                        <div class="input-group input-group-sm compact-search">
                            <input type="text" id="searchFields" class="form-control"
                                placeholder="<?= Localization::translate('search_custom_fields'); ?>"
                                title="<?= Localization::translate('search_custom_fields'); ?>"
                                onkeyup="filterFields()">
                            <button type="button" class="btn btn-outline-secondary"
                                title="<?= Localization::translate('search_custom_fields'); ?>">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Clear Filters -->
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm btn-secondary" onclick="clearFilters()"
                            title="Clear all filters">
                            <i class="fas fa-times me-1"></i> Clear
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-auto ms-auto">
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#createCustomFieldModal">
                                <i class="fas fa-plus me-1"></i>
                                <?= Localization::translate('custom_fields_create_button'); ?>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Client Info for Super Admin -->
        <?php if ($currentUser['system_role'] === 'super_admin' && ($client || !empty($customFields))): ?>
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle"></i>
            <?php if ($client): ?>
                <strong>Client Filter:</strong> Showing custom fields for <strong><?= htmlspecialchars($client['client_name']) ?></strong>
                <a href="<?= UrlHelper::url('settings/custom-fields') ?>" class="btn btn-sm btn-outline-secondary ms-2">
                    <i class="fas fa-times"></i> Show All Clients
                </a>
            <?php else: ?>
                <strong>All Clients:</strong> Showing custom fields from all clients
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ✅ Custom Fields Grid View -->
        <div id="customFieldsContainer" class="fade-transition">
            <?php if (empty($customFields)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-cogs fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted"><?= Localization::translate('no_custom_fields_found'); ?></h5>
                    <p class="text-muted"><?= Localization::translate('create_first_custom_field'); ?></p>
                    <button type="button" class="btn theme-btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#createCustomFieldModal">
                        <i class="fas fa-plus me-1"></i>
                        <?= Localization::translate('custom_fields_create_button'); ?>
                    </button>
                </div>
            <?php else: ?>
                <table class="table table-bordered" id="customFieldsTable">
                    <thead class="question-grid">
                        <tr>
                            <th><?= Localization::translate('field_name'); ?></th>
                            <th><?= Localization::translate('field_label'); ?></th>
                            <th><?= Localization::translate('field_type'); ?></th>
                            <th><?= Localization::translate('required'); ?></th>
                            <th><?= Localization::translate('status'); ?></th>
                            <th><?= Localization::translate('usage'); ?></th>
                            <?php if ($currentUser['system_role'] === 'super_admin'): ?>
                            <th><?= Localization::translate('client'); ?></th>
                            <?php endif; ?>
                            <th><?= Localization::translate('created'); ?></th>
                            <th><?= Localization::translate('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="customFieldsTableBody">
                        <?php foreach ($customFields as $field): ?>
                            <tr data-field-name="<?= strtolower($field['field_name']) ?>"
                                data-field-label="<?= strtolower($field['field_label']) ?>"
                                data-field-type="<?= $field['field_type'] ?>"
                                data-required="<?= $field['is_required'] ? 'required' : 'optional' ?>"
                                data-active="<?= $field['is_active'] ? 'active' : 'inactive' ?>"
                                data-usage="<?= $field['usage_count'] > 0 ? 'used' : 'unused' ?>">
                                <td>
                                    <code><?= htmlspecialchars($field['field_name']) ?></code>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($field['field_label']) ?></strong>
                                    <?php if (!$field['is_active']): ?>
                                        <small class="text-muted d-block">(<?= Localization::translate('inactive'); ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= Localization::translate('custom_fields_field_type_' . $field['field_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($field['is_required']): ?>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-asterisk me-1"></i>
                                            <?= Localization::translate('required'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <?= Localization::translate('optional'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($field['is_active']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <?= Localization::translate('active'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>
                                            <?= Localization::translate('inactive'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($field['usage_count'] > 0): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-users me-1"></i>
                                            <?= $field['usage_count'] ?> <?= Localization::translate('users'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">
                                            <?= Localization::translate('unused'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($currentUser['system_role'] === 'super_admin'): ?>
                                <td>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($field['client_name'] ?? 'Unknown') ?>
                                    </small>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M j, Y', strtotime($field['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <!-- ✅ Edit Button -->
                                    <button type="button"
                                            class="btn theme-btn-primary edit-field-btn"
                                            data-id="<?= $field['id'] ?>"
                                            data-field-name="<?= htmlspecialchars($field['field_name']) ?>"
                                            data-field-label="<?= htmlspecialchars($field['field_label']) ?>"
                                            data-field-type="<?= $field['field_type'] ?>"
                                            data-field-options="<?= htmlspecialchars(json_encode($field['field_options'] ?? [])) ?>"
                                            data-is-required="<?= $field['is_required'] ? '1' : '0' ?>"
                                            title="<?= Localization::translate('edit_custom_field'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- ✅ Activate/Deactivate Button -->
                                    <?php if ($field['is_active']): ?>
                                        <button type="button"
                                                class="btn theme-btn-warning deactivate-field"
                                                data-id="<?= $field['id'] ?>"
                                                data-name="<?= htmlspecialchars($field['field_label']) ?>"
                                                title="<?= Localization::translate('deactivate_custom_field'); ?>">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button"
                                                class="btn theme-btn-success activate-field"
                                                data-id="<?= $field['id'] ?>"
                                                data-name="<?= htmlspecialchars($field['field_label']) ?>"
                                                title="<?= Localization::translate('activate_custom_field'); ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    <?php endif; ?>

                                    <!-- ✅ Delete Button (enabled for all inactive fields) -->
                                    <?php if (!$field['is_active']): ?>
                                        <button type="button"
                                                class="btn theme-btn-danger delete-field"
                                                data-id="<?= $field['id'] ?>"
                                                data-name="<?= htmlspecialchars($field['field_label']) ?>"
                                                data-usage-count="<?= $field['usage_count'] ?>"
                                                title="<?= Localization::translate('delete_custom_field'); ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary"
                                                disabled
                                                title="<?= Localization::translate('deactivate_before_delete'); ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- ✅ Results Count -->
        <div class="text-center text-muted small mt-3" id="resultsCount">
            Showing <?= count($customFields) ?> custom field<?= count($customFields) != 1 ? 's' : '' ?>
        </div>
    </div>
</div>

<!-- ✅ Create Custom Field Modal -->
<div class="modal fade" id="createCustomFieldModal" tabindex="-1" aria-labelledby="createCustomFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="createCustomFieldForm" action="<?= UrlHelper::url('settings/custom-fields') ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="createCustomFieldModalLabel">
                        <i class="fas fa-plus me-2"></i><?= Localization::translate('custom_fields_create_title'); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Client Selection for Super Admin -->
                    <?php if ($currentUser['system_role'] === 'super_admin'): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Client <span class="text-danger">*</span></label>
                                <select class="form-select" id="client_id" name="client_id" required>
                                    <option value="">Select a client...</option>
                                    <?php foreach ($clients as $clientOption): ?>
                                        <option value="<?= $clientOption['id'] ?>" <?= (isset($_GET['client_id']) && $_GET['client_id'] && IdEncryption::getId($_GET['client_id']) == $clientOption['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($clientOption['client_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Select the client this custom field will belong to</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Field Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="field_name" class="form-label"><?= Localization::translate('custom_fields_field_name_required'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="field_name" name="field_name"
                                       placeholder="<?= Localization::translate('custom_fields_field_name_placeholder'); ?>">
                                <div class="form-text">Used internally (no spaces, use underscores)</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <!-- Field Label -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="field_label" class="form-label"><?= Localization::translate('custom_fields_field_label_required'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="field_label" name="field_label"
                                       placeholder="<?= Localization::translate('custom_fields_field_label_placeholder'); ?>">
                                <div class="form-text">Displayed to users</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Field Type -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="field_type" class="form-label"><?= Localization::translate('custom_fields_field_type_required'); ?> <span class="text-danger">*</span></label>
                                <select class="form-select" id="field_type" name="field_type">
                                    <option value="">Select field type...</option>
                                    <option value="text"><?= Localization::translate('custom_fields_field_type_text'); ?></option>
                                    <option value="textarea"><?= Localization::translate('custom_fields_field_type_textarea'); ?></option>
                                    <option value="select"><?= Localization::translate('custom_fields_field_type_select'); ?></option>
                                    <option value="radio"><?= Localization::translate('custom_fields_field_type_radio'); ?></option>
                                    <option value="checkbox"><?= Localization::translate('custom_fields_field_type_checkbox'); ?></option>
                                    <option value="file"><?= Localization::translate('custom_fields_field_type_file'); ?></option>
                                    <option value="date"><?= Localization::translate('custom_fields_field_type_date'); ?></option>
                                    <option value="number"><?= Localization::translate('custom_fields_field_type_number'); ?></option>
                                    <option value="email"><?= Localization::translate('custom_fields_field_type_email'); ?></option>
                                    <option value="phone"><?= Localization::translate('custom_fields_field_type_phone'); ?></option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <!-- Field Settings -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Field Settings</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_required" name="is_required" value="1">
                                    <label class="form-check-label" for="is_required">
                                        <?= Localization::translate('custom_fields_is_required'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Field Options (for select, radio, checkbox) -->
                    <div class="row" id="field_options_container" style="display: none;">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="field_options" class="form-label"><?= Localization::translate('custom_fields_field_options'); ?> <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="field_options" name="field_options" rows="4"
                                          placeholder="<?= Localization::translate('custom_fields_field_options_placeholder'); ?>"></textarea>
                                <div class="form-text"><?= Localization::translate('custom_fields_field_options_help'); ?></div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i><?= Localization::translate('custom_fields_create_button'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ✅ Edit Custom Field Modal -->
<div class="modal fade" id="editCustomFieldModal" tabindex="-1" aria-labelledby="editCustomFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editCustomFieldForm" method="POST">
                <input type="hidden" id="edit_field_id" name="field_id">

                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomFieldModalLabel">
                        <i class="fas fa-edit me-2"></i><?= Localization::translate('edit_custom_field'); ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <!-- Field Name -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_field_name" class="form-label">Field Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_field_name" name="field_name"
                                       placeholder="e.g., customer_phone">
                                <div class="form-text">Used internally (no spaces, use underscores)</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <!-- Field Label -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_field_label" class="form-label">Field Label <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_field_label" name="field_label"
                                       placeholder="e.g., Customer Phone">
                                <div class="form-text">Displayed to users</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Field Type -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_field_type" class="form-label">Field Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="edit_field_type" name="field_type">
                                    <option value="">Select field type...</option>
                                    <option value="text">Text</option>
                                    <option value="textarea">Textarea</option>
                                    <option value="select">Select Dropdown</option>
                                    <option value="radio">Radio Buttons</option>
                                    <option value="checkbox">Checkboxes</option>
                                    <option value="file">File Upload</option>
                                    <option value="date">Date</option>
                                    <option value="number">Number</option>
                                    <option value="email">Email</option>
                                    <option value="phone">Phone</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <!-- Field Settings -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Field Settings</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_required" name="is_required" value="1">
                                    <label class="form-check-label" for="edit_is_required">
                                        Required Field
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Field Options (for select, radio, checkbox) -->
                    <div class="row" id="edit_field_options_container" style="display: none;">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="edit_field_options" class="form-label">Field Options <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="edit_field_options" name="field_options" rows="4"
                                          placeholder="Enter options, one per line:&#10;Option 1&#10;Option 2&#10;Option 3"></textarea>
                                <div class="form-text">Enter each option on a new line</div>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Field
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
// getProjectUrl function is now handled by script.js

// Filter by client (for super admin)
function filterByClient(clientId) {
    const url = new URL(window.location);
    if (clientId) {
        url.searchParams.set('client_id', clientId);
    } else {
        url.searchParams.delete('client_id');
    }
    window.location.href = url.toString();
}

// Filter fields
function filterFields() {
    const searchTerm = document.getElementById('searchFields').value.toLowerCase();
    const typeFilter = document.getElementById('typeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const activeFilter = document.getElementById('activeFilter').value;

    const rows = document.querySelectorAll('#customFieldsTable tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const fieldName = row.dataset.fieldName;
        const fieldLabel = row.dataset.fieldLabel;
        const fieldType = row.dataset.fieldType;
        const required = row.dataset.required;
        const active = row.dataset.active;
        const usage = row.dataset.usage;

        let show = true;

        // Search filter
        if (searchTerm && !fieldName.includes(searchTerm) && !fieldLabel.includes(searchTerm)) {
            show = false;
        }

        // Type filter
        if (typeFilter && fieldType !== typeFilter) {
            show = false;
        }

        // Status filter
        if (statusFilter) {
            if (statusFilter === 'required' && required !== 'required') show = false;
            if (statusFilter === 'optional' && required !== 'optional') show = false;
            if (statusFilter === 'used' && usage !== 'used') show = false;
            if (statusFilter === 'unused' && usage !== 'unused') show = false;
        }

        // Active/Inactive filter
        if (activeFilter) {
            if (activeFilter === 'active' && active !== 'active') show = false;
            if (activeFilter === 'inactive' && active !== 'inactive') show = false;
        }

        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });

    // Update count
    document.getElementById('resultsCount').textContent = `Showing ${visibleCount} custom field${visibleCount != 1 ? 's' : ''}`;
}

// Clear filters
function clearFilters() {
    document.getElementById('searchFields').value = '';
    document.getElementById('typeFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('activeFilter').value = '';
    filterFields();
}

// Submit field action via POST form
function submitFieldAction(action, fieldId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `<?= UrlHelper::url('settings/custom-fields') ?>/${action}`;

    const fieldIdInput = document.createElement('input');
    fieldIdInput.type = 'hidden';
    fieldIdInput.name = 'field_id';
    fieldIdInput.value = fieldId;

    form.appendChild(fieldIdInput);
    document.body.appendChild(form);
    form.submit();
}

// Handle edit field button
document.addEventListener('click', function(e) {
    // Edit field modal
    if (e.target.closest('.edit-field-btn')) {
        e.preventDefault();
        const button = e.target.closest('.edit-field-btn');

        // Get field data from button attributes
        const fieldId = button.dataset.id;
        const fieldName = button.dataset.fieldName;
        const fieldLabel = button.dataset.fieldLabel;
        const fieldType = button.dataset.fieldType;
        const fieldOptions = JSON.parse(button.dataset.fieldOptions || '[]');
        const isRequired = button.dataset.isRequired === '1';

        // Populate the edit modal
        document.getElementById('edit_field_id').value = fieldId;
        document.getElementById('edit_field_name').value = fieldName;
        document.getElementById('edit_field_label').value = fieldLabel;
        document.getElementById('edit_field_type').value = fieldType;
        document.getElementById('edit_is_required').checked = isRequired;

        // Handle field options
        if (fieldOptions && fieldOptions.length > 0) {
            document.getElementById('edit_field_options').value = fieldOptions.join('\n');
            document.getElementById('edit_field_options_container').style.display = 'block';
        } else {
            document.getElementById('edit_field_options').value = '';
            document.getElementById('edit_field_options_container').style.display = 'none';
        }

        // Show/hide options container based on field type
        const optionTypes = ['select', 'radio', 'checkbox'];
        if (optionTypes.includes(fieldType)) {
            document.getElementById('edit_field_options_container').style.display = 'block';
        } else {
            document.getElementById('edit_field_options_container').style.display = 'none';
        }

        // Set form action to modal update route
        document.getElementById('editCustomFieldForm').action = `<?= UrlHelper::url('settings/custom-fields/update-modal') ?>`;

        // Show the modal
        const editModal = new bootstrap.Modal(document.getElementById('editCustomFieldModal'));
        editModal.show();
    }
});

// Handle field actions
document.addEventListener('click', function(e) {
    // Delete field confirmation
    if (e.target.closest('.delete-field')) {
        e.preventDefault();
        const button = e.target.closest('.delete-field');
        const fieldId = button.dataset.id;
        const fieldName = button.dataset.name;
        const usageCount = parseInt(button.dataset.usageCount) || 0;

        // Use existing confirmAction function for consistency
        if (typeof confirmAction === 'function') {
            const customMessage = `<?= Localization::translate('js.confirm_delete_custom_field'); ?>`.replace('{fieldName}', fieldName);

            let customSubtext;

            // Add data information if field has user data
            if (usageCount > 0) {
                customSubtext = `<?= Localization::translate('js.delete_field_with_data_warning'); ?>`.replace('{usageCount}', usageCount);
            } else {
                customSubtext = `<?= Localization::translate('js.delete_field_no_data_warning'); ?>`;
            }

            confirmAction('delete', `custom field "${fieldName}"`, () => {
                submitFieldAction('delete', fieldId);
            }, customMessage, customSubtext);
        } else {
            // Fallback to browser confirm if confirmAction not available
            let confirmMessage = `<?= Localization::translate('js.confirm_delete_custom_field'); ?>`.replace('{fieldName}', fieldName) + '\n\n';

            if (usageCount > 0) {
                confirmMessage += `<?= Localization::translate('js.delete_field_with_data_warning'); ?>`.replace('{usageCount}', usageCount) + '\n\n';
            }

            confirmMessage += `<?= Localization::translate('js.delete_field_no_data_warning'); ?>`;

            if (confirm(confirmMessage)) {
                submitFieldAction('delete', fieldId);
            }
        }
    }

    // Deactivate field confirmation
    if (e.target.closest('.deactivate-field')) {
        e.preventDefault();
        const button = e.target.closest('.deactivate-field');
        const fieldId = button.dataset.id;
        const fieldName = button.dataset.name;

        // Use existing confirmAction function for consistency
        if (typeof confirmAction === 'function') {
            const customMessage = `<?= Localization::translate('js.confirm_deactivate_custom_field'); ?>`.replace('{fieldName}', fieldName);
            const customSubtext = `<?= Localization::translate('js.deactivate_field_warning'); ?>`;

            confirmAction('deactivate', `custom field "${fieldName}"`, () => {
                submitFieldAction('deactivate', fieldId);
            }, customMessage, customSubtext);
        } else {
            // Fallback to browser confirm if confirmAction not available
            if (confirm(`<?= Localization::translate('js.confirm_deactivate_custom_field'); ?>`.replace('{fieldName}', fieldName) + '\n\n' + `<?= Localization::translate('js.deactivate_field_warning'); ?>`)) {
                submitFieldAction('deactivate', fieldId);
            }
        }
    }

    // Activate field confirmation
    if (e.target.closest('.activate-field')) {
        e.preventDefault();
        const button = e.target.closest('.activate-field');
        const fieldId = button.dataset.id;
        const fieldName = button.dataset.name;

        // Use existing confirmAction function for consistency
        if (typeof confirmAction === 'function') {
            const customMessage = `<?= Localization::translate('js.confirm_activate_custom_field'); ?>`.replace('{fieldName}', fieldName);
            const customSubtext = `<?= Localization::translate('js.activate_field_warning'); ?>`;

            confirmAction('activate', `custom field "${fieldName}"`, () => {
                submitFieldAction('activate', fieldId);
            }, customMessage, customSubtext);
        } else {
            // Fallback to browser confirm if confirmAction not available
            if (confirm(`<?= Localization::translate('js.confirm_activate_custom_field'); ?>`.replace('{fieldName}', fieldName) + '\n\n' + `<?= Localization::translate('js.activate_field_warning'); ?>`)) {
                submitFieldAction('activate', fieldId);
            }
        }
    }
});

// Handle field type changes in edit modal
document.getElementById('edit_field_type').addEventListener('change', function() {
    const fieldType = this.value;
    const optionsContainer = document.getElementById('edit_field_options_container');
    const optionsField = document.getElementById('edit_field_options');
    const optionTypes = ['select', 'radio', 'checkbox'];

    if (optionTypes.includes(fieldType)) {
        optionsContainer.style.display = 'block';
        // Validate options if container is shown
        if (optionsField && optionsField.value.trim()) {
            // Trigger validation
            const event = new Event('focusout');
            optionsField.dispatchEvent(event);
        }
    } else {
        optionsContainer.style.display = 'none';
        // Clear options and validation errors when hidden
        if (optionsField) {
            optionsField.value = '';
            // Clear any validation errors
            const errorElement = optionsField.parentNode.querySelector('.invalid-feedback');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
            optionsField.classList.remove('is-invalid');
        }
    }
});

// ✅ Custom Field Modal JavaScript will be handled by custom_field_validation.js
</script>

<!-- ✅ Load Custom Field Validation JavaScript -->
<script src="<?= UrlHelper::url('public/js/custom_field_validation.js') ?>"></script>

<?php include 'views/includes/footer.php'; ?>
