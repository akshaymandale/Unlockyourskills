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
            // First check if this is a prerequisite by looking for it in course_prerequisites
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'image');
            
            if ($isPrerequisite) {
                // For prerequisites, look for records with prerequisite_id = contentId
                $sql = "SELECT * FROM image_progress 
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $contentId, $clientId]);
                $progress = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$progress) {
                    // Create new progress record for prerequisite
                    // For prerequisites, only set prerequisite_id and leave content_id as NULL
                    $sql = "INSERT INTO image_progress (
                                user_id, course_id, prerequisite_id, content_id, image_package_id, client_id,
                                started_at, image_status, is_completed, view_count
                            ) VALUES (?, ?, ?, NULL, ?, ?, NOW(), 'not_viewed', 0, 0)";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$userId, $courseId, $contentId, $imagePackageId, $clientId]);
                    
                    return $this->getProgress($userId, $courseId, $contentId, $clientId);
                } else {
                    return $progress;
                }
            } else {
                // For module content, use the existing logic
                $sql = "SELECT * FROM image_progress WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $contentId, $clientId]);
                
                $progress = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($progress) {
                    return $progress;
                }
                
                // Create new progress record for module content
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
            }
            
        } catch (Exception $e) {
            error_log("Error in getOrCreateProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if content is a prerequisite
     */
    private function isContentPrerequisite($courseId, $contentId, $contentType) {
        try {
            $sql = "SELECT COUNT(*) FROM course_prerequisites 
                    WHERE course_id = ? AND id = ? AND prerequisite_type = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $contentId, $contentType]);
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error checking if content is prerequisite: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update image progress
     */
    public function updateProgress($userId, $courseId, $contentId, $clientId, $data) {
        try {
            // Check if this is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'image');
            
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
            
            if ($isPrerequisite) {
                // For prerequisites, update using prerequisite_id
                $sql = "UPDATE image_progress SET 
                            image_status = ?,
                            is_completed = ?,
                            view_count = ?,
                            viewed_at = ?,
                            notes = ?,
                            updated_at = CURRENT_TIMESTAMP
                            $setStartedAt
                            $setCompletedAt
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
                
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
            } else {
                // For module content, update using content_id
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
            }
            
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
            // Check if this is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'image');
            
            if ($isPrerequisite) {
                // For prerequisites, look for records with prerequisite_id = contentId
                $sql = "SELECT * FROM image_progress 
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            } else {
                // For module content, use content_id
                $sql = "SELECT * FROM image_progress WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            }
            
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
            // Check if this is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, 'image');
            
            if ($isPrerequisite) {
                // For prerequisites, update using prerequisite_id
                $sql = "UPDATE image_progress SET 
                            image_status = 'viewed',
                            is_completed = 1,
                            view_count = view_count + 1,
                            viewed_at = COALESCE(viewed_at, CURRENT_TIMESTAMP),
                            completed_at = NOW(),
                            updated_at = CURRENT_TIMESTAMP
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            } else {
                // For module content, use content_id
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
            }
            
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
