-- ============================================
-- SPLASH SCREEN SETTINGS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS splash_screen_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Status
    is_dynamic_enabled BOOLEAN DEFAULT FALSE,
    
    -- Default Splash Settings
    default_logo VARCHAR(500),
    default_background_color VARCHAR(20) DEFAULT '#FFFFFF',
    default_text_color VARCHAR(20) DEFAULT '#000000',
    default_tagline VARCHAR(255),
    default_animation_type ENUM('fade', 'slide', 'zoom', 'bounce', 'none') DEFAULT 'fade',
    
    -- Dynamic Splash Settings
    dynamic_image VARCHAR(500),
    dynamic_background_color VARCHAR(20),
    dynamic_text_color VARCHAR(20),
    dynamic_title VARCHAR(255),
    dynamic_subtitle VARCHAR(500),
    dynamic_button_text VARCHAR(100),
    dynamic_button_action VARCHAR(500), -- URL or deep link
    dynamic_display_duration INT DEFAULT 3, -- seconds
    
    -- Schedule Settings
    is_scheduled BOOLEAN DEFAULT FALSE,
    schedule_start_date DATETIME,
    schedule_end_date DATETIME,
    
    -- Targeting
    target_new_users_only BOOLEAN DEFAULT FALSE,
    target_platforms VARCHAR(255), -- 'all', 'android', 'ios', 'web'
    
    -- Analytics
    impression_count INT DEFAULT 0,
    click_count INT DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_is_dynamic_enabled (is_dynamic_enabled),
    INDEX idx_schedule (is_scheduled, schedule_start_date, schedule_end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO splash_screen_settings (
    id,
    is_dynamic_enabled,
    default_logo,
    default_background_color,
    default_text_color,
    default_tagline,
    default_animation_type,
    target_platforms
) VALUES (
    1,
    FALSE,
    'assets/images/logo.png',
    '#FFFFFF',
    '#000000',
    'Your Trusted News Source',
    'fade',
    'all'
) ON DUPLICATE KEY UPDATE id=id;
