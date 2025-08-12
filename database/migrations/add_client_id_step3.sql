-- Migration Step 3: Add constraints and indexes
-- Date: 2025-01-17

-- Make client_id NOT NULL and add indexes/foreign keys
ALTER TABLE assessment_questions 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_assessment_questions_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE assessment_options 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_assessment_options_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE assessment_package 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_assessment_package_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE scorm_packages 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_scorm_packages_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE video_package 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_video_package_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE audio_package 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_audio_package_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE image_package 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_image_package_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE documents 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_documents_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE external_content 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_external_content_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE interactive_ai_content_package 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_interactive_ai_content_package_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE non_scorm_package 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_non_scorm_package_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE feedback_questions 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_feedback_questions_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE feedback_question_options 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_feedback_question_options_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE feedback_package 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_feedback_package_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE survey_questions 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_survey_questions_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE survey_question_options 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_survey_question_options_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;

ALTER TABLE survey_package 
MODIFY COLUMN client_id INT(11) NOT NULL,
ADD INDEX idx_survey_package_client_id (client_id),
ADD FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE;
