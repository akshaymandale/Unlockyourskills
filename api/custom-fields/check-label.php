<?php
/**
 * API endpoint to check if custom field label already exists
 */

// Prevent any output before JSON
ob_start();

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(0);

// Start session and include necessary files
session_start();

// Set JSON response header
header('Content-Type: application/json');

// Clean any previous output
ob_clean();

try {
    require_once '../../config/Database.php';
    require_once '../../models/CustomFieldModel.php';
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to load required files']);
    exit;
}

// Support both GET and POST for field_id
$fieldId = $_GET['field_id'] ?? $_POST['field_id'] ?? null;
if (!$fieldId) {
    echo json_encode(['success' => false, 'message' => 'Field ID required']);
    exit;
}

$customFieldModel = new CustomFieldModel();
$field = $customFieldModel->getCustomFieldById($fieldId);

if ($field && !empty($field['field_options'])) {
    $options = $field['field_options'];
    // If options is a string (e.g., 'hr\r\ntechno'), split it into an array
    if (is_string($options)) {
        $options = preg_split('/\r\n|\r|\n/', $options);
        $options = array_filter(array_map('trim', $options)); // Remove empty/whitespace
    }
    // Now filter to only those options that are actually used by users
    $db = new Database();
    $conn = $db->connect();
    $usedOptions = [];
    $stmt = $conn->prepare("SELECT DISTINCT field_value FROM custom_field_values WHERE custom_field_id = :field_id AND is_deleted = 0 AND field_value IS NOT NULL AND field_value != ''");
    $stmt->execute([':field_id' => $fieldId]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    // For checkboxes, values may be comma-separated, so split them
    foreach ($rows as $row) {
        foreach (explode(',', $row) as $val) {
            $val = trim($val);
            if ($val !== '') {
                $usedOptions[$val] = true;
            }
        }
    }
    $filteredOptions = array_values(array_filter($options, function($opt) use ($usedOptions) {
        return isset($usedOptions[$opt]);
    }));
    echo json_encode(['success' => true, 'options' => $filteredOptions]);
} else {
    echo json_encode(['success' => true, 'options' => []]);
}
?>
