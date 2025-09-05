<?php
/**
 * Check existing feedback system structure
 * Understanding how feedback questions, options, mapping, packages, and course linking works
 */

require_once 'config/Database.php';

try {
    $database = new Database();
    $conn = $database->connect();
    
    echo "🔍 Understanding Existing Feedback System Structure...\n\n";
    
    // 1. Check feedback_package table structure and data
    echo "📦 1. FEEDBACK_PACKAGE TABLE:\n";
    echo "----------------------------------------\n";
    $stmt = $conn->query("DESCRIBE feedback_package");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "Column: {$col['Field']} - Type: {$col['Type']} - Null: {$col['Null']} - Key: {$col['Key']}\n";
    }
    
    echo "\n📦 Feedback Package Data:\n";
    $stmt = $conn->query("SELECT * FROM feedback_package WHERE is_deleted = 0");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($packages as $package) {
        echo "ID: {$package['id']}, Title: {$package['title']}, Client: {$package['client_id']}, Created: {$package['created_at']}\n";
    }
    
    echo "\n";
    
    // 2. Check feedback_questions table structure and data
    echo "❓ 2. FEEDBACK_QUESTIONS TABLE:\n";
    echo "----------------------------------------\n";
    $stmt = $conn->query("DESCRIBE feedback_questions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "Column: {$col['Field']} - Type: {$col['Type']} - Null: {$col['Null']} - Key: {$col['Key']}\n";
    }
    
    echo "\n❓ Feedback Questions Data:\n";
    $stmt = $conn->query("SELECT * FROM feedback_questions WHERE is_deleted = 0");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($questions as $question) {
        echo "ID: {$question['id']}, Title: {$question['title']}, Type: {$question['type']}, Client: {$question['client_id']}\n";
    }
    
    echo "\n";
    
    // 3. Check feedback_question_options table structure and data
    echo "🔘 3. FEEDBACK_QUESTION_OPTIONS TABLE:\n";
    echo "----------------------------------------\n";
    $stmt = $conn->query("DESCRIBE feedback_question_options");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "Column: {$col['Field']} - Type: {$col['Type']} - Null: {$col['Null']} - Key: {$col['Key']}\n";
    }
    
    echo "\n🔘 Feedback Question Options Data:\n";
    $stmt = $conn->query("SELECT * FROM feedback_question_options LIMIT 10");
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($options as $option) {
        echo "ID: {$option['id']}, Question ID: {$option['question_id']}, Option Text: {$option['option_text']}\n";
    }
    
    echo "\n";
    
    // 4. Check feedback_question_mapping table structure and data
    echo "🔗 4. FEEDBACK_QUESTION_MAPPING TABLE:\n";
    echo "----------------------------------------\n";
    $stmt = $conn->query("DESCRIBE feedback_question_mapping");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "Column: {$col['Field']} - Type: {$col['Type']} - Null: {$col['Null']} - Key: {$col['Key']}\n";
    }
    
    echo "\n🔗 Feedback Question Mapping Data:\n";
    $stmt = $conn->query("SELECT * FROM feedback_question_mapping LIMIT 10");
    $mappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($mappings as $mapping) {
        echo "ID: {$mapping['id']}, Package ID: {$mapping['feedback_package_id']}, Question ID: {$mapping['feedback_question_id']}\n";
    }
    
    echo "\n";
    
    // 5. Check course_prerequisites table structure and data
    echo "📚 5. COURSE_PREREQUISITES TABLE:\n";
    echo "----------------------------------------\n";
    $stmt = $conn->query("DESCRIBE course_prerequisites");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "Column: {$col['Field']} - Type: {$col['Type']} - Null: {$col['Null']} - Key: {$col['Key']}\n";
    }
    
    echo "\n📚 Course Prerequisites with Feedback Type:\n";
    $stmt = $conn->query("SELECT * FROM course_prerequisites WHERE prerequisite_type = 'feedback'");
    $prereqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($prereqs)) {
        echo "❌ No prerequisites with feedback type found\n";
    } else {
        foreach ($prereqs as $prereq) {
            echo "Course ID: {$prereq['course_id']}, Prerequisite Type: {$prereq['prerequisite_type']}, Prerequisite ID: {$prereq['prerequisite_id']}\n";
        }
    }
    
    echo "\n";
    
    // 6. Check if there are post-requisites in a different way
    echo "📚 6. CHECKING FOR POST-REQUISITES:\n";
    echo "----------------------------------------\n";
    
    // Check if there's a separate post-requisites table
    $stmt = $conn->query("SHOW TABLES LIKE 'course_postrequisites'");
    $postReqsTable = $stmt->fetch();
    
    if ($postReqsTable) {
        echo "✅ Found course_postrequisites table\n";
        $stmt = $conn->query("DESCRIBE course_postrequisites");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "Column: {$col['Field']} - Type: {$col['Type']} - Null: {$col['Null']} - Key: {$col['Key']}\n";
        }
        
        echo "\n📚 Course Post-requisites with Feedback Type:\n";
        $stmt = $conn->query("SELECT * FROM course_postrequisites WHERE postrequisite_type = 'feedback'");
        $postReqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($postReqs)) {
            echo "❌ No post-requisites with feedback type found\n";
        } else {
            foreach ($postReqs as $postReq) {
                echo "Course ID: {$postReq['course_id']}, Post-requisite Type: {$postReq['postrequisite_type']}, Post-requisite ID: {$postReq['postrequisite_id']}\n";
            }
        }
    } else {
        echo "❌ No course_postrequisites table found\n";
        
        // Check if post-requisites are stored in the same table with a different field
        echo "\n🔍 Checking if post-requisites are in course_prerequisites with different logic...\n";
        $stmt = $conn->query("SELECT DISTINCT prerequisite_type FROM course_prerequisites");
        $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Available prerequisite types: " . implode(', ', $types) . "\n";
    }
    
    echo "\n";
    
    // 7. Check how feedback packages are linked to courses
    echo "🔗 7. FEEDBACK PACKAGE TO COURSE LINKING:\n";
    echo "----------------------------------------\n";
    
    // Check if there's a direct course_feedback table
    $stmt = $conn->query("SHOW TABLES LIKE 'course_feedback'");
    $courseFeedbackTable = $stmt->fetch();
    
    if ($courseFeedbackTable) {
        echo "✅ Found course_feedback table\n";
        $stmt = $conn->query("DESCRIBE course_feedback");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "Column: {$col['Field']} - Type: {$col['Type']} - Null: {$col['Null']} - Key: {$col['Key']}\n";
        }
        
        echo "\n📚 Course Feedback Data:\n";
        $stmt = $conn->query("SELECT * FROM course_feedback LIMIT 10");
        $courseFeedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($courseFeedback as $cf) {
            echo "Course ID: {$cf['course_id']}, Feedback Package ID: {$cf['feedback_package_id']}\n";
        }
    } else {
        echo "❌ No course_feedback table found\n";
    }
    
    echo "\n";
    
    // 8. Check courses table structure
    echo "📚 8. COURSES TABLE STRUCTURE:\n";
    echo "----------------------------------------\n";
    $stmt = $conn->query("DESCRIBE courses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "Column: {$col['Field']} - Type: {$col['Type']} - Null: {$col['Null']} - Key: {$col['Key']}\n";
    }
    
    echo "\n📚 Sample Course Data:\n";
    $stmt = $conn->query("SELECT id, name, client_id FROM courses LIMIT 5");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($courses as $course) {
        echo "ID: {$course['id']}, Name: {$course['name']}, Client: {$course['client_id']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
