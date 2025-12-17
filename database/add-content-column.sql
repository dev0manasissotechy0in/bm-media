-- Add content column to articles table
-- This allows storing simple article content directly in the articles table
-- For complex multi-section articles, use article_sections table

ALTER TABLE `articles` 
ADD COLUMN `content` LONGTEXT AFTER `description`;
