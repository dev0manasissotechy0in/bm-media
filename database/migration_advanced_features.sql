-- Advanced Features Migration
-- Date: December 2, 2025

-- OTP Login System
CREATE TABLE IF NOT EXISTS otp_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    purpose ENUM('login', 'registration', 'password_reset') DEFAULT 'login',
    user_type ENUM('user', 'author', 'admin') DEFAULT 'user',
    expires_at DATETIME NOT NULL,
    is_used BOOLEAN DEFAULT 0,
    verified BOOLEAN DEFAULT 0,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_otp (otp_code),
    INDEX idx_expires (expires_at),
    INDEX idx_user_type (user_type)
);

-- Newsletter System
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255),
    status ENUM('subscribed', 'unsubscribed', 'pending') DEFAULT 'pending',
    verification_token VARCHAR(64),
    is_verified BOOLEAN DEFAULT 0,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL,
    preferences TEXT COMMENT 'JSON: categories to receive',
    INDEX idx_email (email),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS newsletter_campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    article_id INT NULL COMMENT 'If newsletter is for an article',
    template_type ENUM('custom', 'article', 'digest') DEFAULT 'custom',
    sent_count INT DEFAULT 0,
    open_count INT DEFAULT 0,
    click_count INT DEFAULT 0,
    bounce_count INT DEFAULT 0,
    status ENUM('draft', 'scheduled', 'sending', 'sent', 'failed') DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS newsletter_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    subscriber_id INT NOT NULL,
    tracking_token VARCHAR(64) UNIQUE,
    is_opened BOOLEAN DEFAULT 0,
    opened_at TIMESTAMP NULL,
    is_clicked BOOLEAN DEFAULT 0,
    clicked_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES newsletter_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (subscriber_id) REFERENCES newsletter_subscribers(id) ON DELETE CASCADE,
    INDEX idx_token (tracking_token),
    INDEX idx_campaign (campaign_id)
);

-- Cookie Preferences & User Tracking
CREATE TABLE IF NOT EXISTS cookie_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL COMMENT 'NULL for anonymous users',
    session_id VARCHAR(64) NOT NULL,
    necessary_cookies BOOLEAN DEFAULT 1,
    functional_cookies BOOLEAN DEFAULT 0,
    analytics_cookies BOOLEAN DEFAULT 0,
    marketing_cookies BOOLEAN DEFAULT 0,
    ip_address VARCHAR(45),
    user_agent TEXT,
    accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_session (session_id),
    INDEX idx_user (user_id)
);

CREATE TABLE IF NOT EXISTS user_interactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    session_id VARCHAR(64) NOT NULL,
    interaction_type ENUM('page_view', 'article_view', 'article_read', 'search', 'comment', 'like', 'save', 'share', 'click') NOT NULL,
    reference_type VARCHAR(50) COMMENT 'article, category, tag, etc',
    reference_id INT COMMENT 'ID of the referenced item',
    page_url TEXT,
    referrer_url TEXT,
    device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
    browser VARCHAR(100),
    os VARCHAR(100),
    ip_address VARCHAR(45),
    country_code VARCHAR(10),
    read_duration INT COMMENT 'Seconds spent reading',
    scroll_depth INT COMMENT 'Percentage scrolled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_session (session_id),
    INDEX idx_type (interaction_type),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_created (created_at)
);

-- SMTP Configuration Extensions
ALTER TABLE settings ADD COLUMN IF NOT EXISTS smtp_purpose ENUM('general', 'auth', 'newsletter', 'contact') DEFAULT 'general' AFTER setting_value;

-- Insert default SMTP configurations for different purposes
INSERT INTO settings (setting_key, setting_value, smtp_purpose) VALUES
('auth_smtp_enabled', '0', 'auth'),
('auth_smtp_host', '', 'auth'),
('auth_smtp_port', '587', 'auth'),
('auth_smtp_username', '', 'auth'),
('auth_smtp_password', '', 'auth'),
('auth_smtp_encryption', 'tls', 'auth'),
('auth_smtp_from_email', '', 'auth'),
('auth_smtp_from_name', 'Authentication', 'auth'),

('newsletter_smtp_enabled', '0', 'newsletter'),
('newsletter_smtp_host', '', 'newsletter'),
('newsletter_smtp_port', '587', 'newsletter'),
('newsletter_smtp_username', '', 'newsletter'),
('newsletter_smtp_password', '', 'newsletter'),
('newsletter_smtp_encryption', 'tls', 'newsletter'),
('newsletter_smtp_from_email', '', 'newsletter'),
('newsletter_smtp_from_name', 'Newsletter', 'newsletter'),

('contact_smtp_enabled', '0', 'contact'),
('contact_smtp_host', '', 'contact'),
('contact_smtp_port', '587', 'contact'),
('contact_smtp_username', '', 'contact'),
('contact_smtp_password', '', 'contact'),
('contact_smtp_encryption', 'tls', 'contact'),
('contact_smtp_from_email', '', 'contact'),
('contact_smtp_from_name', 'Contact Form', 'contact')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- Add newsletter flag to articles
ALTER TABLE articles ADD COLUMN IF NOT EXISTS send_as_newsletter BOOLEAN DEFAULT 0 AFTER status;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS newsletter_sent_at TIMESTAMP NULL AFTER send_as_newsletter;
ALTER TABLE articles ADD COLUMN IF NOT EXISTS newsletter_campaign_id INT NULL AFTER newsletter_sent_at;

-- Add OTP preference to users
ALTER TABLE users ADD COLUMN IF NOT EXISTS otp_enabled BOOLEAN DEFAULT 0 AFTER password;
ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT 0 AFTER otp_enabled;

-- Indexes for performance
CREATE INDEX idx_articles_newsletter ON articles(send_as_newsletter, newsletter_sent_at);
CREATE INDEX idx_users_otp ON users(otp_enabled);

-- Stats view for admin dashboard
CREATE OR REPLACE VIEW newsletter_stats AS
SELECT 
    (SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'subscribed') as total_subscribers,
    (SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'pending') as pending_subscribers,
    (SELECT COUNT(*) FROM newsletter_campaigns WHERE status = 'sent') as total_campaigns,
    (SELECT COUNT(*) FROM newsletter_campaigns WHERE status = 'draft') as draft_campaigns,
    (SELECT SUM(sent_count) FROM newsletter_campaigns WHERE status = 'sent') as total_emails_sent,
    (SELECT SUM(open_count) FROM newsletter_campaigns WHERE status = 'sent') as total_opens,
    (SELECT SUM(click_count) FROM newsletter_campaigns WHERE status = 'sent') as total_clicks;

CREATE OR REPLACE VIEW user_interaction_stats AS
SELECT 
    DATE(created_at) as date,
    interaction_type,
    COUNT(*) as count,
    COUNT(DISTINCT session_id) as unique_sessions,
    COUNT(DISTINCT user_id) as unique_users
FROM user_interactions
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at), interaction_type;
