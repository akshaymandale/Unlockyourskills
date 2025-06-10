<?php
// Check if non_scorm_package table exists and what data it contains

require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->connect();
    
    echo "<h2>Checking non_scorm_package table...</h2>";
    
    // Check if table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'non_scorm_package'");
    $stmt->execute();
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<p style='color: green;'>✓ Table 'non_scorm_package' exists</p>";
        
        // Check table structure
        echo "<h3>Table Structure:</h3>";
        $stmt = $conn->prepare("DESCRIBE non_scorm_package");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check data in table
        echo "<h3>Data in Table:</h3>";
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM non_scorm_package");
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Total records: " . $count['count'] . "</p>";
        
        if ($count['count'] > 0) {
            echo "<h4>All Records:</h4>";
            $stmt = $conn->prepare("SELECT * FROM non_scorm_package ORDER BY created_at DESC");
            $stmt->execute();
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse;'>";
            if (!empty($records)) {
                // Table headers
                echo "<tr>";
                foreach (array_keys($records[0]) as $header) {
                    echo "<th>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr>";
                
                // Table data
                foreach ($records as $record) {
                    echo "<tr>";
                    foreach ($record as $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠ Table is empty</p>";
        }
        
        // Check non-deleted records specifically
        echo "<h3>Non-deleted Records (is_deleted = 0):</h3>";
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM non_scorm_package WHERE is_deleted = 0");
        $stmt->execute();
        $nonDeletedCount = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Non-deleted records: " . $nonDeletedCount['count'] . "</p>";
        
    } else {
        echo "<p style='color: red;'>✗ Table 'non_scorm_package' does NOT exist</p>";
        
        echo "<h3>Creating table...</h3>";
        
        // Create the table
        $createTableSQL = "
        CREATE TABLE non_scorm_package (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content_type ENUM('simulation', 'virtual-reality', 'augmented-reality', 'gamification', 'microlearning', 'adaptive-learning') NOT NULL,
            version_number VARCHAR(50) NOT NULL,
            mobile_support ENUM('Yes', 'No') DEFAULT 'No',
            language_support VARCHAR(100) NOT NULL,
            time_limit INT DEFAULT NULL,
            description TEXT,
            tags TEXT,
            simulation_url VARCHAR(500) DEFAULT NULL,
            vr_platform VARCHAR(100) DEFAULT NULL,
            vr_headset_compatibility TEXT DEFAULT NULL,
            ar_device_compatibility TEXT DEFAULT NULL,
            ar_marker_type VARCHAR(100) DEFAULT NULL,
            game_mechanics TEXT DEFAULT NULL,
            scoring_system VARCHAR(200) DEFAULT NULL,
            microlearning_duration INT DEFAULT NULL,
            microlearning_format VARCHAR(100) DEFAULT NULL,
            adaptive_algorithm VARCHAR(200) DEFAULT NULL,
            personalization_features TEXT DEFAULT NULL,
            created_by VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_deleted TINYINT(1) DEFAULT 0
        )";
        
        $stmt = $conn->prepare($createTableSQL);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Table created successfully</p>";
            
            // Insert test data
            echo "<h3>Inserting test data...</h3>";
            $insertSQL = "
            INSERT INTO non_scorm_package (
                title, content_type, version_number, mobile_support, language_support, 
                time_limit, description, tags, simulation_url, created_by
            ) VALUES (
                'Test Non-SCORM Package', 'simulation', '1.0', 'Yes', 'English', 
                30, 'This is a test non-SCORM package', 'test,simulation,demo', 
                'https://example.com/simulation', 'admin@123'
            )";
            
            $stmt = $conn->prepare($insertSQL);
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✓ Test data inserted successfully</p>";
            } else {
                echo "<p style='color: red;'>✗ Failed to insert test data</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Failed to create table</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
