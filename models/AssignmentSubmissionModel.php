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
            // Build SQL based on submission status
            $submissionStatus = $data['submission_status'] ?? 'submitted';
            if ($submissionStatus === 'submitted') {
                $sql = "INSERT INTO assignment_submissions (
                    client_id, course_id, user_id, assignment_package_id,
                    prerequisite_id, content_id, postrequisite_id,
                    submission_type, submission_file, submission_text, submission_url,
                    submission_status, due_date, is_late, attempt_number,
                    started_at, submitted_at, completed_at, updated_at
                ) VALUES (
                    :client_id, :course_id, :user_id, :assignment_package_id,
                    :prerequisite_id, :content_id, :postrequisite_id,
                    :submission_type, :submission_file, :submission_text, :submission_url,
                    :submission_status, :due_date, :is_late, :attempt_number,
                    :started_at, NOW(), NOW(), NOW()
                )";
            } else {
                $sql = "INSERT INTO assignment_submissions (
                    client_id, course_id, user_id, assignment_package_id,
                    prerequisite_id, content_id, postrequisite_id,
                    submission_type, submission_file, submission_text, submission_url,
                    submission_status, due_date, is_late, attempt_number,
                    started_at, updated_at
                ) VALUES (
                    :client_id, :course_id, :user_id, :assignment_package_id,
                    :prerequisite_id, :content_id, :postrequisite_id,
                    :submission_type, :submission_file, :submission_text, :submission_url,
                    :submission_status, :due_date, :is_late, :attempt_number,
                    :started_at, NOW()
                )";
            }

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':client_id' => $data['client_id'],
                ':course_id' => $data['course_id'],
                ':user_id' => $data['user_id'],
                ':assignment_package_id' => $data['assignment_package_id'],
                ':prerequisite_id' => $data['prerequisite_id'] ?? null,
                ':content_id' => $data['content_id'] ?? null,
                ':postrequisite_id' => $data['postrequisite_id'] ?? null,
                ':submission_type' => $data['submission_type'],
                ':submission_file' => $data['submission_file'] ?? null,
                ':submission_text' => $data['submission_text'] ?? null,
                ':submission_url' => $data['submission_url'] ?? null,
                ':submission_status' => $data['submission_status'] ?? 'submitted',
                ':due_date' => $data['due_date'] ?? null,
                ':is_late' => isset($data['is_late']) ? (int)$data['is_late'] : 0,
                ':attempt_number' => $data['attempt_number'] ?? 1,
                ':started_at' => $data['started_at'] ?? null
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
    public function getSubmissionsByCourseAndUser($courseId, $userId, $assignmentPackageId = null, $context = null) {
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
            
            // Filter by context if provided
            if ($context) {
                switch ($context) {
                    case 'prerequisite':
                        $sql .= " AND s.prerequisite_id IS NOT NULL AND s.content_id IS NULL";
                        break;
                    case 'module':
                        $sql .= " AND s.prerequisite_id IS NULL AND s.content_id IS NOT NULL";
                        break;
                    case 'postrequisite':
                        $sql .= " AND s.prerequisite_id IS NULL AND s.content_id IS NULL AND s.postrequisite_id IS NOT NULL";
                        break;
                }
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
                    WHERE course_id = ? AND user_id = ? AND assignment_package_id = ? 
                    AND submission_status IN ('submitted', 'graded', 'returned')
                    AND is_deleted = 0";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $userId, $assignmentPackageId]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get user's submission attempt count for an assignment (only completed submissions)
     */
    public function getUserSubmissionAttempts($courseId, $userId, $assignmentPackageId) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM assignment_submissions 
                    WHERE course_id = ? AND user_id = ? AND assignment_package_id = ? 
                    AND submission_status IN ('submitted', 'graded', 'returned') 
                    AND is_deleted = 0";
            
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
     * Get recent completed submission within specified seconds to prevent duplicates
     */
    public function getRecentCompletedSubmission($courseId, $userId, $assignmentPackageId, $seconds = 30) {
        try {
            $sql = "SELECT id, submitted_at 
                    FROM assignment_submissions 
                    WHERE course_id = ? AND user_id = ? AND assignment_package_id = ? 
                    AND submission_status IN ('submitted', 'graded', 'returned')
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

    /**
     * Start assignment tracking - create in-progress submission record
     */
    public function startAssignmentTracking($data) {
        try {
            // Check if user already has an in-progress submission for this assignment
            $existingSubmission = $this->getInProgressSubmission($data['course_id'], $data['user_id'], $data['assignment_package_id']);
            
            if ($existingSubmission) {
                // Update started_at if not already set
                if (!$existingSubmission['started_at']) {
                    $sql = "UPDATE assignment_submissions 
                            SET started_at = NOW(), updated_at = NOW() 
                            WHERE id = ?";
                    $stmt = $this->conn->prepare($sql);
                    return $stmt->execute([$existingSubmission['id']]);
                }
                return $existingSubmission['id'];
            }
            
            // Create new in-progress submission
            $sql = "INSERT INTO assignment_submissions (
                client_id, course_id, user_id, assignment_package_id,
                prerequisite_id, content_id, postrequisite_id,
                submission_type, submission_status, started_at, attempt_number,
                created_at, updated_at
            ) VALUES (
                :client_id, :course_id, :user_id, :assignment_package_id,
                :prerequisite_id, :content_id, :postrequisite_id,
                :submission_type, :submission_status, NOW(), :attempt_number,
                NOW(), NOW()
            )";

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                ':client_id' => $data['client_id'],
                ':course_id' => $data['course_id'],
                ':user_id' => $data['user_id'],
                ':assignment_package_id' => $data['assignment_package_id'],
                ':prerequisite_id' => $data['prerequisite_id'] ?? null,
                ':content_id' => $data['content_id'] ?? null,
                ':postrequisite_id' => $data['postrequisite_id'] ?? null,
                ':submission_type' => $data['submission_type'] ?? null,
                ':submission_status' => 'in-progress',
                ':attempt_number' => $data['attempt_number'] ?? 1
            ]);

            if ($result) {
                return $this->conn->lastInsertId();
            } else {
                $this->lastErrorMessage = "Failed to start assignment tracking";
                return false;
            }
        } catch (PDOException $e) {
            $this->lastErrorMessage = "Database error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Get in-progress submission for a user and assignment
     */
    public function getInProgressSubmission($courseId, $userId, $assignmentPackageId, $context = null) {
        try {
            $sql = "SELECT * FROM assignment_submissions 
                    WHERE course_id = ? AND user_id = ? AND assignment_package_id = ? 
                    AND submission_status = 'in-progress' AND is_deleted = 0";
            
            $params = [$courseId, $userId, $assignmentPackageId];
            
            // Filter by context if provided
            if ($context) {
                switch ($context) {
                    case 'prerequisite':
                        $sql .= " AND prerequisite_id IS NOT NULL AND content_id IS NULL";
                        break;
                    case 'module':
                        $sql .= " AND prerequisite_id IS NULL AND content_id IS NOT NULL";
                        break;
                    case 'postrequisite':
                        $sql .= " AND prerequisite_id IS NULL AND content_id IS NULL AND postrequisite_id IS NOT NULL";
                        break;
                }
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Update in-progress submission with new data (without changing status)
     */
    public function updateInProgressSubmission($submissionId, $data) {
        try {
            $sql = "UPDATE assignment_submissions 
                    SET submission_type = ?, 
                        submission_file = ?, 
                        submission_text = ?, 
                        submission_url = ?, 
                        submission_status = ?,
                        updated_at = NOW()
                    WHERE id = ? AND submission_status = 'in-progress'";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $data['submission_type'],
                $data['submission_file'] ?? null,
                $data['submission_text'] ?? null,
                $data['submission_url'] ?? null,
                $data['submission_status'] ?? 'in-progress',
                $submissionId
            ]);
            
            if ($result) {
                return $submissionId;
            } else {
                $this->lastErrorMessage = "Failed to update in-progress submission";
                return false;
            }
        } catch (PDOException $e) {
            $this->lastErrorMessage = "Database error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update in-progress submission to completed status
     */
    public function updateSubmissionToCompleted($submissionId, $data) {
        try {
            $sql = "UPDATE assignment_submissions 
                    SET submission_type = ?, 
                        submission_file = ?, 
                        submission_text = ?, 
                        submission_url = ?, 
                        submission_status = 'submitted',
                        submitted_at = NOW(),
                        completed_at = NOW(),
                        is_late = ?,
                        attempt_number = ?,
                        updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $data['submission_type'],
                $data['submission_file'] ?? null,
                $data['submission_text'] ?? null,
                $data['submission_url'] ?? null,
                isset($data['is_late']) ? (int)$data['is_late'] : 0,
                $data['attempt_number'] ?? 1,
                $submissionId
            ]);
            
            if ($result) {
                return $submissionId;
            } else {
                $this->lastErrorMessage = "Failed to update submission to completed";
                return false;
            }
        } catch (PDOException $e) {
            $this->lastErrorMessage = "Database error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Update assignment submission status and set completed_at when graded
     */
    public function updateSubmissionStatus($submissionId, $status, $grade = null, $feedback = null) {
        try {
            // Set completed_at if status is 'graded' or 'returned'
            $setCompletedAt = "";
            if (in_array($status, ['graded', 'returned'])) {
                $setCompletedAt = ", completed_at = NOW()";
            }
            
            $sql = "UPDATE assignment_submissions 
                    SET submission_status = ?, 
                        grade = ?, 
                        feedback = ?, 
                        graded_at = NOW(),
                        updated_at = NOW()
                        $setCompletedAt
                    WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$status, $grade, $feedback, $submissionId]);
        } catch (PDOException $e) {
            $this->lastErrorMessage = $e->getMessage();
            return false;
        }
    }

    /**
     * Get assignment submission by ID
     */
    public function getSubmissionById($submissionId) {
        try {
            $sql = "SELECT * FROM assignment_submissions WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$submissionId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastErrorMessage = $e->getMessage();
            return false;
        }
    }
}
?>
