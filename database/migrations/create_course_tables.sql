-- Course Creation System Database Migration
-- This migration creates all necessary tables for the course creation system

-- =====================================================
-- COURSE CATEGORIES AND SUBCATEGORIES
-- =====================================================

-- Course Categories Table
CREATE TABLE IF NOT EXISTS course_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(100),
    description TEXT,
    color VARCHAR(7) DEFAULT '#4b0082',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    created_by INT,
    updated_by INT,
    INDEX idx_client_active (client_id, is_active),
    INDEX idx_sort_order (sort_order),
    INDEX idx_deleted (deleted_at)
);

-- Course Subcategories Table
CREATE TABLE IF NOT EXISTS course_subcategories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    client_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    icon VARCHAR(100),
    description TEXT,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (category_id) REFERENCES course_categories(id) ON DELETE CASCADE,
    INDEX idx_category_active (category_id, is_active),
    INDEX idx_client_active (client_id, is_active),
    INDEX idx_sort_order (sort_order),
    INDEX idx_deleted (deleted_at)
);

-- =====================================================
-- MAIN COURSES TABLE
-- =====================================================

-- Courses Table
CREATE TABLE IF NOT EXISTS courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    category_id INT,
    subcategory_id INT,
    course_type ENUM('self_paced', 'instructor_led', 'hybrid') DEFAULT 'self_paced',
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    duration_hours INT DEFAULT 0,
    duration_minutes INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    passing_score INT DEFAULT 70,
    is_self_paced BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_published BOOLEAN DEFAULT FALSE,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    thumbnail_image VARCHAR(500),
    banner_image VARCHAR(500),
    tags JSON,
    learning_objectives JSON,
    prerequisites TEXT,
    target_audience TEXT,
    certificate_template VARCHAR(500),
    completion_criteria TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (category_id) REFERENCES course_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (subcategory_id) REFERENCES course_subcategories(id) ON DELETE SET NULL,
    INDEX idx_client_status (client_id, status),
    INDEX idx_category (category_id),
    INDEX idx_subcategory (subcategory_id),
    INDEX idx_published (is_published),
    INDEX idx_featured (is_featured),
    INDEX idx_deleted (deleted_at),
    INDEX idx_created_by (created_by),
    FULLTEXT idx_search (name, description, short_description)
);

-- =====================================================
-- COURSE MODULES AND CONTENT
-- =====================================================

-- Course Modules Table
CREATE TABLE IF NOT EXISTS course_modules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    module_order INT DEFAULT 0,
    is_required BOOLEAN DEFAULT TRUE,
    estimated_duration INT DEFAULT 0, -- in minutes
    learning_objectives JSON,
    completion_criteria TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course_order (course_id, module_order),
    INDEX idx_deleted (deleted_at)
);

-- Course Module Content Table
CREATE TABLE IF NOT EXISTS course_module_content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    module_id INT NOT NULL,
    content_type VARCHAR(50) NOT NULL, -- 'scorm', 'video', 'audio', 'document', 'interactive', 'assignment'
    content_id INT NOT NULL, -- ID from respective VLR table
    title VARCHAR(255),
    description TEXT,
    content_order INT DEFAULT 0,
    is_required BOOLEAN DEFAULT TRUE,
    estimated_duration INT DEFAULT 0, -- in minutes
    completion_criteria TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE CASCADE,
    INDEX idx_module_order (module_id, content_order),
    INDEX idx_content_type (content_type),
    INDEX idx_content_id (content_id),
    INDEX idx_deleted (deleted_at)
);

-- =====================================================
-- COURSE PREREQUISITES
-- =====================================================

-- Course Prerequisites Table
CREATE TABLE IF NOT EXISTS course_prerequisites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    prerequisite_type ENUM('course', 'assessment', 'survey', 'feedback', 'scorm', 'video', 'audio', 'document', 'interactive', 'assignment', 'external', 'image', 'skill', 'certification') NOT NULL,
    prerequisite_id INT NOT NULL, -- ID of prerequisite (references different tables based on prerequisite_type)
    prerequisite_name VARCHAR(255), -- Display name
    prerequisite_description TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course_order (course_id, sort_order),
    INDEX idx_prerequisite_type (prerequisite_type),
    INDEX idx_prerequisite_id (prerequisite_id),
    INDEX idx_deleted (deleted_at)
);

-- =====================================================
-- COURSE ASSESSMENTS
-- =====================================================

-- Course Assessments Table
CREATE TABLE IF NOT EXISTS course_assessments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    assessment_id INT NOT NULL, -- Reference to assessments table
    assessment_type ENUM('pre_course', 'post_course', 'module_assessment') DEFAULT 'post_course',
    module_id INT NULL, -- For module-specific assessments
    title VARCHAR(255),
    description TEXT,
    is_required BOOLEAN DEFAULT TRUE,
    passing_score INT DEFAULT 70,
    max_attempts INT DEFAULT 3,
    time_limit INT DEFAULT 0, -- in minutes, 0 = no limit
    assessment_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE SET NULL,
    INDEX idx_course_order (course_id, assessment_order),
    INDEX idx_assessment_type (assessment_type),
    INDEX idx_assessment_id (assessment_id),
    INDEX idx_deleted (deleted_at)
);

-- =====================================================
-- COURSE FEEDBACK
-- =====================================================

-- Course Feedback Table
CREATE TABLE IF NOT EXISTS course_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    feedback_id INT NOT NULL, -- Reference to feedback_questions table
    feedback_type ENUM('pre_course', 'post_course', 'module_feedback') DEFAULT 'post_course',
    module_id INT NULL, -- For module-specific feedback
    title VARCHAR(255),
    description TEXT,
    is_required BOOLEAN DEFAULT FALSE,
    feedback_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE SET NULL,
    INDEX idx_course_order (course_id, feedback_order),
    INDEX idx_feedback_type (feedback_type),
    INDEX idx_feedback_id (feedback_id),
    INDEX idx_deleted (deleted_at)
);

-- =====================================================
-- COURSE SURVEYS
-- =====================================================

-- Course Surveys Table
CREATE TABLE IF NOT EXISTS course_surveys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    survey_id INT NOT NULL, -- Reference to survey_questions table
    survey_type ENUM('pre_course', 'post_course', 'module_survey') DEFAULT 'post_course',
    module_id INT NULL, -- For module-specific surveys
    title VARCHAR(255),
    description TEXT,
    is_required BOOLEAN DEFAULT FALSE,
    survey_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    created_by INT,
    updated_by INT,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE SET NULL,
    INDEX idx_course_order (course_id, survey_order),
    INDEX idx_survey_type (survey_type),
    INDEX idx_survey_id (survey_id),
    INDEX idx_deleted (deleted_at)
);

-- =====================================================
-- COURSE ENROLLMENTS AND PROGRESS
-- =====================================================

-- Course Enrollments Table
CREATE TABLE IF NOT EXISTS course_enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    user_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completion_date TIMESTAMP NULL,
    status ENUM('enrolled', 'in_progress', 'completed', 'dropped') DEFAULT 'enrolled',
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    completion_time INT DEFAULT 0, -- in minutes
    last_accessed_at TIMESTAMP NULL,
    certificate_issued BOOLEAN DEFAULT FALSE,
    certificate_issued_at TIMESTAMP NULL,
    certificate_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (course_id, user_id),
    INDEX idx_course_status (course_id, status),
    INDEX idx_user_status (user_id, status),
    INDEX idx_completion_date (completion_date),
    INDEX idx_deleted (deleted_at)
);

-- Module Progress Table
CREATE TABLE IF NOT EXISTS module_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    module_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    time_spent INT DEFAULT 0, -- in minutes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES course_enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_module_progress (enrollment_id, module_id),
    INDEX idx_enrollment_status (enrollment_id, status),
    INDEX idx_completion_date (completed_at)
);

-- Content Progress Table
CREATE TABLE IF NOT EXISTS content_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    content_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    completion_percentage DECIMAL(5,2) DEFAULT 0.00,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    time_spent INT DEFAULT 0, -- in minutes
    score DECIMAL(5,2) NULL, -- For assessments
    attempts INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES course_enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES course_module_content(id) ON DELETE CASCADE,
    UNIQUE KEY unique_content_progress (enrollment_id, content_id),
    INDEX idx_enrollment_status (enrollment_id, status),
    INDEX idx_completion_date (completed_at)
);

-- =====================================================
-- ASSESSMENT RESULTS
-- =====================================================

-- Assessment Results Table
CREATE TABLE IF NOT EXISTS assessment_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    assessment_id INT NOT NULL,
    attempt_number INT DEFAULT 1,
    score DECIMAL(5,2) NOT NULL,
    max_score DECIMAL(5,2) NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    passed BOOLEAN DEFAULT FALSE,
    time_taken INT DEFAULT 0, -- in minutes
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    answers JSON, -- Store detailed answers
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES course_enrollments(id) ON DELETE CASCADE,
    INDEX idx_enrollment_assessment (enrollment_id, assessment_id),
    INDEX idx_score (score),
    INDEX idx_passed (passed),
    INDEX idx_completed_at (completed_at)
);

-- =====================================================
-- FEEDBACK RESPONSES
-- =====================================================

-- Feedback Responses Table
CREATE TABLE IF NOT EXISTS feedback_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    feedback_id INT NOT NULL,
    rating INT NULL, -- 1-5 scale
    response TEXT,
    response_data JSON, -- For structured feedback
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES course_enrollments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_feedback_response (enrollment_id, feedback_id),
    INDEX idx_feedback_rating (feedback_id, rating),
    INDEX idx_submitted_at (submitted_at)
);

-- =====================================================
-- SURVEY RESPONSES
-- =====================================================

-- Survey Responses Table
CREATE TABLE IF NOT EXISTS survey_responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    survey_id INT NOT NULL,
    response_data JSON NOT NULL, -- Store all survey responses
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES course_enrollments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_survey_response (enrollment_id, survey_id),
    INDEX idx_survey_submitted (survey_id, submitted_at)
);

-- =====================================================
-- COURSE ANALYTICS AND METRICS
-- =====================================================

-- Course Analytics Table (for caching aggregated data)
CREATE TABLE IF NOT EXISTS course_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    analytics_date DATE NOT NULL,
    total_enrollments INT DEFAULT 0,
    active_enrollments INT DEFAULT 0,
    completed_enrollments INT DEFAULT 0,
    dropped_enrollments INT DEFAULT 0,
    avg_completion_time DECIMAL(10,2) DEFAULT 0.00, -- in minutes
    avg_completion_rate DECIMAL(5,2) DEFAULT 0.00,
    avg_assessment_score DECIMAL(5,2) DEFAULT 0.00,
    avg_feedback_rating DECIMAL(3,2) DEFAULT 0.00,
    total_assessment_attempts INT DEFAULT 0,
    total_feedback_responses INT DEFAULT 0,
    total_survey_responses INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_course_date (course_id, analytics_date),
    INDEX idx_course_date (course_id, analytics_date),
    INDEX idx_analytics_date (analytics_date)
);

-- =====================================================
-- COURSE SETTINGS AND CONFIGURATION
-- =====================================================

-- Course Settings Table
CREATE TABLE IF NOT EXISTS course_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_course_setting (course_id, setting_key),
    INDEX idx_setting_key (setting_key)
);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default course categories
INSERT IGNORE INTO course_categories (client_id, name, description, icon, color, sort_order, created_by) VALUES
(1, 'Technology', 'Technology and IT related courses', 'fas fa-laptop-code', '#4b0082', 1, 1),
(1, 'Business', 'Business and management courses', 'fas fa-briefcase', '#28a745', 2, 1),
(1, 'Marketing', 'Marketing and advertising courses', 'fas fa-bullhorn', '#ffc107', 3, 1),
(1, 'Design', 'Design and creative courses', 'fas fa-palette', '#dc3545', 4, 1),
(1, 'Health & Wellness', 'Health and wellness courses', 'fas fa-heartbeat', '#fd7e14', 5, 1),
(1, 'Language', 'Language learning courses', 'fas fa-language', '#6f42c1', 6, 1),
(1, 'Finance', 'Finance and accounting courses', 'fas fa-chart-line', '#20c997', 7, 1),
(1, 'Education', 'Education and training courses', 'fas fa-graduation-cap', '#17a2b8', 8, 1);

-- Insert default course subcategories for Technology
INSERT IGNORE INTO course_subcategories (category_id, client_id, name, description, icon, sort_order, created_by) VALUES
(1, 1, 'Web Development', 'HTML, CSS, JavaScript, and modern web technologies', 'fas fa-code', 1, 1),
(1, 1, 'Mobile Development', 'iOS, Android, and cross-platform development', 'fas fa-mobile-alt', 2, 1),
(1, 1, 'Data Science', 'Python, R, machine learning, and data analysis', 'fas fa-chart-bar', 3, 1),
(1, 1, 'Cybersecurity', 'Network security, ethical hacking, and security best practices', 'fas fa-shield-alt', 4, 1),
(1, 1, 'Cloud Computing', 'AWS, Azure, Google Cloud, and cloud architecture', 'fas fa-cloud', 5, 1),
(1, 1, 'DevOps', 'CI/CD, Docker, Kubernetes, and infrastructure automation', 'fas fa-cogs', 6, 1);

-- Insert default course subcategories for Business
INSERT IGNORE INTO course_subcategories (category_id, client_id, name, description, icon, sort_order, created_by) VALUES
(2, 1, 'Leadership', 'Leadership skills, team management, and strategic thinking', 'fas fa-users-cog', 1, 1),
(2, 1, 'Project Management', 'Agile, Scrum, and traditional project management', 'fas fa-tasks', 2, 1),
(2, 1, 'Entrepreneurship', 'Starting and growing a business', 'fas fa-rocket', 3, 1),
(2, 1, 'Sales', 'Sales techniques, customer relationship management', 'fas fa-handshake', 4, 1),
(2, 1, 'Operations', 'Business operations and process improvement', 'fas fa-cogs', 5, 1);

-- Insert default course settings
INSERT IGNORE INTO course_settings (course_id, setting_key, setting_value, setting_type, description) VALUES
(0, 'default_passing_score', '70', 'integer', 'Default passing score for course assessments'),
(0, 'default_max_attempts', '3', 'integer', 'Default maximum attempts for assessments'),
(0, 'enable_certificates', 'true', 'boolean', 'Enable certificate generation for completed courses'),
(0, 'enable_progress_tracking', 'true', 'boolean', 'Enable detailed progress tracking'),
(0, 'enable_feedback', 'true', 'boolean', 'Enable feedback collection'),
(0, 'enable_surveys', 'true', 'boolean', 'Enable survey collection');

-- =====================================================
-- CREATE VIEWS FOR COMMON QUERIES
-- =====================================================

-- Course Overview View
CREATE OR REPLACE VIEW course_overview AS
SELECT 
    c.id,
    c.client_id,
    c.name,
    c.description,
    c.short_description,
    c.category_id,
    cc.name as category_name,
    c.subcategory_id,
    csc.name as subcategory_name,
    c.course_type,
    c.difficulty_level,
    c.duration_hours,
    c.duration_minutes,
    c.status,
    c.is_published,
    c.is_featured,
    c.created_at,
    c.updated_at,
    (SELECT COUNT(*) FROM course_modules WHERE course_id = c.id AND deleted_at IS NULL) as module_count,
    (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.id AND deleted_at IS NULL) as enrollment_count,
    (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.id AND status = 'completed' AND deleted_at IS NULL) as completion_count,
    (SELECT ROUND(AVG(completion_percentage), 1) FROM course_enrollments WHERE course_id = c.id AND deleted_at IS NULL) as avg_completion_rate,
    (SELECT ROUND(AVG(score), 1) FROM assessment_results ar 
     JOIN course_assessments ca ON ar.assessment_id = ca.assessment_id 
     WHERE ca.course_id = c.id) as avg_assessment_score
FROM courses c
LEFT JOIN course_categories cc ON c.category_id = cc.id
LEFT JOIN course_subcategories csc ON c.subcategory_id = csc.id
WHERE c.deleted_at IS NULL;

-- Course Analytics View
CREATE OR REPLACE VIEW course_analytics_view AS
SELECT 
    c.id as course_id,
    c.name as course_name,
    c.client_id,
    COUNT(ce.id) as total_enrollments,
    COUNT(CASE WHEN ce.status = 'enrolled' THEN 1 END) as new_enrollments,
    COUNT(CASE WHEN ce.status = 'in_progress' THEN 1 END) as in_progress_enrollments,
    COUNT(CASE WHEN ce.status = 'completed' THEN 1 END) as completed_enrollments,
    COUNT(CASE WHEN ce.status = 'dropped' THEN 1 END) as dropped_enrollments,
    ROUND(AVG(ce.completion_percentage), 1) as avg_completion_rate,
    ROUND(AVG(CASE WHEN ce.status = 'completed' THEN ce.completion_time ELSE NULL END), 1) as avg_completion_time,
    ROUND(AVG(ar.score), 1) as avg_assessment_score,
    ROUND(AVG(fr.rating), 1) as avg_feedback_rating,
    COUNT(DISTINCT ar.id) as total_assessment_attempts,
    COUNT(DISTINCT fr.id) as total_feedback_responses,
    COUNT(DISTINCT sr.id) as total_survey_responses
FROM courses c
LEFT JOIN course_enrollments ce ON c.id = ce.course_id AND ce.deleted_at IS NULL
LEFT JOIN assessment_results ar ON ce.id = ar.enrollment_id
LEFT JOIN feedback_responses fr ON ce.id = fr.enrollment_id
LEFT JOIN survey_responses sr ON ce.id = sr.enrollment_id
WHERE c.deleted_at IS NULL
GROUP BY c.id, c.name, c.client_id;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

-- Migration completed successfully
-- All course-related tables have been created with proper relationships and indexes
-- Default data has been inserted for categories and subcategories
-- Views have been created for common analytics queries 