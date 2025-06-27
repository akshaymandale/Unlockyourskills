-- Add 'archived' status to events table
-- This migration adds the 'archived' status option to the events table status enum

ALTER TABLE `events` 
MODIFY COLUMN `status` enum('active', 'draft', 'cancelled', 'completed', 'archived') DEFAULT 'active';

-- Update the comment to reflect the new status option
ALTER TABLE `events` 
MODIFY COLUMN `status` enum('active', 'draft', 'cancelled', 'completed', 'archived') DEFAULT 'active' COMMENT 'Event status: active, draft, cancelled, completed, archived'; 