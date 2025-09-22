<?php
/**
 * Simple debug script for course ID 14 external prerequisite
 */

require_once 'config/Database.php';
require_once 'models/ExternalProgressModel.php';
// Note: Completion tracking has been removed

echo "Simple Debug for Course ID 14 External Prerequisite\n";
echo "==================================================\n\n";

try {
    $database = new Database();
    $conn = $database->connect();
    $externalProgressModel = new ExternalProgressModel();
    // Note: Completion tracking has been removed
    
    $courseId = 14;
    
    echo "1. Checking external progress records for course ID $courseId...\n";
    
    // Get external progress records
    $sql = "SELECT user_id, course_id, content_id, client_id, 
                   started_at, completed_at, time_spent, is_completed, visit_count
            FROM external_progress 
            WHERE course_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$courseId]);
    $externalRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($externalRecords)) {
        echo "   ❌ No external progress records found for course ID $courseId\n";
        exit;
    }
    
    echo "   ✓ Found " . count($externalRecords) . " external progress records\n\n";
    
    foreach ($externalRecords as $i => $record) {
        echo "External Progress Record " . ($i + 1) . ":\n";
        echo "  - User ID: {$record['user_id']}\n";
        echo "  - Content ID: {$record['content_id']}\n";
        echo "  - Client ID: {$record['client_id']}\n";
        echo "  - Time spent: {$record['time_spent']} seconds\n";
        echo "  - Started at: {$record['started_at']}\n";
        echo "  - Completed at: {$record['completed_at']}\n";
        echo "  - Is completed: " . ($record['is_completed'] ? 'Yes' : 'No') . "\n";
        echo "  - Visit count: {$record['visit_count']}\n";
        
        // Calculate expected time
        if ($record['started_at'] && $record['completed_at']) {
            $startedAt = new DateTime($record['started_at']);
            $completedAt = new DateTime($record['completed_at']);
            $expectedTime = $completedAt->getTimestamp() - $startedAt->getTimestamp();
            echo "  - Expected time: $expectedTime seconds\n";
            
            if ($record['time_spent'] == $expectedTime) {
                echo "  ✓ Time is correct\n";
            } else {
                echo "  ❌ Time mismatch: stored ({$record['time_spent']}) vs expected ($expectedTime)\n";
            }
        } else {
            echo "  - No timestamps available for calculation\n";
        }
        
        echo "\n";
    }
    
    echo "2. Checking prerequisite completion records...\n";
    
    // Get prerequisite completion records for external prerequisites
    $sql = "SELECT user_id, course_id, prerequisite_id, prerequisite_type, client_id,
                   time_spent, is_completed, completion_percentage
            FROM prerequisite_completion 
            WHERE course_id = ? AND prerequisite_type = 'external'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$courseId]);
    $prerequisiteRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($prerequisiteRecords)) {
        echo "   ❌ No prerequisite completion records found for course ID $courseId\n";
    } else {
        echo "   ✓ Found " . count($prerequisiteRecords) . " prerequisite completion records\n\n";
        
        foreach ($prerequisiteRecords as $i => $record) {
            echo "Prerequisite Completion Record " . ($i + 1) . ":\n";
            echo "  - User ID: {$record['user_id']}\n";
            echo "  - Prerequisite ID: {$record['prerequisite_id']}\n";
            echo "  - Client ID: {$record['client_id']}\n";
            echo "  - Time spent: {$record['time_spent']} seconds\n";
            echo "  - Is completed: " . ($record['is_completed'] ? 'Yes' : 'No') . "\n";
            echo "  - Completion %: {$record['completion_percentage']}%\n";
            echo "\n";
        }
    }
    
    echo "3. Comparing external progress with prerequisite completion...\n";
    
    foreach ($externalRecords as $extRecord) {
        // Find matching prerequisite completion record
        $sql = "SELECT time_spent, is_completed, completion_percentage
                FROM prerequisite_completion 
                WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? 
                AND prerequisite_type = 'external' AND client_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $extRecord['user_id'], 
            $extRecord['course_id'], 
            $extRecord['content_id'], 
            $extRecord['client_id']
        ]);
        $prereqRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($prereqRecord) {
            echo "Comparison for User {$extRecord['user_id']}, Content {$extRecord['content_id']}:\n";
            echo "  - External progress time: {$extRecord['time_spent']} seconds\n";
            echo "  - Prerequisite completion time: {$prereqRecord['time_spent']} seconds\n";
            
            if ($extRecord['time_spent'] == $prereqRecord['time_spent']) {
                echo "  ✓ Times match\n";
            } else {
                echo "  ❌ Times don't match\n";
            }
            echo "\n";
        } else {
            echo "No prerequisite completion record found for User {$extRecord['user_id']}, Content {$extRecord['content_id']}\n\n";
        }
    }
    
    echo "4. Testing time calculation methods...\n";
    
    if (!empty($externalRecords)) {
        $firstRecord = $externalRecords[0];
        
        // Test calculateTimeSpentToNow method
        echo "   Testing calculateTimeSpentToNow for first record...\n";
        $reflection = new ReflectionClass($externalProgressModel);
        $method = $reflection->getMethod('calculateTimeSpentToNow');
        $method->setAccessible(true);
        
        $calculatedTime = $method->invoke(
            $externalProgressModel,
            $firstRecord['user_id'],
            $firstRecord['course_id'],
            $firstRecord['content_id'],
            $firstRecord['client_id']
        );
        
        echo "   - Calculated time: $calculatedTime seconds\n";
        echo "   - Stored time: {$firstRecord['time_spent']} seconds\n";
        
        if ($calculatedTime == $firstRecord['time_spent']) {
            echo "   ✓ Calculated time matches stored time\n";
        } else {
            echo "   ❌ Calculated time differs from stored time\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nDebug completed.\n";
?>
