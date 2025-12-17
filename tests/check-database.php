<?php
/**
 * Database Check - Verify tables and data
 */

require_once 'config/config.php';

$db = Database::getInstance();

echo "<h2>Database Check</h2>";

// Check articles table
echo "<h3>Articles Table:</h3>";
try {
    $article_count = $db->count('articles', '', []);
    echo "✓ Articles table exists<br>";
    echo "Total articles: <strong>$article_count</strong><br>";
    
    if ($article_count > 0) {
        $articles = $db->fetchAll("SELECT id, title, status, category_id FROM articles LIMIT 5");
        echo "<pre>";
        print_r($articles);
        echo "</pre>";
    } else {
        echo "<span style='color: red;'>⚠️ No articles found in database!</span><br>";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Check categories table
echo "<h3>Categories Table:</h3>";
try {
    $category_count = $db->count('categories', '', []);
    echo "✓ Categories table exists<br>";
    echo "Total categories: <strong>$category_count</strong><br>";
    
    if ($category_count > 0) {
        $categories = $db->fetchAll("SELECT id, name, slug, status FROM categories LIMIT 10");
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Slug</th><th>Status</th></tr>";
        foreach ($categories as $cat) {
            echo "<tr>";
            echo "<td>{$cat['id']}</td>";
            echo "<td>{$cat['name']}</td>";
            echo "<td>{$cat['slug']}</td>";
            echo "<td>{$cat['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<span style='color: red;'>⚠️ No categories found in database!</span><br>";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Check published articles per category
echo "<h3>Published Articles by Category:</h3>";
try {
    $cat_articles = $db->fetchAll("
        SELECT c.id, c.name, c.slug, COUNT(a.id) as article_count
        FROM categories c
        LEFT JOIN articles a ON c.id = a.category_id AND a.status = 'published'
        GROUP BY c.id
        ORDER BY c.name
    ");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Category</th><th>Slug</th><th>Published Articles</th></tr>";
    foreach ($cat_articles as $cat) {
        $color = $cat['article_count'] > 0 ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$cat['name']}</td>";
        echo "<td>{$cat['slug']}</td>";
        echo "<td style='color: $color;'><strong>{$cat['article_count']}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Solution:</h3>";
echo "<p>If you see 0 articles, you need to:</p>";
echo "<ol>";
echo "<li>Go to admin panel: <a href='/admin/' target='_blank'>http://localhost/admin/</a></li>";
echo "<li>Navigate to 'Articles' section</li>";
echo "<li>Add some sample articles</li>";
echo "<li>Make sure to set status as 'Published'</li>";
echo "</ol>";
?>
