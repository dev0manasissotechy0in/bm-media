<?php
/**
 * View Reporter Details
 */

require_once 'auth_check.php';

$page_title = 'Reporter Details';
$db = Database::getInstance();

$reporter_id = (int)($_GET['id'] ?? 0);
$reporter = $db->fetchOne("SELECT * FROM reporters WHERE id = ?", [$reporter_id]);

if (!$reporter) {
    Session::setFlash('error', 'Reporter not found.', 'danger');
    redirect('reporters.php');
}

// Get reporter documents
$documents = $db->fetchAll("SELECT * FROM reporter_documents WHERE reporter_id = ? ORDER BY uploaded_at DESC", [$reporter_id]);

// Get article count
$article_count = $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE reporter_id = ?", [$reporter_id])['count'];

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-person-badge-fill"></i> Reporter Details
            </h1>
            <div class="btn-group">
                <a href="reporter-edit.php?id=<?= $reporter_id ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="reporters.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <?php if (!empty($reporter['profile_photo'])): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($reporter['profile_photo']) ?>" 
                             alt="<?= htmlspecialchars($reporter['full_name']) ?>" 
                             class="rounded-circle mb-3" 
                             style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                        <div class="rounded-circle bg-info d-inline-flex align-items-center justify-content-center mb-3" 
                             style="width: 150px; height: 150px;">
                            <i class="bi bi-person text-white" style="font-size: 4rem;"></i>
                        </div>
                        <?php endif; ?>
                        
                        <h4><?= htmlspecialchars($reporter['full_name']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($reporter['email']) ?></p>
                        
                        <?php if ($reporter['status'] === 'active'): ?>
                        <span class="badge bg-success">Active</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Contact Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Email:</strong><br><?= htmlspecialchars($reporter['email']) ?></p>
                        <?php if ($reporter['phone']): ?>
                        <p><strong>Phone:</strong><br><?= htmlspecialchars($reporter['phone']) ?></p>
                        <?php endif; ?>
                        <p class="mb-0"><strong>Joined:</strong><br><?= date('F d, Y', strtotime($reporter['created_at'])) ?></p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body text-center">
                        <h2 class="text-primary"><?= $article_count ?></h2>
                        <p class="text-muted mb-0">Articles Attributed</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <?php if ($reporter['bio']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Bio</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?= nl2br(htmlspecialchars($reporter['bio'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="bi bi-file-earmark-text"></i> Documents 
                            <span class="badge bg-primary"><?= count($documents) ?></span>
                        </h6>
                        <a href="reporter-edit.php?id=<?= $reporter_id ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-upload"></i> Upload More
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($documents)): ?>
                        <p class="text-muted text-center mb-0">No documents uploaded yet</p>
                        <?php else: ?>
                        <div class="row">
                            <?php foreach ($documents as $doc): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <?php if ($doc['document_type'] === 'pdf'): ?>
                                                <i class="bi bi-file-pdf text-danger" style="font-size: 2rem;"></i>
                                                <?php elseif ($doc['document_type'] === 'zip'): ?>
                                                <i class="bi bi-file-zip text-warning" style="font-size: 2rem;"></i>
                                                <?php elseif ($doc['document_type'] === 'image'): ?>
                                                <i class="bi bi-file-image text-info" style="font-size: 2rem;"></i>
                                                <?php else: ?>
                                                <i class="bi bi-file-earmark text-secondary" style="font-size: 2rem;"></i>
                                                <?php endif; ?>
                                                
                                                <h6 class="mt-2 mb-1"><?= htmlspecialchars($doc['document_name']) ?></h6>
                                                <p class="text-muted mb-0" style="font-size: 0.85rem;">
                                                    <i class="bi bi-hdd"></i> <?= number_format($doc['file_size'] / 1024, 2) ?> KB<br>
                                                    <i class="bi bi-calendar"></i> <?= date('M d, Y', strtotime($doc['uploaded_at'])) ?>
                                                </p>
                                            </div>
                                            <div>
                                                <a href="<?= BASE_URL ?>/<?= htmlspecialchars($doc['document_path']) ?>" 
                                                   class="btn btn-sm btn-primary" target="_blank" download>
                                                    <i class="bi bi-download"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
