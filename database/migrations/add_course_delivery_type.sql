-- Migration: Add course_delivery_type column to courses table
-- Date: 2025-01-09
-- Purpose: Separate course type (e-learning, classroom, blended, assessment) from delivery settings (self_paced, instructor_led, hybrid)

-- Add course_delivery_type column to store the original course type values
ALTER TABLE `courses` 
ADD COLUMN `course_delivery_type` enum('e-learning', 'classroom', 'blended', 'assessment') NOT NULL DEFAULT 'e-learning' 
COMMENT 'Course delivery type (e-learning, classroom, blended, assessment)' 
AFTER `course_type`;

-- Add index for the new column
ALTER TABLE `courses` 
ADD INDEX `idx_course_delivery_type` (`course_delivery_type`);

-- Update existing records to set course_delivery_type based on current course_type
UPDATE `courses` SET 
    `course_delivery_type` = CASE 
        WHEN `course_type` = 'self_paced' THEN 'e-learning'
        WHEN `course_type` = 'instructor_led' THEN 'classroom'
        WHEN `course_type` = 'hybrid' THEN 'blended'
        ELSE 'e-learning'
    END
WHERE `course_delivery_type` = 'e-learning';

-- Add comment to clarify the difference between course_type and course_delivery_type
ALTER TABLE `courses` 
MODIFY COLUMN `course_type` enum('self_paced','instructor_led','hybrid') 
COMMENT 'Course delivery method (self_paced, instructor_led, hybrid) - used for internal logic'; 