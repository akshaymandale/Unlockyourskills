<?php

require_once 'config/Database.php';

class AssessmentAttemptIncreaseModel {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    /**
     * Get courses where users have exceeded assessment attempts and failed
     */
    public function getCoursesWithFailedAssessments($clientId) {
        try {
            // Get courses where users have failed assessments (considering latest results only)
            $sql = "
                SELECT DISTINCT 
                    c.id as course_id,
                    c.name as course_name,
                    c.description as course_description,
                    COUNT(DISTINCT latest_results.user_id) as failed_users_count,
                    COUNT(DISTINCT latest_results.assessment_id) as failed_assessments_count
                FROM courses c
                INNER JOIN (
                    SELECT 
                        ar.course_id,
                        ar.user_id,
                        ar.assessment_id,
                        ar.passed,
                        ROW_NUMBER() OVER (PARTITION BY ar.user_id, ar.assessment_id, ar.course_id ORDER BY ar.completed_at DESC) as rn
                    FROM assessment_results ar
                ) latest_results ON c.id = latest_results.course_id AND latest_results.rn = 1 AND latest_results.passed = 0
                INNER JOIN assessment_package ap ON latest_results.assessment_id = ap.id
                WHERE c.client_id = ? 
                GROUP BY c.id, c.name, c.description
                ORDER BY failed_users_count DESC, c.name ASC
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no results, try without client_id filter to see if there are any courses at all
            if (empty($results)) {
                error_log("No courses found with client_id: " . $clientId . ". Trying without client filter...");
                
                $sqlFallback = "
                    SELECT DISTINCT 
                        c.id as course_id,
                        c.name as course_name,
                        c.description as course_description,
                        COUNT(DISTINCT latest_results.user_id) as failed_users_count,
                        COUNT(DISTINCT latest_results.assessment_id) as failed_assessments_count
                    FROM courses c
                    INNER JOIN (
                        SELECT 
                            ar.course_id,
                            ar.user_id,
                            ar.assessment_id,
                            ar.passed,
                            ROW_NUMBER() OVER (PARTITION BY ar.user_id, ar.assessment_id, ar.course_id ORDER BY ar.completed_at DESC) as rn
                        FROM assessment_results ar
                    ) latest_results ON c.id = latest_results.course_id AND latest_results.rn = 1 AND latest_results.passed = 0
                    INNER JOIN assessment_package ap ON latest_results.assessment_id = ap.id
                    GROUP BY c.id, c.name, c.description
                    ORDER BY failed_users_count DESC, c.name ASC
                ";
                
                $stmtFallback = $this->conn->prepare($sqlFallback);
                $stmtFallback->execute();
                $results = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("Fallback query returned " . count($results) . " courses");
            }
            
            return $results;
            
        } catch (Exception $e) {
            error_log("Error getting courses with failed assessments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get assessment contexts (prerequisite/module/post-requisite) for a course where users failed
     */
    public function getAssessmentContextsForCourse($courseId, $clientId) {
        try {
            $contexts = [];
            
            // Get prerequisite assessments
            $sql = "
                SELECT DISTINCT 
                    cp.id as context_id,
                    'prerequisite' as context_type,
                    cp.prerequisite_id as assessment_id,
                    ap.title as assessment_title,
                    ap.num_attempts as max_attempts,
                    COUNT(DISTINCT ar.user_id) as failed_users_count
                FROM course_prerequisites cp
                INNER JOIN assessment_package ap ON cp.prerequisite_id = ap.id
                INNER JOIN assessment_results ar ON ar.assessment_id = ap.id AND ar.course_id = ?
                WHERE cp.course_id = ? 
                AND cp.prerequisite_type = 'assessment'
                AND cp.deleted_at IS NULL
                AND ar.passed = 0
                AND ar.user_id IN (
                    SELECT DISTINCT aa.user_id 
                    FROM assessment_attempts aa
                    WHERE aa.course_id = ?
                    AND aa.assessment_id = ap.id
                    AND aa.status = 'completed'
                    GROUP BY aa.user_id, aa.assessment_id, aa.course_id
                    HAVING COUNT(*) >= ap.num_attempts
                )
                GROUP BY cp.id, cp.prerequisite_id, ap.title, ap.num_attempts
                ORDER BY failed_users_count DESC
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $courseId, $courseId]);
            $prerequisites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $contexts = array_merge($contexts, $prerequisites);
            
            // Get module assessments
            $sql = "
                SELECT DISTINCT 
                    cmc.id as context_id,
                    'module' as context_type,
                    cmc.content_id as assessment_id,
                    ap.title as assessment_title,
                    ap.num_attempts as max_attempts,
                    cm.title as module_title,
                    COUNT(DISTINCT ar.user_id) as failed_users_count
                FROM course_module_content cmc
                INNER JOIN course_modules cm ON cmc.module_id = cm.id
                INNER JOIN assessment_package ap ON cmc.content_id = ap.id
                INNER JOIN assessment_results ar ON ar.assessment_id = ap.id AND ar.course_id = ?
                WHERE cm.course_id = ? 
                AND cmc.content_type = 'assessment'
                AND cmc.deleted_at IS NULL
                AND cm.deleted_at IS NULL
                AND ar.passed = 0
                AND ar.user_id IN (
                    SELECT DISTINCT aa.user_id 
                    FROM assessment_attempts aa
                    WHERE aa.course_id = ?
                    AND aa.assessment_id = ap.id
                    AND aa.status = 'completed'
                    GROUP BY aa.user_id, aa.assessment_id, aa.course_id
                    HAVING COUNT(*) >= ap.num_attempts
                )
                GROUP BY cmc.id, cmc.content_id, ap.title, ap.num_attempts, cm.title
                ORDER BY failed_users_count DESC
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $courseId, $courseId]);
            $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $contexts = array_merge($contexts, $modules);
            
            // Get post-requisite assessments
            $sql = "
                SELECT DISTINCT 
                    cpr.id as context_id,
                    'post_requisite' as context_type,
                    cpr.content_id as assessment_id,
                    ap.title as assessment_title,
                    ap.num_attempts as max_attempts,
                    COUNT(DISTINCT ar.user_id) as failed_users_count
                FROM course_post_requisites cpr
                INNER JOIN assessment_package ap ON cpr.content_id = ap.id
                INNER JOIN assessment_results ar ON ar.assessment_id = ap.id AND ar.course_id = ?
                WHERE cpr.course_id = ? 
                AND cpr.content_type = 'assessment'
                AND ar.passed = 0
                AND ar.user_id IN (
                    SELECT DISTINCT aa.user_id 
                    FROM assessment_attempts aa
                    WHERE aa.course_id = ?
                    AND aa.assessment_id = ap.id
                    AND aa.status = 'completed'
                    GROUP BY aa.user_id, aa.assessment_id, aa.course_id
                    HAVING COUNT(*) >= ap.num_attempts
                )
                GROUP BY cpr.id, cpr.content_id, ap.title, ap.num_attempts
                ORDER BY failed_users_count DESC
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $courseId, $courseId]);
            $postRequisites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $contexts = array_merge($contexts, $postRequisites);
            
            return $contexts;
            
        } catch (Exception $e) {
            error_log("Error getting assessment contexts for course: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get users who have exceeded attempts and failed for a specific assessment context
     */
    public function getFailedUsersForAssessmentContext($courseId, $assessmentId, $contextType, $contextId, $clientId) {
        try {
            $sql = "
                SELECT DISTINCT 
                    u.id as user_id,
                    u.full_name,
                    u.email,
                    u.employee_id,
                    COUNT(aa.id) as attempts_used,
                    ap.num_attempts as max_attempts,
                    ar.percentage as last_score,
                    ar.completed_at as last_attempt_date
                FROM users u
                INNER JOIN assessment_attempts aa ON u.id = aa.user_id
                INNER JOIN assessment_package ap ON aa.assessment_id = ap.id
                INNER JOIN assessment_results ar ON ar.user_id = u.id AND ar.assessment_id = ap.id AND ar.course_id = ?
                INNER JOIN course_applicability ca ON ca.user_id = u.id AND ca.course_id = ?
                WHERE aa.course_id = ?
                AND aa.assessment_id = ?
                AND aa.status = 'completed'
                AND aa.is_deleted = 0
                AND u.client_id = ?
                AND ca.client_id = ?
                AND ar.passed = 0
                AND (
                    (? = 'prerequisite' AND aa.prerequisite_id = ?) OR
                    (? = 'module' AND aa.content_id = ?) OR
                    (? = 'post_requisite' AND aa.postrequisite_id = ?)
                )
                GROUP BY u.id, u.full_name, u.email, u.employee_id, ap.num_attempts, ar.percentage, ar.completed_at
                HAVING attempts_used >= ap.num_attempts
                ORDER BY attempts_used DESC, u.full_name ASC
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $courseId, $courseId, $courseId, $assessmentId, $clientId, $clientId,
                $contextId, $contextType, $contextId, $contextType, $contextId, $contextType
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting failed users for assessment context: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search users by name or email
     */
    public function searchFailedUsers($courseId, $assessmentId, $contextType, $contextId, $clientId, $searchTerm = '') {
        try {
            $searchCondition = '';
            $params = [$courseId, $courseId, $assessmentId, $courseId, $assessmentId, $courseId, $assessmentId, $clientId, $clientId, $contextType, $contextId, $contextType, $contextId, $contextType, $contextId];
            
            if (!empty($searchTerm)) {
                $searchCondition = " AND (up.full_name LIKE ? OR up.email LIKE ? OR up.profile_id LIKE ?)";
                $searchParam = '%' . $searchTerm . '%';
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
            }
            
            $sql = "
                SELECT DISTINCT 
                    up.id as user_id,
                    up.full_name,
                    up.email,
                    up.profile_id as employee_id,
                    all_attempts.attempts_used,
                    ap.num_attempts as max_attempts,
                    latest_result.percentage as last_score,
                    latest_result.completed_at as last_attempt_date
                FROM user_profiles up
                INNER JOIN assessment_attempts context_aa ON up.id = context_aa.user_id
                INNER JOIN assessment_package ap ON context_aa.assessment_id = ap.id
                LEFT JOIN course_applicability ca ON ca.user_id = up.id AND ca.course_id = ?
                INNER JOIN (
                    SELECT 
                        aa.user_id,
                        aa.assessment_id,
                        aa.course_id,
                        COUNT(aa.id) as attempts_used
                    FROM assessment_attempts aa
                    WHERE aa.course_id = ?
                    AND aa.assessment_id = ?
                    AND aa.status = 'completed'
                    AND aa.is_deleted = 0
                    GROUP BY aa.user_id, aa.assessment_id, aa.course_id
                ) all_attempts ON all_attempts.user_id = up.id AND all_attempts.assessment_id = context_aa.assessment_id AND all_attempts.course_id = context_aa.course_id
                INNER JOIN (
                    SELECT 
                        ar.user_id,
                        ar.assessment_id,
                        ar.course_id,
                        ar.percentage,
                        ar.completed_at,
                        ROW_NUMBER() OVER (PARTITION BY ar.user_id, ar.assessment_id, ar.course_id ORDER BY ar.completed_at DESC) as rn
                    FROM assessment_results ar
                    WHERE ar.course_id = ?
                    AND ar.assessment_id = ?
                    AND ar.passed = 0
                ) latest_result ON latest_result.user_id = up.id AND latest_result.assessment_id = context_aa.assessment_id AND latest_result.course_id = context_aa.course_id AND latest_result.rn = 1
                WHERE context_aa.course_id = ?
                AND context_aa.assessment_id = ?
                AND context_aa.status = 'completed'
                AND context_aa.is_deleted = 0
                AND up.client_id = ?
                AND (ca.client_id = ? OR ca.client_id IS NULL)
                AND up.is_deleted = 0
                AND (
                    (? = 'prerequisite' AND context_aa.prerequisite_id = ?) OR
                    (? = 'module' AND context_aa.content_id = ?) OR
                    (? = 'post_requisite' AND context_aa.postrequisite_id = ?)
                )
                AND all_attempts.attempts_used >= ap.num_attempts
                $searchCondition
                ORDER BY all_attempts.attempts_used DESC, up.full_name ASC
                LIMIT 50
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error searching failed users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Increase assessment attempts for selected users
     */
    public function increaseAssessmentAttempts($courseId, $assessmentId, $contextType, $contextId, $userIds, $attemptsToAdd, $reason, $increasedBy, $clientId) {
        try {
            $this->conn->beginTransaction();
            
            $results = [];
            
            // Get original max attempts for this assessment
            $stmt = $this->conn->prepare("
                SELECT num_attempts FROM assessment_package 
                WHERE id = ? AND client_id = ?
            ");
            $stmt->execute([$assessmentId, $clientId]);
            $assessment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$assessment) {
                throw new Exception("Assessment not found");
            }
            
            $originalMaxAttempts = $assessment['num_attempts'];
            
            foreach ($userIds as $userId) {
                // Check if user already has an override for this assessment context
                $stmt = $this->conn->prepare("
                    SELECT id, override_max_attempts, attempts_increased FROM user_assessment_overrides 
                    WHERE user_id = ? AND course_id = ? AND assessment_id = ? 
                    AND context_type = ? AND context_id = ? AND is_active = 1
                ");
                $stmt->execute([$userId, $courseId, $assessmentId, $contextType, $contextId]);
                $existingOverride = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingOverride) {
                    // Update existing override
                    $newMaxAttempts = $existingOverride['override_max_attempts'] + $attemptsToAdd;
                    $newAttemptsIncreased = $existingOverride['attempts_increased'] + $attemptsToAdd;
                    $stmt = $this->conn->prepare("
                        UPDATE user_assessment_overrides 
                        SET override_max_attempts = ?, attempts_increased = ?, reason = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$newMaxAttempts, $newAttemptsIncreased, $reason, $existingOverride['id']]);
                } else {
                    // Create new override
                    $newMaxAttempts = $originalMaxAttempts + $attemptsToAdd;
                    $stmt = $this->conn->prepare("
                        INSERT INTO user_assessment_overrides (
                            user_id, course_id, assessment_id, client_id, context_type, context_id,
                            original_max_attempts, override_max_attempts, attempts_increased, reason, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $userId, $courseId, $assessmentId, $clientId, $contextType, $contextId,
                        $originalMaxAttempts, $newMaxAttempts, $attemptsToAdd, $reason, $increasedBy
                    ]);
                }
                
                // Create history entry for this specific increase operation
                $stmt = $this->conn->prepare("
                    INSERT INTO assessment_attempt_history (
                        user_id, course_id, assessment_id, client_id, context_type, context_id,
                        attempts_increased, previous_max_attempts, new_max_attempts, reason, increased_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId, $courseId, $assessmentId, $clientId, $contextType, $contextId,
                    $attemptsToAdd, $originalMaxAttempts, $newMaxAttempts, $reason, $increasedBy
                ]);
                
                $results[] = [
                    'user_id' => $userId,
                    'previous_max' => $originalMaxAttempts,
                    'new_max' => $newMaxAttempts,
                    'increased_by' => $attemptsToAdd
                ];
            }
            
            $this->conn->commit();
            return [
                'success' => true,
                'results' => $results,
                'message' => 'Assessment attempts increased successfully for ' . count($userIds) . ' users'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error increasing assessment attempts: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to increase assessment attempts: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get effective max attempts for a user (considering overrides)
     */
    public function getEffectiveMaxAttempts($userId, $courseId, $assessmentId, $contextType, $contextId, $clientId) {
        try {
            // First get the original max attempts from assessment_package
            $stmt = $this->conn->prepare("
                SELECT num_attempts FROM assessment_package 
                WHERE id = ? AND client_id = ?
            ");
            $stmt->execute([$assessmentId, $clientId]);
            $assessment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$assessment) {
                return 0; // Assessment not found
            }
            
            $originalMaxAttempts = $assessment['num_attempts'];
            
            // Check if user has an override for this specific context
            $stmt = $this->conn->prepare("
                SELECT override_max_attempts FROM user_assessment_overrides 
                WHERE user_id = ? AND course_id = ? AND assessment_id = ? 
                AND context_type = ? AND context_id = ? AND is_active = 1
            ");
            $stmt->execute([$userId, $courseId, $assessmentId, $contextType, $contextId]);
            $override = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($override) {
                return $override['override_max_attempts'];
            }
            
            return $originalMaxAttempts;
            
        } catch (Exception $e) {
            error_log("Error getting effective max attempts: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get attempt increase history
     */
    public function getAttemptIncreaseHistory($clientId, $limit = 50) {
        try {
            $sql = "
                SELECT 
                    aah.id,
                    aah.course_id,
                    aah.assessment_id,
                    aah.user_id,
                    aah.client_id,
                    aah.context_type,
                    aah.context_id,
                    aah.previous_max_attempts,
                    aah.new_max_attempts,
                    aah.attempts_increased,
                    aah.reason,
                    aah.increased_by,
                    aah.increased_at,
                    c.name as course_name,
                    ap.title as assessment_title,
                    u.full_name as user_name,
                    u.email as user_email,
                    admin.full_name as increased_by_name
                FROM assessment_attempt_history aah
                INNER JOIN courses c ON aah.course_id = c.id
                INNER JOIN assessment_package ap ON aah.assessment_id = ap.id
                INNER JOIN user_profiles u ON aah.user_id = u.id
                INNER JOIN user_profiles admin ON aah.increased_by = admin.id
                WHERE aah.client_id = ?
                ORDER BY aah.increased_at DESC
                LIMIT " . (int)$limit;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting attempt increase history: " . $e->getMessage());
            return [];
        }
    }
}
?>
