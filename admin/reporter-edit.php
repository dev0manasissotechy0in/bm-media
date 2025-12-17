<?php
/**
 * Edit Reporter
 */

require_once 'auth_check.php';
require_once '../includes/LocationAPI.php';

$page_title = 'Edit Reporter';
$db = Database::getInstance();
$locationAPI = LocationAPI::getInstance();

$reporter_id = (int)($_GET['id'] ?? 0);
$reporter = $db->fetchOne("SELECT * FROM reporters WHERE id = ?", [$reporter_id]);

if (!$reporter) {
    Session::setFlash('error', 'Reporter not found.', 'danger');
    redirect('reporters.php');
}

$success = '';
$errors = [];

// Handle document deletion
if (isset($_GET['delete_doc'])) {
    $doc_id = (int)$_GET['delete_doc'];
    $document = $db->fetchOne("SELECT * FROM reporter_documents WHERE id = ? AND reporter_id = ?", [$doc_id, $reporter_id]);
    
    if ($document) {
        // Delete file
        $file_path = __DIR__ . '/../' . $document['document_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete record
        $db->delete('reporter_documents', 'id = ?', [$doc_id]);
        Session::setFlash('success', 'Document deleted successfully!', 'success');
        redirect('reporter-edit.php?id=' . $reporter_id);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $country = $_POST['country'] ?? 'India';
    $state = trim($_POST['state'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $status = $_POST['status'] ?? 'active';
    
    // Validation
    if (empty($full_name)) $errors[] = 'Full name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    
    // Check if email exists (excluding current reporter)
    if ($db->exists('reporters', 'email = ? AND id != ?', [$email, $reporter_id])) {
        $errors[] = 'Email already exists.';
    }
    
    if (empty($errors)) {
        // Handle profile photo upload
        $profile_photo = $reporter['profile_photo'];
        if (!empty($_FILES['profile_photo']['name'])) {
            $upload_dir = __DIR__ . '/../uploads/reporters/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $file_ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_ext)) {
                // Delete old photo
                if (!empty($reporter['profile_photo']) && file_exists(__DIR__ . '/../' . $reporter['profile_photo'])) {
                    unlink(__DIR__ . '/../' . $reporter['profile_photo']);
                }
                
                $new_filename = uniqid('reporter_') . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    $profile_photo = 'uploads/reporters/' . $new_filename;
                }
            }
        }
        
        // Update reporter
        if ($db->update('reporters', [
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'profile_photo' => $profile_photo,
            'bio' => $bio,
            'country' => $country,
            'state' => $state,
            'city' => $city,
            'status' => $status
        ], 'id = ?', [$reporter_id])) {
            
            // Handle new document uploads
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
            
            Session::setFlash('success', 'Reporter updated successfully!', 'success');
            redirect('reporters.php');
        } else {
            $errors[] = 'Failed to update reporter.';
        }
    }
}

// Get reporter documents
$documents = $db->fetchAll("SELECT * FROM reporter_documents WHERE reporter_id = ? ORDER BY uploaded_at DESC", [$reporter_id]);

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil-fill"></i> Edit Reporter
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
                                       value="<?= htmlspecialchars($reporter['full_name']) ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?= htmlspecialchars($reporter['email']) ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control" 
                                               value="<?= htmlspecialchars($reporter['phone'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Bio</label>
                                <textarea name="bio" class="form-control" rows="4"><?= htmlspecialchars($reporter['bio'] ?? '') ?></textarea>
                            </div>
                            
                            <h5 class="mb-3">Location</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Country <span class="text-danger">*</span></label>
                                        <select name="country" id="countrySelect" class="form-select" required>
                                            <option value="India" <?= ($reporter['country'] ?? 'India') === 'India' ? 'selected' : '' ?>>India</option>
                                            <option value="United States" <?= ($reporter['country'] ?? '') === 'United States' ? 'selected' : '' ?>>United States</option>
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
                            
                            <h5 class="mb-3">Existing Documents</h5>
                            <?php if (empty($documents)): ?>
                            <p class="text-muted">No documents uploaded yet.</p>
                            <?php else: ?>
                            <div class="list-group mb-3">
                                <?php foreach ($documents as $doc): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php if ($doc['document_type'] === 'pdf'): ?>
                                        <i class="bi bi-file-pdf text-danger"></i>
                                        <?php elseif ($doc['document_type'] === 'zip'): ?>
                                        <i class="bi bi-file-zip text-warning"></i>
                                        <?php elseif ($doc['document_type'] === 'image'): ?>
                                        <i class="bi bi-file-image text-info"></i>
                                        <?php else: ?>
                                        <i class="bi bi-file-earmark text-secondary"></i>
                                        <?php endif; ?>
                                        
                                        <strong><?= htmlspecialchars(basename($doc['document_path'])) ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            Uploaded: <?= date('M d, Y', strtotime($doc['uploaded_at'])) ?>
                                        </small>
                                    </div>
                                    <div>
                                        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($doc['document_path']) ?>" 
                                           class="btn btn-sm btn-primary" target="_blank" download>
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <a href="?id=<?= $reporter_id ?>&delete_doc=<?= $doc['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this document?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Upload New Documents</label>
                                <input type="file" name="documents[]" class="form-control" multiple 
                                       accept=".pdf,.zip,.jpg,.jpeg,.png,.gif,.doc,.docx">
                                <small class="text-muted">Upload additional documents (PDF, ZIP, Images, DOC). Hold Ctrl to select multiple files.</small>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <h5 class="mb-3">Settings</h5>
                            
                            <div class="mb-3">
                                <?php if (!empty($reporter['profile_photo'])): ?>
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($reporter['profile_photo']) ?>" 
                                     alt="<?= htmlspecialchars($reporter['full_name']) ?>" 
                                     class="img-thumbnail mb-2" style="max-width: 200px;">
                                <?php endif; ?>
                                <label class="form-label">Profile Photo</label>
                                <input type="file" name="profile_photo" class="form-control" accept="image/*">
                                <small class="text-muted">JPG, PNG, GIF (Max 2MB)</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= $reporter['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $reporter['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <a href="reporters.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Reporter
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
    const currentCountry = '<?= htmlspecialchars($reporter['country'] ?? 'India') ?>';
    const currentState = '<?= htmlspecialchars($reporter['state'] ?? '') ?>';
    const currentCity = '<?= htmlspecialchars($reporter['city'] ?? '') ?>';
    
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
