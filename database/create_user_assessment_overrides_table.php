<?php
/**
 * Migration: Create user_assessment_overrides table
 *
 * This table stores user-specific overrides for assessment attempt limits.
 */

require_once 'config/Database.php';

class CreateUserAssessmentOverridesTable {
    private $db;
    private $logFile;

    public function __construct() {
        $this->logFile = __DIR__ . '/user_assessment_overrides_migration.log';
        $this->log("=== Starting User Assessment Overrides Table Creation ===");
    }

    /**
     * Run the migration
     */
    public function migrate() {
        try {
            $this->db = new Database();
            $conn = $this->db->connect();
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $this->log("Database connection established");

            $this->createUserAssessmentOverridesTable($conn);
            $this->addIndexes($conn);
            $this->verifyTableCreation($conn);

            $this->log("=== Migration completed successfully ===");
            return true;

        } catch (Exception $e) {
            $this->log("ERROR: Migration failed - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create user_assessment_overrides table
     */
    private function createUserAssessmentOverridesTable($conn) {
        $this->log("Creating user_assessment_overrides table...");
        $sql = "
            CREATE TABLE IF NOT EXISTS user_assessment_overrides (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NOT NULL COMMENT 'The user ID',
                course_id INT(11) NOT NULL COMMENT 'The course ID',
                assessment_id INT(11) NOT NULL COMMENT 'The assessment_package.id',
                client_id INT(11) NULL COMMENT 'The client ID for multi-tenancy',
                context_type ENUM('prerequisite', 'module', 'post_requisite') NOT NULL COMMENT 'Where the assessment is used',
                context_id INT(11) NOT NULL COMMENT 'The ID of the prerequisite, module_content, or post_requisite record',
                original_max_attempts INT(11) NOT NULL COMMENT 'Original max attempts from assessment_package',
                override_max_attempts INT(11) NOT NULL COMMENT 'Override max attempts for this user',
                reason TEXT NULL COMMENT 'Reason for the override',
                created_by INT(11) NOT NULL COMMENT 'User ID of the admin who created the override',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_active TINYINT(1) DEFAULT 1 COMMENT 'Whether this override is active',
                FOREIGN KEY (user_id) REFERENCES user_profiles(id) ON DELETE CASCADE,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
                FOREIGN KEY (assessment_id) REFERENCES assessment_package(id) ON DELETE CASCADE,
                FOREIGN KEY (created_by) REFERENCES user_profiles(id) ON DELETE RESTRICT,
                UNIQUE KEY unique_user_assessment_context (user_id, course_id, assessment_id, context_type, context_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $conn->exec($sql);
        $this->log("✓ Created user_assessment_overrides table");
    }

    /**
     * Add indexes for better performance
     */
    private function addIndexes($conn) {
        $this->log("Adding indexes...");
        $indexes = [
            "CREATE INDEX idx_uao_user_course_assessment ON user_assessment_overrides (user_id, course_id, assessment_id)",
            "CREATE INDEX idx_uao_context ON user_assessment_overrides (context_type, context_id)",
            "CREATE INDEX idx_uao_active ON user_assessment_overrides (is_active)",
            "CREATE INDEX idx_uao_client ON user_assessment_overrides (client_id)"
        ];

        foreach ($indexes as $indexSql) {
            try {
                $conn->exec($indexSql);
                $this->log("✓ Added index: " . substr($indexSql, 12, 50) . "...");
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                    throw $e;
                }
                $this->log("Index already exists, skipping: " . substr($indexSql, 12, 50) . "...");
            }
        }
    }

    /**
     * Verify table creation
     */
    private function verifyTableCreation($conn) {
        $this->log("Verifying table creation...");
        $stmt = $conn->query("SHOW TABLES LIKE 'user_assessment_overrides'");
        if ($stmt->rowCount() > 0) {
            $this->log("✓ Table user_assessment_overrides exists");
            $stmt = $conn->query("DESCRIBE user_assessment_overrides");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->log("Table structure:");
            foreach ($columns as $col) {
                $this->log("  - " . $col['Field'] . " (" . $col['Type'] . ")");
            }
        } else {
            $this->log("✗ Table user_assessment_overrides does NOT exist");
            throw new Exception("Table user_assessment_overrides was not created.");
        }
    }

    /**
     * Log message to file and console
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        echo $logMessage;
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $migration = new CreateUserAssessmentOverridesTable();
    $migration->migrate();
}
?>
