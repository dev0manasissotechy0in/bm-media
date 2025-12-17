<?php
/**
 * Admin Notification Manager
 * View and manage all sent notifications
 */

require_once '../config/database.php';
require_once '../includes/Security.php';
require_once '../includes/NotificationManager.php';
require_once 'auth_check.php';

$db = new Database();
$notificationManager = new NotificationManager($db);

$page_title = "Notification Manager";

// Get filter parameters
$filter_type = $_GET['filter_type'] ?? 'all';
$filter_tag = $_GET['filter_tag'] ?? 'all';
$filter_target = $_GET['filter_target'] ?? 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 25;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = [];
$params = [];

if ($filter_type !== 'all') {
    $where_clauses[] = "type = ?";
    $params[] = $filter_type;
}

if ($filter_tag !== 'all') {
    $where_clauses[] = "tag = ?";
    $params[] = $filter_tag;
}

if ($filter_target !== 'all') {
    $where_clauses[] = "target = ?";
    $params[] = $filter_target;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get notifications
$query = "SELECT * FROM notifications $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$notifications = $db->fetchAll($query, $params);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM notifications $where_sql";
$count_params = array_slice($params, 0, count($params) - 2);
$total_result = $db->fetchOne($count_query, $count_params);
$total_notifications = $total_result['total'] ?? 0;
$total_pages = ceil($total_notifications / $per_page);

// Get overall statistics
$stats_query = "SELECT 
    COUNT(*) as total_notifications,
    SUM(total_sent) as total_sent,
    SUM(total_delivered) as total_delivered,
    SUM(total_clicked) as total_clicked,
    SUM(total_viewed) as total_viewed,
    ROUND(AVG(CASE WHEN total_sent > 0 THEN (total_clicked / total_sent) * 100 ELSE 0 END), 2) as avg_click_rate
FROM notifications";
$stats = $db->fetchOne($stats_query) ?: [];

// Get unique tags and types for filters
$tags = $db->fetchAll("SELECT DISTINCT tag FROM notifications WHERE tag IS NOT NULL ORDER BY tag");
$types = ['news', 'breaking', 'case_study', 'case_study_update', 'general'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .notification-row:hover { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container-fluid px-4 py-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="bi bi-bell-fill text-primary"></i> Notification Manager
                </h1>
                <p class="text-muted mb-0">View and manage all sent notifications</p>
            </div>
            <div class="col-auto">
                <a href="notification-analytics.php" class="btn btn-info me-2">
                    <i class="bi bi-bar-chart"></i> Analytics
                </a>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-house"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total</p>
                                <h4 class="mb-0"><?= number_format($stats['total_notifications'] ?? 0) ?></h4>
                            </div>
                            <div class="text-primary fs-1">
                                <i class="bi bi-bell-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Sent</p>
                                <h4 class="mb-0"><?= number_format($stats['total_sent'] ?? 0) ?></h4>
                            </div>
                            <div class="text-success fs-1">
                                <i class="bi bi-send-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Delivered</p>
                                <h4 class="mb-0"><?= number_format($stats['total_delivered'] ?? 0) ?></h4>
                            </div>
                            <div class="text-info fs-1">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Viewed</p>
                                <h4 class="mb-0"><?= number_format($stats['total_viewed'] ?? 0) ?></h4>
                            </div>
                            <div class="text-secondary fs-1">
                                <i class="bi bi-eye-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Clicked</p>
                                <h4 class="mb-0"><?= number_format($stats['total_clicked'] ?? 0) ?></h4>
                            </div>
                            <div class="text-warning fs-1">
                                <i class="bi bi-cursor-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm stat-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Avg CTR</p>
                                <h4 class="mb-0"><?= number_format($stats['avg_click_rate'] ?? 0, 1) ?>%</h4>
                            </div>
                            <div class="text-danger fs-1">
                                <i class="bi bi-graph-up"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-funnel"></i> Type
                        </label>
                        <select name="filter_type" class="form-select">
                            <option value="all">All Types</option>
                            <?php foreach ($types as $type): ?>
                            <option value="<?= $type ?>" <?= $filter_type === $type ? 'selected' : '' ?>>
                                <?= getTypeIcon($type) ?> <?= ucfirst(str_replace('_', ' ', $type)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-tag"></i> Tag
                        </label>
                        <select name="filter_tag" class="form-select">
                            <option value="all">All Tags</option>
                            <?php foreach ($tags as $tag): ?>
                            <option value="<?= htmlspecialchars($tag['tag']) ?>" <?= $filter_tag === $tag['tag'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tag['tag']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-device-hdd"></i> Target
                        </label>
                        <select name="filter_target" class="form-select">
                            <option value="all">All Targets</option>
                            <option value="all" <?= $filter_target === 'all' ? 'selected' : '' ?>>🌐 All (Web + App)</option>
                            <option value="web" <?= $filter_target === 'web' ? 'selected' : '' ?>>💻 Web Only</option>
                            <option value="app" <?= $filter_target === 'app' ? 'selected' : '' ?>>📱 App Only</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="notification-manager.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notifications Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul"></i> Sent Notifications
                    </h5>
                    <span class="badge bg-white text-primary">
                        <?= number_format($total_notifications) ?> total
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($notifications)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-bell-slash text-muted" style="font-size: 4rem;"></i>
                    <p class="text-muted mt-3 mb-0">No notifications found</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Notification</th>
                                <th width="100">Type</th>
                                <th width="100">Target</th>
                                <th width="80" class="text-center">Sent</th>
                                <th width="80" class="text-center">Delivered</th>
                                <th width="80" class="text-center">Viewed</th>
                                <th width="80" class="text-center">Clicked</th>
                                <th width="80" class="text-center">CTR</th>
                                <th width="140">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $notif): 
                                $ctr = $notif['total_sent'] > 0 
                                    ? ($notif['total_clicked'] / $notif['total_sent']) * 100 
                                    : 0;
                            ?>
                            <tr class="notification-row">
                                <td class="text-muted"><?= $notif['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-start">
                                        <span class="me-2 fs-4"><?= getTypeIcon($notif['type']) ?></span>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($notif['title']) ?></div>
                                            <small class="text-muted d-block text-truncate" style="max-width: 300px;">
                                                <?= htmlspecialchars($notif['message']) ?>
                                            </small>
                                            <?php if ($notif['tag']): ?>
                                            <span class="badge bg-light text-dark mt-1">
                                                <i class="bi bi-tag-fill"></i> <?= htmlspecialchars($notif['tag']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?= getTypeBadgeColor($notif['type']) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $notif['type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= getTargetBadgeColor($notif['target']) ?>">
                                        <?= strtoupper($notif['target']) ?>
                                    </span>
                                </td>
                                <td class="text-center"><?= number_format($notif['total_sent']) ?></td>
                                <td class="text-center text-success fw-semibold"><?= number_format($notif['total_delivered']) ?></td>
                                <td class="text-center text-info"><?= number_format($notif['total_viewed']) ?></td>
                                <td class="text-center text-warning fw-semibold"><?= number_format($notif['total_clicked']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $ctr >= 5 ? 'success' : ($ctr >= 2 ? 'warning' : 'secondary') ?>" 
                                          title="Click-through rate">
                                        <?= number_format($ctr, 1) ?>%
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M d, Y', strtotime($notif['created_at'])) ?><br>
                                        <span class="text-muted"><?= date('h:i A', strtotime($notif['created_at'])) ?></span>
                                    </small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-light">
                    <nav>
                        <ul class="pagination justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&filter_type=<?= $filter_type ?>&filter_tag=<?= $filter_tag ?>&filter_target=<?= $filter_target ?>">
                                    <i class="bi bi-chevron-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&filter_type=<?= $filter_type ?>&filter_tag=<?= $filter_tag ?>&filter_target=<?= $filter_target ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&filter_type=<?= $filter_type ?>&filter_tag=<?= $filter_tag ?>&filter_target=<?= $filter_target ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&filter_type=<?= $filter_type ?>&filter_tag=<?= $filter_tag ?>&filter_target=<?= $filter_target ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?>&filter_type=<?= $filter_type ?>&filter_tag=<?= $filter_tag ?>&filter_target=<?= $filter_target ?>">
                                    <i class="bi bi-chevron-double-right"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <div class="text-center mt-2 small text-muted">
                        Showing <?= number_format($offset + 1) ?> to <?= number_format(min($offset + $per_page, $total_notifications)) ?> 
                        of <?= number_format($total_notifications) ?> notifications
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        'case_study' => 'info',
        'case_study_update' => 'warning',
        'general' => 'secondary'
    ];
    return $colors[$type] ?? 'secondary';
}

function getTargetBadgeColor($target) {
    $colors = [
        'all' => 'primary',
        'web' => 'info',
        'app' => 'success'
    ];
    return $colors[$target] ?? 'secondary';
}
?>
