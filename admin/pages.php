<?php
/**
 * Custom Pages Management
 */

require_once 'auth_check.php';

$page_title = 'Custom Pages';

$db = Database::getInstance();

// Check and add show_in_app column if it doesn't exist
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM custom_pages LIKE 'show_in_app'");
    if (empty($columns)) {
        // Column doesn't exist, add it
        $db->query("ALTER TABLE custom_pages ADD COLUMN show_in_app BOOLEAN DEFAULT FALSE AFTER show_in_footer");
        $db->query("ALTER TABLE custom_pages ADD INDEX idx_show_in_app (show_in_app)");
        
        // Update common pages
        $db->query("
            UPDATE custom_pages 
            SET show_in_app = TRUE 
            WHERE slug IN ('about-us', 'privacy-policy', 'terms-and-conditions', 'terms-conditions', 'contact-us', 'disclaimer', 'cookie-policy')
            AND status = 'published'
        ");
    }
} catch (Exception $e) {
    // Column check failed, continue anyway
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM custom_pages WHERE id = ?", [$id]);
    $_SESSION['success'] = 'Page deleted successfully';
    header('Location: pages.php');
    exit;
}

// Handle toggle footer visibility
if (isset($_GET['toggle_footer'])) {
    $id = (int)$_GET['toggle_footer'];
    $page = $db->fetchOne("SELECT show_in_footer FROM custom_pages WHERE id = ?", [$id]);
    if ($page) {
        $new_value = $page['show_in_footer'] ? 0 : 1;
        $db->update('custom_pages', ['show_in_footer' => $new_value], 'id = ?', [$id]);
        $_SESSION['success'] = 'Footer visibility updated';
    }
    header('Location: pages.php');
    exit;
}

// Handle toggle app visibility
if (isset($_GET['toggle_app'])) {
    $id = (int)$_GET['toggle_app'];
    $page = $db->fetchOne("SELECT show_in_app FROM custom_pages WHERE id = ?", [$id]);
    if ($page) {
        $new_value = $page['show_in_app'] ? 0 : 1;
        $db->update('custom_pages', ['show_in_app' => $new_value], 'id = ?', [$id]);
        $_SESSION['success'] = 'App visibility updated';
    }
    header('Location: pages.php');
    exit;
}

// Get all pages
$pages = $db->fetchAll("SELECT * FROM custom_pages ORDER BY order_id ASC, title ASC");

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-file-text"></i> Custom Pages
            </h1>
            <a href="page-add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Page
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Pages Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Title</th>
                                <th>Slug</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Show in Footer</th>
                                <th>Show in App</th>
                                <th>Views</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mt-3">No pages created yet. Click "Add New Page" to get started!</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><?= $page['order_id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($page['title']) ?></strong>
                                </td>
                                <td>
                                    <code><?= htmlspecialchars($page['slug']) ?></code>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $page['page_type'] === 'text' ? 'info' : 'primary' ?>">
                                        <?= ucfirst($page['page_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $page['status'] === 'published' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($page['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?toggle_footer=<?= $page['id'] ?>" 
                                       class="badge bg-<?= $page['show_in_footer'] ? 'success' : 'secondary' ?>">
                                        <?= $page['show_in_footer'] ? 'Yes' : 'No' ?>
                                    </a>
                                </td>
                                <td>
                                    <a href="?toggle_app=<?= $page['id'] ?>" 
                                       class="badge bg-<?= $page['show_in_app'] ? 'primary' : 'secondary' ?>">
                                        <?= $page['show_in_app'] ? 'Yes' : 'No' ?>
                                    </a>
                                </td>
                                <td><?= number_format($page['views_count']) ?></td>
                                <td>
                                    <small><?= date('M d, Y', strtotime($page['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="page-edit.php?id=<?= $page['id'] ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/page/<?= $page['slug'] ?>" 
                                           class="btn btn-outline-info" title="View" target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button onclick="deletePage(<?= $page['id'] ?>, '<?= htmlspecialchars(addslashes($page['title'])) ?>')" 
                                                class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
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

        <!-- Info Box -->
        <div class="<strong>Show in Footer:</strong> Pages will appear in the website footer navigation</li>
                <li><strong>Show in App:</strong> Pages will appear in the mobile app profile section</li>
                <li>Use Order ID to control the sequence of pages in footer/app</li>
                <li>Click on the badges to quickly toggle visibility
            <ul class="mb-0">
                <li>Create static pages like About Us, Privacy Policy, Terms & Conditions</li>
                <li>Choose "Text" type for simple content pages</li>
                <li>Choose "Category Articles" to show articles from a specific category</li>
                <li>Pages with "Show in Footer" enabled will appear in the website footer</li>
                <li>Use Order ID to control the sequence of pages in footer</li>
            </ul>
        </div>
    </div>
</div>

<script>
function deletePage(id, title) {
    if (confirm('Are you sure you want to delete the page "' + title + '"?\n\nThis action cannot be undone.')) {
        window.location.href = 'pages.php?delete=' + id;
    }
}
</script>

<?php include 'includes/footer.php'; ?>