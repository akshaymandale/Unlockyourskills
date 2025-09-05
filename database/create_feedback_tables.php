<?php
/**
 * Feedback Tables Creation Script
 * Creates the necessary tables for the feedback response system
 */

require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../config/Database.php';

class FeedbackTablesMigration {
    private $db;
    private $logFile;

    public function __construct() {
        $this->db = new Database();
        $this->logFile = __DIR__ . '/feedback_migration_log.txt';
    }

    /**
     * Run the migration
     */
    public function run() {
        $this->log("Starting Feedback Tables Migration...");
        $this->log("Timestamp: " . date('Y-m-d H:i:s'));

        try {
            $conn = $this->db->connect();
            
            // Check if tables already exist
            $this->log("Checking existing tables...");
            
            $existingTables = $this->getExistingTables($conn);
            
            if (in_array('course_feedback_responses', $existingTables)) {
                $this->log("Table 'course_feedback_responses' already exists. Skipping creation.");
            } else {
                $this->log("Creating course_feedback_responses table...");
                $this->createCourseFeedbackResponsesTable($conn);
            }
            
            if (in_array('course_feedback_assignments', $existingTables)) {
                $this->log("Table 'course_feedback_assignments' already exists. Skipping creation.");
            } else {
                $this->log("Creating course_feedback_assignments table...");
                $this->createCourseFeedbackAssignmentsTable($conn);
            }
            
            // Add client_id columns if they don't exist
            $this->addClientIdColumns($conn);
            
            $this->log("Migration completed successfully!");
            return true;

        } catch (Exception $e) {
            $this->log("ERROR: " . $e->getMessage());
            $this->log("Migration failed!");
            return false;
        }
    }

    /**
     * Get existing tables
     */
    private function getExistingTables($conn) {
        $stmt = $conn->query("SHOW TABLES");
        $tables = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        return $tables;
    }

    /**
     * Create course_feedback_responses table
     */
    private function createCourseFeedbackResponsesTable($conn) {
        $sql = "CREATE TABLE IF NOT EXISTS course_feedback_responses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            course_id INT NOT NULL,
            user_id INT NOT NULL,
            feedback_package_id INT NOT NULL,
            question_id INT NOT NULL,
            response_type ENUM('rating', 'text', 'choice', 'file') NOT NULL,
            rating_value INT NULL,
            text_response TEXT NULL,
            choice_response VARCHAR(255) NULL,
            file_response VARCHAR(500) NULL,
            response_data JSON NULL,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (feedback_package_id) REFERENCES feedback_package(id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES feedback_questions(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_feedback_question (user_id, course_id, feedback_package_id, question_id),
            INDEX idx_course_feedback (course_id, feedback_package_id),
            INDEX idx_user_feedback (user_id, feedback_package_id),
            INDEX idx_submitted_at (submitted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Course feedback responses from users'";

        $conn->exec($sql);
        $this->log("✓ course_feedback_responses table created successfully");
    }

    /**
     * Create course_feedback_assignments table
     */
    private function createCourseFeedbackAssignmentsTable($conn) {
        $sql = "CREATE TABLE IF NOT EXISTS course_feedback_assignments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            course_id INT NOT NULL,
            feedback_package_id INT NOT NULL,
            feedback_type ENUM('pre_course', 'post_course', 'module_feedback') DEFAULT 'post_course',
            module_id INT NULL,
            is_required BOOLEAN DEFAULT FALSE,
            feedback_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by INT,
            updated_by INT,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (feedback_package_id) REFERENCES feedback_package(id) ON DELETE CASCADE,
            UNIQUE KEY unique_course_feedback (course_id, feedback_package_id, module_id),
            INDEX idx_course_order (course_id, feedback_order),
            INDEX idx_feedback_type (feedback_type),
            INDEX idx_is_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Assignment of feedback packages to courses'";

        $conn->exec($sql);
        $this->log("✓ course_feedback_assignments table created successfully");
    }

    /**
     * Add client_id columns if they don't exist
     */
    private function addClientIdColumns($conn) {
        // Check if client_id column exists in course_feedback_responses
        $stmt = $conn->query("SHOW COLUMNS FROM course_feedback_responses LIKE 'client_id'");
        if ($stmt->rowCount() == 0) {
            $this->log("Adding client_id column to course_feedback_responses...");
            $conn->exec("ALTER TABLE course_feedback_responses ADD COLUMN client_id INT NULL AFTER id");
            $conn->exec("ALTER TABLE course_feedback_responses ADD INDEX idx_client_id (client_id)");
            $this->log("✓ client_id column added to course_feedback_responses");
        } else {
            $this->log("client_id column already exists in course_feedback_responses");
        }

        // Check if client_id column exists in course_feedback_assignments
        $stmt = $conn->query("SHOW COLUMNS FROM course_feedback_assignments LIKE 'client_id'");
        if ($stmt->rowCount() == 0) {
            $this->log("Adding client_id column to course_feedback_assignments...");
            $conn->exec("ALTER TABLE course_feedback_assignments ADD COLUMN client_id INT NULL AFTER id");
            $conn->exec("ALTER TABLE course_feedback_assignments ADD INDEX idx_client_id (client_id)");
            $this->log("✓ client_id column added to course_feedback_assignments");
        } else {
            $this->log("client_id column already exists in course_feedback_assignments");
        }
    }

    /**
     * Log message to file
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        
        echo $logMessage;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Run migration if script is executed directly
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    $migration = new FeedbackTablesMigration();
    $success = $migration->run();
    
    if ($success) {
        echo "\n✅ Migration completed successfully!\n";
        exit(0);
    } else {
        echo "\n❌ Migration failed! Check the log for details.\n";
        exit(1);
    }
}
?>
