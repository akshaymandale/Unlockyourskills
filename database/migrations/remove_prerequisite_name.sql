-- Migration: Remove redundant prerequisite_name column from course_prerequisites
-- Safe to run multiple times
-- Date: 2025-01-27
-- Purpose: Remove prerequisite_name field since titles should come from package tables (assessment_package, scorm_packages, etc.)

ALTER TABLE course_prerequisites 
    DROP COLUMN IF EXISTS prerequisite_name;
