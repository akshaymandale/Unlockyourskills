<?php
/**
 * Fix the completion status for course 14 external prerequisite
 */

require_once 'config/Database.php';
require_once 'controllers/ExternalProgressController.php';

echo "Fixing Course 14 External Prerequisite Completion Status\n";
echo "======================================================\n\n";

try {
    $database = new Database();
    $conn = $database->connect();
    
    // Test parameters
    $userId = 75;
    $courseId = 14;
    $contentId = 1;
    $clientId = 2;
    
    echo "1. Current record before fix...\n";
    
    $sql = "SELECT user_id, course_id, content_id, client_id, 
                   started_at, completed_at, time_spent, is_completed, visit_count
            FROM external_progress 
            WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId, $courseId, $contentId, $clientId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   - Time spent: {$record['time_spent']} seconds\n";
    echo "   - Is completed: " . ($record['is_completed'] ? 'Yes' : 'No') . "\n";
    echo "   - Completed at: {$record['completed_at']}\n";
    
    echo "\n2. Triggering updateTimeSpent to fix completion status...\n";
    
    // Simulate the updateTimeSpent call
    $_POST = [
        'course_id' => $courseId,
        'content_id' => $contentId,
        'time_spent' => 100 // This should be overridden by calculation
    ];
    
    $_SESSION['user'] = [
        'id' => $userId,
        'client_id' => $clientId
    ];
    
    ob_start();
    $controller = new ExternalProgressController();
    $controller->updateTimeSpent();
    $output = ob_get_clean();
    
    echo "   - updateTimeSpent response: $output\n";
    
    echo "\n3. Checking record after fix...\n";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId, $courseId, $contentId, $clientId]);
    $fixedRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   - Time spent: {$fixedRecord['time_spent']} seconds\n";
    echo "   - Is completed: " . ($fixedRecord['is_completed'] ? 'Yes' : 'No') . "\n";
    echo "   - Completed at: {$fixedRecord['completed_at']}\n";
    
    if ($fixedRecord['is_completed'] == 1) {
        echo "   ✓ Completion status fixed!\n";
    } else {
        echo "   ❌ Completion status still not fixed\n";
    }
    
    if ($fixedRecord['time_spent'] > 100) {
        echo "   ✓ Time calculated from timestamps!\n";
    } else {
        echo "   ❌ Time not calculated correctly\n";
    }
    
    echo "\n4. Final verification...\n";
    
    // Calculate expected time
    if ($fixedRecord['started_at'] && $fixedRecord['completed_at']) {
        $startedAt = new DateTime($fixedRecord['started_at']);
        $completedAt = new DateTime($fixedRecord['completed_at']);
        $expectedTime = $completedAt->getTimestamp() - $startedAt->getTimestamp();
        echo "   - Expected time: $expectedTime seconds\n";
        echo "   - Actual time: {$fixedRecord['time_spent']} seconds\n";
        
        if ($fixedRecord['time_spent'] == $expectedTime) {
            echo "   ✓ Time calculation is perfect!\n";
        } else {
            echo "   ❌ Time calculation mismatch\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nFix completed.\n";
?>
