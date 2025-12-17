<?php
/**
 * Generate Sample Articles for Each Category
 */

require_once 'config/config.php';

$db = Database::getInstance();

// Get all active categories
$categories = $db->fetchAll("SELECT id, name, slug FROM categories WHERE status = 'active' ORDER BY name");

if (empty($categories)) {
    die("No active categories found. Please add categories first.\n");
}

// Sample article data templates
$article_titles = [
    "Breaking: Major Development in %s Sector",
    "Latest Updates on %s: What You Need to Know",
    "Expert Analysis: The Future of %s",
    "Trending Now: %s Making Headlines Today",
    "In-Depth Report: Understanding %s Better",
    "Top 5 Things About %s You Should Know",
    "Exclusive: Inside Look at %s Industry",
    "Opinion: Why %s Matters More Than Ever",
    "Investigation: The Truth Behind %s",
    "Special Coverage: %s Weekly Roundup"
];

$content_templates = [
    "<p>This is a comprehensive article about %s that provides detailed insights into the current situation. Our expert team has conducted thorough research to bring you the most accurate and up-to-date information.</p>

<h2>Key Highlights</h2>
<p>The recent developments in %s have caught the attention of industry experts and enthusiasts alike. Multiple sources confirm that significant changes are on the horizon, and stakeholders should prepare accordingly.</p>

<h2>Expert Opinion</h2>
<p>Leading experts in the field suggest that %s will continue to evolve rapidly. The implications of these changes extend beyond immediate concerns and may reshape the entire landscape in the coming months.</p>

<h2>What This Means for You</h2>
<p>As a reader interested in %s, it's important to stay informed about these developments. Our team will continue to monitor the situation and provide timely updates as new information becomes available.</p>

<h2>Conclusion</h2>
<p>The current state of %s presents both challenges and opportunities. By staying informed and engaged, readers can better understand and navigate this dynamic environment.</p>",

    "<p>Welcome to our detailed coverage of %s. In this article, we explore the latest trends, analyze key data points, and provide actionable insights for our readers.</p>

<h2>Background</h2>
<p>Understanding %s requires looking at historical context and current market conditions. Over the past few months, we've observed significant shifts that warrant closer examination.</p>

<h2>Current Situation</h2>
<p>The %s sector is experiencing unprecedented activity. Industry leaders are making strategic moves, and new players are entering the market with innovative approaches.</p>

<h2>Analysis</h2>
<p>Our analysis of %s reveals several important factors at play. Economic conditions, technological advancements, and changing consumer preferences are all contributing to the current dynamics.</p>

<h2>Looking Ahead</h2>
<p>As we look to the future of %s, several key trends are emerging. Stakeholders should monitor these developments closely and adapt their strategies accordingly.</p>",

    "<p>This comprehensive guide to %s covers everything you need to know. Whether you're new to the topic or a seasoned follower, you'll find valuable information here.</p>

<h2>Introduction</h2>
<p>In today's fast-paced world, staying informed about %s is more important than ever. This article breaks down complex topics into easy-to-understand segments.</p>

<h2>Main Points</h2>
<p>When it comes to %s, several key factors stand out. Our research team has identified the most critical elements that readers should focus on for a complete understanding.</p>

<h2>Real-World Applications</h2>
<p>The practical implications of %s extend to multiple areas. From business decisions to personal choices, understanding these concepts can help readers make better-informed decisions.</p>

<h2>Expert Insights</h2>
<p>We've consulted with leading experts in %s to bring you their perspectives. Their insights provide valuable context and help readers grasp the bigger picture.</p>

<h2>Final Thoughts</h2>
<p>As developments in %s continue to unfold, staying informed remains crucial. We're committed to providing ongoing coverage and analysis of this important topic.</p>"
];

$descriptions = [
    "Comprehensive coverage of the latest developments in %s with expert analysis and insights.",
    "Stay updated with breaking news and trending stories about %s from reliable sources.",
    "In-depth reporting on %s with detailed analysis and real-world implications.",
    "Expert opinions and analysis on current trends in %s sector.",
    "Complete guide to understanding %s with actionable insights.",
    "Latest updates and breaking news in %s industry.",
    "Exclusive coverage of %s with expert commentary and analysis.",
    "Detailed investigation into %s with comprehensive reporting.",
    "Professional analysis of %s trends and future outlook.",
    "Essential information about %s you need to know today."
];

$tags = $db->fetchAll("SELECT id FROM tags ORDER BY RAND() LIMIT 20");
$tag_ids = array_column($tags, 'id');

$articles_created = 0;
$errors = [];

foreach ($categories as $category) {
    echo "Generating articles for category: {$category['name']}\n";
    
    for ($i = 0; $i < 10; $i++) {
        // Generate article data
        $title = sprintf($article_titles[$i % count($article_titles)], $category['name']);
        $slug = strtolower(str_replace([' ', ':', '?', ','], ['', '', '', ''], $title));
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug) . '-' . time() . '-' . rand(1000, 9999);
        
        $cat_name = $category['name'];
        $content = str_replace('%s', $cat_name, $content_templates[$i % count($content_templates)]);
        
        $description = sprintf($descriptions[$i % count($descriptions)], $category['name']);
        
        // Random flags
        $is_featured = rand(0, 4) == 0 ? 1 : 0; // 20% chance
        $is_top_news = rand(0, 4) == 0 ? 1 : 0;
        $is_breaking = rand(0, 9) == 0 ? 1 : 0; // 10% chance
        
        $content_types = ['standard', 'video', 'photo', 'gallery'];
        $content_type = $content_types[array_rand($content_types)];
        
        // Random published date within last 30 days
        $days_ago = rand(0, 30);
        $published_at = date('Y-m-d H:i:s', strtotime("-$days_ago days"));
        
        $article_data = [
            'title' => $title,
            'slug' => $slug,
            'description' => $description,
            'content' => $content,
            'category_id' => $category['id'],
            'content_type' => $content_type,
            'author_id' => 1, // Assuming admin user ID is 1
            'author_type' => 'admin',
            'is_featured' => $is_featured,
            'is_top_news' => $is_top_news,
            'is_breaking' => $is_breaking,
            'is_live' => 0,
            'status' => 'published',
            'views_count' => rand(100, 10000),
            'likes_count' => rand(10, 500),
            'comments_count' => rand(0, 50),
            'seo_title' => $title,
            'seo_description' => $description,
            'seo_keywords' => $category['name'] . ', news, updates',
            'published_at' => $published_at,
            'created_at' => $published_at,
            'updated_at' => $published_at
        ];
        
        try {
            $article_id = $db->insert('articles', $article_data);
            
            if ($article_id) {
                // Add random tags (2-4 tags per article)
                if (!empty($tag_ids)) {
                    $num_tags = rand(2, min(4, count($tag_ids)));
                    $selected_tags = array_rand(array_flip($tag_ids), $num_tags);
                    $selected_tags = is_array($selected_tags) ? $selected_tags : [$selected_tags];
                    
                    foreach ($selected_tags as $tag_id) {
                        $db->insert('article_tags', [
                            'article_id' => $article_id,
                            'tag_id' => $tag_id
                        ]);
                    }
                }
                
                $articles_created++;
                $article_num = $i + 1;
                echo "  ✓ Article $article_num/10 created (ID: $article_id)\n";
            } else {
                $article_num = $i + 1;
                $errors[] = "Failed to create article for {$category['name']} - Article $article_num";
                echo "  ✗ Failed to create article $article_num/10\n";
            }
        } catch (Exception $e) {
            $errors[] = "Error creating article for {$category['name']}: " . $e->getMessage();
            echo "  ✗ Error: " . $e->getMessage() . "\n";
        }
        
        // Small delay to ensure unique slugs
        usleep(100000); // 0.1 second
    }
    
    echo "\n";
}

echo "\n========================================\n";
echo "Summary:\n";
echo "========================================\n";
echo "Categories processed: " . count($categories) . "\n";
echo "Articles created: $articles_created\n";
echo "Success rate: " . round(($articles_created / (count($categories) * 10)) * 100, 2) . "%\n";

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n✓ Sample article generation complete!\n";
