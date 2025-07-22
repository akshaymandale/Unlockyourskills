-- Migration to remove redundant prerequisite_course_id field
-- This field was redundant because prerequisite_id already handles all prerequisite types

-- First drop the foreign key constraint
ALTER TABLE course_prerequisites DROP FOREIGN KEY IF EXISTS course_prerequisites_ibfk_2;

-- Remove the redundant prerequisite_course_id column
ALTER TABLE course_prerequisites DROP COLUMN IF EXISTS prerequisite_course_id;

-- Update the comment to clarify the purpose of prerequisite_id
ALTER TABLE course_prerequisites 
MODIFY COLUMN prerequisite_id INT NOT NULL COMMENT 'ID of prerequisite (references different tables based on prerequisite_type)';

-- The prerequisite_id now serves as the single source of truth for all prerequisite references:
-- - prerequisite_type = 'course' -> prerequisite_id references courses.id
-- - prerequisite_type = 'assessment' -> prerequisite_id references assessment_package.id
-- - prerequisite_type = 'survey' -> prerequisite_id references survey_package.id
-- - prerequisite_type = 'feedback' -> prerequisite_id references feedback_package.id
-- - prerequisite_type = 'scorm' -> prerequisite_id references scorm_packages.id
-- - prerequisite_type = 'video' -> prerequisite_id references video_package.id
-- - prerequisite_type = 'audio' -> prerequisite_id references audio_package.id
-- - prerequisite_type = 'interactive' -> prerequisite_id references interactive_ai_content_package.id
-- - prerequisite_type = 'assignment' -> prerequisite_id references assignment_package.id
-- - prerequisite_type = 'image' -> prerequisite_id references image_package.id
-- - And so on for other types... 