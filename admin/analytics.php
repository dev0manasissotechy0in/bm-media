<?php
/**
 * Admin Analytics Dashboard
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = Database::getInstance();

// Date range filter
$date_range = $_GET['range'] ?? '7days';
$start_date = match($date_range) {
    'today' => date('Y-m-d'),
    '7days' => date('Y-m-d', strtotime('-7 days')),
    '30days' => date('Y-m-d', strtotime('-30 days')),
    'custom' => $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days')),
    default => date('Y-m-d', strtotime('-7 days'))
};

$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Page Views & Engagement
$page_views = $db->fetchAll(
    "SELECT DATE(created_at) as date, COUNT(*) as views
     FROM user_interactions
     WHERE type = 'page_view' AND created_at >= ?
     GROUP BY DATE(created_at)
     ORDER BY date",
    [$start_date]
);

// Popular Articles
$popular_articles = $db->fetchAll(
    "SELECT a.id, a.title, COUNT(ui.id) as reads,
     AVG(ui.read_duration) as avg_read_time,
     AVG(ui.scroll_depth) as avg_scroll
     FROM articles a
     JOIN user_interactions ui ON ui.reference_id = a.id
     WHERE ui.type = 'article_read' AND ui.created_at >= ?
     GROUP BY a.id
     ORDER BY reads DESC
     LIMIT 10",
    [$start_date]
);

// Traffic Sources
$traffic_sources = $db->fetchAll(
    "SELECT 
        CASE 
            WHEN referrer_url IS NULL OR referrer_url = '' THEN 'Direct'
            WHEN referrer_url LIKE '%google%' THEN 'Google'
            WHEN referrer_url LIKE '%facebook%' THEN 'Facebook'
            WHEN referrer_url LIKE '%twitter%' THEN 'Twitter'
            ELSE 'Other'
        END as source,
        COUNT(*) as count
     FROM user_interactions
     WHERE type = 'page_view' AND created_at >= ?
     GROUP BY source
     ORDER BY count DESC",
    [$start_date]
);

// Device Breakdown
$devices = $db->fetchAll(
    "SELECT device_type, COUNT(DISTINCT session_id) as sessions
     FROM user_interactions
     WHERE created_at >= ?
     GROUP BY device_type",
    [$start_date]
);

// User Engagement Metrics
$engagement = $db->fetchOne(
    "SELECT 
        COUNT(DISTINCT session_id) as unique_visitors,
        COUNT(*) as total_pageviews,
        AVG(read_duration) as avg_time,
        AVG(scroll_depth) as avg_scroll
     FROM user_interactions
     WHERE created_at >= ?",
    [$start_date]
);

// Newsletter Statistics
$newsletter_stats = $db->fetchOne(
    "SELECT * FROM newsletter_stats LIMIT 1"
);

// Top Search Queries
$top_searches = $db->fetchAll(
    "SELECT metadata->>'$.query' as query, COUNT(*) as count
     FROM user_interactions
     WHERE type = 'search' AND created_at >= ?
     GROUP BY query
     ORDER BY count DESC
     LIMIT 10",
    [$start_date]
);

// Browser Stats
$browsers = $db->fetchAll(
    "SELECT browser, COUNT(DISTINCT session_id) as sessions
     FROM user_interactions
     WHERE created_at >= ?
     GROUP BY browser
     ORDER BY sessions DESC",
    [$start_date]
);

// Geographic Data
$countries = $db->fetchAll(
    "SELECT country, COUNT(*) as visits
     FROM user_interactions
     WHERE created_at >= ? AND country IS NOT NULL
     GROUP BY country
     ORDER BY visits DESC
     LIMIT 10",
    [$start_date]
);

$page_title = 'Analytics Dashboard';
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-graph-up"></i> Analytics Dashboard</h2>
        
        <!-- Date Range Selector -->
        <form method="get" class="d-flex gap-2">
            <select name="range" class="form-select" onchange="this.form.submit()">
                <option value="today" <?= $date_range === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="7days" <?= $date_range === '7days' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="30days" <?= $date_range === '30days' ? 'selected' : '' ?>>Last 30 Days</option>
                <option value="custom" <?= $date_range === 'custom' ? 'selected' : '' ?>>Custom Range</option>
            </select>
            
            <?php if ($date_range === 'custom'): ?>
            <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
            <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
            <button type="submit" class="btn btn-primary">Apply</button>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="text-muted">Unique Visitors</h6>
                    <h2><?= number_format($engagement['unique_visitors']) ?></h2>
                    <small><i class="bi bi-people"></i> Sessions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted">Page Views</h6>
                    <h2><?= number_format($engagement['total_pageviews']) ?></h2>
                    <small><i class="bi bi-eye"></i> Total views</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="text-muted">Avg. Time on Page</h6>
                    <h2><?= gmdate('i:s', $engagement['avg_time'] ?? 0) ?></h2>
                    <small><i class="bi bi-clock"></i> Minutes:Seconds</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-muted">Avg. Scroll Depth</h6>
                    <h2><?= round($engagement['avg_scroll'] ?? 0) ?>%</h2>
                    <small><i class="bi bi-arrow-down"></i> Page scroll</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Page Views Chart -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-graph-up"></i> Page Views Over Time</h5>
                </div>
                <div class="card-body">
                    <canvas id="pageViewsChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-pie-chart"></i> Traffic Sources</h5>
                </div>
                <div class="card-body">
                    <canvas id="trafficSourcesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Popular Articles -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-fire"></i> Most Popular Articles</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th>Reads</th>
                                    <th>Avg. Read Time</th>
                                    <th>Avg. Scroll</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_articles as $article): ?>
                                <tr>
                                    <td><?= htmlspecialchars($article['title']) ?></td>
                                    <td><?= number_format($article['reads']) ?></td>
                                    <td><?= gmdate('i:s', $article['avg_read_time'] ?? 0) ?></td>
                                    <td><?= round($article['avg_scroll'] ?? 0) ?>%</td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/article.php?id=<?= $article['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Device & Browser Stats -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-phone"></i> Device Breakdown</h5>
                </div>
                <div class="card-body">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-browser-chrome"></i> Top Browsers</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($browsers as $browser): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($browser['browser'] ?: 'Unknown') ?>
                            <span class="badge bg-primary"><?= number_format($browser['sessions']) ?> sessions</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Newsletter Performance -->
    <?php if ($newsletter_stats): ?>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-envelope-heart"></i> Newsletter Performance (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h6>Campaigns Sent</h6>
                            <h3><?= number_format($newsletter_stats['campaigns_sent']) ?></h3>
                        </div>
                        <div class="col-md-3">
                            <h6>Emails Delivered</h6>
                            <h3><?= number_format($newsletter_stats['total_sent']) ?></h3>
                        </div>
                        <div class="col-md-3">
                            <h6>Open Rate</h6>
                            <h3><?= round($newsletter_stats['avg_open_rate'], 1) ?>%</h3>
                        </div>
                        <div class="col-md-3">
                            <h6>Click Rate</h6>
                            <h3><?= round($newsletter_stats['avg_click_rate'], 1) ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
// Page Views Chart
const pageViewsCtx = document.getElementById('pageViewsChart').getContext('2d');
new Chart(pageViewsCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($page_views, 'date')) ?>,
        datasets: [{
            label: 'Page Views',
            data: <?= json_encode(array_column($page_views, 'views')) ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
    }
});

// Traffic Sources Pie Chart
const trafficCtx = document.getElementById('trafficSourcesChart').getContext('2d');
new Chart(trafficCtx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($traffic_sources, 'source')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($traffic_sources, 'count')) ?>,
            backgroundColor: ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#6c757d']
        }]
    }
});

// Device Chart
const deviceCtx = document.getElementById('deviceChart').getContext('2d');
new Chart(deviceCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($devices, 'device_type')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($devices, 'sessions')) ?>,
            backgroundColor: ['#0d6efd', '#198754', '#ffc107']
        }]
    }
});
</script>

<?php include '../includes/footer.php'; ?>