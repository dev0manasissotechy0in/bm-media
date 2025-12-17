-- ============================================
-- CASE THREADS DATABASE SCHEMA
-- ============================================

-- Drop existing tables (in reverse order of dependencies)
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS case_follows;
DROP TABLE IF EXISTS case_reviews;
DROP TABLE IF EXISTS case_media;
DROP TABLE IF EXISTS case_documents;
DROP TABLE IF EXISTS case_timeline_events;
DROP TABLE IF EXISTS case_article_map;
DROP TABLE IF EXISTS case_threads;

-- ============================================
-- 1. CASE THREADS TABLE (Main entity)
-- ============================================
CREATE TABLE case_threads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    short_description TEXT,
    full_description LONGTEXT,
    
    -- Classification
    status ENUM('ongoing', 'closed', 'historic', 'verdict_pending') DEFAULT 'ongoing',
    category VARCHAR(100), -- Crime, War, Politics, Scam, Corruption, etc.
    
    -- Location and dates
    primary_location VARCHAR(255),
    start_date DATE,
    end_date DATE NULL,
    
    -- Media
    thumbnail VARCHAR(500),
    cover_image VARCHAR(500),
    
    -- Metadata
    total_articles INT DEFAULT 0,
    total_followers INT DEFAULT 0,
    total_views INT DEFAULT 0,
    
    -- SEO
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords TEXT,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_slug (slug),
    INDEX idx_last_activity (last_activity_at DESC),
    INDEX idx_total_articles (total_articles DESC),
    INDEX idx_total_followers (total_followers DESC),
    
    -- Full-text search
    FULLTEXT INDEX ft_search (title, short_description, full_description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. CASE-ARTICLE MAPPING TABLE (Many-to-Many)
-- ============================================
CREATE TABLE case_article_map (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id INT NOT NULL,
    article_id INT NOT NULL,
    
    -- Importance of this article to the case
    relevance_score TINYINT DEFAULT 5, -- 1-10 scale
    is_key_article BOOLEAN DEFAULT FALSE,
    
    -- Additional context
    article_context TEXT, -- Why this article is relevant to this case
    
    -- Timestamps
    linked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Relationships
    FOREIGN KEY (case_id) REFERENCES case_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    
    -- Ensure no duplicate mappings
    UNIQUE KEY unique_case_article (case_id, article_id),
    
    -- Indexes
    INDEX idx_case_id (case_id),
    INDEX idx_article_id (article_id),
    INDEX idx_key_article (is_key_article)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. TIMELINE EVENTS TABLE
-- ============================================
CREATE TABLE case_timeline_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id INT NOT NULL,
    
    -- Event details
    event_title VARCHAR(255) NOT NULL,
    event_description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NULL,
    
    -- Classification
    event_type ENUM(
        'incident', 'arrest', 'fir', 'charge_sheet', 'hearing', 
        'verdict', 'appeal', 'protest', 'policy', 'investigation', 
        'statement', 'other'
    ) DEFAULT 'other',
    
    -- Linked entities
    linked_article_ids TEXT, -- JSON array of article IDs: [1,5,10]
    linked_document_ids TEXT, -- JSON array of document IDs
    
    -- Importance
    is_major_event BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Relationships
    FOREIGN KEY (case_id) REFERENCES case_threads(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_case_id (case_id),
    INDEX idx_event_date (event_date DESC),
    INDEX idx_event_type (event_type),
    INDEX idx_major_event (is_major_event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. DOCUMENTS TABLE
-- ============================================
CREATE TABLE case_documents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id INT NOT NULL,
    
    -- Document details
    title VARCHAR(255) NOT NULL,
    document_type ENUM(
        'judgment', 'court_order', 'fir', 'charge_sheet', 'petition', 
        'government_notification', 'police_report', 'forensic_report', 
        'affidavit', 'other'
    ) NOT NULL,
    
    description TEXT,
    plain_language_summary TEXT, -- Explain in simple terms
    
    -- File details
    file_url VARCHAR(500), -- PDF/DOC link
    file_type VARCHAR(50), -- pdf, doc, html
    file_size INT, -- in bytes
    
    -- Metadata
    document_date DATE,
    source VARCHAR(255), -- Court name, Police station, Govt dept
    official_reference_number VARCHAR(255), -- Case number, FIR number, etc.
    
    -- Display
    display_order INT DEFAULT 0,
    
    -- Timestamps
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Relationships
    FOREIGN KEY (case_id) REFERENCES case_threads(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_case_id (case_id),
    INDEX idx_document_type (document_type),
    INDEX idx_document_date (document_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. MEDIA TABLE
-- ============================================
CREATE TABLE case_media (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id INT NOT NULL,
    
    -- Media details
    media_type ENUM('photo', 'video', 'audio') NOT NULL,
    title VARCHAR(255),
    caption TEXT,
    
    -- File details
    file_url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500),
    
    -- Metadata
    media_date DATE,
    source VARCHAR(255), -- Photographer, Agency, Court, Police
    duration INT NULL, -- For video/audio in seconds
    
    -- Display
    display_order INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Relationships
    FOREIGN KEY (case_id) REFERENCES case_threads(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_case_id (case_id),
    INDEX idx_media_type (media_type),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. OFFICIAL REVIEWS TABLE
-- ============================================
CREATE TABLE case_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    case_id INT NOT NULL,
    
    -- Review details
    title VARCHAR(255) NOT NULL,
    review_type ENUM(
        'official_statement', 'legal_analysis', 'expert_opinion', 
        'fact_check', 'court_observation', 'police_statement', 
        'government_statement'
    ) NOT NULL,
    
    summary TEXT,
    full_content LONGTEXT,
    
    -- Source
    author_name VARCHAR(255),
    author_designation VARCHAR(255), -- Ex-Judge, Lawyer, Police Commissioner
    organization VARCHAR(255),
    
    -- Links
    external_url VARCHAR(500),
    
    -- Metadata
    review_date DATE,
    
    -- Display
    display_order INT DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Relationships
    FOREIGN KEY (case_id) REFERENCES case_threads(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_case_id (case_id),
    INDEX idx_review_type (review_type),
    INDEX idx_verified (is_verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. CASE FOLLOWS TABLE (User subscriptions)
-- ============================================
CREATE TABLE case_follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    case_id INT NOT NULL,
    
    -- Notification preferences
    notify_new_articles BOOLEAN DEFAULT TRUE,
    notify_timeline_events BOOLEAN DEFAULT TRUE,
    notify_documents BOOLEAN DEFAULT TRUE,
    notify_verdicts BOOLEAN DEFAULT TRUE,
    
    -- Timestamps
    followed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Relationships
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (case_id) REFERENCES case_threads(id) ON DELETE CASCADE,
    
    -- Ensure user can't follow same case twice
    UNIQUE KEY unique_user_case (user_id, case_id),
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_case_id (case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 8. NOTIFICATIONS TABLE
-- ============================================
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    case_id INT,
    
    -- Notification content
    notification_type ENUM(
        'new_article', 'timeline_event', 'document_added', 
        'verdict', 'case_update', 'case_closed'
    ) NOT NULL,
    
    title VARCHAR(255) NOT NULL,
    message TEXT,
    
    -- Links
    action_url VARCHAR(500), -- Deep link or web URL
    entity_type VARCHAR(50), -- article, event, document
    entity_id INT,
    
    -- Status
    is_read BOOLEAN DEFAULT FALSE,
    is_sent BOOLEAN DEFAULT FALSE, -- For push notifications
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    
    -- Relationships
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (case_id) REFERENCES case_threads(id) ON DELETE SET NULL,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_case_id (case_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. ADD CASE THREAD FIELDS TO ARTICLES TABLE
-- ============================================
-- Run this if articles table already exists
ALTER TABLE articles 
ADD COLUMN is_case_article BOOLEAN DEFAULT FALSE AFTER content_type,
ADD INDEX idx_is_case_article (is_case_article);

-- ============================================
-- SAMPLE DATA FOR TESTING
-- ============================================

-- Insert sample case thread
INSERT INTO case_threads (
    title, slug, short_description, status, category, 
    primary_location, start_date, thumbnail
) VALUES (
    'Nirbhaya Case - Delhi Gang Rape',
    'nirbhaya-case-delhi-gang-rape',
    'The 2012 Delhi gang rape and murder case that led to widespread protests and legal reforms in India.',
    'closed',
    'Crime',
    'New Delhi, India',
    '2012-12-16',
    '/uploads/cases/nirbhaya-case.jpg'
);

-- Insert sample timeline event
INSERT INTO case_timeline_events (
    case_id, event_title, event_description, event_date, event_type, is_major_event
) VALUES (
    1,
    'Incident Occurred',
    'Brutal gang rape and assault on a 23-year-old woman in a moving bus in South Delhi.',
    '2012-12-16',
    'incident',
    TRUE
);

-- Insert sample document
INSERT INTO case_documents (
    case_id, title, document_type, description, document_date, source
) VALUES (
    1,
    'Supreme Court Judgment - Death Penalty Upheld',
    'judgment',
    'Final Supreme Court judgment upholding death penalty for all four convicts.',
    '2017-05-05',
    'Supreme Court of India'
);
