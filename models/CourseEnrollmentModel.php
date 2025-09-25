<?php
require_once 'config/Database.php';

class CourseEnrollmentModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Enroll user in a course
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return array
     */
    public function enrollUserInCourse($userId, $courseId, $clientId) {
        try {
            // Check if user is already enrolled
            $stmt = $this->conn->prepare("
                SELECT id, status 
                FROM course_enrollments 
                WHERE user_id = ? AND course_id = ? AND client_id = ? AND deleted_at IS NULL
            ");
            $stmt->execute([$userId, $courseId, $clientId]);
            $existingEnrollment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingEnrollment) {
                if ($existingEnrollment['status'] === 'pending') {
                    return ['success' => false, 'message' => 'You have already enrolled in this course. Your enrollment is pending approval.'];
                } elseif ($existingEnrollment['status'] === 'approved') {
                    return ['success' => false, 'message' => 'You are already enrolled in this course.'];
                } elseif ($existingEnrollment['status'] === 'rejected') {
                    return ['success' => false, 'message' => 'Your enrollment in this course was rejected.'];
                }
            }

            // Check if course exists and is searchable
            $stmt = $this->conn->prepare("
                SELECT id, name, show_in_search 
                FROM courses 
                WHERE id = ? AND client_id = ? AND is_deleted = 0
            ");
            $stmt->execute([$courseId, $clientId]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$course) {
                return ['success' => false, 'message' => 'Course not found.'];
            }

            if ($course['show_in_search'] !== 'yes') {
                return ['success' => false, 'message' => 'This course is not available for enrollment.'];
            }

            // Check if user already has course in course_applicability
            $stmt = $this->conn->prepare("
                SELECT id 
                FROM course_applicability 
                WHERE course_id = ? AND user_id = ? AND client_id = ?
            ");
            $stmt->execute([$courseId, $userId, $clientId]);
            $applicability = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($applicability) {
                return ['success' => false, 'message' => 'You already have access to this course.'];
            }

            // Create enrollment record
            $stmt = $this->conn->prepare("
                INSERT INTO course_enrollments (user_id, course_id, client_id, status, enrollment_date) 
                VALUES (?, ?, ?, 'pending', NOW())
            ");
            $result = $stmt->execute([$userId, $courseId, $clientId]);

            if ($result) {
                return [
                    'success' => true, 
                    'message' => 'Successfully enrolled in ' . $course['name'] . '. Your enrollment is pending approval.',
                    'enrollment_id' => $this->conn->lastInsertId()
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to enroll in course.'];
            }

        } catch (Exception $e) {
            error_log("CourseEnrollmentModel: Error enrolling user: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while enrolling in the course.'];
        }
    }

    /**
     * Get user's enrollments
     * @param int $userId
     * @param int $clientId
     * @param string $status
     * @return array
     */
    public function getUserEnrollments($userId, $clientId, $status = null) {
        try {
            $sql = "
                SELECT 
                    ce.id, ce.course_id, ce.status, ce.enrollment_date, ce.approved_at, ce.rejected_at,
                    c.name as course_name, c.thumbnail_image, c.difficulty_level,
                    cat.name as category_name, subcat.name as subcategory_name,
                    approver.first_name as approved_by_name, approver.last_name as approved_by_last_name,
                    rejector.first_name as rejected_by_name, rejector.last_name as rejected_by_last_name
                FROM course_enrollments ce
                INNER JOIN courses c ON ce.course_id = c.id
                LEFT JOIN course_categories cat ON c.category_id = cat.id
                LEFT JOIN course_subcategories subcat ON c.subcategory_id = subcat.id
                LEFT JOIN users approver ON ce.approved_by = approver.id
                LEFT JOIN users rejector ON ce.rejected_by = rejector.id
                WHERE ce.user_id = ? AND ce.client_id = ? AND ce.deleted_at IS NULL
            ";

            $params = [$userId, $clientId];

            if ($status) {
                $sql .= " AND ce.status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY ce.enrollment_date DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("CourseEnrollmentModel: Error getting user enrollments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if user is enrolled in course
     * @param int $userId
     * @param int $courseId
     * @param int $clientId
     * @return bool
     */
    public function isUserEnrolled($userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM course_enrollments 
                WHERE user_id = ? AND course_id = ? AND client_id = ? 
                AND deleted_at IS NULL AND status IN ('pending', 'approved')
            ");
            $stmt->execute([$userId, $courseId, $clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;

        } catch (Exception $e) {
            error_log("CourseEnrollmentModel: Error checking enrollment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get pending enrollment requests for admin approval
     * @param int $clientId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getPendingEnrollments($clientId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $sql = "
                SELECT 
                    ce.id, ce.user_id, ce.course_id, ce.enrollment_date, ce.status,
                    u.username,
                    c.name as course_name, c.thumbnail_image, c.difficulty_level,
                    cat.name as category_name, subcat.name as subcategory_name
                FROM course_enrollments ce
                INNER JOIN users u ON ce.user_id = u.id
                INNER JOIN courses c ON ce.course_id = c.id
                LEFT JOIN course_categories cat ON c.category_id = cat.id
                LEFT JOIN course_subcategories subcat ON c.subcategory_id = subcat.id
                WHERE ce.client_id = ? AND ce.status = 'pending' AND ce.deleted_at IS NULL
                ORDER BY ce.enrollment_date ASC
                LIMIT " . intval($perPage) . " OFFSET " . intval($offset) . "
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("CourseEnrollmentModel: Error getting pending enrollments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get count of pending enrollment requests
     * @param int $clientId
     * @return int
     */
    public function getPendingEnrollmentsCount($clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total
                FROM course_enrollments 
                WHERE client_id = ? AND status = 'pending' AND deleted_at IS NULL
            ");
            $stmt->execute([$clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] ?? 0;

        } catch (Exception $e) {
            error_log("CourseEnrollmentModel: Error getting pending enrollments count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get enrollments by status with pagination
     * @param int $clientId
     * @param string $status
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getEnrollmentsByStatus($clientId, $status, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $sql = "
                SELECT 
                    ce.id, ce.user_id, ce.course_id, ce.enrollment_date, ce.status,
                    ce.approved_at, ce.rejected_at, ce.rejection_reason,
                    u.username,
                    c.name as course_name, c.thumbnail_image, c.difficulty_level,
                    cat.name as category_name, subcat.name as subcategory_name
                FROM course_enrollments ce
                INNER JOIN users u ON ce.user_id = u.id
                INNER JOIN courses c ON ce.course_id = c.id
                LEFT JOIN course_categories cat ON c.category_id = cat.id
                LEFT JOIN course_subcategories subcat ON c.subcategory_id = subcat.id
                WHERE ce.client_id = ? AND ce.deleted_at IS NULL
            ";
            
            $params = [$clientId];
            
            // Add status filter if not 'all'
            if ($status !== 'all') {
                $sql .= " AND ce.status = ?";
                $params[] = $status;
            }
            
            $sql .= " ORDER BY ce.enrollment_date DESC";
            $sql .= " LIMIT " . intval($perPage) . " OFFSET " . intval($offset);
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("CourseEnrollmentModel: Error getting enrollments by status: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get count of enrollments by status
     * @param int $clientId
     * @param string $status
     * @return int
     */
    public function getEnrollmentsCountByStatus($clientId, $status) {
        try {
            $sql = "
                SELECT COUNT(*) as total
                FROM course_enrollments 
                WHERE client_id = ? AND deleted_at IS NULL
            ";
            
            $params = [$clientId];
            
            // Add status filter if not 'all'
            if ($status !== 'all') {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] ?? 0;

        } catch (Exception $e) {
            error_log("CourseEnrollmentModel: Error getting enrollments count by status: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update enrollment status (approve/reject)
     * @param int $enrollmentId
     * @param string $status
     * @param int $adminId
     * @param string $rejectionReason
     * @return array
     */
    public function updateEnrollmentStatus($enrollmentId, $status, $adminId, $rejectionReason = null) {
        try {
            $this->conn->beginTransaction();

            // Update enrollment status
            $sql = "
                UPDATE course_enrollments 
                SET status = ?, 
                    approved_by = ?, 
                    approved_at = ?,
                    rejected_by = ?, 
                    rejected_at = ?,
                    rejection_reason = ?,
                    updated_at = NOW()
                WHERE id = ? AND deleted_at IS NULL
            ";

            $approvedBy = $status === 'approved' ? $adminId : null;
            $approvedAt = $status === 'approved' ? date('Y-m-d H:i:s') : null;
            $rejectedBy = $status === 'rejected' ? $adminId : null;
            $rejectedAt = $status === 'rejected' ? date('Y-m-d H:i:s') : null;

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $status, 
                $approvedBy, 
                $approvedAt,
                $rejectedBy, 
                $rejectedAt,
                $rejectionReason,
                $enrollmentId
            ]);

            if (!$result || $stmt->rowCount() === 0) {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Enrollment not found or already processed'];
            }

            // If approved, add course to user's course_applicability
            if ($status === 'approved') {
                $stmt = $this->conn->prepare("
                    SELECT user_id, course_id, client_id 
                    FROM course_enrollments 
                    WHERE id = ?
                ");
                $stmt->execute([$enrollmentId]);
                $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($enrollment) {
                    // Check if course_applicability entry already exists
                    $stmt = $this->conn->prepare("
                        SELECT id FROM course_applicability 
                        WHERE user_id = ? AND course_id = ? AND client_id = ?
                    ");
                    $stmt->execute([$enrollment['user_id'], $enrollment['course_id'], $enrollment['client_id']]);
                    
                    if (!$stmt->fetch()) {
                        // Insert into course_applicability
                        $stmt = $this->conn->prepare("
                            INSERT INTO course_applicability (user_id, course_id, client_id, applicability_type, created_at, updated_at) 
                            VALUES (?, ?, ?, 'user', NOW(), NOW())
                        ");
                        $stmt->execute([$enrollment['user_id'], $enrollment['course_id'], $enrollment['client_id']]);
                    }
                }
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Enrollment ' . $status . ' successfully'];

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("CourseEnrollmentModel: Error updating enrollment status: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error updating enrollment status'];
        }
    }

    /**
     * Get enrollment statistics
     * @param int $clientId
     * @return array
     */
    public function getEnrollmentStatistics($clientId) {
        try {
            $sql = "
                SELECT 
                    status,
                    COUNT(*) as count
                FROM course_enrollments 
                WHERE client_id = ? AND deleted_at IS NULL
                GROUP BY status
            ";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stats = [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0
            ];
            
            foreach ($results as $result) {
                $stats[$result['status']] = $result['count'];
                $stats['total'] += $result['count'];
            }
            
            return $stats;

        } catch (Exception $e) {
            error_log("CourseEnrollmentModel: Error getting enrollment statistics: " . $e->getMessage());
            return [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0
            ];
        }
    }
}
?>
