-- Add show_in_app column to custom_pages table
-- This column controls which pages appear in the mobile app profile section

ALTER TABLE `custom_pages` 
ADD COLUMN IF NOT EXISTS `show_in_app` BOOLEAN DEFAULT FALSE AFTER `show_in_footer`;

-- Add index for better query performance
ALTER TABLE `custom_pages` 
ADD INDEX IF NOT EXISTS `idx_show_in_app` (`show_in_app`);

-- Update some common pages to show in app
UPDATE `custom_pages` 
SET `show_in_app` = TRUE 
WHERE `slug` IN ('about-us', 'privacy-policy', 'terms-conditions', 'contact-us', 'disclaimer', 'cookie-policy')
  AND `status` = 'published';

-- Show current structure
DESCRIBE `custom_pages`;

-- Show pages that will appear in footer and app
SELECT id, title, slug, show_in_footer, show_in_app, status 
FROM `custom_pages` 
ORDER BY order_id ASC, title ASC;
