-- Migration: Add 'archived' status to feed_posts table
-- This migration adds the 'archived' status option to the status enum in feed_posts table

-- Add 'archived' to the status enum
ALTER TABLE `feed_posts` 
MODIFY COLUMN `status` enum('active','draft','deleted','reported','archived') NOT NULL DEFAULT 'active';

-- Add 'archived' to the status enum for feed_comments table as well (for consistency)
ALTER TABLE `feed_comments` 
MODIFY COLUMN `status` enum('active','deleted','reported','archived') NOT NULL DEFAULT 'active'; 