<?php
/**
 * Newsletter Management - Campaigns
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = Database::getInstance();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $db->delete('newsletter_campaigns', 'id = ?', [$_POST['delete_id']]);
    header('Location: newsletter-campaigns.php');
    exit;
}

// Get campaigns
$status_filter = $_GET['status'] ?? '';
$where = '1=1';
$params = [];

if ($status_filter) {
    $where .= " AND status = ?";
    $params[] = $status_filter;
}

$campaigns = $db->fetchAll(
    "SELECT nc.*, a.title as article_title 
     FROM newsletter_campaigns nc
     LEFT JOIN articles a ON nc.article_id = a.id
     WHERE {$where}
     ORDER BY created_at DESC",
    $params
);

// Get statistics
$stats = [
    'total' => $db->fetchColumn("SELECT COUNT(*) FROM newsletter_campaigns"),
    'draft' => $db->fetchColumn("SELECT COUNT(*) FROM newsletter_campaigns WHERE status = 'draft'"),
    'scheduled' => $db->fetchColumn("SELECT COUNT(*) FROM newsletter_campaigns WHERE status = 'scheduled'"),
    'sent' => $db->fetchColumn("SELECT COUNT(*) FROM newsletter_campaigns WHERE status = 'sent'")
];

$page_title = 'Newsletter Campaigns';
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-megaphone"></i> Newsletter Campaigns</h2>
        <a href="newsletter-create.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Create Campaign
        </a>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6>Total Campaigns</h6>
                    <h2><?= number_format($stats['total']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6>Drafts</h6>
                    <h2><?= number_format($stats['draft']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6>Scheduled</h6>
                    <h2><?= number_format($stats['scheduled']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6>Sent</h6>
                    <h2><?= number_format($stats['sent']) ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="scheduled" <?= $status_filter === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                        <option value="sent" <?= $status_filter === 'sent' ? 'selected' : '' ?>>Sent</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="newsletter-campaigns.php" class="btn btn-secondary w-100">Clear</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Campaigns Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Sent/Total</th>
                            <th>Opens</th>
                            <th>Clicks</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($campaign['title']) ?>
                                <?php if ($campaign['article_id']): ?>
                                <br><small class="text-muted">Article: <?= htmlspecialchars($campaign['article_title']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($campaign['subject']) ?></td>
                            <td>
                                <?php
                                $badge = 'secondary';
                                if ($campaign['status'] === 'sent') $badge = 'success';
                                if ($campaign['status'] === 'scheduled') $badge = 'info';
                                if ($campaign['status'] === 'draft') $badge = 'warning';
                                ?>
                                <span class="badge bg-<?= $badge ?>"><?= ucfirst($campaign['status']) ?></span>
                            </td>
                            <td>
                                <?= number_format($campaign['sent_count']) ?> / <?= number_format($campaign['total_recipients']) ?>
                                <?php if ($campaign['total_recipients'] > 0): ?>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" style="width: <?= ($campaign['sent_count'] / $campaign['total_recipients']) * 100 ?>%"></div>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= number_format($campaign['open_count']) ?>
                                <?php if ($campaign['sent_count'] > 0): ?>
                                <small class="text-muted">(<?= round(($campaign['open_count'] / $campaign['sent_count']) * 100, 1) ?>%)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= number_format($campaign['click_count']) ?>
                                <?php if ($campaign['sent_count'] > 0): ?>
                                <small class="text-muted">(<?= round(($campaign['click_count'] / $campaign['sent_count']) * 100, 1) ?>%)</small>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($campaign['created_at'])) ?></td>
                            <td>
                                <?php if ($campaign['status'] === 'draft'): ?>
                                <a href="newsletter-create.php?id=<?= $campaign['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="newsletter-send.php?id=<?= $campaign['id'] ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-send"></i>
                                </a>
                                <?php elseif ($campaign['status'] === 'sent'): ?>
                                <a href="newsletter-report.php?id=<?= $campaign['id'] ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-graph-up"></i> Report
                                </a>
                                <?php endif; ?>
                                
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this campaign?')">
                                    <input type="hidden" name="delete_id" value="<?= $campaign['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>