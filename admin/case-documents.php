<?php
/**
 * Case Documents Management
 */

require_once 'auth_check.php';

$page_title = 'Case Documents';

$db = Database::getInstance();

$case_id = (int)($_GET['case_id'] ?? 0);

if (!$case_id) {
    $_SESSION['error'] = 'Invalid case ID';
    header('Location: cases.php');
    exit;
}

// Get case
$case = $db->fetchOne("SELECT * FROM case_threads WHERE id = ?", [$case_id]);

if (!$case) {
    $_SESSION['error'] = 'Case not found';
    header('Location: cases.php');
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $doc = $db->fetchOne("SELECT file_url FROM case_documents WHERE id = ? AND case_id = ?", [$id, $case_id]);
    
    if ($doc && $doc['file_url'] && file_exists('../' . $doc['file_url'])) {
        unlink('../' . $doc['file_url']);
    }
    
    $db->query("DELETE FROM case_documents WHERE id = ? AND case_id = ?", [$id, $case_id]);
    $_SESSION['success'] = 'Document deleted successfully';
    header('Location: case-documents.php?case_id=' . $case_id);
    exit;
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document_file'])) {
    $title = trim($_POST['title'] ?? '');
    $document_type = $_POST['document_type'] ?? 'other';
    $document_date = $_POST['document_date'] ?? null;
    $summary = trim($_POST['summary'] ?? '');
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    // Handle file upload
    if ($_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/cases/documents/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = $_FILES['document_file']['name'];
        $file_size = $_FILES['document_file']['size'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowed_types = ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png'];
        if (!in_array($file_extension, $allowed_types)) {
            $errors[] = 'Invalid file type. Allowed: PDF, DOC, DOCX, TXT, JPG, PNG';
        }
        
        if (empty($errors)) {
            $new_filename = 'doc_' . $case_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $file_url = 'uploads/cases/documents/' . $new_filename;
            
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $upload_dir . $new_filename)) {
                $db->insert('case_documents', [
                    'case_id' => $case_id,
                    'title' => $title,
                    'document_type' => $document_type,
                    'document_date' => $document_date,
                    'file_url' => $file_url,
                    'file_type' => $file_extension,
                    'file_size' => $file_size,
                    'plain_language_summary' => $summary
                ]);
                
                $_SESSION['success'] = 'Document uploaded successfully';
                header('Location: case-documents.php?case_id=' . $case_id);
                exit;
            } else {
                $errors[] = 'Failed to upload file';
            }
        }
    } else {
        $errors[] = 'Please select a file to upload';
    }
}

// Get documents
$documents = $db->fetchAll("
    SELECT * FROM case_documents 
    WHERE case_id = ? 
    ORDER BY document_date DESC, id DESC
", [$case_id]);

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="page-header">
        <h1>Documents: <?php echo htmlspecialchars($case['title']); ?></h1>
        <a href="case-edit.php?id=<?php echo $case_id; ?>" class="btn btn-secondary">← Back to Case</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5>Upload Document</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Document File *</label>
                            <input type="file" name="document_file" class="form-control" required accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png">
                            <small class="form-text text-muted">Allowed: PDF, DOC, DOCX, TXT, JPG, PNG (Max 10MB)</small>
                        </div>

                        <div class="form-group">
                            <label>Title *</label>
                            <input type="text" name="title" class="form-control" required 
                                   placeholder="e.g., Supreme Court Judgment">
                        </div>

                        <div class="form-group">
                            <label>Document Type</label>
                            <select name="document_type" class="form-control">
                                <option value="judgment">Judgment</option>
                                <option value="order">Court Order</option>
                                <option value="fir">FIR</option>
                                <option value="chargesheet">Chargesheet</option>
                                <option value="bail_order">Bail Order</option>
                                <option value="plea">Plea/Petition</option>
                                <option value="evidence">Evidence</option>
                                <option value="report">Report</option>
                                <option value="affidavit">Affidavit</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Document Date</label>
                            <input type="date" name="document_date" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Plain Language Summary</label>
                            <textarea name="summary" class="form-control" rows="3" 
                                      placeholder="Brief explanation in simple terms"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Upload Document</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5>Documents (<?php echo count($documents); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($documents)): ?>
                        <p class="text-center text-muted">No documents yet. Upload your first document.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Size</th>
                                        <th>File Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($doc['title']); ?></strong>
                                                <?php if ($doc['plain_language_summary']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($doc['plain_language_summary'], 0, 50)); ?>...</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $doc['document_date'] ? date('M j, Y', strtotime($doc['document_date'])) : 'N/A'; ?></td>
                                            <td><?php echo round($doc['file_size'] / 1024, 1); ?> KB</td>
                                            <td>
                                                <span class="badge badge-info"><?php echo strtoupper($doc['file_type'] ?? 'file'); ?></span>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL . '/' . $doc['file_url']; ?>" 
                                                   class="btn btn-sm btn-info" target="_blank" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?case_id=<?php echo $case_id; ?>&delete=<?php echo $doc['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Delete this document?');"
                                                   title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
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
    </div>
</div>

<?php include 'includes/footer.php'; ?>
