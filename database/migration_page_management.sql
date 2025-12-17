-- ============================================================
-- CUSTOM PAGE MANAGEMENT SYSTEM - DATABASE MIGRATION
-- Run this SQL file to add page management features to existing database
-- ============================================================

USE news_website;

-- ============================================================
-- CREATE TABLES
-- ============================================================

-- Custom Pages Table
CREATE TABLE IF NOT EXISTS `custom_pages` (
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
CREATE TABLE IF NOT EXISTS `contact_queries` (
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

-- Settings Table (for SMTP and dynamic settings)
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- INSERT DEFAULT DATA
-- ============================================================

-- Insert Default SMTP Settings
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('smtp_enabled', '0'),
('smtp_host', ''),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_encryption', 'tls'),
('smtp_from_email', ''),
('smtp_from_name', 'News Website'),
('contact_email', '');

-- Insert Default Custom Pages (About Us, Privacy Policy, Terms)
INSERT IGNORE INTO `custom_pages` (`title`, `slug`, `content`, `page_type`, `status`, `show_in_footer`, `order_id`, `meta_title`, `meta_description`) VALUES
('About Us', 'about-us', 
'Welcome to our news website! We are committed to bringing you the latest, most accurate news from around the world.

Our Mission:
To provide reliable, unbiased news coverage that keeps you informed about what matters most.

Our Team:
We have a dedicated team of journalists and editors working around the clock to bring you breaking news, in-depth analysis, and exclusive stories.

What Sets Us Apart:
- 24/7 news coverage
- Experienced journalists
- Multiple news categories
- User-friendly interface
- Mobile responsive design

Contact Us:
Have a story tip or feedback? We\'d love to hear from you! Visit our contact page to get in touch.

Thank you for choosing us as your trusted news source!', 
'text', 'published', 1, 1, 'About Us', 'Learn more about our news website, mission, and team.'),

('Privacy Policy', 'privacy-policy',
'Privacy Policy

Last Updated: December 2, 2025

1. Information We Collect
We collect information you provide directly to us when you:
- Create an account
- Subscribe to our newsletter
- Submit comments on articles
- Contact us through our contact form
- Use our website and services

2. How We Use Your Information
We use the information we collect to:
- Provide and maintain our services
- Send you newsletters and updates (if subscribed)
- Respond to your inquiries and comments
- Improve our website and user experience
- Analyze usage patterns and trends
- Prevent fraud and ensure security

3. Data Security
We implement appropriate technical and organizational security measures to protect your personal information from unauthorized access, disclosure, alteration, or destruction.

4. Cookies and Tracking
We use cookies and similar technologies to:
- Remember your preferences
- Analyze website traffic
- Personalize content
- Improve user experience

You can control cookies through your browser settings.

5. Third-Party Links
Our website may contain links to third-party websites. We are not responsible for their privacy practices. Please review their privacy policies before providing any information.

6. Your Rights
You have the right to:
- Access your personal information
- Correct inaccurate data
- Delete your account and data
- Opt-out of marketing communications
- Object to data processing

7. Children\'s Privacy
Our services are not intended for children under 13. We do not knowingly collect information from children.

8. Changes to This Policy
We may update this privacy policy from time to time. Please review it periodically for any changes. Continued use of our website constitutes acceptance of updates.

9. Contact Us
If you have questions about this privacy policy, please contact us through our contact page.

By using our website, you consent to our privacy policy.',
'text', 'published', 1, 2, 'Privacy Policy', 'Read our privacy policy to understand how we collect, use, and protect your information.'),

('Terms & Conditions', 'terms-and-conditions',
'Terms and Conditions

Last Updated: December 2, 2025

1. Acceptance of Terms
By accessing and using this website, you accept and agree to be bound by these Terms and Conditions. If you do not agree, please do not use our website.

2. Use License
- Content is provided for personal, non-commercial use only
- You may not modify, copy, or reproduce our content without permission
- You may not use our content for commercial purposes
- You may not attempt to reverse engineer any website features

3. User Accounts
If you create an account, you agree to:
- Provide accurate and complete information
- Maintain account security and confidentiality
- Not share your account credentials
- Notify us immediately of any unauthorized access

4. User-Generated Content
When you post comments or submit content:
- You are responsible for the content you post
- Content must not be illegal, offensive, or harmful
- We reserve the right to remove inappropriate content
- You grant us a license to use content you submit

5. Prohibited Activities
You agree not to:
- Post false, misleading, or defamatory information
- Harass, threaten, or harm other users
- Attempt to hack or disrupt our services
- Use automated systems (bots) to access our website
- Violate any applicable laws or regulations

6. Intellectual Property
All content on this website, including text, images, logos, and graphics, is protected by copyright and trademark laws. Unauthorized use is prohibited.

7. Disclaimer of Warranties
- Content is provided "as is" without warranties of any kind
- We do not guarantee accuracy or completeness of information
- We are not liable for any errors or omissions
- Use of our website is at your own risk

8. Limitation of Liability
We are not liable for any damages arising from:
- Your use of our website
- Inability to access our services
- Third-party content or links
- User-generated content
- Technical issues or errors

9. News Content Disclaimer
- News articles are for informational purposes only
- We strive for accuracy but cannot guarantee it
- Opinions expressed in articles are those of the authors
- We are not responsible for decisions based on our content

10. Modifications
We reserve the right to:
- Modify these terms at any time
- Update our services and features
- Suspend or terminate accounts for violations

11. Governing Law
These terms are governed by applicable laws and regulations.

12. Contact Us
For questions about these terms, please contact us through our contact page.

By using our website, you agree to these terms and conditions.',
'text', 'published', 1, 3, 'Terms & Conditions', 'Read our terms and conditions for using our news website.');

-- ============================================================
-- MIGRATION COMPLETE
-- ============================================================

SELECT 'Custom Page Management System tables created successfully!' as Status;
SELECT COUNT(*) as 'Custom Pages Created' FROM custom_pages;
SELECT COUNT(*) as 'Settings Created' FROM settings;