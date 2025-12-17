<?php
/**
 * Category Management System
 */

require_once 'auth_check.php';

$page_title = 'Category Management';

$db = Database::getInstance();

// Handle AJAX requests for drag & drop ordering
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    header('Content-Type: application/json');
    $order_data = json_decode($_POST['order_data'], true);
    
    try {
        foreach ($order_data as $item) {
            $db->query("UPDATE categories SET order_id = ? WHERE id = ?", [$item['order'], $item['id']]);
        }
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Check if category has articles
    $has_articles = $db->count('articles', 'category_id = ?', [$id]);
    
    if ($has_articles > 0) {
        $_SESSION['error'] = "Cannot delete category with {$has_articles} articles. Move articles first.";
    } else {
        $db->query("DELETE FROM categories WHERE id = ?", [$id]);
        $_SESSION['success'] = 'Category deleted successfully';
    }
    
    header('Location: categories.php');
    exit;
}

// Handle Status Toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $category = $db->fetchOne("SELECT status FROM categories WHERE id = ?", [$id]);
    $new_status = $category['status'] === 'active' ? 'inactive' : 'active';
    
    $db->query("UPDATE categories SET status = ? WHERE id = ?", [$new_status, $id]);
    $_SESSION['success'] = 'Category status updated';
    
    header('Location: categories.php');
    exit;
}

// Get all categories with parent info
$categories = $db->fetchAll("
    SELECT c.*, 
           p.name as parent_name,
           (SELECT COUNT(*) FROM articles WHERE category_id = c.id) as article_count
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.id
    ORDER BY c.order_id ASC, c.name ASC
");

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-folder"></i> Category Management
            </h1>
            <div class="d-flex gap-2">
                <div class="input-group" style="width: 300px;">
                    <input type="text" class="form-control" id="categorySearchInput" placeholder="Search categories...">
                    <button class="btn btn-outline-secondary" type="button" id="categorySearchBtn">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                <a href="category-add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New Category
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <!-- Categories Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Categories</h5>
                <small class="text-muted">Drag and drop rows to reorder categories</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="categoriesTable">
                        <thead class="table-light">
                            <tr>
                                <th width="30"><i class="bi bi-grip-vertical"></i></th>
                                <th width="50">Order</th>
                                <th width="80">Icon</th>
                                <th width="80">Logo</th>
                                <th>Category Name</th>
                                <th>Slug</th>
                                <th>Parent Category</th>
                                <th width="100">Articles</th>
                                <th width="100">Status</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sortable-categories">
                            <?php foreach ($categories as $category): ?>
                            <tr data-id="<?= $category['id'] ?>" data-order="<?= $category['order_id'] ?>">
                                <td class="drag-handle" style="cursor: move;">
                                    <i class="bi bi-grip-vertical"></i>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= $category['order_id'] ?></span>
                                </td>
                                <td>
                                    <?php if ($category['icon']): ?>
                                    <img src="<?= UPLOADS_URL ?>/categories/<?= $category['icon'] ?>" 
                                         alt="Icon" class="img-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                    <i class="bi bi-image text-muted"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($category['logo']): ?>
                                    <img src="<?= UPLOADS_URL ?>/categories/<?= $category['logo'] ?>" 
                                         alt="Logo" class="img-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                    <i class="bi bi-image text-muted"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($category['name']) ?></strong>
                                    <?php if (!empty($category['description'])): ?>
                                    <br><small class="text-muted"><?= substr(htmlspecialchars($category['description']), 0, 50) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?= htmlspecialchars($category['slug']) ?></code>
                                </td>
                                <td>
                                    <?php if ($category['parent_name']): ?>
                                    <span class="badge bg-info"><?= htmlspecialchars($category['parent_name']) ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">Main Category</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary"><?= $category['article_count'] ?></span>
                                </td>
                                <td>
                                    <a href="?toggle=<?= $category['id'] ?>" class="badge bg-<?= $category['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($category['status']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="category-edit.php?id=<?= $category['id'] ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/category/<?= $category['slug'] ?>" 
                                           class="btn btn-outline-info" title="View" target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button onclick="deleteCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>', <?= $category['article_count'] ?>)" 
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

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
// Initialize Sortable for drag & drop
const sortable = new Sortable(document.getElementById('sortable-categories'), {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function(evt) {
        updateOrder();
    }
});

function updateOrder() {
    const rows = document.querySelectorAll('#sortable-categories tr');
    const orderData = [];
    
    rows.forEach((row, index) => {
        orderData.push({
            id: row.dataset.id,
            order: index + 1
        });
        row.querySelector('.badge.bg-secondary').textContent = index + 1;
    });
    
    // Send AJAX request to update order
    fetch('categories.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=update_order&order_data=' + encodeURIComponent(JSON.stringify(orderData))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
            alert.style.zIndex = '9999';
            alert.innerHTML = 'Order updated successfully! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 3000);
        }
    })
    .catch(error => console.error('Error:', error));
}

function deleteCategory(id, name, articleCount) {
    if (articleCount > 0) {
        if (!confirm(`Category "${name}" has ${articleCount} articles. You need to move these articles before deleting. Continue to reassign?`)) {
            return;
        }
    } else {
        if (!confirm(`Are you sure you want to delete category "${name}"?`)) {
            return;
        }
    }
    window.location.href = '?delete=' + id;
}

// Category search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('categorySearchInput');
    const searchBtn = document.getElementById('categorySearchBtn');
    const table = document.getElementById('categoriesTable');
    const rows = table.querySelectorAll('tbody tr');
    
    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;
        
        rows.forEach(row => {
            const categoryName = row.querySelector('td:nth-child(5)')?.textContent.toLowerCase() || '';
            const slug = row.querySelector('td:nth-child(6)')?.textContent.toLowerCase() || '';
            const parentName = row.querySelector('td:nth-child(7)')?.textContent.toLowerCase() || '';
            
            if (searchTerm === '' || 
                categoryName.includes(searchTerm) || 
                slug.includes(searchTerm) || 
                parentName.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show message if no results
        const existingMsg = table.querySelector('.no-results-message');
        if (existingMsg) existingMsg.remove();
        
        if (visibleCount === 0) {
            const tbody = table.querySelector('tbody');
            const tr = document.createElement('tr');
            tr.className = 'no-results-message';
            tr.innerHTML = '<td colspan=\"10\" class=\"text-center text-muted py-4\"><i class=\"bi bi-search\"></i> No categories found matching \"' + searchInput.value + '\"</td>';
            tbody.appendChild(tr);
        }
    }
    
    // Search on button click
    searchBtn.addEventListener('click', performSearch);
    
    // Search on Enter key
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        } else {
            // Live search as user types
            performSearch();
        }
    });
    
    // Clear search on input clear
    searchInput.addEventListener('input', function() {
        if (this.value === '') {
            performSearch();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
