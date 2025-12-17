<?php
/**
 * Manage Author Permissions
 */

require_once 'auth_check.php';

$page_title = 'Author Permissions';
$db = Database::getInstance();

$author_id = (int)($_GET['id'] ?? 0);
$author = $db->fetchOne("SELECT * FROM authors WHERE id = ?", [$author_id]);

if (!$author) {
    Session::setFlash('error', 'Author not found.', 'danger');
    redirect('authors.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $permissions = $_POST['permissions'] ?? [];
    
    if ($db->update('authors', ['permissions' => json_encode($permissions)], 'id = ?', [$author_id])) {
        Session::setFlash('success', 'Permissions updated successfully!', 'success');
        redirect('authors.php');
    } else {
        Session::setFlash('error', 'Failed to update permissions.', 'danger');
    }
}

$current_permissions = json_decode($author['permissions'] ?? '[]', true) ?: [];

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-shield-lock-fill"></i> Author Permissions
            </h1>
            <a href="authors.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Authors
            </a>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <?php if (!empty($author['profile_photo'])): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($author['profile_photo']) ?>" 
                             alt="<?= htmlspecialchars($author['full_name']) ?>" 
                             class="rounded-circle mb-3" 
                             style="width: 100px; height: 100px; object-fit: cover;">
                        <?php else: ?>
                        <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3" 
                             style="width: 100px; height: 100px;">
                            <i class="bi bi-person text-white" style="font-size: 2.5rem;"></i>
                        </div>
                        <?php endif; ?>
                        
                        <h5><?= htmlspecialchars($author['full_name']) ?></h5>
                        <p class="text-muted mb-2"><?= htmlspecialchars($author['email']) ?></p>
                        
                        <?php if ($author['status'] === 'active'): ?>
                        <span class="badge bg-success">Active</span>
                        <?php elseif ($author['status'] === 'suspended'): ?>
                        <span class="badge bg-danger">Suspended</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Manage Permissions</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill"></i>
                                Select the permissions you want to grant to this author. Permissions control what actions the author can perform in their dashboard.
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-3">Article Management</h6>
                                    
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-check form-switch mb-3">
                                                <input type="checkbox" name="permissions[]" value="create_article" 
                                                       class="form-check-input" id="perm_create" 
                                                       <?= in_array('create_article', $current_permissions) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="perm_create">
                                                    <strong>Create Articles</strong>
                                                    <br><small class="text-muted">Allow author to submit new articles for approval</small>
                                                </label>
                                            </div>
                                            
                                            <div class="form-check form-switch mb-3">
                                                <input type="checkbox" name="permissions[]" value="edit_own_article" 
                                                       class="form-check-input" id="perm_edit" 
                                                       <?= in_array('edit_own_article', $current_permissions) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="perm_edit">
                                                    <strong>Edit Own Articles</strong>
                                                    <br><small class="text-muted">Allow editing of their own articles</small>
                                                </label>
                                            </div>
                                            
                                            <div class="form-check form-switch">
                                                <input type="checkbox" name="permissions[]" value="delete_own_article" 
                                                       class="form-check-input" id="perm_delete" 
                                                       <?= in_array('delete_own_article', $current_permissions) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="perm_delete">
                                                    <strong>Delete Own Articles</strong>
                                                    <br><small class="text-muted">Allow deletion of their own articles (draft only)</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6 class="mb-3">Media & Content</h6>
                                    
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-check form-switch mb-3">
                                                <input type="checkbox" name="permissions[]" value="upload_media" 
                                                       class="form-check-input" id="perm_media" 
                                                       <?= in_array('upload_media', $current_permissions) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="perm_media">
                                                    <strong>Upload Media</strong>
                                                    <br><small class="text-muted">Allow uploading images and files</small>
                                                </label>
                                            </div>
                                            
                                            <div class="form-check form-switch mb-3">
                                                <input type="checkbox" name="permissions[]" value="manage_tags" 
                                                       class="form-check-input" id="perm_tags" 
                                                       <?= in_array('manage_tags', $current_permissions) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="perm_tags">
                                                    <strong>Manage Tags</strong>
                                                    <br><small class="text-muted">Allow creating and editing article tags</small>
                                                </label>
                                            </div>
                                            
                                            <div class="form-check form-switch">
                                                <input type="checkbox" name="permissions[]" value="view_analytics" 
                                                       class="form-check-input" id="perm_analytics" 
                                                       <?= in_array('view_analytics', $current_permissions) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="perm_analytics">
                                                    <strong>View Analytics</strong>
                                                    <br><small class="text-muted">Allow viewing article statistics and analytics</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="authors.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Permissions
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
