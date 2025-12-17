<?php
/**
 * Newsletter Management - Subscribers
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Session.php';
require_once '../includes/Security.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = Database::getInstance();
$errors = [];
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        } else {
            // Check if already exists
            $existing = $db->fetchOne("SELECT * FROM newsletter_subscribers WHERE email = ?", [$email]);
            
            if ($existing) {
                $errors[] = 'Email already subscribed';
            } else {
                $db->insert('newsletter_subscribers', [
                    'email' => $email,
                    'status' => 'verified',
                    'verification_token' => bin2hex(random_bytes(32)),
                    'verified_at' => date('Y-m-d H:i:s')
                ]);
                $success = 'Subscriber added successfully';
            }
        }
    }
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $db->delete('newsletter_subscribers', 'id = ?', [$id]);
        $success = 'Subscriber deleted successfully';
    }
    elseif ($action === 'update_status') {
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $db->update('newsletter_subscribers', ['status' => $status], 'id = ?', [$id]);
        $success = 'Status updated successfully';
    }
    elseif ($action === 'import') {
        // Handle CSV import
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
            $imported = 0;
            $skipped = 0;
            
            while (($data = fgetcsv($handle)) !== false) {
                $email = trim($data[0] ?? '');
                
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $existing = $db->fetchOne("SELECT * FROM newsletter_subscribers WHERE email = ?", [$email]);
                    
                    if (!$existing) {
                        $db->insert('newsletter_subscribers', [
                            'email' => $email,
                            'status' => 'verified',
                            'verification_token' => bin2hex(random_bytes(32)),
                            'verified_at' => date('Y-m-d H:i:s')
                        ]);
                        $imported++;
                    } else {
                        $skipped++;
                    }
                }
            }
            fclose($handle);
            $success = "Imported {$imported} subscribers. Skipped {$skipped} duplicates.";
        }
    }
}

// Get subscribers with filters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = ['1=1'];
$params = [];

if ($status_filter) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where[] = "email LIKE ?";
    $params[] = "%{$search}%";
}

$where_clause = implode(' AND ', $where);

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

$total = $db->fetchColumn("SELECT COUNT(*) FROM newsletter_subscribers WHERE {$where_clause}", $params);
$total_pages = ceil($total / $per_page);

$subscribers = $db->fetchAll(
    "SELECT * FROM newsletter_subscribers 
     WHERE {$where_clause} 
     ORDER BY created_at DESC 
     LIMIT {$per_page} OFFSET {$offset}",
    $params
);

// Get statistics
$stats = [
    'total' => $db->fetchColumn("SELECT COUNT(*) FROM newsletter_subscribers"),
    'subscribed' => $db->fetchColumn("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'subscribed'"),
    'pending' => $db->fetchColumn("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'pending'"),
    'unsubscribed' => $db->fetchColumn("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'unsubscribed'")
];

$page_title = 'Newsletter Subscribers';
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-envelope-heart"></i> Newsletter Subscribers</h2>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-lg"></i> Add Subscriber
            </button>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload"></i> Import CSV
            </button>
            <a href="?export=csv" class="btn btn-outline-primary">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible">
        <button class="btn-close" data-bs-dismiss="alert"></button>
        <?php foreach ($errors as $error): ?>
        <div><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible">
        <button class="btn-close" data-bs-dismiss="alert"></button>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Subscribers</h5>
                    <h2 class="mb-0"><?= number_format($stats['total']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h5 class="card-title">Subscribed</h5>
                    <h2 class="mb-0"><?= number_format($stats['subscribed']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <h2 class="mb-0"><?= number_format($stats['pending']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-body">
                    <h5 class="card-title">Unsubscribed</h5>
                    <h2 class="mb-0"><?= number_format($stats['unsubscribed']) ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search email..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="verified" <?= $status_filter === 'verified' ? 'selected' : '' ?>>Verified</option>
                        <option value="unverified" <?= $status_filter === 'unverified' ? 'selected' : '' ?>>Unverified</option>
                        <option value="unsubscribed" <?= $status_filter === 'unsubscribed' ? 'selected' : '' ?>>Unsubscribed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="newsletter-subscribers.php" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Subscribers Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Subscribed Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $sub): ?>
                        <tr>
                            <td><?= $sub['id'] ?></td>
                            <td><?= htmlspecialchars($sub['email']) ?></td>
                            <td>
                                <?php if ($sub['status'] === 'verified'): ?>
                                <span class="badge bg-success">Verified</span>
                                <?php elseif ($sub['status'] === 'unverified'): ?>
                                <span class="badge bg-warning">Unverified</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Unsubscribed</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($sub['created_at'])) ?></td>
                            <td>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this subscriber?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
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
            
            <?php if ($total_pages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Subscriber Modal -->
<div class="modal fade" id="addModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add Subscriber</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Subscriber</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import CSV Modal -->
<div class="modal fade" id="importModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="import">
                <div class="modal-header">
                    <h5 class="modal-title">Import Subscribers from CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <small class="text-muted">CSV format: One email per line</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>