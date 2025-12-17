<?php
/**
 * Users Management
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
$errors = [];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    if ($action === 'toggle_status') {
        $user = $db->fetchOne("SELECT status FROM users WHERE id = ?", [$id]);
        $new_status = $user['status'] === 'active' ? 'blocked' : 'active';
        $db->update('users', ['status' => $new_status], 'id = ?', [$id]);
        $success = 'User status updated';
    }
    
    elseif ($action === 'delete') {
        // Delete user and their data
        $db->query("DELETE FROM comments WHERE user_id = ?", [$id]);
        $db->query("DELETE FROM user_article_likes WHERE user_id = ?", [$id]);
        $db->query("DELETE FROM user_saved_articles WHERE user_id = ?", [$id]);
        $db->query("DELETE FROM users WHERE id = ?", [$id]);
        $success = 'User deleted successfully';
    }
    
    elseif ($action === 'make_reporter') {
        // Get user details
        $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
        
        // Check if already a reporter
        $existing = $db->fetchOne("SELECT id FROM reporters WHERE email = ?", [$user['email']]);
        
        if (!$existing) {
            $db->insert('reporters', [
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'password' => $user['password'], // Copy existing password hash
                'auth_provider' => 'email',
                'status' => 'active',
                'is_author' => 1,
                'email_verified' => 1
            ]);
            $success = 'User promoted to reporter';
        } else {
            $success = 'User is already a reporter';
        }
    }
}

// Filter
$role_filter = $_GET['role'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

// Role filter removed - users table has no role column
// Reporters are in separate reporters table

if ($status_filter !== 'all') {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where[] = "(email LIKE ? OR full_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_clause = implode(' AND ', $where);

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get users
$users = $db->fetchAll(
    "SELECT * FROM users 
     WHERE $where_clause 
     ORDER BY created_at DESC 
     LIMIT $per_page OFFSET $offset",
    $params
);

// Get total
$total = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE $where_clause", $params);
$total_pages = ceil($total['count'] / $per_page);

// Statistics
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'active' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
    'inactive' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'inactive'")['count'],
    'suspended' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE status = 'suspended'")['count']
];

$page_title = 'Manage Users';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manage Users</h1>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?php foreach ($errors as $error): ?>
        <div><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
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
                    <div>Total Users</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h3><?= $stats['active'] ?></h3>
                    <div>Active Users</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h3><?= $stats['inactive'] ?></h3>
                    <div>Inactive Users</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h3><?= $stats['suspended'] ?></h3>
                    <div>Suspended Users</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?= htmlspecialchars($search) ?>" placeholder="Email or full name...">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="suspended" <?= $status_filter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <a href="users.php" class="btn btn-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-users me-1"></i> Users (<?= $total['count'] ?>)
        </div>
        <div class="card-body">
            <?php if (empty($users)): ?>
                <p class="text-muted text-center py-4">No users found.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Full Name</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><strong><?= htmlspecialchars($user['email']) ?></strong></td>
                            <td><?= htmlspecialchars($user['full_name'] ?? '-') ?></td>
                            <td>
                                <?php if ($user['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Blocked</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-<?= $user['status'] === 'active' ? 'warning' : 'success' ?>" 
                                                title="<?= $user['status'] === 'active' ? 'Block' : 'Activate' ?>">
                                            <i class="fas fa-<?= $user['status'] === 'active' ? 'ban' : 'check' ?>"></i>
                                        </button>
                                    </form>
                                    
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="make_reporter">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-info" title="Make Reporter">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                    </form>
                                    
                                    <button class="btn btn-danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['email'])) ?>')">
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
                        <a class="page-link" href="?page=<?= $page - 1 ?>&role=<?= $role_filter ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">Previous</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&role=<?= $role_filter ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&role=<?= $role_filter ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">Next</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form method="post" id="deleteForm" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function deleteUser(id, username) {
    if (confirm('Delete user "' + username + '" and all their data? This cannot be undone.')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>