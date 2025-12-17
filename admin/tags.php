<?php
/**
 * Tags Management
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
$errors = [];
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        
        if (empty($name)) {
            $errors[] = 'Tag name is required';
        }
        
        if (empty($slug)) {
            $slug = generateSlug($name);
        }
        
        // Check duplicate
        $existing = $db->fetchOne("SELECT id FROM tags WHERE slug = ?", [$slug]);
        if ($existing) {
            $errors[] = 'Tag slug already exists';
        }
        
        if (empty($errors)) {
            $db->insert('tags', ['name' => $name, 'slug' => $slug]);
            $success = 'Tag added successfully';
        }
    }
    
    elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        
        if (empty($name)) {
            $errors[] = 'Tag name is required';
        }
        
        if (empty($slug)) {
            $slug = generateSlug($name);
        }
        
        // Check duplicate
        $existing = $db->fetchOne("SELECT id FROM tags WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($existing) {
            $errors[] = 'Tag slug already exists';
        }
        
        if (empty($errors)) {
            $db->update('tags', ['name' => $name, 'slug' => $slug], 'id = ?', [$id]);
            $success = 'Tag updated successfully';
        }
    }
    
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Check if tag is in use
        $in_use = $db->fetchOne("SELECT COUNT(*) as count FROM article_tags WHERE tag_id = ?", [$id]);
        
        if ($in_use['count'] > 0) {
            $errors[] = 'Cannot delete tag: it is used in ' . $in_use['count'] . ' articles';
        } else {
            $db->query("DELETE FROM tags WHERE id = ?", [$id]);
            $success = 'Tag deleted successfully';
        }
    }
}

// Get all tags with article count
$tags = $db->fetchAll(
    "SELECT t.*, COUNT(DISTINCT at.article_id) as article_count
     FROM tags t
     LEFT JOIN article_tags at ON t.id = at.tag_id
     GROUP BY t.id
     ORDER BY t.name ASC"
);

$page_title = 'Manage Tags';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manage Tags</h1>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-tags me-1"></i> Add New Tag
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <input type="hidden" name="action" value="add">
                
                <div class="col-md-5">
                    <label class="form-label">Tag Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                
                <div class="col-md-5">
                    <label class="form-label">Slug (leave empty to auto-generate)</label>
                    <input type="text" name="slug" class="form-control">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Add Tag
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list me-1"></i> All Tags (<?= count($tags) ?>)
        </div>
        <div class="card-body">
            <?php if (empty($tags)): ?>
                <p class="text-muted text-center py-4">No tags found. Add your first tag above.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Articles</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tags as $tag): ?>
                        <tr>
                            <td><?= $tag['id'] ?></td>
                            <td><strong><?= htmlspecialchars($tag['name']) ?></strong></td>
                            <td><code><?= htmlspecialchars($tag['slug']) ?></code></td>
                            <td>
                                <span class="badge bg-info"><?= $tag['article_count'] ?> articles</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editTag(<?= $tag['id'] ?>, '<?= htmlspecialchars(addslashes($tag['name'])) ?>', '<?= htmlspecialchars(addslashes($tag['slug'])) ?>')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteTag(<?= $tag['id'] ?>, '<?= htmlspecialchars(addslashes($tag['name'])) ?>', <?= $tag['article_count'] ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tag Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Slug <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="editSlug" class="form-control" required>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form method="post" id="deleteForm" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
const editModal = new bootstrap.Modal(document.getElementById('editModal'));

function editTag(id, name, slug) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editSlug').value = slug;
    editModal.show();
}

function deleteTag(id, name, articleCount) {
    if (articleCount > 0) {
        if (!confirm('This tag is used in ' + articleCount + ' articles. Are you sure you want to delete "' + name + '"?')) {
            return;
        }
    } else {
        if (!confirm('Delete tag "' + name + '"?')) {
            return;
        }
    }
    
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteForm').submit();
}
</script>

<?php include 'includes/footer.php'; ?>