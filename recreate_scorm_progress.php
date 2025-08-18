<?php
require_once 'config/Database.php';

echo "<h2>Recreating SCORM Progress Entry</h2>";

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Recreate the SCORM progress entry
    echo "<h3>Creating SCORM progress entry...</h3>";
    
    $insertStmt = $conn->prepare("
        INSERT INTO scorm_progress (
            user_id, course_id, content_id, scorm_package_id, client_id,
            lesson_status, lesson_location, score_raw, score_min, score_max,
            total_time, session_time, suspend_data, launch_data, interactions, objectives
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $insertStmt->execute([
        75,           // user_id
        1,            // course_id
        58,           // content_id
        8,            // scorm_package_id
        2,            // client_id (your actual client_id)
        'incomplete', // lesson_status
        '6242f2c04db7c', // lesson_location
        null,         // score_raw (NULL instead of empty string)
        null,         // score_min (NULL instead of empty string)
        null,         // score_max (NULL instead of empty string)
        null,         // total_time (NULL instead of empty string)
        null,         // session_time (NULL instead of empty string)
        '{"h":{"6242f2c025ba0":{"e":1},"6242f2c02c330":{"e":1},"6242f2c033d2b":{"e":1},"6242f2c03af7e":{"e":1},"6242f2c046ef2":{"e":1},"6242f2c04db7c":{"e":1}},"c":"6242f2c04db7c"}', // suspend_data
        null,         // launch_data (NULL instead of empty string)
        null,         // interactions (NULL instead of empty string)
        null          // objectives (NULL instead of empty string)
    ]);
    
    if ($result) {
        echo "<p><strong>✅ Successfully created SCORM progress entry</strong></p>";
        
        // Verify the creation
        $stmt = $conn->prepare("
            SELECT * FROM scorm_progress 
            WHERE user_id = 75 AND course_id = 1 AND content_id = 58
        ");
        $stmt->execute();
        $createdData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Created data:</strong></p>";
        echo "<pre>" . print_r($createdData, true) . "</pre>";
        
    } else {
        echo "<p><strong>❌ Failed to create SCORM progress entry</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>
