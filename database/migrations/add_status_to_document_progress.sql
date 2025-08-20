-- Add status column to document_progress table
ALTER TABLE `document_progress` 
ADD COLUMN `status` ENUM('not_started', 'started', 'in_progress', 'completed') 
NOT NULL DEFAULT 'not_started' 
COMMENT 'Document completion status' 
AFTER `is_completed`;

-- Update existing records to set appropriate status
UPDATE `document_progress` 
SET `status` = CASE 
    WHEN `is_completed` = 1 THEN 'completed'
    WHEN `viewed_percentage` >= 80 THEN 'completed'
    WHEN `viewed_percentage` > 0 THEN 'in_progress'
    WHEN `current_page` > 1 THEN 'started'
    ELSE 'not_started'
END;

-- Add index for better performance
ALTER TABLE `document_progress` 
ADD INDEX `idx_status` (`status`);
