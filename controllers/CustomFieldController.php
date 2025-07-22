<?php

require_once 'models/CustomFieldModel.php';
require_once 'config/Localization.php';
require_once 'core/UrlHelper.php';

class CustomFieldController {
    private $customFieldModel;

    public function __construct() {
        $this->customFieldModel = new CustomFieldModel();
    }

    /**
     * Display custom fields management page
     */
    public function index() {
        // Check if user is logged in and has permission
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?controller=AuthController&action=login');
            exit;
        }

        $clientId = $_SESSION['client_id'];
        $customFields = $this->customFieldModel->getCustomFieldsByClient($clientId);

        include 'views/custom_fields_management.php';
    }

    /**
     * Create a new custom field
     */
    public function create() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request method.'
            ]);
            exit();
        }

        try {
            $clientId = $_SESSION['user']['client_id'] ?? null;

            if (!$clientId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Client ID not found in session.'
                ]);
                exit();
            }

            $fieldErrors = [];
            $errors = [];

            // Validate field name
            if (empty($_POST['field_name']) || trim($_POST['field_name']) === '') {
                $fieldErrors['field_name'] = Localization::translate('validation.field_name_required');
                $errors[] = Localization::translate('validation.field_name_required');
            } else {
                // Validate field name format (no spaces, use underscores)
                $fieldName = trim($_POST['field_name']);
                if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $fieldName)) {
                    $fieldErrors['field_name'] = 'Field name must start with a letter and contain only letters, numbers, and underscores';
                    $errors[] = 'Field name must start with a letter and contain only letters, numbers, and underscores';
                }
            }

            // Validate field label
            if (empty($_POST['field_label']) || trim($_POST['field_label']) === '') {
                $fieldErrors['field_label'] = Localization::translate('validation.field_label_required');
                $errors[] = Localization::translate('validation.field_label_required');
            }

            // Validate field type
            if (empty($_POST['field_type']) || trim($_POST['field_type']) === '') {
                $fieldErrors['field_type'] = Localization::translate('validation.field_type_required');
                $errors[] = Localization::translate('validation.field_type_required');
            }

            // Validate field options for select, radio, checkbox
            if (!empty($_POST['field_type']) && in_array($_POST['field_type'], ['select', 'radio', 'checkbox'])) {
                if (empty($_POST['field_options']) || trim($_POST['field_options']) === '') {
                    $fieldErrors['field_options'] = 'Field options are required for this field type';
                    $errors[] = 'Field options are required for this field type';
                } else {
                    $options = array_filter(array_map('trim', explode("\n", $_POST['field_options'])));
                    if (count($options) < 2) {
                        $fieldErrors['field_options'] = 'At least two options are required';
                        $errors[] = 'At least two options are required';
                    }
                }
            }

            // Check field name uniqueness
            if (!isset($fieldErrors['field_name'])) {
                $existingFields = $this->customFieldModel->getCustomFieldsByClient($clientId, false);
                foreach ($existingFields as $field) {
                    if (strtolower($field['field_name']) === strtolower(trim($_POST['field_name']))) {
                        $fieldErrors['field_name'] = Localization::translate('validation.field_name_exists');
                        $errors[] = Localization::translate('validation.field_name_exists');
                        break;
                    }
                }
            }

            // Check field label uniqueness
            if (!isset($fieldErrors['field_label'])) {
                $existingFields = $this->customFieldModel->getCustomFieldsByClient($clientId, false);
                foreach ($existingFields as $field) {
                    if (strtolower($field['field_label']) === strtolower(trim($_POST['field_label']))) {
                        $fieldErrors['field_label'] = 'A field with this label already exists';
                        $errors[] = 'A field with this label already exists';
                        break;
                    }
                }
            }

            if (!empty($errors)) {
                echo json_encode([
                    'success' => false,
                    'message' => implode('<br>', $errors),
                    'field_errors' => $fieldErrors
                ]);
                exit();
            }

            // Prepare field options for select, radio, checkbox
            $fieldOptions = null;
            if (in_array($_POST['field_type'], ['select', 'radio', 'checkbox']) && !empty($_POST['field_options'])) {
                $options = array_filter(array_map('trim', explode("\n", $_POST['field_options'])));
                if (!empty($options)) {
                    $fieldOptions = $options;
                }
            }

            // Get next field order
            $fieldOrder = $this->customFieldModel->getNextFieldOrder($clientId);

            $data = [
                'client_id' => $clientId,
                'field_name' => trim($_POST['field_name']),
                'field_label' => trim($_POST['field_label']),
                'field_type' => $_POST['field_type'],
                'field_options' => $fieldOptions,
                'is_required' => isset($_POST['is_required']) ? 1 : 0,
                'field_order' => $fieldOrder,
                'is_active' => 1
            ];

            if ($this->customFieldModel->createCustomField($data)) {
                echo json_encode([
                    'success' => true,
                    'message' => Localization::translate('success.custom_field_created')
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => Localization::translate('error.custom_field_create_failed')
                ]);
            }

        } catch (Exception $e) {
            error_log("CustomFieldController create error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => Localization::translate('error.unexpected_error')
            ]);
        }
        exit();
    }

    /**
     * Get custom fields for AJAX requests
     */
    public function getFields() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $clientId = $_SESSION['user']['client_id'] ?? null;
        $customFields = $this->customFieldModel->getCustomFieldsByClient($clientId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'fields' => $customFields]);
    }

    /**
     * Update custom field
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError(Localization::translate('error.invalid_request_method'), UrlHelper::url('users'));
            return;
        }

        try {
            $fieldId = $_POST['field_id'] ?? null;
            if (!$fieldId) {
                $this->toastError(Localization::translate('validation.field_id_required'), UrlHelper::url('users'));
                return;
            }

            // Verify field belongs to current client
            $currentClientId = $_SESSION['user']['client_id'] ?? null;
            $field = $this->customFieldModel->getCustomFieldById($fieldId, $currentClientId);
            if (!$field) {
                $this->toastError(Localization::translate('validation.field_not_found'), UrlHelper::url('users'));
                return;
            }

            $errors = [];

            // Validate input
            if (empty($_POST['field_name']) || trim($_POST['field_name']) === '') {
                $errors[] = Localization::translate('validation.field_name_required');
            }

            if (empty($_POST['field_label']) || trim($_POST['field_label']) === '') {
                $errors[] = Localization::translate('validation.field_label_required');
            }

            if (!empty($errors)) {
                $this->toastError(implode('<br>', $errors), UrlHelper::url('users'));
                return;
            }

            // Prepare field options
            $fieldOptions = null;
            if (in_array($_POST['field_type'], ['select', 'radio', 'checkbox']) && !empty($_POST['field_options'])) {
                $options = array_filter(array_map('trim', explode("\n", $_POST['field_options'])));
                if (!empty($options)) {
                    $fieldOptions = $options;
                }
            }

            $data = [
                'field_name' => trim($_POST['field_name']),
                'field_label' => trim($_POST['field_label']),
                'field_type' => $_POST['field_type'],
                'field_options' => $fieldOptions,
                'is_required' => isset($_POST['is_required']) ? 1 : 0,
                'field_order' => $field['field_order'], // Keep existing order
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];

            if ($this->customFieldModel->updateCustomField($fieldId, $data, $currentClientId)) {
                $this->toastSuccess(Localization::translate('success.custom_field_updated'), UrlHelper::url('users'));
            } else {
                $this->toastError(Localization::translate('error.custom_field_update_failed'), UrlHelper::url('users'));
            }

        } catch (Exception $e) {
            error_log("CustomFieldController update error: " . $e->getMessage());
            $this->toastError(Localization::translate('error.unexpected_error'), UrlHelper::url('users'));
        }
    }

    /**
     * Delete custom field
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        try {
            $fieldId = $_POST['field_id'] ?? null;
            if (!$fieldId) {
                http_response_code(400);
                echo json_encode(['error' => Localization::translate('validation.field_id_required')]);
                return;
            }

            // Verify field belongs to current client
            $currentClientId = $_SESSION['user']['client_id'] ?? null;
            $field = $this->customFieldModel->getCustomFieldById($fieldId, $currentClientId);
            if (!$field) {
                http_response_code(404);
                echo json_encode(['error' => Localization::translate('validation.field_not_found')]);
                return;
            }

            if ($this->customFieldModel->deleteCustomField($fieldId, $currentClientId)) {
                echo json_encode(['success' => true, 'message' => Localization::translate('success.custom_field_deleted')]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => Localization::translate('error.custom_field_delete_failed')]);
            }

        } catch (Exception $e) {
            error_log("CustomFieldController delete error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => Localization::translate('error.unexpected_error')]);
        }
    }

    /**
     * Helper method to show success toast and redirect
     */
    private function toastSuccess($message, $redirectUrl) {
        $encodedMessage = urlencode($message);
        $separator = strpos($redirectUrl, '?') !== false ? '&' : '?';
        header("Location: {$redirectUrl}{$separator}message={$encodedMessage}&type=success");
        exit;
    }

    /**
     * Helper method to show error toast and redirect
     */
    private function toastError($message, $redirectUrl) {
        $encodedMessage = urlencode($message);
        $separator = strpos($redirectUrl, '?') !== false ? '&' : '?';
        header("Location: {$redirectUrl}{$separator}message={$encodedMessage}&type=error");
        exit;
    }

    // ===================================
    // ROUTING-COMPATIBLE METHODS
    // ===================================

    /**
     * Save custom field - routing compatible method
     * Maps to: POST /users/custom-fields
     */
    public function save() {
        return $this->create();
    }
}
