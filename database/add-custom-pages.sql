-- Add custom_pages table for dynamic page management
CREATE TABLE IF NOT EXISTS `custom_pages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `page_type` ENUM('text', 'category_articles', 'tag_articles', 'live_polls', 'statistics', 'graphics') DEFAULT 'text',
  `content` LONGTEXT,
  `category_id` INT DEFAULT NULL,
  `tag_id` INT DEFAULT NULL,
  `show_in_footer` BOOLEAN DEFAULT FALSE,
  `status` ENUM('draft', 'published') DEFAULT 'draft',
  `views_count` INT DEFAULT 0,
  `seo_title` VARCHAR(255),
  `seo_description` TEXT,
  `seo_keywords` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE SET NULL,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_page_type` (`page_type`),
  INDEX `idx_status` (`status`),
  INDEX `idx_show_in_footer` (`show_in_footer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
