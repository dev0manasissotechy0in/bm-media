-- Check Auth SMTP Settings in Database
-- Run this in phpMyAdmin SQL tab

-- 1. Check if smtp_purpose column exists
SHOW COLUMNS FROM settings LIKE 'smtp_purpose';

-- 2. Select all auth-related SMTP settings
SELECT 
    id,
    setting_key,
    setting_value,
    smtp_purpose,
    created_at,
    updated_at
FROM settings 
WHERE smtp_purpose = 'auth'
ORDER BY setting_key;

-- 3. If no results above, insert the auth SMTP settings
INSERT IGNORE INTO settings (setting_key, setting_value, smtp_purpose) VALUES
('auth_smtp_enabled', '0', 'auth'),
('auth_smtp_host', 'smtp.gmail.com', 'auth'),
('auth_smtp_port', '587', 'auth'),
('auth_smtp_username', '', 'auth'),
('auth_smtp_password', '', 'auth'),
('auth_smtp_encryption', 'tls', 'auth'),
('auth_smtp_from_email', '', 'auth'),
('auth_smtp_from_name', 'Authentication', 'auth');

-- 4. Verify insertion - select again
SELECT 
    setting_key,
    setting_value,
    smtp_purpose
FROM settings 
WHERE smtp_purpose = 'auth'
ORDER BY setting_key;

-- 5. Enable auth SMTP (set this to 1 after configuring)
-- UPDATE settings SET setting_value = '1' WHERE setting_key = 'auth_smtp_enabled';

-- 6. Example: Configure Gmail SMTP for auth
-- UPDATE settings SET setting_value = 'smtp.gmail.com' WHERE setting_key = 'auth_smtp_host';
-- UPDATE settings SET setting_value = '587' WHERE setting_key = 'auth_smtp_port';
-- UPDATE settings SET setting_value = 'your-email@gmail.com' WHERE setting_key = 'auth_smtp_username';
-- UPDATE settings SET setting_value = 'your-app-password' WHERE setting_key = 'auth_smtp_password';
-- UPDATE settings SET setting_value = 'tls' WHERE setting_key = 'auth_smtp_encryption';
-- UPDATE settings SET setting_value = 'your-email@gmail.com' WHERE setting_key = 'auth_smtp_from_email';
-- UPDATE settings SET setting_value = 'Your App Name' WHERE setting_key = 'auth_smtp_from_name';
-- UPDATE settings SET setting_value = '1' WHERE setting_key = 'auth_smtp_enabled';

-- 7. Quick Setup for Gmail (Fill in your details)
/*
UPDATE settings SET setting_value = CASE setting_key
    WHEN 'auth_smtp_enabled' THEN '1'
    WHEN 'auth_smtp_host' THEN 'smtp.gmail.com'
    WHEN 'auth_smtp_port' THEN '587'
    WHEN 'auth_smtp_username' THEN 'YOUR_EMAIL@gmail.com'
    WHEN 'auth_smtp_password' THEN 'YOUR_APP_PASSWORD'
    WHEN 'auth_smtp_encryption' THEN 'tls'
    WHEN 'auth_smtp_from_email' THEN 'YOUR_EMAIL@gmail.com'
    WHEN 'auth_smtp_from_name' THEN 'Your App Name'
    ELSE setting_value
END
WHERE setting_key LIKE 'auth_smtp_%';
*/

-- 8. View final configuration
SELECT 
    setting_key,
    CASE 
        WHEN setting_key = 'auth_smtp_password' THEN '***HIDDEN***'
        ELSE setting_value 
    END as setting_value,
    smtp_purpose
FROM settings 
WHERE smtp_purpose = 'auth'
ORDER BY setting_key;
