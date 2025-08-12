-- Create user_profiles table (initial migration)
CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `organization_id` INT DEFAULT NULL,
    `client_id` VARCHAR(50) DEFAULT NULL,
    `profile_id` VARCHAR(50) DEFAULT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `current_password` VARCHAR(255) DEFAULT NULL,
    `user_role` VARCHAR(50) DEFAULT NULL,
    `system_role` ENUM('super_admin', 'admin', 'user') DEFAULT 'user',
    `user_status` VARCHAR(50) DEFAULT 'Active',
    `locked_status` VARCHAR(50) DEFAULT 'Unlocked',
    `language` VARCHAR(10) DEFAULT 'en',
    `profile_picture` VARCHAR(255) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `updated_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_organization_id` (`organization_id`),
    INDEX `idx_system_role` (`system_role`),
    INDEX `idx_client_id` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a sample user for testing (id=42)
INSERT INTO `user_profiles` (
    `id`, `organization_id`, `client_id`, `profile_id`, `full_name`, `email`, `current_password`,
    `user_role`, `system_role`, `user_status`, `locked_status`, `language`, `profile_picture`,
    `created_by`, `updated_by`
) VALUES (
    42, 1, 'TEST_CLIENT', 'P42', 'Test User', 'testuser@example.com', 'password123',
    'User', 'user', 'Active', 'Unlocked', 'en', NULL, NULL, NULL
); 