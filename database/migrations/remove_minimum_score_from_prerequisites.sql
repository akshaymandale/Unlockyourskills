-- Migration to remove minimum_score column from course_prerequisites table
-- This column was unnecessary as minimum scores should be handled at the assessment level, not the prerequisite level

-- Remove minimum_score column from course_prerequisites table
ALTER TABLE course_prerequisites DROP COLUMN IF EXISTS minimum_score;

-- Update the table structure to reflect the change
-- The prerequisite table now focuses on the relationship between courses and their prerequisites
-- without assuming specific scoring requirements that should be handled elsewhere 