-- Add video_status column to video_progress table
-- This column will track video playback status similar to audio_status

ALTER TABLE `video_progress` 
ADD COLUMN `video_status` ENUM('not_started', 'in_progress', 'completed', 'paused', 'stopped') 
DEFAULT 'not_started' 
AFTER `is_completed`;

-- Update existing records to have appropriate status
UPDATE `video_progress` 
SET `video_status` = CASE 
    WHEN `is_completed` = 1 THEN 'completed'
    WHEN `watched_percentage` > 0 THEN 'in_progress'
    ELSE 'not_started'
END;

-- Add index for better performance on status queries
CREATE INDEX `idx_video_progress_status` ON `video_progress` (`video_status`, `user_id`, `course_id`);

-- Add index for status and completion queries
CREATE INDEX `idx_video_progress_status_completion` ON `video_progress` (`video_status`, `is_completed`, `user_id`);
