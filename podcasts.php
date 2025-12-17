<?php
require_once 'config/config.php';

$db = Database::getInstance();

// Get all podcasts
$podcasts = $db->fetchAll("
    SELECT * FROM podcasts 
    WHERE status = 'published' 
    ORDER BY created_at DESC
");

$page_title = 'Podcasts';
include 'includes/header.php';
?>

<!-- Include Podcast Player -->
<?php include 'includes/podcast-player.php'; ?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-4 fw-bold mb-2">
                <i class="bi bi-broadcast"></i> Podcasts
            </h1>
            <p class="text-muted">Discover amazing audio content</p>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($podcasts as $podcast): ?>
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 shadow-sm podcast-card">
                <div class="position-relative">
                    <?php if ($podcast['cover_image']): ?>
                    <img src="<?= UPLOADS_URL ?>/podcasts/<?= $podcast['cover_image'] ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($podcast['title']) ?>"
                         style="height: 250px; object-fit: cover;">
                    <?php else: ?>
                    <div class="bg-gradient-primary text-white d-flex align-items-center justify-content-center" 
                         style="height: 250px;">
                        <i class="bi bi-podcast fs-1"></i>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($podcast['is_series']): ?>
                    <span class="badge bg-primary position-absolute top-0 end-0 m-2">
                        <?= $podcast['total_episodes'] ?> Episodes
                    </span>
                    <?php endif; ?>
                </div>
                
                <div class="card-body">
                    <h5 class="card-title">
                        <a href="<?= BASE_URL ?>/podcast/<?= $podcast['slug'] ?>" 
                           class="text-decoration-none text-dark">
                            <?= htmlspecialchars($podcast['title']) ?>
                        </a>
                    </h5>
                    
                    <p class="text-muted small mb-2">
                        <i class="bi bi-person-circle"></i> <?= htmlspecialchars($podcast['author_name']) ?>
                    </p>
                    
                    <p class="card-text text-muted">
                        <?= htmlspecialchars(truncateText($podcast['description'], 120)) ?>
                    </p>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            <i class="bi bi-eye"></i> <?= formatNumber($podcast['views_count']) ?>
                            <i class="bi bi-heart ms-2"></i> <?= formatNumber($podcast['likes_count']) ?>
                        </div>
                        <span class="badge bg-secondary"><?= $podcast['category'] ?></span>
                    </div>
                </div>
                
                <div class="card-footer bg-white border-top-0">
                    <a href="<?= BASE_URL ?>/podcast/<?= $podcast['slug'] ?>" 
                       class="btn btn-primary w-100">
                        <i class="bi bi-play-circle"></i> Listen Now
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($podcasts)): ?>
        <div class="col-12 text-center py-5">
            <i class="bi bi-broadcast fs-1 text-muted"></i>
            <h3 class="mt-3">No Podcasts Available</h3>
            <p class="text-muted">Check back soon for new content!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.podcast-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.podcast-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>

<?php include 'includes/footer.php'; ?>
