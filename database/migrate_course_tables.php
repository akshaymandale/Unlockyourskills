<?php
/**
 * Course Tables Migration Runner
 * Executes the course creation system database migration
 */

require_once '../config/autoload.php';
require_once '../config/Database.php';

class CourseTablesMigration {
    private $db;
    private $logFile;
    private $migrationFile;

    public function __construct() {
        $this->db = new Database();
        $this->logFile = __DIR__ . '/migration_log.txt';
        $this->migrationFile = __DIR__ . '/migrations/create_course_tables.sql';
    }

    /**
     * Run the migration
     */
    public function run() {
        $this->log("Starting Course Tables Migration...");
        $this->log("Timestamp: " . date('Y-m-d H:i:s'));
        $this->log("Migration file: " . $this->migrationFile);

        try {
            // Check if migration file exists
            if (!file_exists($this->migrationFile)) {
                throw new Exception("Migration file not found: " . $this->migrationFile);
            }

            // Read migration SQL
            $sql = file_get_contents($this->migrationFile);
            if (empty($sql)) {
                throw new Exception("Migration file is empty");
            }

            $this->log("Migration file loaded successfully");

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
                    
                    $result = $this->db->connect()->exec($statement);
                    
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
            $this->log("Migration completed!");
            $this->log("Successful statements: " . $successCount);
            $this->log("Failed statements: " . $errorCount);
            $this->log("Total statements: " . ($successCount + $errorCount));

            if ($errorCount > 0) {
                $this->log("WARNING: Some statements failed. Check the log for details.");
                return false;
            }

            // Verify tables were created
            $this->verifyTables();
            
            $this->log("Migration completed successfully!");
            return true;

        } catch (Exception $e) {
            $this->log("ERROR: " . $e->getMessage());
            $this->log("Migration failed!");
            return false;
        }
    }

    /**
     * Split SQL into individual statements
     */
    private function splitSQL($sql) {
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        
        // Split by semicolon, but handle semicolons in strings
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if (!$inString && ($char === "'" || $char === '"')) {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
            } elseif ($inString && $char === $stringChar && $sql[$i-1] !== '\\') {
                $inString = false;
                $current .= $char;
            } elseif (!$inString && $char === ';') {
                $current .= $char;
                $statements[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        // Add any remaining statement
        if (!empty(trim($current))) {
            $statements[] = trim($current);
        }
        
        return array_filter($statements);
    }

    /**
     * Verify that all required tables were created
     */
    private function verifyTables() {
        $this->log("Verifying table creation...");
        
        $requiredTables = [
            'course_categories',
            'course_subcategories',
            'courses',
            'course_modules',
            'course_module_content',
            'course_prerequisites',
            'course_assessments',
            'course_feedback',
            'course_surveys',
            'course_enrollments',
            'module_progress',
            'content_progress',
            'assessment_results',
            'feedback_responses',
            'survey_responses',
            'course_analytics',
            'course_settings'
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
        
        // Output to console
        echo $logMessage;
    }

    /**
     * Rollback migration (drop all course tables)
     */
    public function rollback() {
        $this->log("Starting Course Tables Migration Rollback...");
        $this->log("Timestamp: " . date('Y-m-d H:i:s'));

        $tables = [
            'course_settings',
            'course_analytics',
            'survey_responses',
            'feedback_responses',
            'assessment_results',
            'content_progress',
            'module_progress',
            'course_enrollments',
            'course_surveys',
            'course_feedback',
            'course_assessments',
            'course_prerequisites',
            'course_module_content',
            'course_modules',
            'courses',
            'course_subcategories',
            'course_categories'
        ];

        $successCount = 0;
        $errorCount = 0;

        foreach ($tables as $table) {
            try {
                $this->log("Dropping table: $table");
                $this->db->connect()->exec("DROP TABLE IF EXISTS $table");
                $successCount++;
                $this->log("✓ Table '$table' dropped successfully");
            } catch (PDOException $e) {
                $errorCount++;
                $this->log("✗ Failed to drop table '$table': " . $e->getMessage());
            }
        }

        $this->log("Rollback completed!");
        $this->log("Successfully dropped: " . $successCount . " tables");
        $this->log("Failed to drop: " . $errorCount . " tables");

        return $errorCount === 0;
    }

    /**
     * Check migration status
     */
    public function status() {
        $this->log("Checking Course Tables Migration Status...");
        
        $requiredTables = [
            'course_categories',
            'course_subcategories',
            'courses',
            'course_modules',
            'course_module_content',
            'course_prerequisites',
            'course_assessments',
            'course_feedback',
            'course_surveys',
            'course_enrollments',
            'module_progress',
            'content_progress',
            'assessment_results',
            'feedback_responses',
            'survey_responses',
            'course_analytics',
            'course_settings'
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
    $migration = new CourseTablesMigration();
    
    $command = $argv[1] ?? 'run';
    
    switch ($command) {
        case 'run':
            $success = $migration->run();
            exit($success ? 0 : 1);
            
        case 'rollback':
            $success = $migration->rollback();
            exit($success ? 0 : 1);
            
        case 'status':
            $success = $migration->status();
            exit($success ? 0 : 1);
            
        default:
            echo "Usage: php migrate_course_tables.php [run|rollback|status]\n";
            echo "  run     - Execute the migration\n";
            echo "  rollback - Rollback the migration (drop all tables)\n";
            echo "  status   - Check migration status\n";
            exit(1);
    }
} else {
    // Web interface
    $migration = new CourseTablesMigration();
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'run':
                $success = $migration->run();
                echo json_encode(['success' => $success]);
                break;
                
            case 'rollback':
                $success = $migration->rollback();
                echo json_encode(['success' => $success]);
                break;
                
            case 'status':
                $success = $migration->status();
                echo json_encode(['success' => $success]);
                break;
                
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        // Show migration interface
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Course Tables Migration</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                .log-output {
                    background: #f8f9fa;
                    border: 1px solid #dee2e6;
                    border-radius: 0.375rem;
                    padding: 1rem;
                    height: 400px;
                    overflow-y: auto;
                    font-family: monospace;
                    font-size: 0.875rem;
                }
            </style>
        </head>
        <body>
            <div class="container mt-5">
                <h1 class="mb-4">Course Tables Migration</h1>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Migration Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2 d-md-block">
                                    <button class="btn btn-primary" onclick="runMigration()">Run Migration</button>
                                    <button class="btn btn-warning" onclick="checkStatus()">Check Status</button>
                                    <button class="btn btn-danger" onclick="rollbackMigration()">Rollback</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">Migration Log</h5>
                            </div>
                            <div class="card-body">
                                <div id="logOutput" class="log-output"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Migration Info</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Migration File:</strong> create_course_tables.sql</p>
                                <p><strong>Tables Created:</strong> 17 tables</p>
                                <p><strong>Includes:</strong></p>
                                <ul>
                                    <li>Course categories & subcategories</li>
                                    <li>Courses & modules</li>
                                    <li>Prerequisites & assessments</li>
                                    <li>Enrollments & progress tracking</li>
                                    <li>Analytics & settings</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                function log(message) {
                    const logOutput = document.getElementById('logOutput');
                    const timestamp = new Date().toLocaleString();
                    logOutput.innerHTML += `[${timestamp}] ${message}\n`;
                    logOutput.scrollTop = logOutput.scrollHeight;
                }

                async function runMigration() {
                    log('Starting migration...');
                    try {
                        const response = await fetch('?action=run');
                        const result = await response.json();
                        if (result.success) {
                            log('✓ Migration completed successfully!');
                        } else {
                            log('✗ Migration failed!');
                        }
                    } catch (error) {
                        log('✗ Error: ' + error.message);
                    }
                }

                async function checkStatus() {
                    log('Checking migration status...');
                    try {
                        const response = await fetch('?action=status');
                        const result = await response.json();
                        if (result.success) {
                            log('✓ Migration is complete - all tables exist');
                        } else {
                            log('✗ Migration is incomplete - some tables are missing');
                        }
                    } catch (error) {
                        log('✗ Error: ' + error.message);
                    }
                }

                async function rollbackMigration() {
                    if (confirm('Are you sure you want to rollback the migration? This will drop all course tables!')) {
                        log('Starting rollback...');
                        try {
                            const response = await fetch('?action=rollback');
                            const result = await response.json();
                            if (result.success) {
                                log('✓ Rollback completed successfully!');
                            } else {
                                log('✗ Rollback failed!');
                            }
                        } catch (error) {
                            log('✗ Error: ' + error.message);
                        }
                    }
                }
            </script>
        </body>
        </html>
        <?php
    }
}
?> 