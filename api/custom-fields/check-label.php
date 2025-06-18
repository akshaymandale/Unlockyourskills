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

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Check authentication
$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser || !in_array($currentUser['system_role'], ['admin', 'super_admin'])) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Get field label from POST data
$fieldLabel = $_POST['field_label'] ?? '';
$excludeId = $_POST['exclude_id'] ?? null;

if (empty($fieldLabel)) {
    echo json_encode(['exists' => false]);
    exit;
}

try {
    // Get client ID for filtering
    $clientId = null;
    if ($currentUser['system_role'] === 'admin') {
        $clientId = $currentUser['client_id'];
    }

    // Check if field label exists
    $customFieldModel = new CustomFieldModel();
    $exists = $customFieldModel->checkFieldLabelExists($fieldLabel, $clientId, $excludeId);

    echo json_encode(['exists' => $exists]);
    
} catch (Exception $e) {
    error_log("API check-label error: " . $e->getMessage());
    echo json_encode(['error' => 'Internal server error']);
}
?>
