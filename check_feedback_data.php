<?php
/**
 * Check feedback data in database
 */

require_once 'config/Database.php';

try {
    $database = new Database();
    $conn = $database->connect();
    
    echo "🔍 Checking feedback data in database...\n\n";
    
    // Check feedback packages
    echo "📦 Feedback Packages:\n";
    $stmt = $conn->query("SELECT id, title, client_id, created_at FROM feedback_package WHERE is_deleted = 0");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($packages)) {
        echo "❌ No feedback packages found\n";
    } else {
        foreach ($packages as $package) {
            echo "✅ ID: {$package['id']}, Title: {$package['title']}, Client: {$package['client_id']}, Created: {$package['created_at']}\n";
        }
    }
    
    echo "\n";
    
    // Check feedback questions
    echo "❓ Feedback Questions:\n";
    $stmt = $conn->query("SELECT id, title, type, client_id FROM feedback_questions WHERE is_deleted = 0");
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($questions)) {
        echo "❌ No feedback questions found\n";
    } else {
        foreach ($questions as $question) {
            echo "✅ ID: {$question['id']}, Title: {$question['title']}, Type: {$question['type']}, Client: {$question['client_id']}\n";
        }
    }
    
    echo "\n";
    
    // Check course feedback assignments
    echo "🔗 Course Feedback Assignments:\n";
    $stmt = $conn->query("SELECT id, course_id, feedback_package_id, feedback_type, is_active FROM course_feedback_assignments");
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($assignments)) {
        echo "❌ No course feedback assignments found\n";
    } else {
        foreach ($assignments as $assignment) {
            echo "✅ ID: {$assignment['id']}, Course: {$assignment['course_id']}, Package: {$assignment['feedback_package_id']}, Type: {$assignment['feedback_type']}, Active: {$assignment['is_active']}\n";
        }
    }
    
    echo "\n";
    
    // Check courses
    echo "📚 Courses:\n";
    $stmt = $conn->query("SELECT id, title, client_id FROM courses LIMIT 5");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($courses)) {
        echo "❌ No courses found\n";
    } else {
        foreach ($courses as $course) {
            echo "✅ ID: {$course['id']}, Title: {$course['title']}, Client: {$course['client_id']}\n";
        }
    }
    
    echo "\n";
    
    // Check if there are any prerequisites/post-requisites with feedback type
    echo "🔗 Prerequisites/Post-requisites with feedback:\n";
    $stmt = $conn->query("SELECT * FROM course_prerequisites WHERE prerequisite_type = 'feedback' OR postrequisite_type = 'feedback'");
    $prereqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($prereqs)) {
        echo "❌ No prerequisites/post-requisites with feedback type found\n";
    } else {
        foreach ($prereqs as $prereq) {
            echo "✅ Course: {$prereq['course_id']}, Prerequisite: {$prereq['prerequisite_type']}, Post-requisite: {$prereq['postrequisite_type']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
