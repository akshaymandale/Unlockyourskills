<?php
require_once 'config/Database.php';

class ImageProgressModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get or create image progress record
     */
    public function getOrCreateProgress($userId, $courseId, $contentId, $imagePackageId, $clientId) {
        try {
            // Check if progress record exists
            $sql = "SELECT * FROM image_progress WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($progress) {
                return $progress;
            }
            
            // Create new progress record
            $sql = "INSERT INTO image_progress (user_id, course_id, content_id, image_package_id, client_id, started_at, image_status, is_completed, view_count) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 'not_viewed', 0, 0)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $imagePackageId, $clientId]);
            
            $progressId = $this->conn->lastInsertId();
            
            // Return the newly created record
            return [
                'id' => $progressId,
                'user_id' => $userId,
                'course_id' => $courseId,
                'content_id' => $contentId,
                'image_package_id' => $imagePackageId,
                'client_id' => $clientId,
                'image_status' => 'not_viewed',
                'is_completed' => 0,
                'view_count' => 0,
                'viewed_at' => null,
                'notes' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Error in getOrCreateProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update image progress
     */
    public function updateProgress($userId, $courseId, $contentId, $clientId, $data) {
        try {
            // Set started_at if not already set and image is being viewed
            $setStartedAt = "";
            if (isset($data['image_status']) && in_array($data['image_status'], ['viewed', 'completed'])) {
                $setStartedAt = ", started_at = CASE WHEN started_at IS NULL THEN NOW() ELSE started_at END";
            }
            
            // Set completed_at if image is completed
            $setCompletedAt = "";
            if (isset($data['is_completed']) && $data['is_completed'] == 1) {
                $setCompletedAt = ", completed_at = NOW()";
            }
            
            $sql = "UPDATE image_progress SET 
                        image_status = ?,
                        is_completed = ?,
                        view_count = ?,
                        viewed_at = ?,
                        notes = ?,
                        updated_at = CURRENT_TIMESTAMP
                        $setStartedAt
                        $setCompletedAt
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $data['image_status'],
                $data['is_completed'],
                $data['view_count'],
                $data['viewed_at'],
                $data['notes'] ?? null,
                $userId,
                $courseId,
                $contentId,
                $clientId
            ]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error in updateProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get image progress
     */
    public function getProgress($userId, $courseId, $contentId, $clientId) {
        try {
            $sql = "SELECT * FROM image_progress WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error in getProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark image as viewed
     */
    public function markAsViewed($userId, $courseId, $contentId, $clientId) {
        try {
            $sql = "UPDATE image_progress SET 
                        image_status = 'viewed',
                        is_completed = 1,
                        view_count = view_count + 1,
                        viewed_at = COALESCE(viewed_at, CURRENT_TIMESTAMP),
                        completed_at = NOW(),
                        updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error in markAsViewed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get image package by ID
     */
    public function getImagePackageById($imagePackageId) {
        try {
            $sql = "SELECT id, title, image_file, version, language, description, tags, client_id, created_by, created_at, updated_by, updated_at, is_deleted FROM image_package WHERE id = ? AND (is_deleted IS NULL OR is_deleted = 0)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$imagePackageId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getImagePackageById: " . $e->getMessage());
            return false;
        }
    }
}
?>
