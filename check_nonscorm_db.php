<?php
// Simple script to check Non-SCORM packages in database
require_once 'config/database.php';

// Create database connection
$database = new Database();
$db = $database->connect();

echo "<h2>Non-SCORM Packages in Database</h2>";

try {
    // Check if the table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'non_scorm_package'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "<p><strong>❌ Table 'non_scorm_package' does not exist!</strong></p>";
    } else {
        echo "<p><strong>✅ Table 'non_scorm_package' exists!</strong></p>";
        
        // Get all Non-SCORM packages
        $stmt = $db->prepare("SELECT * FROM non_scorm_package WHERE is_deleted = 0 ORDER BY created_at DESC");
        $stmt->execute();
        $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Total packages:</strong> " . count($packages) . "</p>";
        
        if (!empty($packages)) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Content Type</th><th>Version</th><th>Mobile Support</th><th>Created At</th></tr>";
            foreach ($packages as $package) {
                echo "<tr>";
                echo "<td>{$package['id']}</td>";
                echo "<td>{$package['title']}</td>";
                echo "<td>{$package['content_type']}</td>";
                echo "<td>{$package['version']}</td>";
                echo "<td>{$package['mobile_support']}</td>";
                echo "<td>{$package['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<h3>Raw Data:</h3>";
            echo "<pre>";
            print_r($packages);
            echo "</pre>";
        } else {
            echo "<p><strong>No Non-SCORM packages found in database.</strong></p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php?controller=VLRController'>Go to VLR Page</a></p>";
?>
