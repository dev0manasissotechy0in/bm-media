<?php
/**
 * Admin Notification Analytics
 * Shows click analytics, popular tags, and notification performance
 */

require_once '../config/database.php';
require_once '../includes/Security.php';
require_once 'auth_check.php';

$db = new Database();
$page_title = "Notification Analytics";

// Date range filter
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get analytics by tag
$tag_analytics = $db->fetchAll(
    "SELECT 
        tag,
        type,
        SUM(total_sent) as total_sent,
        SUM(total_clicked) as total_clicked,
        SUM(total_viewed) as total_viewed,
        AVG(click_rate) as avg_click_rate
    FROM notification_analytics
    WHERE date BETWEEN ? AND ?
    GROUP BY tag, type
    ORDER BY total_clicked DESC",
    [$start_date, $end_date]
);

// Get daily trends
$daily_trends = $db->fetchAll(
    "SELECT 
        date,
        SUM(total_sent) as sent,
        SUM(total_clicked) as clicked,
        AVG(click_rate) as click_rate
    FROM notification_analytics
    WHERE date BETWEEN ? AND ?
    GROUP BY date
    ORDER BY date ASC",
    [$start_date, $end_date]
);

// Get type performance
$type_performance = $db->fetchAll(
    "SELECT 
        n.type,
        COUNT(*) as notification_count,
        SUM(n.total_sent) as total_sent,
        SUM(n.total_clicked) as total_clicked,
        SUM(n.total_viewed) as total_viewed,
        ROUND(AVG(CASE WHEN n.total_sent > 0 THEN (n.total_clicked / n.total_sent) * 100 ELSE 0 END), 2) as avg_ctr
    FROM notifications n
    WHERE n.created_at BETWEEN ? AND ?
    GROUP BY n.type
    ORDER BY total_clicked DESC",
    [$start_date . ' 00:00:00', $end_date . ' 23:59:59']
);

// Get best performing notifications
$top_notifications = $db->fetchAll(
    "SELECT 
        id,
        title,
        type,
        tag,
        total_sent,
        total_clicked,
        total_viewed,
        ROUND((total_clicked / NULLIF(total_sent, 0)) * 100, 2) as ctr,
        created_at
    FROM notifications
    WHERE created_at BETWEEN ? AND ?
    AND total_sent > 0
    ORDER BY ctr DESC, total_clicked DESC
    LIMIT 10",
    [$start_date . ' 00:00:00', $end_date . ' 23:59:59']
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/admin_nav.php'; ?>

    <div class="container-fluid px-4 py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="bi bi-bar-chart"></i> Notification Analytics
                </h1>
            </div>
            <div class="col-auto">
                <a href="notification-manager.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Back to Notifications
                </a>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Apply Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Daily Trends Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Daily Notification Trends</h5>
            </div>
            <div class="card-body">
                <canvas id="dailyTrendsChart" height="80"></canvas>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Type Performance -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Performance by Type</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="typePerformanceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tag Performance Table -->
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Performance by Tag</h5>
                    </div>
                    <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Tag</th>
                                    <th class="text-end">Sent</th>
                                    <th class="text-end">Clicked</th>
                                    <th class="text-end">CTR</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tag_analytics as $tag): 
                                    $ctr = $tag['avg_click_rate'] ?? 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($tag['tag']) ?></span>
                                    </td>
                                    <td class="text-end"><?= number_format($tag['total_sent']) ?></td>
                                    <td class="text-end"><?= number_format($tag['total_clicked']) ?></td>
                                    <td class="text-end">
                                        <span class="badge bg-<?= $ctr >= 5 ? 'success' : ($ctr >= 2 ? 'warning' : 'danger') ?>">
                                            <?= number_format($ctr, 2) ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performing Notifications -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">🏆 Top Performing Notifications</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Rank</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Tag</th>
                                <th class="text-end">Sent</th>
                                <th class="text-end">Clicked</th>
                                <th class="text-end">Viewed</th>
                                <th class="text-end">CTR</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; foreach ($top_notifications as $notif): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-<?= $rank <= 3 ? 'warning' : 'secondary' ?> rounded-circle">
                                        <?= $rank ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2"><?= getTypeIcon($notif['type']) ?></span>
                                        <span><?= htmlspecialchars($notif['title']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?= getTypeBadgeColor($notif['type']) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $notif['type'])) ?>
                                    </span>
                                </td>
                                <td><small><?= htmlspecialchars($notif['tag']) ?></small></td>
                                <td class="text-end"><?= number_format($notif['total_sent']) ?></td>
                                <td class="text-end"><?= number_format($notif['total_clicked']) ?></td>
                                <td class="text-end"><?= number_format($notif['total_viewed']) ?></td>
                                <td class="text-end">
                                    <span class="badge bg-success">
                                        <?= number_format($notif['ctr'], 2) ?>%
                                    </span>
                                </td>
                                <td><small><?= date('M d, Y', strtotime($notif['created_at'])) ?></small></td>
                            </tr>
                            <?php $rank++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Daily Trends Chart
        const dailyCtx = document.getElementById('dailyTrendsChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($daily_trends, 'date')) ?>,
                datasets: [{
                    label: 'Sent',
                    data: <?= json_encode(array_column($daily_trends, 'sent')) ?>,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Clicked',
                    data: <?= json_encode(array_column($daily_trends, 'clicked')) ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Type Performance Chart
        const typeCtx = document.getElementById('typePerformanceChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($type_performance, 'type')) ?>,
                datasets: [{
                    label: 'Clicks',
                    data: <?= json_encode(array_column($type_performance, 'total_clicked')) ?>,
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html>

<?php
function getTypeIcon($type) {
    $icons = [
        'news' => '📰',
        'breaking' => '🔥',
        'case_study' => '📋',
        'case_study_update' => '🔄',
        'general' => '🔔'
    ];
    return $icons[$type] ?? '🔔';
}

function getTypeBadgeColor($type) {
    $colors = [
        'news' => 'primary',
        'breaking' => 'danger',
        'case_study' => 'purple',
        'case_study_update' => 'warning',
        'general' => 'secondary'
    ];
    return $colors[$type] ?? 'secondary';
}
?>
