-- Migration: Remove course-level assessment fields
-- This migration removes max_attempts, passing_score, and status fields from course creation functionality
-- as they are no longer required for the course creation process.

-- Remove fields from courses table
ALTER TABLE courses DROP COLUMN max_attempts;
ALTER TABLE courses DROP COLUMN passing_score;
ALTER TABLE courses DROP COLUMN status;

-- Remove fields from course_assessments table
ALTER TABLE course_assessments DROP COLUMN max_attempts;
ALTER TABLE course_assessments DROP COLUMN passing_score;

-- Note: The course_status field remains in the courses table as it's used for active/inactive status
-- Note: Assessment-level settings (max_attempts, passing_score) are now handled at the VLR level
-- Note: Course enrollment status remains in course_enrollments table for tracking student progress 