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
    public function canUserTakeAssessment($assessmentId, $userId, $clientId = null, $courseId = null)
    {
        $db = $this->conn;

        // For standalone assessments, check if:
        // 1. Assessment exists and is not deleted
        // 2. Assessment belongs to the same client as the user
        // 3. User exists and is active
        // 4. User hasn't exceeded maximum attempts
        
        // First, check if the assessment exists and is accessible
        $sql = "
            SELECT COUNT(*) as count
            FROM assessment_package ap
            WHERE ap.id = ? 
            AND ap.is_deleted = 0
        ";
        
        $params = [$assessmentId];
        
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
        
        // Then check if the user exists and is active
        $sql = "
            SELECT COUNT(*) as count
            FROM user_profiles up
            WHERE up.id = ? 
            AND up.is_deleted = 0
            AND up.user_status = 'Active'
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            return false;
        }
        
        // Finally check if user has exceeded maximum attempts (course-specific if courseId provided)
        return !$this->hasExceededMaxAttempts($assessmentId, $userId, $clientId, $courseId);
    }

    // Create or get existing attempt
    public function createOrGetAttempt($assessmentId, $userId, $clientId = null, $courseId = null)
    {
        $db = $this->conn;

        // Check if user has exceeded maximum attempts (course-specific if courseId provided)
        if ($this->hasExceededMaxAttempts($assessmentId, $userId, $clientId, $courseId)) {
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
            SELECT ap.time_limit
            FROM assessment_package ap


            WHERE ap.id = ?
        ");
        $stmt->execute([$assessmentId]);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $timeLimit = $assessment['time_limit'] ?? 60; // Default 60 minutes
        
        // Use the provided course_id, or fallback to default if not provided
        if (!$courseId) {
            // Fallback to the old logic only if course_id is not provided
            $stmt = $db->prepare("
                SELECT COALESCE(
                    COALESCE(cpr.course_id, cpre.course_id), 
                    1
                ) as course_id
                FROM assessment_package ap
                LEFT JOIN course_post_requisites cpr ON ap.id = cpr.content_id AND cpr.content_type = 'assessment'
                LEFT JOIN course_prerequisites cpre ON ap.id = cpr.prerequisite_id AND cpr.prerequisite_type = 'assessment'
                WHERE ap.id = ?
            ");
            $stmt->execute([$assessmentId]);
            $fallbackResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $courseId = $fallbackResult['course_id'] ?? 1;
        }

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
                user_id, assessment_id, course_id, attempt_number, status, 
                started_at, time_limit, time_remaining, 
                current_question, answers, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, 'in_progress', 
                NOW(), ?, ?, 
                1, '{}', NOW(), NOW()
            )
        ");
        
        $stmt->execute([
            $userId, 
            $assessmentId, 
            $courseId,
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
        
        // Ensure questionId is integer and answer is properly formatted
        $questionIdInt = (int)$questionId;
        
        // For objective questions, ensure answer is integer
        // For subjective questions, keep as string
        $questionStmt = $db->prepare("SELECT question_type FROM assessment_questions WHERE id = ?");
        $questionStmt->execute([$questionIdInt]);
        $question = $questionStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($question && strtolower($question['question_type']) === 'objective') {
            // Ensure answer is integer for objective questions
            $formattedAnswer = (int)$answer;
        } else {
            // Keep subjective answers as strings
            $formattedAnswer = (string)$answer;
        }
        
        // Update answer for this question
        $answers[$questionIdInt] = $formattedAnswer;
        
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

        // Get attempt data with negative marking information
        $stmt = $db->prepare("
            SELECT a.*, ap.passing_percentage, ap.negative_marking, ap.negative_marking_percentage
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
        $negativeMarking = $attempt['negative_marking'] ?? 'No';
        $negativeMarkingPercentage = $attempt['negative_marking_percentage'] ?? 0;

        foreach ($questionIds as $questionId) {
            if (isset($answers[$questionId])) {
                // Ensure questionId is integer for consistency
                $questionIdInt = (int)$questionId;
                $answer = $answers[$questionId];
                
                $questionScore = $this->calculateQuestionScore(
                    $questionIdInt, 
                    $answer, 
                    $negativeMarking, 
                    $negativeMarkingPercentage
                );
                $totalScore += $questionScore['score'];
                $maxScore += $questionScore['max_score'];
                if ($questionScore['score'] > 0) {
                    $correctAnswers++;
                }
            } else {
                // Question not answered - count towards max score but not towards user score
                // Get question details to add to max score
                $stmt = $db->prepare("SELECT marks FROM assessment_questions WHERE id = ?");
                $stmt->execute([$questionId]);
                $question = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($question) {
                    $maxScore += $question['marks'] ?? 1;
                }
            }
        }

        // Ensure total score doesn't go below 0 due to negative marking
        if ($totalScore < 0) {
            $totalScore = 0;
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

        // Save results to assessment_results table
        $stmt = $db->prepare("
            INSERT INTO assessment_results (
                course_id, user_id, assessment_id, attempt_number, score, max_score,
                percentage, passed, time_taken, started_at, completed_at, answers
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?
            )
        ");
        
        // Calculate time taken in minutes
        $startedAt = new DateTime($attempt['started_at']);
        $completedAt = new DateTime();
        $timeTaken = $completedAt->diff($startedAt)->i + ($completedAt->diff($startedAt)->h * 60);
        
        $resultInserted = $stmt->execute([
            $attempt['course_id'],
            $attempt['user_id'],
            $attempt['assessment_id'],
            $attempt['attempt_number'],
            $totalScore,
            $maxScore,
            $percentage,
            $passed ? 1 : 0,
            $timeTaken,
            $attempt['started_at'],
            json_encode($answers)
        ]);
        
        if (!$resultInserted) {
            error_log("Failed to insert assessment results for attempt ID: " . $attemptId);
        }

        return [
            'success' => true,
            'score' => $totalScore,
            'max_score' => $maxScore,
            'percentage' => $percentage,
            'passed' => $passed,
            'correct_answers' => $correctAnswers,
            'total_questions' => count($questionIds),
            'negative_marking_applied' => $negativeMarking === 'Yes',
            'negative_marking_percentage' => $negativeMarkingPercentage
        ];
    }

    // Calculate score for a single question
    private function calculateQuestionScore($questionId, $answer, $negativeMarking = 'No', $negativeMarkingPercentage = 0)
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

        if (strtolower($question['question_type']) === 'objective') {
            // Ensure answer is treated as integer for comparison
            $answerId = (int)$answer;
            $questionIdInt = (int)$questionId;
            
            // Check if answer is correct
            $stmt = $db->prepare("
                SELECT COUNT(*) as count FROM assessment_options 
                WHERE question_id = ? AND id = ? AND is_correct = 1
            ");
            $stmt->execute([$questionIdInt, $answerId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $isCorrect = $result['count'] > 0;
            
            if ($isCorrect) {
                // Correct answer - full marks
                $score = $maxScore;
            } else {
                // Incorrect answer - apply negative marking if enabled
                if ($negativeMarking === 'Yes' && $negativeMarkingPercentage > 0) {
                    // Calculate negative marks based on percentage
                    $negativeMarks = ($maxScore * $negativeMarkingPercentage) / 100;
                    $score = -$negativeMarks; // Negative score
                } else {
                    // No negative marking - 0 marks
                    $score = 0;
                }
            }
            
            // Debug logging (remove in production)
            error_log("Assessment Scoring Debug - Question: {$questionId}, Answer: {$answer}, Answer Type: " . gettype($answer) . ", Correct: " . ($isCorrect ? 'Yes' : 'No') . ", Score: {$score}, Negative Marking: {$negativeMarking}, Negative Percentage: {$negativeMarkingPercentage}");
        } else {
            // Subjective questions - require manual grading
            // For now, give 0 marks until manual grading is implemented
            $score = 0;
            
            // Debug logging (remove in production)
            error_log("Assessment Scoring Debug - Subjective Question: {$questionId}, Score: {$score} (requires manual grading)");
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
    public function hasExceededMaxAttempts($assessmentId, $userId, $clientId = null, $courseId = null)
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
        
        // Count attempts based on whether course_id is provided
        if ($courseId) {
            // Course-specific attempt counting
            $stmt = $db->prepare("
                SELECT COUNT(*) as attempt_count 
                FROM assessment_attempts 
                WHERE assessment_id = ? 
                AND user_id = ? 
                AND course_id = ?
                AND status = 'completed'
                AND is_deleted = 0
            ");
            $stmt->execute([$assessmentId, $userId, $courseId]);
        } else {
            // Global attempt counting (fallback for backward compatibility)
            $stmt = $db->prepare("
                SELECT COUNT(*) as attempt_count 
                FROM assessment_attempts 
                WHERE assessment_id = ? 
                AND user_id = ? 
                AND status = 'completed'
                AND is_deleted = 0
            ");
            $stmt->execute([$assessmentId, $userId]);
        }
        
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

    // Get user's completed assessment attempts for a specific assessment and course
    public function getUserCompletedAssessmentAttemptsForCourse($assessmentId, $userId, $courseId, $clientId = null)
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
            AND aa.course_id = :course_id
            AND aa.status = 'completed'
            AND aa.is_deleted = 0
            ORDER BY aa.attempt_number DESC, aa.created_at DESC
        ");
        
        $stmt->execute([':assessment_id' => $assessmentId, ':user_id' => $userId, ':course_id' => $courseId]);
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

    // Get user's assessment results (pass/fail status) for a specific assessment and course
    public function getUserAssessmentResults($assessmentId, $userId, $clientId = null, $courseId = null)
    {
        $db = $this->conn;

        $stmt = $db->prepare("
            SELECT 
                ar.id,
                ar.score,
                ar.max_score,
                ar.percentage,
                ar.passed,
                ar.attempt_number,
                ar.completed_at,
                ar.created_at
            FROM assessment_results ar
            WHERE ar.assessment_id = :assessment_id 
            AND ar.user_id = :user_id
            " . ($courseId ? "AND ar.course_id = :course_id" : "") . "
            ORDER BY ar.attempt_number DESC, ar.created_at DESC
            LIMIT 1
        ");
        
        $params = [':assessment_id' => $assessmentId, ':user_id' => $userId];
        if ($courseId) {
            $params[':course_id'] = $courseId;
        }
        
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
} 