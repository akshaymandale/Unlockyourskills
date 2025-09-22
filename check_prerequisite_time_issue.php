<?php
/**
 * Check current prerequisite time calculation issue
 */

require_once 'config/Database.php';
// Note: Completion tracking has been removed

echo "Checking Prerequisite Time Calculation Issue\n";
echo "===========================================\n\n";

try {
    $database = new Database();
    $conn = $database->connect();
    // Note: Completion tracking has been removed
    
    // Check for external prerequisites with time_spent = 0
    $sql = "SELECT 
                pc.user_id, pc.course_id, pc.prerequisite_id, pc.prerequisite_type,
                pc.time_spent as prerequisite_time_spent, pc.is_completed as prerequisite_completed,
                ep.time_spent as external_time_spent, ep.started_at, ep.completed_at, ep.is_completed as external_completed
            FROM prerequisite_completion pc
            LEFT JOIN external_progress ep ON (
                pc.prerequisite_type = 'external' AND 
                pc.user_id = ep.user_id AND 
                pc.course_id = ep.course_id AND 
                pc.prerequisite_id = ep.content_id AND 
                pc.client_id = ep.client_id
            )
            WHERE pc.prerequisite_type = 'external' 
            AND pc.time_spent = 0
            AND ep.time_spent > 0
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($results)) {
        echo "✓ No external prerequisites found with time_spent = 0 but external_progress.time_spent > 0\n";
        echo "  This suggests the issue may already be resolved or there are no affected records.\n\n";
    } else {
        echo "❌ Found " . count($results) . " external prerequisites with time calculation issues:\n\n";
        
        foreach ($results as $i => $row) {
            echo ($i + 1) . ". User: {$row['user_id']}, Course: {$row['course_id']}, Content: {$row['prerequisite_id']}\n";
            echo "   - Prerequisite time_spent: {$row['prerequisite_time_spent']} seconds\n";
            echo "   - External time_spent: {$row['external_time_spent']} seconds\n";
            echo "   - Started at: {$row['started_at']}\n";
            echo "   - Completed at: {$row['completed_at']}\n";
            echo "   - External completed: " . ($row['external_completed'] ? 'Yes' : 'No') . "\n";
            echo "   - Prerequisite completed: " . ($row['prerequisite_completed'] ? 'Yes' : 'No') . "\n\n";
            
            // Test the fix
            echo "   🔄 Testing fix for this record...\n";
            $updateResult = $prerequisiteModel->updatePrerequisiteCompletionFromProgress(
                $row['user_id'], $row['course_id'], $row['prerequisite_id'], 'external', 1
            );
            
            if ($updateResult) {
                echo "   ✓ Update successful - New time_spent: {$updateResult['time_spent']} seconds\n";
            } else {
                echo "   ❌ Update failed\n";
            }
            echo "\n";
        }
    }
    
    // Check overall statistics
    echo "Overall Statistics:\n";
    echo "==================\n";
    
    $sql = "SELECT 
                COUNT(*) as total_external_prerequisites,
                SUM(CASE WHEN pc.time_spent > 0 THEN 1 ELSE 0 END) as with_time_spent,
                SUM(CASE WHEN pc.time_spent = 0 THEN 1 ELSE 0 END) as without_time_spent,
                AVG(pc.time_spent) as avg_time_spent
            FROM prerequisite_completion pc
            WHERE pc.prerequisite_type = 'external'";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total external prerequisites: {$stats['total_external_prerequisites']}\n";
    echo "With time_spent > 0: {$stats['with_time_spent']}\n";
    echo "With time_spent = 0: {$stats['without_time_spent']}\n";
    echo "Average time_spent: " . round($stats['avg_time_spent'], 2) . " seconds\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nCheck completed.\n";
?>
