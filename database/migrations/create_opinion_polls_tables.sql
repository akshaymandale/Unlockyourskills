-- =====================================================
-- Create Opinion Poll Tables
-- Date: 2025-01-19
-- Description: Creates tables for Opinion Poll feature
-- =====================================================

-- 1. Create polls table
CREATE TABLE IF NOT EXISTS `polls` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `client_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `type` enum('single_choice', 'multiple_choice') NOT NULL DEFAULT 'single_choice',
    `target_audience` enum('global', 'course_specific', 'group_specific') NOT NULL DEFAULT 'global',
    `course_id` int(11) DEFAULT NULL COMMENT 'For course-specific polls',
    `group_id` int(11) DEFAULT NULL COMMENT 'For group-specific polls',
    `start_datetime` datetime NOT NULL,
    `end_datetime` datetime NOT NULL,
    `show_results` enum('after_vote', 'after_end', 'admin_only') NOT NULL DEFAULT 'after_vote',
    `allow_anonymous` boolean NOT NULL DEFAULT FALSE,
    `allow_vote_change` boolean NOT NULL DEFAULT FALSE,
    `status` enum('draft', 'active', 'paused', 'ended', 'archived') NOT NULL DEFAULT 'draft',
    `created_by` int(11) NOT NULL,
    `updated_by` int(11) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_status` (`status`),
    KEY `idx_target_audience` (`target_audience`),
    KEY `idx_start_end_datetime` (`start_datetime`, `end_datetime`),
    KEY `idx_created_by` (`created_by`),
    CONSTRAINT `fk_polls_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_polls_created_by` FOREIGN KEY (`created_by`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create poll_questions table
CREATE TABLE IF NOT EXISTS `poll_questions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `poll_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `question_text` text NOT NULL,
    `question_order` int(11) NOT NULL DEFAULT 1,
    `media_type` enum('none', 'image', 'video', 'audio') NOT NULL DEFAULT 'none',
    `media_path` varchar(500) DEFAULT NULL,
    `is_required` boolean NOT NULL DEFAULT TRUE,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_poll_id` (`poll_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_question_order` (`question_order`),
    CONSTRAINT `fk_poll_questions_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_poll_questions_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create poll_options table
CREATE TABLE IF NOT EXISTS `poll_options` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `question_id` int(11) NOT NULL,
    `poll_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `option_text` varchar(500) NOT NULL,
    `option_order` int(11) NOT NULL DEFAULT 1,
    `media_type` enum('none', 'image', 'video', 'audio') NOT NULL DEFAULT 'none',
    `media_path` varchar(500) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_question_id` (`question_id`),
    KEY `idx_poll_id` (`poll_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_option_order` (`option_order`),
    CONSTRAINT `fk_poll_options_question` FOREIGN KEY (`question_id`) REFERENCES `poll_questions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_poll_options_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_poll_options_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create poll_votes table
CREATE TABLE IF NOT EXISTS `poll_votes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `poll_id` int(11) NOT NULL,
    `question_id` int(11) NOT NULL,
    `option_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL COMMENT 'NULL for anonymous votes',
    `voter_ip` varchar(45) DEFAULT NULL COMMENT 'IP address for anonymous tracking',
    `voter_session` varchar(255) DEFAULT NULL COMMENT 'Session ID for anonymous tracking',
    `comment` text DEFAULT NULL COMMENT 'Optional comment with vote',
    `voted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_poll_id` (`poll_id`),
    KEY `idx_question_id` (`question_id`),
    KEY `idx_option_id` (`option_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_voted_at` (`voted_at`),
    UNIQUE KEY `unique_user_poll_question` (`poll_id`, `question_id`, `user_id`),
    CONSTRAINT `fk_poll_votes_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_poll_votes_question` FOREIGN KEY (`question_id`) REFERENCES `poll_questions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_poll_votes_option` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_poll_votes_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_poll_votes_user` FOREIGN KEY (`user_id`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create poll_targets table (for specific targeting)
CREATE TABLE IF NOT EXISTS `poll_targets` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `poll_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `target_type` enum('user', 'role', 'course', 'group') NOT NULL,
    `target_id` varchar(100) NOT NULL COMMENT 'User ID, Role name, Course ID, or Group ID',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_poll_id` (`poll_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_target_type` (`target_type`),
    KEY `idx_target_id` (`target_id`),
    CONSTRAINT `fk_poll_targets_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_poll_targets_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create poll_response_log table (for audit trail)
CREATE TABLE IF NOT EXISTS `poll_response_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `poll_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `action` enum('vote_submitted', 'vote_changed', 'vote_deleted') NOT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `details` json DEFAULT NULL COMMENT 'Additional details about the action',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_poll_id` (`poll_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_poll_response_log_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_poll_response_log_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_poll_response_log_user` FOREIGN KEY (`user_id`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
