-- Migration to fix prerequisite type mapping
-- This migration updates the course_prerequisites table to properly handle different VLR content types

-- Update the prerequisite_type enum to include all VLR content types
ALTER TABLE course_prerequisites 
MODIFY COLUMN prerequisite_type ENUM('course', 'assessment', 'survey', 'feedback', 'scorm', 'video', 'audio', 'document', 'interactive', 'assignment', 'external', 'image', 'skill', 'certification') NOT NULL;

-- Update the comment to clarify what prerequisite_id references
ALTER TABLE course_prerequisites 
MODIFY COLUMN prerequisite_id INT NOT NULL COMMENT 'ID of prerequisite course, assessment, or VLR content package';

-- The prerequisite_id now properly references:
-- - 'course' -> courses.id
-- - 'assessment' -> assessment_package.id
-- - 'survey' -> survey_package.id
-- - 'feedback' -> feedback_package.id
-- - 'scorm' -> scorm_packages.id
-- - 'video' -> video_package.id
-- - 'audio' -> audio_package.id
-- - 'document' -> document_package.id (if exists)
-- - 'interactive' -> interactive_ai_content_package.id
-- - 'assignment' -> assignment_package.id
-- - 'external' -> external_package.id (if exists)
-- - 'image' -> image_package.id
-- - 'skill' -> skills table (if exists)
-- - 'certification' -> certifications table (if exists) 