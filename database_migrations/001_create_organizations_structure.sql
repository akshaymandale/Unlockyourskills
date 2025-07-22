-- =====================================================
-- Multi-Tenant Organization Structure Migration
-- =====================================================

-- 1. Create Organizations Table
CREATE TABLE IF NOT EXISTS `organizations` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(100) UNIQUE NOT NULL,
    `client_code` VARCHAR(50) UNIQUE NOT NULL COMMENT 'Maps to existing client_id',
    `logo_path` VARCHAR(500) DEFAULT NULL,
    `primary_color` VARCHAR(7) DEFAULT '#6f42c1',
    `secondary_color` VARCHAR(7) DEFAULT '#495057',
    `accent_color` VARCHAR(7) DEFAULT '#28a745',
    `max_users` INT DEFAULT 10,
    `current_user_count` INT DEFAULT 0,
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `subscription_plan` VARCHAR(50) DEFAULT 'basic',
    `subscription_expires_at` DATETIME DEFAULT NULL,
    
    -- SSO Configuration
    `sso_enabled` BOOLEAN DEFAULT FALSE,
    `sso_provider` VARCHAR(50) DEFAULT NULL,
    `sso_client_id` VARCHAR(255) DEFAULT NULL,
    `sso_client_secret` VARCHAR(500) DEFAULT NULL,
    `sso_redirect_url` VARCHAR(500) DEFAULT NULL,
    
    -- Feature Toggles
    `features_scorm` BOOLEAN DEFAULT TRUE,
    `features_assessments` BOOLEAN DEFAULT TRUE,
    `features_surveys` BOOLEAN DEFAULT TRUE,
    `features_feedback` BOOLEAN DEFAULT TRUE,
    `features_analytics` BOOLEAN DEFAULT TRUE,
    
    -- Timestamps
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_client_code` (`client_code`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create Organization Settings Table (for flexible configurations)
CREATE TABLE IF NOT EXISTS `organization_settings` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `organization_id` INT NOT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    `setting_type` ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_org_setting` (`organization_id`, `setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add organization_id to user_profiles table
ALTER TABLE `user_profiles` 
ADD COLUMN `organization_id` INT DEFAULT NULL AFTER `id`,
ADD COLUMN `system_role` ENUM('super_admin', 'admin', 'user') DEFAULT 'user' AFTER `user_role`,
ADD COLUMN `current_password` VARCHAR(255) DEFAULT NULL AFTER `email`;

-- 4. Add foreign key constraint
ALTER TABLE `user_profiles` 
ADD CONSTRAINT `fk_user_organization` 
FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE CASCADE;

-- 5. Add indexes for performance
ALTER TABLE `user_profiles` 
ADD INDEX `idx_organization_id` (`organization_id`),
ADD INDEX `idx_system_role` (`system_role`),
ADD INDEX `idx_client_id` (`client_id`);

-- 6. Create default super admin organization
INSERT INTO `organizations` (
    `name`, 
    `slug`, 
    `client_code`, 
    `max_users`, 
    `status`,
    `subscription_plan`,
    `features_scorm`,
    `features_assessments`, 
    `features_surveys`,
    `features_feedback`,
    `features_analytics`
) VALUES (
    'Super Admin Organization',
    'super-admin',
    'SUPER_ADMIN',
    999999,
    'active',
    'enterprise',
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE
);

-- 7. Create default super admin user
INSERT INTO `user_profiles` (
    `organization_id`,
    `client_id`,
    `profile_id`,
    `full_name`,
    `email`,
    `current_password`,
    `user_role`,
    `system_role`,
    `user_status`,
    `locked_status`,
    `language`
) VALUES (
    1, -- Super Admin Organization ID
    'SUPER_ADMIN',
    'SA001',
    'Super Administrator',
    'superadmin@unlockyourskills.com',
    'SuperAdmin@123', -- You should change this password
    'Super Admin',
    'super_admin',
    'Active',
    'Unlocked',
    'en'
);
