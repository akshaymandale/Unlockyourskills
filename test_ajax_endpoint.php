<?php
// Test the actual AJAX endpoint
session_start();

// Set up the session like clientUsers() would
$_SESSION['client_management_mode'] = true;

// Simulate the AJAX request
$_POST['client_id'] = '18';
$_POST['page'] = '1';
$_POST['search'] = '';
$_POST['user_status'] = '';
$_POST['locked_status'] = '';
$_POST['user_role'] = '';
$_POST['gender'] = '';

// Simulate super admin user
$_SESSION['user'] = [
    'id' => 1,
    'system_role' => 'super_admin',
    'client_id' => null
];

echo "=== Testing AJAX Endpoint ===\n\n";

echo "Session before AJAX call:\n";
echo "client_management_mode: " . (isset($_SESSION['client_management_mode']) ? 'SET' : 'NOT SET') . "\n";
echo "user system_role: " . ($_SESSION['user']['system_role'] ?? 'NOT SET') . "\n\n";

echo "POST data:\n";
echo "client_id: " . ($_POST['client_id'] ?? 'NOT SET') . "\n\n";

// Include the controller and call ajaxSearch
require_once 'controllers/UserManagementController.php';

// Capture output
ob_start();

try {
    $controller = new UserManagementController();
    $controller->ajaxSearch();
    $output = ob_get_contents();
} catch (Exception $e) {
    $output = "Error: " . $e->getMessage();
} finally {
    ob_end_clean();
}

echo "AJAX Response:\n";
echo $output . "\n";

echo "Session after AJAX call:\n";
echo "client_management_mode: " . (isset($_SESSION['client_management_mode']) ? 'SET' : 'NOT SET') . "\n";

echo "\n=== Test Complete ===\n";
?> 