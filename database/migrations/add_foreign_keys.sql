-- Add Foreign Key Constraints for Progress Tracking Tables
-- This script adds the necessary foreign key constraints after table creation

USE unlockyourskills;

-- Add foreign key constraints for user_course_progress
ALTER TABLE `user_course_progress` 
ADD CONSTRAINT `user_course_progress_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `user_course_progress_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `user_course_progress_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE;

-- Add foreign key constraints for content-specific progress tables
ALTER TABLE `scorm_progress` 
ADD CONSTRAINT `scorm_progress_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `scorm_progress_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `scorm_progress_content_fk` FOREIGN KEY (`content_id`) REFERENCES `course_module_content`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `scorm_progress_package_fk` FOREIGN KEY (`scorm_package_id`) REFERENCES `scorm_packages`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `scorm_progress_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE;

ALTER TABLE `video_progress` 
ADD CONSTRAINT `video_progress_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `video_progress_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `video_progress_content_fk` FOREIGN KEY (`content_id`) REFERENCES `course_module_content`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `video_progress_package_fk` FOREIGN KEY (`video_package_id`) REFERENCES `video_package`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `video_progress_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE;

ALTER TABLE `audio_progress` 
ADD CONSTRAINT `audio_progress_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `audio_progress_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `audio_progress_content_fk` FOREIGN KEY (`content_id`) REFERENCES `course_module_content`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `audio_progress_package_fk` FOREIGN KEY (`audio_package_id`) REFERENCES `audio_package`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `audio_progress_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE;

ALTER TABLE `document_progress` 
ADD CONSTRAINT `document_progress_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `document_progress_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `document_progress_content_fk` FOREIGN KEY (`content_id`) REFERENCES `course_module_content`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `document_progress_package_fk` FOREIGN KEY (`document_package_id`) REFERENCES `documents`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `document_progress_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE;

ALTER TABLE `interactive_progress` 
ADD CONSTRAINT `interactive_progress_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `interactive_progress_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `interactive_progress_content_fk` FOREIGN KEY (`content_id`) REFERENCES `course_module_content`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `interactive_progress_package_fk` FOREIGN KEY (`interactive_package_id`) REFERENCES `interactive_ai_content_package`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `interactive_progress_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE;

ALTER TABLE `external_progress` 
ADD CONSTRAINT `external_progress_user_fk` FOREIGN KEY (`user_id`) REFERENCES `user_profiles`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `external_progress_course_fk` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `external_progress_content_fk` FOREIGN KEY (`content_id`) REFERENCES `course_module_content`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `external_progress_client_fk` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE CASCADE;

-- Foreign keys added successfully
SELECT 'Foreign key constraints added successfully' as status;

