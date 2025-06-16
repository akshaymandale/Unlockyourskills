<?php

require_once 'models/CustomFieldModel.php';
require_once 'config/Localization.php';

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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request method.', 'index.php?controller=UserManagementController');
            return;
        }

        try {
            $clientId = $_SESSION['user']['client_id'] ?? null;

            if (!$clientId) {
                $this->toastError('Client ID not found in session.', 'index.php?controller=UserManagementController');
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

            if (empty($_POST['field_type']) || trim($_POST['field_type']) === '') {
                $errors[] = Localization::translate('validation.field_type_required');
            }

            // Validate field name uniqueness
            $existingFields = $this->customFieldModel->getCustomFieldsByClient($clientId, false);
            foreach ($existingFields as $field) {
                if (strtolower($field['field_name']) === strtolower(trim($_POST['field_name']))) {
                    $errors[] = Localization::translate('validation.field_name_exists');
                    break;
                }
            }

            if (!empty($errors)) {
                $this->toastError(implode('<br>', $errors), 'index.php?controller=UserManagementController');
                return;
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
                $this->toastSuccess(Localization::translate('success.custom_field_created'), 'index.php?controller=UserManagementController');
            } else {
                $this->toastError(Localization::translate('error.custom_field_create_failed'), 'index.php?controller=UserManagementController');
            }

        } catch (Exception $e) {
            error_log("CustomFieldController create error: " . $e->getMessage());
            $this->toastError(Localization::translate('error.unexpected_error'), 'index.php?controller=UserManagementController');
        }
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
            $this->toastError('Invalid request method.', 'index.php?controller=UserManagementController');
            return;
        }

        try {
            $fieldId = $_POST['field_id'] ?? null;
            if (!$fieldId) {
                $this->toastError(Localization::translate('validation.field_id_required'), 'index.php?controller=UserManagementController');
                return;
            }

            // Verify field belongs to current client
            $field = $this->customFieldModel->getCustomFieldById($fieldId);
            $currentClientId = $_SESSION['user']['client_id'] ?? null;
            if (!$field || $field['client_id'] != $currentClientId) {
                $this->toastError(Localization::translate('validation.field_not_found'), 'index.php?controller=UserManagementController');
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
                $this->toastError(implode('<br>', $errors), 'index.php?controller=UserManagementController');
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

            if ($this->customFieldModel->updateCustomField($fieldId, $data)) {
                $this->toastSuccess(Localization::translate('success.custom_field_updated'), 'index.php?controller=UserManagementController');
            } else {
                $this->toastError(Localization::translate('error.custom_field_update_failed'), 'index.php?controller=UserManagementController');
            }

        } catch (Exception $e) {
            error_log("CustomFieldController update error: " . $e->getMessage());
            $this->toastError(Localization::translate('error.unexpected_error'), 'index.php?controller=UserManagementController');
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
            $field = $this->customFieldModel->getCustomFieldById($fieldId);
            $currentClientId = $_SESSION['user']['client_id'] ?? null;
            if (!$field || $field['client_id'] != $currentClientId) {
                http_response_code(404);
                echo json_encode(['error' => Localization::translate('validation.field_not_found')]);
                return;
            }

            if ($this->customFieldModel->deleteCustomField($fieldId)) {
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
        header("Location: {$redirectUrl}&message={$encodedMessage}&type=success");
        exit;
    }

    /**
     * Helper method to show error toast and redirect
     */
    private function toastError($message, $redirectUrl) {
        $encodedMessage = urlencode($message);
        header("Location: {$redirectUrl}&message={$encodedMessage}&type=error");
        exit;
    }
}
