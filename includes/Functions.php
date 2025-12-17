<?php
// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Get current URL
 */
function currentURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Format date
 * @param mixed $date Primary date to format
 * @param string $format Date format string
 * @param mixed $fallback Fallback date if primary is empty
 * @return string Formatted date or 'N/A' if both are empty
 */
function formatDate($date, $format = 'd M, Y', $fallback = null) {
    // Use fallback if primary date is empty
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        if (!empty($fallback) && $fallback !== '0000-00-00 00:00:00') {
            $date = $fallback;
        } else {
            return 'N/A';
        }
    }
    
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return 'N/A';
    }
    return date($format, $timestamp);
}

/**
 * Time ago function
 * @param mixed $datetime Primary datetime to format
 * @param mixed $fallback Fallback datetime if primary is empty
 * @return string Time ago string or 'N/A' if both are empty
 */
function timeAgo($datetime, $fallback = null) {
    // Use fallback if primary datetime is empty
    if (empty($datetime)) {
        if (!empty($fallback)) {
            $datetime = $fallback;
        } else {
            return 'N/A';
        }
    }
    $time = strtotime($datetime);
    if ($time === false) {
        return 'N/A';
    }
    $diff = time() - $time;
    
    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return date('d M, Y', $time);
    }
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . $suffix;
    }
    return $text;
}

/**
 * Get excerpt from HTML content
 */
function getExcerpt($html, $length = 200) {
    $text = strip_tags($html);
    return truncateText($text, $length);
}

/**
 * Generate URL-friendly slug from text
 */
function generateSlug($text) {
    // Convert to lowercase
    $slug = strtolower(trim($text));
    
    // Replace spaces and underscores with hyphens
    $slug = preg_replace('/[\s_]+/', '-', $slug);
    
    // Remove special characters (keep only alphanumeric and hyphens)
    $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
    
    // Remove multiple consecutive hyphens
    $slug = preg_replace('/-+/', '-', $slug);
    
    // Trim hyphens from start and end
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * Format number (for views, likes, etc.)
 */
function formatNumber($num) {
    if ($num >= 1000000) {
        return round($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return round($num / 1000, 1) . 'K';
    }
    return $num;
}

/**
 * Get user IP
 */
function getUserIP() {
    return Security::getClientIP();
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Send JSON response
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Handle file upload
 */
function uploadFile($file, $upload_path, $allowed_types, $max_size) {
    // Validate file
    $validation = Security::validateFileUpload($file, $allowed_types, $max_size);
    
    if (!$validation['success']) {
        return ['success' => false, 'message' => $validation['message']];
    }
    
    // Create upload directory if not exists
    if (!file_exists($upload_path)) {
        mkdir($upload_path, 0755, true);
    }
    
    // Generate secure filename
    $filename = Security::generateSecureFilename($file['name']);
    $destination = $upload_path . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $destination,
            'url' => str_replace(ROOT_PATH, BASE_URL, $destination)
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

/**
 * Delete file
 */
function deleteFile($file_path) {
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return false;
}

/**
 * Resize image
 */
function resizeImage($source, $destination, $width, $height) {
    list($orig_width, $orig_height, $image_type) = getimagesize($source);
    
    // Calculate aspect ratio
    $ratio = $orig_width / $orig_height;
    
    if ($width / $height > $ratio) {
        $width = $height * $ratio;
    } else {
        $height = $width / $ratio;
    }
    
    // Create image from source
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Create new image
    $new_image = imagecreatetruecolor($width, $height);
    
    // Preserve transparency for PNG and GIF
    if ($image_type == IMAGETYPE_PNG || $image_type == IMAGETYPE_GIF) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }
    
    // Resize
    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
    
    // Save image
    switch ($image_type) {
        case IMAGETYPE_JPEG:
            imagejpeg($new_image, $destination, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($new_image, $destination, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($new_image, $destination);
            break;
    }
    
    imagedestroy($image);
    imagedestroy($new_image);
    
    return true;
}

/**
 * Send email (basic function - can be extended with PHPMailer)
 */
function sendEmail($to, $subject, $message, $from = SITE_EMAIL) {
    $headers = "From: " . $from . "\r\n";
    $headers .= "Reply-To: " . $from . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Generate pagination HTML
 */
function generatePagination($current_page, $total_pages, $base_url) {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page - 1) . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=1">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . $total_pages . '">' . $total_pages . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page + 1) . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Generate breadcrumb
 */
function generateBreadcrumb($items) {
    $html = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
    
    $count = count($items);
    $i = 1;
    
    foreach ($items as $title => $url) {
        if ($i == $count) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . $title . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . $url . '">' . $title . '</a></li>';
        }
        $i++;
    }
    
    $html .= '</ol></nav>';
    
    return $html;
}

/**
 * Get category by ID
 */
function getCategoryById($id) {
    $db = Database::getInstance();
    return $db->fetchOne("SELECT * FROM categories WHERE id = ? AND status = 'active'", [$id]);
}

/**
 * Get category by slug
 */
function getCategoryBySlug($slug) {
    $db = Database::getInstance();
    return $db->fetchOne("SELECT * FROM categories WHERE slug = ? AND status = 'active'", [$slug]);
}

/**
 * Get all categories
 */
function getAllCategories($parent_id = null) {
    $db = Database::getInstance();
    if ($parent_id === null) {
        return $db->fetchAll("SELECT * FROM categories WHERE parent_id IS NULL AND status = 'active' ORDER BY order_id ASC");
    } else {
        return $db->fetchAll("SELECT * FROM categories WHERE parent_id = ? AND status = 'active' ORDER BY order_id ASC", [$parent_id]);
    }
}

/**
 * Get top marked categories
 */
function getTopCategories() {
    $db = Database::getInstance();
    return $db->fetchAll("SELECT * FROM categories WHERE is_top_marked = 1 AND parent_id IS NULL AND status = 'active' ORDER BY order_id ASC");
}

/**
 * Get subcategories by parent ID
 */
function getSubcategories($parent_id) {
    $db = Database::getInstance();
    return $db->fetchAll("SELECT * FROM categories WHERE parent_id = ? AND status = 'active' ORDER BY order_id ASC", [$parent_id]);
}

/**
 * Get article by ID
 */
function getArticleById($id) {
    $db = Database::getInstance();
    return $db->fetchOne("SELECT * FROM articles WHERE id = ? AND status = 'published'", [$id]);
}

/**
 * Get article by slug
 */
function getArticleBySlug($slug) {
    $db = Database::getInstance();
    return $db->fetchOne("SELECT * FROM articles WHERE slug = ? AND status = 'published'", [$slug]);
}

/**
 * Increment article views
 */
function incrementArticleViews($article_id) {
    trackView('article', $article_id);
}

/**
 * Track views for content (articles, videos, podcasts, reels, stories)
 * Prevents duplicate views from same device/IP within 24 hours
 */
function trackView($content_type, $content_id) {
    // Get user identifier (IP + User Agent hash for uniqueness)
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fingerprint = md5($ip_address . $user_agent);
    
    // Session-based quick check (prevents same page refresh)
    $session_key = "viewed_{$content_type}_{$content_id}";
    if (isset($_SESSION[$session_key])) {
        return; // Already viewed in this session
    }
    
    $db = Database::getInstance();
    
    // Check if view exists in last 24 hours
    $existing = $db->fetchOne("
        SELECT id FROM content_views 
        WHERE content_type = ? 
        AND content_id = ? 
        AND fingerprint = ? 
        AND viewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ", [$content_type, $content_id, $fingerprint]);
    
    if ($existing) {
        return; // Already viewed in last 24 hours
    }
    
    // Record new view
    try {
        $db->insert('content_views', [
            'content_type' => $content_type,
            'content_id' => $content_id,
            'ip_address' => $ip_address,
            'fingerprint' => $fingerprint,
            'user_agent' => substr($user_agent, 0, 255),
            'viewed_at' => date('Y-m-d H:i:s')
        ]);
        
        // Increment counter in main table
        $table_map = [
            'article' => 'articles',
            'video' => 'articles',
            'podcast' => 'podcasts',
            'reel' => 'reels',
            'story' => 'stories',
            'mobile_story' => 'mobile_stories'
        ];
        
        if (isset($table_map[$content_type])) {
            $db->query("UPDATE {$table_map[$content_type]} SET views_count = views_count + 1 WHERE id = ?", [$content_id]);
        }
        
        // Mark as viewed in session
        $_SESSION[$session_key] = true;
        
    } catch (Exception $e) {
        error_log("View tracking error: " . $e->getMessage());
    }
}

/**
 * Get site settings
 */
function getSiteSetting($key, $default = '') {
    $db = Database::getInstance();
    $result = $db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = ?", [$key]);
    return $result ? $result['setting_value'] : $default;
}

/**
 * Update site setting
 */
function updateSiteSetting($key, $value) {
    $db = Database::getInstance();
    $exists = $db->exists('site_settings', 'setting_key = ?', [$key]);
    
    if ($exists) {
        return $db->update('site_settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
    } else {
        return $db->insert('site_settings', ['setting_key' => $key, 'setting_value' => $value]);
    }
}

/**
 * Generate meta tags
 */
function generateMetaTags($title, $description = '', $keywords = '', $image = '', $url = '') {
    $site_name = getSiteSetting('site_name', SITE_NAME);
    $title = $title . ' - ' . $site_name;
    $description = $description ?: DEFAULT_META_DESCRIPTION;
    $keywords = $keywords ?: DEFAULT_META_KEYWORDS;
    $image = $image ?: DEFAULT_OG_IMAGE;
    $url = $url ?: currentURL();
    
    $html = '<title>' . htmlspecialchars($title) . '</title>' . "\n";
    $html .= '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
    $html .= '<meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . "\n";
    
    // Open Graph
    $html .= '<meta property="og:title" content="' . htmlspecialchars($title) . '">' . "\n";
    $html .= '<meta property="og:description" content="' . htmlspecialchars($description) . '">' . "\n";
    $html .= '<meta property="og:image" content="' . htmlspecialchars($image) . '">' . "\n";
    $html .= '<meta property="og:url" content="' . htmlspecialchars($url) . '">' . "\n";
    $html .= '<meta property="og:type" content="website">' . "\n";
    
    // Twitter Card
    $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    $html .= '<meta name="twitter:title" content="' . htmlspecialchars($title) . '">' . "\n";
    $html .= '<meta name="twitter:description" content="' . htmlspecialchars($description) . '">' . "\n";
    $html .= '<meta name="twitter:image" content="' . htmlspecialchars($image) . '">' . "\n";
    
    return $html;
}

/**
 * Check if user liked article
 */
function hasUserLikedArticle($user_id, $article_id) {
    $db = Database::getInstance();
    return $db->exists('user_article_likes', 'user_id = ? AND article_id = ?', [$user_id, $article_id]);
}

/**
 * Check if user saved article
 */
function hasUserSavedArticle($user_id, $article_id) {
    $db = Database::getInstance();
    return $db->exists('user_saved_articles', 'user_id = ? AND article_id = ?', [$user_id, $article_id]);
}

/**
 * Get related articles
 */
function getRelatedArticles($article_id, $category_id, $limit = 5) {
    $db = Database::getInstance();
    return $db->fetchAll(
        "SELECT * FROM articles 
         WHERE category_id = ? AND id != ? AND status = 'published' 
         ORDER BY published_at DESC LIMIT ?",
        [$category_id, $article_id, $limit]
    );
}

/**
 * Debug helper
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * Log activity
 */
function logActivity($message, $type = 'info') {
    $log_file = ROOT_PATH . '/logs/' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [{$type}] {$message}\n";
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}
?>
