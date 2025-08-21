<?php
require_once 'config/Database.php';

class AudioProgressModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get or create audio progress record
     */
    public function getOrCreateProgress($userId, $courseId, $contentId, $audioPackageId, $clientId) {
        $sql = "SELECT * FROM audio_progress 
                WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $courseId, $contentId, $clientId]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$progress) {
            // Create new progress record
            $sql = "INSERT INTO audio_progress (user_id, course_id, content_id, audio_package_id, client_id, 
                    `current_time`, duration, listened_percentage, completion_threshold, is_completed, audio_status, playback_status,
                    play_count, last_listened_at, playback_speed, notes, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, 0, 0, 0, 80, 0, 'not_started', 'not_started', 0, NOW(), 1.0, '', NOW(), NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $audioPackageId, $clientId]);
            
            $progressId = $this->conn->lastInsertId();
            return $this->getProgressById($progressId);
        }

        return $progress;
    }

    /**
     * Get progress by ID
     */
    public function getProgressById($progressId) {
        $sql = "SELECT * FROM audio_progress WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$progressId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update audio progress
     */
    public function updateProgress($progressId, $data) {
        $sql = "UPDATE audio_progress SET 
                `current_time` = ?, 
                duration = ?, 
                listened_percentage = ?, 
                is_completed = ?, 
                audio_status = ?,
                playback_status = ?,
                play_count = ?, 
                last_listened_at = NOW(), 
                playback_speed = ?, 
                notes = ?, 
                updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['current_time'],
            $data['duration'],
            $data['listened_percentage'],
            $data['is_completed'],
            $data['audio_status'] ?? 'not_started',
            $data['playback_status'] ?? 'not_started',
            $data['play_count'],
            $data['playback_speed'],
            $data['notes'],
            $progressId
        ]);
    }

    /**
     * Get user's audio progress for a specific course
     */
    public function getUserAudioProgress($userId, $courseId, $clientId) {
        $sql = "SELECT ap.*, cmc.content_type, cmc.content_order 
                FROM audio_progress ap
                JOIN course_module_content cmc ON ap.content_id = cmc.id
                WHERE ap.user_id = ? AND ap.course_id = ? AND ap.client_id = ?
                ORDER BY cmc.content_order ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $courseId, $clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get completion percentage for audio content
     */
    public function getAudioCompletionPercentage($userId, $contentId, $clientId) {
        $sql = "SELECT listened_percentage, is_completed FROM audio_progress 
                WHERE user_id = ? AND content_id = ? AND client_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $contentId, $clientId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['is_completed'] ? 100 : $result['listened_percentage'];
        }
        
        return 0;
    }

    /**
     * Get audio progress for a specific content
     */
    public function getProgress($userId, $courseId, $contentId, $clientId) {
        $sql = "SELECT * FROM audio_progress 
                WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $courseId, $contentId, $clientId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Mark audio as completed
     */
    public function markAsCompleted($progressId) {
        $sql = "UPDATE audio_progress SET is_completed = 1, audio_status = 'completed', playback_status = 'stopped', updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$progressId]);
    }

    /**
     * Update audio status
     */
    public function updateAudioStatus($progressId, $status) {
        $validStatuses = ['not_started', 'in_progress', 'completed'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        $sql = "UPDATE audio_progress SET audio_status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $progressId]);
    }

    /**
     * Update playback status
     */
    public function updatePlaybackStatus($progressId, $status) {
        $validStatuses = ['not_started', 'playing', 'paused', 'stopped'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        $sql = "UPDATE audio_progress SET playback_status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $progressId]);
    }

    /**
     * Get audio progress summary for dashboard
     */
    public function getAudioProgressSummary($userId, $clientId) {
        $sql = "SELECT 
                    COUNT(*) as total_audio,
                    SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_audio,
                    AVG(listened_percentage) as avg_progress
                FROM audio_progress 
                WHERE user_id = ? AND client_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $clientId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Delete audio progress (for cleanup)
     */
    public function deleteProgress($progressId) {
        $sql = "DELETE FROM audio_progress WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$progressId]);
    }
}
?>
