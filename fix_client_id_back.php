<?php
require_once 'config/Database.php';

echo "<h2>Fixing Client ID Back to 2</h2>";

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Update client_id back to 2
    echo "<h3>Updating client_id back to 2...</h3>";
    
    $updateStmt = $conn->prepare("
        UPDATE scorm_progress 
        SET client_id = 2 
        WHERE user_id = 75 AND course_id = 1 AND content_id = 58
    ");
    
    $result = $updateStmt->execute();
    
    if ($result) {
        echo "<p><strong>✅ Successfully updated client_id back to 2</strong></p>";
        
        // Verify the update
        $stmt = $conn->prepare("
            SELECT * FROM scorm_progress 
            WHERE user_id = 75 AND course_id = 1 AND content_id = 58
        ");
        $stmt->execute();
        $updatedData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Updated data:</strong></p>";
        echo "<pre>" . print_r($updatedData, true) . "</pre>";
        
    } else {
        echo "<p><strong>❌ Failed to update client_id</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>
