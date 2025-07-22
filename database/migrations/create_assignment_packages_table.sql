-- Assignment Package Table
CREATE TABLE IF NOT EXISTS `assignment_package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `tags` text,
  `version` varchar(50) NOT NULL,
  `language` int(11) DEFAULT NULL,
  `time_limit` int(11) DEFAULT NULL COMMENT 'Time limit in minutes',
  `mobile_support` enum('Yes', 'No') DEFAULT 'No',
  
  -- Assignment-specific fields
  `assignment_file` varchar(255) DEFAULT NULL COMMENT 'Uploaded assignment file (PDF, DOC, etc.)',
  `assignment_type` enum('individual', 'group', 'project', 'case_study', 'research') DEFAULT 'individual',
  `difficulty_level` enum('Beginner', 'Intermediate', 'Advanced') DEFAULT 'Beginner',
  `estimated_duration` int(11) DEFAULT NULL COMMENT 'Estimated completion time in minutes',
  `max_attempts` int(11) DEFAULT 1 COMMENT 'Maximum number of attempts allowed',
  `passing_score` int(11) DEFAULT NULL COMMENT 'Passing score percentage',
  `submission_format` enum('file_upload', 'text_entry', 'url_submission', 'mixed') DEFAULT 'file_upload',
  `allow_late_submission` enum('Yes', 'No') DEFAULT 'No',
  `late_submission_penalty` int(11) DEFAULT 0 COMMENT 'Penalty percentage for late submissions',
  
  -- Instructions and requirements
  `instructions` text DEFAULT NULL COMMENT 'Assignment instructions for learners',
  `requirements` text DEFAULT NULL COMMENT 'Assignment requirements and criteria',
  `rubric` text DEFAULT NULL COMMENT 'Grading rubric or criteria',
  `learning_objectives` text DEFAULT NULL COMMENT 'Learning objectives for the assignment',
  `prerequisites` text DEFAULT NULL COMMENT 'Prerequisites for the assignment',
  
  -- File uploads
  `thumbnail_image` varchar(255) DEFAULT NULL COMMENT 'Thumbnail image for the assignment',
  `sample_solution` varchar(255) DEFAULT NULL COMMENT 'Sample solution file (optional)',
  `supporting_materials` text DEFAULT NULL COMMENT 'JSON array of supporting material files',
  
  -- Common fields
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  
  PRIMARY KEY (`id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_assignment_type` (`assignment_type`),
  KEY `idx_difficulty_level` (`difficulty_level`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_is_deleted` (`is_deleted`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`language`) REFERENCES `languages`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`updated_by`) REFERENCES `user_profiles`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Assignment packages table'; 