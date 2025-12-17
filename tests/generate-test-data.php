<?php
/**
 * Test Data Generator
 * Adds 10 categories and 10 random articles of different types for testing
 */

require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

// Sample data
$category_names = [
    'Technology',
    'Business',
    'Health & Wellness',
    'Entertainment',
    'Sports',
    'Politics',
    'Science',
    'Travel',
    'Food & Lifestyle',
    'Education'
];

$article_titles = [
    'Breaking News: Major Tech Breakthrough Announced',
    'Market Analysis: What Investors Should Know',
    'New Health Study Reveals Surprising Findings',
    'Celebrity Spotlight: Inside Their Latest Project',
    'Championship Finals: Historic Victory for Underdogs',
    'Government Policy Changes Impact Economy',
    'Scientists Discover New Species in Amazon',
    'Top 10 Destinations to Visit This Year',
    'Recipe Guide: How to Cook Like a Chef',
    'Education Reform Bill Passes Congress'
];

$article_descriptions = [
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
    'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
    'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
    'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
    'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas volutpat arcu.',
    'Donec ut ex ac nulla pellentesque mollis in a enim. Cras non velit nec ligula pellentesque elementum.',
    'Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae donec sed orci.',
    'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.',
    'Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores.',
    'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum.'
];

$content_types = ['standard', 'video', 'photo', 'gallery', 'reel'];

echo "=== Test Data Generator ===\n\n";

// Step 1: Create Categories
echo "Creating 10 categories...\n";
$category_ids = [];

foreach ($category_names as $name) {
    $slug = strtolower(str_replace(' & ', '-', str_replace(' ', '-', $name)));
    
    try {
        $existing = $db->fetchOne("SELECT id FROM categories WHERE slug = ?", [$slug]);
        
        if ($existing) {
            $category_ids[] = $existing['id'];
            echo "  ✓ Category already exists: $name (ID: {$existing['id']})\n";
        } else {
            $db->insert('categories', [
                'name' => $name,
                'slug' => $slug,
                'description' => "Category for $name articles"
            ]);
            
            $new_cat = $db->fetchOne("SELECT id FROM categories WHERE slug = ?", [$slug]);
            $category_ids[] = $new_cat['id'];
            echo "  ✓ Created category: $name (ID: {$new_cat['id']})\n";
        }
    } catch (Exception $e) {
        echo "  ✗ Error creating category: $name - {$e->getMessage()}\n";
    }
}

echo "\n";

// Step 2: Create Articles
echo "Creating 10 random articles...\n";

// Get admin user ID
$admin = $db->fetchOne("SELECT id FROM admin_users LIMIT 1");
$admin_id = $admin ? $admin['id'] : 1;

for ($i = 1; $i <= 10; $i++) {
    try {
        // Pick random values
        $title = $article_titles[$i - 1];
        $description = $article_descriptions[$i - 1];
        $slug = strtolower(str_replace(' ', '-', $title) . '-' . uniqid());
        $category_id = $category_ids[array_rand($category_ids)];
        $content_type = $content_types[array_rand($content_types)];
        
        // Random flags
        $is_featured = rand(0, 1);
        $is_top_news = rand(0, 1);
        $is_live = rand(0, 1);
        $is_breaking = rand(0, 1);
        
        // Simulate published_at from 1-30 days ago
        $days_ago = rand(1, 30);
        $published_at = date('Y-m-d H:i:s', strtotime("-$days_ago days"));
        
        // Build insert query based on content type
        $insert_data = [
            'title' => $title,
            'slug' => $slug,
            'description' => $description,
            'content' => $description . "\n\nThis is additional content for the article.",
            'category_id' => $category_id,
            'author_id' => $admin_id,
            'author_type' => 'admin',
            'content_type' => $content_type,
            'is_featured' => $is_featured,
            'is_top_news' => $is_top_news,
            'is_live' => $is_live,
            'is_breaking' => $is_breaking,
            'status' => 'published',
            'published_at' => $published_at,
            'views_count' => rand(10, 5000),
            'likes_count' => rand(0, 500),
            'comments_count' => rand(0, 100),
            'thumbnail' => 'placeholder.jpg', // You should upload real images
            'seo_title' => $title,
            'seo_description' => substr($description, 0, 160),
            'seo_keywords' => implode(',', array_slice(explode(' ', $title), 0, 5))
        ];
        
        // Add type-specific fields
        if ($content_type === 'video') {
            $insert_data['media_video_url'] = 'https://www.youtube.com/embed/dQw4w9WgXcQ';
        } elseif ($content_type === 'reel') {
            $insert_data['media_reel_url'] = 'https://www.youtube.com/embed/dQw4w9WgXcQ';
        } elseif ($content_type === 'gallery') {
            $insert_data['media_gallery_images'] = json_encode(['placeholder.jpg']);
        }
        
        // Insert article
        $db->insert('articles', $insert_data);
        
        $article = $db->fetchOne("SELECT id FROM articles WHERE slug = ?", [$slug]);
        
        // Add random tags
        $all_tags = $db->fetchAll("SELECT id FROM tags LIMIT 5");
        if (!empty($all_tags)) {
            $num_tags = rand(1, min(3, count($all_tags)));
            $selected_tags = array_rand($all_tags, $num_tags);
            if (!is_array($selected_tags)) {
                $selected_tags = [$selected_tags];
            }
            
            foreach ($selected_tags as $tag_idx) {
                $db->insert('article_tags', [
                    'article_id' => $article['id'],
                    'tag_id' => $all_tags[$tag_idx]['id']
                ]);
            }
        }
        
        $features = [];
        if ($is_featured) $features[] = 'Featured';
        if ($is_top_news) $features[] = 'Top News';
        if ($is_live) $features[] = 'Live';
        if ($is_breaking) $features[] = 'Breaking';
        
        $features_str = !empty($features) ? ' [' . implode(', ', $features) . ']' : '';
        
        echo "  ✓ Article $i: $title$features_str\n";
        echo "     Type: $content_type | Published: $published_at | Views: {$insert_data['views_count']}\n";
        
    } catch (Exception $e) {
        echo "  ✗ Error creating article $i: {$e->getMessage()}\n";
    }
}

echo "\n✅ Test data generation completed!\n";
echo "\nYou can now visit your website to see the new categories and articles.\n";
echo "Go to: " . BASE_URL . "\n";
?>
