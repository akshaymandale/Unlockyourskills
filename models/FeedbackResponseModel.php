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
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting feedback responses: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user has already submitted feedback for a specific feedback package
     */
    public function hasUserSubmittedFeedback($courseId, $userId, $feedbackPackageId) {
        try {
            $sql = "SELECT COUNT(*) FROM course_feedback_responses 
                    WHERE course_id = ? AND user_id = ? AND feedback_package_id = ?";
            
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
            // Get feedback package details from course_post_requisites (existing system)
            $sql = "SELECT fp.*, cpr.requisite_type as feedback_type, cpr.is_required
                    FROM feedback_package fp
                    JOIN course_post_requisites cpr ON fp.id = cpr.content_id
                    WHERE cpr.course_id = ? AND cpr.content_id = ? AND cpr.content_type = 'feedback' AND cpr.is_deleted = 0";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $feedbackPackageId]);
            $feedbackPackage = $stmt->fetch(PDO::FETCH_ASSOC);
            
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
