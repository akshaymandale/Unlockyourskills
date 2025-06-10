<?php
// Simple script to add a test Non-SCORM package
require_once 'config/database.php';

// Create database connection
$database = new Database();
$db = $database->connect();

echo "<h2>Adding Test Non-SCORM Package</h2>";

try {
    // Insert a test Non-SCORM package
    $stmt = $db->prepare("INSERT INTO non_scorm_package (title, content_type, version, mobile_support, tags, created_by, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        'Test HTML5 Interactive Content',
        'html5',
        '1.0',
        'Yes',
        'test,html5,interactive',
        1,
        'This is a test HTML5 interactive content package for testing purposes.'
    ]);
    
    if ($result) {
        $insertId = $db->lastInsertId();
        echo "<p style='color: green;'>✅ Test Non-SCORM package created successfully!</p>";
        echo "<p>Inserted record ID: $insertId</p>";
        
        // Verify the record was created
        $stmt = $db->prepare("SELECT * FROM non_scorm_package WHERE id = ?");
        $stmt->execute([$insertId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            echo "<h3>Created Record:</h3>";
            echo "<ul>";
            echo "<li><strong>ID:</strong> " . $record['id'] . "</li>";
            echo "<li><strong>Title:</strong> " . htmlspecialchars($record['title']) . "</li>";
            echo "<li><strong>Content Type:</strong> " . htmlspecialchars($record['content_type']) . "</li>";
            echo "<li><strong>Version:</strong> " . htmlspecialchars($record['version']) . "</li>";
            echo "<li><strong>Mobile Support:</strong> " . $record['mobile_support'] . "</li>";
            echo "<li><strong>Tags:</strong> " . htmlspecialchars($record['tags']) . "</li>";
            echo "<li><strong>Is Deleted:</strong> " . $record['is_deleted'] . "</li>";
            echo "<li><strong>Created At:</strong> " . $record['created_at'] . "</li>";
            echo "</ul>";
        }
        
        echo "<p><a href='index.php?controller=VLRController' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to VLR Page to See Result</a></p>";
        
    } else {
        echo "<p style='color: red;'>❌ Failed to create test record</p>";
        $errorInfo = $stmt->errorInfo();
        echo "<p>Error: " . print_r($errorInfo, true) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>
