<?php

require_once 'models/CustomFieldModel.php';
require_once 'models/ClientModel.php';
require_once 'controllers/CustomFieldController.php';
require_once 'core/UrlHelper.php';
require_once 'core/IdEncryption.php';
require_once 'config/Localization.php';

class SettingsController {
    private $customFieldModel;
    private $clientModel;

    public function __construct() {
        $this->customFieldModel = new CustomFieldModel();
        $this->clientModel = new ClientModel();
    }

    /**
     * Settings main page
     */
    public function index() {
        // Redirect to manage portal settings tab
        UrlHelper::redirect('manage-portal#settings');
    }

    /**
     * Custom Fields Management Page
     */
    public function customFields() {
        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser) {
            UrlHelper::redirect('login');
            return;
        }

        // Only allow admin and super admin to manage custom fields
        if (!in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied. You do not have permission to manage custom fields.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        // Get client information
        $clientId = null;
        $client = null;
        
        if ($currentUser['system_role'] === 'admin') {
            // Client admin can only manage their own client's custom fields
            $clientId = $currentUser['client_id'];
            $client = $this->clientModel->getClientById($clientId);
            
            if (!$client) {
                $this->redirectWithToast('Client not found.', 'error', UrlHelper::url('dashboard'));
                return;
            }

            // Check if custom field creation is enabled for this client
            if ($client['custom_field_creation'] != 1) {
                $this->redirectWithToast('Custom field creation is disabled for your organization.', 'error', UrlHelper::url('manage-portal'));
                return;
            }
        } elseif ($currentUser['system_role'] === 'super_admin') {
            // Super admin can manage custom fields for any client
            // Check if filtering by specific client
            $targetClientId = $_GET['client_id'] ?? null;
            if ($targetClientId) {
                try {
                    $clientId = IdEncryption::getId($targetClientId);
                    $client = $this->clientModel->getClientById($clientId);
                } catch (Exception $e) {
                    $this->redirectWithToast('Invalid client ID.', 'error', UrlHelper::url('settings/custom-fields'));
                    return;
                }
            }
        }

        // Get custom fields for the client (including both active and inactive)
        $customFields = [];
        if ($clientId) {
            $customFields = $this->customFieldModel->getCustomFieldsByClient($clientId, false);
        } else {
            // Super admin viewing all custom fields (including both active and inactive)
            $customFields = $this->customFieldModel->getAllCustomFields(false);
        }

        // Usage count is now stored directly in the custom_fields table
        // No need to add it dynamically

        // Get all clients for super admin
        $clients = [];
        if ($currentUser['system_role'] === 'super_admin') {
            $clients = $this->clientModel->getAllClients();
        }

        require 'views/settings/custom_fields.php';
    }

    // Create Custom Field method removed - now handled via modal popup

    /**
     * Edit Custom Field Page
     */
    public function editCustomField() {
        if (!isset($_GET['id'])) {
            $this->redirectWithToast('Custom field ID is required.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        try {
            $encryptedId = urldecode($_GET['id']);
            $fieldId = IdEncryption::getId($encryptedId);
        } catch (Exception $e) {
            $this->redirectWithToast('Invalid custom field ID.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        // Get custom field
        $customField = $this->customFieldModel->getCustomFieldById($fieldId);
        if (!$customField) {
            $this->redirectWithToast('Custom field not found.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check if user has permission to edit this field
        if ($currentUser['system_role'] === 'admin' && $customField['client_id'] != $currentUser['client_id']) {
            $this->redirectWithToast('Access denied. You can only edit custom fields for your organization.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Usage count is now stored directly in the database
        // No need to add it dynamically

        require 'views/settings/edit_custom_field.php';
    }

    /**
     * Store Custom Field with comprehensive validation
     */
    public function storeCustomField() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithToast('Invalid request method.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        // Get client information
        $clientId = null;
        if ($currentUser['system_role'] === 'admin') {
            $clientId = $currentUser['client_id'];
            $client = $this->clientModel->getClientById($clientId);

            if (!$client || $client['custom_field_creation'] != 1) {
                $this->redirectWithToast('Custom field creation is disabled for your organization.', 'error', UrlHelper::url('settings/custom-fields'));
                return;
            }
        } elseif ($currentUser['system_role'] === 'super_admin') {
            // Super admin can create fields for any client
            $targetClientId = $_POST['client_id'] ?? null;
            if ($targetClientId) {
                $clientId = $targetClientId;
            }
        }

        // Validate input data
        $errors = $this->validateCustomFieldData($_POST, $clientId);

        if (!empty($errors)) {
            $errorMessage = implode(', ', $errors);
            $this->redirectWithToast($errorMessage, 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Prepare data for creation
        $fieldData = [
            'field_name' => trim($_POST['field_name']),
            'field_label' => trim($_POST['field_label']),
            'field_type' => $_POST['field_type'],
            'field_options' => $_POST['field_options'] ?? '',
            'is_required' => isset($_POST['is_required']) ? 1 : 0,
            'client_id' => $clientId,
            'created_by' => $currentUser['id']
        ];

        // Create the custom field
        $result = $this->customFieldModel->createCustomField($fieldData);

        if ($result) {
            $this->redirectWithToast('Custom field created successfully!', 'success', UrlHelper::url('settings/custom-fields'));
        } else {
            $this->redirectWithToast('Failed to create custom field.', 'error', UrlHelper::url('settings/custom-fields'));
        }
    }

    /**
     * Validate custom field data
     */
    private function validateCustomFieldData($data, $clientId = null, $excludeId = null) {
        $errors = [];

        // Validate field name
        $fieldName = trim($data['field_name'] ?? '');
        if (empty($fieldName)) {
            $errors[] = 'Field name is required';
        } elseif (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $fieldName)) {
            $errors[] = 'Field name must start with a letter and contain only letters, numbers, and underscores';
        } elseif ($this->customFieldModel->checkFieldNameExists($fieldName, $clientId, $excludeId)) {
            $errors[] = 'A field with this name already exists';
        }

        // Validate field label
        $fieldLabel = trim($data['field_label'] ?? '');
        if (empty($fieldLabel)) {
            $errors[] = 'Field label is required';
        } elseif ($this->customFieldModel->checkFieldLabelExists($fieldLabel, $clientId, $excludeId)) {
            $errors[] = 'A field with this label already exists';
        }

        // Validate field type
        $fieldType = $data['field_type'] ?? '';
        $allowedTypes = ['text', 'textarea', 'select', 'radio', 'checkbox', 'file', 'date', 'number', 'email', 'phone'];
        if (empty($fieldType)) {
            $errors[] = 'Field type is required';
        } elseif (!in_array($fieldType, $allowedTypes)) {
            $errors[] = 'Invalid field type';
        }

        // Validate field options for select/radio/checkbox
        if (in_array($fieldType, ['select', 'radio', 'checkbox'])) {
            $fieldOptions = trim($data['field_options'] ?? '');
            if (empty($fieldOptions)) {
                $errors[] = 'Field options are required for ' . $fieldType . ' fields';
            }
        }

        return $errors;
    }

    /**
     * Update Custom Field via Modal (dedicated POST route)
     */
    public function updateCustomFieldModal() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithToast('Invalid request method.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        // Get field ID from POST data
        $fieldId = $_POST['field_id'] ?? null;
        if (!$fieldId) {
            $this->redirectWithToast('Field ID is required.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Get custom field
        $customField = $this->customFieldModel->getCustomFieldById($fieldId);
        if (!$customField) {
            $this->redirectWithToast('Custom field not found.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check if user has permission to edit this field
        if ($currentUser['system_role'] === 'admin' && $customField['client_id'] != $currentUser['client_id']) {
            $this->redirectWithToast('Access denied. You can only edit custom fields for your organization.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Validate input data
        $errors = $this->validateCustomFieldData($_POST, $customField['client_id'], $fieldId);

        if (!empty($errors)) {
            $errorMessage = implode(', ', $errors);
            $this->redirectWithToast($errorMessage, 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Prepare data for update
        $fieldData = [
            'field_name' => trim($_POST['field_name']),
            'field_label' => trim($_POST['field_label']),
            'field_type' => $_POST['field_type'],
            'field_options' => $_POST['field_options'] ?? '',
            'is_required' => isset($_POST['is_required']) ? 1 : 0
        ];

        // Update the custom field
        $result = $this->customFieldModel->updateCustomField($fieldId, $fieldData, $customField['client_id']);

        if ($result) {
            $this->redirectWithToast('Custom field updated successfully!', 'success', UrlHelper::url('settings/custom-fields'));
        } else {
            $this->redirectWithToast('Failed to update custom field.', 'error', UrlHelper::url('settings/custom-fields'));
        }
    }

    /**
     * Update Custom Field (original PUT method for RESTful API)
     */
    public function updateCustomField() {
        // Handle both POST (with field_id in body) and PUT (with id in URL) methods
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        if (isset($_POST['_method'])) {
            $requestMethod = strtoupper($_POST['_method']);
        }

        if (!in_array($requestMethod, ['POST', 'PUT'])) {
            $this->redirectWithToast('Invalid request method.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        // Get field ID - either from POST body or URL parameter
        $fieldId = null;

        if (isset($_POST['field_id'])) {
            // From POST body (modal form)
            $fieldId = $_POST['field_id'];
        } elseif (isset($_GET['id'])) {
            // From URL parameter (PUT route)
            $fieldId = $_GET['id'];
        }

        if (!$fieldId) {
            $this->redirectWithToast('Field ID is required.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Get custom field
        $customField = $this->customFieldModel->getCustomFieldById($fieldId);
        if (!$customField) {
            $this->redirectWithToast('Custom field not found.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check if user has permission to edit this field
        if ($currentUser['system_role'] === 'admin' && $customField['client_id'] != $currentUser['client_id']) {
            $this->redirectWithToast('Access denied. You can only edit custom fields for your organization.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Validate input data
        $errors = $this->validateCustomFieldData($_POST, $customField['client_id'], $fieldId);

        if (!empty($errors)) {
            $errorMessage = implode(', ', $errors);
            $this->redirectWithToast($errorMessage, 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Prepare data for update
        $fieldData = [
            'field_name' => trim($_POST['field_name']),
            'field_label' => trim($_POST['field_label']),
            'field_type' => $_POST['field_type'],
            'field_options' => $_POST['field_options'] ?? '',
            'is_required' => isset($_POST['is_required']) ? 1 : 0
        ];

        // Update the custom field
        $result = $this->customFieldModel->updateCustomField($fieldId, $fieldData, $customField['client_id']);

        if ($result) {
            $this->redirectWithToast('Custom field updated successfully!', 'success', UrlHelper::url('settings/custom-fields'));
        } else {
            $this->redirectWithToast('Failed to update custom field.', 'error', UrlHelper::url('settings/custom-fields'));
        }
    }

    /**
     * Delete Custom Field (permanent deletion - only for inactive unused fields)
     */
    public function deleteCustomField() {
        if (!isset($_GET['id'])) {
            $this->redirectWithToast('Custom field ID is required.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        try {
            $encryptedId = urldecode($_GET['id']);
            $fieldId = IdEncryption::getId($encryptedId);
        } catch (Exception $e) {
            $this->redirectWithToast('Invalid custom field ID.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        // Get custom field
        $customField = $this->customFieldModel->getCustomFieldById($fieldId);
        if (!$customField) {
            $this->redirectWithToast('Custom field not found.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check if user has permission to delete this field
        if ($currentUser['system_role'] === 'admin' && $customField['client_id'] != $currentUser['client_id']) {
            $this->redirectWithToast('Access denied. You can only delete custom fields for your organization.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check if field is active
        if ($customField['is_active']) {
            $this->redirectWithToast('Cannot delete active custom field. Please deactivate it first.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Get usage count for logging purposes
        $usageCount = $this->customFieldModel->getFieldUsageCount($fieldId);

        // Get current user for audit trail
        $currentUser = $_SESSION['user'] ?? null;
        $deletedBy = $currentUser ? $currentUser['id'] : null;

        // Soft delete the custom field (marks as deleted but preserves data)
        $result = $this->customFieldModel->softDeleteCustomField($fieldId, $deletedBy);

        if ($result) {
            if ($usageCount > 0) {
                $this->redirectWithToast("Custom field deleted successfully! Data from {$usageCount} user(s) has been marked as deleted but preserved for recovery.", 'success', UrlHelper::url('settings/custom-fields'));
            } else {
                $this->redirectWithToast('Custom field deleted successfully!', 'success', UrlHelper::url('settings/custom-fields'));
            }
        } else {
            $this->redirectWithToast('Failed to delete custom field.', 'error', UrlHelper::url('settings/custom-fields'));
        }
    }

    /**
     * Deactivate Custom Field
     */
    public function deactivateCustomField() {
        if (!isset($_GET['id'])) {
            $this->redirectWithToast('Custom field ID is required.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        try {
            $encryptedId = urldecode($_GET['id']);
            $fieldId = IdEncryption::getId($encryptedId);
        } catch (Exception $e) {
            $this->redirectWithToast('Invalid custom field ID.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        // Get custom field
        $customField = $this->customFieldModel->getCustomFieldById($fieldId);
        if (!$customField) {
            $this->redirectWithToast('Custom field not found.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check if user has permission to modify this field
        if ($currentUser['system_role'] === 'admin' && $customField['client_id'] != $currentUser['client_id']) {
            $this->redirectWithToast('Access denied. You can only modify custom fields for your organization.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Deactivate the custom field
        $result = $this->customFieldModel->updateCustomFieldStatus($fieldId, 0);

        if ($result) {
            $this->redirectWithToast('Custom field deactivated successfully!', 'success', UrlHelper::url('settings/custom-fields'));
        } else {
            $this->redirectWithToast('Failed to deactivate custom field.', 'error', UrlHelper::url('settings/custom-fields'));
        }
    }

    /**
     * Activate Custom Field
     */
    public function activateCustomField() {
        if (!isset($_GET['id'])) {
            $this->redirectWithToast('Custom field ID is required.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        try {
            $encryptedId = urldecode($_GET['id']);
            $fieldId = IdEncryption::getId($encryptedId);
        } catch (Exception $e) {
            $this->redirectWithToast('Invalid custom field ID.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        // Get custom field
        $customField = $this->customFieldModel->getCustomFieldById($fieldId);
        if (!$customField) {
            $this->redirectWithToast('Custom field not found.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check if user has permission to modify this field
        if ($currentUser['system_role'] === 'admin' && $customField['client_id'] != $currentUser['client_id']) {
            $this->redirectWithToast('Access denied. You can only modify custom fields for your organization.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Activate the custom field
        $result = $this->customFieldModel->updateCustomFieldStatus($fieldId, 1);

        if ($result) {
            $this->redirectWithToast('Custom field activated successfully!', 'success', UrlHelper::url('settings/custom-fields'));
        } else {
            $this->redirectWithToast('Failed to activate custom field.', 'error', UrlHelper::url('settings/custom-fields'));
        }
    }

    /**
     * Check if field name already exists (API endpoint)
     */
    public function checkFieldName() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Invalid request method']);
            return;
        }

        $fieldName = $_POST['field_name'] ?? '';
        $currentUser = $_SESSION['user'] ?? null;

        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        if (empty($fieldName)) {
            echo json_encode(['exists' => false]);
            return;
        }

        // Get client ID for filtering
        $clientId = null;
        if ($currentUser['system_role'] === 'admin') {
            $clientId = $currentUser['client_id'];
        }

        $exists = $this->customFieldModel->checkFieldNameExists($fieldName, $clientId);
        echo json_encode(['exists' => $exists]);
    }

    /**
     * Check if field label already exists (API endpoint)
     */
    public function checkFieldLabel() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Invalid request method']);
            return;
        }

        $fieldLabel = $_POST['field_label'] ?? '';
        $currentUser = $_SESSION['user'] ?? null;

        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            echo json_encode(['error' => 'Access denied']);
            return;
        }

        if (empty($fieldLabel)) {
            echo json_encode(['exists' => false]);
            return;
        }

        // Get client ID for filtering
        $clientId = null;
        if ($currentUser['system_role'] === 'admin') {
            $clientId = $currentUser['client_id'];
        }

        $exists = $this->customFieldModel->checkFieldLabelExists($fieldLabel, $clientId);
        echo json_encode(['exists' => $exists]);
    }

    /**
     * Activate Custom Field (POST method for modal actions)
     */
    public function activateCustomFieldPost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithToast('Invalid request method.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        $fieldId = $_POST['field_id'] ?? null;
        if (!$fieldId) {
            $this->redirectWithToast('Field ID is required.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        // Get custom field
        $customField = $this->customFieldModel->getCustomFieldById($fieldId);
        if (!$customField) {
            $this->redirectWithToast('Custom field not found.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check if user has permission to modify this field
        if ($currentUser['system_role'] === 'admin' && $customField['client_id'] != $currentUser['client_id']) {
            $this->redirectWithToast('Access denied. You can only modify custom fields for your organization.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Activate the custom field
        $result = $this->customFieldModel->updateCustomFieldStatus($fieldId, 1);

        if ($result) {
            $this->redirectWithToast('Custom field activated successfully!', 'success', UrlHelper::url('settings/custom-fields'));
        } else {
            $this->redirectWithToast('Failed to activate custom field.', 'error', UrlHelper::url('settings/custom-fields'));
        }
    }

    /**
     * Deactivate Custom Field (POST method for modal actions)
     */
    public function deactivateCustomFieldPost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithToast('Invalid request method.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        $fieldId = $_POST['field_id'] ?? null;
        if (!$fieldId) {
            $this->redirectWithToast('Field ID is required.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        // Get custom field
        $customField = $this->customFieldModel->getCustomFieldById($fieldId);
        if (!$customField) {
            $this->redirectWithToast('Custom field not found.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check if user has permission to modify this field
        if ($currentUser['system_role'] === 'admin' && $customField['client_id'] != $currentUser['client_id']) {
            $this->redirectWithToast('Access denied. You can only modify custom fields for your organization.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Deactivate the custom field
        $result = $this->customFieldModel->updateCustomFieldStatus($fieldId, 0);

        if ($result) {
            $this->redirectWithToast('Custom field deactivated successfully!', 'success', UrlHelper::url('settings/custom-fields'));
        } else {
            $this->redirectWithToast('Failed to deactivate custom field.', 'error', UrlHelper::url('settings/custom-fields'));
        }
    }

    /**
     * Delete Custom Field (POST method for modal actions)
     */
    public function deleteCustomFieldPost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithToast('Invalid request method.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        $fieldId = $_POST['field_id'] ?? null;
        if (!$fieldId) {
            $this->redirectWithToast('Field ID is required.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check user permissions
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
            $this->redirectWithToast('Access denied.', 'error', UrlHelper::url('dashboard'));
            return;
        }

        // Get custom field
        $customField = $this->customFieldModel->getCustomFieldById($fieldId);
        if (!$customField) {
            $this->redirectWithToast('Custom field not found.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check if user has permission to delete this field
        if ($currentUser['system_role'] === 'admin' && $customField['client_id'] != $currentUser['client_id']) {
            $this->redirectWithToast('Access denied. You can only delete custom fields for your organization.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Check if field is active
        if ($customField['is_active']) {
            $this->redirectWithToast('Cannot delete active custom field. Please deactivate it first.', 'error', UrlHelper::url('settings/custom-fields'));
            return;
        }

        // Get usage count for logging purposes
        $usageCount = $this->customFieldModel->getFieldUsageCount($fieldId);

        // Get current user for audit trail
        $currentUser = $_SESSION['user'] ?? null;
        $deletedBy = $currentUser ? $currentUser['id'] : null;

        // Soft delete the custom field (marks as deleted but preserves data)
        $result = $this->customFieldModel->softDeleteCustomField($fieldId, $deletedBy);

        if ($result) {
            if ($usageCount > 0) {
                $this->redirectWithToast("Custom field deleted successfully! Data from {$usageCount} user(s) has been marked as deleted but preserved for recovery.", 'success', UrlHelper::url('settings/custom-fields'));
            } else {
                $this->redirectWithToast('Custom field deleted successfully!', 'success', UrlHelper::url('settings/custom-fields'));
            }
        } else {
            $this->redirectWithToast('Failed to delete custom field.', 'error', UrlHelper::url('settings/custom-fields'));
        }
    }

    /**
     * Helper method to redirect with toast message
     */
    private function redirectWithToast($message, $type, $url) {
        $encodedMessage = urlencode($message);
        $separator = strpos($url, '?') !== false ? '&' : '?';
        header("Location: {$url}{$separator}message={$encodedMessage}&type={$type}");
        exit();
    }
}
