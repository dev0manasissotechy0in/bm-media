<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

// Check categories
$categories = $db->fetchAll("SELECT COUNT(*) as cnt FROM categories");
echo "✅ Total Categories: " . $categories[0]['cnt'] . "\n\n";

// Check articles
$articles = $db->fetchAll("SELECT COUNT(*) as cnt FROM articles");
echo "✅ Total Articles: " . $articles[0]['cnt'] . "\n\n";

// Check article types distribution
echo "📊 Article Types Distribution:\n";
$types = $db->fetchAll("SELECT content_type, COUNT(*) as cnt FROM articles GROUP BY content_type ORDER BY cnt DESC");
foreach ($types as $type) {
    echo "   • {$type['content_type']}: {$type['cnt']} articles\n";
}

// Check featured/top articles
echo "\n🌟 Special Articles:\n";
$featured = $db->fetchAll("SELECT COUNT(*) as cnt FROM articles WHERE is_featured = 1");
echo "   • Featured: " . $featured[0]['cnt'] . "\n";
$top = $db->fetchAll("SELECT COUNT(*) as cnt FROM articles WHERE is_top_news = 1");
echo "   • Top News: " . $top[0]['cnt'] . "\n";
$live = $db->fetchAll("SELECT COUNT(*) as cnt FROM articles WHERE is_live = 1");
echo "   • Live: " . $live[0]['cnt'] . "\n";
$breaking = $db->fetchAll("SELECT COUNT(*) as cnt FROM articles WHERE is_breaking = 1");
echo "   • Breaking: " . $breaking[0]['cnt'] . "\n";

// Show latest articles
echo "\n📰 Latest Articles:\n";
$latest = $db->fetchAll("SELECT title, content_type, published_at FROM articles ORDER BY published_at DESC LIMIT 5");
foreach ($latest as $i => $article) {
    echo "   " . ($i + 1) . ". {$article['title']}\n";
    echo "      Type: {$article['content_type']} | Published: {$article['published_at']}\n";
}

echo "\n✅ Test data verification complete!\n";
?>
