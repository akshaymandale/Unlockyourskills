-- Create image_progress table for tracking image viewing status
CREATE TABLE IF NOT EXISTS `image_progress` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `course_id` int(11) NOT NULL,
    `content_id` int(11) NOT NULL COMMENT 'ID from course_module_content',
    `image_package_id` int(11) NOT NULL COMMENT 'ID from image_package',
    `client_id` int(11) NOT NULL,
    `viewed_at` timestamp NULL DEFAULT NULL COMMENT 'When the image was first viewed',
    `view_count` int(11) DEFAULT 0 COMMENT 'Number of times image was viewed',
    `is_completed` tinyint(1) DEFAULT 0 COMMENT 'Whether image viewing is marked as complete',
    `image_status` ENUM('not_viewed', 'viewed', 'completed') DEFAULT 'not_viewed' COMMENT 'Current status of image viewing',
    `notes` text COMMENT 'User notes about the image',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_image` (`user_id`, `course_id`, `content_id`, `client_id`),
    KEY `idx_image_progress_user` (`user_id`),
    KEY `idx_image_progress_course` (`course_id`),
    KEY `idx_image_progress_content` (`content_id`),
    KEY `idx_image_progress_client` (`client_id`),
    KEY `idx_image_progress_status` (`image_status`),
    KEY `idx_image_progress_completion` (`is_completed`, `user_id`, `course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Track image viewing progress and status';

-- Add foreign key constraints
ALTER TABLE `image_progress` 
ADD CONSTRAINT `fk_image_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_image_progress_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_image_progress_content` FOREIGN KEY (`content_id`) REFERENCES `course_module_content` (`id`) ON DELETE CASCADE;
