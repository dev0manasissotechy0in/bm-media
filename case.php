<?php
/**
 * Case Detail Page - Single Case Thread View
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Functions.php';
require_once 'includes/Session.php';

$db = Database::getInstance();

// Get case slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: case-threads');
    exit;
}

// Fetch case details
$case = $db->fetchOne("
    SELECT * FROM case_threads WHERE slug = ?
", [$slug]);

// If not found by slug, try by ID (fallback for legacy URLs)
if (!$case) {
    $case = $db->fetchOne("
        SELECT * FROM case_threads WHERE id = ?
    ", [$slug]);
}

if (!$case) {
    // Log the attempted slug for debugging
    error_log("Case not found with slug: " . $slug);
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Check if user is following this case (requires login)
$case['is_following'] = false;
if (Session::has('user_id')) {
    $userId = Session::get('user_id');
    $followCheck = $db->fetchOne("SELECT id FROM case_follows WHERE user_id = ? AND case_id = ?", [$userId, $case['id']]);
    $case['is_following'] = !empty($followCheck);
}

// Get linked articles count
$linkedCount = $db->fetchOne("SELECT COUNT(*) as count FROM case_article_map WHERE case_id = ?", [$case['id']]);
$case['linked_articles_count'] = $linkedCount['count'] ?? 0;

// Increment views
$db->query("UPDATE case_threads SET total_views = total_views + 1 WHERE id = ?", [$case['id']]);

// Fetch timeline events with count
$timelineEvents = $db->fetchAll("
    SELECT * FROM case_timeline_events 
    WHERE case_id = ? 
    ORDER BY event_date DESC, event_time DESC
    LIMIT 20
", [$case['id']]);

// Add linked_articles_count to each event
foreach ($timelineEvents as &$event) {
    if (!empty($event['linked_article_ids'])) {
        $linkedIds = json_decode($event['linked_article_ids'], true);
        $event['linked_articles_count'] = is_array($linkedIds) ? count($linkedIds) : 0;
    } else {
        $event['linked_articles_count'] = 0;
    }
}
unset($event); // Break reference

$timelineCount = $db->fetchOne("
    SELECT COUNT(*) as total FROM case_timeline_events WHERE case_id = ?
", [$case['id']]);

$timeline = [
    'events' => $timelineEvents,
    'total' => $timelineCount['total'] ?? 0
];

// Fetch articles with count
$articles = $db->fetchAll("
    SELECT 
        a.id, a.title, a.slug, a.thumbnail, a.published_at, a.views_count,
        cam.is_key_article,
        c.name as category_name,
        au.id as author_id, au.full_name as author_name
    FROM case_article_map cam
    JOIN articles a ON cam.article_id = a.id
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN authors au ON a.author_id = au.id
    WHERE cam.case_id = ? AND a.status = 'published'
    ORDER BY cam.is_key_article DESC, a.published_at DESC
    LIMIT 10
", [$case['id']]);

// Structure author data for each article
foreach ($articles as &$article) {
    $article['author'] = [
        'id' => $article['author_id'] ?? null,
        'name' => $article['author_name'] ?? 'Unknown'
    ];
    unset($article['author_id'], $article['author_name']);
}
unset($article);

$articlesCount = $db->fetchOne("
    SELECT COUNT(*) as total FROM case_article_map cam
    JOIN articles a ON cam.article_id = a.id
    WHERE cam.case_id = ? AND a.status = 'published'
", [$case['id']]);

$recentArticles = [
    'articles' => $articles,
    'total' => $articlesCount['total'] ?? 0
];

// Fetch documents with count
$documents = $db->fetchAll("
    SELECT * FROM case_documents
    WHERE case_id = ?
    ORDER BY document_date DESC
    LIMIT 10
", [$case['id']]);

$documentsCount = $db->fetchOne("
    SELECT COUNT(*) as total FROM case_documents WHERE case_id = ?
", [$case['id']]);

$documentsData = [
    'documents' => $documents,
    'total' => $documentsCount['total'] ?? 0
];

// Fetch media with count and types
$media = $db->fetchAll("
    SELECT * FROM case_media
    WHERE case_id = ?
    ORDER BY is_featured DESC, media_date DESC
    LIMIT 20
", [$case['id']]);

$mediaStats = $db->fetchOne("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN media_type = 'photo' THEN 1 ELSE 0 END) as photos,
        SUM(CASE WHEN media_type = 'video' THEN 1 ELSE 0 END) as videos,
        SUM(CASE WHEN media_type = 'audio' THEN 1 ELSE 0 END) as audio
    FROM case_media WHERE case_id = ?
", [$case['id']]);

$mediaData = [
    'items' => $media,
    'total' => $mediaStats['total'] ?? 0,
    'photos' => $mediaStats['photos'] ?? 0,
    'videos' => $mediaStats['videos'] ?? 0,
    'audio' => $mediaStats['audio'] ?? 0
];

// Reviews (placeholder for now)
$reviews = [];

// Page meta
$page_title = $case['meta_title'] ?: $case['title'];
$page_description = $case['meta_description'] ?: $case['short_description'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/case-detail.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Use the existing detail.php design from views/cases/detail.php -->
    <?php include 'views/cases/detail.php'; ?>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function followCase() {
            // TODO: Implement follow functionality
            alert('Please login to follow this case');
        }
        
        function openMedia(url) {
            window.open(url, '_blank');
        }
    </script>
</body>
</html>
