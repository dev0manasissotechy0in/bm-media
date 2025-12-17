<?php
/**
 * SEO Helper Functions
 * Dynamic meta tags generation
 */

/**
 * Generate meta tags for a page
 */
function generateMetaTags($data) {
    $defaults = [
        'title' => SITE_NAME,
        'description' => 'Latest news and updates',
        'keywords' => 'news, updates, breaking news',
        'image' => BASE_URL . '/assets/images/default-og.jpg',
        'url' => BASE_URL . $_SERVER['REQUEST_URI'],
        'type' => 'website',
        'author' => SITE_NAME,
        'published_time' => null,
        'modified_time' => null
    ];
    
    $meta = array_merge($defaults, $data);
    
    // Clean and truncate
    $meta['title'] = htmlspecialchars(substr($meta['title'], 0, 60));
    $meta['description'] = htmlspecialchars(substr(strip_tags($meta['description']), 0, 160));
    
    $output = '';
    
    // Basic meta tags
    $output .= '<meta name="description" content="' . $meta['description'] . '">' . "\n";
    $output .= '<meta name="keywords" content="' . htmlspecialchars($meta['keywords']) . '">' . "\n";
    $output .= '<meta name="author" content="' . htmlspecialchars($meta['author']) . '">' . "\n";
    
    // Open Graph tags
    $output .= '<meta property="og:title" content="' . $meta['title'] . '">' . "\n";
    $output .= '<meta property="og:description" content="' . $meta['description'] . '">' . "\n";
    $output .= '<meta property="og:image" content="' . $meta['image'] . '">' . "\n";
    $output .= '<meta property="og:url" content="' . $meta['url'] . '">' . "\n";
    $output .= '<meta property="og:type" content="' . $meta['type'] . '">' . "\n";
    $output .= '<meta property="og:site_name" content="' . SITE_NAME . '">' . "\n";
    
    // Twitter Card tags
    $output .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    $output .= '<meta name="twitter:title" content="' . $meta['title'] . '">' . "\n";
    $output .= '<meta name="twitter:description" content="' . $meta['description'] . '">' . "\n";
    $output .= '<meta name="twitter:image" content="' . $meta['image'] . '">' . "\n";
    
    // Article specific tags
    if ($meta['type'] === 'article') {
        if ($meta['published_time']) {
            $output .= '<meta property="article:published_time" content="' . date('c', strtotime($meta['published_time'])) . '">' . "\n";
        }
        if ($meta['modified_time']) {
            $output .= '<meta property="article:modified_time" content="' . date('c', strtotime($meta['modified_time'])) . '">' . "\n";
        }
    }
    
    // Canonical URL
    $output .= '<link rel="canonical" href="' . $meta['url'] . '">' . "\n";
    
    return $output;
}

/**
 * Generate JSON-LD structured data
 */
function generateStructuredData($type, $data) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => $type
    ];
    
    if ($type === 'NewsArticle') {
        $schema = array_merge($schema, [
            'headline' => $data['title'],
            'description' => strip_tags($data['description'] ?? ''),
            'image' => $data['image'] ?? '',
            'datePublished' => date('c', strtotime($data['published_at'])),
            'dateModified' => date('c', strtotime($data['updated_at'])),
            'author' => [
                '@type' => 'Person',
                'name' => $data['author_name'] ?? SITE_NAME
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => SITE_NAME,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => BASE_URL . '/assets/images/logo.png'
                ]
            ]
        ]);
    } elseif ($type === 'Organization') {
        $schema = array_merge($schema, [
            'name' => SITE_NAME,
            'url' => BASE_URL,
            'logo' => BASE_URL . '/assets/images/logo.png',
            'sameAs' => $data['social_links'] ?? []
        ]);
    } elseif ($type === 'BreadcrumbList') {
        $schema['itemListElement'] = [];
        foreach ($data['items'] as $index => $item) {
            $schema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url']
            ];
        }
    }
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate breadcrumb structured data
 */
function generateBreadcrumbs($items) {
    $breadcrumbs = [
        [
            'name' => 'Home',
            'url' => BASE_URL
        ]
    ];
    
    $breadcrumbs = array_merge($breadcrumbs, $items);
    
    return generateStructuredData('BreadcrumbList', ['items' => $breadcrumbs]);
}

/**
 * Submit sitemap to search engines
 */
function submitSitemap() {
    $sitemap_url = BASE_URL . '/sitemap.xml.php';
    
    $engines = [
        'google' => 'https://www.google.com/ping?sitemap=' . urlencode($sitemap_url),
        'bing' => 'https://www.bing.com/ping?sitemap=' . urlencode($sitemap_url)
    ];
    
    $results = [];
    foreach ($engines as $engine => $url) {
        $response = @file_get_contents($url);
        $results[$engine] = $response !== false;
    }
    
    return $results;
}
