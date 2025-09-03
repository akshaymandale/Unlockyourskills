<?php
/**
 * Update Survey Response Type Enum
 * Add 'checkbox' to the response_type enum in course_survey_responses table
 */

require_once 'config/autoload.php';
require_once 'config/Database.php';

echo "<h1>Update Survey Response Type Enum</h1>";

try {
    $db = new Database();
    $conn = $db->connect();
    
    echo "<h2>Database Connection</h2>";
    echo "<p style='color: green;'>✓ Database connected successfully</p>";
    
    // Check current enum values
    echo "<h2>Current Enum Values</h2>";
    $stmt = $conn->query("SHOW COLUMNS FROM course_survey_responses LIKE 'response_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Current response_type definition: " . $column['Type'] . "</p>";
    
    // Update the enum to include 'checkbox'
    echo "<h2>Updating Enum</h2>";
    $sql = "ALTER TABLE course_survey_responses 
            MODIFY COLUMN response_type ENUM('rating', 'text', 'choice', 'checkbox', 'file') NOT NULL";
    
    $result = $conn->exec($sql);
    
    if ($result !== false) {
        echo "<p style='color: green;'>✓ Enum updated successfully</p>";
        
        // Verify the update
        $stmt = $conn->query("SHOW COLUMNS FROM course_survey_responses LIKE 'response_type'");
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Updated response_type definition: " . $column['Type'] . "</p>";
        
    } else {
        echo "<p style='color: red;'>✗ Failed to update enum</p>";
    }
    
    // Test updating existing checkbox response
    echo "<h2>Test Checkbox Response</h2>";
    try {
        // First, check if there's an existing record
        $checkSql = "SELECT * FROM course_survey_responses 
                     WHERE user_id = 75 AND course_id = 8 AND survey_package_id = 3 AND question_id = 7";
        $stmt = $conn->prepare($checkSql);
        $stmt->execute();
        $existingRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingRecord) {
            echo "<p style='color: blue;'>Found existing record (ID: {$existingRecord['id']})</p>";
            echo "<p><strong>Current response_type:</strong> {$existingRecord['response_type']}</p>";
            echo "<p><strong>Current response_data:</strong> {$existingRecord['response_data']}</p>";
            
            // Update the existing record to use checkbox type
            $updateData = [
                'response_type' => 'checkbox',
                'response_data' => json_encode(['28', '29']),
                'rating_value' => null,
                'text_response' => null,
                'choice_response' => null,
                'file_response' => null
            ];
            
            $updateSql = "UPDATE course_survey_responses 
                          SET response_type = :response_type,
                              response_data = :response_data,
                              rating_value = :rating_value,
                              text_response = :text_response,
                              choice_response = :choice_response,
                              file_response = :file_response,
                              updated_at = CURRENT_TIMESTAMP
                          WHERE id = :id";
            
            $updateData['id'] = $existingRecord['id'];
            $stmt = $conn->prepare($updateSql);
            $result = $stmt->execute($updateData);
            
            if ($result) {
                echo "<p style='color: green;'>✓ Test checkbox response updated successfully</p>";
                
                // Show the updated data
                $stmt = $conn->prepare("SELECT * FROM course_survey_responses WHERE id = ?");
                $stmt->execute([$existingRecord['id']]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "<h3>Updated Record:</h3>";
                echo "<pre>" . print_r($record, true) . "</pre>";
                
                // Test the display functionality
                echo "<h3>Display Test:</h3>";
                if ($record['response_type'] === 'checkbox' && !empty($record['response_data'])) {
                    $selectedOptionIds = json_decode($record['response_data'], true);
                    if (is_array($selectedOptionIds)) {
                        echo "<p><strong>Selected Option IDs:</strong> " . implode(', ', $selectedOptionIds) . "</p>";
                        
                        // Get option texts
                        $optionTexts = [];
                        foreach ($selectedOptionIds as $optionId) {
                            $optionStmt = $conn->prepare("SELECT option_text FROM survey_question_options WHERE id = ?");
                            $optionStmt->execute([$optionId]);
                            $optionResult = $optionStmt->fetch(PDO::FETCH_ASSOC);
                            if ($optionResult) {
                                $optionTexts[] = $optionResult['option_text'];
                            }
                        }
                        
                        echo "<p><strong>Option Texts:</strong> " . implode(', ', $optionTexts) . "</p>";
                        echo "<p><strong>Display Format:</strong></p>";
                        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
                        foreach ($optionTexts as $optionText) {
                            echo '<span class="badge bg-info me-1 mb-1">' . htmlspecialchars($optionText) . '</span>';
                        }
                        echo "</div>";
                    }
                }
            } else {
                echo "<p style='color: red;'>✗ Test checkbox response update failed</p>";
            }
        } else {
            echo "<p style='color: orange;'>No existing record found to update. Please submit a survey first.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Test error: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}
?>