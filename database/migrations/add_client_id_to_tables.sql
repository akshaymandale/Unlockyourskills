-- Migration: Add client_id to all relevant tables for client data isolation
-- Date: 2025-01-17
-- Description: Ensures all content and data is properly isolated by client

-- Add client_id to assessment_questions table
ALTER TABLE assessment_questions
ADD COLUMN client_id INT(11) NULL AFTER id;

-- Add client_id to assessment_options table
ALTER TABLE assessment_options 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to assessment_package table
ALTER TABLE assessment_package 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to scorm_packages table
ALTER TABLE scorm_packages 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to video_package table
ALTER TABLE video_package 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to audio_package table
ALTER TABLE audio_package 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to image_package table
ALTER TABLE image_package 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to documents table
ALTER TABLE documents 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to external_content table
ALTER TABLE external_content 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to interactive_ai_content_package table
ALTER TABLE interactive_ai_content_package 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to non_scorm_package table
ALTER TABLE non_scorm_package 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to feedback_questions table
ALTER TABLE feedback_questions 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to feedback_question_options table
ALTER TABLE feedback_question_options 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to feedback_package table
ALTER TABLE feedback_package 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to survey_questions table
ALTER TABLE survey_questions 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to survey_question_options table
ALTER TABLE survey_question_options 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Add client_id to survey_package table
ALTER TABLE survey_package 
ADD COLUMN client_id INT(11) NOT NULL AFTER id,
ADD INDEX idx_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

-- Note: custom_fields and user_profiles already have client_id
-- Note: Tables like countries, states, cities, languages are global and don't need client_id

-- Update existing data to assign to a default client (if any exists)
-- This is a one-time operation for existing data
SET @default_client_id = (SELECT id FROM clients ORDER BY id LIMIT 1);

-- Update all tables with the default client_id for existing records
UPDATE assessment_questions SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE assessment_options SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE assessment_package SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE scorm_packages SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE video_package SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE audio_package SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE image_package SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE documents SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE external_content SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE interactive_ai_content_package SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE non_scorm_package SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE feedback_questions SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE feedback_question_options SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE feedback_package SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE survey_questions SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE survey_question_options SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
UPDATE survey_package SET client_id = @default_client_id WHERE client_id = 0 OR client_id IS NULL;
