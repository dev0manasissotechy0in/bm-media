<?php
/**
 * Case Threads - Long-running news cases
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Functions.php';
require_once 'includes/Session.php';

$db = Database::getInstance();

// Page meta information
$page_title = 'Case Threads - Track Long-Running Cases';
$page_description = 'Follow comprehensive coverage of major ongoing cases, investigations, and events with timeline, articles, documents, and media.';

// Pagination
$page_num = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page_num - 1) * $per_page;

// Filters
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';

$where = ['1=1'];
$params = [];

if (!empty($category_filter)) {
    $where[] = "category = ?";
    $params[] = $category_filter;
}

if (!empty($status_filter)) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $where[] = "(title LIKE ? OR short_description LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(' AND ', $where);

// Get cases
$cases = $db->fetchAll("
    SELECT 
        id, title, slug, short_description,
        status, category, primary_location,
        start_date, end_date,
        thumbnail, cover_image,
        total_articles, total_followers, total_views,
        created_at, last_activity_at
    FROM case_threads
    WHERE $where_clause
    ORDER BY last_activity_at DESC, created_at DESC
    LIMIT $per_page OFFSET $offset
", $params);

// Get total count
$total_result = $db->fetchOne("SELECT COUNT(*) as count FROM case_threads WHERE $where_clause", $params);
$total_cases = $total_result['count'];
$total_pages = ceil($total_cases / $per_page);

// Get categories for filter
$categories = [
    'crime' => 'Crime',
    'war' => 'War & Conflict',
    'corporate' => 'Corporate Scandal',
    'political' => 'Political',
    'environmental' => 'Environmental',
    'humanitarian' => 'Humanitarian'
];

// Get statuses for filter
$statuses = [
    'active' => 'Active',
    'concluded' => 'Concluded',
    'archived' => 'Archived'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* HEADER */
        .page-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 60px 20px 40px;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 15px;
        }
        
        .page-header p {
            font-size: 18px;
            opacity: 0.95;
            max-width: 700px;
            margin: 0 auto;
        }
        
        /* FILTERS */
        .filters-section {
            background: white;
            padding: 25px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .filters-container {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .filter-group label {
            font-weight: 600;
            font-size: 14px;
            color: #555;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            min-width: 150px;
        }
        
        .search-box {
            min-width: 300px;
        }
        
        .btn-filter {
            padding: 10px 20px;
            background: #1e3c72;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-filter:hover {
            background: #2a5298;
        }
        
        .btn-clear {
            padding: 10px 20px;
            background: white;
            color: #666;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        
        /* STATS */
        .stats-bar {
            background: white;
            padding: 15px 0;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .stats-bar p {
            text-align: center;
            color: #666;
            font-size: 15px;
        }
        
        /* CASES GRID */
        .cases-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }
        
        .case-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            display: block;
        }
        
        .case-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .case-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .case-content {
            padding: 25px;
        }
        
        .case-badges {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-crime { background: #fee2e2; color: #991b1b; }
        .badge-war { background: #fed7aa; color: #9a3412; }
        .badge-corporate { background: #e9d5ff; color: #6b21a8; }
        .badge-political { background: #dbeafe; color: #1e40af; }
        .badge-environmental { background: #d1fae5; color: #065f46; }
        .badge-humanitarian { background: #fef3c7; color: #92400e; }
        
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-concluded { background: #e5e7eb; color: #374151; }
        .badge-archived { background: #fef3c7; color: #92400e; }
        
        .case-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #1a1a1a;
            line-height: 1.3;
        }
        
        .case-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .case-meta {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #888;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
        }
        
        .empty-state svg {
            width: 120px;
            height: 120px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .empty-state p {
            color: #666;
            font-size: 16px;
        }
        
        /* PAGINATION */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 40px 0;
        }
        
        .pagination a,
        .pagination span {
            padding: 10px 15px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background: #1e3c72;
            color: white;
            border-color: #1e3c72;
        }
        
        .pagination .active {
            background: #1e3c72;
            color: white;
            border-color: #1e3c72;
        }
        
        @media (max-width: 768px) {
            .page-header h1 { font-size: 28px; }
            .page-header p { font-size: 16px; }
            .cases-grid { grid-template-columns: 1fr; }
            .filters-container { flex-direction: column; align-items: stretch; }
            .filter-group { flex-direction: column; align-items: stretch; }
            .search-box { min-width: 100%; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="container">
            <h1>📚 Case Threads</h1>
            <p>Follow comprehensive coverage of major cases and investigations with timelines, documents, and expert analysis.</p>
        </div>
    </div>
    
    <!-- FILTERS -->
    <div class="filters-section">
        <div class="container">
            <form method="GET" action="" class="filters-container">
                <div class="filter-group">
                    <label>Category:</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $category_filter === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Status:</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $status_filter === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <input type="search" name="search" class="search-box" placeholder="Search cases..." 
                           value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                
                <button type="submit" class="btn-filter">Apply Filters</button>
                <a href="case-threads" class="btn-clear">Clear</a>
            </form>
        </div>
    </div>
    
    <!-- STATS -->
    <?php if ($total_cases > 0): ?>
    <div class="container">
        <div class="stats-bar">
            <p>Showing <?php echo count($cases); ?> of <?php echo $total_cases; ?> case threads</p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- CASES GRID -->
    <div class="container">
        <?php if (empty($cases)): ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3>No Cases Found</h3>
                <p>Try adjusting your filters or search query.</p>
            </div>
        <?php else: ?>
            <div class="cases-grid">
                <?php foreach ($cases as $case): ?>
                    <a href="case/<?php echo htmlspecialchars($case['slug']); ?>" class="case-card">
                        <img src="<?php echo htmlspecialchars($case['thumbnail'] ?? $case['cover_image'] ?? 'assets/images/default-case.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($case['title']); ?>" 
                             class="case-thumbnail"
                             onerror="this.src='assets/images/default-case.jpg'">
                        
                        <div class="case-content">
                            <div class="case-badges">
                                <span class="badge badge-<?php echo htmlspecialchars($case['category']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($case['category'])); ?>
                                </span>
                                <span class="badge badge-<?php echo htmlspecialchars($case['status']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($case['status'])); ?>
                                </span>
                            </div>
                            
                            <h3 class="case-title"><?php echo htmlspecialchars($case['title']); ?></h3>
                            <p class="case-description"><?php echo htmlspecialchars($case['short_description']); ?></p>
                            
                            <div class="case-meta">
                                <span class="meta-item">
                                    📰 <?php echo number_format($case['total_articles']); ?> articles
                                </span>
                                <span class="meta-item">
                                    👥 <?php echo number_format($case['total_followers']); ?> followers
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- PAGINATION -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page_num > 1): ?>
                        <a href="?page=<?php echo $page_num - 1; ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">
                            ← Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page_num - 2); $i <= min($total_pages, $page_num + 2); $i++): ?>
                        <?php if ($i == $page_num): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page_num < $total_pages): ?>
                        <a href="?page=<?php echo $page_num + 1; ?>&category=<?php echo $category_filter; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_query); ?>">
                            Next →
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
