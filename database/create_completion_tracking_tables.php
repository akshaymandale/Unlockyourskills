<?php
/**
 * Create Completion Tracking Tables Migration
 * 
 * Creates tables for tracking module, course, prerequisite, and post-requisite completion
 */

require_once 'config/Database.php';

class CompletionTrackingMigration {
    private $db;
    private $logFile;

    public function __construct() {
        $this->db = new Database();
        $this->logFile = __DIR__ . '/completion_tracking_migration.log';
    }

    /**
     * Run the migration
     */
    public function migrate() {
        $this->log("Starting Completion Tracking Tables Migration...");
        
        try {
            $this->createModuleCompletionTable();
            $this->createCourseCompletionTable();
            $this->createPrerequisiteCompletionTable();
            $this->createPostRequisiteCompletionTable();
            
            $this->log("Migration completed successfully!");
            return true;
        } catch (Exception $e) {
            $this->log("Migration failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create module_completion table
     */
    private function createModuleCompletionTable() {
        $this->log("Creating module_completion table...");
        
        $sql = "CREATE TABLE IF NOT EXISTS module_completion (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            module_id INT NOT NULL,
            client_id INT NOT NULL,
            completion_percentage DECIMAL(5,2) DEFAULT 0.00,
            is_completed TINYINT(1) DEFAULT 0,
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            last_accessed_at TIMESTAMP NULL,
            time_spent INT DEFAULT 0 COMMENT 'Time spent in seconds',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_user_module (user_id, course_id, module_id, client_id),
            KEY idx_user_course (user_id, course_id),
            KEY idx_module (module_id),
            KEY idx_client (client_id),
            KEY idx_completed (is_completed),
            KEY idx_completed_at (completed_at),
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->connect()->exec($sql);
        $this->log("✓ module_completion table created");
    }

    /**
     * Create course_completion table
     */
    private function createCourseCompletionTable() {
        $this->log("Creating course_completion table...");
        
        $sql = "CREATE TABLE IF NOT EXISTS course_completion (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            client_id INT NOT NULL,
            completion_percentage DECIMAL(5,2) DEFAULT 0.00,
            is_completed TINYINT(1) DEFAULT 0,
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            last_accessed_at TIMESTAMP NULL,
            time_spent INT DEFAULT 0 COMMENT 'Time spent in seconds',
            prerequisites_completed TINYINT(1) DEFAULT 0,
            modules_completed TINYINT(1) DEFAULT 0,
            post_requisites_completed TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_user_course (user_id, course_id, client_id),
            KEY idx_user (user_id),
            KEY idx_course (course_id),
            KEY idx_client (client_id),
            KEY idx_completed (is_completed),
            KEY idx_completed_at (completed_at),
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->connect()->exec($sql);
        $this->log("✓ course_completion table created");
    }

    /**
     * Create prerequisite_completion table
     */
    private function createPrerequisiteCompletionTable() {
        $this->log("Creating prerequisite_completion table...");
        
        $sql = "CREATE TABLE IF NOT EXISTS prerequisite_completion (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            prerequisite_id INT NOT NULL,
            prerequisite_type ENUM('course','assessment','survey','feedback','scorm','video','audio','document','interactive','assignment','external','image','skill','certification') NOT NULL,
            client_id INT NOT NULL,
            completion_percentage DECIMAL(5,2) DEFAULT 0.00,
            is_completed TINYINT(1) DEFAULT 0,
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            last_accessed_at TIMESTAMP NULL,
            time_spent INT DEFAULT 0 COMMENT 'Time spent in seconds',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_user_prerequisite (user_id, course_id, prerequisite_id, prerequisite_type, client_id),
            KEY idx_user_course (user_id, course_id),
            KEY idx_prerequisite (prerequisite_id, prerequisite_type),
            KEY idx_client (client_id),
            KEY idx_completed (is_completed),
            KEY idx_completed_at (completed_at),
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->connect()->exec($sql);
        $this->log("✓ prerequisite_completion table created");
    }

    /**
     * Create post_requisite_completion table
     */
    private function createPostRequisiteCompletionTable() {
        $this->log("Creating post_requisite_completion table...");
        
        $sql = "CREATE TABLE IF NOT EXISTS post_requisite_completion (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            post_requisite_id INT NOT NULL,
            content_type ENUM('assessment','feedback','survey','assignment') NOT NULL,
            client_id INT NOT NULL,
            completion_percentage DECIMAL(5,2) DEFAULT 0.00,
            is_completed TINYINT(1) DEFAULT 0,
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            last_accessed_at TIMESTAMP NULL,
            time_spent INT DEFAULT 0 COMMENT 'Time spent in seconds',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_user_post_requisite (user_id, course_id, post_requisite_id, content_type, client_id),
            KEY idx_user_course (user_id, course_id),
            KEY idx_post_requisite (post_requisite_id, content_type),
            KEY idx_client (client_id),
            KEY idx_completed (is_completed),
            KEY idx_completed_at (completed_at),
            
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->connect()->exec($sql);
        $this->log("✓ post_requisite_completion table created");
    }

    /**
     * Verify that all tables were created
     */
    public function verifyTables() {
        $this->log("Verifying table creation...");
        
        $requiredTables = [
            'module_completion',
            'course_completion',
            'prerequisite_completion',
            'post_requisite_completion'
        ];

        $existingTables = [];
        $missingTables = [];

        foreach ($requiredTables as $table) {
            try {
                $stmt = $this->db->connect()->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    $existingTables[] = $table;
                    $this->log("✓ Table '$table' exists");
                } else {
                    $missingTables[] = $table;
                    $this->log("✗ Table '$table' missing");
                }
            } catch (PDOException $e) {
                $missingTables[] = $table;
                $this->log("✗ Error checking table '$table': " . $e->getMessage());
            }
        }

        $this->log("Table verification complete:");
        $this->log("Existing tables: " . count($existingTables) . "/" . count($requiredTables));
        $this->log("Missing tables: " . count($missingTables));

        if (!empty($missingTables)) {
            $this->log("Missing tables: " . implode(', ', $missingTables));
        }

        return empty($missingTables);
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
        $this->log("Checking Completion Tracking Migration Status...");
        
        $requiredTables = [
            'module_completion',
            'course_completion',
            'prerequisite_completion',
            'post_requisite_completion'
        ];

        $existingTables = [];
        $missingTables = [];

        foreach ($requiredTables as $table) {
            try {
                $stmt = $this->db->connect()->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    $existingTables[] = $table;
                } else {
                    $missingTables[] = $table;
                }
            } catch (PDOException $e) {
                $missingTables[] = $table;
            }
        }

        $this->log("Migration Status:");
        $this->log("Existing tables: " . count($existingTables) . "/" . count($requiredTables));
        $this->log("Missing tables: " . count($missingTables));

        if (empty($missingTables)) {
            $this->log("✓ Migration is complete - all tables exist");
            return true;
        } else {
            $this->log("✗ Migration is incomplete - missing tables: " . implode(', ', $missingTables));
            return false;
        }
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $migration = new CompletionTrackingMigration();
    
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
            echo "Usage: php create_completion_tracking_tables.php [migrate|status|verify]\n";
            break;
    }
}
?>
