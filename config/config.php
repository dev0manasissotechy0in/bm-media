<?php
// ============================================================
// CORE CONFIGURATION FILE
// ============================================================

// Error Reporting (Set to 0 in production)
// Exclude deprecated warnings from Google library for cleaner API responses
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);

// Development Mode (Set to false in production)
define('DEV_MODE', true);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'news_website');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_NAME', 'Brackodd Media');
// define('SITE_URL', 'http://10.0.2.2'); // Android Emulator testing
define('SITE_URL', 'http://192.168.1.3'); // Real Device - Wi-Fi IP
// define('SITE_URL', 'https://brackoddmedia.com'); // Production
// define('SITE_URL', ''); // Auto-detect
define('SITE_EMAIL', 'contact@brackoddmedia.com');

// Directory Paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('API_PATH', ROOT_PATH . '/api');

// URL Paths
define('BASE_URL', SITE_URL);
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');
define('ADMIN_URL', BASE_URL . '/admin');
define('API_URL', BASE_URL . '/api');

// Upload Directories
define('UPLOAD_ARTICLE_PATH', UPLOADS_PATH . '/articles');
define('UPLOAD_USER_PATH', UPLOADS_PATH . '/users');
define('UPLOAD_REPORTER_PATH', UPLOADS_PATH . '/reporters');
define('UPLOAD_ADS_PATH', UPLOADS_PATH . '/ads');
define('UPLOAD_CATEGORY_PATH', UPLOADS_PATH . '/categories');
define('UPLOAD_ELECTION_PATH', UPLOADS_PATH . '/election');
define('UPLOAD_CRICKET_PATH', UPLOADS_PATH . '/cricket');
define('UPLOAD_STORIES_PATH', UPLOADS_PATH . '/stories');
define('UPLOAD_REELS_PATH', UPLOADS_PATH . '/reels');

// Session Configuration
define('SESSION_LIFETIME', 86400); // 24 hours
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);

// Security Configuration
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 10);
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour

// Pagination
define('ARTICLES_PER_PAGE', 20);
define('COMMENTS_PER_PAGE', 50);
define('ADMIN_ITEMS_PER_PAGE', 25);

// File Upload Limits
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_VIDEO_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);
define('ALLOWED_DOCUMENT_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);

// Social Authentication (Add your credentials)
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT_URI', BASE_URL . '/auth/google-callback.php');

define('FACEBOOK_APP_ID', '');
define('FACEBOOK_APP_SECRET', '');
define('FACEBOOK_REDIRECT_URI', BASE_URL . '/auth/facebook-callback.php');

// Email Configuration (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_FROM_EMAIL', SITE_EMAIL);
define('SMTP_FROM_NAME', SITE_NAME);

// API Keys
define('GOOGLE_ANALYTICS_ID', '');
define('GOOGLE_ADSENSE_CLIENT', '');
define('FACEBOOK_PIXEL_ID', '');

// Cache Configuration
define('ENABLE_CACHE', false);
define('CACHE_LIFETIME', 3600); // 1 hour

// SEO Configuration
define('DEFAULT_META_DESCRIPTION', 'Get the latest news from around the world. Breaking news, politics, sports, entertainment, technology, business and more.');
define('DEFAULT_META_KEYWORDS', 'news, breaking news, latest news, politics, sports, entertainment');
define('DEFAULT_OG_IMAGE', ASSETS_URL . '/images/og-image.jpg');

// Notification Configuration
define('ENABLE_WEB_NOTIFICATIONS', true);
define('FIREBASE_SERVER_KEY', '');

// Live Update Configuration
define('LIVE_UPDATE_INTERVAL', 10000); // 10 seconds in milliseconds
define('ELECTION_UPDATE_INTERVAL', 10000);
define('CRICKET_UPDATE_INTERVAL', 10000);
define('MARKET_UPDATE_INTERVAL', 10000);

// Auto-refresh Settings
define('ENABLE_AUTO_REFRESH', true);

// Comment Moderation
define('COMMENTS_REQUIRE_APPROVAL', true);

// User Registration
define('ENABLE_USER_REGISTRATION', true);
define('REQUIRE_EMAIL_VERIFICATION', false);

// Reporter Settings
define('REPORTER_ID_PREFIX', 'REP');
define('REPORTER_VALIDITY_DAYS', 365);

// Article Settings
define('ENABLE_ARTICLE_DOWNLOAD', true);
define('ARTICLE_EXCERPT_LENGTH', 200);

// Sitemap Configuration
define('SITEMAP_PATH', ROOT_PATH . '/sitemap.xml');
define('SITEMAP_UPDATE_FREQUENCY', 'daily');

// Google Indexing API
define('GOOGLE_INDEXING_API_KEY', '');

// Ads Configuration
define('ENABLE_GOOGLE_ADS', false);
define('ENABLE_CUSTOM_ADS', true);

// Newsletter Configuration
define('NEWSLETTER_FROM_EMAIL', SITE_EMAIL);
define('NEWSLETTER_FROM_NAME', SITE_NAME);

// Load required files
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Security.php';
require_once INCLUDES_PATH . '/Session.php';
require_once INCLUDES_PATH . '/Functions.php';

// Start session
Session::start();

// Initialize database connection
$db = Database::getInstance();
?>
