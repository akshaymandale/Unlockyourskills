<?php
require_once 'config/autoload.php';
require_once 'config/Database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "<h3>Table Structure:</h3>\n";
    
    // Check course_modules structure
    echo "<h4>course_modules table structure:</h4>\n";
    $stmt = $db->query("DESCRIBE course_modules");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h4>course_module_content table structure:</h4>\n";
    $stmt = $db->query("DESCRIBE course_module_content");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>\n";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>Sample Data:</h3>\n";
    
    // Check course_modules with correct column names
    $stmt = $db->query("SELECT * FROM course_modules LIMIT 3");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($modules)) {
        echo "<p>No modules found in course_modules table.</p>\n";
    } else {
        echo "<h4>course_modules sample data:</h4>\n";
        echo "<pre>" . print_r($modules, true) . "</pre>\n";
    }
    
    // Check course_module_content
    $stmt = $db->query("SELECT * FROM course_module_content LIMIT 3");
    $content = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($content)) {
        echo "<p>No content found in course_module_content table.</p>\n";
    } else {
        echo "<h4>course_module_content sample data:</h4>\n";
        echo "<pre>" . print_r($content, true) . "</pre>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}
?>
