-- ============================================================
-- COMPLETE NEWS WEBSITE DATABASE SCHEMA
-- ============================================================

-- Drop existing database if needed
-- DROP DATABASE IF EXISTS news_website;
CREATE DATABASE IF NOT EXISTS news_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE news_website;

-- ============================================================
-- CORE USER MANAGEMENT TABLES
-- ============================================================

-- Admin Users Table
CREATE TABLE `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(20),
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255),
  `profile_photo` VARCHAR(500),
  `role` ENUM('super_admin', 'admin', 'editor') DEFAULT 'admin',
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `last_login` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table (Regular Users)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255),
  `phone` VARCHAR(20),
  `password` VARCHAR(255),
  `full_name` VARCHAR(255),
  `profile_photo` VARCHAR(500),
  `auth_provider` ENUM('email', 'phone', 'google', 'facebook') DEFAULT 'email',
  `provider_id` VARCHAR(255),
  `email_verified` BOOLEAN DEFAULT FALSE,
  `phone_verified` BOOLEAN DEFAULT FALSE,
  `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
  `last_login` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_email` (`email`),
  UNIQUE KEY `unique_phone` (`phone`),
  INDEX `idx_status` (`status`),
  INDEX `idx_provider` (`auth_provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reporters Table
CREATE TABLE `reporters` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `phone` VARCHAR(20),
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `profile_photo` VARCHAR(500),
  `unique_reporter_id` VARCHAR(50) UNIQUE NOT NULL,
  `auth_provider` ENUM('email', 'phone', 'google', 'facebook') DEFAULT 'email',
  `provider_id` VARCHAR(255),
  `is_author` BOOLEAN DEFAULT FALSE,
  `validity_date` DATE,
  `status` ENUM('pending', 'active', 'inactive', 'suspended') DEFAULT 'pending',
  `bio` TEXT,
  `last_login` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_status` (`status`),
  INDEX `idx_is_author` (`is_author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reporter Documents Table
CREATE TABLE `reporter_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reporter_id` INT NOT NULL,
  `document_type` VARCHAR(100),
  `document_path` VARCHAR(500) NOT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`reporter_id`) REFERENCES `reporters`(`id`) ON DELETE CASCADE,
  INDEX `idx_reporter` (`reporter_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CATEGORY MANAGEMENT TABLES
-- ============================================================

-- Categories Table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `parent_id` INT DEFAULT NULL,
  `icon` VARCHAR(500),
  `logo` VARCHAR(500),
  `order_id` INT DEFAULT 0,
  `is_top_marked` BOOLEAN DEFAULT FALSE,
  `seo_title` VARCHAR(255),
  `seo_description` TEXT,
  `seo_keywords` TEXT,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_parent` (`parent_id`),
  INDEX `idx_order` (`order_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tags Table
CREATE TABLE `tags` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `is_new` BOOLEAN DEFAULT FALSE,
  `seo_title` VARCHAR(255),
  `seo_description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ARTICLE/NEWS MANAGEMENT TABLES
-- ============================================================

-- Articles Table
CREATE TABLE `articles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(500) NOT NULL,
  `slug` VARCHAR(500) NOT NULL UNIQUE,
  `description` TEXT,
  `thumbnail` VARCHAR(500),
  `thumbnail_alt` VARCHAR(255),
  `content_type` ENUM('reel', 'video', 'photo', 'gallery', 'standard') DEFAULT 'standard',
  `category_id` INT,
  `author_id` INT,
  `author_type` ENUM('admin', 'reporter') DEFAULT 'admin',
  `is_featured` BOOLEAN DEFAULT FALSE,
  `is_top_news` BOOLEAN DEFAULT FALSE,
  `is_live` BOOLEAN DEFAULT FALSE,
  `is_breaking` BOOLEAN DEFAULT FALSE,
  `views_count` INT DEFAULT 0,
  `likes_count` INT DEFAULT 0,
  `comments_count` INT DEFAULT 0,
  `downloads_count` INT DEFAULT 0,
  `seo_title` VARCHAR(255),
  `seo_description` TEXT,
  `seo_keywords` TEXT,
  `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
  `published_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_published` (`published_at`),
  INDEX `idx_featured` (`is_featured`),
  INDEX `idx_live` (`is_live`),
  FULLTEXT KEY `search_title` (`title`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Article Content Sections Table
CREATE TABLE `article_sections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `article_id` INT NOT NULL,
  `section_type` ENUM('subtitle', 'text_image', 'text_video', 'text_ui') DEFAULT 'subtitle',
  `subtitle` VARCHAR(500),
  `content` LONGTEXT,
  `media_url` VARCHAR(500),
  `media_alt` VARCHAR(255),
  `order_id` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  INDEX `idx_article` (`article_id`),
  INDEX `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Article Tags Relationship Table
CREATE TABLE `article_tags` (
  `article_id` INT NOT NULL,
  `tag_id` INT NOT NULL,
  PRIMARY KEY (`article_id`, `tag_id`),
  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE CASCADE,
  INDEX `idx_article` (`article_id`),
  INDEX `idx_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Live Article Updates Table
CREATE TABLE `article_live_updates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `article_id` INT NOT NULL,
  `update_text` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  INDEX `idx_article` (`article_id`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Article Gallery Images Table
CREATE TABLE `article_gallery` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `article_id` INT NOT NULL,
  `image_url` VARCHAR(500) NOT NULL,
  `image_alt` VARCHAR(255),
  `caption` TEXT,
  `order_id` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  INDEX `idx_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- USER INTERACTION TABLES
-- ============================================================

-- User Saved Articles Table
CREATE TABLE `user_saved_articles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `article_id` INT NOT NULL,
  `saved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_save` (`user_id`, `article_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Article Likes Table
CREATE TABLE `user_article_likes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `article_id` INT NOT NULL,
  `liked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_like` (`user_id`, `article_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments Table
CREATE TABLE `comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `article_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `parent_id` INT DEFAULT NULL,
  `comment_text` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE,
  INDEX `idx_article` (`article_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Article Downloads Table
CREATE TABLE `article_downloads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `article_id` INT NOT NULL,
  `downloaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_article` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CUSTOM PAGES TABLES
-- ============================================================

-- Custom Pages Table
CREATE TABLE `custom_pages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(500) NOT NULL,
  `slug` VARCHAR(500) NOT NULL UNIQUE,
  `page_type` ENUM('text', 'polls', 'graphics', 'election_polls', 'category_articles', 'tag_articles', 'statistics') DEFAULT 'text',
  `content` LONGTEXT,
  `category_id` INT DEFAULT NULL,
  `tag_id` INT DEFAULT NULL,
  `seo_title` VARCHAR(255),
  `seo_description` TEXT,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`tag_id`) REFERENCES `tags`(`id`) ON DELETE SET NULL,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_type` (`page_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ELECTION DASHBOARD TABLES
-- ============================================================

-- Political Parties Table
CREATE TABLE `election_parties` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `short_name` VARCHAR(50),
  `color_code` VARCHAR(7),
  `symbol_image` VARCHAR(500),
  `description` TEXT,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Constituencies Table
CREATE TABLE `election_constituencies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `state` VARCHAR(100),
  `constituency_type` ENUM('lok_sabha', 'vidhan_sabha', 'other') DEFAULT 'lok_sabha',
  `total_voters` INT DEFAULT 0,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_state` (`state`),
  INDEX `idx_type` (`constituency_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Candidates Table
CREATE TABLE `election_candidates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `party_id` INT,
  `constituency_id` INT,
  `photo` VARCHAR(500),
  `age` INT,
  `education` VARCHAR(255),
  `criminal_cases` INT DEFAULT 0,
  `assets` VARCHAR(100),
  `bio` TEXT,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`party_id`) REFERENCES `election_parties`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`constituency_id`) REFERENCES `election_constituencies`(`id`) ON DELETE CASCADE,
  INDEX `idx_party` (`party_id`),
  INDEX `idx_constituency` (`constituency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Election Results Table (Live)
CREATE TABLE `election_results` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `constituency_id` INT NOT NULL,
  `candidate_id` INT NOT NULL,
  `party_id` INT,
  `votes` INT DEFAULT 0,
  `vote_percentage` DECIMAL(5,2) DEFAULT 0,
  `status` ENUM('leading', 'trailing', 'won', 'lost') DEFAULT 'trailing',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`constituency_id`) REFERENCES `election_constituencies`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`candidate_id`) REFERENCES `election_candidates`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`party_id`) REFERENCES `election_parties`(`id`) ON DELETE SET NULL,
  INDEX `idx_constituency` (`constituency_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Opinion Polls Table
CREATE TABLE `election_opinion_polls` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `poll_name` VARCHAR(255) NOT NULL,
  `party_id` INT,
  `predicted_seats` INT,
  `poll_date` DATE,
  `pollster` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`party_id`) REFERENCES `election_parties`(`id`) ON DELETE CASCADE,
  INDEX `idx_date` (`poll_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exit Polls Table
CREATE TABLE `election_exit_polls` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `poll_name` VARCHAR(255) NOT NULL,
  `party_id` INT,
  `predicted_seats` INT,
  `poll_date` DATE,
  `pollster` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`party_id`) REFERENCES `election_parties`(`id`) ON DELETE CASCADE,
  INDEX `idx_date` (`poll_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Election News Table
CREATE TABLE `election_news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT,
  `image` VARCHAR(500),
  `is_breaking` BOOLEAN DEFAULT FALSE,
  `published_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Election Updates Table (Live Updates)
CREATE TABLE `election_updates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `update_text` TEXT NOT NULL,
  `update_type` ENUM('result', 'turnout', 'news', 'alert') DEFAULT 'news',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- CRICKET DASHBOARD TABLES
-- ============================================================

-- Cricket Teams Table
CREATE TABLE `cricket_teams` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `short_name` VARCHAR(10),
  `logo` VARCHAR(500),
  `country` VARCHAR(100),
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cricket Players Table
CREATE TABLE `cricket_players` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `team_id` INT,
  `photo` VARCHAR(500),
  `role` ENUM('batsman', 'bowler', 'all_rounder', 'wicket_keeper') DEFAULT 'batsman',
  `batting_style` VARCHAR(100),
  `bowling_style` VARCHAR(100),
  `matches_played` INT DEFAULT 0,
  `runs_scored` INT DEFAULT 0,
  `wickets_taken` INT DEFAULT 0,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `cricket_teams`(`id`) ON DELETE SET NULL,
  INDEX `idx_team` (`team_id`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cricket Matches Table
CREATE TABLE `cricket_matches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `match_title` VARCHAR(500) NOT NULL,
  `team1_id` INT NOT NULL,
  `team2_id` INT NOT NULL,
  `venue` VARCHAR(255),
  `match_date` DATETIME,
  `match_type` ENUM('test', 'odi', 't20', 't10', 'hundred') DEFAULT 't20',
  `series_name` VARCHAR(255),
  `status` ENUM('upcoming', 'live', 'completed', 'abandoned') DEFAULT 'upcoming',
  `toss_winner_id` INT,
  `toss_decision` ENUM('bat', 'bowl'),
  `match_winner_id` INT,
  `result_text` VARCHAR(500),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`team1_id`) REFERENCES `cricket_teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`team2_id`) REFERENCES `cricket_teams`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`toss_winner_id`) REFERENCES `cricket_teams`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`match_winner_id`) REFERENCES `cricket_teams`(`id`) ON DELETE SET NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_date` (`match_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cricket Scores Table
CREATE TABLE `cricket_scores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `match_id` INT NOT NULL,
  `team_id` INT NOT NULL,
  `innings` INT DEFAULT 1,
  `runs` INT DEFAULT 0,
  `wickets` INT DEFAULT 0,
  `overs` DECIMAL(4,1) DEFAULT 0.0,
  `run_rate` DECIMAL(4,2) DEFAULT 0.00,
  `extras` INT DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`match_id`) REFERENCES `cricket_matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`team_id`) REFERENCES `cricket_teams`(`id`) ON DELETE CASCADE,
  INDEX `idx_match` (`match_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cricket Ball by Ball Table
CREATE TABLE `cricket_ball_by_ball` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `match_id` INT NOT NULL,
  `innings` INT DEFAULT 1,
  `over_number` INT,
  `ball_number` INT,
  `batsman_id` INT,
  `bowler_id` INT,
  `runs` INT DEFAULT 0,
  `extras` INT DEFAULT 0,
  `is_wicket` BOOLEAN DEFAULT FALSE,
  `wicket_type` VARCHAR(100),
  `commentary` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`match_id`) REFERENCES `cricket_matches`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`batsman_id`) REFERENCES `cricket_players`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`bowler_id`) REFERENCES `cricket_players`(`id`) ON DELETE SET NULL,
  INDEX `idx_match` (`match_id`),
  INDEX `idx_innings` (`innings`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cricket Points Table
CREATE TABLE `cricket_points_table` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `series_name` VARCHAR(255) NOT NULL,
  `team_id` INT NOT NULL,
  `matches_played` INT DEFAULT 0,
  `wins` INT DEFAULT 0,
  `losses` INT DEFAULT 0,
  `draws` INT DEFAULT 0,
  `no_result` INT DEFAULT 0,
  `points` INT DEFAULT 0,
  `net_run_rate` DECIMAL(5,3) DEFAULT 0.000,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`team_id`) REFERENCES `cricket_teams`(`id`) ON DELETE CASCADE,
  INDEX `idx_series` (`series_name`),
  INDEX `idx_points` (`points`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cricket Polls Table
CREATE TABLE `cricket_polls` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `match_id` INT,
  `question` VARCHAR(500) NOT NULL,
  `option1` VARCHAR(255),
  `option2` VARCHAR(255),
  `option3` VARCHAR(255),
  `option4` VARCHAR(255),
  `option1_votes` INT DEFAULT 0,
  `option2_votes` INT DEFAULT 0,
  `option3_votes` INT DEFAULT 0,
  `option4_votes` INT DEFAULT 0,
  `status` ENUM('active', 'closed') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`match_id`) REFERENCES `cricket_matches`(`id`) ON DELETE CASCADE,
  INDEX `idx_match` (`match_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cricket News Table
CREATE TABLE `cricket_news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT,
  `image` VARCHAR(500),
  `is_breaking` BOOLEAN DEFAULT FALSE,
  `published_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MARKET DASHBOARD TABLES
-- ============================================================

-- Market Indices Table
CREATE TABLE `market_indices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `symbol` VARCHAR(20) NOT NULL UNIQUE,
  `current_value` DECIMAL(12,2) DEFAULT 0.00,
  `change_value` DECIMAL(12,2) DEFAULT 0.00,
  `change_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `open_value` DECIMAL(12,2) DEFAULT 0.00,
  `high_value` DECIMAL(12,2) DEFAULT 0.00,
  `low_value` DECIMAL(12,2) DEFAULT 0.00,
  `close_value` DECIMAL(12,2) DEFAULT 0.00,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_symbol` (`symbol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Market Stocks Table
CREATE TABLE `market_stocks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_name` VARCHAR(255) NOT NULL,
  `symbol` VARCHAR(20) NOT NULL UNIQUE,
  `category` VARCHAR(100),
  `current_price` DECIMAL(12,2) DEFAULT 0.00,
  `change_value` DECIMAL(12,2) DEFAULT 0.00,
  `change_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `open_price` DECIMAL(12,2) DEFAULT 0.00,
  `high_price` DECIMAL(12,2) DEFAULT 0.00,
  `low_price` DECIMAL(12,2) DEFAULT 0.00,
  `close_price` DECIMAL(12,2) DEFAULT 0.00,
  `volume` BIGINT DEFAULT 0,
  `market_cap` DECIMAL(15,2),
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_symbol` (`symbol`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Market History Table (For Charts)
CREATE TABLE `market_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `symbol` VARCHAR(20) NOT NULL,
  `type` ENUM('index', 'stock') DEFAULT 'stock',
  `price` DECIMAL(12,2) NOT NULL,
  `volume` BIGINT DEFAULT 0,
  `recorded_at` DATETIME NOT NULL,
  INDEX `idx_symbol` (`symbol`),
  INDEX `idx_recorded` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Market Polls Table
CREATE TABLE `market_polls` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `question` VARCHAR(500) NOT NULL,
  `option1` VARCHAR(255),
  `option2` VARCHAR(255),
  `option3` VARCHAR(255),
  `option4` VARCHAR(255),
  `option1_votes` INT DEFAULT 0,
  `option2_votes` INT DEFAULT 0,
  `option3_votes` INT DEFAULT 0,
  `option4_votes` INT DEFAULT 0,
  `status` ENUM('active', 'closed') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Finance News Table
CREATE TABLE `finance_news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT,
  `image` VARCHAR(500),
  `is_breaking` BOOLEAN DEFAULT FALSE,
  `published_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ADS MANAGEMENT TABLES
-- ============================================================

-- Ads Management Table
CREATE TABLE `ads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ad_name` VARCHAR(255) NOT NULL,
  `ad_type` ENUM('google', 'custom', 'government', 'taboola') DEFAULT 'custom',
  `ad_position` VARCHAR(100),
  `ad_code` TEXT,
  `ad_image` VARCHAR(500),
  `ad_link` VARCHAR(500),
  `status` ENUM('active', 'inactive', 'pending_approval') DEFAULT 'pending_approval',
  `priority` INT DEFAULT 1,
  `start_date` DATE,
  `end_date` DATE,
  `impressions` INT DEFAULT 0,
  `clicks` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_position` (`ad_position`),
  INDEX `idx_status` (`status`),
  INDEX `idx_type` (`ad_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- NOTIFICATION TABLES
-- ============================================================

-- Web Notifications Table
CREATE TABLE `web_notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `icon` VARCHAR(500),
  `link` VARCHAR(500),
  `target_users` ENUM('all', 'subscribers') DEFAULT 'all',
  `sent_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sent` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter Subscribers Table
CREATE TABLE `newsletter_subscribers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `status` ENUM('active', 'unsubscribed') DEFAULT 'active',
  `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter Campaigns Table
CREATE TABLE `newsletter_campaigns` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `campaign_name` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(500) NOT NULL,
  `content` LONGTEXT,
  `article_id` INT,
  `campaign_type` ENUM('custom', 'article') DEFAULT 'custom',
  `status` ENUM('draft', 'scheduled', 'sent') DEFAULT 'draft',
  `scheduled_at` DATETIME,
  `sent_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`article_id`) REFERENCES `articles`(`id`) ON DELETE SET NULL,
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- STORIES & REELS TABLES
-- ============================================================

-- Stories Table
CREATE TABLE `stories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `media_url` VARCHAR(500) NOT NULL,
  `media_type` ENUM('image', 'video') DEFAULT 'image',
  `link` VARCHAR(500),
  `duration` INT DEFAULT 5,
  `category_id` INT,
  `status` ENUM('active', 'expired') DEFAULT 'active',
  `views_count` INT DEFAULT 0,
  `expires_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reels Table
CREATE TABLE `reels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `video_url` VARCHAR(500) NOT NULL,
  `thumbnail` VARCHAR(500),
  `description` TEXT,
  `category_id` INT,
  `views_count` INT DEFAULT 0,
  `likes_count` INT DEFAULT 0,
  `shares_count` INT DEFAULT 0,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEO & INDEXING TABLES
-- ============================================================

-- SEO Settings Table
CREATE TABLE `seo_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `site_name` VARCHAR(255),
  `site_description` TEXT,
  `site_keywords` TEXT,
  `site_logo` VARCHAR(500),
  `favicon` VARCHAR(500),
  `google_analytics` TEXT,
  `google_search_console` TEXT,
  `facebook_pixel` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sitemap Pages Table
CREATE TABLE `sitemap_pages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `url` VARCHAR(500) NOT NULL,
  `priority` DECIMAL(2,1) DEFAULT 0.5,
  `change_freq` ENUM('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never') DEFAULT 'daily',
  `last_modified` DATETIME,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SETTINGS TABLES
-- ============================================================

-- Site Settings Table
CREATE TABLE `site_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `setting_type` VARCHAR(50),
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INSERT DEFAULT DATA
-- ============================================================

-- Insert Default Admin
INSERT INTO `admin_users` (`username`, `email`, `password`, `full_name`, `role`, `status`) VALUES
('admin', 'admin@newswebsite.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin', 'super_admin', 'active');
-- Default password: password (hashed with bcrypt)

-- Insert Default Categories
INSERT INTO `categories` (`name`, `slug`, `order_id`, `is_top_marked`, `status`) VALUES
('Politics', 'politics', 1, TRUE, 'active'),
('Sports', 'sports', 2, TRUE, 'active'),
('Entertainment', 'entertainment', 3, TRUE, 'active'),
('Technology', 'technology', 4, TRUE, 'active'),
('Business', 'business', 5, TRUE, 'active'),
('World', 'world', 6, TRUE, 'active'),
('Health', 'health', 7, FALSE, 'active'),
('Education', 'education', 8, FALSE, 'active'),
('Opinion', 'opinion', 9, FALSE, 'active'),
('Lifestyle', 'lifestyle', 10, FALSE, 'active');

-- Insert Default Market Indices
INSERT INTO `market_indices` (`name`, `symbol`, `current_value`, `change_value`, `change_percentage`) VALUES
('BSE Sensex', 'SENSEX', 72000.00, 250.50, 0.35),
('NSE Nifty 50', 'NIFTY', 21500.00, 75.25, 0.35);

-- Insert Default Site Settings
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('site_name', 'News Website', 'text'),
('site_tagline', 'Your Trusted News Source', 'text'),
('site_email', 'contact@newswebsite.com', 'email'),
('site_phone', '+1234567890', 'text'),
('articles_per_page', '20', 'number'),
('enable_comments', '1', 'boolean'),
('enable_user_registration', '1', 'boolean'),
('enable_google_ads', '0', 'boolean'),
('enable_custom_ads', '1', 'boolean');

-- Insert SEO Settings
INSERT INTO `seo_settings` (`site_name`, `site_description`, `site_keywords`) VALUES
('News Website', 'Get the latest news from around the world. Breaking news, politics, sports, entertainment, technology, business and more.', 'news, breaking news, latest news, politics, sports, entertainment');

-- ============================================================
-- CUSTOM PAGE MANAGEMENT SYSTEM
-- ============================================================

-- Custom Pages Table (for dynamic page management)
CREATE TABLE `custom_pages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `content` LONGTEXT,
  `page_type` ENUM('text', 'category_articles') DEFAULT 'text',
  `category_id` INT DEFAULT NULL,
  `status` ENUM('draft', 'published') DEFAULT 'draft',
  `show_in_footer` BOOLEAN DEFAULT FALSE,
  `order_id` INT DEFAULT 0,
  `meta_title` VARCHAR(255),
  `meta_description` TEXT,
  `views_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_status` (`status`),
  INDEX `idx_show_in_footer` (`show_in_footer`),
  INDEX `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact Queries Table
CREATE TABLE `contact_queries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(500) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` BOOLEAN DEFAULT FALSE,
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_is_read` (`is_read`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings Table (for SMTP and other dynamic settings)
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default SMTP Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('smtp_enabled', '0'),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_encryption', 'tls'),
('smtp_from_email', ''),
('smtp_from_name', 'News Website'),
('contact_email', '');

-- Insert Default Custom Pages (Terms, Privacy, About)
INSERT INTO `custom_pages` (`title`, `slug`, `content`, `page_type`, `status`, `show_in_footer`, `order_id`, `meta_title`, `meta_description`) VALUES
('About Us', 'about-us', 'Welcome to our news website! We are committed to bringing you the latest, most accurate news from around the world.\n\nOur Mission:\nTo provide reliable, unbiased news coverage that keeps you informed about what matters most.\n\nOur Team:\nWe have a dedicated team of journalists and editors working around the clock to bring you breaking news, in-depth analysis, and exclusive stories.\n\nContact Us:\nHave a story tip or feedback? We\'d love to hear from you! Visit our contact page to get in touch.', 'text', 'published', 1, 1, 'About Us', 'Learn more about our news website, mission, and team.'),

('Privacy Policy', 'privacy-policy', 'Privacy Policy\n\nLast Updated: ' || CURRENT_DATE || '\n\n1. Information We Collect\nWe collect information you provide directly to us when you create an account, subscribe to our newsletter, or contact us.\n\n2. How We Use Your Information\n- To provide and maintain our services\n- To send you newsletters and updates (if subscribed)\n- To respond to your inquiries\n- To improve our website and user experience\n\n3. Data Security\nWe implement appropriate security measures to protect your personal information.\n\n4. Cookies\nWe use cookies to enhance your browsing experience and analyze website traffic.\n\n5. Third-Party Links\nOur website may contain links to third-party websites. We are not responsible for their privacy practices.\n\n6. Your Rights\nYou have the right to access, correct, or delete your personal information.\n\n7. Changes to This Policy\nWe may update this privacy policy from time to time. Please review it periodically.\n\n8. Contact Us\nIf you have questions about this privacy policy, please contact us.', 'text', 'published', 1, 2, 'Privacy Policy', 'Read our privacy policy to understand how we collect, use, and protect your information.'),

('Terms & Conditions', 'terms-and-conditions', 'Terms and Conditions\n\nLast Updated: ' || CURRENT_DATE || '\n\n1. Acceptance of Terms\nBy accessing and using this website, you accept and agree to be bound by these Terms and Conditions.\n\n2. Use License\n- Content is provided for personal, non-commercial use only\n- You may not modify or reproduce our content without permission\n- You may not use our content for commercial purposes\n\n3. User Accounts\n- You are responsible for maintaining account security\n- You must provide accurate information\n- You must not share your account credentials\n\n4. User Content\n- You are responsible for any content you post\n- We reserve the right to remove inappropriate content\n- You grant us a license to use content you submit\n\n5. Prohibited Activities\n- Posting false or misleading information\n- Harassing or threatening other users\n- Attempting to hack or disrupt our services\n- Using automated systems to access our website\n\n6. Intellectual Property\nAll content on this website is protected by copyright and trademark laws.\n\n7. Disclaimer\nContent is provided "as is" without warranties of any kind.\n\n8. Limitation of Liability\nWe are not liable for any damages arising from your use of our website.\n\n9. Governing Law\nThese terms are governed by applicable laws.\n\n10. Changes to Terms\nWe reserve the right to modify these terms at any time.\n\nBy using our website, you agree to these terms.', 'text', 'published', 1, 3, 'Terms & Conditions', 'Read our terms and conditions for using our news website.');

-- ============================================================
-- END OF SCHEMA
-- ============================================================
