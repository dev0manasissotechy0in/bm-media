<?php
/**
 * Dynamic XML Sitemap Generator
 * Generates SEO-friendly sitemap for all content
 */

header('Content-Type: application/xml; charset=utf-8');

require_once 'config/config.php';

try {
    $db = Database::getInstance();
    
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

    // Homepage
    echo '<url>';
    echo '<loc>' . BASE_URL . '/</loc>';
    echo '<changefreq>daily</changefreq>';
    echo '<priority>1.0</priority>';
    echo '<lastmod>' . date('Y-m-d') . '</lastmod>';
    echo '</url>';

// Articles
$articles = $db->fetchAll("
    SELECT slug, updated_at, thumbnail, title 
    FROM articles 
    WHERE status = 'published' 
    ORDER BY updated_at DESC
") ?: [];

foreach ($articles as $article) {
    echo '<url>';
    echo '<loc>' . BASE_URL . '/article/' . htmlspecialchars($article['slug']) . '</loc>';
    echo '<lastmod>' . (!empty($article['updated_at']) ? date('Y-m-d', strtotime($article['updated_at'])) : date('Y-m-d')) . '</lastmod>';
    echo '<changefreq>weekly</changefreq>';
    echo '<priority>0.8</priority>';
    
    // Add image if exists
    if (!empty($article['thumbnail'])) {
        echo '<image:image>';
        echo '<image:loc>' . htmlspecialchars(UPLOADS_URL . '/articles/' . $article['thumbnail']) . '</image:loc>';
        echo '<image:title>' . htmlspecialchars($article['title']) . '</image:title>';
        echo '</image:image>';
    }
    
    echo '</url>';
}

// Categories
$categories = $db->fetchAll("
    SELECT slug, updated_at 
    FROM categories 
    WHERE status = 'active' AND parent_id IS NULL
") ?: [];

foreach ($categories as $category) {
    echo '<url>';
    echo '<loc>' . BASE_URL . '/' . htmlspecialchars($category['slug']) . '</loc>';
    echo '<lastmod>' . (!empty($category['updated_at']) ? date('Y-m-d', strtotime($category['updated_at'])) : date('Y-m-d')) . '</lastmod>';
    echo '<changefreq>daily</changefreq>';
    echo '<priority>0.7</priority>';
    echo '</url>';
}

// Sub-categories
$subcategories = $db->fetchAll("
    SELECT c.slug as sub_slug, p.slug as parent_slug, c.updated_at 
    FROM categories c 
    JOIN categories p ON c.parent_id = p.id 
    WHERE c.status = 'active' AND c.parent_id IS NOT NULL
") ?: [];

foreach ($subcategories as $subcat) {
    echo '<url>';
    echo '<loc>' . BASE_URL . '/' . htmlspecialchars($subcat['parent_slug']) . '/' . htmlspecialchars($subcat['sub_slug']) . '</loc>';
    echo '<lastmod>' . (!empty($subcat['updated_at']) ? date('Y-m-d', strtotime($subcat['updated_at'])) : date('Y-m-d')) . '</lastmod>';
    echo '<changefreq>daily</changefreq>';
    echo '<priority>0.6</priority>';
    echo '</url>';
}

// Tags
$tags = $db->fetchAll("
    SELECT slug 
    FROM tags 
    ORDER BY created_at DESC
") ?: [];

foreach ($tags as $tag) {
    echo '<url>';
    echo '<loc>' . BASE_URL . '/tag/' . htmlspecialchars($tag['slug']) . '</loc>';
    echo '<changefreq>weekly</changefreq>';
    echo '<priority>0.5</priority>';
    echo '</url>';
}

// Custom Pages
$custom_pages = $db->fetchAll("
    SELECT slug, updated_at 
    FROM custom_pages 
    WHERE status = 'published'
") ?: [];

foreach ($custom_pages as $page) {
    echo '<url>';
    echo '<loc>' . BASE_URL . '/' . htmlspecialchars($page['slug']) . '</loc>';
    echo '<lastmod>' . (!empty($page['updated_at']) ? date('Y-m-d', strtotime($page['updated_at'])) : date('Y-m-d')) . '</lastmod>';
    echo '<changefreq>monthly</changefreq>';
    echo '<priority>0.5</priority>';
    echo '</url>';
}

// Special Dashboards
$dashboards = ['election-dashboard', 'cricket-dashboard', 'market-dashboard'];
foreach ($dashboards as $dashboard) {
    echo '<url>';
    echo '<loc>' . BASE_URL . '/' . $dashboard . '</loc>';
    echo '<changefreq>hourly</changefreq>';
    echo '<priority>0.9</priority>';
    echo '<lastmod>' . date('Y-m-d') . '</lastmod>';
    echo '</url>';
}

// Reels
$reels = $db->fetchAll("
    SELECT id, created_at 
    FROM reels 
    WHERE status = 'active' 
    ORDER BY created_at DESC LIMIT 100
") ?: [];

foreach ($reels as $reel) {
    echo '<url>';
    echo '<loc>' . BASE_URL . '/reel/' . $reel['id'] . '</loc>';
    echo '<lastmod>' . date('Y-m-d', strtotime($reel['created_at'])) . '</lastmod>';
    echo '<changefreq>weekly</changefreq>';
    echo '<priority>0.6</priority>';
    echo '</url>';
}

// Podcasts
$podcasts = $db->fetchAll("
    SELECT slug, created_at 
    FROM podcasts 
    WHERE status = 'published' 
    ORDER BY created_at DESC LIMIT 100
") ?: [];

foreach ($podcasts as $podcast) {
    echo '<url>';
    echo '<loc>' . BASE_URL . '/podcast/' . htmlspecialchars($podcast['slug']) . '</loc>';
    echo '<lastmod>' . date('Y-m-d', strtotime($podcast['created_at'])) . '</lastmod>';
    echo '<changefreq>weekly</changefreq>';
    echo '<priority>0.6</priority>';
    echo '</url>';
}

// Mobile Stories
$mobile_stories = $db->fetchAll("
    SELECT id, created_at 
    FROM mobile_stories 
    WHERE status = 'active' 
    ORDER BY created_at DESC LIMIT 100
") ?: [];

foreach ($mobile_stories as $story) {
    echo '<url>';
    echo '<loc>' . BASE_URL . '/mobile-story.php?id=' . $story['id'] . '</loc>';
    echo '<lastmod>' . date('Y-m-d', strtotime($story['created_at'])) . '</lastmod>';
    echo '<changefreq>weekly</changefreq>';
    echo '<priority>0.5</priority>';
    echo '</url>';
}

// Static Pages
$static_pages = [
    'search' => 0.5,
    'articles' => 0.7,
    'categories' => 0.7,
    'podcasts' => 0.6,
    'reels' => 0.6,
    'mobile-stories' => 0.5,
];

foreach ($static_pages as $page => $priority) {
    echo '<url>';
    echo '<loc>' . BASE_URL . '/' . $page . '</loc>';
    echo '<changefreq>daily</changefreq>';
    echo '<priority>' . $priority . '</priority>';
    echo '<lastmod>' . date('Y-m-d') . '</lastmod>';
    echo '</url>';
}

echo '</urlset>';

} catch (Exception $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Error generating sitemap: " . htmlspecialchars($e->getMessage());
}
