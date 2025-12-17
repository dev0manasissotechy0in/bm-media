<?php
/**
 * XML Sitemap Generator
 */

require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

// Set XML header
header('Content-Type: application/xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Homepage -->
    <url>
        <loc><?= BASE_URL ?></loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    
    <!-- Static Pages -->
    <?php
    $static_pages = [
        '' => ['freq' => 'daily', 'priority' => '1.0'],
        'contact.php' => ['freq' => 'monthly', 'priority' => '0.7'],
        'reporter-form.php' => ['freq' => 'monthly', 'priority' => '0.6'],
        'stories.php' => ['freq' => 'weekly', 'priority' => '0.8'],
        'reels.php' => ['freq' => 'weekly', 'priority' => '0.8'],
    ];
    
    foreach ($static_pages as $page => $meta):
        if ($page === '') continue; // Already added homepage
    ?>
    <url>
        <loc><?= BASE_URL ?>/<?= $page ?></loc>
        <changefreq><?= $meta['freq'] ?></changefreq>
        <priority><?= $meta['priority'] ?></priority>
    </url>
    <?php endforeach; ?>
    
    <!-- Custom Pages -->
    <?php
    $custom_pages = $db->fetchAll(
        "SELECT slug, updated_at FROM custom_pages WHERE status = 'published' ORDER BY id"
    );
    
    foreach ($custom_pages as $page):
    ?>
    <url>
        <loc><?= BASE_URL ?>/page/<?= htmlspecialchars($page['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($page['updated_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>
    
    <!-- Categories -->
    <?php
    $categories = $db->fetchAll(
        "SELECT slug, updated_at FROM categories WHERE status = 'active' ORDER BY name"
    );
    
    foreach ($categories as $category):
    ?>
    <url>
        <loc><?= BASE_URL ?>/category/<?= htmlspecialchars($category['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($category['updated_at'])) ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>
    
    <!-- Articles -->
    <?php
    $articles = $db->fetchAll(
        "SELECT slug, updated_at FROM articles 
         WHERE status = 'published' 
         ORDER BY published_at DESC 
         LIMIT 1000"
    );
    
    foreach ($articles as $article):
    ?>
    <url>
        <loc><?= BASE_URL ?>/article/<?= htmlspecialchars($article['slug']) ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($article['updated_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <?php endforeach; ?>
    
    <!-- Tags -->
    <?php
    $tags = $db->fetchAll("SELECT slug FROM tags ORDER BY name LIMIT 100");
    
    foreach ($tags as $tag):
    ?>
    <url>
        <loc><?= BASE_URL ?>/tag/<?= htmlspecialchars($tag['slug']) ?></loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>
</urlset>