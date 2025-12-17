-- ====================================================
-- AUTH SYSTEM FIX - Database Migration
-- Run this to ensure all auth-related tables are correct
-- ====================================================

-- 1. Ensure OTP codes table has all required columns
ALTER TABLE otp_codes 
ADD COLUMN IF NOT EXISTS user_type ENUM('user', 'author', 'admin') DEFAULT 'user' AFTER purpose,
ADD COLUMN IF NOT EXISTS verified BOOLEAN DEFAULT 0 AFTER is_used;

-- Add indexes if they don't exist
ALTER TABLE otp_codes 
ADD INDEX IF NOT EXISTS idx_user_type (user_type),
ADD INDEX IF NOT EXISTS idx_purpose (purpose),
ADD INDEX IF NOT EXISTS idx_verified (verified);

-- 2. Ensure user_sessions table exists
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    device_info VARCHAR(255),
    ip_address VARCHAR(45),
    fcm_token TEXT,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Ensure users table has all required fields
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS fcm_token TEXT AFTER phone_verified,
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL AFTER fcm_token,
ADD COLUMN IF NOT EXISTS auth_provider ENUM('email', 'google', 'facebook', 'apple') DEFAULT 'email' AFTER password;

-- 4. Add indexes for better performance
ALTER TABLE users 
ADD INDEX IF NOT EXISTS idx_email (email),
ADD INDEX IF NOT EXISTS idx_phone (phone),
ADD INDEX IF NOT EXISTS idx_status (status),
ADD INDEX IF NOT EXISTS idx_auth_provider (auth_provider);

-- 5. Clean up expired OTPs (older than 24 hours)
DELETE FROM otp_codes 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- 6. Clean up expired sessions (older than 30 days)
DELETE FROM user_sessions 
WHERE expires_at < NOW();

-- 7. Verification: Check the structure
SELECT 'OTP Table Columns' as check_type, COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'otp_codes' 
AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION;

SELECT 'User Table Columns' as check_type, COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'users' 
AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION;

SELECT 'Session Table Columns' as check_type, COLUMN_NAME, DATA_TYPE, IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'user_sessions' 
AND TABLE_SCHEMA = DATABASE()
ORDER BY ORDINAL_POSITION;

-- Success message
SELECT '✅ AUTH SYSTEM DATABASE MIGRATION COMPLETED' as status;
