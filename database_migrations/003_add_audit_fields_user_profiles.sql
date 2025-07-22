-- =====================================================
-- Add Audit Fields to user_profiles Table
-- =====================================================

-- Add created_by and updated_by columns to user_profiles table
ALTER TABLE `user_profiles` 
ADD COLUMN `created_by` INT DEFAULT NULL AFTER `retirement_date`,
ADD COLUMN `updated_by` INT DEFAULT NULL AFTER `created_by`;

-- Add foreign key constraints for audit fields
ALTER TABLE `user_profiles` 
ADD CONSTRAINT `fk_user_created_by` 
FOREIGN KEY (`created_by`) REFERENCES `user_profiles`(`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_user_updated_by` 
FOREIGN KEY (`updated_by`) REFERENCES `user_profiles`(`id`) ON DELETE SET NULL;

-- Add indexes for performance
ALTER TABLE `user_profiles` 
ADD INDEX `idx_created_by` (`created_by`),
ADD INDEX `idx_updated_by` (`updated_by`);

-- Update existing records to set created_by to the first admin user of each client
-- This is a one-time migration for existing data
UPDATE `user_profiles` up1
SET `created_by` = (
    SELECT up2.id 
    FROM `user_profiles` up2 
    WHERE up2.client_id = up1.client_id 
    AND up2.system_role IN ('admin', 'super_admin')
    AND up2.user_status = 'Active'
    ORDER BY up2.id ASC 
    LIMIT 1
)
WHERE `created_by` IS NULL;

-- For records where no admin exists in the same client, set to super admin
UPDATE `user_profiles` 
SET `created_by` = (
    SELECT id FROM `user_profiles` 
    WHERE system_role = 'super_admin' 
    AND user_status = 'Active'
    ORDER BY id ASC 
    LIMIT 1
)
WHERE `created_by` IS NULL;
