<?php
// Check Resume Position in user_course_progress table
require_once 'config/Database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    $userId = 75;
    $courseId = 1;
    $clientId = 2;
    
    echo "Checking Resume Position for User ID: $userId, Course ID: $courseId, Client ID: $clientId\n\n";
    
    // Check user_course_progress
    $stmt = $conn->prepare("
        SELECT current_module_id, current_content_id, resume_position, updated_at
        FROM user_course_progress 
        WHERE user_id = ? AND course_id = ? AND client_id = ?
    ");
    $stmt->execute([$userId, $courseId, $clientId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($record) {
        echo "✅ Course Progress Record Found:\n";
        echo "   - Current Module ID: " . ($record['current_module_id'] ?? 'NULL') . "\n";
        echo "   - Current Content ID: " . ($record['current_content_id'] ?? 'NULL') . "\n";
        echo "   - Resume Position: " . ($record['resume_position'] ?? 'NULL') . "\n";
        echo "   - Updated: " . $record['updated_at'] . "\n";
        
        if ($record['resume_position']) {
            $resumeData = json_decode($record['resume_position'], true);
            echo "   - Decoded Resume Data: " . json_encode($resumeData, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ No course progress record found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>



