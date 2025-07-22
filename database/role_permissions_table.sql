-- Role Permissions Table
-- This table stores module permissions for each user role

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `can_access` tinyint(1) NOT NULL DEFAULT 0,
  `can_create` tinyint(1) NOT NULL DEFAULT 0,
  `can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_role_module` (`role_id`, `module_name`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_module_name` (`module_name`),
  CONSTRAINT `fk_role_permissions_role_id` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default permissions for existing roles
-- This will give basic access to common modules for all roles

INSERT INTO `role_permissions` (`role_id`, `module_name`, `can_access`, `can_create`, `can_edit`, `can_delete`) 
SELECT 
    ur.id,
    'dashboard',
    1, 0, 0, 0
FROM `user_roles` ur 
WHERE ur.is_active = 1;

INSERT INTO `role_permissions` (`role_id`, `module_name`, `can_access`, `can_create`, `can_edit`, `can_delete`) 
SELECT 
    ur.id,
    'user_management',
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END
FROM `user_roles` ur 
WHERE ur.is_active = 1;

INSERT INTO `role_permissions` (`role_id`, `module_name`, `can_access`, `can_create`, `can_edit`, `can_delete`) 
SELECT 
    ur.id,
    'course_management',
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'instructor') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'instructor') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'instructor') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END
FROM `user_roles` ur 
WHERE ur.is_active = 1;

INSERT INTO `role_permissions` (`role_id`, `module_name`, `can_access`, `can_create`, `can_edit`, `can_delete`) 
SELECT 
    ur.id,
    'reports',
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'manager') THEN 1 ELSE 0 END,
    0, 0, 0
FROM `user_roles` ur 
WHERE ur.is_active = 1;

INSERT INTO `role_permissions` (`role_id`, `module_name`, `can_access`, `can_create`, `can_edit`, `can_delete`) 
SELECT 
    ur.id,
    'social_feed',
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'manager', 'instructor', 'learner') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'manager', 'instructor') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'manager') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END
FROM `user_roles` ur 
WHERE ur.is_active = 1;

INSERT INTO `role_permissions` (`role_id`, `module_name`, `can_access`, `can_create`, `can_edit`, `can_delete`) 
SELECT 
    ur.id,
    'announcements',
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'manager', 'instructor', 'learner') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'manager') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'manager') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END
FROM `user_roles` ur 
WHERE ur.is_active = 1;

INSERT INTO `role_permissions` (`role_id`, `module_name`, `can_access`, `can_create`, `can_edit`, `can_delete`) 
SELECT 
    ur.id,
    'events',
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'manager', 'instructor', 'learner') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'manager') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'manager') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END
FROM `user_roles` ur 
WHERE ur.is_active = 1;

INSERT INTO `role_permissions` (`role_id`, `module_name`, `can_access`, `can_create`, `can_edit`, `can_delete`) 
SELECT 
    ur.id,
    'opinion_polls',
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'manager', 'instructor', 'learner') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'manager') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'manager') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END
FROM `user_roles` ur 
WHERE ur.is_active = 1;

INSERT INTO `role_permissions` (`role_id`, `module_name`, `can_access`, `can_create`, `can_edit`, `can_delete`) 
SELECT 
    ur.id,
    'vlr',
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'instructor') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'instructor') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin', 'instructor') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END
FROM `user_roles` ur 
WHERE ur.is_active = 1;

INSERT INTO `role_permissions` (`role_id`, `module_name`, `can_access`, `can_create`, `can_edit`, `can_delete`) 
SELECT 
    ur.id,
    'settings',
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END,
    CASE WHEN ur.system_role IN ('super_admin', 'admin') THEN 1 ELSE 0 END
FROM `user_roles` ur 
WHERE ur.is_active = 1; 