-- Migration: Add client_id to interactive_ai_content_package table
-- Date: 2024-01-XX

-- Add client_id column after id column
ALTER TABLE `interactive_ai_content_package` 
ADD COLUMN `client_id` int(11) NOT NULL AFTER `id`;

-- Add foreign key constraint (optional - uncomment if you want to enforce referential integrity)
-- ALTER TABLE `interactive_ai_content_package` 
-- ADD CONSTRAINT `fk_interactive_content_client` 
-- FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE;

-- Add index for better performance
ALTER TABLE `interactive_ai_content_package` 
ADD INDEX `idx_client_id` (`client_id`);

-- Update existing records to have a default client_id (if any exist)
-- Replace 1 with the appropriate default client ID
-- UPDATE `interactive_ai_content_package` SET `client_id` = 1 WHERE `client_id` = 0; 