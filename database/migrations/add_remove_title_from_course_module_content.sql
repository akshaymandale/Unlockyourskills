-- Migration: Remove redundant title column from course_module_content
-- Safe to run multiple times

ALTER TABLE course_module_content 
    DROP COLUMN IF EXISTS title;


