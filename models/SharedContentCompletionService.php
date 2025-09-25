<?php
/**
 * SharedContentCompletionService
 * Handles auto-completion of shared content between prerequisites and modules
 */

require_once 'config/Database.php';

class SharedContentCompletionService {
    private $conn;
    
    // Content type to table mapping
    private $contentTypeTables = [
        'video' => 'video_progress',
        'audio' => 'audio_progress', 
        'document' => 'document_progress',
        'image' => 'image_progress',
        'scorm' => 'scorm_progress',
        'external' => 'external_progress',
        'interactive' => 'interactive_progress',
        'assessment' => 'assessment_results',
        'assignment' => 'assignment_submissions',
        'survey' => 'course_survey_responses',
        'feedback' => 'course_feedback_responses'
    ];
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    
    /**
     * Handle shared content completion for any content type
     */
    public function handleSharedContentCompletion($userId, $courseId, $contentId, $clientId, $contentType, $contextType, $contextId = null) {
        try {
            error_log("[SharedContent] Processing {$contentType} completion for user: {$userId}, course: {$courseId}, content: {$contentId}, context: {$contextType}");
            
            // Get the latest progress for this content
            $progressData = $this->getLatestContentProgress($contentId, $userId, $courseId, $clientId, $contentType);
            
            if (!$progressData) {
                error_log("[SharedContent] No progress data found for {$contentType} content {$contentId}");
                return;
            }
            
            // Handle based on context type
            if ($contextType === 'prerequisite') {
                $this->handlePrerequisiteToModuleCompletion($userId, $courseId, $contentId, $clientId, $contentType, $progressData);
            } else {
                $this->handleModuleToPrerequisiteCompletion($userId, $courseId, $contentId, $clientId, $contentType, $progressData);
            }
            
        } catch (Exception $e) {
            error_log("[SharedContent] Error handling shared {$contentType} completion: " . $e->getMessage());
        }
    }
    
    /**
     * Handle completion when content was completed as prerequisite
     */
    private function handlePrerequisiteToModuleCompletion($userId, $courseId, $contentId, $clientId, $contentType, $progressData) {
        // Find module content containing this content
        $moduleContents = $this->getModuleContentsForContent($courseId, $contentId, $contentType);
        
        if (!empty($moduleContents)) {
            foreach ($moduleContents as $moduleContent) {
                $this->createModuleContentProgress($userId, $courseId, $moduleContent, $clientId, $contentType, $progressData);
            }
            
            error_log("[SharedContent] Created module progress for shared {$contentType} content {$contentId} in course {$courseId} for user {$userId}");
        }
    }
    
    /**
     * Handle completion when content was completed as module content
     */
    private function handleModuleToPrerequisiteCompletion($userId, $courseId, $contentId, $clientId, $contentType, $progressData) {
        // Find prerequisites containing this content
        $prerequisiteIds = $this->getPrerequisiteIdsForContent($courseId, $contentId, $contentType);
        
        if (!empty($prerequisiteIds)) {
            foreach ($prerequisiteIds as $prereqId) {
                $this->createPrerequisiteContentProgress($userId, $courseId, $prereqId, $clientId, $contentType, $progressData);
            }
            
            error_log("[SharedContent] Created prerequisite progress for shared {$contentType} content {$contentId} in course {$courseId} for user {$userId}");
        }
    }
    
    /**
     * Get module contents that contain this content
     */
    private function getModuleContentsForContent($courseId, $contentId, $contentType) {
        try {
            $sql = "SELECT cmc.id as content_id, cmc.module_id, cmc.content_id as actual_content_id, cm.title as module_title
                    FROM course_module_content cmc
                    JOIN course_modules cm ON cmc.module_id = cm.id
                    WHERE cm.course_id = ? AND cmc.content_id = ? AND cmc.content_type = ? 
                    AND cmc.deleted_at IS NULL AND cm.deleted_at IS NULL";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $contentId, $contentType]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("[SharedContent] Error getting module contents for {$contentType}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get prerequisite IDs that contain this content
     */
    private function getPrerequisiteIdsForContent($courseId, $contentId, $contentType) {
        try {
            $sql = "SELECT id FROM course_prerequisites 
                    WHERE course_id = ? AND prerequisite_id = ? AND prerequisite_type = ? 
                    AND deleted_at IS NULL";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId, $contentId, $contentType]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            error_log("[SharedContent] Error getting prerequisite IDs for {$contentType}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get the latest progress for this content
     */
    private function getLatestContentProgress($contentId, $userId, $courseId, $clientId, $contentType) {
        try {
            $table = $this->contentTypeTables[$contentType];
            
            if ($contentType === 'assessment') {
                $sql = "SELECT * FROM {$table} 
                        WHERE assessment_id = ? AND user_id = ? AND course_id = ? AND client_id = ? 
                        ORDER BY completed_at DESC LIMIT 1";
            } elseif ($contentType === 'assignment') {
                $sql = "SELECT * FROM {$table} 
                        WHERE assignment_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ? 
                        ORDER BY created_at DESC LIMIT 1";
            } elseif ($contentType === 'survey') {
                $sql = "SELECT * FROM {$table} 
                        WHERE survey_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ? 
                        ORDER BY completed_at DESC LIMIT 1";
            } elseif ($contentType === 'feedback') {
                $sql = "SELECT * FROM {$table} 
                        WHERE feedback_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ? 
                        ORDER BY completed_at DESC LIMIT 1";
            } elseif ($contentType === 'video') {
                // For video content, look by video_package_id since that's the actual content identifier
                $sql = "SELECT * FROM {$table} 
                        WHERE video_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ? 
                        ORDER BY completed_at DESC, updated_at DESC LIMIT 1";
            } elseif ($contentType === 'audio') {
                // For audio content, look by audio_package_id since that's the actual content identifier
                $sql = "SELECT * FROM {$table} 
                        WHERE audio_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ? 
                        ORDER BY completed_at DESC, updated_at DESC LIMIT 1";
            } elseif ($contentType === 'document') {
                // For document content, look by document_package_id since that's the actual content identifier
                $sql = "SELECT * FROM {$table} 
                        WHERE document_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ? 
                        ORDER BY completed_at DESC, updated_at DESC LIMIT 1";
            } elseif ($contentType === 'image') {
                // For image content, look by image_package_id since that's the actual content identifier
                $sql = "SELECT * FROM {$table} 
                        WHERE image_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ? 
                        ORDER BY completed_at DESC, updated_at DESC LIMIT 1";
            } elseif ($contentType === 'interactive') {
                // For interactive content, try both content_id and prerequisite_id
                $sql = "SELECT * FROM {$table} 
                        WHERE (content_id = ? OR prerequisite_id = ?) AND user_id = ? AND course_id = ? AND client_id = ? 
                        ORDER BY completed_at DESC, updated_at DESC LIMIT 1";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$contentId, $contentId, $userId, $courseId, $clientId]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } elseif ($contentType === 'external') {
                // For external content, look by external_package_id since that's the actual content identifier
                $sql = "SELECT * FROM {$table} 
                        WHERE external_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ? 
                        ORDER BY completed_at DESC, updated_at DESC LIMIT 1";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$contentId, $userId, $courseId, $clientId]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $sql = "SELECT * FROM {$table} 
                        WHERE content_id = ? AND user_id = ? AND course_id = ? AND client_id = ? 
                        ORDER BY completed_at DESC, updated_at DESC LIMIT 1";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$contentId, $userId, $courseId, $clientId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("[SharedContent] Error getting latest {$contentType} progress: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create module content progress entry
     */
    private function createModuleContentProgress($userId, $courseId, $moduleContent, $clientId, $contentType, $progressData) {
        try {
            $table = $this->contentTypeTables[$contentType];
            
            // Check if progress already exists for this module content
            $checkSql = "SELECT id FROM {$table} 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute([$userId, $courseId, $moduleContent['content_id'], $clientId]);
            $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingRecord) {
                // For assignments, update existing record with submission details if they're missing
                if ($contentType === 'assignment') {
                    $this->updateExistingAssignmentSubmission($existingRecord['id'], $progressData);
                    error_log("[SharedContent] Updated existing module assignment submission {$existingRecord['id']} with submission details");
                } else {
                    error_log("[SharedContent] Module {$contentType} progress already exists for content {$moduleContent['content_id']}");
                }
                return;
            }
            
            // Create new progress entry based on content type
            $this->insertModuleProgressByType($userId, $courseId, $moduleContent, $clientId, $contentType, $progressData);
            
            error_log("[SharedContent] Created module {$contentType} progress for content {$moduleContent['content_id']}");
            
        } catch (Exception $e) {
            error_log("[SharedContent] Error creating module {$contentType} progress: " . $e->getMessage());
        }
    }
    
    /**
     * Create prerequisite content progress entry
     */
    private function createPrerequisiteContentProgress($userId, $courseId, $prerequisiteId, $clientId, $contentType, $progressData) {
        try {
            $table = $this->contentTypeTables[$contentType];
            
            // Check if progress already exists for this prerequisite
            $checkSql = "SELECT id FROM {$table} 
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->execute([$userId, $courseId, $prerequisiteId, $clientId]);
            
            if ($checkStmt->fetch()) {
                error_log("[SharedContent] Prerequisite {$contentType} progress already exists for prerequisite {$prerequisiteId}");
                return;
            }
            
            // Create new progress entry based on content type
            $this->insertPrerequisiteProgressByType($userId, $courseId, $prerequisiteId, $clientId, $contentType, $progressData);
            
            error_log("[SharedContent] Created prerequisite {$contentType} progress for prerequisite {$prerequisiteId}");
            
        } catch (Exception $e) {
            error_log("[SharedContent] Error creating prerequisite {$contentType} progress: " . $e->getMessage());
        }
    }
    
    /**
     * Insert module progress based on content type
     */
    private function insertModuleProgressByType($userId, $courseId, $moduleContent, $clientId, $contentType, $progressData) {
        switch ($contentType) {
            case 'video':
                $this->insertVideoModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData);
                break;
            case 'audio':
                $this->insertAudioModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData);
                break;
            case 'document':
                $this->insertDocumentModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData);
                break;
            case 'image':
                $this->insertImageModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData);
                break;
            case 'scorm':
                $this->insertScormModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData);
                break;
            case 'external':
                $this->insertExternalModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData);
                break;
            case 'interactive':
                $this->insertInteractiveModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData);
                break;
            case 'assessment':
                $this->insertAssessmentModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData);
                break;
            case 'assignment':
                $this->insertAssignmentModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData);
                break;
            case 'survey':
                $this->insertSurveyModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData);
                break;
            case 'feedback':
                $this->insertFeedbackModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData);
                break;
        }
    }
    
    /**
     * Insert prerequisite progress based on content type
     */
    private function insertPrerequisiteProgressByType($userId, $courseId, $prerequisiteId, $clientId, $contentType, $progressData) {
        switch ($contentType) {
            case 'video':
                $this->insertVideoPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData);
                break;
            case 'audio':
                $this->insertAudioPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData);
                break;
            case 'document':
                $this->insertDocumentPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData);
                break;
            case 'image':
                $this->insertImagePrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData);
                break;
            case 'scorm':
                $this->insertScormPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData);
                break;
            case 'external':
                $this->insertExternalPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData);
                break;
            case 'interactive':
                $this->insertInteractivePrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData);
                break;
            case 'assessment':
                $this->insertAssessmentPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData);
                break;
            case 'assignment':
                $this->insertAssignmentPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData);
                break;
            case 'survey':
                $this->insertSurveyPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData);
                break;
            case 'feedback':
                $this->insertFeedbackPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData);
                break;
        }
    }
    
    // Video Progress Methods
    private function insertVideoModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData) {
        $sql = "INSERT INTO video_progress (
                    user_id, course_id, content_id, video_package_id, client_id,
                    started_at, `current_time`, duration, watched_percentage, completion_threshold,
                    is_completed, video_status, play_count, last_watched_at, bookmarks, notes,
                    completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $moduleContent['content_id'], $progressData['video_package_id'], $clientId,
            $progressData['started_at'], $progressData['current_time'], $progressData['duration'], 
            $progressData['watched_percentage'], $progressData['completion_threshold'],
            $progressData['is_completed'], $progressData['video_status'], $progressData['play_count'], 
            $progressData['last_watched_at'], $progressData['bookmarks'] ?? '', $progressData['notes'] ?? '',
            $progressData['completed_at']
        ]);
    }
    
    private function insertVideoPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData) {
        $sql = "INSERT INTO video_progress (
                    user_id, course_id, prerequisite_id, video_package_id, client_id,
                    started_at, `current_time`, duration, watched_percentage, completion_threshold,
                    is_completed, video_status, play_count, last_watched_at, bookmarks, notes,
                    completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $prerequisiteId, $progressData['video_package_id'], $clientId,
            $progressData['started_at'], $progressData['current_time'], $progressData['duration'], 
            $progressData['watched_percentage'], $progressData['completion_threshold'],
            $progressData['is_completed'], $progressData['video_status'], $progressData['play_count'], 
            $progressData['last_watched_at'], $progressData['bookmarks'] ?? '', $progressData['notes'] ?? '',
            $progressData['completed_at']
        ]);
    }
    
    // Audio Progress Methods
    private function insertAudioModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData) {
        $sql = "INSERT INTO audio_progress (
                    user_id, course_id, content_id, audio_package_id, client_id,
                    started_at, current_time, duration, listened_percentage, completion_threshold,
                    is_completed, audio_status, playback_status, play_count, last_listened_at,
                    playback_speed, notes, completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $moduleContent['content_id'], $progressData['audio_package_id'], $clientId,
            $progressData['started_at'], $progressData['current_time'], $progressData['duration'], 
            $progressData['listened_percentage'], $progressData['completion_threshold'],
            $progressData['is_completed'], $progressData['audio_status'], $progressData['playback_status'] ?? 'not_started', 
            $progressData['play_count'], $progressData['last_listened_at'], $progressData['playback_speed'] ?? 1.0,
            $progressData['notes'] ?? '', $progressData['completed_at']
        ]);
    }
    
    private function insertAudioPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData) {
        $sql = "INSERT INTO audio_progress (
                    user_id, course_id, prerequisite_id, audio_package_id, client_id,
                    started_at, current_time, duration, listened_percentage, completion_threshold,
                    is_completed, audio_status, playback_status, play_count, last_listened_at,
                    playback_speed, notes, completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $prerequisiteId, $progressData['audio_package_id'], $clientId,
            $progressData['started_at'], $progressData['current_time'], $progressData['duration'], 
            $progressData['listened_percentage'], $progressData['completion_threshold'],
            $progressData['is_completed'], $progressData['audio_status'], $progressData['playback_status'] ?? 'not_started', 
            $progressData['play_count'], $progressData['last_listened_at'], $progressData['playback_speed'] ?? 1.0,
            $progressData['notes'] ?? '', $progressData['completed_at']
        ]);
    }
    
    // Document Progress Methods
    private function insertDocumentModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData) {
        $sql = "INSERT INTO document_progress (
                    user_id, course_id, content_id, document_package_id, client_id,
                    started_at, current_page, total_pages, pages_viewed, viewed_percentage,
                    completion_threshold, is_completed, status, time_spent, last_viewed_at,
                    completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $moduleContent['content_id'], $progressData['document_package_id'], $clientId,
            $progressData['started_at'], $progressData['current_page'], $progressData['total_pages'], 
            $progressData['pages_viewed'], $progressData['viewed_percentage'], $progressData['completion_threshold'],
            $progressData['is_completed'], $progressData['status'], $progressData['time_spent'], 
            $progressData['last_viewed_at'], $progressData['completed_at']
        ]);
    }
    
    private function insertDocumentPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData) {
        $sql = "INSERT INTO document_progress (
                    user_id, course_id, prerequisite_id, document_package_id, client_id,
                    started_at, current_page, total_pages, pages_viewed, viewed_percentage,
                    completion_threshold, is_completed, status, time_spent, last_viewed_at,
                    completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $prerequisiteId, $progressData['document_package_id'], $clientId,
            $progressData['started_at'], $progressData['current_page'], $progressData['total_pages'], 
            $progressData['pages_viewed'], $progressData['viewed_percentage'], $progressData['completion_threshold'],
            $progressData['is_completed'], $progressData['status'], $progressData['time_spent'], 
            $progressData['last_viewed_at'], $progressData['completed_at']
        ]);
    }
    
    // Image Progress Methods
    private function insertImageModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData) {
        $sql = "INSERT INTO image_progress (
                    user_id, course_id, content_id, image_package_id, client_id,
                    started_at, image_status, is_completed, view_count, viewed_at,
                    created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $moduleContent['content_id'], $progressData['image_package_id'], $clientId,
            $progressData['started_at'], $progressData['image_status'] ?? 'viewed', 
            $progressData['is_completed'], $progressData['view_count'] ?? 1, 
            $progressData['viewed_at'] ?? date('Y-m-d H:i:s')
        ]);
    }
    
    private function insertImagePrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData) {
        $sql = "INSERT INTO image_progress (
                    user_id, course_id, prerequisite_id, image_package_id, client_id,
                    started_at, image_status, is_completed, view_count, viewed_at,
                    completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $prerequisiteId, $progressData['image_package_id'], $clientId,
            $progressData['started_at'], $progressData['image_status'] ?? 'viewed', 
            $progressData['is_completed'], $progressData['view_count'] ?? 1, 
            $progressData['viewed_at'] ?? date('Y-m-d H:i:s'), $progressData['completed_at'] ?? date('Y-m-d H:i:s')
        ]);
    }
    
    // SCORM Progress Methods
    private function insertScormModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData) {
        $sql = "INSERT INTO scorm_progress (
                    user_id, course_id, content_id, scorm_package_id, client_id,
                    lesson_status, lesson_location, score_raw, score_min, score_max,
                    total_time, session_time, suspend_data, launch_data, interactions, objectives,
                    completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $moduleContent['content_id'], $progressData['scorm_package_id'], $clientId,
            $progressData['lesson_status'], $progressData['lesson_location'] ?? '', 
            $progressData['score_raw'] ?? 0, $progressData['score_min'] ?? 0, $progressData['score_max'] ?? 100,
            $progressData['total_time'] ?? '', $progressData['session_time'] ?? '', 
            $progressData['suspend_data'] ?? '', $progressData['launch_data'] ?? '', 
            $progressData['interactions'] ?? '', $progressData['objectives'] ?? '',
            $progressData['completed_at']
        ]);
    }
    
    private function insertScormPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData) {
        $sql = "INSERT INTO scorm_progress (
                    user_id, course_id, prerequisite_id, scorm_package_id, client_id,
                    lesson_status, lesson_location, score_raw, score_min, score_max,
                    total_time, session_time, suspend_data, launch_data, interactions, objectives,
                    completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $prerequisiteId, $progressData['scorm_package_id'], $clientId,
            $progressData['lesson_status'], $progressData['lesson_location'] ?? '', 
            $progressData['score_raw'] ?? 0, $progressData['score_min'] ?? 0, $progressData['score_max'] ?? 100,
            $progressData['total_time'] ?? '', $progressData['session_time'] ?? '', 
            $progressData['suspend_data'] ?? '', $progressData['launch_data'] ?? '', 
            $progressData['interactions'] ?? '', $progressData['objectives'] ?? '',
            $progressData['completed_at']
        ]);
    }
    
    // External Progress Methods
    private function insertExternalModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData) {
        $sql = "INSERT INTO external_progress (
                    user_id, course_id, content_id, external_package_id, client_id,
                    started_at, time_spent, is_completed, external_url, visit_count,
                    last_visited_at, completed_at, completion_notes, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $moduleContent['content_id'], $moduleContent['actual_content_id'], $clientId,
            $progressData['started_at'] ?? null, $progressData['time_spent'] ?? 0, $progressData['is_completed'] ?? 0, 
            $progressData['external_url'] ?? '', $progressData['visit_count'] ?? 0,
            $progressData['last_visited_at'] ?? null, $progressData['completed_at'] ?? null, 
            $progressData['completion_notes'] ?? null
        ]);
    }
    
    private function insertExternalPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData) {
        // Get the external_package_id from the prerequisite
        $stmt = $this->conn->prepare("SELECT prerequisite_id FROM course_prerequisites WHERE id = ?");
        $stmt->execute([$prerequisiteId]);
        $externalPackageId = $stmt->fetchColumn();
        
        $sql = "INSERT INTO external_progress (
                    user_id, course_id, prerequisite_id, external_package_id, client_id,
                    started_at, time_spent, is_completed, external_url, visit_count,
                    last_visited_at, completed_at, completion_notes, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $prerequisiteId, $externalPackageId, $clientId,
            $progressData['started_at'] ?? null, $progressData['time_spent'] ?? 0, $progressData['is_completed'] ?? 0, 
            $progressData['external_url'] ?? '', $progressData['visit_count'] ?? 0,
            $progressData['last_visited_at'] ?? null, $progressData['completed_at'] ?? null, 
            $progressData['completion_notes'] ?? null
        ]);
    }
    
    // Interactive Progress Methods
    private function insertInteractiveModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData) {
        $sql = "INSERT INTO interactive_progress (
                    user_id, course_id, content_id, client_id,
                    started_at, time_spent, is_completed, status, last_accessed_at,
                    completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $moduleContent['content_id'], $clientId,
            $progressData['started_at'], $progressData['time_spent'], $progressData['is_completed'], 
            $progressData['status'], $progressData['last_accessed_at'], $progressData['completed_at']
        ]);
    }
    
    private function insertInteractivePrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData) {
        $sql = "INSERT INTO interactive_progress (
                    user_id, course_id, prerequisite_id, client_id,
                    started_at, time_spent, is_completed, status, last_accessed_at,
                    completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $prerequisiteId, $clientId,
            $progressData['started_at'], $progressData['time_spent'], $progressData['is_completed'], 
            $progressData['status'], $progressData['last_accessed_at'], $progressData['completed_at']
        ]);
    }
    
    // Assessment Progress Methods
    private function insertAssessmentModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData) {
        $sql = "INSERT INTO assessment_results (
                    user_id, course_id, assessment_id, client_id,
                    score, max_score, percentage, passed, completed_at,
                    created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $moduleContent['actual_content_id'], $clientId,
            $progressData['score'], $progressData['max_score'], $progressData['percentage'], 
            $progressData['passed'], $progressData['completed_at']
        ]);
    }
    
    private function insertAssessmentPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData) {
        $sql = "INSERT INTO assessment_results (
                    user_id, course_id, assessment_id, client_id,
                    score, max_score, percentage, passed, completed_at,
                    created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $progressData['assessment_id'], $clientId,
            $progressData['score'], $progressData['max_score'], $progressData['percentage'], 
            $progressData['passed'], $progressData['completed_at']
        ]);
    }
    
    // Assignment Progress Methods
    private function updateExistingAssignmentSubmission($submissionId, $progressData) {
        try {
            // Get the submission details from the prerequisite submission instead of progressData
            // because progressData might not contain the actual submission details
            $prereqStmt = $this->conn->prepare("
                SELECT submission_type, submission_file, submission_text, submission_url
                FROM assignment_submissions 
                WHERE assignment_package_id = ? AND prerequisite_id IS NOT NULL 
                ORDER BY created_at DESC LIMIT 1
            ");
            $prereqStmt->execute([$progressData['assignment_package_id']]);
            $prereqDetails = $prereqStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($prereqDetails) {
                // Update existing assignment submission with submission details from prerequisite
                $updateSql = "UPDATE assignment_submissions SET 
                                submission_type = ?, submission_file = ?, submission_text = ?, submission_url = ?,
                                updated_at = NOW()
                              WHERE id = ? AND (submission_type IS NULL OR submission_file IS NULL OR submission_text IS NULL OR submission_url IS NULL)";
                
                $stmt = $this->conn->prepare($updateSql);
                $stmt->execute([
                    $prereqDetails['submission_type'],
                    $prereqDetails['submission_file'],
                    $prereqDetails['submission_text'],
                    $prereqDetails['submission_url'],
                    $submissionId
                ]);
                
                if ($stmt->rowCount() > 0) {
                    error_log("[SharedContent] Updated assignment submission {$submissionId} with submission details from prerequisite");
                } else {
                    error_log("[SharedContent] Assignment submission {$submissionId} already has all submission details");
                }
            } else {
                error_log("[SharedContent] No prerequisite submission found for assignment package {$progressData['assignment_package_id']}");
            }
            
        } catch (Exception $e) {
            error_log("[SharedContent] Error updating existing assignment submission: " . $e->getMessage());
        }
    }
    
    private function insertAssignmentModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData) {
        // For assignments, we need to handle the unique constraint differently
        // Check if there's already a submission for this assignment package
        $checkStmt = $this->conn->prepare("
            SELECT id, attempt_number, submission_type, submission_file, submission_text, submission_url,
                   due_date, is_late, started_at
            FROM assignment_submissions 
            WHERE user_id = ? AND course_id = ? AND assignment_package_id = ? AND client_id = ?
            ORDER BY attempt_number DESC LIMIT 1
        ");
        $checkStmt->execute([$userId, $courseId, $progressData['assignment_package_id'], $clientId]);
        $existingSubmission = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingSubmission) {
            // Create new submission with next attempt number for module context
            $nextAttempt = $existingSubmission['attempt_number'] + 1;
            
            $sql = "INSERT INTO assignment_submissions (
                        user_id, course_id, assignment_package_id, client_id,
                        prerequisite_id, content_id, postrequisite_id,
                        submission_type, submission_file, submission_text, submission_url,
                        submission_status, due_date, is_late, attempt_number,
                        started_at, submitted_at, completed_at, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                    )";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $userId, $courseId, $progressData['assignment_package_id'], $clientId,
                null, $moduleContent['content_id'], null, // Set content_id for module content
                $existingSubmission['submission_type'], $existingSubmission['submission_file'], 
                $existingSubmission['submission_text'], $existingSubmission['submission_url'],
                $progressData['submission_status'] ?? 'submitted', $existingSubmission['due_date'], 
                $existingSubmission['is_late'], $nextAttempt,
                $existingSubmission['started_at'], $progressData['submitted_at'], $progressData['completed_at'] ?? null
            ]);
            
            error_log("[SharedContent] Created new assignment submission for module context with attempt {$nextAttempt} (copied submission details from prerequisite)");
        } else {
            // No existing submission - create first one
            $sql = "INSERT INTO assignment_submissions (
                        user_id, course_id, assignment_package_id, client_id,
                        prerequisite_id, content_id, postrequisite_id,
                        submission_type, submission_file, submission_text, submission_url,
                        submission_status, due_date, is_late, attempt_number,
                        started_at, submitted_at, completed_at, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                    )";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $userId, $courseId, $progressData['assignment_package_id'], $clientId,
                null, $moduleContent['content_id'], null, // Set content_id for module content
                $progressData['submission_type'] ?? null, $progressData['submission_file'] ?? null, 
                $progressData['submission_text'] ?? null, $progressData['submission_url'] ?? null,
                $progressData['submission_status'] ?? 'submitted', $progressData['due_date'] ?? null, 
                $progressData['is_late'] ?? null, 1,
                $progressData['started_at'] ?? null, $progressData['submitted_at'], $progressData['completed_at'] ?? null
            ]);
            
            error_log("[SharedContent] Created new assignment submission for module context with attempt 1");
        }
    }
    
    private function insertAssignmentPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData) {
        $sql = "INSERT INTO assignment_submissions (
                    user_id, course_id, assignment_package_id, client_id,
                    prerequisite_id, content_id, postrequisite_id,
                    submission_status, submitted_at, completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $progressData['assignment_package_id'], $clientId,
            $prerequisiteId, null, null, // Set prerequisite_id for prerequisite content
            $progressData['submission_status'] ?? 'submitted', $progressData['submitted_at'], $progressData['completed_at'] ?? null
        ]);
    }
    
    // Survey Progress Methods
    private function insertSurveyModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData) {
        $sql = "INSERT INTO course_survey_responses (
                    user_id, course_id, survey_package_id, client_id,
                    completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $moduleContent['actual_content_id'], $clientId,
            $progressData['completed_at']
        ]);
    }
    
    private function insertSurveyPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData) {
        $sql = "INSERT INTO course_survey_responses (
                    user_id, course_id, survey_package_id, client_id,
                    completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $progressData['survey_package_id'], $clientId,
            $progressData['completed_at']
        ]);
    }
    
    // Feedback Progress Methods
    private function insertFeedbackModuleProgress($userId, $courseId, $moduleContent, $clientId, $progressData) {
        $sql = "INSERT INTO course_feedback_responses (
                    user_id, course_id, feedback_package_id, client_id,
                    completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $moduleContent['actual_content_id'], $clientId,
            $progressData['completed_at']
        ]);
    }
    
    private function insertFeedbackPrerequisiteProgress($userId, $courseId, $prerequisiteId, $clientId, $progressData) {
        $sql = "INSERT INTO course_feedback_responses (
                    user_id, course_id, feedback_package_id, client_id,
                    completed_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, NOW(), NOW()
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, $courseId, $progressData['feedback_package_id'], $clientId,
            $progressData['completed_at']
        ]);
    }
}
?>
