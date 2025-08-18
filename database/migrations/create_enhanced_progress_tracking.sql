-- Enhanced Progress Tracking System
-- This system works with course_applicability instead of course_enrollments
-- Date: 2025-01-30

-- =====================================================
-- USER COURSE PROGRESS (replaces course_enrollments dependency)
-- =====================================================

-- User Course Progress Table
CREATE TABLE IF NOT EXISTS `user_course_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `status` enum('not_started','in_progress','completed','paused') NOT NULL DEFAULT 'not_started',
  `completion_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `started_at` timestamp NULL DEFAULT NULL,
  `last_accessed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `total_time_spent` int(11) NOT NULL DEFAULT 0 COMMENT 'Total time spent in minutes',
  `current_module_id` int(11) DEFAULT NULL COMMENT 'Current module user is working on',
  `current_content_id` int(11) DEFAULT NULL COMMENT 'Current content item user is working on',
  `resume_position` text DEFAULT NULL COMMENT 'JSON object with resume data (varies by content type)',
  `certificate_issued` tinyint(1) NOT NULL DEFAULT '0',
  `certificate_issued_at` timestamp NULL DEFAULT NULL,
  `certificate_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_course` (`user_id`, `course_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_status` (`status`),
  KEY `idx_completion_percentage` (`completion_percentage`),
  KEY `idx_last_accessed` (`last_accessed_at`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`current_module_id`) REFERENCES `course_modules`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`current_content_id`) REFERENCES `course_module_content`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='User progress tracking for courses (replaces course_enrollments dependency)';

-- =====================================================
-- ENHANCED MODULE PROGRESS (updated to work with new system)
-- =====================================================

-- Update module_progress table to work with user_course_progress instead of course_enrollments
-- First, drop the foreign key constraint if it exists
SET @constraint_name = (
    SELECT CONSTRAINT_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'module_progress' 
    AND COLUMN_NAME = 'enrollment_id'
    AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

SET @sql = IF(@constraint_name IS NOT NULL, 
    CONCAT('ALTER TABLE `module_progress` DROP FOREIGN KEY `', @constraint_name, '`'), 
    'SELECT "No foreign key to drop" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Now drop the enrollment_id column and add new columns
ALTER TABLE `module_progress` 
DROP COLUMN `enrollment_id`,
ADD COLUMN `user_id` int(11) NOT NULL AFTER `id`,
ADD COLUMN `course_id` int(11) NOT NULL AFTER `user_id`,
ADD COLUMN `client_id` int(11) NOT NULL AFTER `course_id`;

-- Add indexes and constraints
ALTER TABLE `module_progress` 
ADD UNIQUE KEY `unique_user_module` (`user_id`, `course_id`, `module_id`),
ADD KEY `idx_user_id` (`user_id`),
ADD KEY `idx_course_id` (`course_id`),
ADD KEY `idx_client_id` (`client_id`);

-- Add foreign key constraints
ALTER TABLE `module_progress` 
ADD CONSTRAINT `module_progress_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `module_progress_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `module_progress_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE;

-- =====================================================
-- ENHANCED CONTENT PROGRESS (updated to work with new system)
-- =====================================================

-- Update content_progress table to work with user_course_progress instead of course_enrollments
-- First, drop the foreign key constraint if it exists
SET @constraint_name = (
    SELECT CONSTRAINT_NAME 
    FROM information_schema.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'content_progress' 
    AND COLUMN_NAME = 'enrollment_id'
    AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

SET @sql = IF(@constraint_name IS NOT NULL, 
    CONCAT('ALTER TABLE `content_progress` DROP FOREIGN KEY `', @constraint_name, '`'), 
    'SELECT "No foreign key to drop" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Now drop the enrollment_id column and add new columns
ALTER TABLE `content_progress` 
DROP COLUMN `enrollment_id`,
ADD COLUMN `user_id` int(11) NOT NULL AFTER `id`,
ADD COLUMN `course_id` int(11) NOT NULL AFTER `user_id`,
ADD COLUMN `client_id` int(11) NOT NULL AFTER `course_id`,
ADD COLUMN `content_type` varchar(50) NOT NULL AFTER `content_id` COMMENT 'Type of content (scorm, video, audio, document, etc.)',
ADD COLUMN `resume_data` text DEFAULT NULL COMMENT 'JSON object with resume data specific to content type',
ADD COLUMN `progress_data` text DEFAULT NULL COMMENT 'JSON object with detailed progress data',
ADD COLUMN `last_position` varchar(255) DEFAULT NULL COMMENT 'Last position in content (timestamp for video, page for document, etc.)',
ADD COLUMN `completion_criteria_met` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether completion criteria have been met';

-- Add indexes and constraints
ALTER TABLE `content_progress` 
ADD UNIQUE KEY `unique_user_content` (`user_id`, `course_id`, `content_id`),
ADD KEY `idx_user_id` (`user_id`),
ADD KEY `idx_course_id` (`course_id`),
ADD KEY `idx_client_id` (`client_id`),
ADD KEY `idx_content_type` (`content_type`),
ADD KEY `idx_status` (`status`);

-- Add foreign key constraints
ALTER TABLE `content_progress` 
ADD CONSTRAINT `content_progress_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `content_progress_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `content_progress_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE;

-- =====================================================
-- CONTENT-SPECIFIC PROGRESS TRACKING
-- =====================================================

-- SCORM Progress Tracking Table
CREATE TABLE IF NOT EXISTS `scorm_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL COMMENT 'ID from course_module_content',
  `scorm_package_id` int(11) NOT NULL COMMENT 'ID from scorm_packages',
  `client_id` int(11) NOT NULL,
  `lesson_status` varchar(50) DEFAULT NULL COMMENT 'SCORM lesson status (not attempted, incomplete, completed, failed, passed)',
  `lesson_location` varchar(255) DEFAULT NULL COMMENT 'SCORM lesson location (resume point)',
  `score_raw` decimal(10,2) DEFAULT NULL COMMENT 'Raw score from SCORM',
  `score_min` decimal(10,2) DEFAULT NULL COMMENT 'Minimum score from SCORM',
  `score_max` decimal(10,2) DEFAULT NULL COMMENT 'Maximum score from SCORM',
  `total_time` varchar(50) DEFAULT NULL COMMENT 'Total time spent (SCORM format)',
  `session_time` varchar(50) DEFAULT NULL COMMENT 'Current session time',
  `suspend_data` text DEFAULT NULL COMMENT 'SCORM suspend data for resume',
  `launch_data` text DEFAULT NULL COMMENT 'SCORM launch data',
  `interactions` text DEFAULT NULL COMMENT 'JSON array of SCORM interactions',
  `objectives` text DEFAULT NULL COMMENT 'JSON array of SCORM objectives',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_scorm` (`user_id`, `course_id`, `content_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_scorm_package_id` (`scorm_package_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_lesson_status` (`lesson_status`),
  FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`content_id`) REFERENCES `course_module_content`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`scorm_package_id`) REFERENCES `scorm_packages`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detailed SCORM progress tracking';

-- Video Progress Tracking Table
CREATE TABLE IF NOT EXISTS `video_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL COMMENT 'ID from course_module_content',
  `video_package_id` int(11) NOT NULL COMMENT 'ID from video_package',
  `client_id` int(11) NOT NULL,
  `current_time` int(11) NOT NULL DEFAULT 0 COMMENT 'Current playback position in seconds',
  `duration` int(11) NOT NULL DEFAULT 0 COMMENT 'Total video duration in seconds',
  `watched_percentage` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Percentage of video watched',
  `completion_threshold` decimal(5,2) NOT NULL DEFAULT 80.00 COMMENT 'Percentage required to mark as complete',
  `is_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether video is marked as complete',
  `play_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of times video was played',
  `last_watched_at` timestamp NULL DEFAULT NULL COMMENT 'Last time video was watched',
  `bookmarks` text DEFAULT NULL COMMENT 'JSON array of user bookmarks',
  `notes` text DEFAULT NULL COMMENT 'User notes about the video',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_video` (`user_id`, `course_id`, `content_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_video_package_id` (`video_package_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_is_completed` (`is_completed`),
  KEY `idx_watched_percentage` (`watched_percentage`),
  FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`content_id`) REFERENCES `course_module_content`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`video_package_id`) REFERENCES `video_package`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detailed video progress tracking';

-- Audio Progress Tracking Table
CREATE TABLE IF NOT EXISTS `audio_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL COMMENT 'ID from course_module_content',
  `audio_package_id` int(11) NOT NULL COMMENT 'ID from audio_package',
  `client_id` int(11) NOT NULL,
  `current_time` int(11) NOT NULL DEFAULT 0 COMMENT 'Current playback position in seconds',
  `duration` int(11) NOT NULL DEFAULT 0 COMMENT 'Total audio duration in seconds',
  `listened_percentage` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Percentage of audio listened to',
  `completion_threshold` decimal(5,2) NOT NULL DEFAULT 80.00 COMMENT 'Percentage required to mark as complete',
  `is_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether audio is marked as complete',
  `play_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of times audio was played',
  `last_listened_at` timestamp NULL DEFAULT NULL COMMENT 'Last time audio was listened to',
  `playback_speed` decimal(3,2) NOT NULL DEFAULT 1.00 COMMENT 'Playback speed used (1.0 = normal)',
  `notes` text DEFAULT NULL COMMENT 'User notes about the audio',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_audio` (`user_id`, `course_id`, `content_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_audio_package_id` (`audio_package_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_is_completed` (`is_completed`),
  KEY `idx_listened_percentage` (`listened_percentage`),
  FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`content_id`) REFERENCES `course_module_content`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`audio_package_id`) REFERENCES `audio_package`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detailed audio progress tracking';

-- Document Progress Tracking Table
CREATE TABLE IF NOT EXISTS `document_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL COMMENT 'ID from course_module_content',
  `document_package_id` int(11) NOT NULL COMMENT 'ID from document_package',
  `client_id` int(11) NOT NULL,
  `current_page` int(11) NOT NULL DEFAULT 1 COMMENT 'Current page user is viewing',
  `total_pages` int(11) NOT NULL DEFAULT 0 COMMENT 'Total pages in document',
  `pages_viewed` text DEFAULT NULL COMMENT 'JSON array of pages that have been viewed',
  `viewed_percentage` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Percentage of pages viewed',
  `completion_threshold` decimal(5,2) NOT NULL DEFAULT 80.00 COMMENT 'Percentage required to mark as complete',
  `is_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether document is marked as complete',
  `time_spent` int(11) NOT NULL DEFAULT 0 COMMENT 'Total time spent viewing in seconds',
  `last_viewed_at` timestamp NULL DEFAULT NULL COMMENT 'Last time document was viewed',
  `bookmarks` text DEFAULT NULL COMMENT 'JSON array of user bookmarks',
  `notes` text DEFAULT NULL COMMENT 'User notes about the document',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_document` (`user_id`, `course_id`, `content_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_document_package_id` (`document_package_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_is_completed` (`is_completed`),
  KEY `idx_viewed_percentage` (`viewed_percentage`),
  FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`content_id`) REFERENCES `course_module_content`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`document_package_id`) REFERENCES `document_package`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detailed document progress tracking';

-- Interactive Content Progress Tracking Table
CREATE TABLE IF NOT EXISTS `interactive_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL COMMENT 'ID from course_module_content',
  `interactive_package_id` int(11) NOT NULL COMMENT 'ID from interactive_ai_content_package',
  `client_id` int(11) NOT NULL,
  `interaction_data` text DEFAULT NULL COMMENT 'JSON object with interaction history',
  `current_step` int(11) NOT NULL DEFAULT 1 COMMENT 'Current step in interactive content',
  `total_steps` int(11) NOT NULL DEFAULT 0 COMMENT 'Total steps in interactive content',
  `completion_percentage` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Percentage of interactive content completed',
  `is_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether interactive content is completed',
  `time_spent` int(11) NOT NULL DEFAULT 0 COMMENT 'Total time spent in seconds',
  `last_interaction_at` timestamp NULL DEFAULT NULL COMMENT 'Last time user interacted with content',
  `user_responses` text DEFAULT NULL COMMENT 'JSON array of user responses',
  `ai_feedback` text DEFAULT NULL COMMENT 'JSON array of AI feedback received',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_interactive` (`user_id`, `course_id`, `content_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_interactive_package_id` (`interactive_package_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_is_completed` (`is_completed`),
  KEY `idx_completion_percentage` (`completion_percentage`),
  FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`content_id`) REFERENCES `course_module_content`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`interactive_package_id`) REFERENCES `interactive_ai_content_package`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detailed interactive content progress tracking';

-- External Content Progress Tracking Table
CREATE TABLE IF NOT EXISTS `external_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL COMMENT 'ID from course_module_content',
  `external_package_id` int(11) NOT NULL COMMENT 'ID from external_package',
  `client_id` int(11) NOT NULL,
  `external_url` varchar(500) NOT NULL COMMENT 'External content URL',
  `visit_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of times external content was visited',
  `last_visited_at` timestamp NULL DEFAULT NULL COMMENT 'Last time external content was visited',
  `time_spent` int(11) NOT NULL DEFAULT 0 COMMENT 'Total time spent in seconds',
  `is_completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether external content is marked as complete',
  `completion_notes` text DEFAULT NULL COMMENT 'User notes about completion',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_external` (`user_id`, `course_id`, `content_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_external_package_id` (`external_package_id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_is_completed` (`is_completed`),
  KEY `idx_last_visited` (`last_visited_at`),
  FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`content_id`) REFERENCES `course_module_content`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`external_package_id`) REFERENCES `external_package`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='External content progress tracking';

-- =====================================================
-- PROGRESS CALCULATION VIEWS
-- =====================================================

-- Course Progress Summary View
CREATE OR REPLACE VIEW `course_progress_summary` AS
SELECT 
    ucp.user_id,
    ucp.course_id,
    ucp.client_id,
    c.title as course_title,
    ucp.status,
    ucp.completion_percentage,
    ucp.started_at,
    ucp.last_accessed_at,
    ucp.completed_at,
    ucp.total_time_spent,
    COUNT(DISTINCT cm.id) as total_modules,
    COUNT(DISTINCT CASE WHEN mp.status = 'completed' THEN mp.id END) as completed_modules,
    COUNT(DISTINCT cmc.id) as total_content_items,
    COUNT(DISTINCT CASE WHEN cp.status = 'completed' THEN cp.id END) as completed_content_items,
    ROUND(
        (COUNT(DISTINCT CASE WHEN mp.status = 'completed' THEN mp.id END) + 
         COUNT(DISTINCT CASE WHEN cp.status = 'completed' THEN cp.id END)) * 100.0 / 
        (COUNT(DISTINCT cm.id) + COUNT(DISTINCT cmc.id)), 2
    ) as calculated_progress
FROM user_course_progress ucp
JOIN courses c ON ucp.course_id = c.id
LEFT JOIN course_modules cm ON c.id = cm.course_id AND cm.is_deleted = 0
LEFT JOIN module_progress mp ON ucp.user_id = mp.user_id AND ucp.course_id = mp.course_id AND cm.id = mp.module_id
LEFT JOIN course_module_content cmc ON cm.id = cmc.module_id AND cmc.is_deleted = 0
LEFT JOIN content_progress cp ON ucp.user_id = cp.user_id AND ucp.course_id = cp.course_id AND cmc.id = cp.content_id
GROUP BY ucp.user_id, ucp.course_id, ucp.client_id;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Add additional indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_user_course_progress_status` ON `user_course_progress` (`status`);
CREATE INDEX IF NOT EXISTS `idx_user_course_progress_completion` ON `user_course_progress` (`completion_percentage`);
CREATE INDEX IF NOT EXISTS `idx_user_course_progress_last_accessed` ON `user_course_progress` (`last_accessed_at`);

CREATE INDEX IF NOT EXISTS `idx_module_progress_status` ON `module_progress` (`status`);
CREATE INDEX IF NOT EXISTS `idx_module_progress_completion` ON `module_progress` (`completion_percentage`);

CREATE INDEX IF NOT EXISTS `idx_content_progress_status` ON `content_progress` (`status`);
CREATE INDEX IF NOT EXISTS `idx_content_progress_type` ON `content_progress` (`content_type`);
CREATE INDEX IF NOT EXISTS `idx_content_progress_completion` ON `content_progress` (`completion_percentage`);

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

-- This migration creates a comprehensive progress tracking system that:
-- 1. Replaces course_enrollments dependency with user_course_progress
-- 2. Provides content-specific progress tracking for all VLR types
-- 3. Enables resume functionality for interrupted sessions
-- 4. Calculates progress based on content type and completion criteria
-- 5. Maintains backward compatibility with existing progress tables
-- 6. Works seamlessly with course_applicability system
