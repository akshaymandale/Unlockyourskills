-- Create assessment_questions table
CREATE TABLE IF NOT EXISTS `assessment_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `question_type` enum('objective','subjective') NOT NULL DEFAULT 'objective',
  `marks` int(11) NOT NULL DEFAULT 1,
  `tags` text,
  `skills` text,
  `difficulty_level` enum('easy','medium','hard') DEFAULT 'medium',
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_question_type` (`question_type`),
  KEY `idx_marks` (`marks`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_is_deleted` (`is_deleted`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Assessment questions table';

-- Create assessment_options table for objective questions
CREATE TABLE IF NOT EXISTS `assessment_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` text NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `option_order` int(11) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_question_id` (`question_id`),
  KEY `idx_is_correct` (`is_correct`),
  KEY `idx_is_deleted` (`is_deleted`),
  FOREIGN KEY (`question_id`) REFERENCES `assessment_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Assessment question options table';

-- Create assessment_package table
CREATE TABLE IF NOT EXISTS `assessment_package` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `duration` int(11) DEFAULT NULL COMMENT 'Duration in minutes',
  `passing_score` int(11) DEFAULT NULL COMMENT 'Passing score percentage',
  `total_marks` int(11) DEFAULT NULL,
  `question_count` int(11) DEFAULT NULL,
  `selected_question_ids` text COMMENT 'Comma-separated list of question IDs',
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_is_deleted` (`is_deleted`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Assessment packages table'; 