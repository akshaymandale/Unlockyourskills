<?php
/**
 * Fix the submitted_at column in course_feedback_responses table
 * to match the survey system behavior
 */

require_once __DIR__ . '/../config/Database.php';

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "=== Fixing Feedback submitted_at Column ===\n\n";
    
    // Check current column definition
    $sql = "DESCRIBE course_feedback_responses";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $submittedAtColumn = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'submitted_at') {
            $submittedAtColumn = $column;
            break;
        }
    }
    
    if ($submittedAtColumn) {
        echo "Current submitted_at column:\n";
        echo "  Type: {$submittedAtColumn['Type']}\n";
        echo "  Null: {$submittedAtColumn['Null']}\n";
        echo "  Default: " . ($submittedAtColumn['Default'] ?? 'NULL') . "\n\n";
        
        // Check if it needs to be fixed
        if ($submittedAtColumn['Null'] === 'NO' || $submittedAtColumn['Default'] !== null) {
            echo "Fixing submitted_at column...\n";
            
            // Alter the column to allow NULL and remove default
            $sql = "ALTER TABLE course_feedback_responses 
                    MODIFY COLUMN submitted_at timestamp NULL DEFAULT NULL";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute();
            
            if ($result) {
                echo "✓ submitted_at column fixed successfully\n";
                
                // Verify the change
                $sql = "DESCRIBE course_feedback_responses";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($columns as $column) {
                    if ($column['Field'] === 'submitted_at') {
                        echo "\nNew submitted_at column:\n";
                        echo "  Type: {$column['Type']}\n";
                        echo "  Null: {$column['Null']}\n";
                        echo "  Default: " . ($column['Default'] ?? 'NULL') . "\n";
                        break;
                    }
                }
            } else {
                echo "✗ Failed to fix submitted_at column\n";
            }
        } else {
            echo "✓ submitted_at column is already correct\n";
        }
    } else {
        echo "✗ submitted_at column not found\n";
    }
    
    echo "\n=== Migration Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
