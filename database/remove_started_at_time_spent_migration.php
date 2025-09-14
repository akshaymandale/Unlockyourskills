<?php
/**
 * Migration to remove started_at and time_spent columns from completion tables
 */

require_once 'config/Database.php';

class RemoveStartedAtTimeSpentMigration {
    private $db;
    private $logFile;

    public function __construct() {
        $this->db = new Database();
        $this->logFile = __DIR__ . '/remove_started_at_time_spent_migration.log';
    }

    /**
     * Run the migration
     */
    public function migrate() {
        $this->log("Starting Remove Started At and Time Spent Migration...");
        
        try {
            $this->removeFromPrerequisiteCompletion();
            $this->removeFromPostRequisiteCompletion();
            $this->removeFromModuleCompletion();
            $this->removeFromCourseCompletion();
            
            $this->log("Migration completed successfully!");
            return true;
        } catch (Exception $e) {
            $this->log("Migration failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove started_at and time_spent from prerequisite_completion table
     */
    private function removeFromPrerequisiteCompletion() {
        $this->log("Removing started_at and time_spent from prerequisite_completion...");
        
        $conn = $this->db->connect();
        
        // Check if columns exist before dropping
        $checkStartedAt = $conn->query("SHOW COLUMNS FROM prerequisite_completion LIKE 'started_at'");
        $checkTimeSpent = $conn->query("SHOW COLUMNS FROM prerequisite_completion LIKE 'time_spent'");
        
        if ($checkStartedAt->rowCount() > 0) {
            $conn->exec("ALTER TABLE prerequisite_completion DROP COLUMN started_at");
            $this->log("✓ Removed started_at from prerequisite_completion");
        } else {
            $this->log("⚠ started_at column not found in prerequisite_completion");
        }
        
        if ($checkTimeSpent->rowCount() > 0) {
            $conn->exec("ALTER TABLE prerequisite_completion DROP COLUMN time_spent");
            $this->log("✓ Removed time_spent from prerequisite_completion");
        } else {
            $this->log("⚠ time_spent column not found in prerequisite_completion");
        }
    }

    /**
     * Remove started_at and time_spent from post_requisite_completion table
     */
    private function removeFromPostRequisiteCompletion() {
        $this->log("Removing started_at and time_spent from post_requisite_completion...");
        
        $conn = $this->db->connect();
        
        // Check if columns exist before dropping
        $checkStartedAt = $conn->query("SHOW COLUMNS FROM post_requisite_completion LIKE 'started_at'");
        $checkTimeSpent = $conn->query("SHOW COLUMNS FROM post_requisite_completion LIKE 'time_spent'");
        
        if ($checkStartedAt->rowCount() > 0) {
            $conn->exec("ALTER TABLE post_requisite_completion DROP COLUMN started_at");
            $this->log("✓ Removed started_at from post_requisite_completion");
        } else {
            $this->log("⚠ started_at column not found in post_requisite_completion");
        }
        
        if ($checkTimeSpent->rowCount() > 0) {
            $conn->exec("ALTER TABLE post_requisite_completion DROP COLUMN time_spent");
            $this->log("✓ Removed time_spent from post_requisite_completion");
        } else {
            $this->log("⚠ time_spent column not found in post_requisite_completion");
        }
    }

    /**
     * Remove started_at and time_spent from module_completion table
     */
    private function removeFromModuleCompletion() {
        $this->log("Removing started_at and time_spent from module_completion...");
        
        $conn = $this->db->connect();
        
        // Check if columns exist before dropping
        $checkStartedAt = $conn->query("SHOW COLUMNS FROM module_completion LIKE 'started_at'");
        $checkTimeSpent = $conn->query("SHOW COLUMNS FROM module_completion LIKE 'time_spent'");
        
        if ($checkStartedAt->rowCount() > 0) {
            $conn->exec("ALTER TABLE module_completion DROP COLUMN started_at");
            $this->log("✓ Removed started_at from module_completion");
        } else {
            $this->log("⚠ started_at column not found in module_completion");
        }
        
        if ($checkTimeSpent->rowCount() > 0) {
            $conn->exec("ALTER TABLE module_completion DROP COLUMN time_spent");
            $this->log("✓ Removed time_spent from module_completion");
        } else {
            $this->log("⚠ time_spent column not found in module_completion");
        }
    }

    /**
     * Remove started_at and time_spent from course_completion table
     */
    private function removeFromCourseCompletion() {
        $this->log("Removing started_at and time_spent from course_completion...");
        
        $conn = $this->db->connect();
        
        // Check if columns exist before dropping
        $checkStartedAt = $conn->query("SHOW COLUMNS FROM course_completion LIKE 'started_at'");
        $checkTimeSpent = $conn->query("SHOW COLUMNS FROM course_completion LIKE 'time_spent'");
        
        if ($checkStartedAt->rowCount() > 0) {
            $conn->exec("ALTER TABLE course_completion DROP COLUMN started_at");
            $this->log("✓ Removed started_at from course_completion");
        } else {
            $this->log("⚠ started_at column not found in course_completion");
        }
        
        if ($checkTimeSpent->rowCount() > 0) {
            $conn->exec("ALTER TABLE course_completion DROP COLUMN time_spent");
            $this->log("✓ Removed time_spent from course_completion");
        } else {
            $this->log("⚠ time_spent column not found in course_completion");
        }
    }

    /**
     * Log message to file and console
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        echo $logMessage;
    }
}

// Run migration if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $migration = new RemoveStartedAtTimeSpentMigration();
    $migration->migrate();
}
?>
