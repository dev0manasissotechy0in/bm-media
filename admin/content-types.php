<?php
/**
 * Content Types Management
 * Manage article content types (News, Opinion, Feature, Interview, etc.)
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $color = trim($_POST['color'] ?? '#000000');
        $template = trim($_POST['template'] ?? 'default');
        $status = $_POST['status'] ?? 'active';
        
        // Validation
        if (empty($name)) {
            $errors[] = 'Content type name is required';
        }
        if (empty($slug)) {
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $name)));
        }
        
        // Check if slug already exists
        $existing = $db->fetchOne("SELECT * FROM content_types WHERE slug = ?", [$slug]);
        if ($existing) {
            $errors[] = 'A content type with this slug already exists';
        }
        
        if (empty($errors)) {
            $data = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'icon' => $icon,
                'color' => $color,
                'template' => $template,
                'status' => $status,
                'settings' => json_encode([
                    'show_author' => $_POST['show_author'] ?? 1,
                    'show_date' => $_POST['show_date'] ?? 1,
                    'show_category' => $_POST['show_category'] ?? 1,
                    'show_tags' => $_POST['show_tags'] ?? 1,
                    'show_share' => $_POST['show_share'] ?? 1,
                    'show_comments' => $_POST['show_comments'] ?? 1,
                    'allow_featured' => $_POST['allow_featured'] ?? 1,
                    'require_featured_image' => $_POST['require_featured_image'] ?? 0,
                    'min_word_count' => intval($_POST['min_word_count'] ?? 0),
                    'max_word_count' => intval($_POST['max_word_count'] ?? 0)
                ])
            ];
            
            $db->insert('content_types', $data);
            $success = 'Content type added successfully';
        }
    }
    elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $color = trim($_POST['color'] ?? '#000000');
        $template = trim($_POST['template'] ?? 'default');
        $status = $_POST['status'] ?? 'active';
        
        if (empty($name)) {
            $errors[] = 'Content type name is required';
        }
        
        // Check if slug exists for different ID
        $existing = $db->fetchOne("SELECT * FROM content_types WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($existing) {
            $errors[] = 'A content type with this slug already exists';
        }
        
        if (empty($errors)) {
            $data = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'icon' => $icon,
                'color' => $color,
                'template' => $template,
                'status' => $status,
                'settings' => json_encode([
                    'show_author' => $_POST['show_author'] ?? 1,
                    'show_date' => $_POST['show_date'] ?? 1,
                    'show_category' => $_POST['show_category'] ?? 1,
                    'show_tags' => $_POST['show_tags'] ?? 1,
                    'show_share' => $_POST['show_share'] ?? 1,
                    'show_comments' => $_POST['show_comments'] ?? 1,
                    'allow_featured' => $_POST['allow_featured'] ?? 1,
                    'require_featured_image' => $_POST['require_featured_image'] ?? 0,
                    'min_word_count' => intval($_POST['min_word_count'] ?? 0),
                    'max_word_count' => intval($_POST['max_word_count'] ?? 0)
                ])
            ];
            
            $db->update('content_types', $data, 'id = ?', [$id]);
            $success = 'Content type updated successfully';
        }
    }
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        // Check if content type is in use
        $article_count = $db->fetchColumn("SELECT COUNT(*) FROM articles WHERE content_type_id = ?", [$id]);
        
        if ($article_count > 0) {
            $errors[] = "Cannot delete content type. It is used by {$article_count} article(s). Please reassign or delete those articles first.";
        } else {
            $db->delete('content_types', 'id = ?', [$id]);
            $success = 'Content type deleted successfully';
        }
    }
    elseif ($action === 'reorder') {
        $orders = json_decode($_POST['orders'] ?? '[]', true);
        foreach ($orders as $item) {
            $db->update('content_types', 
                ['display_order' => $item['order']], 
                'id = ?', 
                [$item['id']]
            );
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

// Get all content types
$content_types = $db->fetchAll(
    "SELECT ct.*, 
     (SELECT COUNT(*) FROM articles WHERE content_type_id = ct.id) as article_count
     FROM content_types ct
     ORDER BY ct.display_order ASC, ct.name ASC"
);

$page_title = 'Content Types';
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-file-earmark-text"></i> Content Types</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg"></i> Add Content Type
        </button>
    </div>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <button class="btn-close" data-bs-dismiss="alert"></button>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <button class="btn-close" data-bs-dismiss="alert"></button>
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle"></i> About Content Types
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        Content types allow you to categorize articles by their format and purpose. 
                        Each type can have custom settings, templates, and display options.
                    </p>
                    <p class="mb-0">
                        <strong>Examples:</strong> News (breaking stories), Opinion (editorials), Feature (long-form), 
                        Interview (Q&A format), Review (product/book/movie reviews), Analysis (deep-dive), etc.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="contentTypesTable">
                    <thead>
                        <tr>
                            <th width="50">Order</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Template</th>
                            <th>Articles</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sortableTypes">
                        <?php foreach ($content_types as $type): ?>
                        <?php
                        $settings = json_decode($type['settings'], true) ?? [];
                        ?>
                        <tr data-id="<?= $type['id'] ?>">
                            <td class="text-center">
                                <i class="bi bi-grip-vertical handle" style="cursor: move;"></i>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($type['icon']): ?>
                                    <i class="<?= htmlspecialchars($type['icon']) ?> me-2" 
                                       style="color: <?= htmlspecialchars($type['color']) ?>; font-size: 1.2rem;"></i>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($type['name']) ?></strong>
                                        <?php if ($type['description']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($type['description']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><code><?= htmlspecialchars($type['slug']) ?></code></td>
                            <td>
                                <span class="badge bg-info"><?= htmlspecialchars($type['template']) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?= number_format($type['article_count']) ?> articles</span>
                            </td>
                            <td>
                                <?php if ($type['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" 
                                        onclick="editContentType(<?= htmlspecialchars(json_encode($type)) ?>)"
                                        data-bs-toggle="modal" data-bs-target="#editModal">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                
                                <form method="post" class="d-inline" 
                                      onsubmit="return confirm('Delete this content type? <?= $type['article_count'] > 0 ? 'Warning: ' . $type['article_count'] . ' articles use this type!' : '' ?>')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $type['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            <?= $type['article_count'] > 0 ? 'disabled title="Cannot delete - in use"' : '' ?>>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($content_types)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">No content types yet. Click "Add Content Type" to create one.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Content Type Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add Content Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g., News, Opinion, Feature">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" placeholder="Auto-generated if empty">
                            <small class="text-muted">URL-friendly identifier (lowercase, dashes)</small>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" 
                                      placeholder="Brief description of this content type"></textarea>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Icon (CSS Class)</label>
                            <input type="text" name="icon" class="form-control" placeholder="bi bi-newspaper">
                            <small class="text-muted">Bootstrap Icons class</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" class="form-control form-control-color" value="#007bff">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Template</label>
                            <select name="template" class="form-select">
                                <option value="default">Default</option>
                                <option value="feature">Feature (Long-form)</option>
                                <option value="interview">Interview (Q&A)</option>
                                <option value="review">Review (Rating)</option>
                                <option value="gallery">Gallery (Photo-heavy)</option>
                                <option value="video">Video (Embedded)</option>
                                <option value="timeline">Timeline (Chronological)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Word Count Limits</label>
                            <div class="input-group">
                                <input type="number" name="min_word_count" class="form-control" placeholder="Min" min="0">
                                <span class="input-group-text">to</span>
                                <input type="number" name="max_word_count" class="form-control" placeholder="Max" min="0">
                            </div>
                            <small class="text-muted">0 = no limit</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Display Settings</h6>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="show_author" class="form-check-input" id="show_author_add" value="1" checked>
                                <label class="form-check-label" for="show_author_add">Show Author</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="show_date" class="form-check-input" id="show_date_add" value="1" checked>
                                <label class="form-check-label" for="show_date_add">Show Date</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="show_category" class="form-check-input" id="show_category_add" value="1" checked>
                                <label class="form-check-label" for="show_category_add">Show Category</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="show_tags" class="form-check-input" id="show_tags_add" value="1" checked>
                                <label class="form-check-label" for="show_tags_add">Show Tags</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="show_share" class="form-check-input" id="show_share_add" value="1" checked>
                                <label class="form-check-label" for="show_share_add">Show Share Buttons</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="show_comments" class="form-check-input" id="show_comments_add" value="1" checked>
                                <label class="form-check-label" for="show_comments_add">Allow Comments</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="allow_featured" class="form-check-input" id="allow_featured_add" value="1" checked>
                                <label class="form-check-label" for="allow_featured_add">Allow Featured Articles</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="require_featured_image" class="form-check-input" id="require_featured_image_add" value="1">
                                <label class="form-check-label" for="require_featured_image_add">Require Featured Image</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Content Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Content Type Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Content Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" id="edit_slug" class="form-control">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Icon</label>
                            <input type="text" name="icon" id="edit_icon" class="form-control">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Color</label>
                            <input type="color" name="color" id="edit_color" class="form-control form-control-color">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Template</label>
                            <select name="template" id="edit_template" class="form-select">
                                <option value="default">Default</option>
                                <option value="feature">Feature (Long-form)</option>
                                <option value="interview">Interview (Q&A)</option>
                                <option value="review">Review (Rating)</option>
                                <option value="gallery">Gallery (Photo-heavy)</option>
                                <option value="video">Video (Embedded)</option>
                                <option value="timeline">Timeline (Chronological)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Word Count Limits</label>
                            <div class="input-group">
                                <input type="number" name="min_word_count" id="edit_min_word_count" class="form-control" placeholder="Min" min="0">
                                <span class="input-group-text">to</span>
                                <input type="number" name="max_word_count" id="edit_max_word_count" class="form-control" placeholder="Max" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3">Display Settings</h6>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="show_author" class="form-check-input" id="edit_show_author" value="1">
                                <label class="form-check-label" for="edit_show_author">Show Author</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="show_date" class="form-check-input" id="edit_show_date" value="1">
                                <label class="form-check-label" for="edit_show_date">Show Date</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="show_category" class="form-check-input" id="edit_show_category" value="1">
                                <label class="form-check-label" for="edit_show_category">Show Category</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="show_tags" class="form-check-input" id="edit_show_tags" value="1">
                                <label class="form-check-label" for="edit_show_tags">Show Tags</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="show_share" class="form-check-input" id="edit_show_share" value="1">
                                <label class="form-check-label" for="edit_show_share">Show Share Buttons</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="show_comments" class="form-check-input" id="edit_show_comments" value="1">
                                <label class="form-check-label" for="edit_show_comments">Allow Comments</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="allow_featured" class="form-check-input" id="edit_allow_featured" value="1">
                                <label class="form-check-label" for="edit_allow_featured">Allow Featured Articles</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="require_featured_image" class="form-check-input" id="edit_require_featured_image" value="1">
                                <label class="form-check-label" for="edit_require_featured_image">Require Featured Image</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Content Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sortable.js for drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// Edit content type
function editContentType(type) {
    const settings = JSON.parse(type.settings || '{}');
    
    document.getElementById('edit_id').value = type.id;
    document.getElementById('edit_name').value = type.name;
    document.getElementById('edit_slug').value = type.slug;
    document.getElementById('edit_description').value = type.description || '';
    document.getElementById('edit_icon').value = type.icon || '';
    document.getElementById('edit_color').value = type.color || '#000000';
    document.getElementById('edit_template').value = type.template || 'default';
    document.getElementById('edit_status').value = type.status;
    
    // Settings checkboxes
    document.getElementById('edit_show_author').checked = settings.show_author ?? true;
    document.getElementById('edit_show_date').checked = settings.show_date ?? true;
    document.getElementById('edit_show_category').checked = settings.show_category ?? true;
    document.getElementById('edit_show_tags').checked = settings.show_tags ?? true;
    document.getElementById('edit_show_share').checked = settings.show_share ?? true;
    document.getElementById('edit_show_comments').checked = settings.show_comments ?? true;
    document.getElementById('edit_allow_featured').checked = settings.allow_featured ?? true;
    document.getElementById('edit_require_featured_image').checked = settings.require_featured_image ?? false;
    
    // Word counts
    document.getElementById('edit_min_word_count').value = settings.min_word_count || '';
    document.getElementById('edit_max_word_count').value = settings.max_word_count || '';
}

// Drag & drop reordering
const sortable = new Sortable(document.getElementById('sortableTypes'), {
    handle: '.handle',
    animation: 150,
    onEnd: function(evt) {
        const rows = document.querySelectorAll('#sortableTypes tr');
        const orders = [];
        
        rows.forEach((row, index) => {
            orders.push({
                id: row.dataset.id,
                order: index + 1
            });
        });
        
        // Send to server
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=reorder&orders=' + encodeURIComponent(JSON.stringify(orders))
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
