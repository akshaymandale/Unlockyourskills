-- Migration: Remove redundant title column from course_post_requisites
-- Safe to run multiple times
-- Date: 2025-01-27
-- Purpose: Remove title field since titles should come from package tables (assessment_package, feedback_package, etc.)

ALTER TABLE course_post_requisites 
    DROP COLUMN IF EXISTS title;
