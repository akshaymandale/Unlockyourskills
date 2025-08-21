-- Migration: Add audio_status column to audio_progress table
-- This column will track the current playback status of audio content

-- Add audio_status column
ALTER TABLE `audio_progress` 
ADD COLUMN `audio_status` ENUM('not_started', 'playing', 'paused', 'completed', 'stopped') NOT NULL DEFAULT 'not_started' AFTER `is_completed`;

-- Add index for better performance on status queries
ALTER TABLE `audio_progress` 
ADD INDEX `idx_audio_status` (`audio_status`);

-- Update existing records to have appropriate status
UPDATE `audio_progress` 
SET `audio_status` = CASE 
    WHEN `is_completed` = 1 THEN 'completed'
    WHEN `listened_percentage` > 0 THEN 'paused'
    ELSE 'not_started'
END;

-- Add comment to document the column purpose
ALTER TABLE `audio_progress` 
MODIFY COLUMN `audio_status` ENUM('not_started', 'playing', 'paused', 'completed', 'stopped') NOT NULL DEFAULT 'not_started' COMMENT 'Current playback status of the audio content';
