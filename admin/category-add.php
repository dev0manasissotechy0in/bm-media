<?php
/**
 * Add New Category
 */

require_once 'auth_check.php';

$page_title = 'Add Category';

$db = Database::getInstance();

$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $order_id = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $icon_type = $_POST['icon_type'] ?? 'file';
    $icon_class = trim($_POST['icon_class'] ?? '');
    
    // SEO fields
    $seo_title = trim($_POST['seo_title'] ?? '');
    $seo_description = trim($_POST['seo_description'] ?? '');
    $seo_keywords = trim($_POST['seo_keywords'] ?? '');
    
    // Store form data for repopulation
    $form_data = $_POST;
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Category name is required';
    }
    
    if (empty($slug)) {
        $slug = strtolower(str_replace(' ', '-', $name));
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    }
    
    // Check slug uniqueness
    $existing = $db->fetchOne("SELECT id FROM categories WHERE slug = ?", [$slug]);
    if ($existing) {
        $errors[] = 'Slug already exists. Please use a different slug.';
    }
    
    // Handle icon - either file or class
    // Handle icon - either file or class
    $icon = null;
    if ($icon_type === 'class' && !empty($icon_class)) {
        // Store the icon class name prefixed with 'class:'
        $icon = 'class:' . $icon_class;
    } elseif ($icon_type === 'file' && !empty($_FILES['icon']['name'])) {
        $icon_info = pathinfo($_FILES['icon']['name']);
        $icon_ext = strtolower($icon_info['extension']);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico'];
        
        if (in_array($icon_ext, $allowed)) {
            $icon = time() . '_icon.' . $icon_ext;
            $upload_path = UPLOAD_CATEGORY_PATH . '/' . $icon;
            
            if (!move_uploaded_file($_FILES['icon']['tmp_name'], $upload_path)) {
                $errors[] = 'Failed to upload icon';
            }
        } else {
            $errors[] = 'Invalid icon format. Allowed: ' . implode(', ', $allowed);
        }
    }
    
    // Handle logo upload
    $logo = null;
    if (!empty($_FILES['logo']['name'])) {
        $logo_info = pathinfo($_FILES['logo']['name']);
        $logo_ext = strtolower($logo_info['extension']);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        
        if (in_array($logo_ext, $allowed)) {
            $logo = time() . '_logo.' . $logo_ext;
            $upload_path = UPLOAD_CATEGORY_PATH . '/' . $logo;
            
            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $errors[] = 'Failed to upload logo';
            }
        } else {
            $errors[] = 'Invalid logo format. Allowed: ' . implode(', ', $allowed);
        }
    }
    
    if (empty($errors)) {
        // Insert category
        $data = [
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $parent_id,
            'icon' => $icon,
            'logo' => $logo,
            'order_id' => $order_id,
            'status' => $status,
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'seo_keywords' => $seo_keywords,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $category_id = $db->insert('categories', $data);
        
        if ($category_id) {
            $_SESSION['success'] = 'Category added successfully';
            header('Location: categories.php');
            exit;
        } else {
            $errors[] = 'Failed to create category';
        }
    }
}

// Get parent categories for dropdown
$parent_categories = $db->fetchAll("SELECT id, name FROM categories WHERE parent_id IS NULL AND status = 'active' ORDER BY name ASC");

// Get max order for default value
$max_order = $db->fetchOne("SELECT MAX(order_id) as max_order FROM categories");
$default_order = ($max_order['max_order'] ?? 0) + 1;

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-plus-circle"></i> Add New Category
            </h1>
            <a href="categories.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Categories
            </a>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h5>Please fix the following errors:</h5>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <!-- Left Column -->
                <div class="col-md-8">
                    <!-- Basic Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?= htmlspecialchars($form_data['name'] ?? '') ?>"
                                       placeholder="Enter category name">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Slug <span class="text-danger">*</span></label>
                                <input type="text" name="slug" class="form-control"
                                       value="<?= htmlspecialchars($form_data['slug'] ?? '') ?>"
                                       placeholder="category-slug (auto-generated if empty)">
                                <small class="text-muted">URL-friendly version of the name. Leave empty for auto-generation.</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Parent Category</label>
                                    <select name="parent_id" class="form-select">
                                        <option value="">None (Main Category)</option>
                                        <?php foreach ($parent_categories as $parent): ?>
                                        <option value="<?= $parent['id'] ?>" <?= ($form_data['parent_id'] ?? '') == $parent['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($parent['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Select a parent to make this a subcategory</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Display Order</label>
                                    <input type="number" name="order_id" class="form-control" min="0"
                                           value="<?= htmlspecialchars($form_data['order_id'] ?? $default_order) ?>">
                                    <small class="text-muted">Lower numbers appear first</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-search"></i> SEO Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">SEO Title</label>
                                <input type="text" name="seo_title" class="form-control" maxlength="255"
                                       value="<?= htmlspecialchars($form_data['seo_title'] ?? '') ?>"
                                       placeholder="Custom title for search engines">
                                <small class="text-muted">Leave empty to use category name. Max 60 characters recommended.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">SEO Description</label>
                                <textarea name="seo_description" class="form-control" rows="3" maxlength="500"
                                          placeholder="Description for search engines"><?= htmlspecialchars($form_data['seo_description'] ?? '') ?></textarea>
                                <small class="text-muted">Max 160 characters recommended for search results.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">SEO Keywords</label>
                                <input type="text" name="seo_keywords" class="form-control"
                                       value="<?= htmlspecialchars($form_data['seo_keywords'] ?? '') ?>"
                                       placeholder="keyword1, keyword2, keyword3">
                                <small class="text-muted">Comma-separated keywords for this category</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-md-4">
                    <!-- Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= ($form_data['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($form_data['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Category Icon -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-image"></i> Category Icon</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Icon Type</label>
                                <select name="icon_type" id="iconType" class="form-select">
                                    <option value="file">Upload File/Image</option>
                                    <option value="class">Icon Class (Bootstrap/Font Awesome)</option>
                                </select>
                            </div>
                            
                            <!-- File Upload Option -->
                            <div id="iconFileOption" class="mb-3">
                                <label class="form-label">Upload Icon File</label>
                                <input type="file" name="icon" class="form-control" accept="image/*">
                                <small class="text-muted">Recommended: 40x40px, PNG/SVG/ICO</small>
                                <div id="icon-preview" class="mt-2"></div>
                            </div>
                            
                            <!-- Icon Class Option -->
                            <div id="iconClassOption" class="mb-3" style="display: none;">
                                <label class="form-label">Icon Class Name</label>
                                <input type="text" name="icon_class" class="form-control" 
                                       placeholder="e.g., bi bi-newspaper or fa fa-news">
                                <small class="text-muted">
                                    Examples:<br>
                                    • Bootstrap: <code>bi bi-house</code><br>
                                    • Font Awesome: <code>fa fa-home</code>
                                </small>
                                <div class="mt-2">
                                    <a href="https://icons.getbootstrap.com/" target="_blank" class="btn btn-sm btn-outline-primary">Bootstrap Icons</a>
                                    <a href="https://fontawesome.com/icons" target="_blank" class="btn btn-sm btn-outline-primary">Font Awesome</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category Logo -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-image"></i> Category Logo</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Upload Logo (Large)</label>
                                <input type="file" name="logo" class="form-control" accept="image/*">
                                <small class="text-muted">Recommended: 200x200px, PNG/SVG</small>
                            </div>
                            <div id="logo-preview"></div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Create Category
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle between file upload and icon class
document.getElementById('iconType').addEventListener('change', function() {
    const iconFileOption = document.getElementById('iconFileOption');
    const iconClassOption = document.getElementById('iconClassOption');
    
    if (this.value === 'file') {
        iconFileOption.style.display = 'block';
        iconClassOption.style.display = 'none';
    } else {
        iconFileOption.style.display = 'none';
        iconClassOption.style.display = 'block';
    }
});

// Image preview functionality
const iconInput = document.querySelector('input[name="icon"]');
if (iconInput) {
    iconInput.addEventListener('change', function(e) {
        previewImage(e.target, 'icon-preview');
    });
}

const logoInput = document.querySelector('input[name="logo"]');
if (logoInput) {
    logoInput.addEventListener('change', function(e) {
        previewImage(e.target, 'logo-preview');
    });
}

function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    preview.innerHTML = '';
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width: 150px;">`;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Auto-generate slug from name
document.querySelector('input[name="name"]').addEventListener('input', function(e) {
    const slugInput = document.querySelector('input[name="slug"]');
    if (!slugInput.value || slugInput.dataset.auto !== 'false') {
        let slug = e.target.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
        slugInput.value = slug;
    }
});

document.querySelector('input[name="slug"]').addEventListener('input', function() {
    this.dataset.auto = 'false';
});
</script>

<?php include 'includes/footer.php'; ?>
