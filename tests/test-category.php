<?php
$data = json_decode(file_get_contents('http://localhost/api/articles/by-category.php?category_id=5'), true);
if ($data && $data['success'] && count($data['articles']) > 0) {
    $article = $data['articles'][0];
    echo "Category API Test:\n";
    echo "Article: " . $article['title'] . "\n";
    echo "Has is_live: " . (isset($article['is_live']) ? 'YES' : 'NO') . "\n";
    echo "Has timeline_updates: " . (isset($article['timeline_updates']) ? 'YES' : 'NO') . "\n";
    if (isset($article['timeline_updates'])) {
        echo "Timeline updates count: " . count($article['timeline_updates']) . "\n";
    }
} else {
    echo "No articles found\n";
}
