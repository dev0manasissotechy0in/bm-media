<?php
/**
 * User Profile Settings
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Functions.php';
require_once '../includes/Session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Get user details
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        // Validation
        if (empty($full_name)) {
            $errors[] = 'Full name is required';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        } else {
            // Check email uniqueness
            $existing = $db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user_id]);
            if ($existing) {
                $errors[] = 'Email already in use';
            }
        }
        
        // Handle profile photo upload
        $profile_photo = $user['profile_photo'];
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                $errors[] = 'Invalid image format. Use JPG, PNG, or GIF';
            } elseif ($_FILES['profile_photo']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Image too large. Maximum 5MB';
            } else {
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path = '../uploads/users/';
                
                if (!is_dir($upload_path)) {
                    mkdir($upload_path, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path . $new_filename)) {
                    // Delete old photo
                    if ($profile_photo && file_exists($upload_path . $profile_photo)) {
                        unlink($upload_path . $profile_photo);
                    }
                    $profile_photo = $new_filename;
                }
            }
        }
        
        if (empty($errors)) {
            $update_data = [
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'profile_photo' => $profile_photo,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $db->update('users', $update_data, 'id = ?', [$user_id]);
            $success = 'Profile updated successfully';
            
            // Refresh user data
            $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
        }
    }
    
    elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password)) {
            $errors[] = 'Current password is required';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        }
        
        if (empty($new_password)) {
            $errors[] = 'New password is required';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $db->update('users', ['password' => $hashed_password], 'id = ?', [$user_id]);
            $success = 'Password changed successfully';
        }
    }
}

$page_title = 'Profile Settings';
include '../includes/header.php';
?>

<main class="container my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-3">My Account</h6>
                    <div class="list-group list-group-flush">
                        <a href="<?= BASE_URL ?>/user/dashboard.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a href="<?= BASE_URL ?>/user/saved-articles.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-bookmark"></i> Saved Articles
                        </a>
                        <a href="<?= BASE_URL ?>/user/profile.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-person"></i> Profile Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <h2 class="mb-4"><i class="bi bi-person-circle"></i> Profile Settings</h2>
            
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
            
            <!-- Profile Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-12 mb-3 text-center">
                                <?php if ($user['profile_photo']): ?>
                                    <img src="<?= BASE_URL ?>/uploads/users/<?= htmlspecialchars($user['profile_photo']) ?>" 
                                         class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;" id="photoPreview">
                                <?php else: ?>
                                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" 
                                         style="width: 150px; height: 150px; font-size: 3rem;" id="photoPreview">
                                        <?= strtoupper(substr($user['full_name'] ?? $user['email'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div>
                                    <label for="profilePhoto" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-camera"></i> Change Photo
                                    </label>
                                    <input type="file" name="profile_photo" id="profilePhoto" class="d-none" accept="image/*">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Member Since</label>
                                <input type="text" class="form-control" 
                                       value="<?= date('F d, Y', strtotime($user['created_at'])) ?>" readonly>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">New Password <span class="text-danger">*</span></label>
                                <input type="password" name="new_password" class="form-control" 
                                       minlength="6" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Photo preview
document.getElementById('profilePhoto').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').outerHTML = 
                '<img src="' + e.target.result + '" class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;" id="photoPreview">';
        }
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

<?php include '../includes/footer.php'; ?>