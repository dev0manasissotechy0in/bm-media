<?php
/**
 * Author Profile Management
 */

require_once 'auth_check.php';

$page_title = 'Profile Settings';
$db = Database::getInstance();

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
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
            
            if ($db->update('authors', [
                'full_name' => $full_name,
                'phone' => $phone,
                'bio' => $bio,
                'profile_photo' => $profile_photo
            ], 'id = ?', [$author_id])) {
                Session::setFlash('success', 'Profile updated successfully!', 'success');
                redirect('profile.php');
            } else {
                $errors[] = 'Failed to update profile.';
            }
        }
    }
    
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password)) {
            $errors[] = 'All password fields are required.';
        } elseif (!password_verify($current_password, $author['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        }
        
        if (empty($errors)) {
            if ($db->update('authors', [
                'password' => password_hash($new_password, PASSWORD_DEFAULT)
            ], 'id = ?', [$author_id])) {
                Session::setFlash('success', 'Password changed successfully!', 'success');
                redirect('profile.php');
            } else {
                $errors[] = 'Failed to change password.';
            }
        }
    }
}

// Refresh author data
$author = $db->fetchOne("SELECT * FROM authors WHERE id = ?", [$author_id]);

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <h1 class="h3 mb-4">
            <i class="bi bi-person-gear"></i> Profile Settings
        </h1>
        
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
        
        <div class="row">
            <div class="col-md-8">
                <!-- Profile Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Full Name *</label>
                                        <input type="text" name="full_name" class="form-control" 
                                               value="<?= htmlspecialchars($author['full_name']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" 
                                               value="<?= htmlspecialchars($author['email']) ?>" disabled>
                                        <small class="text-muted">Contact admin to change email</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($author['phone'] ?? '') ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Bio</label>
                                <textarea name="bio" class="form-control" rows="4"><?= htmlspecialchars($author['bio'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <?php if (!empty($author['profile_photo'])): ?>
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($author['profile_photo']) ?>" 
                                     alt="Current photo" class="img-thumbnail mb-2" style="max-width: 200px;">
                                <?php endif; ?>
                                <label class="form-label">Profile Photo</label>
                                <input type="file" name="profile_photo" class="form-control" accept="image/*">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Account Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Account Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Status:</strong><br>
                            <?php if ($author['status'] === 'active'): ?>
                            <span class="badge bg-success">Active</span>
                            <?php elseif ($author['status'] === 'suspended'): ?>
                            <span class="badge bg-danger">Suspended</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </p>
                        <p><strong>Member Since:</strong><br>
                            <?= date('F d, Y', strtotime($author['created_at'])) ?>
                        </p>
                        <p class="mb-0"><strong>Last Login:</strong><br>
                            <?= $author['last_login'] ? date('M d, Y H:i', strtotime($author['last_login'])) : 'Never' ?>
                        </p>
                    </div>
                </div>
                
                <!-- Permissions -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Your Permissions</h6>
                    </div>
                    <div class="card-body">
                        <?php 
                        $permissions = json_decode($author['permissions'] ?? '[]', true) ?: [];
                        if (empty($permissions)): 
                        ?>
                        <p class="text-muted mb-0">No permissions assigned</p>
                        <?php else: ?>
                        <?php foreach ($permissions as $permission): ?>
                        <div class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <?= ucwords(str_replace('_', ' ', $permission)) ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
