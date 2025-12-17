-- Add app_users table for mobile app users with social login support
CREATE TABLE IF NOT EXISTS `app_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `uid` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Firebase UID or generated UID',
  `email` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `photo_url` TEXT DEFAULT NULL,
  `provider` ENUM('email', 'google', 'facebook', 'apple', 'phone') DEFAULT 'email',
  `status` ENUM('active', 'inactive', 'banned') DEFAULT 'active',
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_uid` (`uid`),
  INDEX `idx_provider` (`provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add user bookmarks table
CREATE TABLE IF NOT EXISTS `user_bookmarks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `article_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `app_users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_bookmark` (`user_id`, `article_id`),
  INDEX `idx_user_bookmarks` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add user liked articles table
CREATE TABLE IF NOT EXISTS `user_likes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `article_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `app_users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_like` (`user_id`, `article_id`),
  INDEX `idx_user_likes` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add social login settings to site_settings if not exists
INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('google_login_enabled', '0', 'boolean'),
('facebook_login_enabled', '0', 'boolean'),
('google_client_id', '', 'text'),
('google_client_secret', '', 'text'),
('facebook_app_id', '', 'text'),
('facebook_app_secret', '', 'text'),
('otp_enabled', '1', 'boolean'),
('otp_expiry_minutes', '10', 'number');
