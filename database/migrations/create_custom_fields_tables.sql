-- Create custom_fields table to store field definitions
CREATE TABLE IF NOT EXISTS `custom_fields` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `client_id` int(11) NOT NULL,
    `field_name` varchar(255) NOT NULL,
    `field_label` varchar(255) NOT NULL,
    `field_type` enum('text', 'textarea', 'select', 'radio', 'checkbox', 'file', 'date', 'number', 'email', 'phone') NOT NULL,
    `field_options` text DEFAULT NULL COMMENT 'JSON array for select/radio/checkbox options',
    `is_required` tinyint(1) DEFAULT 0,
    `field_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_id` (`client_id`),
    KEY `idx_field_order` (`field_order`),
    CONSTRAINT `fk_custom_fields_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create custom_field_values table to store user custom field data
CREATE TABLE IF NOT EXISTS `custom_field_values` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `custom_field_id` int(11) NOT NULL,
    `field_value` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_field` (`user_id`, `custom_field_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_custom_field_id` (`custom_field_id`),
    CONSTRAINT `fk_custom_field_values_user` FOREIGN KEY (`user_id`) REFERENCES `user_profiles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_custom_field_values_field` FOREIGN KEY (`custom_field_id`) REFERENCES `custom_fields` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration script to remove customised_1 to customised_10 columns from user_profiles
-- Note: This should be run after ensuring all data is migrated to the new system
-- ALTER TABLE `user_profiles` 
-- DROP COLUMN `customised_1`,
-- DROP COLUMN `customised_2`,
-- DROP COLUMN `customised_3`,
-- DROP COLUMN `customised_4`,
-- DROP COLUMN `customised_5`,
-- DROP COLUMN `customised_6`,
-- DROP COLUMN `customised_7`,
-- DROP COLUMN `customised_8`,
-- DROP COLUMN `customised_9`,
-- DROP COLUMN `customised_10`;
