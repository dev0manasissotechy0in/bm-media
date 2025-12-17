-- Content Types Table
CREATE TABLE IF NOT EXISTS content_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    color VARCHAR(20) DEFAULT '#000000',
    template VARCHAR(50) DEFAULT 'default',
    settings JSON,
    display_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add content_type_id to articles table
ALTER TABLE articles 
ADD COLUMN content_type_id INT DEFAULT NULL AFTER category_id,
ADD FOREIGN KEY (content_type_id) REFERENCES content_types(id) ON DELETE SET NULL;

-- Insert default content types
INSERT INTO content_types (name, slug, description, icon, color, template, display_order, settings) VALUES
('News', 'news', 'Breaking news and current events', 'bi bi-newspaper', '#dc3545', 'default', 1, 
 '{"show_author":1,"show_date":1,"show_category":1,"show_tags":1,"show_share":1,"show_comments":1,"allow_featured":1,"require_featured_image":1,"min_word_count":100,"max_word_count":0}'),

('Opinion', 'opinion', 'Editorials, columns, and opinion pieces', 'bi bi-chat-quote', '#6f42c1', 'default', 2,
 '{"show_author":1,"show_date":1,"show_category":1,"show_tags":1,"show_share":1,"show_comments":1,"allow_featured":1,"require_featured_image":0,"min_word_count":300,"max_word_count":0}'),

('Feature', 'feature', 'Long-form journalism and in-depth stories', 'bi bi-file-earmark-text', '#0d6efd', 'feature', 3,
 '{"show_author":1,"show_date":1,"show_category":1,"show_tags":1,"show_share":1,"show_comments":1,"allow_featured":1,"require_featured_image":1,"min_word_count":1000,"max_word_count":0}'),

('Interview', 'interview', 'Q&A format interviews', 'bi bi-mic', '#198754', 'interview', 4,
 '{"show_author":1,"show_date":1,"show_category":1,"show_tags":1,"show_share":1,"show_comments":1,"allow_featured":1,"require_featured_image":1,"min_word_count":500,"max_word_count":0}'),

('Review', 'review', 'Product, book, movie, and event reviews', 'bi bi-star', '#fd7e14', 'review', 5,
 '{"show_author":1,"show_date":1,"show_category":1,"show_tags":1,"show_share":1,"show_comments":1,"allow_featured":0,"require_featured_image":1,"min_word_count":300,"max_word_count":2000}'),

('Analysis', 'analysis', 'Deep-dive analysis and explainers', 'bi bi-graph-up', '#20c997', 'default', 6,
 '{"show_author":1,"show_date":1,"show_category":1,"show_tags":1,"show_share":1,"show_comments":1,"allow_featured":1,"require_featured_image":0,"min_word_count":800,"max_word_count":0}'),

('Gallery', 'gallery', 'Photo galleries and visual stories', 'bi bi-images', '#e83e8c', 'gallery', 7,
 '{"show_author":1,"show_date":1,"show_category":1,"show_tags":1,"show_share":1,"show_comments":1,"allow_featured":1,"require_featured_image":1,"min_word_count":0,"max_word_count":500}'),

('Video', 'video', 'Video content and multimedia stories', 'bi bi-play-circle', '#6610f2', 'video', 8,
 '{"show_author":1,"show_date":1,"show_category":1,"show_tags":1,"show_share":1,"show_comments":1,"allow_featured":1,"require_featured_image":1,"min_word_count":0,"max_word_count":500}'),

('Listicle', 'listicle', 'List-based articles (Top 10, Best of, etc.)', 'bi bi-list-ol', '#ffc107', 'default', 9,
 '{"show_author":1,"show_date":1,"show_category":1,"show_tags":1,"show_share":1,"show_comments":1,"allow_featured":1,"require_featured_image":1,"min_word_count":500,"max_word_count":3000}'),

('Live Blog', 'live-blog', 'Live coverage of ongoing events', 'bi bi-broadcast', '#ff0000', 'timeline', 10,
 '{"show_author":1,"show_date":1,"show_category":1,"show_tags":1,"show_share":1,"show_comments":0,"allow_featured":1,"require_featured_image":0,"min_word_count":0,"max_word_count":0}');
