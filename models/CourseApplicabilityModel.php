<?php
require_once 'config/Database.php';

class CourseApplicabilityModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Assign applicability (all, by custom field, or by user)
    public function assignApplicability($courseId, $type, $data, $clientId) {
        if ($type === 'all') {
            // Check for duplicate
            $check = $this->conn->prepare("SELECT COUNT(*) FROM course_applicability WHERE course_id = :course_id AND applicability_type = 'all' AND client_id = :client_id");
            $check->execute([':course_id' => $courseId, ':client_id' => $clientId]);
            if ($check->fetchColumn() > 0) return 'duplicate';
            $sql = "INSERT INTO course_applicability (course_id, applicability_type, client_id) VALUES (:course_id, 'all', :client_id)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':course_id' => $courseId, ':client_id' => $clientId]);
        } elseif ($type === 'custom_field') {
            $check = $this->conn->prepare("SELECT COUNT(*) FROM course_applicability WHERE course_id = :course_id AND applicability_type = 'custom_field' AND custom_field_id = :field_id AND custom_field_value = :field_value AND client_id = :client_id");
            $check->execute([
                ':course_id' => $courseId,
                ':field_id' => $data['custom_field_id'],
                ':field_value' => $data['custom_field_value'],
                ':client_id' => $clientId
            ]);
            if ($check->fetchColumn() > 0) return 'duplicate';
            $sql = "INSERT INTO course_applicability (course_id, applicability_type, custom_field_id, custom_field_value, client_id) VALUES (:course_id, 'custom_field', :field_id, :field_value, :client_id)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':course_id' => $courseId,
                ':field_id' => $data['custom_field_id'],
                ':custom_field_value' => $data['custom_field_value'],
                ':client_id' => $clientId
            ]);
        } elseif ($type === 'user') {
            // Remove all existing user applicability rules for this course
            $del = $this->conn->prepare("DELETE FROM course_applicability WHERE course_id = :course_id AND applicability_type = 'user' AND client_id = :client_id");
            $del->execute([':course_id' => $courseId, ':client_id' => $clientId]);
            // Ensure user_ids is an array of integers
            if (isset($data['user_ids'])) {
                if (!is_array($data['user_ids'])) {
                    $data['user_ids'] = array_filter(array_map('trim', explode(',', $data['user_ids'])));
                } else {
                    // Flatten any comma-containing values
                    $flat = [];
                    foreach ($data['user_ids'] as $v) {
                        foreach (explode(',', $v) as $id) {
                            $id = trim($id);
                            if ($id !== '') $flat[] = $id;
                        }
                    }
                    $data['user_ids'] = $flat;
                }
            }
            error_log('assignApplicability: user_ids after normalization = ' . print_r($data['user_ids'], true));
            $sql = "INSERT INTO course_applicability (course_id, applicability_type, user_id, client_id) VALUES (:course_id, 'user', :user_id, :client_id)";
            $stmt = $this->conn->prepare($sql);
            $success = true;
            try {
                foreach ($data['user_ids'] as $userId) {
                    error_log('assignApplicability: inserting user_id = ' . $userId);
                    if (!is_numeric($userId) || empty($userId)) continue;
                    $success = $success && $stmt->execute([':course_id' => $courseId, ':user_id' => (int)$userId, ':client_id' => $clientId]);
                }
            } catch (PDOException $e) {
                error_log('assignApplicability: PDOException: ' . $e->getMessage());
                $success = false;
            }
            return $success;
        }
        return false;
    }

    // Fetch all applicability rules for a course
    public function getApplicability($courseId, $clientId = null) {
        if ($clientId) {
            $sql = "SELECT * FROM course_applicability WHERE course_id = :course_id AND client_id = :client_id ORDER BY id ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':course_id' => $courseId, ':client_id' => $clientId]);
        } else {
            $sql = "SELECT * FROM course_applicability WHERE course_id = :course_id ORDER BY id ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':course_id' => $courseId]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Remove a specific applicability rule
    public function removeApplicability($applicabilityId, $clientId = null) {
        if ($clientId) {
            $sql = "DELETE FROM course_applicability WHERE id = :id AND client_id = :client_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':id' => $applicabilityId, ':client_id' => $clientId]);
        } else {
            $sql = "DELETE FROM course_applicability WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':id' => $applicabilityId]);
        }
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
                // Users with the custom field value - use client_id from applicability rule
                $sql = "SELECT id FROM users WHERE client_id = :client_id AND customised_{$rule['custom_field_id']} = :field_value AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':client_id' => $rule['client_id'], ':field_value' => $rule['custom_field_value']]);
                $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $userIds = array_merge($userIds, $ids);
            } elseif ($rule['applicability_type'] === 'user') {
                $userIds[] = $rule['user_id'];
            }
        }
        if ($userIds === 'ALL') {
            // Fetch all users for the course's client - use client_id from applicability rule
            $firstRule = reset($applicabilities);
            if ($firstRule && isset($firstRule['client_id'])) {
                $sql = "SELECT u.id FROM users u WHERE u.client_id = :client_id AND u.is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([':client_id' => $firstRule['client_id']]);
                $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
        }
        // Remove duplicates
        return array_unique($userIds);
    }
} 