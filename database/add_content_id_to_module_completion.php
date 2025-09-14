<?php
/**
 * Migration script to add content_id column to module_completion table
 * This column will track the specific content that was completed
 */

require_once __DIR__ . '/../config/Database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "=== Adding content_id column to module_completion table ===\n\n";
    
    // Check if column already exists
    $checkSql = "SHOW COLUMNS FROM module_completion LIKE 'content_id'";
    $stmt = $conn->prepare($checkSql);
    $stmt->execute();
    $columnExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($columnExists) {
        echo "✓ content_id column already exists in module_completion table\n";
    } else {
        // Add the content_id column
        $sql = "ALTER TABLE module_completion 
                ADD COLUMN content_id INT NULL 
                COMMENT 'ID from course_module_content, course_prerequisites, or course_post_requisites' 
                AFTER module_id";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute();
        
        if ($result) {
            echo "✓ Successfully added content_id column to module_completion table\n";
        } else {
            echo "✗ Failed to add content_id column\n";
            exit(1);
        }
    }
    
    // Add index for better performance
    $indexSql = "SHOW INDEX FROM module_completion WHERE Key_name = 'idx_content_id'";
    $stmt = $conn->prepare($indexSql);
    $stmt->execute();
    $indexExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$indexExists) {
        $indexSql = "ALTER TABLE module_completion ADD INDEX idx_content_id (content_id)";
        $stmt = $conn->prepare($indexSql);
        $result = $stmt->execute();
        
        if ($result) {
            echo "✓ Successfully added index on content_id column\n";
        } else {
            echo "✗ Failed to add index on content_id column\n";
        }
    } else {
        echo "✓ Index on content_id column already exists\n";
    }
    
    // Show the updated table structure
    echo "\n=== Updated module_completion table structure ===\n";
    $describeSql = "DESCRIBE module_completion";
    $stmt = $conn->prepare($describeSql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "  {$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']} - {$column['Default']} - {$column['Extra']}\n";
    }
    
    echo "\n=== Migration completed successfully ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
