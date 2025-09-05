<?php
/**
 * Complete Database Setup Script
 * Creates all necessary tables for the feedback system
 */

require_once __DIR__ . '/../config/Database.php';

class CompleteDatabaseSetup {
    private $db;
    private $logFile;

    public function __construct() {
        $this->db = new Database();
        $this->logFile = __DIR__ . '/setup_database_log.txt';
    }

    /**
     * Run the complete database setup
     */
    public function run() {
        $this->log("Starting Complete Database Setup...");
        $this->log("Timestamp: " . date('Y-m-d H:i:s'));

        try {
            $conn = $this->db->connect();
            
            // Read and execute the SQL file
            $sqlFile = __DIR__ . '/setup_complete_database.sql';
            
            if (!file_exists($sqlFile)) {
                throw new Exception("SQL file not found: " . $sqlFile);
            }

            $this->log("Reading SQL file: " . $sqlFile);
            $sql = file_get_contents($sqlFile);
            
            if (empty($sql)) {
                throw new Exception("SQL file is empty");
            }

            $this->log("SQL file loaded successfully");

            // Split SQL into individual statements
            $statements = $this->splitSQL($sql);
            $this->log("Found " . count($statements) . " SQL statements to execute");

            // Execute each statement
            $successCount = 0;
            $errorCount = 0;

            foreach ($statements as $index => $statement) {
                $statement = trim($statement);
                
                // Skip empty statements and comments
                if (empty($statement) || strpos($statement, '--') === 0) {
                    continue;
                }

                try {
                    $this->log("Executing statement " . ($index + 1) . ": " . substr($statement, 0, 100) . "...");
                    
                    $result = $conn->exec($statement);
                    
                    if ($result !== false) {
                        $successCount++;
                        $this->log("✓ Statement " . ($index + 1) . " executed successfully");
                    } else {
                        $errorCount++;
                        $this->log("✗ Statement " . ($index + 1) . " failed");
                    }
                } catch (PDOException $e) {
                    $errorCount++;
                    $this->log("✗ Statement " . ($index + 1) . " failed: " . $e->getMessage());
                }
            }

            // Summary
            $this->log("Setup completed!");
            $this->log("Successful statements: " . $successCount);
            $this->log("Failed statements: " . $errorCount);
            $this->log("Total statements: " . ($successCount + $errorCount));

            if ($errorCount > 0) {
                $this->log("WARNING: Some statements failed. Check the log for details.");
                return false;
            }

            // Verify tables were created
            $this->verifyTables($conn);
            
            $this->log("Database setup completed successfully!");
            return true;

        } catch (Exception $e) {
            $this->log("ERROR: " . $e->getMessage());
            $this->log("Setup failed!");
            return false;
        }
    }

    /**
     * Split SQL into individual statements
     */
    private function splitSQL($sql) {
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        
        // Split by semicolon, but be careful with semicolons in strings
        $statements = [];
        $currentStatement = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (($char === "'" || $char === '"') && ($i === 0 || $sql[$i-1] !== '\\')) {
                if (!$inString) {
                    $inString = true;
                    $stringChar = $char;
                } elseif ($char === $stringChar) {
                    $inString = false;
                }
            }
            
            if ($char === ';' && !$inString) {
                $currentStatement .= $char;
                if (trim($currentStatement)) {
                    $statements[] = trim($currentStatement);
                }
                $currentStatement = '';
            } else {
                $currentStatement .= $char;
            }
        }
        
        // Add the last statement if it exists
        if (trim($currentStatement)) {
            $statements[] = trim($currentStatement);
        }
        
        return $statements;
    }

    /**
     * Verify that tables were created
     */
    private function verifyTables($conn) {
        $this->log("Verifying tables...");
        
        $expectedTables = [
            'users',
            'feedback_package',
            'feedback_questions',
            'feedback_question_options',
            'feedback_question_mapping',
            'courses',
            'course_modules',
            'course_feedback_responses',
            'course_feedback_assignments'
        ];
        
        $stmt = $conn->query("SHOW TABLES");
        $existingTables = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $existingTables[] = $row[0];
        }
        
        $missingTables = array_diff($expectedTables, $existingTables);
        
        if (empty($missingTables)) {
            $this->log("✓ All expected tables created successfully");
        } else {
            $this->log("✗ Missing tables: " . implode(', ', $missingTables));
        }
        
        // Show table count
        $this->log("Total tables in database: " . count($existingTables));
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

// Run setup if script is executed directly
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    $setup = new CompleteDatabaseSetup();
    $success = $setup->run();
    
    if ($success) {
        echo "\n✅ Database setup completed successfully!\n";
        echo "Check the log file for details: database/setup_database_log.txt\n";
        exit(0);
    } else {
        echo "\n❌ Database setup failed! Check the log for details.\n";
        echo "Log file: database/setup_database_log.txt\n";
        exit(1);
    }
}
?>
