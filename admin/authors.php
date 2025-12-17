<?php
/**
 * Author Management System
 * Manage authors with permissions and article approval workflow
 */

require_once 'auth_check.php';

$page_title = 'Author Management';
$db = Database::getInstance();

$success = '';
$error = '';

// Handle status update
if (isset($_POST['update_status'])) {
    $author_id = (int)$_POST['author_id'];
    $new_status = $_POST['status'];
    
    if ($db->update('authors', ['status' => $new_status], 'id = ?', [$author_id])) {
        $success = 'Author status updated successfully!';
    } else {
        $error = 'Failed to update status.';
    }
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $author_id = (int)$_GET['delete'];
    
    // Get author details
    $author = $db->fetchOne("SELECT profile_photo FROM authors WHERE id = ?", [$author_id]);
    
    if ($author) {
        // Delete profile photo
        if (!empty($author['profile_photo']) && file_exists(__DIR__ . '/../' . $author['profile_photo'])) {
            unlink(__DIR__ . '/../' . $author['profile_photo']);
        }
        
        // Update articles to remove author association
        $db->update('articles', ['author_id' => NULL], 'author_id = ?', [$author_id]);
        
        // Delete author
        if ($db->delete('authors', 'id = ?', [$author_id])) {
            $success = 'Author deleted successfully!';
        } else {
            $error = 'Failed to delete author.';
        }
    }
}

// Get all authors with statistics
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($status)) {
    $where[] = "status = ?";
    $params[] = $status;
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$authors = $db->fetchAll(
    "SELECT a.*, 
    (SELECT COUNT(*) FROM articles WHERE author_id = a.id) as total_articles,
    (SELECT COUNT(*) FROM articles WHERE author_id = a.id AND approval_status = 'approved') as approved_articles,
    (SELECT COUNT(*) FROM articles WHERE author_id = a.id AND approval_status = 'declined') as declined_articles,
    (SELECT COUNT(*) FROM articles WHERE author_id = a.id AND approval_status = 'pending') as pending_articles
    FROM authors a
    {$where_clause}
    ORDER BY a.created_at DESC",
    $params
);

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-pen-fill"></i> Author Management
            </h1>
            <a href="author-add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Author
            </a>
        </div>
        
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button class="btn-close" data-bs-dismiss="alert"></button>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button class="btn-close" data-bs-dismiss="alert"></button>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by name or email..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="authors.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Authors Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Articles</th>
                                <th>Pending</th>
                                <th>Approved</th>
                                <th>Declined</th>
                                <th>Last Login</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($authors)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p class="mb-0 mt-2">No authors found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($authors as $author): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($author['profile_photo'])): ?>
                                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($author['profile_photo']) ?>" 
                                         alt="<?= htmlspecialchars($author['full_name']) ?>" 
                                         class="rounded-circle" 
                                         style="width: 45px; height: 45px; object-fit: cover;">
                                    <?php else: ?>
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" 
                                         style="width: 45px; height: 45px;">
                                        <i class="bi bi-person text-white"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($author['full_name']) ?></strong>
                                </td>
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($author['email']) ?>">
                                        <?= htmlspecialchars($author['email']) ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-primary" style="font-size: 14px;">
                                        <?= $author['total_articles'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($author['pending_articles'] > 0): ?>
                                    <span class="badge bg-warning text-dark" style="font-size: 14px;">
                                        <?= $author['pending_articles'] ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-success" style="font-size: 14px;">
                                        <?= $author['approved_articles'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($author['declined_articles'] > 0): ?>
                                    <span class="badge bg-danger" style="font-size: 14px;">
                                        <?= $author['declined_articles'] ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($author['last_login']): ?>
                                    <small class="text-muted">
                                        <?= date('M d, Y H:i', strtotime($author['last_login'])) ?>
                                    </small>
                                    <?php else: ?>
                                    <small class="text-muted">Never</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="author_id" value="<?= $author['id'] ?>">
                                        <select name="status" class="form-select form-select-sm" 
                                                onchange="if(confirm('Change author status?')) this.form.submit();" 
                                                style="width: 120px;">
                                            <option value="active" <?= $author['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                            <option value="inactive" <?= $author['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                            <option value="suspended" <?= $author['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="author-view.php?id=<?= $author['id'] ?>" 
                                           class="btn btn-info" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="author-edit.php?id=<?= $author['id'] ?>" 
                                           class="btn btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="author-permissions.php?id=<?= $author['id'] ?>" 
                                           class="btn btn-secondary" title="Permissions">
                                            <i class="bi bi-shield-lock"></i>
                                        </a>
                                        <a href="?delete=<?= $author['id'] ?>" 
                                           class="btn btn-danger" 
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this author? Their articles will remain but lose author association.')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h3><?= count($authors) ?></h3>
                        <p class="mb-0">Total Authors</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h3><?= count(array_filter($authors, fn($a) => $a['status'] === 'active')) ?></h3>
                        <p class="mb-0">Active</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h3><?= array_sum(array_column($authors, 'pending_articles')) ?></h3>
                        <p class="mb-0">Pending Articles</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h3><?= array_sum(array_column($authors, 'total_articles')) ?></h3>
                        <p class="mb-0">Total Articles</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
