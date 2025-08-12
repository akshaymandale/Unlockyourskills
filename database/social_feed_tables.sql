-- Social Feed (News Wall) Database Tables
-- This file contains all the necessary tables for the social feed functionality

-- Feed Posts Table
CREATE TABLE IF NOT EXISTS `feed_posts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `title` varchar(150) DEFAULT NULL,
    `body` text NOT NULL,
    `post_type` enum('text','media','poll','link') NOT NULL DEFAULT 'text',
    `visibility` enum('global','course_specific','group_specific') NOT NULL DEFAULT 'global',
    `course_id` int(11) DEFAULT NULL,
    `group_id` int(11) DEFAULT NULL,
    `media_files` json DEFAULT NULL,
    `link_preview` json DEFAULT NULL,
    `poll_data` json DEFAULT NULL,
    `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
    `comments_locked` tinyint(1) NOT NULL DEFAULT 0,
    `status` enum('active','draft','deleted','reported','archived') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_course_id` (`course_id`),
    KEY `idx_group_id` (`group_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_visibility` (`visibility`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feed Comments Table
CREATE TABLE IF NOT EXISTS `feed_comments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `post_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `parent_comment_id` int(11) DEFAULT NULL,
    `body` text NOT NULL,
    `mentions` json DEFAULT NULL,
    `status` enum('active','deleted','reported','archived') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_post_id` (`post_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_parent_comment_id` (`parent_comment_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`post_id`) REFERENCES `feed_posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_comment_id`) REFERENCES `feed_comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feed Reactions Table
CREATE TABLE IF NOT EXISTS `feed_reactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `post_id` int(11) DEFAULT NULL,
    `comment_id` int(11) DEFAULT NULL,
    `user_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `reaction_type` enum('like','love','haha','wow','sad','angry') NOT NULL DEFAULT 'like',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_reaction` (`post_id`, `comment_id`, `user_id`),
    KEY `idx_post_id` (`post_id`),
    KEY `idx_comment_id` (`comment_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_reaction_type` (`reaction_type`),
    FOREIGN KEY (`post_id`) REFERENCES `feed_posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`comment_id`) REFERENCES `feed_comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feed Reports Table
CREATE TABLE IF NOT EXISTS `feed_reports` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `post_id` int(11) DEFAULT NULL,
    `comment_id` int(11) DEFAULT NULL,
    `reported_by_user_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `report_reason` enum('spam','inappropriate','harassment','fake_news','other') NOT NULL,
    `report_details` text DEFAULT NULL,
    `status` enum('pending','reviewed','resolved','dismissed') NOT NULL DEFAULT 'pending',
    `moderator_notes` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_post_id` (`post_id`),
    KEY `idx_comment_id` (`comment_id`),
    KEY `idx_reported_by_user_id` (`reported_by_user_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`post_id`) REFERENCES `feed_posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`comment_id`) REFERENCES `feed_comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feed Notifications Table
CREATE TABLE IF NOT EXISTS `feed_notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `notification_type` enum('mention','comment','reaction','post_pinned','comment_reply') NOT NULL,
    `post_id` int(11) DEFAULT NULL,
    `comment_id` int(11) DEFAULT NULL,
    `triggered_by_user_id` int(11) DEFAULT NULL,
    `message` text NOT NULL,
    `is_read` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_notification_type` (`notification_type`),
    KEY `idx_post_id` (`post_id`),
    KEY `idx_comment_id` (`comment_id`),
    KEY `idx_triggered_by_user_id` (`triggered_by_user_id`),
    KEY `idx_is_read` (`is_read`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`post_id`) REFERENCES `feed_posts`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`comment_id`) REFERENCES `feed_comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feed Media Files Table (for tracking uploaded files)
CREATE TABLE IF NOT EXISTS `feed_media_files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `post_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(500) NOT NULL,
    `file_type` enum('image','video','audio','document') NOT NULL,
    `file_size` int(11) NOT NULL,
    `mime_type` varchar(100) NOT NULL,
    `thumbnail_path` varchar(500) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_post_id` (`post_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_file_type` (`file_type`),
    FOREIGN KEY (`post_id`) REFERENCES `feed_posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feed Poll Votes Table
CREATE TABLE IF NOT EXISTS `feed_poll_votes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `post_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `poll_option_index` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_poll_vote` (`post_id`, `user_id`),
    KEY `idx_post_id` (`post_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_poll_option_index` (`poll_option_index`),
    FOREIGN KEY (`post_id`) REFERENCES `feed_posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Feed User Settings Table (for user preferences)
CREATE TABLE IF NOT EXISTS `feed_user_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `can_post` tinyint(1) NOT NULL DEFAULT 1,
    `can_comment` tinyint(1) NOT NULL DEFAULT 1,
    `can_react` tinyint(1) NOT NULL DEFAULT 1,
    `notification_preferences` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_settings` (`user_id`, `client_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;