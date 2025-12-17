<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($case['meta_title'] ?? $case['title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($case['meta_description'] ?? $case['short_description']); ?>">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background: #f5f5f5;
        }
        
        /* HERO SECTION */
        .case-hero {
            position: relative;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 60px 20px 40px;
        }
        
        .case-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('<?php echo $case['cover_image']; ?>') center/cover;
            opacity: 0.15;
        }
        
        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .case-category {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }
        
        .case-title {
            font-size: 42px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 20px;
        }
        
        .case-description {
            font-size: 18px;
            line-height: 1.6;
            opacity: 0.95;
            max-width: 800px;
            margin-bottom: 30px;
        }
        
        .case-meta {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .meta-icon {
            width: 20px;
            height: 20px;
            opacity: 0.8;
        }
        
        .case-status {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
        }
        
        .status-active { background: #10b981; color: white; }
        .status-concluded { background: #6b7280; color: white; }
        .status-archived { background: #f59e0b; color: white; }
        
        .case-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: white;
            color: #1e3c72;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        /* CONTAINER */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* TABS */
        .tabs-nav {
            background: white;
            border-bottom: 1px solid #e5e5e5;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .tabs-nav ul {
            list-style: none;
            display: flex;
            gap: 5px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            overflow-x: auto;
        }
        
        .tabs-nav li {
            white-space: nowrap;
        }
        
        .tabs-nav a {
            display: block;
            padding: 16px 20px;
            color: #666;
            text-decoration: none;
            font-weight: 600;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tabs-nav a:hover,
        .tabs-nav a.active {
            color: #1e3c72;
            border-bottom-color: #1e3c72;
        }
        
        /* CONTENT SECTIONS */
        .content-section {
            background: white;
            margin: 30px 0;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        /* TIMELINE */
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #1e3c72, #e5e5e5);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 40px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -29px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #1e3c72;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #1e3c72;
        }
        
        .timeline-item.major::before {
            width: 18px;
            height: 18px;
            left: -32px;
            background: #ef4444;
            box-shadow: 0 0 0 2px #ef4444, 0 0 20px rgba(239,68,68,0.3);
        }
        
        .timeline-date {
            font-size: 13px;
            font-weight: 600;
            color: #666;
            margin-bottom: 8px;
        }
        
        .timeline-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        
        .timeline-description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .timeline-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #eff6ff;
            color: #1e3c72;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 8px;
        }
        
        /* ARTICLES GRID */
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .article-card {
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
        }
        
        .article-card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transform: translateY(-4px);
        }
        
        .article-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f0f0f0;
        }
        
        .article-content {
            padding: 20px;
        }
        
        .article-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .article-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #666;
        }
        
        /* DOCUMENTS */
        .documents-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .document-card {
            border: 1px solid #e5e5e5;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            gap: 20px;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
        }
        
        .document-card:hover {
            border-color: #1e3c72;
            background: #f8fafc;
        }
        
        .document-icon {
            width: 50px;
            height: 50px;
            background: #eff6ff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .document-info {
            flex: 1;
        }
        
        .document-type {
            font-size: 12px;
            color: #1e3c72;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .document-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .document-summary {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }
        
        /* MEDIA GALLERY */
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .media-item {
            position: relative;
            border-radius: 10px;
            overflow: hidden;
            aspect-ratio: 16/9;
            cursor: pointer;
        }
        
        .media-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .media-item:hover img {
            transform: scale(1.05);
        }
        
        .media-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 15px;
            color: white;
        }
        
        .media-caption {
            font-size: 14px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .case-title { font-size: 28px; }
            .case-description { font-size: 16px; }
            .content-section { padding: 25px 20px; }
            .section-title { font-size: 22px; }
            .articles-grid { grid-template-columns: 1fr; }
            .media-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <!-- HERO SECTION -->
    <div class="case-hero">
        <div class="hero-content">
            <span class="case-category"><?php echo htmlspecialchars($case['category']); ?></span>
            
            <h1 class="case-title"><?php echo htmlspecialchars($case['title']); ?></h1>
            
            <p class="case-description"><?php echo htmlspecialchars($case['short_description']); ?></p>
            
            <div class="case-meta">
                <div class="meta-item">
                    <span class="meta-icon">📍</span>
                    <span><?php echo htmlspecialchars($case['primary_location']); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">📅</span>
                    <span><?php echo date('M j, Y', strtotime($case['start_date'])); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">📰</span>
                    <span><?php echo number_format($case['total_articles']); ?> articles</span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">👥</span>
                    <span><?php echo number_format($case['total_followers']); ?> followers</span>
                </div>
            </div>
            
            <span class="case-status status-<?php echo strtolower($case['status']); ?>">
                <?php echo ucfirst($case['status']); ?>
            </span>
            
            <div class="case-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                <button 
                    class="btn <?php echo $case['is_following'] ? 'btn-success' : 'btn-primary'; ?>" 
                    id="followCaseBtn"
                    data-case-id="<?php echo $case['id']; ?>"
                    onclick="toggleCaseFollow(<?php echo $case['id']; ?>, <?php echo $case['is_following'] ? 'true' : 'false'; ?>)">
                    <i class="bi bi-<?php echo $case['is_following'] ? 'check-circle' : 'bell'; ?> me-1"></i>
                    <span><?php echo $case['is_following'] ? 'Following' : 'Follow Case'; ?></span>
                </button>
                <?php else: ?>
                <a href="<?= BASE_URL ?>/login" class="btn btn-primary">
                    <i class="bi bi-bell me-1"></i> Follow Case
                </a>
                <?php endif; ?>
                <button class="btn btn-secondary" onclick="shareCase()">
                    <i class="bi bi-share me-1"></i> Share
                </button>
            </div>
        </div>
    </div>
    
    <!-- TABS NAVIGATION -->
    <nav class="tabs-nav">
        <ul>
            <li><a href="#overview" class="active">Overview</a></li>
            <li><a href="#timeline">Timeline</a></li>
            <li><a href="#articles">Articles (<?php echo number_format($recentArticles['total']); ?>)</a></li>
            <li><a href="#documents">Documents (<?php echo $documentsData['total']; ?>)</a></li>
            <li><a href="#media">Media (<?php echo $mediaData['total']; ?>)</a></li>
            <li><a href="#reviews">Analysis</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <!-- OVERVIEW SECTION -->
        <section id="overview" class="content-section">
            <div class="section-header">
                <h2 class="section-title">Case Overview</h2>
            </div>
            <div style="font-size: 16px; line-height: 1.8; color: #333;">
                <?php echo nl2br(htmlspecialchars($case['full_description'] ?? $case['short_description'] ?? 'No description available.')); ?>
            </div>
        </section>
        
        <!-- TIMELINE SECTION -->
        <section id="timeline" class="content-section">
            <div class="section-header">
                <h2 class="section-title">Timeline of Events</h2>
                <span style="color: #666;"><?php echo $timeline['total']; ?> events</span>
            </div>
            
            <div class="timeline">
                <?php foreach ($timeline['events'] as $event): ?>
                <div class="timeline-item <?php echo $event['is_major_event'] ? 'major' : ''; ?>">
                    <div class="timeline-date">
                        <?php 
                        echo date('F j, Y', strtotime($event['event_date']));
                        if ($event['event_time']) {
                            echo ' at ' . date('g:i A', strtotime($event['event_time']));
                        }
                        ?>
                    </div>
                    <h3 class="timeline-title"><?php echo htmlspecialchars($event['event_title']); ?></h3>
                    <p class="timeline-description"><?php echo htmlspecialchars($event['event_description']); ?></p>
                    <?php if ($event['is_major_event']): ?>
                        <span class="timeline-badge">MAJOR EVENT</span>
                    <?php endif; ?>
                    <span class="timeline-badge"><?php echo ucfirst($event['event_type']); ?></span>
                    <?php if ($event['linked_articles_count'] > 0): ?>
                        <span class="timeline-badge"><?php echo $event['linked_articles_count']; ?> articles</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- ARTICLES SECTION -->
        <section id="articles" class="content-section">
            <div class="section-header">
                <h2 class="section-title">Related Articles</h2>
                <a href="/cases/<?php echo $case['slug']; ?>/articles" style="color: #1e3c72; font-weight: 600; text-decoration: none;">
                    View all <?php echo number_format($recentArticles['total']); ?> →
                </a>
            </div>
            
            <div class="articles-grid">
                <?php foreach ($recentArticles['articles'] as $article): ?>
                <a href="/article/<?php echo $article['slug']; ?>" class="article-card">
                    <img src="<?php echo htmlspecialchars($article['thumbnail']); ?>" 
                         alt="<?php echo htmlspecialchars($article['title']); ?>" 
                         class="article-thumbnail">
                    <div class="article-content">
                        <?php if ($article['is_key_article']): ?>
                            <span style="display: inline-block; background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; margin-bottom: 10px;">
                                ⭐ KEY ARTICLE
                            </span>
                        <?php endif; ?>
                        <h3 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                        <div class="article-meta">
                            <span><?php echo $article['author']['name']; ?></span>
                            <span>•</span>
                            <span><?php echo formatDate($article['published_at'] ?? '', 'M j, Y', $article['created_at'] ?? ''); ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- DOCUMENTS SECTION -->
        <section id="documents" class="content-section">
            <div class="section-header">
                <h2 class="section-title">Legal Documents</h2>
                <span style="color: #666;"><?php echo $documentsData['total']; ?> documents</span>
            </div>
            
            <div class="documents-list">
                <?php foreach ($documentsData['documents'] as $doc): ?>
                <a href="<?php echo BASE_URL . '/' . htmlspecialchars($doc['file_url']); ?>" 
                   target="_blank" 
                   class="document-card">
                    <div class="document-icon">
                        <span style="font-size: 24px;">📄</span>
                    </div>
                    <div class="document-info">
                        <div class="document-type"><?php echo htmlspecialchars($doc['document_type']); ?></div>
                        <h3 class="document-title"><?php echo htmlspecialchars($doc['title']); ?></h3>
                        <?php if ($doc['plain_language_summary']): ?>
                            <p class="document-summary"><?php echo htmlspecialchars($doc['plain_language_summary']); ?></p>
                        <?php endif; ?>
                        <div style="margin-top: 10px; font-size: 12px; color: #888;">
                            <?php echo date('M j, Y', strtotime($doc['document_date'])); ?> • 
                            <?php echo strtoupper($doc['file_type'] ?? 'pdf'); ?> • 
                            <?php echo round(($doc['file_size'] ?? 0) / 1024, 1); ?> KB
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- MEDIA SECTION -->
        <section id="media" class="content-section">
            <div class="section-header">
                <h2 class="section-title">Media Gallery</h2>
                <div style="color: #666;">
                    <?php echo $mediaData['photos']; ?> photos • 
                    <?php echo $mediaData['videos']; ?> videos • 
                    <?php echo $mediaData['audio']; ?> audio
                </div>
            </div>
            
            <div class="media-grid">
                <?php foreach ($mediaData['items'] as $media): ?>
                <div class="media-item" onclick="openMedia('<?php echo BASE_URL . '/' . htmlspecialchars($media['file_url']); ?>')">
                    <?php 
                    $thumbnailUrl = $media['thumbnail_url'] 
                        ? (BASE_URL . '/' . $media['thumbnail_url']) 
                        : (BASE_URL . '/' . $media['file_url']); 
                    ?>
                    <img src="<?php echo htmlspecialchars($thumbnailUrl); ?>" 
                         alt="<?php echo htmlspecialchars($media['caption'] ?? 'Media'); ?>"
                         onerror="this.src='<?php echo BASE_URL; ?>/assets/images/default-thumbnail.jpg'">
                    <div class="media-overlay">
                        <span class="media-caption"><?php echo htmlspecialchars($media['caption'] ?? 'Media'); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    
    <script>
        function followCase() {
            const caseId = <?php echo $case['id']; ?>;
            const isFollowing = <?php echo $case['is_following'] ? 'true' : 'false'; ?>;
            
            fetch(`/api/cases/${caseId}/${isFollowing ? 'unfollow' : 'follow'}`, {
                method: isFollowing ? 'DELETE' : 'POST',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token')
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
        
        function openMedia(url) {
            window.open(url, '_blank');
        }
        
        function shareCase() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($case['title']); ?>',
                    text: '<?php echo addslashes($case['short_description']); ?>',
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href);
                alert('Link copied to clipboard!');
            }
        }
    </script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Case Follow System -->
    <script src="<?= ASSETS_URL ?>/js/case-follow.js"></script>
</body>
</html>
