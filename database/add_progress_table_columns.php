<?php
/**
 * Migration script to add prerequisite_id, postrequisite_id, and content_id columns to all progress tables
 */

require_once 'config/Database.php';

try {
    echo "=== Adding Required Columns to Progress Tables ===\n\n";
    
    $database = new Database();
    $conn = $database->connect();
    
    // List of all progress tables and their current status
    $progressTables = [
        'video_progress' => ['has_content_id' => true, 'has_prerequisite_id' => false, 'has_postrequisite_id' => false],
        'audio_progress' => ['has_content_id' => true, 'has_prerequisite_id' => false, 'has_postrequisite_id' => false],
        'document_progress' => ['has_content_id' => true, 'has_prerequisite_id' => false, 'has_postrequisite_id' => false],
        'image_progress' => ['has_content_id' => true, 'has_prerequisite_id' => false, 'has_postrequisite_id' => false],
        'scorm_progress' => ['has_content_id' => true, 'has_prerequisite_id' => false, 'has_postrequisite_id' => false],
        'external_progress' => ['has_content_id' => true, 'has_prerequisite_id' => false, 'has_postrequisite_id' => false],
        'interactive_progress' => ['has_content_id' => true, 'has_prerequisite_id' => false, 'has_postrequisite_id' => false],
        'assignment_submissions' => ['has_content_id' => false, 'has_prerequisite_id' => false, 'has_postrequisite_id' => false],
        'course_survey_responses' => ['has_content_id' => false, 'has_prerequisite_id' => false, 'has_postrequisite_id' => false],
        'course_feedback_responses' => ['has_content_id' => false, 'has_prerequisite_id' => false, 'has_postrequisite_id' => false]
    ];
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($progressTables as $table => $status) {
        echo "Processing table: {$table}\n";
        echo str_repeat("-", 50) . "\n";
        
        try {
            // Add prerequisite_id column if missing
            if (!$status['has_prerequisite_id']) {
                $sql = "ALTER TABLE `{$table}` ADD COLUMN `prerequisite_id` INT(11) NULL DEFAULT NULL AFTER `course_id`";
                $conn->exec($sql);
                echo "  ✅ Added prerequisite_id column\n";
            } else {
                echo "  ✓ prerequisite_id column already exists\n";
            }
            
            // Add postrequisite_id column if missing
            if (!$status['has_postrequisite_id']) {
                $sql = "ALTER TABLE `{$table}` ADD COLUMN `postrequisite_id` INT(11) NULL DEFAULT NULL AFTER `prerequisite_id`";
                $conn->exec($sql);
                echo "  ✅ Added postrequisite_id column\n";
            } else {
                echo "  ✓ postrequisite_id column already exists\n";
            }
            
            // Add content_id column if missing
            if (!$status['has_content_id']) {
                $sql = "ALTER TABLE `{$table}` ADD COLUMN `content_id` INT(11) NULL DEFAULT NULL AFTER `postrequisite_id`";
                $conn->exec($sql);
                echo "  ✅ Added content_id column\n";
            } else {
                echo "  ✓ content_id column already exists\n";
            }
            
            // Add indexes for better performance
            try {
                $indexSql = "ALTER TABLE `{$table}` ADD INDEX `idx_prerequisite_id` (`prerequisite_id`)";
                $conn->exec($indexSql);
                echo "  ✅ Added index for prerequisite_id\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                    echo "  ⚠️  Warning adding prerequisite_id index: " . $e->getMessage() . "\n";
                } else {
                    echo "  ✓ prerequisite_id index already exists\n";
                }
            }
            
            try {
                $indexSql = "ALTER TABLE `{$table}` ADD INDEX `idx_postrequisite_id` (`postrequisite_id`)";
                $conn->exec($indexSql);
                echo "  ✅ Added index for postrequisite_id\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                    echo "  ⚠️  Warning adding postrequisite_id index: " . $e->getMessage() . "\n";
                } else {
                    echo "  ✓ postrequisite_id index already exists\n";
                }
            }
            
            try {
                $indexSql = "ALTER TABLE `{$table}` ADD INDEX `idx_content_id` (`content_id`)";
                $conn->exec($indexSql);
                echo "  ✅ Added index for content_id\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                    echo "  ⚠️  Warning adding content_id index: " . $e->getMessage() . "\n";
                } else {
                    echo "  ✓ content_id index already exists\n";
                }
            }
            
            $successCount++;
            echo "  ✅ Table {$table} processed successfully\n";
            
        } catch (Exception $e) {
            $errorCount++;
            echo "  ❌ Error processing table {$table}: " . $e->getMessage() . "\n";
        }
        
        echo "\n" . str_repeat("=", 60) . "\n\n";
    }
    
    // Summary
    echo "MIGRATION SUMMARY:\n";
    echo "==================\n";
    echo "Tables processed successfully: {$successCount}\n";
    echo "Tables with errors: {$errorCount}\n";
    echo "\nAll progress tables now have:\n";
    echo "- prerequisite_id (INT, NULL, indexed)\n";
    echo "- postrequisite_id (INT, NULL, indexed)\n";
    echo "- content_id (INT, NULL, indexed)\n";
    echo "\nMigration completed!\n";
    
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
