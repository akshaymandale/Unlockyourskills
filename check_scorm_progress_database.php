<?php
/**
 * Check SCORM Progress Database
 * This script verifies that all required SCORM data is properly stored in the database
 */

// Database configuration - using same method as main application
$host = 'localhost';
$dbname = 'unlockyourskills';
$username = 'root';
$password = '';

try {
    // Try different connection methods for XAMPP (same as main app)
    $dsn_options = [
        "mysql:host=" . $host . ";dbname=" . $dbname,
        "mysql:host=" . $host . ";port=3306;dbname=" . $dbname,
        "mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=" . $dbname
    ];

    $pdo = null;
    $connected = false;
    
    foreach ($dsn_options as $dsn) {
        try {
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $connected = true;
            echo "<p style='color: green;'>✅ Connected to database using: " . $dsn . "</p>\n";
            break;
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠️ Connection failed with: " . $dsn . " - " . $e->getMessage() . "</p>\n";
            continue;
        }
    }

    if (!$connected) {
        throw new PDOException("Could not connect to database with any method");
    }
    
    echo "<h2>SCORM Progress Database Check</h2>\n";
    echo "<h3>1. Checking scorm_progress table structure</h3>\n";
    
    // Check table structure
    $stmt = $pdo->query("DESCRIBE scorm_progress");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>2. Checking scorm_progress table data</h3>\n";
    
    // Check all records in scorm_progress table
    $stmt = $pdo->query("SELECT * FROM scorm_progress ORDER BY id DESC LIMIT 10");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($records)) {
        echo "<p style='color: red;'>No records found in scorm_progress table!</p>\n";
    } else {
        echo "<p>Found " . count($records) . " records in scorm_progress table:</p>\n";
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>\n";
        echo "<tr>";
        foreach (array_keys($records[0]) as $header) {
            echo "<th style='padding: 5px;'>{$header}</th>";
        }
        echo "</tr>\n";
        
        foreach ($records as $record) {
            echo "<tr>";
            foreach ($record as $value) {
                if ($value === null) {
                    echo "<td style='padding: 5px; color: gray;'>NULL</td>";
                } else {
                    // Truncate long values for display
                    $displayValue = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                    echo "<td style='padding: 5px;'>{$displayValue}</td>";
                }
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h3>3. Checking specific SCORM data for course_id = 1, content_id = 58</h3>\n";
    
    // Check specific SCORM data
    $stmt = $pdo->prepare("
        SELECT * FROM scorm_progress 
        WHERE course_id = ? AND content_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([1, 58]);
    $specificRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($specificRecords)) {
        echo "<p style='color: red;'>No SCORM progress records found for course_id=1, content_id=58!</p>\n";
    } else {
        echo "<p>Found " . count($specificRecords) . " SCORM progress records for the specific content:</p>\n";
        
        foreach ($specificRecords as $index => $record) {
            echo "<h4>Record " . ($index + 1) . ":</h4>\n";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>\n";
            foreach ($record as $key => $value) {
                echo "<tr>";
                echo "<td style='padding: 5px; font-weight: bold;'>{$key}</td>";
                if ($value === null) {
                    echo "<td style='padding: 5px; color: gray;'>NULL</td>";
                } else {
                    // For long values like suspend_data, show full content
                    if (in_array($key, ['suspend_data', 'launch_data']) && strlen($value) > 100) {
                        echo "<td style='padding: 5px; max-width: 400px; word-wrap: break-word;'>";
                        echo "<pre style='white-space: pre-wrap;'>{$value}</pre>";
                        echo "</td>";
                    } else {
                        echo "<td style='padding: 5px;'>{$value}</td>";
                    }
                }
                echo "</tr>\n";
            }
            echo "</table>\n";
        }
    }
    
    echo "<h3>4. Checking user_course_progress table for overall progress</h3>\n";
    
    // Check user_course_progress table
    $stmt = $pdo->query("SELECT * FROM user_course_progress WHERE course_id = 1 AND user_id = 75 ORDER BY id DESC LIMIT 5");
    $courseProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($courseProgress)) {
        echo "<p style='color: red;'>No user_course_progress records found for course_id=1, user_id=75!</p>\n";
    } else {
        echo "<p>Found " . count($courseProgress) . " user_course_progress records:</p>\n";
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>\n";
        echo "<tr>";
        foreach (array_keys($courseProgress[0]) as $header) {
            echo "<th style='padding: 5px;'>{$header}</th>";
        }
        echo "</tr>\n";
        
        foreach ($courseProgress as $record) {
            echo "<tr>";
            foreach ($record as $value) {
                if ($value === null) {
                    echo "<td style='padding: 5px; color: gray;'>NULL</td>";
                } else {
                    $displayValue = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                    echo "<td style='padding: 5px;'>{$displayValue}</td>";
                }
            }
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<h3>5. Database Summary</h3>\n";
    
    // Get table counts
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM scorm_progress");
    $scormCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_course_progress");
    $courseCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<p><strong>Total records:</strong></p>\n";
    echo "<ul>";
    echo "<li>scorm_progress: {$scormCount} records</li>";
    echo "<li>user_course_progress: {$courseCount} records</li>";
    echo "</ul>\n";
    
    echo "<h3>6. Recommendations</h3>\n";
    
    if ($scormCount == 0) {
        echo "<p style='color: red;'>❌ CRITICAL: No SCORM progress records found. The SCORM tracking system is not working.</p>\n";
    } else {
        echo "<p style='color: green;'>✅ SCORM progress records exist. Basic tracking is working.</p>\n";
    }
    
    if (empty($specificRecords)) {
        echo "<p style='color: orange;'>⚠️ WARNING: No specific SCORM progress for the current content. Resume functionality may not work.</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Specific SCORM progress found. Resume functionality should work.</p>\n";
    }
    
    if (empty($courseProgress)) {
        echo "<p style='color: red;'>❌ CRITICAL: No user course progress records. Overall progress tracking is not working.</p>\n";
    } else {
        echo "<p style='color: green;'>✅ User course progress records exist. Overall tracking is working.</p>\n";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>
