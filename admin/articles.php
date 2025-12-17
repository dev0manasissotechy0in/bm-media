<?php
/**
 * Articles Management
 */

require_once 'auth_check.php';

$page_title = 'Articles';

$db = Database::getInstance();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM articles WHERE id = ?", [$id]);
    $_SESSION['success'] = 'Article deleted successfully';
    header('Location: articles.php');
    exit;
}

// Handle Status Toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $article = $db->fetchOne("SELECT status FROM articles WHERE id = ?", [$id]);
    $new_status = $article['status'] === 'published' ? 'draft' : 'published';
    
    $db->query("UPDATE articles SET status = ? WHERE id = ?", [$new_status, $id]);
    $_SESSION['success'] = 'Article status updated';
    
    header('Location: articles.php');
    exit;
}

// Filters
$status_filter = $_GET['status'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = [];
$params = [];

if ($status_filter !== 'all') {
    $where[] = "a.status = ?";
    $params[] = $status_filter;
}

if ($category_filter !== 'all') {
    $where[] = "a.category_id = ?";
    $params[] = $category_filter;
}

if ($type_filter !== 'all') {
    switch ($type_filter) {
        case 'live':
            $where[] = "a.is_live = 1";
            break;
        case 'breaking':
            $where[] = "a.is_breaking = 1";
            break;
        case 'featured':
            $where[] = "a.is_featured = 1";
            break;
    }
}

if (!empty($search)) {
    $where[] = "(a.title LIKE ? OR a.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM articles a $where_clause";
$total_result = $db->fetchOne($count_query, $params);
$total = $total_result['total'];
$total_pages = ceil($total / $per_page);

// Get articles
$articles = $db->fetchAll("
    SELECT a.*, c.name as category_name, c.slug as category_slug,
           CASE 
               WHEN a.author_type = 'admin' THEN ad.full_name
               WHEN a.author_type = 'reporter' THEN r.full_name
               ELSE 'Unknown'
           END as author_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN admin_users ad ON a.author_id = ad.id AND a.author_type = 'admin'
    LEFT JOIN reporters r ON a.author_id = r.id AND a.author_type = 'reporter'
    $where_clause
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
", array_merge($params, [$per_page, $offset]));

// Get categories for filter
$categories = $db->fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");

// Get authors for bulk actions
$admin_users = $db->fetchAll("SELECT id, full_name FROM admin_users ORDER BY full_name ASC");
$reporters = $db->fetchAll("SELECT id, full_name FROM reporters ORDER BY full_name ASC");

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-newspaper"></i> Articles Management
            </h1>
            <a href="article-add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Article
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <!-- Bulk Actions Bar -->
        <div id="bulkActionsBar" class="card mb-4" style="display: none;">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <strong><span id="selectedCount">0</span> article(s) selected</strong>
                    </div>
                    <div class="col-md-3">
                        <select id="bulkAction" class="form-select">
                            <option value="">-- Select Action --</option>
                            <optgroup label="Status">
                                <option value="publish">Publish</option>
                                <option value="draft">Move to Draft</option>
                                <option value="archive">Archive</option>
                            </optgroup>
                            <optgroup label="Features">
                                <option value="feature">Mark as Featured</option>
                                <option value="unfeature">Remove Featured</option>
                                <option value="breaking">Mark as Breaking</option>
                                <option value="unbreaking">Remove Breaking</option>
                            </optgroup>
                            <optgroup label="Modify">
                                <option value="category">Change Category</option>
                                <option value="author">Change Author</option>
                            </optgroup>
                            <optgroup label="Danger">
                                <option value="delete" class="text-danger">Delete</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-3" id="categorySelector" style="display: none;">
                        <select id="bulkCategory" class="form-select">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4" id="authorSelector" style="display: none;">
                        <div class="row">
                            <div class="col-6">
                                <select id="bulkAuthorType" class="form-select">
                                    <option value="">-- Type --</option>
                                    <option value="admin">Admin</option>
                                    <option value="reporter">Reporter</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select id="bulkAuthor" class="form-select" disabled>
                                    <option value="">-- Select Author --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="btn-group">
                            <button type="button" onclick="applyBulkAction(event)" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Apply
                            </button>
                            <button type="button" onclick="clearSelection()" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search articles..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="archived" <?= $status_filter === 'archived' ? 'selected' : '' ?>>Archived</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="all" <?= $type_filter === 'all' ? 'selected' : '' ?>>All Types</option>
                            <option value="live" <?= $type_filter === 'live' ? 'selected' : '' ?>>Live</option>
                            <option value="breaking" <?= $type_filter === 'breaking' ? 'selected' : '' ?>>Breaking</option>
                            <option value="featured" <?= $type_filter === 'featured' ? 'selected' : '' ?>>Featured</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6>Total Articles</h6>
                        <h3><?= number_format($total) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6>Published</h6>
                        <h3><?= number_format($db->count('articles', 'status = ?', ['published'])) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6>Drafts</h6>
                        <h3><?= number_format($db->count('articles', 'status = ?', ['draft'])) ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6>Live Articles</h6>
                        <h3><?= number_format($db->count('articles', 'is_live = ?', [1])) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Articles Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAll" class="form-check-input" 
                                           onchange="toggleSelectAll(this)">
                                </th>
                                <th width="60">ID</th>
                                <th width="80">Thumbnail</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Author</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Date</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($articles)): ?>
                            <tr>
                                <td colspan="11" class="text-center py-4">
                                    <i class="bi bi-inbox fs-1 text-muted"></i>
                                    <p class="text-muted mt-2">No articles found</p>
                                    <a href="article-add.php" class="btn btn-primary">
                                        <i class="bi bi-plus-circle"></i> Add Your First Article
                                    </a>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($articles as $article): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input article-checkbox" 
                                           value="<?= $article['id'] ?>" onchange="updateBulkActions()">
                                </td>
                                <td><?= $article['id'] ?></td>
                                <td>
                                    <?php if ($article['thumbnail']): ?>
                                    <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" 
                                         alt="" class="img-thumbnail" style="width: 60px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                    <div class="bg-light text-center" style="width: 60px; height: 40px; line-height: 40px;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars(substr($article['title'], 0, 60)) ?><?= strlen($article['title']) > 60 ? '...' : '' ?></strong>
                                    <?php if ($article['is_live']): ?>
                                    <span class="badge bg-danger ms-2">LIVE</span>
                                    <?php endif; ?>
                                    <?php if ($article['is_breaking']): ?>
                                    <span class="badge bg-warning ms-2">BREAKING</span>
                                    <?php endif; ?>
                                    <?php if ($article['is_featured']): ?>
                                    <span class="badge bg-info ms-2">FEATURED</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($article['category_name']): ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($article['category_name']) ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($article['author_name'] ?? 'N/A') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark"><?= ucfirst($article['content_type']) ?></span>
                                </td>
                                <td>
                                    <a href="?toggle=<?= $article['id'] ?>&<?= http_build_query($_GET) ?>" 
                                       class="badge bg-<?= $article['status'] === 'published' ? 'success' : ($article['status'] === 'draft' ? 'warning' : 'secondary') ?>">
                                        <?= ucfirst($article['status']) ?>
                                    </a>
                                </td>
                                <td><?= number_format($article['views_count']) ?></td>
                                <td>
                                    <small><?= date('M d, Y', strtotime($article['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="article-edit.php?id=<?= $article['id'] ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($article['is_live']): ?>
                                        <a href="live-updates.php?article_id=<?= $article['id'] ?>" 
                                           class="btn btn-outline-danger" title="Manage Live Updates">
                                            <i class="bi bi-broadcast"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" 
                                           class="btn btn-outline-info" title="View" target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button onclick="deleteArticle(<?= $article['id'] ?>, '<?= htmlspecialchars(addslashes($article['title'])) ?>')" 
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
            
            <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>">
                                Previous
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>">
                                Next
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
<script>
// Author data for bulk actions
const adminUsers = <?= json_encode($admin_users) ?>;
const reporters = <?= json_encode($reporters) ?>;

// Toggle select all checkboxes
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.article-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkActions();
}

// Update bulk actions bar visibility
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.article-checkbox:checked');
    const count = checkboxes.length;
    const bulkBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    
    selectedCount.textContent = count;
    bulkBar.style.display = count > 0 ? 'block' : 'none';
    
    // Update select all checkbox state
    const selectAll = document.getElementById('selectAll');
    const allCheckboxes = document.querySelectorAll('.article-checkbox');
    selectAll.checked = count === allCheckboxes.length && count > 0;
    selectAll.indeterminate = count > 0 && count < allCheckboxes.length;
}

// Clear selection
function clearSelection() {
    document.querySelectorAll('.article-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    document.getElementById('bulkAction').value = '';
    updateBulkActions();
}

// Handle bulk action selection
document.getElementById('bulkAction')?.addEventListener('change', function() {
    const action = this.value;
    const categorySelector = document.getElementById('categorySelector');
    const authorSelector = document.getElementById('authorSelector');
    
    // Hide all extra selectors
    categorySelector.style.display = 'none';
    authorSelector.style.display = 'none';
    
    // Show relevant selector
    if (action === 'category') {
        categorySelector.style.display = 'block';
    } else if (action === 'author') {
        authorSelector.style.display = 'block';
    }
});

// Handle author type selection
document.getElementById('bulkAuthorType')?.addEventListener('change', function() {
    const authorType = this.value;
    const authorSelect = document.getElementById('bulkAuthor');
    
    authorSelect.innerHTML = '<option value="">-- Select Author --</option>';
    authorSelect.disabled = !authorType;
    
    if (authorType === 'admin') {
        adminUsers.forEach(user => {
            authorSelect.innerHTML += `<option value="${user.id}">${user.full_name}</option>`;
        });
    } else if (authorType === 'reporter') {
        reporters.forEach(reporter => {
            authorSelect.innerHTML += `<option value="${reporter.id}">${reporter.full_name}</option>`;
        });
    }
});

// Apply bulk action
function applyBulkAction(event) {
    const action = document.getElementById('bulkAction').value;
    if (!action) {
        alert('Please select an action');
        return;
    }
    
    const checkboxes = document.querySelectorAll('.article-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one article');
        return;
    }
    
    // Validate additional fields
    if (action === 'category') {
        const categoryId = document.getElementById('bulkCategory').value;
        if (!categoryId) {
            alert('Please select a category');
            return;
        }
    }
    
    if (action === 'author') {
        const authorType = document.getElementById('bulkAuthorType').value;
        const authorId = document.getElementById('bulkAuthor').value;
        if (!authorType || !authorId) {
            alert('Please select author type and author');
            return;
        }
    }
    
    // Confirm for delete action
    if (action === 'delete') {
        if (!confirm(`Are you sure you want to delete ${checkboxes.length} article(s)? This action cannot be undone.`)) {
            return;
        }
    }
    
    // Collect article IDs
    const articleIds = Array.from(checkboxes).map(cb => cb.value);
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', action);
    articleIds.forEach(id => formData.append('article_ids[]', id));
    
    if (action === 'category') {
        formData.append('category_id', document.getElementById('bulkCategory').value);
    }
    
    if (action === 'author') {
        formData.append('author_type', document.getElementById('bulkAuthorType').value);
        formData.append('author_id', document.getElementById('bulkAuthor').value);
    }
    
    // Show loading
    const applyBtn = event.target;
    const originalText = applyBtn.innerHTML;
    applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
    applyBtn.disabled = true;
    
    // Send request
    fetch('articles-bulk-action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'An error occurred');
                applyBtn.innerHTML = originalText;
                applyBtn.disabled = false;
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was:', text);
            alert('Server error: ' + text.substring(0, 200));
            applyBtn.innerHTML = originalText;
            applyBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request');
        applyBtn.innerHTML = originalText;
        applyBtn.disabled = false;
    });
}

function deleteArticle(id, title) {
    if (confirm(`Are you sure you want to delete article "${title}"?`)) {
        window.location.href = '?delete=' + id;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
