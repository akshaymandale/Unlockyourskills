<?php
require_once 'config/autoload.php';

// Start session for testing
session_start();

// Mock session data
$_SESSION['id'] = 1;
$_SESSION['user'] = ['client_id' => 2]; // Use client_id 2 to match the courses

try {
    require_once 'controllers/CourseCreationController.php';
    
    // Mock the GET parameter
    $_GET['id'] = 1;
    
    echo "Testing editCourse Controller Method:\n";
    echo "=====================================\n";
    
    $controller = new CourseCreationController();
    
    // Capture the output
    ob_start();
    $controller->editCourse();
    $output = ob_get_clean();
    
    echo "Modal HTML Length: " . strlen($output) . " characters\n";
    echo "First 500 characters:\n";
    echo substr($output, 0, 500) . "\n";
    
    // Check if the course name is in the output
    if (strpos($output, 'course testing') !== false) {
        echo "\n✅ Course name found in modal output!\n";
    } else {
        echo "\n❌ Course name NOT found in modal output\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 