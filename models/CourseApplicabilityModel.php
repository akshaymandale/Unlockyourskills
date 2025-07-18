<?php
require_once 'config/Database.php';

class CourseApplicabilityModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Assign applicability (all, by custom field, or by user)
    public function assignApplicability($courseId, $type, $data) {
        if ($type === 'all') {
            // Check for duplicate
            $check = $this->conn->prepare("SELECT COUNT(*) FROM course_applicability WHERE course_id = :course_id AND applicability_type = 'all'");
            $check->execute([':course_id' => $courseId]);
            if ($check->fetchColumn() > 0) return 'duplicate';
            $sql = "INSERT INTO course_applicability (course_id, applicability_type) VALUES (:course_id, 'all')";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':course_id' => $courseId]);
        } elseif ($type === 'custom_field') {
            $check = $this->conn->prepare("SELECT COUNT(*) FROM course_applicability WHERE course_id = :course_id AND applicability_type = 'custom_field' AND custom_field_id = :field_id AND custom_field_value = :field_value");
            $check->execute([
                ':course_id' => $courseId,
                ':field_id' => $data['custom_field_id'],
                ':field_value' => $data['custom_field_value']
            ]);
            if ($check->fetchColumn() > 0) return 'duplicate';
            $sql = "INSERT INTO course_applicability (course_id, applicability_type, custom_field_id, custom_field_value) VALUES (:course_id, 'custom_field', :field_id, :field_value)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':course_id' => $courseId,
                ':field_id' => $data['custom_field_id'],
                ':field_value' => $data['custom_field_value']
            ]);
        } elseif ($type === 'user') {
            $sql = "INSERT INTO course_applicability (course_id, applicability_type, user_id) VALUES (:course_id, 'user', :user_id)";
            $stmt = $this->conn->prepare($sql);
            $success = true;
            foreach ($data['user_ids'] as $userId) {
                // Check for duplicate for each user
                $check = $this->conn->prepare("SELECT COUNT(*) FROM course_applicability WHERE course_id = :course_id AND applicability_type = 'user' AND user_id = :user_id");
                $check->execute([':course_id' => $courseId, ':user_id' => $userId]);
                if ($check->fetchColumn() > 0) {
                    $success = false;
                    continue;
                }
                $success = $success && $stmt->execute([':course_id' => $courseId, ':user_id' => $userId]);
            }
            return $success ? true : 'duplicate';
        }
        return false;
    }

    // Fetch all applicability rules for a course
    public function getApplicability($courseId) {
        $sql = "SELECT * FROM course_applicability WHERE course_id = :course_id ORDER BY id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':course_id' => $courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Remove a specific applicability rule
    public function removeApplicability($applicabilityId) {
        $sql = "DELETE FROM course_applicability WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $applicabilityId]);
    }

    // Get all users to whom the course is applicable (resolve all rules)
    public function getApplicableUsers($courseId) {
        $applicabilities = $this->getApplicability($courseId);
        $userIds = [];
        foreach ($applicabilities as $rule) {
            if ($rule['applicability_type'] === 'all') {
                // All users for the course's client
                $userIds = 'ALL'; // Special flag
                break;
            } elseif ($rule['applicability_type'] === 'custom_field') {
                // Users with the custom field value
                $sql = "SELECT id FROM users WHERE client_id = (SELECT client_id FROM courses WHERE id = :course_id) AND customised_{$rule['custom_field_id']} = :field_value AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':course_id' => $courseId, ':field_value' => $rule['custom_field_value']]);
                $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $userIds = array_merge($userIds, $ids);
            } elseif ($rule['applicability_type'] === 'user') {
                $userIds[] = $rule['user_id'];
            }
        }
        if ($userIds === 'ALL') {
            // Fetch all users for the course's client
            $sql = "SELECT u.id FROM users u JOIN courses c ON u.client_id = c.client_id WHERE c.id = :course_id AND u.is_deleted = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':course_id' => $courseId]);
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        // Remove duplicates
        return array_unique($userIds);
    }
} 