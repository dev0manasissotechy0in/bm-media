<?php
/**
 * Comments Management
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
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    if ($action === 'approve') {
        $db->update('comments', ['status' => 'approved'], 'id = ?', [$id]);
        $success = 'Comment approved';
    }
    
    elseif ($action === 'reject') {
        $db->update('comments', ['status' => 'rejected'], 'id = ?', [$id]);
        $success = 'Comment rejected';
    }
    
    elseif ($action === 'delete') {
        $db->query("DELETE FROM comments WHERE id = ?", [$id]);
        $success = 'Comment deleted';
    }
}

// Filter
$status_filter = $_GET['status'] ?? 'all';
$where_clause = "1=1";
$params = [];

if ($status_filter !== 'all') {
    $where_clause .= " AND c.status = ?";
    $params[] = $status_filter;
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get comments
$comments = $db->fetchAll(
    "SELECT c.*, a.title as article_title, a.slug as article_slug,
     u.full_name as username, u.email as user_email
     FROM comments c
     JOIN articles a ON c.article_id = a.id
     LEFT JOIN users u ON c.user_id = u.id
     WHERE $where_clause
     ORDER BY c.created_at DESC
     LIMIT $per_page OFFSET $offset",
    $params
);

// Get total
$total = $db->fetchOne("SELECT COUNT(*) as count FROM comments c WHERE $where_clause", $params);
$total_pages = ceil($total['count'] / $per_page);

// Get statistics
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM comments")['count'],
    'pending' => $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE status = 'pending'")['count'],
    'approved' => $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE status = 'approved'")['count'],
    'rejected' => $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE status = 'rejected'")['count']
];

$page_title = 'Manage Comments';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manage Comments</h1>
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h3><?= $stats['total'] ?></h3>
                    <div>Total Comments</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h3><?= $stats['pending'] ?></h3>
                    <div>Pending Review</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h3><?= $stats['approved'] ?></h3>
                    <div>Approved</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <h3><?= $stats['rejected'] ?></h3>
                    <div>Rejected</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="?status=all" class="btn btn-outline-primary <?= $status_filter === 'all' ? 'active' : '' ?>">
                    All (<?= $stats['total'] ?>)
                </a>
                <a href="?status=pending" class="btn btn-outline-warning <?= $status_filter === 'pending' ? 'active' : '' ?>">
                    Pending (<?= $stats['pending'] ?>)
                </a>
                <a href="?status=approved" class="btn btn-outline-success <?= $status_filter === 'approved' ? 'active' : '' ?>">
                    Approved (<?= $stats['approved'] ?>)
                </a>
                <a href="?status=rejected" class="btn btn-outline-danger <?= $status_filter === 'rejected' ? 'active' : '' ?>">
                    Rejected (<?= $stats['rejected'] ?>)
                </a>
            </div>
        </div>
    </div>
    
    <!-- Comments List -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-comments me-1"></i> Comments
        </div>
        <div class="card-body">
            <?php if (empty($comments)): ?>
                <p class="text-muted text-center py-4">No comments found.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Comment</th>
                            <th>Article</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td style="max-width: 300px;">
                                <?= htmlspecialchars(substr($comment['comment'], 0, 100)) ?>
                                <?= strlen($comment['comment']) > 100 ? '...' : '' ?>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/article/<?= $comment['article_slug'] ?>" target="_blank">
                                    <?= htmlspecialchars(substr($comment['article_title'], 0, 40)) ?>...
                                </a>
                            </td>
                            <td>
                                <?php if ($comment['username']): ?>
                                    <?= htmlspecialchars($comment['username']) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($comment['name']) ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($comment['email']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($comment['status'] === 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php elseif ($comment['status'] === 'approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($comment['created_at'])) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-info" onclick="viewComment(<?= $comment['id'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <?php if ($comment['status'] !== 'approved'): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                                        <button type="submit" class="btn btn-success" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($comment['status'] !== 'rejected'): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="id" value="<?= $comment['id'] ?>">
                                        <button type="submit" class="btn btn-warning" title="Reject">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-danger" onclick="deleteComment(<?= $comment['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?status=<?= $status_filter ?>&page=<?= $page - 1 ?>">Previous</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?status=<?= $status_filter ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?status=<?= $status_filter ?>&page=<?= $page + 1 ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </card>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Comment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="commentDetails">
                Loading...
            </div>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form method="post" id="deleteForm" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));

function viewComment(id) {
    // Load comment details via AJAX or show from existing data
    const comment = <?= json_encode($comments) ?>.find(c => c.id == id);
    if (comment) {
        document.getElementById('commentDetails').innerHTML = '<div><strong>Article:</strong> ' + comment.article_title + '</div>' +
            '<div><strong>Author:</strong> ' + (comment.username || comment.name) + '</div>' +
            '<div><strong>Email:</strong> ' + (comment.user_email || comment.email) + '</div>' +
            '<div><strong>Date:</strong> ' + comment.created_at + '</div>' +
            '<div><strong>Status:</strong> ' + comment.status + '</div>' +
            '<hr><div><strong>Comment:</strong><p>' + comment.comment + '</p></div>';
        viewModal.show();
    }
}

function deleteComment(id) {
    if (confirm('Delete this comment permanently?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>