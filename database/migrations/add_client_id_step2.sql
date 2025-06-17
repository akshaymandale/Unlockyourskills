-- Migration Step 2: Update existing data with default client_id
-- Date: 2025-01-17

-- Get the first client ID (usually Super Admin or first created client)
SET @default_client_id = (SELECT id FROM clients ORDER BY id LIMIT 1);

-- Update all tables with the default client_id for existing records
UPDATE assessment_questions SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE assessment_options SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE assessment_package SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE scorm_packages SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE video_package SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE audio_package SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE image_package SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE documents SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE external_content SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE interactive_ai_content_package SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE non_scorm_package SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE feedback_questions SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE feedback_question_options SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE feedback_package SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE survey_questions SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE survey_question_options SET client_id = @default_client_id WHERE client_id IS NULL;
UPDATE survey_package SET client_id = @default_client_id WHERE client_id IS NULL;
