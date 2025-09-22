<?php
/**
 * Migration: Add prerequisite_id, postrequisite_id, and content_id columns to assessment tables
 * 
 * This migration adds three new columns to both assessment_attempts and assessment_results tables:
 * - prerequisite_id: References course_prerequisites.id
 * - postrequisite_id: References course_post_requisites.id  
 * - content_id: References course_module_content.id
 * 
 * These columns will help bifurcate between prerequisite, module, and post-requisite assessments.
 */

require_once 'config/Database.php';

class AddAssessmentTrackingColumns {
    private $db;
    private $logFile;
    
    public function __construct() {
        $this->logFile = __DIR__ . '/assessment_tracking_columns_migration.log';
        $this->log("=== Starting Assessment Tracking Columns Migration ===");
    }
    
    /**
     * Run the migration
     */
    public function run() {
        try {
            $this->db = new Database();
            $conn = $this->db->connect();
            
            $this->log("Database connection established");
            
            // Add columns to assessment_attempts table
            $this->addColumnsToAssessmentAttempts($conn);
            
            // Add columns to assessment_results table
            $this->addColumnsToAssessmentResults($conn);
            
            // Add indexes for better performance
            $this->addIndexes($conn);
            
            // Verify the changes
            $this->verifyChanges($conn);
            
            $this->log("=== Migration completed successfully ===");
            return true;
            
        } catch (Exception $e) {
            $this->log("ERROR: Migration failed - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add columns to assessment_attempts table
     */
    private function addColumnsToAssessmentAttempts($conn) {
        $this->log("Adding columns to assessment_attempts table...");
        
        $columns = [
            "prerequisite_id INT(11) NULL COMMENT 'References course_prerequisites.id'",
            "postrequisite_id INT(11) NULL COMMENT 'References course_post_requisites.id'",
            "content_id INT(11) NULL COMMENT 'References course_module_content.id'"
        ];
        
        foreach ($columns as $column) {
            $columnName = explode(' ', $column)[0];
            
            // Check if column already exists
            $checkSql = "SHOW COLUMNS FROM assessment_attempts LIKE '$columnName'";
            $result = $conn->query($checkSql);
            
            if ($result->rowCount() > 0) {
                $this->log("Column $columnName already exists in assessment_attempts, skipping...");
                continue;
            }
            
            $sql = "ALTER TABLE assessment_attempts ADD COLUMN $column";
            $conn->exec($sql);
            $this->log("✓ Added column $columnName to assessment_attempts");
        }
    }
    
    /**
     * Add columns to assessment_results table
     */
    private function addColumnsToAssessmentResults($conn) {
        $this->log("Adding columns to assessment_results table...");
        
        $columns = [
            "prerequisite_id INT(11) NULL COMMENT 'References course_prerequisites.id'",
            "postrequisite_id INT(11) NULL COMMENT 'References course_post_requisites.id'",
            "content_id INT(11) NULL COMMENT 'References course_module_content.id'"
        ];
        
        foreach ($columns as $column) {
            $columnName = explode(' ', $column)[0];
            
            // Check if column already exists
            $checkSql = "SHOW COLUMNS FROM assessment_results LIKE '$columnName'";
            $result = $conn->query($checkSql);
            
            if ($result->rowCount() > 0) {
                $this->log("Column $columnName already exists in assessment_results, skipping...");
                continue;
            }
            
            $sql = "ALTER TABLE assessment_results ADD COLUMN $column";
            $conn->exec($sql);
            $this->log("✓ Added column $columnName to assessment_results");
        }
    }
    
    /**
     * Add indexes for better performance
     */
    private function addIndexes($conn) {
        $this->log("Adding indexes for better performance...");
        
        $indexes = [
            "assessment_attempts" => [
                "idx_assessment_attempts_prerequisite" => "prerequisite_id",
                "idx_assessment_attempts_postrequisite" => "postrequisite_id", 
                "idx_assessment_attempts_content" => "content_id"
            ],
            "assessment_results" => [
                "idx_assessment_results_prerequisite" => "prerequisite_id",
                "idx_assessment_results_postrequisite" => "postrequisite_id",
                "idx_assessment_results_content" => "content_id"
            ]
        ];
        
        foreach ($indexes as $table => $tableIndexes) {
            foreach ($tableIndexes as $indexName => $column) {
                // Check if index already exists
                $checkSql = "SHOW INDEX FROM $table WHERE Key_name = '$indexName'";
                $result = $conn->query($checkSql);
                
                if ($result->rowCount() > 0) {
                    $this->log("Index $indexName already exists on $table, skipping...");
                    continue;
                }
                
                $sql = "CREATE INDEX $indexName ON $table ($column)";
                $conn->exec($sql);
                $this->log("✓ Created index $indexName on $table");
            }
        }
    }
    
    /**
     * Verify the changes
     */
    private function verifyChanges($conn) {
        $this->log("Verifying changes...");
        
        $tables = ['assessment_attempts', 'assessment_results'];
        $expectedColumns = ['prerequisite_id', 'postrequisite_id', 'content_id'];
        
        foreach ($tables as $table) {
            $this->log("Checking $table table...");
            
            foreach ($expectedColumns as $column) {
                $sql = "SHOW COLUMNS FROM $table LIKE '$column'";
                $result = $conn->query($sql);
                
                if ($result->rowCount() > 0) {
                    $this->log("✓ Column $column exists in $table");
                } else {
                    $this->log("✗ Column $column missing in $table");
                }
            }
        }
    }
    
    /**
     * Log message to file and console
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        // Write to log file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Also output to console
        echo $logMessage;
    }
}

// Run the migration if this file is executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $migration = new AddAssessmentTrackingColumns();
    $success = $migration->run();
    
    if ($success) {
        echo "\n✅ Migration completed successfully!\n";
        echo "Check the log file for details: " . __DIR__ . "/assessment_tracking_columns_migration.log\n";
    } else {
        echo "\n❌ Migration failed! Check the log file for details.\n";
        exit(1);
    }
}
?>
