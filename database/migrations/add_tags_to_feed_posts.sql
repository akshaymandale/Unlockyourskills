-- Migration: Add tags column to feed_posts table
-- This migration adds a tags column to store comma-separated tags for social feed posts

ALTER TABLE `feed_posts` 
ADD COLUMN `tags` TEXT DEFAULT NULL AFTER `body`,
ADD INDEX `idx_tags` (`tags`(255));

-- Add a comment to document the tags column
ALTER TABLE `feed_posts` 
MODIFY COLUMN `tags` TEXT DEFAULT NULL COMMENT 'Comma-separated list of tags for the post'; 