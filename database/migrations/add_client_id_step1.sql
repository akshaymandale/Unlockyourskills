-- Migration Step 1: Add client_id columns (nullable first)
-- Date: 2025-01-17

-- Add client_id to assessment_questions table
ALTER TABLE assessment_questions ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to assessment_options table  
ALTER TABLE assessment_options ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to assessment_package table
ALTER TABLE assessment_package ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to scorm_packages table
ALTER TABLE scorm_packages ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to video_package table
ALTER TABLE video_package ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to audio_package table
ALTER TABLE audio_package ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to image_package table
ALTER TABLE image_package ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to documents table
ALTER TABLE documents ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to external_content table
ALTER TABLE external_content ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to interactive_ai_content_package table
ALTER TABLE interactive_ai_content_package ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to non_scorm_package table
ALTER TABLE non_scorm_package ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to feedback_questions table
ALTER TABLE feedback_questions ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to feedback_question_options table
ALTER TABLE feedback_question_options ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to feedback_package table
ALTER TABLE feedback_package ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to survey_questions table
ALTER TABLE survey_questions ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to survey_question_options table
ALTER TABLE survey_question_options ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to survey_package table
ALTER TABLE survey_package ADD COLUMN client_id INT(11) NULL AFTER id;
