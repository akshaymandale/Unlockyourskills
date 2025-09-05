<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/Database.php';

class VideoProgressModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get or create video progress record
     */
    public function getOrCreateProgress($userId, $courseId, $contentId, $videoPackageId, $clientId) {
        try {
            // Check if progress record exists
            $sql = "SELECT * FROM video_progress 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$progress) {
                // Create new progress record
                $sql = "INSERT INTO video_progress 
                        (user_id, course_id, content_id, video_package_id, client_id, 
                         `current_time`, duration, watched_percentage, completion_threshold, 
                         is_completed, video_status, play_count, last_watched_at, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, 0, 0, 0, 80, 0, 'not_started', 0, NOW(), NOW(), NOW())";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $contentId, $videoPackageId, $clientId]);
                
                // Get the newly created record
                $sql = "SELECT * FROM video_progress 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$userId, $courseId, $contentId, $clientId]);
                $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return $progress;
        } catch (Exception $e) {
            error_log("Error in getOrCreateProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update video progress
     */
    public function updateProgress($userId, $courseId, $contentId, $clientId, $data) {
        try {
            $sql = "UPDATE video_progress SET 
                    `current_time` = ?,
                    duration = ?,
                    watched_percentage = ?,
                    completion_threshold = ?,
                    is_completed = ?,
                    video_status = ?,
                    play_count = ?,
                    last_watched_at = NOW(),
                    bookmarks = ?,
                    notes = ?,
                    updated_at = NOW()
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute([
                $data['current_time'] ?? 0,
                $data['duration'] ?? 0,
                $data['watched_percentage'] ?? 0,
                $data['completion_threshold'] ?? 80,
                $data['is_completed'] ?? 0,
                $data['video_status'] ?? 'not_started',
                $data['play_count'] ?? 0,
                $data['bookmarks'] ?? null,
                $data['notes'] ?? null,
                $userId,
                $courseId,
                $contentId,
                $clientId
            ]);

            return $result;
        } catch (Exception $e) {
            error_log("Error in updateProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get video progress for a specific content
     */
    public function getProgress($userId, $courseId, $contentId, $clientId) {
        try {
            $sql = "SELECT * FROM video_progress 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getProgress: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get resume position for video
     */
    public function getResumePosition($userId, $courseId, $contentId, $clientId) {
        try {
            $sql = "SELECT `current_time`, duration, watched_percentage, is_completed, video_status 
                    FROM video_progress 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($progress) {
                return [
                    'success' => true,
                    'resume_position' => intval($progress['current_time']),
                    'duration' => intval($progress['duration']),
                    'watched_percentage' => floatval($progress['watched_percentage']),
                    'is_completed' => (bool)$progress['is_completed'],
                    'video_status' => $progress['video_status'] ?? 'not_started'
                ];
            }
            
            return [
                'success' => true,
                'resume_position' => 0,
                'duration' => 0,
                'watched_percentage' => 0,
                'is_completed' => false,
                'video_status' => 'not_started'
            ];
        } catch (Exception $e) {
            error_log("Error in getResumePosition: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error'
            ];
        }
    }

    /**
     * Get video statistics
     */
    public function getVideoStats($userId, $courseId, $contentId, $clientId) {
        try {
            $sql = "SELECT 
                        `current_time`,
                        duration,
                        watched_percentage,
                        is_completed,
                        video_status,
                        play_count,
                        last_watched_at,
                        bookmarks,
                        notes
                    FROM video_progress 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($progress) {
                return [
                    'success' => true,
                    'data' => [
                        'current_time' => intval($progress['current_time']),
                        'duration' => intval($progress['duration']),
                        'watched_percentage' => floatval($progress['watched_percentage']),
                        'is_completed' => (bool)$progress['is_completed'],
                        'video_status' => $progress['video_status'] ?? 'not_started',
                        'play_count' => intval($progress['play_count']),
                        'last_watched_at' => $progress['last_watched_at'],
                        'bookmarks' => $progress['bookmarks'] ? json_decode($progress['bookmarks'], true) : [],
                        'notes' => $progress['notes']
                    ]
                ];
            }
            
            return [
                'success' => true,
                'data' => [
                    'current_time' => 0,
                    'duration' => 0,
                    'watched_percentage' => 0,
                    'is_completed' => false,
                    'video_status' => 'not_started',
                    'play_count' => 0,
                    'last_watched_at' => null,
                    'bookmarks' => [],
                    'notes' => null
                ]
            ];
        } catch (Exception $e) {
            error_log("Error in getVideoStats: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Database error'
            ];
        }
    }

    /**
     * Get video package by ID
     */
    public function getVideoPackageById($videoPackageId) {
        try {
            $sql = "SELECT id, title, video_file, version, language, time_limit, description, tags, mobile_support, client_id, created_by, created_at, updated_by, updated_at, is_deleted FROM video_package WHERE id = ? AND (is_deleted IS NULL OR is_deleted = 0)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$videoPackageId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getVideoPackageById: " . $e->getMessage());
            return false;
        }
    }
}
?>
