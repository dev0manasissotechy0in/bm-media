-- Notification System Database Schema
-- Creates tables for managing notifications across web and app

-- Notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('news', 'breaking', 'case_study', 'case_study_update', 'general') NOT NULL DEFAULT 'general',
  `tag` VARCHAR(100) DEFAULT NULL,
  `target` ENUM('all', 'web', 'app') NOT NULL DEFAULT 'all',
  `reference_type` VARCHAR(50) DEFAULT NULL COMMENT 'article, mobile_story, case, etc',
  `reference_id` INT(11) DEFAULT NULL,
  `image_url` VARCHAR(500) DEFAULT NULL,
  `action_url` VARCHAR(500) DEFAULT NULL,
  `total_sent` INT(11) DEFAULT 0,
  `total_delivered` INT(11) DEFAULT 0,
  `total_clicked` INT(11) DEFAULT 0,
  `total_viewed` INT(11) DEFAULT 0,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_tag` (`tag`),
  KEY `idx_target` (`target`),
  KEY `idx_reference` (`reference_type`, `reference_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores all notifications sent to users';

-- User notifications (tracks delivery to individual users)
CREATE TABLE IF NOT EXISTS `user_notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `notification_id` INT(11) NOT NULL,
  `user_id` INT(11) DEFAULT NULL COMMENT 'NULL for guest users',
  `device_token` VARCHAR(500) DEFAULT NULL,
  `platform` ENUM('web', 'android', 'ios') NOT NULL,
  `is_delivered` BOOLEAN DEFAULT 0,
  `is_read` BOOLEAN DEFAULT 0,
  `is_clicked` BOOLEAN DEFAULT 0,
  `delivered_at` TIMESTAMP NULL DEFAULT NULL,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `clicked_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notification` (`notification_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_platform` (`platform`),
  KEY `idx_read` (`is_read`),
  FOREIGN KEY (`notification_id`) REFERENCES `notifications`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracks notification delivery to individual users';

-- User notification preferences
CREATE TABLE IF NOT EXISTS `user_notification_preferences` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL COMMENT 'NULL for guest/default settings',
  `device_token` VARCHAR(500) DEFAULT NULL,
  `platform` ENUM('web', 'android', 'ios') NOT NULL DEFAULT 'web',
  `news_enabled` BOOLEAN DEFAULT 1,
  `breaking_enabled` BOOLEAN DEFAULT 1,
  `case_study_enabled` BOOLEAN DEFAULT 1,
  `case_study_update_enabled` BOOLEAN DEFAULT 1,
  `general_enabled` BOOLEAN DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_platform` (`user_id`, `platform`),
  KEY `idx_device_token` (`device_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User notification preferences';

-- FCM device tokens (for mobile push notifications)
CREATE TABLE IF NOT EXISTS `device_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `device_token` VARCHAR(500) NOT NULL,
  `platform` ENUM('android', 'ios', 'web') NOT NULL,
  `device_info` TEXT DEFAULT NULL COMMENT 'Device model, OS version, etc',
  `is_active` BOOLEAN DEFAULT 1,
  `last_used` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_token` (`device_token`),
  KEY `idx_user` (`user_id`),
  KEY `idx_platform` (`platform`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Stores FCM device tokens for push notifications';

-- Notification analytics aggregated by tag
CREATE TABLE IF NOT EXISTS `notification_analytics` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tag` VARCHAR(100) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `total_sent` INT(11) DEFAULT 0,
  `total_clicked` INT(11) DEFAULT 0,
  `total_viewed` INT(11) DEFAULT 0,
  `click_rate` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Percentage',
  `date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tag_date` (`tag`, `date`),
  KEY `idx_type` (`type`),
  KEY `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Daily aggregated notification analytics by tag';

-- Web push subscriptions
CREATE TABLE IF NOT EXISTS `web_push_subscriptions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `endpoint` VARCHAR(500) NOT NULL,
  `p256dh_key` VARCHAR(255) NOT NULL,
  `auth_token` VARCHAR(255) NOT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `is_active` BOOLEAN DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_endpoint` (`endpoint`),
  KEY `idx_user` (`user_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Web push notification subscriptions';
