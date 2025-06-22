-- Event Management System Database Tables
-- Created for Announcement Event Management feature

-- Events table
CREATE TABLE IF NOT EXISTS `events` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `client_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `event_type` enum('live_class', 'webinar', 'deadline', 'maintenance', 'meeting', 'workshop') NOT NULL,
    `event_link` varchar(500) DEFAULT NULL,
    `start_datetime` datetime NOT NULL,
    `end_datetime` datetime DEFAULT NULL,
    `audience_type` enum('global', 'course_specific', 'group_specific') NOT NULL,
    `location` varchar(255) DEFAULT NULL,
    `enable_rsvp` tinyint(1) DEFAULT 0,
    `send_reminder_before` int(11) DEFAULT 0 COMMENT 'Minutes before event to send reminder',
    `status` enum('active', 'draft', 'cancelled', 'completed') DEFAULT 'active',
    `created_by` int(11) NOT NULL,
    `updated_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_deleted` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_start_datetime` (`start_datetime`),
    KEY `idx_status` (`status`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_audience_type` (`audience_type`),
    KEY `idx_is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event RSVPs table
CREATE TABLE IF NOT EXISTS `event_rsvps` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `response` enum('yes', 'no', 'maybe') NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_deleted` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_event_user_rsvp` (`event_id`, `user_id`, `client_id`, `is_deleted`),
    KEY `idx_event_id` (`event_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_response` (`response`),
    KEY `idx_is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event audiences table (for course/group specific events)
CREATE TABLE IF NOT EXISTS `event_audiences` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_id` int(11) NOT NULL,
    `audience_type` enum('course', 'group') NOT NULL,
    `audience_id` int(11) NOT NULL COMMENT 'Course ID or Group ID',
    `client_id` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_deleted` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_event_id` (`event_id`),
    KEY `idx_audience_type` (`audience_type`),
    KEY `idx_audience_id` (`audience_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event attachments table (for future use)
CREATE TABLE IF NOT EXISTS `event_attachments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(500) NOT NULL,
    `file_size` int(11) NOT NULL,
    `file_type` varchar(100) NOT NULL,
    `uploaded_by` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_deleted` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_event_id` (`event_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_uploaded_by` (`uploaded_by`),
    KEY `idx_is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event notifications table (for tracking sent notifications)
CREATE TABLE IF NOT EXISTS `event_notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `client_id` int(11) NOT NULL,
    `notification_type` enum('creation', 'reminder', 'update', 'cancellation') NOT NULL,
    `sent_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` enum('sent', 'failed', 'pending') DEFAULT 'pending',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_event_id` (`event_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_notification_type` (`notification_type`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraints (optional, depending on your existing schema)
-- ALTER TABLE `events` ADD CONSTRAINT `fk_events_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `events` ADD CONSTRAINT `fk_events_created_by` FOREIGN KEY (`created_by`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE;

-- ALTER TABLE `event_rsvps` ADD CONSTRAINT `fk_event_rsvps_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `event_rsvps` ADD CONSTRAINT `fk_event_rsvps_user` FOREIGN KEY (`user_id`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `event_rsvps` ADD CONSTRAINT `fk_event_rsvps_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

-- ALTER TABLE `event_audiences` ADD CONSTRAINT `fk_event_audiences_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `event_audiences` ADD CONSTRAINT `fk_event_audiences_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

-- ALTER TABLE `event_attachments` ADD CONSTRAINT `fk_event_attachments_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `event_attachments` ADD CONSTRAINT `fk_event_attachments_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `event_attachments` ADD CONSTRAINT `fk_event_attachments_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE;

-- ALTER TABLE `event_notifications` ADD CONSTRAINT `fk_event_notifications_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `event_notifications` ADD CONSTRAINT `fk_event_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `event_notifications` ADD CONSTRAINT `fk_event_notifications_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

-- Insert sample data for testing (optional)
-- INSERT INTO `events` (`client_id`, `title`, `description`, `event_type`, `start_datetime`, `audience_type`, `enable_rsvp`, `created_by`) VALUES
-- (1, 'Welcome Webinar', 'Introduction to our platform and features', 'webinar', '2024-01-15 14:00:00', 'global', 1, 1),
-- (1, 'JavaScript Workshop', 'Advanced JavaScript concepts and best practices', 'workshop', '2024-01-20 10:00:00', 'course_specific', 1, 1),
-- (1, 'System Maintenance', 'Scheduled system maintenance and updates', 'maintenance', '2024-01-25 02:00:00', 'global', 0, 1);
