<?php
/**
 * Edit Author
 */

require_once 'auth_check.php';
require_once '../includes/LocationAPI.php';

$page_title = 'Edit Author';
$db = Database::getInstance();
$locationAPI = LocationAPI::getInstance();

$author_id = (int)($_GET['id'] ?? 0);
$author = $db->fetchOne("SELECT * FROM authors WHERE id = ?", [$author_id]);

if (!$author) {
    Session::setFlash('error', 'Author not found.', 'danger');
    redirect('authors.php');
}

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $country = $_POST['country'] ?? 'India';
    $state = trim($_POST['state'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $permissions = $_POST['permissions'] ?? [];
    
    // Validation
    if (empty($full_name)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    
    // Check if email exists (excluding current author)
    if ($db->exists('authors', 'email = ? AND id != ?', [$email, $author_id])) {
        $errors[] = 'Email already exists.';
    }
    
    if (empty($errors)) {
        // Handle profile photo upload
        $profile_photo = $author['profile_photo'];
        if (!empty($_FILES['profile_photo']['name'])) {
            $upload_dir = __DIR__ . '/../uploads/authors/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext)) {
                // Delete old photo
                if (!empty($author['profile_photo']) && file_exists(__DIR__ . '/../' . $author['profile_photo'])) {
                    unlink(__DIR__ . '/../' . $author['profile_photo']);
                }
                
                $new_filename = uniqid('author_') . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    $profile_photo = 'uploads/authors/' . $new_filename;
                }
            }
        }
        
        // Update data
        $update_data = [
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'profile_photo' => $profile_photo,
            'bio' => $bio,
            'country' => $country,
            'state' => $state,
            'city' => $city,
            'permissions' => json_encode($permissions),
            'status' => $status
        ];
        
        // Update password if provided
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 6) {
                $errors[] = 'Password must be at least 6 characters.';
            } elseif ($_POST['password'] !== $_POST['confirm_password']) {
                $errors[] = 'Passwords do not match.';
            } else {
                $update_data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
        }
        
        if (empty($errors)) {
            if ($db->update('authors', $update_data, 'id = ?', [$author_id])) {
                Session::setFlash('success', 'Author updated successfully!', 'success');
                redirect('authors.php');
            } else {
                $errors[] = 'Failed to update author.';
            }
        }
    }
}

$current_permissions = json_decode($author['permissions'] ?? '[]', true) ?: [];

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil-fill"></i> Edit Author
            </h1>
            <a href="authors.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Authors
            </a>
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
        
        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <h5 class="mb-3">Author Information</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?= htmlspecialchars($author['full_name']) ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?= htmlspecialchars($author['email']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" 
                                               value="<?= htmlspecialchars($author['phone'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="password" class="form-control">
                                        <small class="text-muted">Leave blank to keep current password</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Confirm Password</label>
                                        <input type="password" name="confirm_password" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Bio</label>
                                <textarea name="bio" class="form-control" rows="4"><?= htmlspecialchars($author['bio'] ?? '') ?></textarea>
                            </div>
                            
                            <h5 class="mb-3">Location</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Country <span class="text-danger">*</span></label>
                                        <select name="country" id="countrySelect" class="form-select" required>
                                            <option value="India" <?= ($author['country'] ?? 'India') === 'India' ? 'selected' : '' ?>>India</option>
                                            <option value="United States" <?= ($author['country'] ?? '') === 'United States' ? 'selected' : '' ?>>United States</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">State <span class="text-danger">*</span></label>
                                        <select name="state" id="stateSelect" class="form-select" required>
                                            <option value="">Select State</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">City <span class="text-danger">*</span></label>
                                        <select name="city" id="citySelect" class="form-select" required>
                                            <option value="">Select City</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <h5 class="mb-3">Settings</h5>
                            
                            <div class="mb-3">
                                <?php if (!empty($author['profile_photo'])): ?>
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($author['profile_photo']) ?>" 
                                     alt="<?= htmlspecialchars($author['full_name']) ?>" 
                                     class="img-thumbnail mb-2" style="max-width: 200px;">
                                <?php endif; ?>
                                <label class="form-label">Profile Photo</label>
                                <input type="file" name="profile_photo" class="form-control" accept="image/*">
                                <small class="text-muted">JPG, PNG, GIF (Max 2MB)</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= $author['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $author['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="suspended" <?= $author['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Permissions</label>
                                <div class="form-check">
                                    <input type="checkbox" name="permissions[]" value="create_article" 
                                           class="form-check-input" id="perm_create" 
                                           <?= in_array('create_article', $current_permissions) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="perm_create">Create Articles</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="permissions[]" value="edit_own_article" 
                                           class="form-check-input" id="perm_edit" 
                                           <?= in_array('edit_own_article', $current_permissions) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="perm_edit">Edit Own Articles</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="permissions[]" value="delete_own_article" 
                                           class="form-check-input" id="perm_delete" 
                                           <?= in_array('delete_own_article', $current_permissions) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="perm_delete">Delete Own Articles</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="permissions[]" value="upload_media" 
                                           class="form-check-input" id="perm_media" 
                                           <?= in_array('upload_media', $current_permissions) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="perm_media">Upload Media</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="authors.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Author
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Location data from LocationAPI
    const locationData = <?= $locationAPI->getLocationJSON() ?>;
    const countrySelect = document.getElementById('countrySelect');
    const stateSelect = document.getElementById('stateSelect');
    const citySelect = document.getElementById('citySelect');
    
    // Current values from database
    const currentCountry = '<?= htmlspecialchars($author['country'] ?? 'India') ?>';
    const currentState = '<?= htmlspecialchars($author['state'] ?? '') ?>';
    const currentCity = '<?= htmlspecialchars($author['city'] ?? '') ?>';
    
    // Update states when country changes
    countrySelect.addEventListener('change', function() {
        const country = this.value;
        stateSelect.innerHTML = '<option value="">Select State</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';
        
        if (locationData[country]) {
            Object.keys(locationData[country]).forEach(state => {
                const option = document.createElement('option');
                option.value = state;
                option.textContent = state;
                stateSelect.appendChild(option);
            });
        }
    });
    
    // Update cities when state changes
    stateSelect.addEventListener('change', function() {
        const country = countrySelect.value;
        const state = this.value;
        citySelect.innerHTML = '<option value="">Select City</option>';
        
        if (locationData[country] && locationData[country][state]) {
            locationData[country][state].forEach(city => {
                const option = document.createElement('option');
                option.value = city;
                option.textContent = city;
                citySelect.appendChild(option);
            });
        }
    });
    
    // Initialize with current values
    window.addEventListener('DOMContentLoaded', function() {
        // Trigger country change to populate states
        countrySelect.dispatchEvent(new Event('change'));
        
        // Wait for states to populate then set current state
        setTimeout(() => {
            if (currentState) {
                stateSelect.value = currentState;
                stateSelect.dispatchEvent(new Event('change'));
                
                // Wait for cities to populate then set current city
                setTimeout(() => {
                    if (currentCity) {
                        citySelect.value = currentCity;
                    }
                }, 100);
            }
        }, 100);
    });
</script>

<?php include 'includes/footer.php'; ?>
