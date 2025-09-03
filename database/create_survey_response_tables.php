<?php
/**
 * Survey Response Tables Creation Script
 * Creates the necessary tables for the survey response system
 */

require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/../config/Database.php';

class SurveyResponseTablesMigration {
    private $db;
    private $logFile;

    public function __construct() {
        $this->db = new Database();
        $this->logFile = __DIR__ . '/survey_response_migration_log.txt';
    }

    /**
     * Run the migration
     */
    public function run() {
        $this->log("Starting Survey Response Tables Migration...");
        $this->log("Timestamp: " . date('Y-m-d H:i:s'));

        try {
            $conn = $this->db->connect();
            
            // Check if tables already exist
            $this->log("Checking existing tables...");
            
            $existingTables = $this->getExistingTables($conn);
            
            if (in_array('course_survey_responses', $existingTables)) {
                $this->log("Table 'course_survey_responses' already exists. Skipping creation.");
            } else {
                $this->log("Creating course_survey_responses table...");
                $this->createCourseSurveyResponsesTable($conn);
            }
            
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
     * Create course_survey_responses table
     */
    private function createCourseSurveyResponsesTable($conn) {
        $sql = "CREATE TABLE IF NOT EXISTS course_survey_responses (
            id INT PRIMARY KEY AUTO_INCREMENT,
            client_id INT NOT NULL,
            course_id INT NOT NULL,
            user_id INT NOT NULL,
            survey_package_id INT NOT NULL,
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
            FOREIGN KEY (survey_package_id) REFERENCES survey_package(id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_survey_question (user_id, course_id, survey_package_id, question_id),
            INDEX idx_course_survey (course_id, survey_package_id),
            INDEX idx_user_survey (user_id, survey_package_id),
            INDEX idx_submitted_at (submitted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Course survey responses from users'";

        $conn->exec($sql);
        $this->log("✓ course_survey_responses table created successfully");
    }

    /**
     * Log message to file and console
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        // Log to file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Log to console
        echo $logMessage;
    }
}

// Run migration if script is executed directly
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    $migration = new SurveyResponseTablesMigration();
    $success = $migration->run();
    
    if ($success) {
        echo "\n✅ Survey Response Tables Migration completed successfully!\n";
        exit(0);
    } else {
        echo "\n❌ Survey Response Tables Migration failed!\n";
        exit(1);
    }
}
?>
