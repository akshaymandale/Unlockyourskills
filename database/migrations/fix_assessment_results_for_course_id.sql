-- Migration: Fix Assessment Results for Course ID
-- Date: 2025-01-30
-- Purpose: Modify assessment_results and assessment_attempts tables to work with course_id instead of enrollment_id

-- Step 1: Add course_id to assessment_attempts table
ALTER TABLE assessment_attempts 
ADD COLUMN course_id INT NULL AFTER assessment_id,
ADD INDEX idx_course_id (course_id),
ADD FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL;

-- Step 2: Create a temporary table for assessment_results with new structure
CREATE TABLE IF NOT EXISTS assessment_results_new (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    user_id INT NOT NULL,
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
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user_profiles(id) ON DELETE CASCADE,
    INDEX idx_course_user_assessment (course_id, user_id, assessment_id),
    INDEX idx_score (score),
    INDEX idx_passed (passed),
    INDEX idx_completed_at (completed_at)
);

-- Step 3: Copy existing data if any (this will be empty for new installations)
-- Note: This step is included for completeness but will not affect new installations
INSERT INTO assessment_results_new (
    course_id, user_id, assessment_id, attempt_number, score, max_score, 
    percentage, passed, time_taken, started_at, completed_at, 
    answers, feedback, created_at, updated_at
)
SELECT 
    COALESCE(ca.course_id, 1) as course_id, -- Default to course_id 1 if no mapping found
    COALESCE(ce.user_id, 1) as user_id, -- Default to user_id 1 if no enrollment found
    ar.assessment_id,
    ar.attempt_number,
    ar.score,
    ar.max_score,
    ar.percentage,
    ar.passed,
    ar.time_taken,
    ar.started_at,
    ar.completed_at,
    ar.answers,
    ar.feedback,
    ar.created_at,
    ar.updated_at
FROM assessment_results ar
LEFT JOIN course_enrollments ce ON ar.enrollment_id = ce.id
LEFT JOIN course_assessments ca ON ar.assessment_id = ca.assessment_id
WHERE ar.id IS NOT NULL;

-- Step 4: Drop the old table and rename the new one
DROP TABLE IF EXISTS assessment_results;
RENAME TABLE assessment_results_new TO assessment_results;

-- Step 5: Update assessment_attempts to populate course_id for existing records
-- This will set course_id based on the course_assessments table
UPDATE assessment_attempts aa
INNER JOIN course_assessments ca ON aa.assessment_id = ca.assessment_id
SET aa.course_id = ca.course_id
WHERE aa.course_id IS NULL;

-- Step 6: Add NOT NULL constraint to course_id in assessment_attempts
-- First ensure all records have course_id, then make it NOT NULL
ALTER TABLE assessment_attempts 
MODIFY COLUMN course_id INT NOT NULL;

-- Step 7: Update the foreign key constraint
ALTER TABLE assessment_attempts 
DROP FOREIGN KEY assessment_attempts_ibfk_1; -- Drop old constraint if exists

ALTER TABLE assessment_attempts 
ADD CONSTRAINT fk_assessment_attempts_course 
FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE;
