<?php
/**
 * Add Sample Live Updates to Live Articles
 */

require_once 'config/config.php';

$db = Database::getInstance();

// First, mark some random articles as live
$articles = $db->fetchAll("SELECT id, title FROM articles WHERE status = 'published' ORDER BY RAND() LIMIT 5");

if (empty($articles)) {
    die("No published articles found.\n");
}

$live_updates_samples = [
    "Breaking: Major development just reported. Officials are investigating the situation.",
    "Update: New information has emerged regarding the ongoing situation.",
    "Sources confirm that negotiations are currently underway.",
    "ALERT: Emergency services have been dispatched to the location.",
    "Latest: Eyewitness reports suggest significant progress in the last hour.",
    "Developing: Authorities have issued an official statement moments ago.",
    "Just In: Additional details are now being revealed by officials.",
    "Confirmed: The situation is being closely monitored by experts.",
    "Update: Response teams are currently on the scene assessing the situation.",
    "Breaking: New developments indicate a shift in the current scenario.",
    "Flash: Critical information has just been received from reliable sources.",
    "Update: Stakeholders are meeting to discuss immediate action plans.",
    "Live: Press conference scheduled to address recent developments.",
    "Alert: Weather conditions/Situation has changed significantly in the past hour.",
    "Confirmed: Multiple agencies are now coordinating their response efforts."
];

$updates_created = 0;

foreach ($articles as $article) {
    echo "Setting up LIVE article: {$article['title']}\n";
    
    // Mark article as live
    $db->update('articles', ['is_live' => 1], 'id = ?', [$article['id']]);
    
    // Create timeline of updates (8-12 updates per article)
    $num_updates = rand(8, 12);
    $base_time = time();
    
    for ($i = 0; $i < $num_updates; $i++) {
        // Create updates going backwards in time (most recent first in display)
        $minutes_ago = $i * rand(5, 20); // 5-20 minutes between updates
        $update_time = date('Y-m-d H:i:s', $base_time - ($minutes_ago * 60));
        
        $update_text = $live_updates_samples[array_rand($live_updates_samples)];
        
        $update_data = [
            'article_id' => $article['id'],
            'update_text' => $update_text,
            'created_at' => $update_time
        ];
        
        try {
            $update_id = $db->insert('article_live_updates', $update_data);
            if ($update_id) {
                $updates_created++;
                echo "  ✓ Update " . ($i + 1) . "/$num_updates added (Time: $update_time)\n";
            }
        } catch (Exception $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
}

echo "\n========================================\n";
echo "Summary:\n";
echo "========================================\n";
echo "Live articles created: " . count($articles) . "\n";
echo "Total updates created: $updates_created\n";
echo "\n✓ Sample live updates generation complete!\n";
echo "\nYou can now view these articles to see the timeline:\n";
foreach ($articles as $article) {
    $article_slug = $db->fetchOne("SELECT slug FROM articles WHERE id = ?", [$article['id']]);
    if ($article_slug) {
        echo "  - " . BASE_URL . "/article/" . $article_slug['slug'] . "\n";
    }
}
