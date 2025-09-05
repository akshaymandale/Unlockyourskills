<?php
/**
 * Check Table Structure Script
 * Verifies the structure of key feedback system tables
 */

require_once __DIR__ . '/../config/Database.php';

class TableStructureChecker {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Check table structures
     */
    public function check() {
        try {
            $conn = $this->db->connect();
            
            echo "🔍 Checking table structures...\n\n";
            
            $this->checkTableStructure($conn, 'course_feedback_responses');
            $this->checkTableStructure($conn, 'course_feedback_assignments');
            $this->checkTableStructure($conn, 'feedback_package');
            $this->checkTableStructure($conn, 'feedback_questions');
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Check structure of a specific table
     */
    private function checkTableStructure($conn, $tableName) {
        echo "📋 Table: $tableName\n";
        echo str_repeat("-", 50) . "\n";
        
        try {
            $stmt = $conn->query("DESCRIBE $tableName");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $column) {
                $field = $column['Field'];
                $type = $column['Type'];
                $null = $column['Null'];
                $key = $column['Key'];
                $default = $column['Default'];
                $extra = $column['Extra'];
                
                echo sprintf("%-25s %-20s %-5s %-10s %-15s %s\n", 
                    $field, $type, $null, $key, $default ?: 'NULL', $extra);
            }
            
        } catch (Exception $e) {
            echo "❌ Error checking table structure: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
}

// Run structure check if script is executed directly
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    $checker = new TableStructureChecker();
    $checker->check();
}
?>
