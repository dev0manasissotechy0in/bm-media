-- Add missing description column to categories table
ALTER TABLE `categories` 
ADD COLUMN `description` TEXT AFTER `slug`;
