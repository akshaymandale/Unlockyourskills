-- Migration: Add missing fields to courses table for enhanced Add Course functionality
-- Date: 2024-01-30

-- Add missing fields to courses table
ALTER TABLE `courses` 
ADD COLUMN `course_status` enum('active', 'inactive') NOT NULL DEFAULT 'active' COMMENT 'Course status (active/inactive)' AFTER `difficulty_level`,
ADD COLUMN `module_structure` enum('sequential', 'non_sequential') NOT NULL DEFAULT 'sequential' COMMENT 'Module completion structure' AFTER `course_status`,
ADD COLUMN `course_points` int(11) NOT NULL DEFAULT 0 COMMENT 'Points earned upon course completion' AFTER `module_structure`,
ADD COLUMN `course_cost` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Course cost amount' AFTER `course_points`,
ADD COLUMN `currency` varchar(10) DEFAULT NULL COMMENT 'Currency code for course cost' AFTER `course_cost`,
ADD COLUMN `reassign_course` enum('yes', 'no') NOT NULL DEFAULT 'no' COMMENT 'Whether to reassign course after completion' AFTER `currency`,
ADD COLUMN `reassign_days` int(11) DEFAULT NULL COMMENT 'Days after which to reassign course' AFTER `reassign_course`,
ADD COLUMN `show_in_search` enum('yes', 'no') NOT NULL DEFAULT 'no' COMMENT 'Whether to show course in search results' AFTER `reassign_days`,
ADD COLUMN `certificate_option` enum('after_rating', 'on_completion') NOT NULL DEFAULT 'after_rating' COMMENT 'When to issue certificate' AFTER `show_in_search`;

-- Add indexes for new fields
ALTER TABLE `courses` 
ADD INDEX `idx_course_status` (`course_status`),
ADD INDEX `idx_module_structure` (`module_structure`),
ADD INDEX `idx_course_points` (`course_points`),
ADD INDEX `idx_course_cost` (`course_cost`),
ADD INDEX `idx_currency` (`currency`),
ADD INDEX `idx_reassign_course` (`reassign_course`),
ADD INDEX `idx_show_in_search` (`show_in_search`),
ADD INDEX `idx_certificate_option` (`certificate_option`);

-- Add comments to existing fields for clarity
ALTER TABLE `courses` 
MODIFY COLUMN `max_attempts` int(11) DEFAULT 1 COMMENT 'Maximum attempts allowed (deprecated - moved to assessments)',
MODIFY COLUMN `passing_score` decimal(5,2) DEFAULT 70.00 COMMENT 'Minimum score to pass (deprecated - moved to assessments)';

-- Update existing records to have default values for new fields
UPDATE `courses` SET 
    `course_status` = 'active',
    `module_structure` = 'sequential',
    `course_points` = 0,
    `course_cost` = 0.00,
    `reassign_course` = 'no',
    `show_in_search` = 'no',
    `certificate_option` = 'after_rating'
WHERE `course_status` IS NULL;

-- Create course assignments table for post-requisite assignments
CREATE TABLE IF NOT EXISTS `course_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL COMMENT 'ID from assignment_package table',
  `assignment_type` enum('post_course', 'module_assignment') NOT NULL DEFAULT 'post_course',
  `module_id` int(11) DEFAULT NULL COMMENT 'NULL for post course assignments',
  `title` varchar(255) NOT NULL,
  `description` text,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_assignment_id` (`assignment_id`),
  KEY `idx_assignment_type` (`assignment_type`),
  KEY `idx_module_id` (`module_id`),
  KEY `idx_is_deleted` (`is_deleted`),
  KEY `idx_created_by` (`created_by`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assignment_id`) REFERENCES `assignment_package`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`module_id`) REFERENCES `course_modules`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `user_profiles`(`id`),
  FOREIGN KEY (`updated_by`) REFERENCES `user_profiles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Course assignments table for post-requisite content'; 