-- Content Views Tracking Table
-- Prevents duplicate view counting from same device/IP

CREATE TABLE IF NOT EXISTS `content_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `content_type` varchar(50) NOT NULL COMMENT 'article, video, podcast, reel, story, mobile_story',
  `content_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `fingerprint` varchar(32) NOT NULL COMMENT 'MD5 hash of IP + User Agent',
  `user_agent` varchar(255) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_type`, `content_id`),
  KEY `idx_fingerprint` (`fingerprint`),
  KEY `idx_viewed_at` (`viewed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create index for cleanup queries
CREATE INDEX idx_cleanup ON content_views (viewed_at);
