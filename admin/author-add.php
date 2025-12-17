<?php
/**
 * Add New Author
 */

require_once 'auth_check.php';
require_once '../includes/EmailHelper.php';
require_once '../includes/LocationAPI.php';

$page_title = 'Add New Author';
$db = Database::getInstance();
$locationAPI = LocationAPI::getInstance();

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $bio = trim($_POST['bio'] ?? '');
    $country = $_POST['country'] ?? 'India';
    $state = trim($_POST['state'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $permissions = $_POST['permissions'] ?? [];
    
    // Validation
    if (empty($full_name)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';
    
    // Check if email exists
    if ($db->exists('authors', 'email = ?', [$email])) {
        $errors[] = 'Email already exists.';
    }
    
    if (empty($errors)) {
        // Handle profile photo upload
        $profile_photo = '';
        if (!empty($_FILES['profile_photo']['name'])) {
            $upload_dir = __DIR__ . '/../uploads/authors/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $new_filename = uniqid('author_') . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    $profile_photo = 'uploads/authors/' . $new_filename;
                }
            }
        }
        
        // Insert author
        $author_id = $db->insert('authors', [
            'full_name' => $full_name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'phone' => $phone,
            'profile_photo' => $profile_photo,
            'bio' => $bio,
            'country' => $country,
            'state' => $state,
            'city' => $city,
            'permissions' => json_encode($permissions),
            'status' => $status
        ]);
        
        if ($author_id) {
            // Send welcome email
            $emailHelper = new EmailHelper('auth');
            $emailHelper->sendWelcomeEmail($email, $full_name, 'author');
            
            Session::setFlash('success', 'Author added successfully!', 'success');
            redirect('authors.php');
        } else {
            $errors[] = 'Failed to add author.';
        }
    }
}

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-person-plus-fill"></i> Add New Author
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
                                       value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" 
                                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Password *</label>
                                        <input type="password" name="password" class="form-control" required>
                                        <small class="text-muted">Minimum 6 characters</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Confirm Password *</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Bio</label>
                                <textarea name="bio" class="form-control" rows="4"><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
                            </div>
                            
                            <h5 class="mb-3 mt-4">Location</h5>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Country *</label>
                                        <select name="country" id="country" class="form-select" required>
                                            <option value="India" selected>India</option>
                                            <option value="United States">United States</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">State *</label>
                                        <select name="state" id="state" class="form-select" required>
                                            <option value="">Select State</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">City *</label>
                                        <select name="city" id="city" class="form-select" required>
                                            <option value="">Select City</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <h5 class="mb-3">Settings</h5>
                            
                            <div class="mb-3">
                                <label class="form-label">Profile Photo</label>
                                <input type="file" name="profile_photo" class="form-control" accept="image/*">
                                <small class="text-muted">JPG, PNG, GIF (Max 2MB)</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Permissions</label>
                                <div class="form-check">
                                    <input type="checkbox" name="permissions[]" value="create_article" 
                                           class="form-check-input" id="perm_create" checked>
                                    <label class="form-check-label" for="perm_create">Create Articles</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="permissions[]" value="edit_own_article" 
                                           class="form-check-input" id="perm_edit" checked>
                                    <label class="form-check-label" for="perm_edit">Edit Own Articles</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="permissions[]" value="delete_own_article" 
                                           class="form-check-input" id="perm_delete">
                                    <label class="form-check-label" for="perm_delete">Delete Own Articles</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="permissions[]" value="upload_media" 
                                           class="form-check-input" id="perm_media" checked>
                                    <label class="form-check-label" for="perm_media">Upload Media</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="authors.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Add Author
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const locationData = <?= $locationAPI->getLocationJSON() ?>;
    const countrySelect = document.getElementById('country');
    const stateSelect = document.getElementById('state');
    const citySelect = document.getElementById('city');
    
    if (!countrySelect || !stateSelect || !citySelect) {
        console.error('Location select elements not found');
        return;
    }

    countrySelect.addEventListener('change', function() {
        const country = this.value;
        
        stateSelect.innerHTML = '<option value="">Select State</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';
        
        if (locationData[country]) {
            const states = Object.keys(locationData[country]);
            states.forEach(state => {
                const option = document.createElement('option');
                option.value = state;
                option.textContent = state;
                stateSelect.appendChild(option);
            });
        }
    });

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

    // Initialize on page load
    countrySelect.dispatchEvent(new Event('change'));
});
</script>

<?php include 'includes/footer.php'; ?>
