<?php
/**
 * Ads Management Helper Functions
 * Handle dynamic ad display based on settings
 */

/**
 * Get active ad for a specific position
 */
function getAd($position, $page_type = 'general') {
    $db = Database::getInstance();
    
    // Check if custom ads are enabled for this position
    $settings = $db->fetchOne("
        SELECT * FROM ad_settings 
        WHERE position = ? AND status = 'active'
    ", [$position]);
    
    if (!$settings) {
        return null;
    }
    
    // If custom ads are enabled, show custom ad (hide Google Ads)
    if ($settings['ad_type'] === 'custom') {
        $ad = $db->fetchOne("
            SELECT * FROM ads 
            WHERE position = ? 
            AND status = 'active' 
            AND (page_type = ? OR page_type = 'all')
            AND (start_date IS NULL OR start_date <= CURDATE())
            AND (end_date IS NULL OR end_date >= CURDATE())
            ORDER BY priority DESC, RAND()
            LIMIT 1
        ", [$position, $page_type]);
        
        if ($ad) {
            // Track impression
            trackAdImpression($ad['id']);
            return $ad;
        }
    }
    
    // If Google Ads are enabled
    if ($settings['ad_type'] === 'google' && !empty($settings['ad_code'])) {
        return [
            'type' => 'google',
            'code' => $settings['ad_code']
        ];
    }
    
    // If Taboola Feed is enabled
    if ($settings['ad_type'] === 'taboola' && !empty($settings['ad_code'])) {
        return [
            'type' => 'taboola',
            'code' => $settings['ad_code']
        ];
    }
    
    // If Government Ads are enabled
    if ($settings['ad_type'] === 'government' && !empty($settings['ad_code'])) {
        return [
            'type' => 'government',
            'code' => $settings['ad_code']
        ];
    }
    
    return null;
}

/**
 * Display ad HTML
 */
function displayAd($position, $page_type = 'general') {
    $ad = getAd($position, $page_type);
    
    if (!$ad) {
        return '';
    }
    
    $html = '<div class="ad-container ad-' . htmlspecialchars($position) . '" data-position="' . htmlspecialchars($position) . '">';
    
    if (isset($ad['type'])) {
        // Google, Taboola, or Government Ads
        $html .= '<div class="ad-label">Advertisement</div>';
        $html .= $ad['code'];
    } else {
        // Custom Ad
        $html .= '<div class="ad-label">Advertisement</div>';
        
        if ($ad['link_url']) {
            $html .= '<a href="' . htmlspecialchars($ad['link_url']) . '" target="_blank" class="ad-link" data-ad-id="' . $ad['id'] . '" onclick="trackAdClick(' . $ad['id'] . ')">';
        }
        
        if ($ad['ad_format'] === 'image') {
            $html .= '<img src="' . UPLOADS_URL . '/ads/' . htmlspecialchars($ad['image_path']) . '" alt="' . htmlspecialchars($ad['title']) . '" class="img-fluid">';
        } elseif ($ad['ad_format'] === 'html') {
            $html .= $ad['html_content'];
        } elseif ($ad['ad_format'] === 'video') {
            $html .= '<video controls class="w-100"><source src="' . UPLOADS_URL . '/ads/' . htmlspecialchars($ad['video_path']) . '" type="video/mp4"></video>';
        }
        
        if ($ad['link_url']) {
            $html .= '</a>';
        }
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Track ad impression
 */
function trackAdImpression($ad_id) {
    $db = Database::getInstance();
    $db->execute("
        INSERT INTO ad_impressions (ad_id, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, NOW())
    ", [$ad_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
    
    // Update ad statistics
    $db->execute("UPDATE ads SET impressions = impressions + 1 WHERE id = ?", [$ad_id]);
}

/**
 * Track ad click (called via AJAX)
 */
function trackAdClick($ad_id) {
    $db = Database::getInstance();
    $db->execute("
        INSERT INTO ad_clicks (ad_id, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, NOW())
    ", [$ad_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'] ?? '']);
    
    // Update ad statistics
    $db->execute("UPDATE ads SET clicks = clicks + 1 WHERE id = ?", [$ad_id]);
}

/**
 * Get ad performance statistics
 */
function getAdStats($ad_id, $days = 30) {
    $db = Database::getInstance();
    
    $stats = $db->fetchOne("
        SELECT 
            COUNT(DISTINCT ai.id) as impressions,
            COUNT(DISTINCT ac.id) as clicks,
            (COUNT(DISTINCT ac.id) / NULLIF(COUNT(DISTINCT ai.id), 0) * 100) as ctr
        FROM ads a
        LEFT JOIN ad_impressions ai ON a.id = ai.ad_id 
            AND ai.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        LEFT JOIN ad_clicks ac ON a.id = ac.ad_id 
            AND ac.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        WHERE a.id = ?
    ", [$days, $days, $ad_id]);
    
    return $stats;
}

/**
 * Common ad positions
 */
function getAdPositions() {
    return [
        'header_banner' => 'Header Banner (728x90)',
        'sidebar_top' => 'Sidebar Top (300x250)',
        'sidebar_middle' => 'Sidebar Middle (300x250)',
        'sidebar_bottom' => 'Sidebar Bottom (300x600)',
        'article_top' => 'Article Top (728x90)',
        'article_middle' => 'Article Middle (336x280)',
        'article_bottom' => 'Article Bottom (728x90)',
        'footer_banner' => 'Footer Banner (728x90)',
        'popup' => 'Popup/Overlay',
        'sticky_bottom' => 'Sticky Bottom Banner',
        'in_feed' => 'In-Feed Native Ad'
    ];
}
