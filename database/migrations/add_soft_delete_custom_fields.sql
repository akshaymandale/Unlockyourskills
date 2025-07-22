-- =====================================================
-- Add Soft Delete Support to Custom Fields Tables
-- =====================================================
-- Date: 2025-06-18
-- Description: Add is_deleted column to custom_fields and custom_field_values tables
--              to implement soft deletion instead of hard deletion

-- Add is_deleted column to custom_fields table
ALTER TABLE `custom_fields` 
ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`,
ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `is_deleted`,
ADD COLUMN `deleted_by` INT(11) NULL DEFAULT NULL AFTER `deleted_at`;

-- Add index for soft delete queries
ALTER TABLE `custom_fields` 
ADD INDEX `idx_is_deleted` (`is_deleted`),
ADD INDEX `idx_deleted_at` (`deleted_at`);

-- Add foreign key for deleted_by (references user who deleted the field)
ALTER TABLE `custom_fields` 
ADD CONSTRAINT `fk_custom_fields_deleted_by` 
FOREIGN KEY (`deleted_by`) REFERENCES `user_profiles`(`id`) ON DELETE SET NULL;

-- Add is_deleted column to custom_field_values table
ALTER TABLE `custom_field_values` 
ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `field_value`,
ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `is_deleted`,
ADD COLUMN `deleted_by` INT(11) NULL DEFAULT NULL AFTER `deleted_at`;

-- Add index for soft delete queries
ALTER TABLE `custom_field_values` 
ADD INDEX `idx_is_deleted` (`is_deleted`),
ADD INDEX `idx_deleted_at` (`deleted_at`);

-- Add foreign key for deleted_by (references user who deleted the value)
ALTER TABLE `custom_field_values` 
ADD CONSTRAINT `fk_custom_field_values_deleted_by` 
FOREIGN KEY (`deleted_by`) REFERENCES `user_profiles`(`id`) ON DELETE SET NULL;

-- Update existing queries to exclude deleted records by default
-- Note: Application code will need to be updated to include WHERE is_deleted = 0 conditions
