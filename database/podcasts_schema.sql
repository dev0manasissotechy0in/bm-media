-- Podcasts Schema
-- Run this SQL to create podcast-related tables

-- Podcasts table (main podcast/series)
CREATE TABLE IF NOT EXISTS `podcasts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) UNIQUE NOT NULL,
  `description` TEXT,
  `thumbnail` VARCHAR(255),
  `cover_image` VARCHAR(255),
  `author_name` VARCHAR(255),
  `author_bio` TEXT,
  `category` VARCHAR(100),
  `language` VARCHAR(50) DEFAULT 'English',
  `is_series` TINYINT(1) DEFAULT 0 COMMENT '1 for series with episodes, 0 for single podcast',
  `total_episodes` INT DEFAULT 0,
  `status` ENUM('published', 'draft', 'archived') DEFAULT 'published',
  `views_count` INT DEFAULT 0,
  `likes_count` INT DEFAULT 0,
  `saves_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_status` (`status`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Podcast Episodes table
CREATE TABLE IF NOT EXISTS `podcast_episodes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `podcast_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `audio_url` VARCHAR(500) NOT NULL,
  `duration` INT NOT NULL COMMENT 'Duration in seconds',
  `episode_number` INT,
  `season_number` INT DEFAULT 1,
  `thumbnail` VARCHAR(255),
  `status` ENUM('published', 'draft') DEFAULT 'published',
  `views_count` INT DEFAULT 0,
  `likes_count` INT DEFAULT 0,
  `published_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`podcast_id`) REFERENCES `podcasts`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_episode` (`podcast_id`, `episode_number`, `season_number`),
  INDEX `idx_podcast` (`podcast_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Podcast Likes
CREATE TABLE IF NOT EXISTS `user_podcast_likes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `podcast_id` INT,
  `episode_id` INT,
  `liked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`podcast_id`) REFERENCES `podcasts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`episode_id`) REFERENCES `podcast_episodes`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_podcast_like` (`user_id`, `podcast_id`),
  UNIQUE KEY `unique_episode_like` (`user_id`, `episode_id`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Saved Podcasts
CREATE TABLE IF NOT EXISTS `user_saved_podcasts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `podcast_id` INT NOT NULL,
  `saved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`podcast_id`) REFERENCES `podcasts`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_save` (`user_id`, `podcast_id`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Podcast Progress (resume playback)
CREATE TABLE IF NOT EXISTS `user_podcast_progress` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `podcast_id` INT,
  `episode_id` INT,
  `progress_seconds` INT DEFAULT 0 COMMENT 'Current playback position in seconds',
  `completed` TINYINT(1) DEFAULT 0,
  `last_played_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`podcast_id`) REFERENCES `podcasts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`episode_id`) REFERENCES `podcast_episodes`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_progress` (`user_id`, `podcast_id`, `episode_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_last_played` (`last_played_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Podcast Notifications
CREATE TABLE IF NOT EXISTS `user_podcast_notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `podcast_id` INT NOT NULL,
  `enabled` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`podcast_id`) REFERENCES `podcasts`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_notification` (`user_id`, `podcast_id`),
  INDEX `idx_user_enabled` (`user_id`, `enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
