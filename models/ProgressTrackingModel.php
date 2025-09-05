<?php
require_once 'config/Database.php';

class ProgressTrackingModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // =====================================================
    // COURSE PROGRESS MANAGEMENT
    // =====================================================

    /**
     * Initialize or get user course progress
     */
    public function initializeCourseProgress($userId, $courseId, $clientId) {
        try {
            // Check if progress already exists
            $stmt = $this->conn->prepare("
                SELECT * FROM user_course_progress 
                WHERE user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $clientId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update last accessed time
                $stmt = $this->conn->prepare("
                    UPDATE user_course_progress 
                    SET last_accessed_at = NOW(), updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$existing['id']]);
                return $existing;
            } else {
                // Create new progress record
                $stmt = $this->conn->prepare("
                    INSERT INTO user_course_progress 
                    (user_id, course_id, client_id, status, started_at, last_accessed_at)
                    VALUES (?, ?, ?, 'not_started', NOW(), NOW())
                ");
                $stmt->execute([$userId, $courseId, $clientId]);
                
                $progressId = $this->conn->lastInsertId();
                
                // Initialize module and content progress
                $this->initializeModuleProgress($userId, $courseId, $clientId);
                $this->initializeContentProgress($userId, $courseId, $clientId);
                
                return $this->getCourseProgress($userId, $courseId, $clientId);
            }
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::initializeCourseProgress error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get course progress for a user
     */
    public function getCourseProgress($userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM user_course_progress 
                WHERE user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $clientId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::getCourseProgress error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update course progress status
     */
    public function updateCourseProgress($userId, $courseId, $clientId, $data) {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if (in_array($key, ['status', 'completion_percentage', 'current_module_id', 'current_content_id', 'resume_position'])) {
                    $fields[] = "`$key` = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $fields[] = "`updated_at` = NOW()";
            $values[] = $userId;
            $values[] = $courseId;
            $values[] = $clientId;
            
            $sql = "UPDATE user_course_progress SET " . implode(', ', $fields) . 
                   " WHERE user_id = ? AND course_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::updateCourseProgress error: " . $e->getMessage());
            return false;
        }
    }

    // =====================================================
    // MODULE PROGRESS MANAGEMENT
    // =====================================================

    /**
     * Initialize module progress for all modules in a course
     */
    private function initializeModuleProgress($userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id FROM course_modules 
                WHERE course_id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
            ");
            $stmt->execute([$courseId]);
            $modules = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($modules as $moduleId) {
                $this->initializeSingleModuleProgress($userId, $courseId, $moduleId, $clientId);
            }
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::initializeModuleProgress error: " . $e->getMessage());
        }
    }

    /**
     * Initialize progress for a single module
     */
    private function initializeSingleModuleProgress($userId, $courseId, $moduleId, $clientId) {
        // Module progress tracking removed - no longer using module_progress table
        error_log("ProgressTrackingModel::initializeSingleModuleProgress - module_progress table removed, skipping initialization");
        return true;
    }

    /**
     * Update module progress
     */
    public function updateModuleProgress($userId, $courseId, $moduleId, $clientId, $data) {
        // Module progress tracking removed - no longer using module_progress table
        error_log("ProgressTrackingModel::updateModuleProgress - module_progress table removed, skipping update");
        return true;
    }

    // =====================================================
    // CONTENT PROGRESS MANAGEMENT
    // =====================================================

    /**
     * Initialize content progress for all content in a course
     */
    private function initializeContentProgress($userId, $courseId, $clientId) {
        try {
            error_log("DEBUG: initializeContentProgress called - userId: $userId, courseId: $courseId, clientId: $clientId");
            
            $stmt = $this->conn->prepare("
                SELECT cmc.id, cmc.content_type 
                FROM course_module_content cmc
                JOIN course_modules cm ON cmc.module_id = cm.id
                WHERE cm.course_id = ? AND (cmc.deleted_at IS NULL OR cmc.deleted_at = '0000-00-00 00:00:00')
            ");
            $stmt->execute([$courseId]);
            $contentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("DEBUG: Found " . count($contentItems) . " content items to initialize");

            foreach ($contentItems as $content) {
                error_log("DEBUG: Initializing content - ID: {$content['id']}, Type: {$content['content_type']}");
                $this->initializeSingleContentProgress($userId, $courseId, $content['id'], $content['content_type'], $clientId);
            }
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::initializeContentProgress error: " . $e->getMessage());
        }
    }

    /**
     * Initialize progress for a single content item
     */
    private function initializeSingleContentProgress($userId, $courseId, $contentId, $contentType, $clientId) {
        try {
            error_log("DEBUG: initializeSingleContentProgress called - userId: $userId, courseId: $courseId, contentId: $contentId, contentType: $contentType, clientId: $clientId");
            
            // Initialize general content progress
            $stmt = $this->conn->prepare("
                INSERT IGNORE INTO content_progress 
                (user_id, course_id, content_id, content_type, client_id, status, completion_percentage)
                VALUES (?, ?, ?, ?, ?, 'not_started', 0.00)
            ");
            $result = $stmt->execute([$userId, $courseId, $contentId, $contentType, $clientId]);
            error_log("DEBUG: Content progress insert result: " . ($result ? 'SUCCESS' : 'FAILED'));

            // Initialize content-specific progress based on type
            error_log("DEBUG: Calling initializeContentSpecificProgress for type: $contentType");
            $this->initializeContentSpecificProgress($userId, $courseId, $contentId, $contentType, $clientId);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::initializeSingleContentProgress error: " . $e->getMessage());
        }
    }

    /**
     * Initialize content-specific progress tracking
     */
    private function initializeContentSpecificProgress($userId, $courseId, $contentId, $contentType, $clientId) {
        try {
            error_log("DEBUG: initializeContentSpecificProgress called - userId: $userId, courseId: $courseId, contentId: $contentId, contentType: $contentType, clientId: $clientId");
            
            switch ($contentType) {
                case 'scorm':
                    error_log("DEBUG: Initializing SCORM progress for content ID: $contentId");
                    $this->initializeScormProgress($userId, $courseId, $contentId, $clientId);
                    break;
                case 'assignment':
                    error_log("DEBUG: Initializing assignment progress for content ID: $contentId");
                    $this->initializeAssignmentProgress($userId, $courseId, $contentId, $clientId);
                    break;
                case 'video':
                    error_log("DEBUG: Initializing video progress for content ID: $contentId");
                    $this->initializeVideoProgress($userId, $courseId, $contentId, $clientId);
                    break;
                case 'audio':
                    error_log("DEBUG: Initializing audio progress for content ID: $contentId");
                    $this->initializeAudioProgress($userId, $courseId, $contentId, $clientId);
                    break;
                case 'document':
                    error_log("DEBUG: Initializing document progress for content ID: $contentId");
                    $this->initializeDocumentProgress($userId, $courseId, $contentId, $clientId);
                    break;
                case 'interactive':
                    error_log("DEBUG: Initializing interactive progress for content ID: $contentId");
                    $this->initializeInteractiveProgress($userId, $courseId, $contentId, $clientId);
                    break;
                case 'external':
                    error_log("DEBUG: Initializing external progress for content ID: $contentId");
                    $this->initializeExternalProgress($userId, $courseId, $contentId, $clientId);
                    break;
                default:
                    error_log("DEBUG: Unknown content type: $contentType for content ID: $contentId");
                    break;
            }
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::initializeContentSpecificProgress error: " . $e->getMessage());
        }
    }

    // =====================================================
    // SCORM PROGRESS TRACKING
    // =====================================================

    /**
     * Initialize SCORM progress tracking
     */
    public function initializeScormProgress($userId, $courseId, $contentId, $clientId) {
        try {
            error_log("DEBUG: initializeScormProgress called - userId: $userId, courseId: $courseId, contentId: $contentId, clientId: $clientId");
            
            // Get SCORM package ID from course_module_content
            // The contentId parameter is actually the course_module_content.id
            // We need to find the scorm_package_id from the scorm_packages table
            $stmt = $this->conn->prepare("
                SELECT sp.id as scorm_package_id 
                FROM scorm_packages sp
                INNER JOIN course_module_content cmc ON cmc.content_id = sp.id AND cmc.content_type = 'scorm'
                WHERE cmc.id = ?
            ");
            $stmt->execute([$contentId]);
            $scormPackageId = $stmt->fetchColumn();
            
            error_log("DEBUG: SCORM package ID found: " . ($scormPackageId ?: 'NULL'));

            if ($scormPackageId) {
                $stmt = $this->conn->prepare("
                    INSERT IGNORE INTO scorm_progress 
                    (user_id, course_id, content_id, scorm_package_id, client_id, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $result = $stmt->execute([$userId, $courseId, $contentId, $scormPackageId, $clientId]);
                error_log("DEBUG: SCORM progress insert result: " . ($result ? 'SUCCESS' : 'FAILED'));
                
                if ($result) {
                    $insertId = $this->conn->lastInsertId();
                    error_log("DEBUG: SCORM progress record ID: " . $insertId);
                }
            } else {
                error_log("DEBUG: No SCORM package found for content ID: $contentId");
            }
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::initializeScormProgress error: " . $e->getMessage());
        }
    }

    /**
     * Initialize assignment progress
     */
    public function initializeAssignmentProgress($userId, $courseId, $contentId, $clientId) {
        try {
            error_log("DEBUG: initializeAssignmentProgress called - userId: $userId, courseId: $courseId, contentId: $contentId, clientId: $clientId");
            
            // Get assignment package ID from course_module_content
            // The contentId parameter is actually the course_module_content.id
            // We need to find the assignment_package_id from the assignment_package table
            $stmt = $this->conn->prepare("
                SELECT ap.id as assignment_package_id 
                FROM assignment_package ap
                INNER JOIN course_module_content cmc ON cmc.content_id = ap.id AND cmc.content_type = 'assignment'
                WHERE cmc.id = ?
            ");
            $stmt->execute([$contentId]);
            $assignmentPackageId = $stmt->fetchColumn();
            
            error_log("DEBUG: Assignment package ID found: " . ($assignmentPackageId ?: 'NULL'));

            if ($assignmentPackageId) {
                // For assignments, we don't need a separate progress table
                // Progress is tracked through assignment submissions
                // Just log that we found the assignment
                error_log("DEBUG: Assignment progress tracking initialized for assignment package ID: $assignmentPackageId");
            } else {
                error_log("DEBUG: No assignment package found for content ID: $contentId");
            }
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::initializeAssignmentProgress error: " . $e->getMessage());
        }
    }

    /**
     * Update SCORM progress
     */
    public function updateScormProgress($userId, $courseId, $contentId, $clientId, $data) {
        try {
            error_log("DEBUG: updateScormProgress called - userId: $userId, courseId: $courseId, contentId: $contentId, clientId: $clientId");
            error_log("DEBUG: updateScormProgress data: " . json_encode($data));
            
            // First, check if SCORM progress record exists
            $stmt = $this->conn->prepare("SELECT id FROM scorm_progress WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?");
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            $existingRecord = $stmt->fetch();
            
            if (!$existingRecord) {
                error_log("DEBUG: updateScormProgress - no existing record, creating one first");
                // Create the record first
                $this->initializeScormProgress($userId, $courseId, $contentId, $clientId);
            }
            
            $fields = [];
            $values = [];
            
            $allowedFields = [
                'lesson_status', 'lesson_location', 'score_raw', 'score_min', 'score_max',
                'total_time', 'session_time', 'suspend_data', 'launch_data', 'interactions', 'objectives'
            ];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "`$key` = ?";
                    $values[] = $value;
                }
            }
            
            error_log("DEBUG: updateScormProgress fields to update: " . json_encode($fields));
            
            if (empty($fields)) {
                error_log("DEBUG: updateScormProgress - no valid fields to update");
                return false;
            }
            
            $fields[] = "`updated_at` = NOW()";
            $values[] = (int)$userId;
            $values[] = (int)$courseId;
            $values[] = (int)$contentId;
            $values[] = (int)$clientId;
            
            $sql = "UPDATE scorm_progress SET " . implode(', ', $fields) . 
                   " WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            error_log("DEBUG: updateScormProgress SQL: " . $sql);
            error_log("DEBUG: updateScormProgress values: " . json_encode($values));
            
            // Debug: Check if the record exists before updating
            $checkStmt = $this->conn->prepare("SELECT id FROM scorm_progress WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?");
            $checkStmt->execute([(int)$userId, (int)$courseId, (int)$contentId, (int)$clientId]);
            $existingRecord = $checkStmt->fetch();
            error_log("DEBUG: updateScormProgress - Record exists check: " . ($existingRecord ? 'YES' : 'NO'));
            if ($existingRecord) {
                error_log("DEBUG: updateScormProgress - Existing record ID: " . $existingRecord['id']);
            }
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($values);
            
            if ($result) {
                $rowCount = $stmt->rowCount();
                error_log("DEBUG: updateScormProgress SUCCESS - rows affected: $rowCount");
            } else {
                error_log("DEBUG: updateScormProgress FAILED - execute returned false");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::updateScormProgress error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update assignment progress
     */
    public function updateAssignmentProgress($userId, $courseId, $contentId, $clientId, $data) {
        try {
            error_log("DEBUG: updateAssignmentProgress called - userId: $userId, courseId: $courseId, contentId: $contentId, clientId: $clientId");
            error_log("DEBUG: updateAssignmentProgress data: " . json_encode($data));
            
            // For assignments, progress is tracked through assignment submissions
            // We don't need to update a separate progress table
            // The progress is calculated based on submission status in getAssignmentProgress method
            
            // Just log that the assignment progress was updated
            error_log("DEBUG: updateAssignmentProgress - Assignment progress tracking completed");
            
            return true;
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::updateAssignmentProgress error: " . $e->getMessage());
            return false;
        }
    }

    // =====================================================
    // VIDEO PROGRESS TRACKING
    // =====================================================

    /**
     * Initialize video progress tracking
     */
    private function initializeVideoProgress($userId, $courseId, $contentId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT content_id FROM course_module_content 
                WHERE id = ? AND content_type = 'video'
            ");
            $stmt->execute([$contentId]);
            $videoPackageId = $stmt->fetchColumn();

            if ($videoPackageId) {
                $stmt = $this->conn->prepare("
                    INSERT IGNORE INTO video_progress 
                    (user_id, course_id, content_id, video_package_id, client_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $courseId, $contentId, $videoPackageId, $clientId]);
            }
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::initializeVideoProgress error: " . $e->getMessage());
        }
    }

    /**
     * Update video progress
     */
    public function updateVideoProgress($userId, $courseId, $contentId, $clientId, $data) {
        try {
            $fields = [];
            $values = [];
            
            $allowedFields = [
                'current_time', 'duration', 'watched_percentage', 'is_completed',
                'play_count', 'last_watched_at', 'bookmarks', 'notes'
            ];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "`$key` = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $fields[] = "`updated_at` = NOW()";
            $values[] = $userId;
            $values[] = $courseId;
            $values[] = $contentId;
            $values[] = $clientId;
            
            $sql = "UPDATE video_progress SET " . implode(', ', $fields) . 
                   " WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::updateVideoProgress error: " . $e->getMessage());
            return false;
        }
    }

    // =====================================================
    // AUDIO PROGRESS TRACKING
    // =====================================================

    /**
     * Initialize audio progress tracking
     */
    private function initializeAudioProgress($userId, $courseId, $contentId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT content_id FROM course_module_content 
                WHERE id = ? AND content_type = 'audio'
            ");
            $stmt->execute([$contentId]);
            $audioPackageId = $stmt->fetchColumn();

            if ($audioPackageId) {
                $stmt = $this->conn->prepare("
                    INSERT IGNORE INTO audio_progress 
                    (user_id, course_id, content_id, audio_package_id, client_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $courseId, $contentId, $audioPackageId, $clientId]);
            }
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::initializeAudioProgress error: " . $e->getMessage());
        }
    }

    /**
     * Update audio progress
     */
    public function updateAudioProgress($userId, $courseId, $contentId, $clientId, $data) {
        try {
            $fields = [];
            $values = [];
            
            $allowedFields = [
                'current_time', 'duration', 'listened_percentage', 'is_completed',
                'play_count', 'last_listened_at', 'playback_speed', 'notes'
            ];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "`$key` = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $fields[] = "`updated_at` = NOW()";
            $values[] = $userId;
            $values[] = $courseId;
            $values[] = $contentId;
            $values[] = $clientId;
            
            $sql = "UPDATE audio_progress SET " . implode(', ', $fields) . 
                   " WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::updateAudioProgress error: " . $e->getMessage());
            return false;
        }
    }

    // =====================================================
    // DOCUMENT PROGRESS TRACKING
    // =====================================================

    /**
     * Initialize document progress tracking
     */
    private function initializeDocumentProgress($userId, $courseId, $contentId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT content_id FROM course_module_content 
                WHERE id = ? AND content_type = 'document'
            ");
            $stmt->execute([$contentId]);
            $documentPackageId = $stmt->fetchColumn();

            if ($documentPackageId) {
                $stmt = $this->conn->prepare("
                    INSERT IGNORE INTO document_progress 
                    (user_id, course_id, content_id, document_package_id, client_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $courseId, $contentId, $documentPackageId, $clientId]);
            }
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::initializeDocumentProgress error: " . $e->getMessage());
        }
    }

    /**
     * Update document progress
     */
    public function updateDocumentProgress($userId, $courseId, $contentId, $clientId, $data) {
        try {
            $fields = [];
            $values = [];
            
            $allowedFields = [
                'current_page', 'total_pages', 'pages_viewed', 'viewed_percentage',
                'is_completed', 'time_spent', 'last_viewed_at', 'bookmarks', 'notes'
            ];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "`$key` = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $fields[] = "`updated_at` = NOW()";
            $values[] = $userId;
            $values[] = $courseId;
            $values[] = $contentId;
            $values[] = $clientId;
            
            $sql = "UPDATE document_progress SET " . implode(', ', $fields) . 
                   " WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::updateDocumentProgress error: " . $e->getMessage());
            return false;
        }
    }

    // =====================================================
    // INTERACTIVE CONTENT PROGRESS TRACKING
    // =====================================================

    /**
     * Initialize interactive content progress tracking
     */
    private function initializeInteractiveProgress($userId, $courseId, $contentId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT content_id FROM course_module_content 
                WHERE id = ? AND content_type = 'interactive'
            ");
            $stmt->execute([$contentId]);
            $interactivePackageId = $stmt->fetchColumn();

            if ($interactivePackageId) {
                $stmt = $this->conn->prepare("
                    INSERT IGNORE INTO interactive_progress 
                    (user_id, course_id, content_id, interactive_package_id, client_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $courseId, $contentId, $interactivePackageId, $clientId]);
            }
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::initializeInteractiveProgress error: " . $e->getMessage());
        }
    }

    /**
     * Update interactive content progress
     */
    public function updateInteractiveProgress($userId, $courseId, $contentId, $clientId, $data) {
        try {
            $fields = [];
            $values = [];
            
            $allowedFields = [
                'interaction_data', 'current_step', 'total_steps', 'completion_percentage',
                'is_completed', 'time_spent', 'last_interaction_at', 'user_responses', 'ai_feedback'
            ];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "`$key` = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $fields[] = "`updated_at` = NOW()";
            $values[] = $userId;
            $values[] = $courseId;
            $values[] = $contentId;
            $values[] = $clientId;
            
            $sql = "UPDATE interactive_progress SET " . implode(', ', $fields) . 
                   " WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::updateInteractiveProgress error: " . $e->getMessage());
            return false;
        }
    }

    // =====================================================
    // EXTERNAL CONTENT PROGRESS TRACKING
    // =====================================================

    /**
     * Initialize external content progress tracking
     */
    private function initializeExternalProgress($userId, $courseId, $contentId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT content_id FROM course_module_content 
                WHERE id = ? AND content_type = 'external'
            ");
            $stmt->execute([$contentId]);
            $externalPackageId = $stmt->fetchColumn();

            if ($externalPackageId) {
                $stmt = $this->conn->prepare("
                    INSERT IGNORE INTO external_progress 
                    (user_id, course_id, content_id, external_package_id, client_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $courseId, $contentId, $externalPackageId, $clientId]);
            }
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::initializeExternalProgress error: " . $e->getMessage());
        }
    }

    /**
     * Update external content progress
     */
    public function updateExternalProgress($userId, $courseId, $contentId, $clientId, $data) {
        try {
            $fields = [];
            $values = [];
            
            $allowedFields = [
                'visit_count', 'last_visited_at', 'time_spent', 'is_completed', 'completion_notes'
            ];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "`$key` = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $fields[] = "`updated_at` = NOW()";
            $values[] = $userId;
            $values[] = $courseId;
            $values[] = $contentId;
            $values[] = $clientId;
            
            $sql = "UPDATE external_progress SET " . implode(', ', $fields) . 
                   " WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::updateExternalProgress error: " . $e->getMessage());
            return false;
        }
    }

    // =====================================================
    // PROGRESS CALCULATION AND RESUME FUNCTIONALITY
    // =====================================================

    /**
     * Calculate overall course progress
     */
    public function calculateCourseProgress($userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(DISTINCT cm.id) as total_modules,
                    COUNT(DISTINCT cmc.id) as total_content_items
                FROM courses c
                LEFT JOIN course_modules cm ON c.id = cm.course_id AND (cm.deleted_at IS NULL OR cm.deleted_at = '0000-00-00 00:00:00')
                LEFT JOIN course_module_content cmc ON cm.id = cmc.module_id AND (cmc.deleted_at IS NULL OR cmc.deleted_at = '0000-00-00 00:00:00')
                WHERE c.id = ?
            ");
            $stmt->execute([$courseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $totalItems = $result['total_modules'] + $result['total_content_items'];
            $completedItems = 0; // For now, we'll set this to 0 until we implement full progress tracking

            if ($totalItems > 0) {
                $percentage = round(($completedItems / $totalItems) * 100, 2);
            } else {
                $percentage = 0.00;
            }

            // Update course progress
            $this->updateCourseProgress($userId, $courseId, $clientId, [
                'completion_percentage' => $percentage
            ]);

            return [
                'total_modules' => $result['total_modules'],
                'completed_modules' => 0, // Placeholder
                'total_content_items' => $result['total_content_items'],
                'completed_content_items' => 0, // Placeholder
                'completion_percentage' => $percentage
            ];
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::calculateCourseProgress error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get resume position for a user in a course
     */
    public function getResumePosition($userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    current_module_id,
                    current_content_id,
                    resume_position
                FROM user_course_progress 
                WHERE user_id = ? AND course_id = ? AND client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $clientId]);
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$progress) {
                return null;
            }

            $resumeData = [
                'current_module_id' => $progress['current_module_id'],
                'current_content_id' => $progress['current_content_id'],
                'resume_position' => $progress['resume_position'] ? json_decode($progress['resume_position'], true) : null
            ];

            // Get detailed resume data for specific content types
            if ($progress['current_content_id']) {
                $stmt = $this->conn->prepare("
                    SELECT content_type FROM course_module_content WHERE id = ?
                ");
                $stmt->execute([$progress['current_content_id']]);
                $contentType = $stmt->fetchColumn();

                switch ($contentType) {
                    case 'scorm':
                        $resumeData['scorm_data'] = $this->getScormResumeData($userId, $courseId, $progress['current_content_id'], $clientId);
                        break;
                    case 'video':
                        $resumeData['video_data'] = $this->getVideoResumeData($userId, $courseId, $progress['current_content_id'], $clientId);
                        break;
                    case 'audio':
                        $resumeData['audio_data'] = $this->getAudioResumeData($userId, $courseId, $progress['current_content_id'], $clientId);
                        break;
                    case 'document':
                        $resumeData['document_data'] = $this->getDocumentResumeData($userId, $courseId, $progress['current_content_id'], $clientId);
                        break;
                    case 'interactive':
                        $resumeData['interactive_data'] = $this->getInteractiveResumeData($userId, $courseId, $progress['current_content_id'], $clientId);
                        break;
                }
            }

            return $resumeData;
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::getResumePosition error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get resume position for a specific content item
     */
    public function getContentResumePosition($userId, $courseId, $contentId, $clientId) {
        try {
            // First get the content type
            $stmt = $this->conn->prepare("
                SELECT content_type FROM course_module_content WHERE id = ?
            ");
            $stmt->execute([$contentId]);
            $contentType = $stmt->fetchColumn();

            if (!$contentType) {
                return null;
            }

            $resumeData = [
                'current_content_id' => $contentId,
                'content_type' => $contentType
            ];

            // Get specific content resume data based on type
            switch ($contentType) {
                case 'scorm':
                    $scormData = $this->getScormResumeData($userId, $courseId, $contentId, $clientId);
                    if ($scormData) {
                        $resumeData['scorm_data'] = $scormData;
                    }
                    break;
                case 'video':
                    $videoData = $this->getVideoResumeData($userId, $courseId, $contentId, $clientId);
                    if ($videoData) {
                        $resumeData['video_data'] = $videoData;
                    }
                    break;
                case 'audio':
                    $audioData = $this->getAudioResumeData($userId, $courseId, $contentId, $clientId);
                    if ($audioData) {
                        $resumeData['audio_data'] = $audioData;
                    }
                    break;
                case 'document':
                    $documentData = $this->getDocumentResumeData($userId, $courseId, $contentId, $clientId);
                    if ($documentData) {
                        $resumeData['document_data'] = $documentData;
                    }
                    break;
                case 'interactive':
                    $interactiveData = $this->getInteractiveResumeData($userId, $courseId, $contentId, $clientId);
                    if ($interactiveData) {
                        $resumeData['interactive_data'] = $interactiveData;
                    }
                    break;
            }

            return $resumeData;
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::getContentResumePosition error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get SCORM resume data
     */
    private function getScormResumeData($userId, $courseId, $contentId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT lesson_status, lesson_location, suspend_data, launch_data
                FROM scorm_progress 
                WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::getScormResumeData error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get video resume data
     */
    private function getVideoResumeData($userId, $courseId, $contentId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT current_time, duration, watched_percentage, bookmarks
                FROM video_progress 
                WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::getVideoResumeData error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get audio resume data
     */
    private function getAudioResumeData($userId, $courseId, $contentId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT current_time, duration, listened_percentage, playback_speed
                FROM audio_progress 
                WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::getAudioResumeData error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get document resume data
     */
    private function getDocumentResumeData($userId, $courseId, $contentId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT current_page, total_pages, pages_viewed, bookmarks
                FROM document_progress 
                WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::getDocumentResumeData error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get interactive content resume data
     */
    private function getInteractiveResumeData($userId, $courseId, $contentId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT current_step, total_steps, interaction_data, user_responses
                FROM interactive_progress 
                WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::getInteractiveResumeData error: " . $e->getMessage());
            return null;
        }
    }

    // =====================================================
    // UTILITY METHODS
    // =====================================================

    /**
     * Check if user has access to a course (via course_applicability)
     */
    public function hasCourseAccess($userId, $courseId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM course_applicability 
                WHERE course_id = ?
                AND (
                    applicability_type = 'all' 
                    OR (applicability_type = 'user' AND user_id = ?)
                )
            ");
            $stmt->execute([$courseId, $userId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::hasCourseAccess error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's progress summary for all courses
     */
    public function getUserProgressSummary($userId, $clientId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    ucp.course_id,
                    c.name as course_title,
                    ucp.status,
                    ucp.completion_percentage,
                    ucp.last_accessed_at,
                    ucp.total_time_spent
                FROM user_course_progress ucp
                JOIN courses c ON ucp.course_id = c.id
                WHERE ucp.user_id = ? AND ucp.client_id = ?
                ORDER BY ucp.last_accessed_at DESC
            ");
            $stmt->execute([$userId, $clientId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::getUserProgressSummary error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set resume position for a user in a course
     */
    public function setResumePosition($userId, $courseId, $clientId, $moduleId = null, $contentId = null, $resumePosition = null) {
        try {
            error_log("DEBUG: setResumePosition called - userId: $userId, courseId: $courseId, moduleId: " . ($moduleId ?? 'NULL') . ", contentId: " . ($contentId ?? 'NULL'));
            error_log("DEBUG: setResumePosition - resumePosition: " . json_encode($resumePosition));
            
            // Update user_course_progress with resume position
            $stmt = $this->conn->prepare("
                UPDATE user_course_progress 
                SET current_module_id = ?, current_content_id = ?, resume_position = ?, updated_at = NOW()
                WHERE user_id = ? AND course_id = ? AND client_id = ?
            ");
            
            $result = $stmt->execute([
                $moduleId,
                $contentId,
                $resumePosition ? json_encode($resumePosition) : null,
                $userId,
                $courseId,
                $clientId
            ]);
            
            if ($result) {
                error_log("DEBUG: setResumePosition - user_course_progress updated successfully");
                
                // Also update the specific content progress if contentId is provided
                if ($contentId && $resumePosition) {
                    $this->updateContentResumePosition($userId, $courseId, $contentId, $clientId, $resumePosition);
                }
                
                return true;
            }
            
            error_log("DEBUG: setResumePosition - user_course_progress update failed");
            return false;
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::setResumePosition error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update content-specific resume position
     */
    private function updateContentResumePosition($userId, $courseId, $contentId, $clientId, $resumePosition) {
        try {
            // Get content type
            $stmt = $this->conn->prepare("SELECT content_type FROM course_module_content WHERE id = ?");
            $stmt->execute([$contentId]);
            $contentType = $stmt->fetchColumn();
            
            if (!$contentType) {
                error_log("DEBUG: updateContentResumePosition - content type not found for content ID: $contentId");
                return false;
            }
            
            error_log("DEBUG: updateContentResumePosition - updating $contentType resume position for content ID: $contentId");
            
            switch ($contentType) {
                case 'scorm':
                    // Update SCORM progress with resume data
                    $stmt = $this->conn->prepare("
                        UPDATE scorm_progress 
                        SET lesson_location = ?, suspend_data = ?, updated_at = NOW()
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                    ");
                    $stmt->execute([
                        $resumePosition['lesson_location'] ?? null,
                        $resumePosition['suspend_data'] ?? null,
                        $userId, $courseId, $contentId, $clientId
                    ]);
                    break;
                    
                case 'video':
                    // Update video progress with resume data
                    $stmt = $this->conn->prepare("
                        UPDATE video_progress 
                        SET current_time = ?, updated_at = NOW()
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                    ");
                    $stmt->execute([
                        $resumePosition['current_time'] ?? 0,
                        $userId, $courseId, $contentId, $clientId
                    ]);
                    break;
                    
                case 'audio':
                    // Update audio progress with resume data
                    $stmt = $this->conn->prepare("
                        UPDATE audio_progress 
                        SET current_time = ?, updated_at = NOW()
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                    ");
                    $stmt->execute([
                        $resumePosition['current_time'] ?? 0,
                        $userId, $courseId, $contentId, $clientId
                    ]);
                    break;
                    
                default:
                    error_log("DEBUG: updateContentResumePosition - unsupported content type: $contentType");
                    break;
            }
            
            error_log("DEBUG: updateContentResumePosition - resume position updated successfully for $contentType");
            return true;
            
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::updateContentResumePosition error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get latest progress for specific content
     */
    public function getContentProgress($userId, $courseId, $contentId, $contentType, $clientId) {
        try {
            error_log("DEBUG: getContentProgress called - userId: $userId, courseId: $courseId, contentId: $contentId, contentType: $contentType, clientId: $clientId");
            
            switch ($contentType) {
                case 'scorm':
                    $stmt = $this->conn->prepare("
                        SELECT lesson_location, suspend_data, lesson_status, score_raw, score_min, score_max, 
                               total_time, session_time, created_at, updated_at
                        FROM scorm_progress 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                        ORDER BY updated_at DESC
                        LIMIT 1
                    ");
                    break;
                    
                case 'video':
                    $stmt = $this->conn->prepare("
                        SELECT current_time, total_time, watched_percentage, created_at, updated_at
                        FROM video_progress 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                        ORDER BY updated_at DESC
                        LIMIT 1
                    ");
                    break;
                    
                case 'audio':
                    $stmt = $this->conn->prepare("
                        SELECT current_time, total_time, listened_percentage, created_at, updated_at
                        FROM audio_progress 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                        ORDER BY updated_at DESC
                        LIMIT 1
                    ");
                    break;
                    
                case 'document':
                    $stmt = $this->conn->prepare("
                        SELECT current_page, total_pages, read_percentage, created_at, updated_at
                        FROM document_progress 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                        ORDER BY updated_at DESC
                        LIMIT 1
                    ");
                    break;
                    
                case 'interactive':
                    $stmt = $this->conn->prepare("
                        SELECT current_step, total_steps, completion_percentage, created_at, updated_at
                        FROM interactive_progress 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                        ORDER BY updated_at DESC
                        LIMIT 1
                    ");
                    break;
                    
                case 'external':
                    $stmt = $this->conn->prepare("
                        SELECT current_position, total_duration, completion_percentage, created_at, updated_at
                        FROM external_progress 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                        ORDER BY updated_at DESC
                        LIMIT 1
                    ");
                    break;
                    
                default:
                    error_log("DEBUG: getContentProgress - unsupported content type: $contentType");
                    return null;
            }
            
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($progress) {
                error_log("DEBUG: getContentProgress - progress found: " . json_encode($progress));
                return $progress;
            } else {
                error_log("DEBUG: getContentProgress - no progress found");
                return null;
            }
            
        } catch (PDOException $e) {
            error_log("ProgressTrackingModel::getContentProgress error: " . $e->getMessage());
            return null;
        }
    }
}

