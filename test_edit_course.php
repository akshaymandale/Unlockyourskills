<?php
// Simple test script to verify edit course functionality
require_once 'config/autoload.php';

// Start session for testing
session_start();

// Mock session data
$_SESSION['id'] = 1;
$_SESSION['user'] = ['client_id' => 1];

// Test the editCourse method
try {
    require_once 'controllers/CourseCreationController.php';
    $controller = new CourseCreationController();
    
    // Test with a course ID
    $_GET['id'] = 1; // Assuming course ID 1 exists
    
    echo "Testing editCourse method...\n";
    $controller->editCourse();
    echo "✅ editCourse method executed successfully\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 