-- Migration to fix course ordering and prerequisites issues
-- Run this after the main course tables are created

-- Update course_prerequisites table to ensure proper structure
ALTER TABLE course_prerequisites 
MODIFY COLUMN prerequisite_type ENUM('course', 'assessment', 'survey', 'feedback', 'scorm', 'video', 'audio', 'document', 'interactive', 'assignment', 'external', 'image', 'skill', 'certification') NOT NULL,
MODIFY COLUMN prerequisite_id INT NOT NULL COMMENT 'ID of prerequisite (references different tables based on prerequisite_type)',
MODIFY COLUMN sort_order INT DEFAULT 0 COMMENT 'Order of prerequisites for display';

-- Remove redundant prerequisite_course_id column if it exists
ALTER TABLE course_prerequisites DROP COLUMN IF EXISTS prerequisite_course_id;

-- Remove minimum_score column if it exists
ALTER TABLE course_prerequisites DROP COLUMN IF EXISTS minimum_score;

-- Add indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_course_order ON course_prerequisites (course_id, sort_order);
CREATE INDEX IF NOT EXISTS idx_prerequisite_type ON course_prerequisites (prerequisite_type);
CREATE INDEX IF NOT EXISTS idx_prerequisite_id ON course_prerequisites (prerequisite_id);
CREATE INDEX IF NOT EXISTS idx_deleted ON course_prerequisites (deleted_at);

-- Update course_modules table to ensure proper ordering
ALTER TABLE course_modules 
MODIFY COLUMN module_order INT DEFAULT 0 COMMENT 'Order of modules within the course';

-- Add indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_course_order ON course_modules (course_id, module_order);
CREATE INDEX IF NOT EXISTS idx_deleted ON course_modules (deleted_at);

-- Update course_module_content table to ensure proper ordering
ALTER TABLE course_module_content 
MODIFY COLUMN content_order INT DEFAULT 0 COMMENT 'Order of content within the module';

-- Add indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_module_order ON course_module_content (module_id, content_order);
CREATE INDEX IF NOT EXISTS idx_content_type ON course_module_content (content_type);
CREATE INDEX IF NOT EXISTS idx_content_id ON course_module_content (content_id);
CREATE INDEX IF NOT EXISTS idx_deleted ON course_module_content (deleted_at);

-- Update course_assessments table to ensure proper ordering
ALTER TABLE course_assessments 
MODIFY COLUMN assessment_order INT DEFAULT 0 COMMENT 'Order of assessments within the course';

-- Add indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_course_order ON course_assessments (course_id, assessment_order);
CREATE INDEX IF NOT EXISTS idx_assessment_type ON course_assessments (assessment_type);
CREATE INDEX IF NOT EXISTS idx_assessment_id ON course_assessments (assessment_id);
CREATE INDEX IF NOT EXISTS idx_deleted ON course_assessments (deleted_at);

-- Update course_feedback table to ensure proper ordering
ALTER TABLE course_feedback 
MODIFY COLUMN feedback_order INT DEFAULT 0 COMMENT 'Order of feedback forms within the course';

-- Add indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_course_order ON course_feedback (course_id, feedback_order);
CREATE INDEX IF NOT EXISTS idx_feedback_type ON course_feedback (feedback_type);
CREATE INDEX IF NOT EXISTS idx_feedback_id ON course_feedback (feedback_id);
CREATE INDEX IF NOT EXISTS idx_deleted ON course_feedback (deleted_at);

-- Update course_surveys table to ensure proper ordering
ALTER TABLE course_surveys 
MODIFY COLUMN survey_order INT DEFAULT 0 COMMENT 'Order of surveys within the course';

-- Add indexes if they don't exist
CREATE INDEX IF NOT EXISTS idx_course_order ON course_surveys (course_id, survey_order);
CREATE INDEX IF NOT EXISTS idx_survey_type ON course_surveys (survey_type);
CREATE INDEX IF NOT EXISTS idx_survey_id ON course_surveys (survey_id);
CREATE INDEX IF NOT EXISTS idx_deleted ON course_surveys (deleted_at); 