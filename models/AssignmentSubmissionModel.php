<?php
/**
 * Assignment Submission Model
 * 
 * Handles all database operations related to assignment submissions
 */

require_once 'config/Database.php';

class AssignmentSubmissionModel {
    private $conn;
    private $lastErrorMessage = '';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get the last error message
     */
    public function getLastError() {
        return $this->lastErrorMessage;
    }

    /**
     * Save assignment submission
     */
    public function saveSubmission($data) {
        try {
            $sql = "INSERT INTO assignment_submissions (
                client_id, course_id, user_id, assignment_package_id,
                submission_type, submission_file, submission_text, submission_url,
                submission_status, due_date, is_late, attempt_number
            ) VALUES (
                :client_id, :course_id, :user_id, :assignment_package_id,
                :submission_type, :submission_file, :submission_text, :submission_url,
                :submission_status, :due_date, :is_late, :attempt_number
            )";

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':client_id' => $data['client_id'],
                ':course_id' => $data['course_id'],
                ':user_id' => $data['user_id'],
                ':assignment_package_id' => $data['assignment_package_id'],
                ':submission_type' => $data['submission_type'],
                ':submission_file' => $data['submission_file'] ?? null,
                ':submission_text' => $data['submission_text'] ?? null,
                ':submission_url' => $data['submission_url'] ?? null,
                ':submission_status' => $data['submission_status'] ?? 'submitted',
                ':due_date' => $data['due_date'] ?? null,
                ':is_late' => isset($data['is_late']) ? (int)$data['is_late'] : 0,
                ':attempt_number' => $data['attempt_number'] ?? 1
            ]);

            if ($result) {
                return $this->conn->lastInsertId();
            } else {
                $this->lastErrorMessage = "Failed to save assignment submission";
                return false;
            }
        } catch (PDOException $e) {
            $this->lastErrorMessage = "Database error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get assignment submissions for a specific course and user
     */
    public function getSubmissionsByCourseAndUser($courseId, $userId, $assignmentPackageId = null) {
        try {
            $sql = "SELECT 
                s.*,
                ap.title as assignment_title,
                ap.description as assignment_description,
                ap.instructions as assignment_instructions,
                ap.requirements as assignment_requirements,
                ap.submission_format as assignment_submission_format,
                ap.max_attempts as assignment_max_attempts,
                ap.passing_score as assignment_passing_score,
                ap.allow_late_submission as assignment_allow_late_submission,
                ap.late_submission_penalty as assignment_late_submission_penalty
                FROM assignment_submissions s
                LEFT JOIN assignment_package ap ON s.assignment_package_id = ap.id
                WHERE s.course_id = ? AND s.user_id = ? AND s.is_deleted = 0";
            
            $params = [$courseId, $userId];
            
            if ($assignmentPackageId) {
                $sql .= " AND s.assignment_package_id = ?";
                $params[] = $assignmentPackageId;
            }
            
            $sql .= " ORDER BY s.submitted_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get submission files for each submission
            foreach ($submissions as &$submission) {
                $submission['files'] = $this->getSubmissionFiles($submission['id']);
                $submission['comments'] = $this->getSubmissionComments($submission['id']);
            }
            
            return $submissions;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get assignment package details
     */
    public function getAssignmentPackage($assignmentPackageId) {
        try {
            $sql = "SELECT 
                ap.*,
                l.language_name
                FROM assignment_package ap
                LEFT JOIN languages l ON ap.language = l.id
                WHERE ap.id = ? AND ap.is_deleted = 0";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$assignmentPackageId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Check if user has submitted assignment for a specific assignment package
     */
    public function hasUserSubmittedAssignment($courseId, $userId, $assignmentPackageId) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM assignment_submissions 
                    WHERE course_id = ? AND user_id = ? AND assignment_package_id = ? AND is_deleted = 0";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $userId, $assignmentPackageId]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get user's submission attempt count for an assignment
     */
    public function getUserSubmissionAttempts($courseId, $userId, $assignmentPackageId) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM assignment_submissions 
                    WHERE course_id = ? AND user_id = ? AND assignment_package_id = ? AND is_deleted = 0";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $userId, $assignmentPackageId]);
            
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Get recent submission within specified seconds to prevent duplicates
     */
    public function getRecentSubmission($courseId, $userId, $assignmentPackageId, $seconds = 30) {
        try {
            $sql = "SELECT id, submitted_at 
                    FROM assignment_submissions 
                    WHERE course_id = ? AND user_id = ? AND assignment_package_id = ? 
                    AND is_deleted = 0 
                    AND submitted_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                    ORDER BY submitted_at DESC 
                    LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $userId, $assignmentPackageId, $seconds]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get submission files
     */
    public function getSubmissionFiles($submissionId) {
        try {
            $sql = "SELECT * FROM assignment_submission_files 
                    WHERE submission_id = ? AND is_deleted = 0 
                    ORDER BY uploaded_at ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$submissionId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Save submission file
     */
    public function saveSubmissionFile($data) {
        try {
            $sql = "INSERT INTO assignment_submission_files (
                submission_id, file_name, file_path, file_size, file_type
            ) VALUES (
                :submission_id, :file_name, :file_path, :file_size, :file_type
            )";

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':submission_id' => $data['submission_id'],
                ':file_name' => $data['file_name'],
                ':file_path' => $data['file_path'],
                ':file_size' => $data['file_size'],
                ':file_type' => $data['file_type']
            ]);

            if ($result) {
                return $this->conn->lastInsertId();
            } else {
                $this->lastErrorMessage = "Failed to save submission file";
                return false;
            }
        } catch (PDOException $e) {
            $this->lastErrorMessage = "Database error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get submission comments
     */
    public function getSubmissionComments($submissionId) {
        try {
            $sql = "SELECT 
                c.*,
                u.first_name,
                u.last_name,
                u.email
                FROM assignment_submission_comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.submission_id = ? AND c.is_deleted = 0 
                ORDER BY c.created_at ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$submissionId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Add submission comment
     */
    public function addSubmissionComment($data) {
        try {
            $sql = "INSERT INTO assignment_submission_comments (
                submission_id, user_id, comment_text, comment_type, is_private
            ) VALUES (
                :submission_id, :user_id, :comment_text, :comment_type, :is_private
            )";

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':submission_id' => $data['submission_id'],
                ':user_id' => $data['user_id'],
                ':comment_text' => $data['comment_text'],
                ':comment_type' => $data['comment_type'] ?? 'student',
                ':is_private' => $data['is_private'] ?? false
            ]);

            if ($result) {
                return $this->conn->lastInsertId();
            } else {
                $this->lastErrorMessage = "Failed to add comment";
                return false;
            }
        } catch (PDOException $e) {
            $this->lastErrorMessage = "Database error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get assignment packages for a course (prerequisites and post-requisites)
     */
    public function getCourseAssignmentPackages($courseId) {
        try {
            // Get prerequisite assignments
            $sql = "SELECT 
                ap.*,
                'prerequisite' as package_type,
                cp.prerequisite_description as description,
                cp.sort_order
                FROM course_prerequisites cp
                LEFT JOIN assignment_package ap ON cp.prerequisite_id = ap.id
                WHERE cp.course_id = ? AND cp.prerequisite_type = 'assignment' AND (cp.deleted_at IS NULL OR cp.deleted_at = '0000-00-00 00:00:00') AND ap.is_deleted = 0
                ORDER BY cp.sort_order ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            $prerequisiteAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get post-requisite assignments
            $sql = "SELECT 
                ap.*,
                'post_requisite' as package_type,
                cpr.description,
                cpr.sort_order
                FROM course_post_requisites cpr
                LEFT JOIN assignment_package ap ON cpr.content_id = ap.id
                WHERE cpr.course_id = ? AND cpr.content_type = 'assignment' AND cpr.is_deleted = 0 AND ap.is_deleted = 0
                ORDER BY cpr.sort_order ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            $postRequisiteAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Combine and return
            $assignmentPackages = array_merge($prerequisiteAssignments, $postRequisiteAssignments);
            
            return $assignmentPackages;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Delete assignment submission (soft delete)
     */
    public function deleteSubmission($submissionId, $userId) {
        try {
            $sql = "UPDATE assignment_submissions 
                    SET is_deleted = 1, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ? AND user_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$submissionId, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get assignment submission statistics for a course
     */
    public function getSubmissionStats($courseId, $assignmentPackageId = null) {
        try {
            $sql = "SELECT 
                COUNT(*) as total_submissions,
                COUNT(CASE WHEN submission_status = 'submitted' THEN 1 END) as pending_submissions,
                COUNT(CASE WHEN submission_status = 'graded' THEN 1 END) as graded_submissions,
                COUNT(CASE WHEN is_late = 1 THEN 1 END) as late_submissions,
                AVG(grade) as average_grade
                FROM assignment_submissions 
                WHERE course_id = ? AND is_deleted = 0";
            
            $params = [$courseId];
            
            if ($assignmentPackageId) {
                $sql .= " AND assignment_package_id = ?";
                $params[] = $assignmentPackageId;
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
