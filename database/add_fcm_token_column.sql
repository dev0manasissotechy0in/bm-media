-- Add fcm_token column to users table for push notifications
ALTER TABLE `users` 
ADD COLUMN `fcm_token` TEXT NULL COMMENT 'Firebase Cloud Messaging device token for push notifications' AFTER `status`,
ADD COLUMN `fcm_token_updated_at` DATETIME NULL COMMENT 'Last time FCM token was updated' AFTER `fcm_token`,
ADD INDEX `idx_fcm_token` (`fcm_token_updated_at`);
