<?php
/**
 * Remove Completion Tables Migration
 * 
 * Drops the completion tracking tables: prerequisite_completion, module_completion, 
 * post_requisite_completion, and course_completion
 */

require_once 'config/Database.php';

class RemoveCompletionTablesMigration {
    private $db;
    private $logFile;

    public function __construct() {
        $this->db = new Database();
        $this->logFile = __DIR__ . '/remove_completion_tables_migration.log';
    }

    /**
     * Run the migration
     */
    public function migrate() {
        $this->log("Starting Completion Tables Removal Migration...");
        
        try {
            $this->dropPrerequisiteCompletionTable();
            $this->dropModuleCompletionTable();
            $this->dropPostRequisiteCompletionTable();
            $this->dropCourseCompletionTable();
            
            $this->log("Migration completed successfully!");
            return true;
        } catch (Exception $e) {
            $this->log("Migration failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Drop prerequisite_completion table
     */
    private function dropPrerequisiteCompletionTable() {
        $this->log("Dropping prerequisite_completion table...");
        
        $sql = "DROP TABLE IF EXISTS prerequisite_completion";
        $this->db->connect()->exec($sql);
        $this->log("✓ prerequisite_completion table dropped");
    }

    /**
     * Drop module_completion table
     */
    private function dropModuleCompletionTable() {
        $this->log("Dropping module_completion table...");
        
        $sql = "DROP TABLE IF EXISTS module_completion";
        $this->db->connect()->exec($sql);
        $this->log("✓ module_completion table dropped");
    }

    /**
     * Drop post_requisite_completion table
     */
    private function dropPostRequisiteCompletionTable() {
        $this->log("Dropping post_requisite_completion table...");
        
        $sql = "DROP TABLE IF EXISTS post_requisite_completion";
        $this->db->connect()->exec($sql);
        $this->log("✓ post_requisite_completion table dropped");
    }

    /**
     * Drop course_completion table
     */
    private function dropCourseCompletionTable() {
        $this->log("Dropping course_completion table...");
        
        $sql = "DROP TABLE IF EXISTS course_completion";
        $this->db->connect()->exec($sql);
        $this->log("✓ course_completion table dropped");
    }

    /**
     * Verify that all tables were dropped
     */
    public function verifyTables() {
        $this->log("Verifying table removal...");
        
        $tablesToRemove = [
            'module_completion',
            'course_completion',
            'prerequisite_completion',
            'post_requisite_completion'
        ];

        $existingTables = [];
        $removedTables = [];

        foreach ($tablesToRemove as $table) {
            try {
                $stmt = $this->db->connect()->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    $existingTables[] = $table;
                    $this->log("✗ Table '$table' still exists");
                } else {
                    $removedTables[] = $table;
                    $this->log("✓ Table '$table' removed");
                }
            } catch (PDOException $e) {
                $removedTables[] = $table;
                $this->log("✓ Table '$table' removed (or never existed)");
            }
        }

        $this->log("Table removal verification complete:");
        $this->log("Removed tables: " . count($removedTables) . "/" . count($tablesToRemove));
        $this->log("Still existing tables: " . count($existingTables));

        if (!empty($existingTables)) {
            $this->log("Still existing tables: " . implode(', ', $existingTables));
        }

        return empty($existingTables);
    }

    /**
     * Log message to file and console
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        
        // Write to log file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Also output to console
        echo $logMessage;
    }

    /**
     * Check migration status
     */
    public function status() {
        $this->log("Checking Completion Tables Removal Status...");
        
        $tablesToRemove = [
            'module_completion',
            'course_completion',
            'prerequisite_completion',
            'post_requisite_completion'
        ];

        $existingTables = [];
        $removedTables = [];

        foreach ($tablesToRemove as $table) {
            try {
                $stmt = $this->db->connect()->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    $existingTables[] = $table;
                } else {
                    $removedTables[] = $table;
                }
            } catch (PDOException $e) {
                $removedTables[] = $table;
            }
        }

        $this->log("Removal Status:");
        $this->log("Removed tables: " . count($removedTables) . "/" . count($tablesToRemove));
        $this->log("Still existing tables: " . count($existingTables));

        if (empty($existingTables)) {
            $this->log("✓ Migration is complete - all tables removed");
            return true;
        } else {
            $this->log("✗ Migration is incomplete - still existing tables: " . implode(', ', $existingTables));
            return false;
        }
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $migration = new RemoveCompletionTablesMigration();
    
    $command = $argv[1] ?? 'migrate';
    
    switch ($command) {
        case 'migrate':
            $migration->migrate();
            $migration->verifyTables();
            break;
        case 'status':
            $migration->status();
            break;
        case 'verify':
            $migration->verifyTables();
            break;
        default:
            echo "Usage: php remove_completion_tables.php [migrate|status|verify]\n";
            break;
    }
}
?>
