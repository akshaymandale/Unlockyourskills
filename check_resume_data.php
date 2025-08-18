<?php
// Check Resume Position Data
require_once 'config/autoload.php';

// Configure session to match main application
session_save_path('/Applications/XAMPP/xamppfiles/htdocs/Unlockyourskills/sessions');
session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'secure' => false,
    'samesite' => 'Lax'
]);

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    echo "Please log in first\n";
    exit;
}

$userId = $_SESSION['user']['id'];
$clientId = $_SESSION['user']['client_id'];
$courseId = 1; // Course 12
$contentId = 49; // SCORM content

echo "Checking Resume Position Data for User ID: $userId, Client ID: $clientId\n";
echo "Course ID: $courseId, Content ID: $contentId\n\n";

try {
    // Use the same database connection method as the main app
    $host = 'localhost';
    $dbname = 'unlockyourskills';
    $username = 'root';
    $password = '';
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Check user_course_progress for resume position
    echo "1. Checking user_course_progress resume position...\n";
    $stmt = $conn->prepare("
        SELECT current_module_id, current_content_id, resume_position, updated_at
        FROM user_course_progress 
        WHERE user_id = ? AND course_id = ? AND client_id = ?
    ");
    $stmt->execute([$userId, $courseId, $clientId]);
    $courseProgress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($courseProgress) {
        echo "   ✅ Course progress found:\n";
        echo "      - Current Module ID: " . ($courseProgress['current_module_id'] ?? 'NULL') . "\n";
        echo "      - Current Content ID: " . ($courseProgress['current_content_id'] ?? 'NULL') . "\n";
        echo "      - Resume Position: " . ($courseProgress['resume_position'] ?? 'NULL') . "\n";
        echo "      - Updated: " . $courseProgress['updated_at'] . "\n";
        
        if ($courseProgress['resume_position']) {
            $resumeData = json_decode($courseProgress['resume_position'], true);
            echo "      - Decoded Resume Data: " . json_encode($resumeData, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "   ❌ No course progress found\n";
    }
    
    // 2. Check SCORM progress for detailed resume data
    echo "\n2. Checking SCORM progress resume data...\n";
    $stmt = $conn->prepare("
        SELECT lesson_location, suspend_data, lesson_status, updated_at
        FROM scorm_progress 
        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
    ");
    $stmt->execute([$userId, $courseId, $contentId, $clientId]);
    $scormProgress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($scormProgress) {
        echo "   ✅ SCORM progress found:\n";
        echo "      - Lesson Location: " . ($scormProgress['lesson_location'] ?? 'NULL') . "\n";
        echo "      - Suspend Data: " . ($scormProgress['suspend_data'] ?? 'NULL') . "\n";
        echo "      - Lesson Status: " . ($scormProgress['lesson_status'] ?? 'NULL') . "\n";
        echo "      - Updated: " . $scormProgress['updated_at'] . "\n";
        
        if ($scormProgress['suspend_data']) {
            $suspendData = json_decode($scormProgress['suspend_data'], true);
            echo "      - Decoded Suspend Data: " . json_encode($suspendData, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "   ❌ No SCORM progress found\n";
    }
    
    // 3. Check if resume position is being set when content is closed
    echo "\n3. Checking if resume position is being set...\n";
    $stmt = $conn->prepare("
        SELECT resume_position, updated_at
        FROM user_course_progress 
        WHERE user_id = ? AND course_id = ? AND client_id = ? AND resume_position IS NOT NULL
        ORDER BY updated_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId, $courseId, $clientId]);
    $resumeRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resumeRecord) {
        echo "   ✅ Resume position found:\n";
        echo "      - Resume Position: " . $resumeRecord['resume_position'] . "\n";
        echo "      - Updated: " . $resumeRecord['updated_at'] . "\n";
    } else {
        echo "   ❌ No resume position data found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
