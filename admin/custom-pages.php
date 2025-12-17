<?php
/**
 * Custom Pages Management
 */

require_once 'auth_check.php';

$page_title = 'Custom Pages';

$db = Database::getInstance();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM custom_pages WHERE id = ?", [$id]);
    $_SESSION['success'] = 'Page deleted successfully';
    header('Location: custom-pages.php');
    exit;
}

// Handle Status Toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $page = $db->fetchOne("SELECT status FROM custom_pages WHERE id = ?", [$id]);
    $new_status = $page['status'] === 'published' ? 'draft' : 'published';
    
    $db->query("UPDATE custom_pages SET status = ? WHERE id = ?", [$new_status, $id]);
    $_SESSION['success'] = 'Page status updated';
    
    header('Location: custom-pages.php');
    exit;
}

// Get all custom pages
$pages = $db->fetchAll("
    SELECT * FROM custom_pages 
    ORDER BY created_at DESC
");

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-file-earmark-text"></i> Custom Pages
            </h1>
            <a href="custom-page-add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Page
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <!-- Pages Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Page Title</th>
                                <th>Slug</th>
                                <th>Page Type</th>
                                <th>Footer Visible</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Created</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><?= $page['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($page['title']) ?></strong>
                                </td>
                                <td><code><?= htmlspecialchars($page['slug']) ?></code></td>
                                <td>
                                    <span class="badge bg-info"><?= ucfirst($page['page_type']) ?></span>
                                </td>
                                <td>
                                    <?php if ($page['show_in_footer']): ?>
                                    <span class="badge bg-success">Yes</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?toggle=<?= $page['id'] ?>" class="badge bg-<?= $page['status'] === 'published' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($page['status']) ?>
                                    </a>
                                </td>
                                <td><?= number_format($page['views_count']) ?></td>
                                <td><?= date('M d, Y', strtotime($page['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="custom-page-edit.php?id=<?= $page['id'] ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/page/<?= $page['slug'] ?>" 
                                           class="btn btn-outline-info" title="View" target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button onclick="deletePage(<?= $page['id'] ?>, '<?= htmlspecialchars($page['title']) ?>')" 
                                                class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
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

<script>
function deletePage(id, title) {
    if (confirm(`Are you sure you want to delete page "${title}"?`)) {
        window.location.href = '?delete=' + id;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
