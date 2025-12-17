<?php
/**
 * Add New Reporter
 */

require_once 'auth_check.php';
require_once __DIR__ . '/../includes/LocationAPI.php';
require_once __DIR__ . '/../includes/EmailHelper.php';

$page_title = 'Add New Reporter';
$db = Database::getInstance();
$locationAPI = LocationAPI::getInstance();

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
    $status = $_POST['status'] ?? 'pending';
    
    // Validation
    if (empty($full_name)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    
    // Check if email exists
    if ($db->exists('reporters', 'email = ?', [$email])) {
        $errors[] = 'Email already exists.';
    }
    
    if (empty($errors)) {
        // Handle profile photo upload
        $profile_photo = '';
        if (!empty($_FILES['profile_photo']['name'])) {
            $upload_dir = __DIR__ . '/../uploads/reporters/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext)) {
                $new_filename = uniqid('reporter_') . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    $profile_photo = 'uploads/reporters/' . $new_filename;
                }
            }
        }
        
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        $verification_expires = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        // Generate unique reporter ID
        $unique_reporter_id = 'REP' . str_pad($db->fetchOne("SELECT MAX(id) as max_id FROM reporters")['max_id'] + 1, 6, '0', STR_PAD_LEFT);
        
        // Insert reporter
        $reporter_id = $db->insert('reporters', [
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'profile_photo' => $profile_photo,
            'bio' => $bio,
            'country' => $country,
            'state' => $state,
            'city' => $city,
            'unique_reporter_id' => $unique_reporter_id,
            'status' => $status,
            'email_verified' => 0,
            'verification_token' => $verification_token,
            'verification_expires' => $verification_expires,
            'password' => password_hash('reporter' . rand(1000, 9999), PASSWORD_DEFAULT) // Temporary password
        ]);
        
        if ($reporter_id) {
            // Handle document uploads
            if (!empty($_FILES['documents']['name'][0])) {
                $doc_upload_dir = __DIR__ . '/../uploads/reporter_documents/';
                if (!is_dir($doc_upload_dir)) mkdir($doc_upload_dir, 0755, true);
                
                foreach ($_FILES['documents']['name'] as $key => $filename) {
                    if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $allowed_exts = ['pdf', 'zip', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx'];
                        
                        if (in_array($file_ext, $allowed_exts)) {
                            $new_filename = uniqid('doc_') . '.' . $file_ext;
                            $upload_path = $doc_upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['documents']['tmp_name'][$key], $upload_path)) {
                                // Determine document type
                                $doc_type = 'other';
                                if ($file_ext === 'pdf') $doc_type = 'pdf';
                                elseif ($file_ext === 'zip') $doc_type = 'zip';
                                elseif (in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) $doc_type = 'image';
                                
                                // Insert document record
                                $db->insert('reporter_documents', [
                                    'reporter_id' => $reporter_id,
                                    'document_path' => 'uploads/reporter_documents/' . $new_filename,
                                    'document_type' => $doc_type
                                ]);
                            }
                        }
                    }
                }
            }
            
            // Send verification email
            try {
                $emailHelper = new EmailHelper('auth');
                $verification_link = BASE_URL . '/verify-reporter.php?token=' . $verification_token;
                
                $subject = 'Verify Your Email - Brackodd Media Reporter';
                $body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
                        <h1 style="color: white; margin: 0;">Email Verification Required</h1>
                    </div>
                    
                    <div style="padding: 30px; background: #f8f9fa;">
                        <h2 style="color: #333;">Hello ' . htmlspecialchars($full_name) . ',</h2>
                        
                        <p style="font-size: 16px; line-height: 1.6; color: #555;">
                            Thank you for registering as a reporter at <strong>Brackodd Media</strong>. To complete your registration and activate your account, please verify your email address.
                        </p>
                        
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="' . $verification_link . '" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
                                Verify Email Address
                            </a>
                        </div>
                        
                        <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0;">
                            <p style="margin: 0; font-size: 14px; color: #856404;">
                                <strong>⏰ Important:</strong> This verification link is valid for <strong>24 hours</strong> only. Please verify your email before it expires.
                            </p>
                        </div>
                        
                        <p style="font-size: 14px; color: #666; margin-top: 20px;">
                            If the button doesn\'t work, copy and paste this link into your browser:<br>
                            <a href="' . $verification_link . '" style="color: #667eea; word-break: break-all;">' . $verification_link . '</a>
                        </p>
                        
                        <p style="font-size: 14px; color: #666;">
                            If you did not request this registration, please ignore this email.
                        </p>
                    </div>
                    
                    <div style="background: #333; color: white; padding: 20px; text-align: center; font-size: 12px;">
                        <p style="margin: 5px 0;">&copy; ' . date('Y') . ' Brackodd Media. All rights reserved.</p>
                    </div>
                </div>';
                
                $emailHelper->send($email, $subject, $body);
                Session::setFlash('success', 'Reporter added successfully! Verification email sent to ' . $email, 'success');
            } catch (Exception $e) {
                Session::setFlash('warning', 'Reporter added but verification email failed: ' . $e->getMessage(), 'warning');
            }
            
            redirect('reporters.php');
        } else {
            $errors[] = 'Failed to add reporter.';
        }
    }
}

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-person-plus-fill"></i> Add New Reporter
            </h1>
            <a href="reporters.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Reporters
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
                            <h5 class="mb-3">Reporter Information</h5>
                            
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
                            
                            <div class="mb-3">
                                <label class="form-label">Documents</label>
                                <input type="file" name="documents[]" class="form-control" multiple 
                                       accept=".pdf,.zip,.jpg,.jpeg,.png,.gif,.doc,.docx">
                                <small class="text-muted">Upload multiple documents (PDF, ZIP, Images, DOC). Hold Ctrl to select multiple files.</small>
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
                                    <option value="pending" selected>Pending Verification</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            
                            <div class="alert alert-info">
                                <small>
                                    <i class="bi bi-info-circle-fill"></i>
                                    <strong>Note:</strong> A verification email will be sent to the reporter. They must verify within 24 hours.
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="reporters.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Add Reporter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Location data
const locationData = <?= $locationAPI->getLocationJSON() ?>;

document.getElementById('country').addEventListener('change', function() {
    const country = this.value;
    const stateSelect = document.getElementById('state');
    const citySelect = document.getElementById('city');
    
    // Clear existing options
    stateSelect.innerHTML = '<option value="">Select State</option>';
    citySelect.innerHTML = '<option value="">Select City</option>';
    
    // Populate states
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

document.getElementById('state').addEventListener('change', function() {
    const country = document.getElementById('country').value;
    const state = this.value;
    const citySelect = document.getElementById('city');
    
    // Clear existing options
    citySelect.innerHTML = '<option value="">Select City</option>';
    
    // Populate cities
    if (locationData[country] && locationData[country][state]) {
        locationData[country][state].forEach(city => {
            const option = document.createElement('option');
            option.value = city;
            option.textContent = city;
            citySelect.appendChild(option);
        });
    }
});

// Trigger change on page load to populate India states
document.getElementById('country').dispatchEvent(new Event('change'));
</script>

<?php include 'includes/footer.php'; ?>
