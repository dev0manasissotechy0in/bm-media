<?php
/**
 * RSS Feed Generator
 */

require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

// Get site settings
$site_name = 'News Website';
$site_description = 'Latest news and updates';
$settings = $db->fetchAll("SELECT * FROM settings WHERE setting_key IN ('site_name', 'site_description')");
foreach ($settings as $setting) {
    if ($setting['setting_key'] === 'site_name') $site_name = $setting['setting_value'];
    if ($setting['setting_key'] === 'site_description') $site_description = $setting['setting_value'];
}

// Get latest 50 articles
$articles = $db->fetchAll(
    "SELECT a.*, c.name as category_name, c.slug as category_slug,
     CASE 
         WHEN a.author_type = 'admin' THEN ad.full_name
         WHEN a.author_type = 'reporter' THEN r.full_name
         ELSE 'Unknown'
     END as author_name
     FROM articles a
     LEFT JOIN categories c ON a.category_id = c.id
     LEFT JOIN admin_users ad ON a.author_id = ad.id AND a.author_type = 'admin'
     LEFT JOIN reporters r ON a.author_id = r.id AND a.author_type = 'reporter'
     WHERE a.status = 'published'
     ORDER BY a.published_at DESC
     LIMIT 50"
);

// Set RSS header
header('Content-Type: application/rss+xml; charset=utf-8');

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title><?= htmlspecialchars($site_name) ?></title>
        <link><?= BASE_URL ?></link>
        <description><?= htmlspecialchars($site_description) ?></description>
        <language>en-us</language>
        <lastBuildDate><?= date('r') ?></lastBuildDate>
        <atom:link href="<?= BASE_URL ?>/rss.php" rel="self" type="application/rss+xml" />
        
        <?php foreach ($articles as $article): ?>
        <item>
            <title><?= htmlspecialchars($article['title']) ?></title>
            <link><?= BASE_URL ?>/article/<?= htmlspecialchars($article['slug']) ?></link>
            <description><![CDATA[<?= htmlspecialchars(substr(strip_tags($article['content']), 0, 300)) ?>...]]></description>
            <pubDate><?= date('r', strtotime($article['published_at'] ?: $article['created_at'])) ?></pubDate>
            <guid isPermaLink="true"><?= BASE_URL ?>/article/<?= htmlspecialchars($article['slug']) ?></guid>
            <?php if ($article['category_name']): ?>
            <category><?= htmlspecialchars($article['category_name']) ?></category>
            <?php endif; ?>
            <?php if ($article['author_name']): ?>
            <dc:creator><?= htmlspecialchars($article['author_name']) ?></dc:creator>
            <?php endif; ?>
            <?php if ($article['featured_image']): ?>
            <enclosure url="<?= BASE_URL ?>/uploads/articles/<?= htmlspecialchars($article['featured_image']) ?>" type="image/jpeg" />
            <?php endif; ?>
        </item>
        <?php endforeach; ?>
    </channel>
</rss>