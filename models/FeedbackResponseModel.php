<?php
require_once 'config/Database.php';

class FeedbackResponseModel {
    private $conn;
    private $lastErrorMessage = '';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get last error message from model operations
     */
    public function getLastError() {
        return $this->lastErrorMessage;
    }

    /**
     * Start feedback (create entry with started_at)
     */
    public function startFeedback($clientId, $courseId, $userId, $feedbackPackageId) {
        try {
            // Use a dummy question ID that exists in feedback_questions table
            // First, get any existing question ID for this feedback package
            $sql = "SELECT fq.id FROM feedback_questions fq
                    JOIN feedback_question_mapping fqm ON fq.id = fqm.feedback_question_id
                    WHERE fqm.feedback_package_id = ? AND fq.is_deleted = 0
                    LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$feedbackPackageId]);
            $question = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$question) {
                // If no questions exist, create a dummy question
                $sql = "INSERT INTO feedback_questions (title, type, is_deleted, created_at) 
                        VALUES ('Feedback Start', 'text', 0, NOW())";
                $this->conn->exec($sql);
                $questionId = $this->conn->lastInsertId();
                
                // Map it to the feedback package
                $sql = "INSERT INTO feedback_question_mapping (feedback_package_id, feedback_question_id) 
                        VALUES (?, ?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$feedbackPackageId, $questionId]);
            } else {
                $questionId = $question['id'];
            }
            
            $sql = "INSERT INTO course_feedback_responses 
                    (client_id, course_id, user_id, feedback_package_id, question_id, response_type, 
                     text_response, started_at, submitted_at) 
                    VALUES 
                    (?, ?, ?, ?, ?, 'feedback_start', 'Feedback started', NOW(), NULL)
                    ON DUPLICATE KEY UPDATE
                    started_at = NOW(),
                    submitted_at = NULL,
                    updated_at = NOW()";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$clientId, $courseId, $userId, $feedbackPackageId, $questionId]);
            
        } catch (PDOException $e) {
            error_log("Error starting feedback: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Complete feedback (update existing entry with all responses)
     */
    public function completeFeedback($clientId, $courseId, $userId, $feedbackPackageId, $responses = []) {
        try {
            // Create a summary of responses
            $responseSummary = [];
            foreach ($responses as $questionId => $response) {
                $responseSummary[] = "Q{$questionId}: " . (is_array($response['value']) ? implode(', ', $response['value']) : $response['value']);
            }
            $textResponse = implode('; ', $responseSummary);
            
            // Update the existing feedback_start record
            $sql = "UPDATE course_feedback_responses 
                    SET text_response = ?,
                        response_data = ?,
                        completed_at = NOW(),
                        submitted_at = NOW(),
                        updated_at = NOW()
                    WHERE course_id = ? AND user_id = ? AND feedback_package_id = ? AND response_type = 'feedback_start'";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                $textResponse,
                json_encode($responses),
                $courseId,
                $userId,
                $feedbackPackageId
            ]);
            
        } catch (PDOException $e) {
            error_log("Error completing feedback: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Save a feedback response
     */
    public function saveResponse($data) {
        try {
            $sql = "INSERT INTO course_feedback_responses 
                    (client_id, course_id, user_id, feedback_package_id, question_id, response_type, 
                     rating_value, text_response, choice_response, file_response, response_data) 
                    VALUES 
                    (:client_id, :course_id, :user_id, :feedback_package_id, :question_id, :response_type,
                     :rating_value, :text_response, :choice_response, :file_response, :response_data)
                    ON DUPLICATE KEY UPDATE
                    rating_value = VALUES(rating_value),
                    text_response = VALUES(text_response),
                    choice_response = VALUES(choice_response),
                    file_response = VALUES(file_response),
                    response_data = VALUES(response_data),
                    updated_at = CURRENT_TIMESTAMP";

            $stmt = $this->conn->prepare($sql);
            $params = [
                ':client_id' => $data['client_id'],
                ':course_id' => $data['course_id'],
                ':user_id' => $data['user_id'],
                ':feedback_package_id' => $data['feedback_package_id'],
                ':question_id' => $data['question_id'],
                ':response_type' => $data['response_type'],
                ':rating_value' => $data['rating_value'] ?? null,
                ':text_response' => $data['text_response'] ?? null,
                ':choice_response' => $data['choice_response'] ?? null,
                ':file_response' => $data['file_response'] ?? null,
                ':response_data' => $data['response_data'] ?? null
            ];
            $result = $stmt->execute($params);

            // Treat any successful execute as success. lastInsertId can be "0" for updates
            // or when values did not change, which is still a successful save for our purposes.
            if ($result === true) {
                // Lightweight debug log for success
                @file_put_contents(__DIR__ . '/../debug_backend.log', date('c') . " [OK] Saved feedback response for user_id={$data['user_id']} course_id={$data['course_id']} question_id={$data['question_id']}\n", FILE_APPEND);
            }
            return $result;
        } catch (PDOException $e) {
            error_log("Error saving feedback response: " . $e->getMessage());
            $this->lastErrorMessage = $e->getMessage();
            // Write extended debug info to local project log for easier inspection
            $debug = [
                'error' => $e->getMessage(),
                'data' => $data,
                // redact large fields if any in the future
            ];
            @file_put_contents(__DIR__ . '/../debug_backend.log', date('c') . ' [ERR] saveResponse ' . json_encode($debug) . "\n", FILE_APPEND);
            return false;
        }
    }

    /**
     * Get feedback responses for a specific course and user
     */
    public function getResponsesByCourseAndUser($courseId, $userId, $feedbackPackageId = null) {
        try {
            // First, check if we have a feedback_start record (new single-entry approach)
            $sql = "SELECT * FROM course_feedback_responses 
                    WHERE course_id = ? AND user_id = ? AND response_type = 'feedback_start'";
            
            $params = [$courseId, $userId];
            
            if ($feedbackPackageId) {
                $sql .= " AND feedback_package_id = ?";
                $params[] = $feedbackPackageId;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $feedbackStartRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($feedbackStartRecord && !empty($feedbackStartRecord['response_data'])) {
                // New single-entry approach - parse the response_data JSON
                $responseData = json_decode($feedbackStartRecord['response_data'], true);
                $responses = [];
                
                if (is_array($responseData)) {
                    foreach ($responseData as $questionId => $questionResponse) {
                        // Get question details
                        $questionSql = "SELECT title, type FROM feedback_questions WHERE id = ?";
                        $questionStmt = $this->conn->prepare($questionSql);
                        $questionStmt->execute([$questionId]);
                        $question = $questionStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($question) {
                            $response = [
                                'question_id' => $questionId,
                                'question_title' => $question['title'],
                                'question_type' => $question['type'],
                                'response_type' => $questionResponse['type'],
                                'text_response' => $feedbackStartRecord['text_response'],
                                'submitted_at' => $feedbackStartRecord['submitted_at'],
                                'completed_at' => $feedbackStartRecord['completed_at'],
                                'started_at' => $feedbackStartRecord['started_at']
                            ];
                            
                            // Handle different response types
                            if ($questionResponse['type'] === 'checkbox' && isset($questionResponse['value'])) {
                                $selectedOptionIds = $questionResponse['value'];
                                if (!is_array($selectedOptionIds)) {
                                    $selectedOptionIds = [$selectedOptionIds];
                                }
                                
                                $optionTexts = [];
                                foreach ($selectedOptionIds as $optionId) {
                                    $optionText = $this->getOptionTextById($optionId);
                                    if ($optionText) {
                                        $optionTexts[] = $optionText;
                                    }
                                }
                                $response['checkbox_options'] = $optionTexts;
                            } elseif (in_array($questionResponse['type'], ['choice', 'multi_choice', 'dropdown']) && isset($questionResponse['value'])) {
                                $optionText = $this->getOptionTextById($questionResponse['value']);
                                $response['option_text'] = $optionText;
                            } elseif (in_array($questionResponse['type'], ['text', 'short_answer', 'long_answer']) && isset($questionResponse['value'])) {
                                $response['text_response'] = $questionResponse['value'];
                            } elseif ($questionResponse['type'] === 'rating' && isset($questionResponse['value'])) {
                                $response['rating_value'] = $questionResponse['value'];
                            }
                            
                            $responses[] = $response;
                        }
                    }
                }
                
                return $responses;
            } else {
                // Fallback to old approach for individual question records
                $sql = "SELECT cfr.*, fq.title as question_title, fq.type as question_type, 
                               fqo.option_text as option_text
                        FROM course_feedback_responses cfr
                        JOIN feedback_questions fq ON cfr.question_id = fq.id
                        LEFT JOIN feedback_question_options fqo ON cfr.choice_response = fqo.id
                        WHERE cfr.course_id = ? AND cfr.user_id = ?";
                
                $params = [$courseId, $userId];
                
                if ($feedbackPackageId) {
                    $sql .= " AND cfr.feedback_package_id = ?";
                    $params[] = $feedbackPackageId;
                }
                
                $sql .= " ORDER BY cfr.submitted_at DESC";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
                
                $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Process checkbox responses to get actual option texts
                foreach ($responses as &$response) {
                    if ($response['response_type'] === 'checkbox' && !empty($response['response_data'])) {
                        $selectedOptionIds = json_decode($response['response_data'], true);
                        
                        // Handle both single values and arrays
                        if (!is_array($selectedOptionIds)) {
                            // Single value - convert to array
                            $selectedOptionIds = [$selectedOptionIds];
                        }
                        
                        if (is_array($selectedOptionIds)) {
                            $optionTexts = [];
                            foreach ($selectedOptionIds as $optionId) {
                                $optionText = $this->getOptionTextById($optionId);
                                if ($optionText) {
                                    $optionTexts[] = $optionText;
                                }
                            }
                            $response['checkbox_options'] = $optionTexts;
                        }
                    }
                }
                
                return $responses;
            }
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get option text by option ID
     */
    public function getOptionTextById($optionId) {
        try {
            $sql = "SELECT option_text FROM feedback_question_options WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$optionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['option_text'] : null;
        } catch (PDOException $e) {
            error_log("Error getting feedback option text: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if user has already submitted feedback for a specific feedback package
     */
    public function hasUserSubmittedFeedback($courseId, $userId, $feedbackPackageId) {
        try {
            $sql = "SELECT COUNT(*) FROM course_feedback_responses 
                    WHERE course_id = ? AND user_id = ? AND feedback_package_id = ? 
                    AND response_type = 'feedback_start' AND completed_at IS NOT NULL";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $userId, $feedbackPackageId]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error checking feedback submission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get feedback package details with questions for a course
     */
    public function getFeedbackPackageForCourse($courseId, $feedbackPackageId) {
        try {
            // First check course_post_requisites (existing system)
            $sql = "SELECT fp.*, cpr.requisite_type as feedback_type, cpr.is_required
                    FROM feedback_package fp
                    JOIN course_post_requisites cpr ON fp.id = cpr.content_id
                    WHERE cpr.course_id = ? AND cpr.content_id = ? AND cpr.content_type = 'feedback' AND cpr.is_deleted = 0";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $feedbackPackageId]);
            $feedbackPackage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If not found in post-requisites, check prerequisites
            if (!$feedbackPackage) {
                $sql = "SELECT fp.*, cp.prerequisite_type as feedback_type, cp.prerequisite_description
                        FROM feedback_package fp
                        JOIN course_prerequisites cp ON fp.id = cp.prerequisite_id
                        WHERE cp.course_id = ? AND cp.prerequisite_id = ? AND cp.prerequisite_type = 'feedback' AND cp.deleted_at IS NULL";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$courseId, $feedbackPackageId]);
                $feedbackPackage = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Add is_required field for prerequisites (default to true)
                if ($feedbackPackage) {
                    $feedbackPackage['is_required'] = 1;
                }
            }
            
            if (!$feedbackPackage) {
                return false;
            }
            
            // Get questions for this feedback package
            $sql = "SELECT fq.*, fqo.id as option_id, fqo.option_text, fqo.media_path as option_media
                    FROM feedback_questions fq
                    JOIN feedback_question_mapping fqm ON fq.id = fqm.feedback_question_id
                    LEFT JOIN feedback_question_options fqo ON fq.id = fqo.question_id AND fqo.is_deleted = 0
                    WHERE fqm.feedback_package_id = ? AND fq.is_deleted = 0
                    ORDER BY fq.id, fqo.id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$feedbackPackageId]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organize questions with options
            $organizedQuestions = [];
            foreach ($questions as $row) {
                $questionId = $row['id'];
                if (!isset($organizedQuestions[$questionId])) {
                    $organizedQuestions[$questionId] = [
                        'id' => $row['id'],
                        'title' => $row['title'],
                        'type' => $row['type'],
                        'tags' => $row['tags'],
                        'rating_scale' => $row['rating_scale'],
                        'rating_symbol' => $row['rating_symbol'],
                        'media_path' => $row['media_path'],
                        'options' => []
                    ];
                }
                
                if ($row['option_id']) {
                    $organizedQuestions[$questionId]['options'][] = [
                        'id' => $row['option_id'],
                        'text' => $row['option_text'],
                        'media_path' => $row['option_media']
                    ];
                }
            }
            
            $feedbackPackage['questions'] = array_values($organizedQuestions);
            return $feedbackPackage;
            
        } catch (PDOException $e) {
            error_log("Error getting feedback package: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all feedback packages assigned to a course
     */
    public function getFeedbackPackagesForCourse($courseId) {
        try {
            $sql = "SELECT fp.*, cpr.requisite_type as feedback_type, cpr.is_required, cpr.sort_order as feedback_order
                    FROM feedback_package fp
                    JOIN course_post_requisites cpr ON fp.id = cpr.content_id
                    WHERE cpr.course_id = ? AND cpr.content_type = 'feedback' AND cpr.is_deleted = 0
                    ORDER BY cpr.sort_order ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting feedback packages for course: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get feedback statistics for a course
     */
    public function getFeedbackStats($courseId, $feedbackPackageId = null) {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT cfr.user_id) as total_responses,
                        COUNT(DISTINCT cfr.question_id) as questions_answered,
                        AVG(cfr.rating_value) as avg_rating
                    FROM course_feedback_responses cfr
                    WHERE cfr.course_id = ?";
            
            $params = [$courseId];
            
            if ($feedbackPackageId) {
                $sql .= " AND cfr.feedback_package_id = ?";
                $params[] = $feedbackPackageId;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting feedback stats: " . $e->getMessage());
            return [
                'total_responses' => 0,
                'questions_answered' => 0,
                'avg_rating' => 0
            ];
        }
    }

    /**
     * Delete feedback responses for a specific course and user
     */
    public function deleteResponses($courseId, $userId, $feedbackPackageId = null) {
        try {
            $sql = "DELETE FROM course_feedback_responses 
                    WHERE course_id = ? AND user_id = ?";
            
            $params = [$courseId, $userId];
            
            if ($feedbackPackageId) {
                $sql .= " AND feedback_package_id = ?";
                $params[] = $feedbackPackageId;
            }
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error deleting feedback responses: " . $e->getMessage());
            return false;
        }
    }
}
?>
