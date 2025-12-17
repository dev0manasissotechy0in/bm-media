<?php
/**
 * Contact Queries Management
 */

require_once 'auth_check.php';

$page_title = 'Contact Queries';

$db = Database::getInstance();

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM contact_queries WHERE id = ?", [$id]);
    $_SESSION['success'] = 'Query deleted successfully';
    header('Location: contact-queries.php');
    exit;
}

// Handle mark as read/unread
if (isset($_GET['toggle_read'])) {
    $id = (int)$_GET['toggle_read'];
    $query = $db->fetchOne("SELECT is_read FROM contact_queries WHERE id = ?", [$id]);
    if ($query) {
        $new_value = $query['is_read'] ? 0 : 1;
        $db->update('contact_queries', ['is_read' => $new_value], 'id = ?', [$id]);
        $_SESSION['success'] = 'Status updated';
    }
    header('Location: contact-queries.php');
    exit;
}

// Pagination
$page_num = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page_num - 1) * $per_page;

// Filter
$filter = $_GET['filter'] ?? 'all';
$where = '';
if ($filter === 'unread') {
    $where = 'WHERE is_read = 0';
} elseif ($filter === 'read') {
    $where = 'WHERE is_read = 1';
} elseif ($filter === 'app') {
    $where = "WHERE source = 'app'";
} elseif ($filter === 'website') {
    $where = "WHERE source = 'website'";
}

// Get queries
$queries = $db->fetchAll(
    "SELECT * FROM contact_queries $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset"
);

// Get total count
$total_result = $db->fetchOne("SELECT COUNT(*) as count FROM contact_queries $where");
$total = $total_result['count'];
$total_pages = ceil($total / $per_page);

// Get app and website counts
$app_count = $db->fetchOne("SELECT COUNT(*) as count FROM contact_queries WHERE source = 'app'");
$website_count = $db->fetchOne("SELECT COUNT(*) as count FROM contact_queries WHERE source = 'website'");

// Get unread count
$unread_count = $db->fetchOne("SELECT COUNT(*) as count FROM contact_queries WHERE is_read = 0");

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-envelope"></i> Contact Queries
                <?php if ($unread_count['count'] > 0): ?>
                <span class="badge bg-danger"><?= $unread_count['count'] ?> New</span>
                <?php endif; ?>
            </h1>
            <a href="smtp-settings.php" class="btn btn-secondary">
                <i class="bi bi-gear"></i> SMTP Settings
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>" href="?filter=all">
                    All (<?= $db->fetchOne("SELECT COUNT(*) as count FROM contact_queries")['count'] ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'unread' ? 'active' : '' ?>" href="?filter=unread">
                    Unread (<?= $unread_count['count'] ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'read' ? 'active' : '' ?>" href="?filter=read">
                    Read
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'app' ? 'active' : '' ?>" href="?filter=app">
                    <i class="bi bi-phone"></i> App (<?= $app_count['count'] ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'website' ? 'active' : '' ?>" href="?filter=website">
                    <i class="bi bi-globe"></i> Website (<?= $website_count['count'] ?>)
                </a>
            </li>
        </ul>

        <!-- Queries Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="40"></th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Message</th>                                <th>Source</th>                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($queries)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mt-3">No queries found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($queries as $query): ?>
                            <tr class="<?= !$query['is_read'] ? 'table-warning' : '' ?>">
                                <td class="text-center">
                                    <?php if (!$query['is_read']): ?>
                                    <i class="bi bi-envelope-fill text-primary" title="Unread"></i>
                                    <?php else: ?>
                                    <i class="bi bi-envelope-open text-muted" title="Read"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($query['name']) ?></strong>
                                </td>
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($query['email']) ?>">
                                        <?= htmlspecialchars($query['email']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($query['subject']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-link text-decoration-none p-0" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#messageModal<?= $query['id'] ?>">
                                        <?= htmlspecialchars(substr($query['message'], 0, 50)) ?>...
                                    </button>
                                </td>
                                <td>
                                    <?php if ($query['source'] === 'app'): ?>
                                        <span class="badge bg-info">
                                            <i class="bi bi-phone"></i> App
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-globe"></i> Website
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= date('M d, Y H:i', strtotime($query['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="?toggle_read=<?= $query['id'] ?>&filter=<?= $filter ?>" 
                                           class="btn btn-outline-secondary" 
                                           title="Mark as <?= $query['is_read'] ? 'unread' : 'read' ?>">
                                            <i class="bi bi-envelope<?= $query['is_read'] ? '-fill' : '-open' ?>"></i>
                                        </a>
                                        <button onclick="deleteQuery(<?= $query['id'] ?>, '<?= htmlspecialchars(addslashes($query['name'])) ?>')" 
                                                class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Message Modal -->
                            <div class="modal fade" id="messageModal<?= $query['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="Source:</strong> 
                                                <?php if ($query['source'] === 'app'): ?>
                                                    <span class="badge bg-info">
                                                        <i class="bi bi-phone"></i> Mobile App
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="bi bi-globe"></i> Website
                                                    </span>
                                                <?php endif; ?>
                                            </p>
                                            <p><strong>modal-title">Contact Query</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Name:</strong> <?= htmlspecialchars($query['name']) ?></p>
                                            <p><strong>Email:</strong> 
                                                <a href="mailto:<?= htmlspecialchars($query['email']) ?>">
                                                    <?= htmlspecialchars($query['email']) ?>
                                                </a>
                                            </p>
                                            <p><strong>Subject:</strong> <?= htmlspecialchars($query['subject']) ?></p>
                                            <p><strong>Message:</strong></p>
                                            <p class="border p-3 bg-light"><?= nl2br(htmlspecialchars($query['message'])) ?></p>
                                            <p><strong>Date:</strong> <?= date('F d, Y H:i:s', strtotime($query['created_at'])) ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <a href="mailto:<?= htmlspecialchars($query['email']) ?>?subject=Re: <?= urlencode($query['subject']) ?>" 
                                               class="btn btn-primary">
                                                <i class="bi bi-reply"></i> Reply via Email
                                            </a>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page_num > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?filter=<?= $filter ?>&page=<?= $page_num - 1 ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $page_num ? 'active' : '' ?>">
                    <a class="page-link" href="?filter=<?= $filter ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page_num < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?filter=<?= $filter ?>&page=<?= $page_num + 1 ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script>
function deleteQuery(id, name) {
    if (confirm('Are you sure you want to delete the query from "' + name + '"?\n\nThis action cannot be undone.')) {
        window.location.href = 'contact-queries.php?delete=' + id;
    }
}
</script>

<?php include 'includes/footer.php'; ?>