-- Migration: Unified Post-Requisites Table
-- Date: 2024-01-30
-- Purpose: Replace 4 separate post-requisite tables with a single unified table

-- Create the new unified post-requisites table
CREATE TABLE IF NOT EXISTS `course_post_requisites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `content_type` enum('assessment', 'feedback', 'survey', 'assignment') NOT NULL COMMENT 'Type of post-requisite content',
  `content_id` int(11) NOT NULL COMMENT 'ID from respective package table (assessment_package, feedback_package, survey_package, assignment_package)',
  `requisite_type` enum('post_course', 'module_requisite') NOT NULL DEFAULT 'post_course' COMMENT 'When this content should be completed',
  `module_id` int(11) DEFAULT NULL COMMENT 'NULL for post course requisites, module_id for module-specific',
  `title` varchar(255) NOT NULL,
  `description` text,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `settings` json DEFAULT NULL COMMENT 'Additional settings specific to content type (e.g., passing_score for assessments)',
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_course_id` (`course_id`),
  KEY `idx_content_type` (`content_type`),
  KEY `idx_content_id` (`content_id`),
  KEY `idx_requisite_type` (`requisite_type`),
  KEY `idx_module_id` (`module_id`),
  KEY `idx_sort_order` (`sort_order`),
  KEY `idx_is_deleted` (`is_deleted`),
  KEY `idx_created_by` (`created_by`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`module_id`) REFERENCES `course_modules`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `user_profiles`(`id`),
  FOREIGN KEY (`updated_by`) REFERENCES `user_profiles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unified table for all post-requisite content (assessments, feedback, surveys, assignments)';

-- Migrate existing data from the 4 separate tables
-- Note: This migration assumes the old tables exist and have data

-- Migrate from course_assessments
INSERT INTO `course_post_requisites` (
    `course_id`, `content_type`, `content_id`, `requisite_type`, `module_id`, 
    `title`, `description`, `is_required`, `sort_order`, `settings`, 
    `created_by`, `updated_by`, `created_at`, `updated_at`, `is_deleted`
)
SELECT 
    `course_id`, 
    'assessment' as `content_type`, 
    `assessment_id` as `content_id`, 
    `assessment_type` as `requisite_type`, 
    `module_id`, 
    `title`, 
    `description`, 
    `is_required`, 
    `assessment_order` as `sort_order`, 
    JSON_OBJECT(
        'time_limit', `time_limit`
    ) as `settings`,
    `created_by`, 
    `updated_by`, 
    `created_at`, 
    `updated_at`, 
    IF(`deleted_at` IS NOT NULL, 1, 0) as `is_deleted`
FROM `course_assessments` 
WHERE `deleted_at` IS NULL;

-- Migrate from course_feedback
INSERT INTO `course_post_requisites` (
    `course_id`, `content_type`, `content_id`, `requisite_type`, `module_id`, 
    `title`, `description`, `is_required`, `sort_order`, `settings`, 
    `created_by`, `updated_by`, `created_at`, `updated_at`, `is_deleted`
)
SELECT 
    `course_id`, 
    'feedback' as `content_type`, 
    `feedback_id` as `content_id`, 
    `feedback_type` as `requisite_type`, 
    `module_id`, 
    `title`, 
    `description`, 
    `is_required`, 
    `feedback_order` as `sort_order`, 
    NULL as `settings`,
    `created_by`, 
    `updated_by`, 
    `created_at`, 
    `updated_at`, 
    IF(`deleted_at` IS NOT NULL, 1, 0) as `is_deleted`
FROM `course_feedback` 
WHERE `deleted_at` IS NULL;

-- Migrate from course_surveys
INSERT INTO `course_post_requisites` (
    `course_id`, `content_type`, `content_id`, `requisite_type`, `module_id`, 
    `title`, `description`, `is_required`, `sort_order`, `settings`, 
    `created_by`, `updated_by`, `created_at`, `updated_at`, `is_deleted`
)
SELECT 
    `course_id`, 
    'survey' as `content_type`, 
    `survey_id` as `content_id`, 
    `survey_type` as `requisite_type`, 
    `module_id`, 
    `title`, 
    `description`, 
    `is_required`, 
    `survey_order` as `sort_order`, 
    NULL as `settings`,
    `created_by`, 
    `updated_by`, 
    `created_at`, 
    `updated_at`, 
    IF(`deleted_at` IS NOT NULL, 1, 0) as `is_deleted`
FROM `course_surveys` 
WHERE `deleted_at` IS NULL;

-- Migrate from course_assignments
INSERT INTO `course_post_requisites` (
    `course_id`, `content_type`, `content_id`, `requisite_type`, `module_id`, 
    `title`, `description`, `is_required`, `sort_order`, `settings`, 
    `created_by`, `updated_by`, `created_at`, `updated_at`, `is_deleted`
)
SELECT 
    `course_id`, 
    'assignment' as `content_type`, 
    `assignment_id` as `content_id`, 
    `assignment_type` as `requisite_type`, 
    `module_id`, 
    `title`, 
    `description`, 
    `is_required`, 
    `sort_order`, 
    NULL as `settings`,
    `created_by`, 
    `updated_by`, 
    `created_at`, 
    `updated_at`, 
    `is_deleted`
FROM `course_assignments` 
WHERE `is_deleted` = 0;

-- Old tables have been dropped in a separate migration
-- See: database/migrations/drop_old_post_requisite_tables.sql 