<?php
// Start session and simulate a logged-in user
session_start();

// Set up session data like a logged-in user (matching the posts in database)
$_SESSION['loggedin'] = true;
$_SESSION['username'] = 'superadmin@unlockyourskills.com';
$_SESSION['client_code'] = 'TEST_CLIENT';
$_SESSION['id'] = 42;
$_SESSION['lang'] = 'en';

$_SESSION['user'] = [
    'id' => 42,
    'profile_id' => 'SUPER_ADMIN_001',
    'client_id' => 1,  // This matches the posts in database
    'full_name' => 'Super Administrator',
    'email' => 'superadmin@unlockyourskills.com',
    'user_role' => 'Super Administrator',
    'system_role' => 'super_admin',
    'client_name' => 'Test Client'
];

echo "<h2>🔧 Testing Social Feed with Session</h2>";
echo "<p><strong>Session Data Set:</strong></p>";
echo "<pre>" . json_encode($_SESSION, JSON_PRETTY_PRINT) . "</pre>";

// Now test the API
echo "<h3>Testing SocialFeedController::list()</h3>";

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