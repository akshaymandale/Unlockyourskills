-- Migration: Add client_id column to course_applicability table
-- This ensures that course applicability rules are properly scoped to the client organization

-- Add client_id column
ALTER TABLE `course_applicability` 
ADD COLUMN `client_id` int(11) NOT NULL AFTER `user_id`;

-- Add index for better performance
ALTER TABLE `course_applicability` 
ADD INDEX `idx_client_id` (`client_id`);

-- Update existing records with client_id from the courses table
-- This assumes that courses table has client_id column
UPDATE `course_applicability` ca 
INNER JOIN `courses` c ON ca.course_id = c.id 
SET ca.client_id = c.client_id 
WHERE ca.client_id = 0 OR ca.client_id IS NULL;

-- Make client_id NOT NULL after populating existing records
ALTER TABLE `course_applicability` 
MODIFY COLUMN `client_id` int(11) NOT NULL;

-- Add foreign key constraint for referential integrity
ALTER TABLE `course_applicability` 
ADD CONSTRAINT `fk_course_applicability_client_id` 
FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) 
ON DELETE CASCADE ON UPDATE CASCADE;
