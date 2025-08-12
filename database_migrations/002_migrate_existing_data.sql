-- =====================================================
-- Migrate Existing Data to Organization Structure
-- =====================================================

-- 1. Create organizations for existing unique client_ids
INSERT INTO `organizations` (
    `name`, 
    `slug`, 
    `client_code`, 
    `max_users`, 
    `status`,
    `subscription_plan`
)
SELECT DISTINCT
    CONCAT('Organization ', UPPER(client_id)) as name,
    LOWER(REPLACE(client_id, ' ', '-')) as slug,
    client_id as client_code,
    50 as max_users, -- Default limit
    'active' as status,
    'basic' as subscription_plan
FROM `user_profiles` 
WHERE client_id IS NOT NULL 
AND client_id != '' 
AND client_id != 'SUPER_ADMIN'
AND NOT EXISTS (
    SELECT 1 FROM `organizations` WHERE `client_code` = `user_profiles`.`client_id`
);

-- 2. Update existing users with organization_id
UPDATE `user_profiles` up
JOIN `organizations` o ON up.client_id = o.client_code
SET up.organization_id = o.id
WHERE up.organization_id IS NULL;

-- 3. Set system_role based on existing user_role
UPDATE `user_profiles` 
SET `system_role` = CASE 
    WHEN `user_role` = 'Admin' THEN 'admin'
    WHEN `user_role` = 'Super Admin' THEN 'super_admin'
    ELSE 'user'
END
WHERE `system_role` = 'user';

-- 4. Update current user count for each organization
UPDATE `organizations` o
SET `current_user_count` = (
    SELECT COUNT(*) 
    FROM `user_profiles` up 
    WHERE up.organization_id = o.id 
    AND up.user_status = 'Active'
);

-- 5. Add organization_id to content tables
-- SCORM Packages
ALTER TABLE `scorm_packages` 
ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_scorm_organization` 
FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE;

-- Non-SCORM Packages  
ALTER TABLE `non_scorm_package` 
ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_non_scorm_organization` 
FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE;

-- Assessments
ALTER TABLE `assessments` 
ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_assessments_organization` 
FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE;

-- Questions
ALTER TABLE `questions` 
ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_questions_organization` 
FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE;

-- Survey Questions
ALTER TABLE `survey_questions` 
ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_survey_questions_organization` 
FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE;

-- Feedback Questions
ALTER TABLE `feedback_questions` 
ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_feedback_questions_organization` 
FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE;

-- Audio Packages
ALTER TABLE `audio_packages` 
ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_audio_packages_organization` 
FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE;

-- Video Packages
ALTER TABLE `video_packages` 
ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_video_packages_organization` 
FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE;

-- Image Packages
ALTER TABLE `image_packages` 
ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_image_packages_organization` 
FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE;

-- Document Packages
ALTER TABLE `document_packages` 
ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_document_packages_organization` 
FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE;

-- External Content
ALTER TABLE `external_content` 
ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_external_content_organization` 
FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE;

-- Interactive Content
ALTER TABLE `interactive_content` 
ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_interactive_content_organization` 
FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE;
