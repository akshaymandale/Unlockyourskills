<?php
// Check SCORM Progress Database
session_start();

// Simulate user session
$_SESSION['user'] = [
    'id' => 75,
    'client_id' => 2
];

require_once 'config/Database.php';

try {
    $db = new Database();
    $conn = $db->connect();

    $userId = 75;
    $courseId = 1;
    $contentId = 58;
    $clientId = 2;

    echo "<h2>Checking SCORM Progress in Database</h2>";
    echo "<p>User ID: $userId, Course ID: $courseId, Content ID: $contentId, Client ID: $clientId</p>";

    // Check scorm_progress table
    $stmt = $conn->prepare("SELECT * FROM scorm_progress WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?");
    $stmt->execute([$userId, $courseId, $contentId, $clientId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($record) {
        echo "<h3>SCORM Progress Record Found:</h3>";
        echo "<pre>" . print_r($record, true) . "</pre>";
        
        // Check specific fields
        echo "<h3>Key Fields:</h3>";
        echo "<p><strong>lesson_location:</strong> " . ($record['lesson_location'] ?? 'NULL') . "</p>";
        echo "<p><strong>suspend_data:</strong> " . ($record['suspend_data'] ?? 'NULL') . "</p>";
        echo "<p><strong>lesson_status:</strong> " . ($record['lesson_status'] ?? 'NULL') . "</p>";
        echo "<p><strong>updated_at:</strong> " . ($record['updated_at'] ?? 'NULL') . "</p>";
    } else {
        echo "<p><strong>No record found in scorm_progress table!</strong></p>";
    }

    // Also check if there are any records at all for this user
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM scorm_progress WHERE user_id = ?");
    $stmt->execute([$userId]);
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Total SCORM Progress Records for User $userId:</h3>";
    echo "<p>" . $total['total'] . " records</p>";

    // Show all records for this user
    $stmt = $conn->prepare("SELECT * FROM scorm_progress WHERE user_id = ? ORDER BY updated_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $allRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($allRecords) {
        echo "<h3>Recent SCORM Progress Records:</h3>";
        foreach ($allRecords as $i => $rec) {
            echo "<h4>Record " . ($i + 1) . ":</h4>";
            echo "<pre>" . print_r($rec, true) . "</pre>";
        }
    }

} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
}
?>
