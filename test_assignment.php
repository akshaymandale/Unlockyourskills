<?php
session_start();
require_once 'models/VLRModel.php';

// Set up a test session
$_SESSION['user']['client_id'] = 2;
$_SESSION['user']['system_role'] = 'admin';

$model = new VLRModel();

echo "Testing getAssignmentPackages method...\n";
echo "Client ID: 2\n";

$assignments = $model->getAssignmentPackages(2);

echo "Result count: " . count($assignments) . "\n";
if (!empty($assignments)) {
    echo "First assignment: " . json_encode($assignments[0]) . "\n";
} else {
    echo "No assignments found\n";
}

// Test without client filtering
echo "\nTesting without client filtering...\n";
$allAssignments = $model->getAssignmentPackages(null);
echo "Result count: " . count($allAssignments) . "\n";
if (!empty($allAssignments)) {
    echo "First assignment: " . json_encode($allAssignments[0]) . "\n";
} else {
    echo "No assignments found\n";
}
?> 