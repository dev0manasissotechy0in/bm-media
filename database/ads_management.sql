-- Ads Management System Tables

-- Google AdSense Settings Table
CREATE TABLE IF NOT EXISTS `ads_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` ENUM('google_adsense', 'other') DEFAULT 'google_adsense',
  `client_id` VARCHAR(255),
  `ad_slot_banner` VARCHAR(255),
  `ad_slot_sidebar` VARCHAR(255),
  `ad_slot_article` VARCHAR(255),
  `enabled` TINYINT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Custom Ads Table
CREATE TABLE IF NOT EXISTS `custom_ads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `code` LONGTEXT NOT NULL,
  `placement` ENUM('header', 'sidebar', 'article', 'footer', 'category') NOT NULL,
  `position` ENUM('top', 'middle', 'bottom') DEFAULT 'top',
  `status` TINYINT DEFAULT 1,
  `impressions` INT DEFAULT 0,
  `clicks` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_placement` (`placement`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ad Analytics Table (for tracking)
CREATE TABLE IF NOT EXISTS `ad_analytics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ad_id` INT,
  `ad_type` ENUM('custom', 'google') DEFAULT 'custom',
  `placement` VARCHAR(100),
  `event_type` ENUM('impression', 'click') NOT NULL,
  `page_url` VARCHAR(500),
  `user_ip` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ad_id` (`ad_id`),
  INDEX `idx_event_type` (`event_type`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
