<?php
/**
 * Download Statistics
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Functions.php';
require_once '../includes/Session.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();

// Date filter
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Get download statistics
$downloads = $db->fetchAll("
    SELECT a.id, a.title, a.slug, c.name as category_name,
           COUNT(ad.id) as download_count,
           MAX(ad.downloaded_at) as last_download
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN article_downloads ad ON a.id = ad.article_id 
        AND ad.downloaded_at BETWEEN ? AND ?
    WHERE a.status = 'published'
    GROUP BY a.id
    HAVING download_count > 0
    ORDER BY download_count DESC
    LIMIT 100
", [$date_from . ' 00:00:00', $date_to . ' 23:59:59']);

// Get overall stats
$total_downloads = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM article_downloads 
    WHERE downloaded_at BETWEEN ? AND ?
", [$date_from . ' 00:00:00', $date_to . ' 23:59:59']);

$unique_users = $db->fetchColumn("
    SELECT COUNT(DISTINCT user_id) 
    FROM article_downloads 
    WHERE downloaded_at BETWEEN ? AND ?
", [$date_from . ' 00:00:00', $date_to . ' 23:59:59']);

$unique_articles = $db->fetchColumn("
    SELECT COUNT(DISTINCT article_id) 
    FROM article_downloads 
    WHERE downloaded_at BETWEEN ? AND ?
", [$date_from . ' 00:00:00', $date_to . ' 23:59:59']);

// Get daily downloads for chart
$daily_downloads = $db->fetchAll("
    SELECT DATE(downloaded_at) as date, COUNT(*) as count
    FROM article_downloads
    WHERE downloaded_at BETWEEN ? AND ?
    GROUP BY DATE(downloaded_at)
    ORDER BY date ASC
", [$date_from . ' 00:00:00', $date_to . ' 23:59:59']);

$page_title = 'Download Statistics';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4 mb-4"><i class="bi bi-download"></i> Download Statistics</h1>
    
    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Downloads</h5>
                    <h2><?= number_format($total_downloads) ?></h2>
                    <small>In selected period</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Unique Users</h5>
                    <h2><?= number_format($unique_users) ?></h2>
                    <small>Downloaded articles</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Articles Downloaded</h5>
                    <h2><?= number_format($unique_articles) ?></h2>
                    <small>Unique articles</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Daily Downloads Chart -->
    <?php if (!empty($daily_downloads)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Daily Downloads Trend</h5>
        </div>
        <div class="card-body">
            <canvas id="downloadsChart" height="80"></canvas>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Top Downloaded Articles -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Most Downloaded Articles</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Article</th>
                            <th>Category</th>
                            <th>Downloads</th>
                            <th>Last Download</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($downloads)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="bi bi-inbox fs-1 text-muted"></i>
                                <p class="text-muted">No downloads in selected period</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($downloads as $index => $download): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($download['title']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($download['category_name'] ?? 'N/A') ?></td>
                            <td><span class="badge bg-primary"><?= number_format($download['download_count']) ?></span></td>
                            <td><?= date('M d, Y H:i', strtotime($download['last_download'])) ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/article.php?slug=<?= $download['slug'] ?>" 
                                   target="_blank" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($daily_downloads)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('downloadsChart');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($daily_downloads, 'date')) ?>,
        datasets: [{
            label: 'Downloads',
            data: <?= json_encode(array_column($daily_downloads, 'count')) ?>,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
