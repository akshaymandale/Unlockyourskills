<?php
require_once 'config/Database.php';

echo "<h2>Fixing Client ID Mismatch</h2>";

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Check current data
    echo "<h3>Current SCORM Progress Data:</h3>";
    $stmt = $conn->prepare("
        SELECT * FROM scorm_progress 
        WHERE user_id = 75 AND course_id = 1 AND content_id = 58
    ");
    $stmt->execute();
    $currentData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentData) {
        echo "<p><strong>Current data:</strong></p>";
        echo "<pre>" . print_r($currentData, true) . "</pre>";
        
        // Update client_id from 2 to 1
        echo "<h3>Updating client_id from 2 to 1...</h3>";
        
        $updateStmt = $conn->prepare("
            UPDATE scorm_progress 
            SET client_id = 1 
            WHERE user_id = 75 AND course_id = 1 AND content_id = 58
        ");
        
        $result = $updateStmt->execute();
        
        if ($result) {
            echo "<p><strong>✅ Successfully updated client_id to 1</strong></p>";
            
            // Verify the update
            $stmt->execute();
            $updatedData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Updated data:</strong></p>";
            echo "<pre>" . print_r($updatedData, true) . "</pre>";
            
        } else {
            echo "<p><strong>❌ Failed to update client_id</strong></p>";
        }
        
    } else {
        echo "<p><strong>❌ No SCORM progress data found</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>
