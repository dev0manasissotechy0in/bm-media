-- Create notification_logs table to track sent notifications
CREATE TABLE IF NOT EXISTS `notification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL COMMENT 'test, article, breaking, podcast, etc.',
  `title` varchar(255) DEFAULT NULL,
  `body` text,
  `topic` varchar(100) DEFAULT NULL COMMENT 'FCM topic: all, category_1, etc.',
  `target_tokens` text COMMENT 'Specific device tokens if not using topic',
  `sent_by` int(11) DEFAULT NULL COMMENT 'Admin/Author ID who sent it',
  `fcm_response` text COMMENT 'Raw FCM response',
  `success` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `sent_by` (`sent_by`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Log of all push notifications sent via FCM';
