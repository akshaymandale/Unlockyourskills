<?php
require_once 'config/Database.php';

class AssessmentPlayerModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Get assessment with questions and options
    public function getAssessmentWithQuestions($assessmentId, $clientId = null)
    {
        $db = $this->conn;

        // Build WHERE clause for client isolation
        $clientWhere = '';
        $params = [':id' => $assessmentId];
        
        if ($clientId) {
            $clientWhere = 'AND client_id = :client_id';
            $params[':client_id'] = $clientId;
        }

        // Fetch the assessment data
        $stmt = $db->prepare("
            SELECT *
            FROM assessment_package
            WHERE id = :id AND is_deleted = 0 $clientWhere
        ");
        $stmt->execute($params);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assessment) {
            return null;
        }

        // Get question IDs from the mapping table
        $stmt = $db->prepare("
            SELECT question_id 
            FROM assessment_question_mapping 
            WHERE assessment_package_id = :assessment_id
        ");
        $stmt->execute([':assessment_id' => $assessmentId]);
        $questionMappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $questionIds = array_column($questionMappings, 'question_id');

        if (empty($questionIds)) {
            $assessment['selected_questions'] = [];
            return $assessment;
        }

        // Fetch questions with their options
        $placeholders = str_repeat('?,', count($questionIds) - 1) . '?';
        $stmt = $db->prepare("
            SELECT 
                q.id, 
                q.question_text AS title, 
                q.tags, 
                q.marks, 
                q.question_type AS type,
                q.level AS difficulty_level,
                q.competency_skills AS skills
            FROM assessment_questions q
            WHERE q.id IN ($placeholders) AND q.is_deleted = 0
            ORDER BY FIELD(q.id, $placeholders)
        ");
        
        // Execute with question IDs twice (once for IN clause, once for ORDER BY)
        $params = array_merge($questionIds, $questionIds);
        $stmt->execute($params);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch options for each question
        foreach ($questions as &$question) {
            $stmt = $db->prepare("
                SELECT id, option_text, is_correct, option_index
                FROM assessment_options
                WHERE question_id = ? AND is_deleted = 0
                ORDER BY option_index ASC
            ");
            $stmt->execute([$question['id']]);
            $question['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Add questions to assessment array
        $assessment['selected_questions'] = $questions;

        return $assessment;
    }

    // Check if user can take this assessment
    public function canUserTakeAssessment($assessmentId, $userId, $clientId = null)
    {
        $db = $this->conn;

        // For standalone assessments, check if:
        // 1. Assessment exists and is not deleted
        // 2. Assessment belongs to the same client as the user
        // 3. User exists and is active
        // 4. User hasn't exceeded maximum attempts
        
        $sql = "
            SELECT COUNT(*) as count
            FROM assessment_package ap
            JOIN user_profiles up ON up.client_id = ap.client_id
            WHERE ap.id = ? 
            AND up.id = ? 
            AND ap.is_deleted = 0 
            AND up.is_deleted = 0
            AND up.user_status = 'Active'
        ";
        
        $params = [$assessmentId, $userId];
        
        if ($clientId) {
            $sql .= " AND ap.client_id = ?";
            $params[] = $clientId;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            return false;
        }
        
        // Check if user has exceeded maximum attempts
        return !$this->hasExceededMaxAttempts($assessmentId, $userId, $clientId);
    }

    // Create or get existing attempt
    public function createOrGetAttempt($assessmentId, $userId, $clientId = null)
    {
        $db = $this->conn;

        // Check if user has exceeded maximum attempts
        if ($this->hasExceededMaxAttempts($assessmentId, $userId, $clientId)) {
            throw new Exception('Maximum attempts exceeded for this assessment');
        }

        // Check for existing incomplete attempt
        $stmt = $db->prepare("
            SELECT id FROM assessment_attempts
            WHERE assessment_id = ? AND user_id = ? AND status IN ('in_progress')
            AND is_deleted = 0
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$assessmentId, $userId]);
        $existingAttempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingAttempt) {
            return $existingAttempt['id'];
        }

        // Get assessment details for time limit
        $stmt = $db->prepare("
            SELECT time_limit FROM assessment_package WHERE id = ?
        ");
        $stmt->execute([$assessmentId]);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $timeLimit = $assessment['time_limit'] ?? 60; // Default 60 minutes

        // Get next attempt number
        $stmt = $db->prepare("
            SELECT COALESCE(MAX(attempt_number), 0) + 1 as next_attempt
            FROM assessment_attempts
            WHERE assessment_id = ? AND user_id = ? AND is_deleted = 0
        ");
        $stmt->execute([$assessmentId, $userId]);
        $attemptResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $attemptNumber = $attemptResult['next_attempt'];

        // Create new attempt
        $stmt = $db->prepare("
            INSERT INTO assessment_attempts (
                user_id, assessment_id, attempt_number, status, 
                started_at, time_limit, time_remaining, 
                current_question, answers, created_at, updated_at
            ) VALUES (
                ?, ?, ?, 'in_progress', 
                NOW(), ?, ?, 
                1, '{}', NOW(), NOW()
            )
        ");
        
        $stmt->execute([
            $userId, 
            $assessmentId, 
            $attemptNumber,
            $timeLimit, 
            $timeLimit * 60 // Convert to seconds
        ]);

        return $db->lastInsertId();
    }

    // Get attempt data
    public function getAttempt($attemptId)
    {
        $db = $this->conn;
        
        $stmt = $db->prepare("
            SELECT * FROM assessment_attempts WHERE id = ? AND is_deleted = 0
        ");
        $stmt->execute([$attemptId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Save answer for a question
    public function saveAnswer($attemptId, $questionId, $answer, $currentQuestion)
    {
        $db = $this->conn;

        // Get current answers
        $stmt = $db->prepare("
            SELECT answers FROM assessment_attempts WHERE id = ?
        ");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $answers = json_decode($attempt['answers'], true) ?: [];
        
        // Update answer for this question
        $answers[$questionId] = $answer;
        
        // Update attempt
        $stmt = $db->prepare("
            UPDATE assessment_attempts 
            SET answers = ?, current_question = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([json_encode($answers), $currentQuestion, $attemptId]);
    }

    // Submit assessment and calculate results
    public function submitAssessment($attemptId)
    {
        $db = $this->conn;

        // Get attempt data
        $stmt = $db->prepare("
            SELECT a.*, ap.passing_percentage
            FROM assessment_attempts a
            JOIN assessment_package ap ON a.assessment_id = ap.id
            WHERE a.id = ?
        ");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt) {
            return ['success' => false, 'message' => 'Attempt not found'];
        }

        // Calculate score
        $answers = json_decode($attempt['answers'], true) ?: [];
        
        // Get question IDs from the mapping table
        $stmt = $db->prepare("
            SELECT question_id 
            FROM assessment_question_mapping 
            WHERE assessment_package_id = ?
        ");
        $stmt->execute([$attempt['assessment_id']]);
        $questionMappings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $questionIds = array_column($questionMappings, 'question_id');
        
        $totalScore = 0;
        $maxScore = 0;
        $correctAnswers = 0;

        foreach ($questionIds as $questionId) {
            if (isset($answers[$questionId])) {
                $questionScore = $this->calculateQuestionScore($questionId, $answers[$questionId]);
                $totalScore += $questionScore['score'];
                $maxScore += $questionScore['max_score'];
                if ($questionScore['score'] > 0) {
                    $correctAnswers++;
                }
            }
        }

        $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 2) : 0;
        $passed = $percentage >= ($attempt['passing_percentage'] ?? 70);

        // Update attempt
        $stmt = $db->prepare("
            UPDATE assessment_attempts 
            SET status = 'completed', completed_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ");
        
        if (!$stmt->execute([$attemptId])) {
            return ['success' => false, 'message' => 'Failed to update attempt'];
        }

        return [
            'success' => true,
            'score' => $totalScore,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'passed' => $passed,
            'correct_answers' => $correctAnswers,
            'total_questions' => count($questionIds)
        ];
    }

    // Calculate score for a single question
    private function calculateQuestionScore($questionId, $answer)
    {
        $db = $this->conn;

        // Get question details
        $stmt = $db->prepare("
            SELECT question_type, marks FROM assessment_questions WHERE id = ?
        ");
        $stmt->execute([$questionId]);
        $question = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$question) {
            return ['score' => 0, 'max_score' => 0];
        }

        $maxScore = $question['marks'] ?? 1;

        if ($question['question_type'] === 'objective') {
            // Check if answer is correct
            $stmt = $db->prepare("
                SELECT COUNT(*) as count FROM assessment_options 
                WHERE question_id = ? AND id = ? AND is_correct = 1
            ");
            $stmt->execute([$questionId, $answer]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $score = $result['count'] > 0 ? $maxScore : 0;
        } else {
            // Subjective questions - give full marks for now
            // In a real system, you might want manual grading
            $score = $maxScore;
        }

        return ['score' => $score, 'max_score' => $maxScore];
    }

    // Get attempt progress
    public function getAttemptProgress($attemptId)
    {
        $db = $this->conn;

        $stmt = $db->prepare("
            SELECT current_question, answers, time_remaining
            FROM assessment_attempts WHERE id = ?
        ");
        $stmt->execute([$attemptId]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt) {
            return null;
        }

        $answers = json_decode($attempt['answers'], true) ?: [];
        $answeredCount = count($answers);

        return [
            'current_question' => $attempt['current_question'],
            'answered_count' => $answeredCount,
            'time_remaining' => $attempt['time_remaining']
        ];
    }

    // Update time remaining
    public function updateTimeRemaining($attemptId, $timeRemaining)
    {
        $db = $this->conn;

        $stmt = $db->prepare("
            UPDATE assessment_attempts 
            SET time_remaining = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        return $stmt->execute([$timeRemaining, $attemptId]);
    }

    // Verify attempt ownership
    public function verifyAttemptOwnership($attemptId, $userId)
    {
        $db = $this->conn;

        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM assessment_attempts 
            WHERE id = ? AND user_id = ? AND is_deleted = 0
        ");
        $stmt->execute([$attemptId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }

    // Check if user has exceeded maximum attempts for an assessment
    public function hasExceededMaxAttempts($assessmentId, $userId, $clientId = null)
    {
        $db = $this->conn;

        // Get assessment max attempts
        $stmt = $db->prepare("
            SELECT num_attempts FROM assessment_package 
            WHERE id = ? AND is_deleted = 0
        ");
        $stmt->execute([$assessmentId]);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$assessment || !$assessment['num_attempts']) {
            return false; // No limit set
        }

        $maxAttempts = intval($assessment['num_attempts']);
        
        // Count only completed user attempts
        $stmt = $db->prepare("
            SELECT COUNT(*) as attempt_count 
            FROM assessment_attempts 
            WHERE assessment_id = ? 
            AND user_id = ? 
            AND status = 'completed'
            AND is_deleted = 0
        ");
        
        $stmt->execute([$assessmentId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $attemptCount = intval($result['attempt_count']);
        return $attemptCount >= $maxAttempts;
    }

    // Get user's assessment attempts for a specific assessment
    public function getUserAssessmentAttempts($assessmentId, $userId, $clientId = null)
    {
        $db = $this->conn;

        $stmt = $db->prepare("
            SELECT 
                aa.id,
                aa.attempt_number,
                aa.status,
                aa.started_at,
                aa.completed_at,
                aa.created_at,
                aa.updated_at
            FROM assessment_attempts aa
            WHERE aa.assessment_id = :assessment_id 
            AND aa.user_id = :user_id 
            AND aa.is_deleted = 0
            ORDER BY aa.attempt_number DESC, aa.created_at DESC
        ");
        
        $stmt->execute([':assessment_id' => $assessmentId, ':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get user's completed assessment attempts for a specific assessment
    public function getUserCompletedAssessmentAttempts($assessmentId, $userId, $clientId = null)
    {
        $db = $this->conn;

        $stmt = $db->prepare("
            SELECT 
                aa.id,
                aa.attempt_number,
                aa.status,
                aa.started_at,
                aa.completed_at,
                aa.created_at,
                aa.updated_at
            FROM assessment_attempts aa
            WHERE aa.assessment_id = :assessment_id 
            AND aa.user_id = :user_id 
            AND aa.status = 'completed'
            AND aa.is_deleted = 0
            ORDER BY aa.attempt_number DESC, aa.created_at DESC
        ");
        
        $stmt->execute([':assessment_id' => $assessmentId, ':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get assessment details including num_attempts
    public function getAssessmentDetails($assessmentId, $clientId = null)
    {
        $db = $this->conn;

        // Build WHERE clause for client isolation
        $clientWhere = '';
        $params = [':id' => $assessmentId];
        
        if ($clientId) {
            $clientWhere = 'AND client_id = :client_id';
            $params[':client_id'] = $clientId;
        }

        // Fetch the assessment data
        $stmt = $db->prepare("
            SELECT id, title, tags, num_attempts, time_limit, passing_percentage
            FROM assessment_package
            WHERE id = :id AND is_deleted = 0 $clientWhere
        ");
        $stmt->execute($params);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $assessment;
    }
} 