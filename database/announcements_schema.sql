-- Announcement Management System Database Schema
-- Following the same patterns as Opinion Polls

-- Main announcements table
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    body LONGTEXT NOT NULL,
    audience_type ENUM('global', 'course_specific', 'group_specific') NOT NULL DEFAULT 'global',
    urgency ENUM('info', 'warning', 'urgent') NOT NULL DEFAULT 'info',
    require_acknowledgment BOOLEAN NOT NULL DEFAULT FALSE,
    cta_label VARCHAR(100) NULL,
    cta_url VARCHAR(500) NULL,
    start_datetime DATETIME NULL,
    end_datetime DATETIME NULL,
    status ENUM('draft', 'active', 'scheduled', 'expired', 'archived') NOT NULL DEFAULT 'draft',
    created_by INT NOT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
    
    INDEX idx_client_id (client_id),
    INDEX idx_audience_type (audience_type),
    INDEX idx_status (status),
    INDEX idx_urgency (urgency),
    INDEX idx_start_datetime (start_datetime),
    INDEX idx_end_datetime (end_datetime),
    INDEX idx_created_by (created_by),
    INDEX idx_is_deleted (is_deleted),
    
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES user_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES user_profiles(id) ON DELETE SET NULL
);

-- Announcement target courses (for course-specific announcements)
CREATE TABLE announcement_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    course_id INT NOT NULL,
    client_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
    
    INDEX idx_announcement_id (announcement_id),
    INDEX idx_course_id (course_id),
    INDEX idx_client_id (client_id),
    INDEX idx_is_deleted (is_deleted),
    
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    UNIQUE KEY unique_announcement_course (announcement_id, course_id, client_id)
);

-- Announcement target groups (for group-specific announcements)
CREATE TABLE announcement_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    group_id INT NOT NULL,
    client_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
    
    INDEX idx_announcement_id (announcement_id),
    INDEX idx_group_id (group_id),
    INDEX idx_client_id (client_id),
    INDEX idx_is_deleted (is_deleted),
    
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    UNIQUE KEY unique_announcement_group (announcement_id, group_id, client_id)
);

-- Announcement attachments
CREATE TABLE announcement_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    client_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    upload_order INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
    
    INDEX idx_announcement_id (announcement_id),
    INDEX idx_client_id (client_id),
    INDEX idx_file_type (file_type),
    INDEX idx_is_deleted (is_deleted),
    
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- User acknowledgments
CREATE TABLE announcement_acknowledgments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_id INT NOT NULL,
    client_id INT NOT NULL,
    acknowledged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    
    INDEX idx_announcement_id (announcement_id),
    INDEX idx_user_id (user_id),
    INDEX idx_client_id (client_id),
    INDEX idx_acknowledged_at (acknowledged_at),
    
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_acknowledgment (announcement_id, user_id, client_id)
);

-- User views tracking (for analytics)
CREATE TABLE announcement_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_id INT NOT NULL,
    client_id INT NOT NULL,
    first_viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    view_count INT NOT NULL DEFAULT 1,
    time_spent_seconds INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    
    INDEX idx_announcement_id (announcement_id),
    INDEX idx_user_id (user_id),
    INDEX idx_client_id (client_id),
    INDEX idx_first_viewed_at (first_viewed_at),
    
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_view (announcement_id, user_id, client_id)
);

-- Notification log for announcements
CREATE TABLE announcement_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_id INT NOT NULL,
    client_id INT NOT NULL,
    notification_type ENUM('in_app', 'email', 'push') NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'read') NOT NULL DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_announcement_id (announcement_id),
    INDEX idx_user_id (user_id),
    INDEX idx_client_id (client_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at),
    
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Add indexes for performance optimization
CREATE INDEX idx_announcements_active_global ON announcements (client_id, status, audience_type, start_datetime, end_datetime) 
WHERE status = 'active' AND audience_type = 'global' AND is_deleted = FALSE;

CREATE INDEX idx_announcements_active_course ON announcements (client_id, status, audience_type, start_datetime, end_datetime) 
WHERE status = 'active' AND audience_type = 'course_specific' AND is_deleted = FALSE;

CREATE INDEX idx_announcements_scheduled ON announcements (client_id, status, start_datetime) 
WHERE status = 'scheduled' AND is_deleted = FALSE;

-- Sample data for testing (optional)
-- INSERT INTO announcements (client_id, title, body, audience_type, urgency, created_by, status) VALUES
-- (1, 'Welcome to the Platform', 'Welcome to our learning platform! We are excited to have you here.', 'global', 'info', 1, 'active'),
-- (1, 'System Maintenance Notice', 'The system will be under maintenance on Sunday from 2 AM to 4 AM.', 'global', 'warning', 1, 'active'),
-- (1, 'New Course Available', 'Check out our new advanced programming course!', 'global', 'info', 1, 'active');
