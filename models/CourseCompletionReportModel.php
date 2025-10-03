<?php
// models/CourseCompletionReportModel.php
require_once __DIR__ . '/../config/Database.php';

class CourseCompletionReportModel {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->connect();
    }

    /**
     * Get all custom fields for a client that can be used for filtering
     */
    public function getCustomFieldsForFiltering($clientId) {
        try {
            $sql = "SELECT id, field_name, field_label, field_type, field_options 
                    FROM custom_fields 
                    WHERE client_id = ? AND is_deleted = 0 AND is_active = 1 
                    AND field_type = 'select'
                    ORDER BY field_order ASC, id ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientId]);
            $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode field_options JSON and format for frontend
            foreach ($fields as &$field) {
                if ($field['field_options']) {
                    $options = json_decode($field['field_options'], true);
                    if (is_array($options)) {
                        $field['field_options'] = array_filter(array_map('trim', $options));
                    } else {
                        $field['field_options'] = array_filter(array_map('trim', explode("\r\n", $options)));
                    }
                } else {
                    $field['field_options'] = [];
                }
            }
            
            return $fields;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get custom field values for a specific field
     */
    public function getCustomFieldValues($fieldId, $clientId) {
        try {
            $sql = "SELECT DISTINCT cfv.field_value 
                    FROM custom_field_values cfv
                    INNER JOIN user_profiles u ON cfv.user_id = u.id
                    WHERE cfv.custom_field_id = ? AND u.client_id = ? AND cfv.field_value IS NOT NULL AND cfv.field_value != ''
                    ORDER BY cfv.field_value ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$fieldId, $clientId]);
            $values = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return $values;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get filter options for the report
     */
    public function getFilterOptions($clientId) {
        try {
            // Get users for this client - same approach as UserProgressReportModel
            $sql = "SELECT id, full_name, email FROM user_profiles WHERE client_id = ? AND is_deleted = 0 ORDER BY full_name";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientId]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get courses for this client - same approach as UserProgressReportModel
            $sql = "SELECT id, name FROM courses WHERE client_id = ? AND is_deleted = 0 ORDER BY name";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientId]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            
            return [
                'users' => $users,
                'courses' => $courses
            ];
        } catch (Exception $e) {
            return [
                'users' => [],
                'courses' => []
            ];
        }
    }

    /**
     * Get course completion data with filters - updated to work with existing tables
     * Uses individual progress tables instead of non-existent course_completion table
     */
    public function getCourseCompletionData($filters = [], $page = 1, $perPage = 20) {
        try {
            $clientId = $filters['client_id'] ?? (isset($_SESSION['user']['client_id']) ? $_SESSION['user']['client_id'] : null);
            
            $whereConditions = ["c.client_id = ?", "c.is_deleted = 0"];
            $params = [$clientId];

            // Course filter
            if (!empty($filters['course_ids']) && is_array($filters['course_ids'])) {
                $placeholders = str_repeat('?,', count($filters['course_ids']) - 1) . '?';
                $whereConditions[] = "c.id IN ($placeholders)";
                $params = array_merge($params, $filters['course_ids']);
            }

            // Date filter - show courses created within the date range
            // This is more appropriate for a course completion report
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $whereConditions[] = "c.created_at >= ? AND c.created_at <= ?";
                $params[] = $filters['start_date'];
                $params[] = $filters['end_date'];
            }

            // Custom field value filter - only show courses that have enrollments from users with selected custom field values
            if (!empty($filters['custom_field_id']) && !empty($filters['custom_field_value']) && is_array($filters['custom_field_value'])) {
                $fieldValuePlaceholders = str_repeat('?,', count($filters['custom_field_value']) - 1) . '?';
                $whereConditions[] = "EXISTS (
                    SELECT 1 FROM course_enrollments ce
                    INNER JOIN user_profiles u ON ce.user_id = u.id
                    INNER JOIN custom_field_values cfv ON u.id = cfv.user_id
                    WHERE ce.course_id = c.id 
                    AND cfv.custom_field_id = ?
                    AND cfv.field_value IN ($fieldValuePlaceholders)
                    AND cfv.field_value IS NOT NULL 
                    AND cfv.field_value != ''
                )";
                $params[] = (int)$filters['custom_field_id'];
                $params = array_merge($params, $filters['custom_field_value']);
                
            }

            // Status filter - will be handled in post-processing after data retrieval

            $whereClause = implode(' AND ', $whereConditions);

            // Count total records
            $countSql = "SELECT COUNT(DISTINCT c.id) as total 
                        FROM courses c
                        WHERE $whereClause";
            
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($params);
            $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Calculate pagination
            $offset = ($page - 1) * $perPage;
            $totalPages = ceil($totalRecords / $perPage);

            // Get basic course data first
            $sql = "SELECT 
                        c.id as course_id,
                        c.name as course_name,
                        c.description as course_description
                    FROM courses c
                    WHERE $whereClause
                    ORDER BY c.name ASC
                    LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Now get completion data for each course using individual progress tables
            $data = [];
            foreach ($courses as $course) {
                $courseId = $course['course_id'];
                
                // Get applicable users (users who should have access to this course)
                $applicableUsersSql = "SELECT COUNT(DISTINCT u.id) as count
                    FROM user_profiles u
                    WHERE u.client_id = ? AND u.is_deleted = 0
                    AND (
                        EXISTS (SELECT 1 FROM course_applicability ca WHERE ca.course_id = ? AND ca.applicability_type = 'all' AND ca.client_id = ?)
                        OR EXISTS (SELECT 1 FROM course_applicability ca WHERE ca.course_id = ? AND ca.applicability_type = 'user' AND ca.user_id = u.id AND ca.client_id = ?)
                        OR EXISTS (SELECT 1 FROM course_enrollments ce WHERE ce.course_id = ? AND ce.user_id = u.id AND ce.client_id = ? AND ce.status = 'approved' AND ce.deleted_at IS NULL)
                    )";
                
                $stmt = $this->conn->prepare($applicableUsersSql);
                $stmt->execute([$clientId, $courseId, $clientId, $courseId, $clientId, $courseId, $clientId]);
                $applicableUsers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                // Get enrolled users
                $enrolledUsersSql = "SELECT COUNT(DISTINCT ce.user_id) as count
                    FROM course_enrollments ce
                    WHERE ce.course_id = ? AND ce.client_id = ? AND ce.status = 'approved' AND ce.deleted_at IS NULL";
                
                $stmt = $this->conn->prepare($enrolledUsersSql);
                $stmt->execute([$courseId, $clientId]);
                $enrolledUsers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                // Calculate completion data from individual progress tables
                $completionData = $this->calculateCourseCompletionFromProgressTables($courseId, $clientId, $filters);
                
                // Calculate not started users
                // For courses assigned via applicability, not_started = applicable_users - users_with_progress
                // For courses with enrollments, not_started = enrolled_users - users_with_progress
                $usersWithProgress = $completionData['completed_count'] + $completionData['in_progress_count'];
                
                if ($enrolledUsers > 0) {
                    // Course uses enrollment system
                    $notStartedUsers = max(0, $enrolledUsers - $usersWithProgress);
                } else {
                    // Course uses applicability system
                    $notStartedUsers = max(0, $applicableUsers - $usersWithProgress);
                }
                
                // Calculate completion rate
                if ($enrolledUsers > 0) {
                    // Course uses enrollment system
                    $completionRate = round(($completionData['completed_count'] / $enrolledUsers) * 100, 1);
                } else {
                    // Course uses applicability system
                    $completionRate = $applicableUsers > 0 ? round(($completionData['completed_count'] / $applicableUsers) * 100, 1) : 0;
                }
                
                $data[] = [
                    'course_id' => $courseId,
                    'course_name' => $course['course_name'],
                    'course_description' => $course['course_description'],
                    'total_applicable_users' => $applicableUsers,
                    'total_enrollments' => $enrolledUsers,
                    'completed_count' => $completionData['completed_count'],
                    'in_progress_count' => $completionData['in_progress_count'],
                    'not_started_count' => $notStartedUsers,
                    'avg_completion' => $completionData['avg_completion'],
                    'completion_rate' => $completionRate,
                    'last_activity' => $completionData['last_activity']
                ];
            }
            
            

            // Apply status filtering if specified
            if (!empty($filters['status']) && is_array($filters['status'])) {
                $filteredData = [];
                foreach ($data as $course) {
                    $shouldInclude = false;
                    
                    foreach ($filters['status'] as $status) {
                        switch ($status) {
                            case 'not_started':
                                if ($course['not_started_count'] > 0) {
                                    $shouldInclude = true;
                                }
                                break;
                            case 'in_progress':
                                if ($course['in_progress_count'] > 0) {
                                    $shouldInclude = true;
                                }
                                break;
                            case 'completed':
                                if ($course['completed_count'] > 0) {
                                    $shouldInclude = true;
                                }
                                break;
                        }
                    }
                    
                    if ($shouldInclude) {
                        $filteredData[] = $course;
                    }
                }
                $data = $filteredData;
            }

            return [
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => count($data),
                    'total_pages' => ceil(count($data) / $perPage),
                    'has_next' => $page < ceil(count($data) / $perPage),
                    'has_prev' => $page > 1
                ]
            ];

        } catch (Exception $e) {
            return [
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 20,
                    'total' => 0,
                    'total_pages' => 0,
                    'has_next' => false,
                    'has_prev' => false
                ]
            ];
        }
    }

    /**
     * Calculate course completion data from all progress tables and completion tracking tables
     */
    private function calculateCourseCompletionFromProgressTables($courseId, $clientId, $filters = []) {
        try {
            // Build date filter conditions
            $dateFilter = '';
            $dateParams = [];
            
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $dateFilter = " AND (
                    (started_at >= ? AND started_at <= ?) OR 
                    (completed_at >= ? AND completed_at <= ?) OR
                    (created_at >= ? AND created_at <= ?) OR
                    (updated_at >= ? AND updated_at <= ?)
                )";
                $dateParams = [
                    $filters['start_date'], $filters['end_date'],
                    $filters['start_date'], $filters['end_date'],
                    $filters['start_date'], $filters['end_date'],
                    $filters['start_date'], $filters['end_date']
                ];
            }
            
            // Build custom field value filter for progress users
            $customFieldFilter = '';
            $customFieldParams = [];
            if (!empty($filters['custom_field_id']) && !empty($filters['custom_field_value']) && is_array($filters['custom_field_value'])) {
                $fieldValuePlaceholders = str_repeat('?,', count($filters['custom_field_value']) - 1) . '?';
                $customFieldFilter = " AND user_id IN (
                    SELECT u.id FROM user_profiles u
                    INNER JOIN custom_field_values cfv ON u.id = cfv.user_id
                    WHERE u.client_id = ?
                    AND cfv.custom_field_id = ?
                    AND cfv.field_value IN ($fieldValuePlaceholders)
                    AND cfv.field_value IS NOT NULL 
                    AND cfv.field_value != ''
                )";
                $customFieldParams = [$clientId, (int)$filters['custom_field_id']];
                $customFieldParams = array_merge($customFieldParams, $filters['custom_field_value']);
            }

            // Get all users who have any progress or completion activity in this course
            $progressUsersSql = "SELECT DISTINCT user_id FROM (
                SELECT user_id FROM video_progress WHERE course_id = ? AND client_id = ?" . $dateFilter . $customFieldFilter . "
                UNION
                SELECT user_id FROM audio_progress WHERE course_id = ? AND client_id = ?" . $dateFilter . $customFieldFilter . "
                UNION
                SELECT user_id FROM document_progress WHERE course_id = ? AND client_id = ?" . $dateFilter . $customFieldFilter . "
                UNION
                SELECT user_id FROM image_progress WHERE course_id = ? AND client_id = ?" . $dateFilter . $customFieldFilter . "
                UNION
                SELECT user_id FROM scorm_progress WHERE course_id = ? AND client_id = ?" . $dateFilter . $customFieldFilter . "
                UNION
                SELECT user_id FROM external_progress WHERE course_id = ? AND client_id = ?" . $dateFilter . $customFieldFilter . "
                UNION
                SELECT user_id FROM interactive_progress WHERE course_id = ? AND client_id = ?" . $dateFilter . $customFieldFilter . "
                UNION
                SELECT user_id FROM course_feedback_responses WHERE course_id = ? AND client_id = ?" . $dateFilter . $customFieldFilter . "
                UNION
                SELECT user_id FROM course_survey_responses WHERE course_id = ? AND client_id = ?" . $dateFilter . $customFieldFilter . "
                UNION
                SELECT user_id FROM assignment_submissions WHERE course_id = ? AND client_id = ?" . $dateFilter . $customFieldFilter . "
                UNION
                SELECT user_id FROM assessment_results WHERE course_id = ? AND client_id = ?" . $dateFilter . $customFieldFilter . "
            ) as all_progress";
            
            $stmt = $this->conn->prepare($progressUsersSql);
            
            // Build parameters array
            $params = [];
            for ($i = 0; $i < 11; $i++) { // 11 tables
                $params[] = $courseId;
                $params[] = $clientId;
                if (!empty($dateParams)) {
                    $params = array_merge($params, $dateParams);
                }
                if (!empty($customFieldParams)) {
                    $params = array_merge($params, $customFieldParams);
                }
            }
            
            $stmt->execute($params);
            $progressUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $completedCount = 0;
            $inProgressCount = 0;
            $totalCompletion = 0;
            $lastActivity = null;
            
            // For each user with progress, calculate their completion percentage
            foreach ($progressUsers as $userId) {
                $userCompletion = $this->calculateUserCourseCompletion($userId, $courseId, $clientId, $filters);
                
                if ($userCompletion['completion_percentage'] >= 100) {
                    $completedCount++;
                } elseif ($userCompletion['completion_percentage'] > 0) {
                    $inProgressCount++;
                }
                
                $totalCompletion += $userCompletion['completion_percentage'];
                
                if ($userCompletion['last_activity'] && (!$lastActivity || $userCompletion['last_activity'] > $lastActivity)) {
                    $lastActivity = $userCompletion['last_activity'];
                }
            }
            
            $avgCompletion = count($progressUsers) > 0 ? round($totalCompletion / count($progressUsers), 1) : 0;
            
            return [
                'completed_count' => $completedCount,
                'in_progress_count' => $inProgressCount,
                'avg_completion' => $avgCompletion,
                'last_activity' => $lastActivity
            ];
            
        } catch (Exception $e) {
            return [
                'completed_count' => 0,
                'in_progress_count' => 0,
                'avg_completion' => 0,
                'last_activity' => null
            ];
        }
    }
    
    /**
     * Calculate user's completion percentage for a course including all completion tracking
     */
    private function calculateUserCourseCompletion($userId, $courseId, $clientId, $filters = []) {
        try {
            // Get comprehensive completion data from all tables
            $completionSql = "SELECT 
                -- Progress tables
                MAX(COALESCE(vp.watched_percentage, 0)) as video_progress,
                MAX(COALESCE(ap.listened_percentage, 0)) as audio_progress,
                MAX(COALESCE(dp.viewed_percentage, 0)) as document_progress,
                MAX(COALESCE(intp.completion_percentage, 0)) as interactive_progress,
                
                -- Completion tracking tables
                CASE WHEN COUNT(DISTINCT cfr.id) > 0 THEN 100 ELSE 0 END as feedback_completion,
                CASE WHEN COUNT(DISTINCT csr.id) > 0 THEN 100 ELSE 0 END as survey_completion,
                CASE WHEN COUNT(DISTINCT CASE WHEN asub.submission_status IN ('submitted', 'graded', 'returned', 'resubmitted') THEN asub.id END) > 0 THEN 100 ELSE 0 END as assignment_completion,
                CASE WHEN COUNT(DISTINCT ar.id) > 0 THEN 100 ELSE 0 END as assessment_completion,
                
                -- Last activity timestamps
                GREATEST(
                    COALESCE(vp.last_watched_at, '1900-01-01'),
                    COALESCE(ap.last_listened_at, '1900-01-01'),
                    COALESCE(dp.last_viewed_at, '1900-01-01'),
                    COALESCE(intp.last_interaction_at, '1900-01-01'),
                    COALESCE(cfr.completed_at, '1900-01-01'),
                    COALESCE(csr.completed_at, '1900-01-01'),
                    COALESCE(asub.completed_at, '1900-01-01'),
                    COALESCE(ar.completed_at, '1900-01-01')
                ) as last_activity
                
                FROM (
                    SELECT 1 as dummy
                ) dummy_table
                
                -- Progress tables
                LEFT JOIN video_progress vp ON vp.user_id = ? AND vp.course_id = ? AND vp.client_id = ?" . 
                (!empty($filters['start_date']) && !empty($filters['end_date']) ? 
                    " AND (vp.started_at >= ? AND vp.started_at <= ? OR vp.completed_at >= ? AND vp.completed_at <= ?)" : "") . "
                LEFT JOIN audio_progress ap ON ap.user_id = ? AND ap.course_id = ? AND ap.client_id = ?" . 
                (!empty($filters['start_date']) && !empty($filters['end_date']) ? 
                    " AND (ap.started_at >= ? AND ap.started_at <= ? OR ap.completed_at >= ? AND ap.completed_at <= ?)" : "") . "
                LEFT JOIN document_progress dp ON dp.user_id = ? AND dp.course_id = ? AND dp.client_id = ?" . 
                (!empty($filters['start_date']) && !empty($filters['end_date']) ? 
                    " AND (dp.started_at >= ? AND dp.started_at <= ? OR dp.completed_at >= ? AND dp.completed_at <= ?)" : "") . "
                LEFT JOIN interactive_progress intp ON intp.user_id = ? AND intp.course_id = ? AND intp.client_id = ?" . 
                (!empty($filters['start_date']) && !empty($filters['end_date']) ? 
                    " AND (intp.started_at >= ? AND intp.started_at <= ? OR intp.completed_at >= ? AND intp.completed_at <= ?)" : "") . "
                
                -- Completion tracking tables
                LEFT JOIN course_feedback_responses cfr ON cfr.user_id = ? AND cfr.course_id = ? AND cfr.client_id = ? AND cfr.completed_at IS NOT NULL" . 
                (!empty($filters['start_date']) && !empty($filters['end_date']) ? 
                    " AND (cfr.started_at >= ? AND cfr.started_at <= ? OR cfr.completed_at >= ? AND cfr.completed_at <= ?)" : "") . "
                LEFT JOIN course_survey_responses csr ON csr.user_id = ? AND csr.course_id = ? AND csr.client_id = ? AND csr.completed_at IS NOT NULL" . 
                (!empty($filters['start_date']) && !empty($filters['end_date']) ? 
                    " AND (csr.started_at >= ? AND csr.started_at <= ? OR csr.completed_at >= ? AND csr.completed_at <= ?)" : "") . "
                LEFT JOIN assignment_submissions asub ON asub.user_id = ? AND asub.course_id = ? AND asub.client_id = ?" . 
                (!empty($filters['start_date']) && !empty($filters['end_date']) ? 
                    " AND (asub.started_at >= ? AND asub.started_at <= ? OR asub.completed_at >= ? AND asub.completed_at <= ?)" : "") . "
                LEFT JOIN assessment_results ar ON ar.user_id = ? AND ar.course_id = ? AND ar.client_id = ? AND ar.completed_at IS NOT NULL" . 
                (!empty($filters['start_date']) && !empty($filters['end_date']) ? 
                    " AND (ar.started_at >= ? AND ar.started_at <= ? OR ar.completed_at >= ? AND ar.completed_at <= ?)" : "") . "";
            
            $stmt = $this->conn->prepare($completionSql);
            
            // Build parameters array
            $params = [];
            
            // Progress tables (4 tables)
            for ($i = 0; $i < 4; $i++) {
                $params[] = $userId;
                $params[] = $courseId;
                $params[] = $clientId;
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $params[] = $filters['start_date'];
                    $params[] = $filters['end_date'];
                    $params[] = $filters['start_date'];
                    $params[] = $filters['end_date'];
                }
            }
            
            // Completion tracking tables (4 tables)
            for ($i = 0; $i < 4; $i++) {
                $params[] = $userId;
                $params[] = $courseId;
                $params[] = $clientId;
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $params[] = $filters['start_date'];
                    $params[] = $filters['end_date'];
                    $params[] = $filters['start_date'];
                    $params[] = $filters['end_date'];
                }
            }
            
            $stmt->execute($params);
            
            $completion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($completion) {
                // Calculate overall completion percentage
                $progressValues = [
                    $completion['video_progress'],
                    $completion['audio_progress'],
                    $completion['document_progress'],
                    $completion['interactive_progress'],
                    $completion['feedback_completion'],
                    $completion['survey_completion'],
                    $completion['assignment_completion'],
                    $completion['assessment_completion']
                ];
                
                // Calculate weighted average or use the maximum completion
                $totalCompletion = array_sum($progressValues);
                $avgCompletion = $totalCompletion / count($progressValues);
                
                // Alternative: Use maximum completion across all activities
                $maxCompletion = max($progressValues);
                
                // Use the higher of average or maximum (more generous approach)
                $finalCompletion = max($avgCompletion, $maxCompletion);
                
                return [
                    'completion_percentage' => round($finalCompletion, 1),
                    'last_activity' => $completion['last_activity'] !== '1900-01-01' ? $completion['last_activity'] : null
                ];
            }
            
            return ['completion_percentage' => 0, 'last_activity' => null];
            
        } catch (Exception $e) {
            return ['completion_percentage' => 0, 'last_activity' => null];
        }
    }
    

    /**
     * Get summary statistics for the report - updated to work with existing tables and respect filters
     */
    public function getSummaryStats($filters = []) {
        try {
            $clientId = $filters['client_id'] ?? (isset($_SESSION['user']['client_id']) ? $_SESSION['user']['client_id'] : null);
            
            // Build course filter conditions (same as in getCourseCompletionData)
            $whereConditions = ["c.client_id = ?", "c.is_deleted = 0"];
            $params = [$clientId];

            // Course filter
            if (!empty($filters['course_ids']) && is_array($filters['course_ids'])) {
                $placeholders = str_repeat('?,', count($filters['course_ids']) - 1) . '?';
                $whereConditions[] = "c.id IN ($placeholders)";
                $params = array_merge($params, $filters['course_ids']);
            }

            // Date filter - show courses created within the date range
            // This is more appropriate for a course completion report
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $whereConditions[] = "c.created_at >= ? AND c.created_at <= ?";
                $params[] = $filters['start_date'];
                $params[] = $filters['end_date'];
            }

            // Custom field value filter - only show courses that have enrollments from users with selected custom field values
            if (!empty($filters['custom_field_id']) && !empty($filters['custom_field_value']) && is_array($filters['custom_field_value'])) {
                $fieldValuePlaceholders = str_repeat('?,', count($filters['custom_field_value']) - 1) . '?';
                $whereConditions[] = "EXISTS (
                    SELECT 1 FROM course_enrollments ce
                    INNER JOIN user_profiles u ON ce.user_id = u.id
                    INNER JOIN custom_field_values cfv ON u.id = cfv.user_id
                    WHERE ce.course_id = c.id 
                    AND cfv.custom_field_id = ?
                    AND cfv.field_value IN ($fieldValuePlaceholders)
                    AND cfv.field_value IS NOT NULL 
                    AND cfv.field_value != ''
                )";
                $params[] = (int)$filters['custom_field_id'];
                $params = array_merge($params, $filters['custom_field_value']);
            }

            // Status filter - will be handled in post-processing after data retrieval

            $whereClause = implode(' AND ', $whereConditions);
            
            // Get filtered courses count
            $coursesSql = "SELECT COUNT(*) as total FROM courses c WHERE $whereClause";
            $stmt = $this->conn->prepare($coursesSql);
            $stmt->execute($params);
            $totalCourses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get total enrollments for filtered courses
            $enrollmentsSql = "SELECT COUNT(DISTINCT ce.user_id) as total 
                FROM course_enrollments ce 
                INNER JOIN courses c ON ce.course_id = c.id
                WHERE ce.client_id = ? AND ce.status = 'approved' AND ce.deleted_at IS NULL
                AND c.is_deleted = 0";
            
            $enrollmentParams = [$clientId];
            
            // Apply course filter to enrollments
            if (!empty($filters['course_ids']) && is_array($filters['course_ids'])) {
                $placeholders = str_repeat('?,', count($filters['course_ids']) - 1) . '?';
                $enrollmentsSql .= " AND ce.course_id IN ($placeholders)";
                $enrollmentParams = array_merge($enrollmentParams, $filters['course_ids']);
            }
            
            // Apply custom field value filter to enrollments
            if (!empty($filters['custom_field_id']) && !empty($filters['custom_field_value']) && is_array($filters['custom_field_value'])) {
                $fieldValuePlaceholders = str_repeat('?,', count($filters['custom_field_value']) - 1) . '?';
                $enrollmentsSql .= " AND EXISTS (
                    SELECT 1 FROM user_profiles u
                    INNER JOIN custom_field_values cfv ON u.id = cfv.user_id
                    WHERE u.id = ce.user_id 
                    AND cfv.custom_field_id = ?
                    AND cfv.field_value IN ($fieldValuePlaceholders)
                    AND cfv.field_value IS NOT NULL 
                    AND cfv.field_value != ''
                )";
                $enrollmentParams[] = (int)$filters['custom_field_id'];
                $enrollmentParams = array_merge($enrollmentParams, $filters['custom_field_value']);
            }
            
            $stmt = $this->conn->prepare($enrollmentsSql);
            $stmt->execute($enrollmentParams);
            $totalEnrollments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Calculate average completion from progress tables for filtered courses only
            $avgCompletionPercentage = 0;
            $coursesWithCompletions = 0;
            $overallCompletionRate = 0;
            
            if ($totalCourses > 0) {
                // Get filtered courses for this client
                $coursesSql = "SELECT id FROM courses c WHERE $whereClause";
                $stmt = $this->conn->prepare($coursesSql);
                $stmt->execute($params);
                $courses = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $totalCompletion = 0;
                $coursesWithAnyCompletion = 0;
                $totalCompletedUsers = 0;
                
                foreach ($courses as $courseId) {
                    $completionData = $this->calculateCourseCompletionFromProgressTables($courseId, $clientId, $filters);
                    $totalCompletion += $completionData['avg_completion'];
                    
                    if ($completionData['completed_count'] > 0) {
                        $coursesWithCompletions++;
                    }
                    
                    if ($completionData['avg_completion'] > 0) {
                        $coursesWithAnyCompletion++;
                    }
                    
                    $totalCompletedUsers += $completionData['completed_count'];
                }
                
                $avgCompletionPercentage = count($courses) > 0 ? round($totalCompletion / count($courses), 1) : 0;
                
                // Calculate overall completion rate for filtered courses
                if ($totalEnrollments > 0) {
                    $overallCompletionRate = round(($totalCompletedUsers / $totalEnrollments) * 100, 1);
                }
            }

            

            // Apply status filtering if specified
            if (!empty($filters['status']) && is_array($filters['status'])) {
                // Get filtered course data to recalculate summary stats
                $filteredData = $this->getCourseCompletionData($filters);
                $filteredCourses = $filteredData['data'] ?? [];
                
                // Recalculate summary stats based on filtered data
                $totalCourses = count($filteredCourses);
                $totalEnrollments = 0;
                $totalCompletion = 0;
                $coursesWithCompletions = 0;
                $totalCompletedUsers = 0;
                
                foreach ($filteredCourses as $course) {
                    $totalEnrollments += $course['total_enrollments'];
                    $totalCompletion += $course['completion_rate'];
                    
                    if ($course['completed_count'] > 0) {
                        $coursesWithCompletions++;
                    }
                    
                    $totalCompletedUsers += $course['completed_count'];
                }
                
                $avgCompletionPercentage = $totalCourses > 0 ? round($totalCompletion / $totalCourses, 1) : 0;
                $overallCompletionRate = $totalEnrollments > 0 ? round(($totalCompletedUsers / $totalEnrollments) * 100, 1) : 0;
            }

            return [
                'total_courses' => $totalCourses,
                'total_enrollments' => $totalEnrollments,
                'avg_completion_percentage' => $avgCompletionPercentage,
                'courses_with_completions' => $coursesWithCompletions,
                'overall_completion_rate' => $overallCompletionRate
            ];

        } catch (Exception $e) {
            return [
                'total_courses' => 0,
                'total_enrollments' => 0,
                'avg_completion_percentage' => 0,
                'courses_with_completions' => 0,
                'overall_completion_rate' => 0
            ];
        }
    }
}
?>