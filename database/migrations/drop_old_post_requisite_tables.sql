-- Migration: Drop old post-requisite tables
-- Date: 2024-01-30
-- Purpose: Remove the 4 separate post-requisite tables after successful migration to unified table

-- Drop the old post-requisite tables
-- These tables are no longer needed since we've migrated to course_post_requisites

DROP TABLE IF EXISTS `course_assessments`;
DROP TABLE IF EXISTS `course_feedback`;
DROP TABLE IF EXISTS `course_surveys`;
DROP TABLE IF EXISTS `course_assignments`;

-- Verify the unified table exists and has data
SELECT 
    'course_post_requisites' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT content_type) as content_types
FROM course_post_requisites
WHERE is_deleted = 0; 