<?php
require_once 'config/Database.php';

class SurveyResponseModel {
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
     * Start a survey (create initial entry with started_at)
     */
    public function startSurvey($clientId, $courseId, $userId, $surveyPackageId) {
        try {
            $sql = "INSERT INTO course_survey_responses 
                    (client_id, course_id, user_id, survey_package_id, question_id, response_type, 
                     rating_value, text_response, choice_response, file_response, response_data, started_at, submitted_at) 
                    VALUES 
                    (:client_id, :course_id, :user_id, :survey_package_id, :question_id, :response_type,
                     :rating_value, :text_response, :choice_response, :file_response, :response_data, NOW(), NULL)
                    ON DUPLICATE KEY UPDATE
                    started_at = CASE WHEN started_at IS NULL THEN NOW() ELSE started_at END,
                    submitted_at = NULL,
                    updated_at = CURRENT_TIMESTAMP";

            $stmt = $this->conn->prepare($sql);
            $params = [
                ':client_id' => $clientId,
                ':course_id' => $courseId,
                ':user_id' => $userId,
                ':survey_package_id' => $surveyPackageId,
                ':question_id' => 7, // Using existing question ID 7
                ':response_type' => 'survey_start',
                ':rating_value' => null,
                ':text_response' => 'Survey started',
                ':choice_response' => null,
                ':file_response' => null,
                ':response_data' => null
            ];

            $result = $stmt->execute($params);
            
            if ($result) {
                return $this->conn->lastInsertId();
            } else {
                $this->lastErrorMessage = "Failed to start survey";
                return false;
            }
        } catch (PDOException $e) {
            $this->lastErrorMessage = "Database error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Complete a survey (update with completed_at and response data)
     */
    public function completeSurvey($clientId, $courseId, $userId, $surveyPackageId, $responses = []) {
        try {
            // Prepare response data for storage
            $responseData = null;
            $textResponse = null;
            $choiceResponse = null;
            $fileResponse = null;
            $ratingValue = null;
            
            if (!empty($responses)) {
                // Store all responses as JSON in response_data
                $responseData = json_encode($responses);
                
                // Also store a summary in text_response
                $responseSummary = [];
                foreach ($responses as $questionId => $response) {
                    $responseSummary[] = "Q{$questionId}: " . (is_array($response['value']) ? implode(', ', $response['value']) : $response['value']);
                }
                $textResponse = implode(' | ', $responseSummary);
            }
            
            $sql = "UPDATE course_survey_responses 
                    SET completed_at = NOW(),
                        submitted_at = NOW(),
                        text_response = :text_response,
                        choice_response = :choice_response,
                        file_response = :file_response,
                        rating_value = :rating_value,
                        response_data = :response_data,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE client_id = :client_id 
                    AND course_id = :course_id 
                    AND user_id = :user_id 
                    AND survey_package_id = :survey_package_id
                    AND response_type = 'survey_start'";

            $stmt = $this->conn->prepare($sql);
            $params = [
                ':client_id' => $clientId,
                ':course_id' => $courseId,
                ':user_id' => $userId,
                ':survey_package_id' => $surveyPackageId,
                ':text_response' => $textResponse,
                ':choice_response' => $choiceResponse,
                ':file_response' => $fileResponse,
                ':rating_value' => $ratingValue,
                ':response_data' => $responseData
            ];

            $result = $stmt->execute($params);
            
            if ($result) {
                return $stmt->rowCount() > 0;
            } else {
                $this->lastErrorMessage = "Failed to complete survey";
                return false;
            }
        } catch (PDOException $e) {
            $this->lastErrorMessage = "Database error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Save a survey response
     */
    public function saveResponse($data) {
        try {
            $sql = "INSERT INTO course_survey_responses 
                    (client_id, course_id, user_id, survey_package_id, question_id, response_type, 
                     rating_value, text_response, choice_response, file_response, response_data, submitted_at) 
                    VALUES 
                    (:client_id, :course_id, :user_id, :survey_package_id, :question_id, :response_type,
                     :rating_value, :text_response, :choice_response, :file_response, :response_data, NOW())
                    ON DUPLICATE KEY UPDATE
                    rating_value = VALUES(rating_value),
                    text_response = VALUES(text_response),
                    choice_response = VALUES(choice_response),
                    file_response = VALUES(file_response),
                    response_data = VALUES(response_data),
                    submitted_at = NOW(),
                    updated_at = CURRENT_TIMESTAMP";

            $stmt = $this->conn->prepare($sql);
            $params = [
                ':client_id' => $data['client_id'],
                ':course_id' => $data['course_id'],
                ':user_id' => $data['user_id'],
                ':survey_package_id' => $data['survey_package_id'],
                ':question_id' => $data['question_id'],
                ':response_type' => $data['response_type'],
                ':rating_value' => $data['rating_value'],
                ':text_response' => $data['text_response'],
                ':choice_response' => $data['choice_response'],
                ':file_response' => $data['file_response'],
                ':response_data' => $data['response_data']
            ];

            $result = $stmt->execute($params);
            
            if ($result) {
                return $this->conn->lastInsertId();
            } else {
                $this->lastErrorMessage = "Failed to save survey response";
                return false;
            }
        } catch (PDOException $e) {
            $this->lastErrorMessage = "Database error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get survey responses for a specific course and user
     */
    public function getResponsesByCourseAndUser($courseId, $userId, $surveyPackageId = null) {
        try {
            // First, check if we have a survey_start record (new single-entry approach)
            $sql = "SELECT * FROM course_survey_responses 
                    WHERE course_id = ? AND user_id = ? AND response_type = 'survey_start'";
            
            $params = [$courseId, $userId];
            
            if ($surveyPackageId) {
                $sql .= " AND survey_package_id = ?";
                $params[] = $surveyPackageId;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $surveyStartRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($surveyStartRecord && !empty($surveyStartRecord['response_data'])) {
                // New single-entry approach - parse the response_data JSON
                $responseData = json_decode($surveyStartRecord['response_data'], true);
                $responses = [];
                
                if (is_array($responseData)) {
                    foreach ($responseData as $questionId => $questionResponse) {
                        // Get question details
                        $questionSql = "SELECT title, type FROM survey_questions WHERE id = ?";
                        $questionStmt = $this->conn->prepare($questionSql);
                        $questionStmt->execute([$questionId]);
                        $question = $questionStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($question) {
                            $response = [
                                'question_id' => $questionId,
                                'question_title' => $question['title'],
                                'question_type' => $question['type'],
                                'response_type' => $questionResponse['type'],
                                'text_response' => $surveyStartRecord['text_response'],
                                'submitted_at' => $surveyStartRecord['submitted_at'],
                                'completed_at' => $surveyStartRecord['completed_at'],
                                'started_at' => $surveyStartRecord['started_at']
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
                            } elseif ($questionResponse['type'] === 'choice' && isset($questionResponse['value'])) {
                                $optionText = $this->getOptionTextById($questionResponse['value']);
                                $response['option_text'] = $optionText;
                            } elseif ($questionResponse['type'] === 'text' && isset($questionResponse['value'])) {
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
                $sql = "SELECT csr.*, sq.title as question_title, sq.type as question_type, 
                               sqo.option_text as option_text
                        FROM course_survey_responses csr
                        JOIN survey_questions sq ON csr.question_id = sq.id
                        LEFT JOIN survey_question_options sqo ON csr.choice_response = sqo.id
                        WHERE csr.course_id = ? AND csr.user_id = ?";
                
                $params = [$courseId, $userId];
                
                if ($surveyPackageId) {
                    $sql .= " AND csr.survey_package_id = ?";
                    $params[] = $surveyPackageId;
                }
                
                $sql .= " ORDER BY csr.submitted_at DESC";
                
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
            $sql = "SELECT option_text FROM survey_question_options WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$optionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['option_text'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Check if user has already submitted survey for a specific survey package
     */
    public function hasUserSubmittedSurvey($courseId, $userId, $surveyPackageId) {
        try {
            // Check for survey completion by looking for completed_at timestamp
            $sql = "SELECT COUNT(*) FROM course_survey_responses 
                    WHERE course_id = ? AND user_id = ? AND survey_package_id = ? 
                    AND response_type = 'survey_start' AND completed_at IS NOT NULL";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $userId, $surveyPackageId]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get survey package details with questions for a course
     */
    public function getSurveyPackageWithQuestions($surveyPackageId, $courseId, $clientId = null) {
        try {
            // Get survey package details
            $sql = "SELECT sp.* FROM survey_package sp WHERE sp.id = ? AND sp.is_deleted = 0";
            $params = [$surveyPackageId];
            
            if ($clientId) {
                $sql .= " AND sp.client_id = ?";
                $params[] = $clientId;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $surveyPackage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$surveyPackage) {
                return null;
            }
            
            // Get questions for this survey package
            $sql = "SELECT sq.*, sqm.created_at as mapped_at
                    FROM survey_question_mapping sqm
                    JOIN survey_questions sq ON sqm.survey_question_id = sq.id
                    WHERE sqm.survey_package_id = ? AND sq.is_deleted = 0
                    ORDER BY sqm.created_at ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$surveyPackageId]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get options for each question
            foreach ($questions as &$question) {
                $optionsSql = "SELECT id, option_text, media_path 
                               FROM survey_question_options 
                               WHERE question_id = ? AND is_deleted = 0 
                               ORDER BY id ASC";
                $optionsStmt = $this->conn->prepare($optionsSql);
                $optionsStmt->execute([$question['id']]);
                $question['options'] = $optionsStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $surveyPackage['questions'] = $questions;
            return $surveyPackage;
            
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get all survey packages assigned to a course (prerequisites and post-requisites)
     */
    public function getCourseSurveyPackages($courseId, $clientId = null) {
        try {
            $surveyPackages = [];
            
            // Get prerequisite surveys
            $sql = "SELECT cp.prerequisite_id as survey_package_id, cp.prerequisite_description as description,
                           'prerequisite' as type, sp.title, sp.tags
                    FROM course_prerequisites cp
                    JOIN survey_package sp ON cp.prerequisite_id = sp.id
                    WHERE cp.course_id = ? AND cp.prerequisite_type = 'survey' AND sp.is_deleted = 0";
            
            $params = [$courseId];
            if ($clientId) {
                $sql .= " AND sp.client_id = ?";
                $params[] = $clientId;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $prerequisiteSurveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get post-requisite surveys
            $sql = "SELECT cpr.content_id as survey_package_id, cpr.description,
                           'post_requisite' as type, sp.title, sp.tags
                    FROM course_post_requisites cpr
                    JOIN survey_package sp ON cpr.content_id = sp.id
                    WHERE cpr.course_id = ? AND cpr.content_type = 'survey' AND sp.is_deleted = 0";
            
            $params = [$courseId];
            if ($clientId) {
                $sql .= " AND sp.client_id = ?";
                $params[] = $clientId;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $postRequisiteSurveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Combine and return
            $surveyPackages = array_merge($prerequisiteSurveys, $postRequisiteSurveys);
            
            return $surveyPackages;
            
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Delete survey responses for a specific course and user
     */
    public function deleteResponsesByCourseAndUser($courseId, $userId, $surveyPackageId = null) {
        try {
            $sql = "DELETE FROM course_survey_responses WHERE course_id = ? AND user_id = ?";
            $params = [$courseId, $userId];
            
            if ($surveyPackageId) {
                $sql .= " AND survey_package_id = ?";
                $params[] = $surveyPackageId;
            }
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get survey response statistics for a course
     */
    public function getSurveyResponseStats($courseId, $surveyPackageId = null) {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT csr.user_id) as total_responses,
                        COUNT(DISTINCT csr.question_id) as questions_answered,
                        MIN(csr.submitted_at) as first_response,
                        MAX(csr.submitted_at) as last_response
                    FROM course_survey_responses csr
                    WHERE csr.course_id = ?";
            
            $params = [$courseId];
            
            if ($surveyPackageId) {
                $sql .= " AND csr.survey_package_id = ?";
                $params[] = $surveyPackageId;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return null;
        }
    }
}
?>
