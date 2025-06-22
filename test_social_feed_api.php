<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session to simulate being logged in
session_start();

// Set up session data like a logged-in user
$_SESSION['user'] = [
    'id' => 42,
    'client_id' => 1,
    'role' => 'super_admin'
];

echo "<h2>Testing Social Feed API</h2>";
echo "<p><strong>Session user:</strong> " . json_encode($_SESSION['user']) . "</p>";

try {
    // Include the controller
    require_once 'controllers/SocialFeedController.php';
    
    // Create controller instance
    $controller = new SocialFeedController();
    
    // Simulate the list action
    $_GET['page'] = 1;
    $_GET['limit'] = 10;
    $_GET['search'] = '';
    $_GET['post_type'] = '';
    $_GET['visibility'] = '';
    $_GET['status'] = '';
    $_GET['date_from'] = '';
    $_GET['date_to'] = '';
    
    echo "<p><strong>Calling list method...</strong></p>";
    
    // Call the list method
    $controller->list();
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?> 