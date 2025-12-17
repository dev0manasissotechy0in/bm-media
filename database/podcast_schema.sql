-- PODCAST SYSTEM DATABASE SCHEMA
-- Creates tables for podcast categories, episodes, and user progress tracking

-- ============================================================
-- PODCAST CATEGORIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `podcast_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text,
  `icon` varchar(500),
  `color` varchar(20) DEFAULT '#3B82F6',
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_active` (`is_active`),
  KEY `idx_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default categories
INSERT INTO `podcast_categories` (`name`, `slug`, `description`, `color`, `display_order`) VALUES
('News & Politics', 'news-politics', 'Daily news analysis and political discussions', '#EF4444', 1),
('Technology', 'technology', 'Tech news, reviews, and innovations', '#3B82F6', 2),
('Business', 'business', 'Business insights and market analysis', '#10B981', 3),
('Entertainment', 'entertainment', 'Entertainment news and celebrity interviews', '#F59E0B', 4),
('Sports', 'sports', 'Sports commentary and analysis', '#8B5CF6', 5);

-- ============================================================
-- PODCASTS TABLE (Episodes)
-- ============================================================
CREATE TABLE IF NOT EXISTS `podcasts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(500) NOT NULL,
  `slug` varchar(500) NOT NULL,
  `description` text,
  `category_id` int(11) NOT NULL,
  
  -- Media Files
  `audio_file` varchar(500) NOT NULL COMMENT 'Path to audio file (mp3, m4a, etc.)',
  `cover_image` varchar(500) NOT NULL COMMENT 'Podcast cover/thumbnail',
  `duration` int(11) NOT NULL DEFAULT 0 COMMENT 'Duration in seconds',
  `file_size` bigint(20) DEFAULT 0 COMMENT 'File size in bytes',
  
  -- Metadata
  `author` varchar(255) DEFAULT 'Admin',
  `host` varchar(255) COMMENT 'Podcast host name',
  `guest` varchar(500) COMMENT 'Guest names (comma-separated)',
  `episode_number` int(11) DEFAULT NULL,
  `season_number` int(11) DEFAULT NULL,
  
  -- SEO & Display
  `meta_description` text,
  `tags` varchar(500) COMMENT 'Comma-separated tags',
  `published_at` datetime DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  
  -- Statistics
  `play_count` int(11) DEFAULT 0,
  `download_count` int(11) DEFAULT 0,
  `like_count` int(11) DEFAULT 0,
  `share_count` int(11) DEFAULT 0,
  
  -- Timestamps
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_category` (`category_id`),
  KEY `idx_published` (`published_at`),
  KEY `idx_active` (`is_active`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_play_count` (`play_count`),
  CONSTRAINT `fk_podcast_category` FOREIGN KEY (`category_id`) REFERENCES `podcast_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PODCAST PROGRESS TABLE (User Playback Tracking)
-- ============================================================
CREATE TABLE IF NOT EXISTS `podcast_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'NULL for guests, user ID for logged-in users',
  `guest_token` varchar(100) DEFAULT NULL COMMENT 'Token for guest users (stored in cookie/localStorage)',
  `podcast_id` int(11) NOT NULL,
  
  -- Progress Data
  `current_time` int(11) NOT NULL DEFAULT 0 COMMENT 'Current playback position in seconds',
  `duration` int(11) NOT NULL DEFAULT 0 COMMENT 'Total duration in seconds',
  `progress_percentage` decimal(5,2) DEFAULT 0.00 COMMENT 'Progress as percentage',
  `is_completed` tinyint(1) DEFAULT 0 COMMENT '1 if listened to 95% or more',
  
  -- Playback Metadata
  `playback_speed` decimal(3,2) DEFAULT 1.00 COMMENT 'Playback speed (0.5x to 2x)',
  `volume` decimal(3,2) DEFAULT 1.00 COMMENT 'Volume level (0 to 1)',
  `last_played_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_podcast` (`user_id`, `podcast_id`),
  UNIQUE KEY `unique_guest_podcast` (`guest_token`, `podcast_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_guest` (`guest_token`),
  KEY `idx_podcast` (`podcast_id`),
  KEY `idx_last_played` (`last_played_at`),
  CONSTRAINT `fk_progress_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_podcast` FOREIGN KEY (`podcast_id`) REFERENCES `podcasts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PODCAST LIKES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS `podcast_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `podcast_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_like` (`podcast_id`, `user_id`),
  UNIQUE KEY `unique_guest_like` (`podcast_id`, `guest_token`),
  KEY `idx_podcast` (`podcast_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_like_podcast` FOREIGN KEY (`podcast_id`) REFERENCES `podcasts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_like_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PODCAST DOWNLOADS TABLE (Track Downloads)
-- ============================================================
CREATE TABLE IF NOT EXISTS `podcast_downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `podcast_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_token` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45),
  `user_agent` varchar(500),
  `downloaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_podcast` (`podcast_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_downloaded_at` (`downloaded_at`),
  CONSTRAINT `fk_download_podcast` FOREIGN KEY (`podcast_id`) REFERENCES `podcasts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_download_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================
-- Additional composite indexes for common queries
CREATE INDEX idx_podcast_category_published ON podcasts(category_id, published_at DESC);
CREATE INDEX idx_podcast_featured_published ON podcasts(is_featured, published_at DESC);
CREATE INDEX idx_progress_user_played ON podcast_progress(user_id, last_played_at DESC);
CREATE INDEX idx_progress_guest_played ON podcast_progress(guest_token, last_played_at DESC);

-- ============================================================
-- SAMPLE PODCAST DATA (Optional)
-- ============================================================
-- Insert sample podcast (uncomment to use)
/*
INSERT INTO `podcasts` (`title`, `slug`, `description`, `category_id`, `audio_file`, `cover_image`, `duration`, `author`, `host`, `published_at`, `is_featured`, `is_active`) VALUES
('Welcome to Our Podcast', 'welcome-to-our-podcast', 'In this inaugural episode, we introduce our podcast and what listeners can expect in upcoming episodes.', 1, 'uploads/podcasts/audio/welcome-episode.mp3', 'uploads/podcasts/covers/welcome-cover.jpg', 1800, 'Admin', 'John Doe', NOW(), 1, 1),
('Tech Trends 2025', 'tech-trends-2025', 'Exploring the biggest technology trends shaping 2025 and beyond.', 2, 'uploads/podcasts/audio/tech-trends.mp3', 'uploads/podcasts/covers/tech-cover.jpg', 2400, 'Admin', 'Jane Smith', NOW(), 1, 1);
*/
