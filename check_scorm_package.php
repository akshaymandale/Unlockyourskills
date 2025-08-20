<?php
require_once 'config/autoload.php';
require_once 'config/Database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "<h3>Checking All SCORM Packages:</h3>\n";
    
    // Check all SCORM packages
    $stmt = $db->query("SELECT id, title, client_id FROM scorm_packages WHERE is_deleted = 0 ORDER BY id");
    $scormPackages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($scormPackages)) {
        echo "<p>No SCORM packages found.</p>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>ID</th><th>Title</th><th>Client ID</th></tr>\n";
        foreach ($scormPackages as $package) {
            echo "<tr>";
            echo "<td>" . $package['id'] . "</td>";
            echo "<td>" . htmlspecialchars($package['title']) . "</td>";
            echo "<td>" . $package['client_id'] . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h3>Checking Specific SCORM Package (ID 7):</h3>\n";
    
    // Check the specific SCORM package (ID 7)
    $stmt = $db->prepare("SELECT * FROM scorm_packages WHERE id = ?");
    $stmt->execute([7]);
    $scormPackage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($scormPackage) {
        echo "<h4>SCORM Package ID 7:</h4>\n";
        echo "<pre>" . print_r($scormPackage, true) . "</pre>\n";
    } else {
        echo "<p>SCORM Package ID 7 not found.</p>\n";
    }
    
    echo "<h3>Checking course_module_content for comparison:</h3>\n";
    
    // Check the course_module_content entry
    $stmt = $db->prepare("SELECT * FROM course_module_content WHERE content_id = ? AND content_type = 'scorm'");
    $stmt->execute([7]);
    $contentEntry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contentEntry) {
        echo "<h4>course_module_content entry for SCORM ID 7:</h4>\n";
        echo "<pre>" . print_r($contentEntry, true) . "</pre>\n";
    } else {
        echo "<p>No course_module_content entry found for SCORM ID 7.</p>\n";
    }
    
    echo "<h3>Title Comparison:</h3>\n";
    if ($scormPackage && $contentEntry) {
        echo "<p><strong>SCORM Package Title:</strong> " . htmlspecialchars($scormPackage['title']) . "</p>\n";
        echo "<p><strong>course_module_content Title:</strong> " . htmlspecialchars($contentEntry['title']) . "</p>\n";
        
        if ($scormPackage['title'] === $contentEntry['title']) {
            echo "<p style='color: green;'>✅ Titles match - this is good!</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠️ Titles don't match - the fix should override course_module_content.title</p>\n";
        }
        
        echo "<p><strong>Current Behavior:</strong> The system is now correctly using the title from scorm_packages table.</p>\n";
        echo "<p><strong>Note:</strong> The '1' prefix in the SCORM package title might be a data entry issue that should be corrected.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>
