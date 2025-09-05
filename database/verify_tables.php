<?php
/**
 * Verify Database Tables Script
 * Checks if all necessary tables were created successfully
 */

require_once __DIR__ . '/../config/Database.php';

class TableVerification {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Verify all tables exist
     */
    public function verify() {
        try {
            $conn = $this->db->connect();
            
            echo "🔍 Verifying database tables...\n\n";
            
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
            
            echo "📊 Database: unlockyourskills\n";
            echo "📋 Total tables found: " . count($existingTables) . "\n\n";
            
            $missingTables = array_diff($expectedTables, $existingTables);
            $createdTables = array_intersect($expectedTables, $existingTables);
            
            if (empty($missingTables)) {
                echo "✅ All expected tables created successfully!\n\n";
            } else {
                echo "❌ Missing tables:\n";
                foreach ($missingTables as $table) {
                    echo "   - $table\n";
                }
                echo "\n";
            }
            
            if (!empty($createdTables)) {
                echo "✅ Created tables:\n";
                foreach ($createdTables as $table) {
                    echo "   - $table\n";
                }
                echo "\n";
            }
            
            // Check sample data
            $this->checkSampleData($conn);
            
            return empty($missingTables);
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Check if sample data was inserted
     */
    private function checkSampleData($conn) {
        echo "🔍 Checking sample data...\n\n";
        
        try {
            // Check users
            $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
            $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "👥 Users: $userCount\n";
            
            // Check feedback package
            $stmt = $conn->query("SELECT COUNT(*) as count FROM feedback_package");
            $packageCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "📦 Feedback Packages: $packageCount\n";
            
            // Check feedback questions
            $stmt = $conn->query("SELECT COUNT(*) as count FROM feedback_questions");
            $questionCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "❓ Feedback Questions: $questionCount\n";
            
            // Check courses
            $stmt = $conn->query("SELECT COUNT(*) as count FROM courses");
            $courseCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "📚 Courses: $courseCount\n";
            
            // Check course feedback assignments
            $stmt = $conn->query("SELECT COUNT(*) as count FROM course_feedback_assignments");
            $assignmentCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "🔗 Course Feedback Assignments: $assignmentCount\n";
            
        } catch (Exception $e) {
            echo "❌ Error checking sample data: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
}

// Run verification if script is executed directly
if (php_sapi_name() === 'cli' || basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    $verification = new TableVerification();
    $success = $verification->verify();
    
    if ($success) {
        echo "🎉 Database setup verification completed successfully!\n";
        echo "The feedback system is ready to use.\n";
        exit(0);
    } else {
        echo "⚠️  Some issues were found. Please check the output above.\n";
        exit(1);
    }
}
?>
