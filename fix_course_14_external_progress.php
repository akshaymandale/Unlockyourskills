<?php
/**
 * Fix external_progress time_spent for course ID 14
 */

require_once 'config/Database.php';
require_once 'models/ExternalProgressModel.php';
// Note: Completion tracking has been removed

echo "Fixing External Progress Time for Course ID 14\n";
echo "=============================================\n\n";

try {
    $database = new Database();
    $conn = $database->connect();
    $externalProgressModel = new ExternalProgressModel();
    // Note: Completion tracking has been removed
    
    $courseId = 14;
    
    echo "1. Finding external progress records for course ID $courseId...\n";
    
    // Find all external progress records for course 14
    $sql = "SELECT 
                ep.user_id, ep.course_id, ep.content_id, ep.client_id,
                ep.started_at, ep.completed_at, ep.time_spent,
                ep.is_completed, ep.visit_count
            FROM external_progress ep
            WHERE ep.course_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$courseId]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($records)) {
        echo "   ❌ No external progress records found for course ID $courseId\n";
        exit;
    }
    
    echo "   ✓ Found " . count($records) . " external progress records\n\n";
    
    $fixedCount = 0;
    $skippedCount = 0;
    
    foreach ($records as $i => $record) {
        echo "Record " . ($i + 1) . ":\n";
        echo "  - User: {$record['user_id']}, Content: {$record['content_id']}\n";
        echo "  - Current time_spent: {$record['time_spent']} seconds\n";
        echo "  - Started at: {$record['started_at']}\n";
        echo "  - Completed at: {$record['completed_at']}\n";
        echo "  - Is completed: " . ($record['is_completed'] ? 'Yes' : 'No') . "\n";
        
        // Calculate correct time_spent
        $correctTimeSpent = 0;
        if ($record['started_at'] && $record['completed_at']) {
            $startedAt = new DateTime($record['started_at']);
            $completedAt = new DateTime($record['completed_at']);
            $correctTimeSpent = $completedAt->getTimestamp() - $startedAt->getTimestamp();
            $correctTimeSpent = max(0, $correctTimeSpent);
        } elseif ($record['started_at'] && $record['is_completed']) {
            // If completed but no completed_at, calculate from started_at to now
            $startedAt = new DateTime($record['started_at']);
            $completedAt = new DateTime();
            $correctTimeSpent = $completedAt->getTimestamp() - $startedAt->getTimestamp();
            $correctTimeSpent = max(0, $correctTimeSpent);
        }
        
        echo "  - Correct time_spent: $correctTimeSpent seconds\n";
        
        if ($correctTimeSpent != $record['time_spent']) {
            echo "  🔄 Updating time_spent...\n";
            
            // Update the external_progress record
            $updateSql = "UPDATE external_progress SET 
                          time_spent = ?,
                          completed_at = CASE WHEN completed_at IS NULL AND is_completed = 1 THEN NOW() ELSE completed_at END,
                          updated_at = NOW()
                          WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $updateStmt = $conn->prepare($updateSql);
            $updateResult = $updateStmt->execute([
                $correctTimeSpent,
                $record['user_id'],
                $record['course_id'],
                $record['content_id'],
                $record['client_id']
            ]);
            
            if ($updateResult) {
                echo "  ✓ External progress updated successfully\n";
                
                // Update prerequisite completion if this is a prerequisite
                $prerequisiteResult = $prerequisiteModel->updatePrerequisiteCompletionFromProgress(
                    $record['user_id'],
                    $record['course_id'],
                    $record['content_id'],
                    'external',
                    $record['client_id']
                );
                
                if ($prerequisiteResult) {
                    echo "  ✓ Prerequisite completion updated: {$prerequisiteResult['time_spent']} seconds\n";
                } else {
                    echo "  ⚠️  Prerequisite completion update failed (may not be a prerequisite)\n";
                }
                
                $fixedCount++;
            } else {
                echo "  ❌ Failed to update external progress\n";
            }
        } else {
            echo "  ✓ Time_spent is already correct\n";
            $skippedCount++;
        }
        
        echo "\n";
    }
    
    echo "2. Summary:\n";
    echo "   - Total records processed: " . count($records) . "\n";
    echo "   - Records fixed: $fixedCount\n";
    echo "   - Records skipped (already correct): $skippedCount\n";
    
    if ($fixedCount > 0) {
        echo "\n3. Verifying fixes...\n";
        
        // Check the updated records
        $sql = "SELECT 
                    ep.user_id, ep.content_id, ep.time_spent,
                    pc.time_spent as pc_time_spent
                FROM external_progress ep
                LEFT JOIN prerequisite_completion pc ON (
                    ep.user_id = pc.user_id AND 
                    ep.course_id = pc.course_id AND 
                    ep.content_id = pc.prerequisite_id AND 
                    pc.prerequisite_type = 'external' AND
                    ep.client_id = pc.client_id
                )
                WHERE ep.course_id = ? AND ep.is_completed = 1
                ORDER BY ep.user_id, ep.content_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$courseId]);
        $updatedRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $mismatchCount = 0;
        foreach ($updatedRecords as $record) {
            if ($record['pc_time_spent'] && $record['time_spent'] != $record['pc_time_spent']) {
                $mismatchCount++;
                echo "   ❌ Mismatch: User {$record['user_id']}, Content {$record['content_id']}\n";
                echo "      External: {$record['time_spent']}s, Prerequisite: {$record['pc_time_spent']}s\n";
            }
        }
        
        if ($mismatchCount == 0) {
            echo "   ✓ All records are now consistent between external_progress and prerequisite_completion\n";
        } else {
            echo "   ⚠️  $mismatchCount records still have mismatches\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nFix completed.\n";
?>
