<?php
require_once 'config/config.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    redirect(BASE_URL . '/podcasts.php');
}

$db = Database::getInstance();

// Get podcast
$podcast = $db->fetchOne("SELECT * FROM podcasts WHERE slug = ? AND status = 'published'", [$slug]);

if (!$podcast) {
    redirect(BASE_URL . '/404.php');
}

// Get episodes if series
$episodes = [];
$total_duration = $podcast['duration'] ?? 0;
if ($podcast['is_series']) {
    $episodes = $db->fetchAll("
        SELECT * FROM podcast_episodes 
        WHERE podcast_id = ? AND status = 'published' 
        ORDER BY season_number DESC, episode_number DESC
    ", [$podcast['id']]);
    
    // Calculate total duration for series
    $total_duration = 0;
    foreach ($episodes as $ep) {
        $total_duration += $ep['duration'];
    }
}

// Cache busting version
$cache_version = time();

// Increment view count
trackView('podcast', $podcast['id']);

// Check if user has liked/saved
$user_liked = false;
$user_saved = false;
$user_notification = false;

if (Security::isLoggedIn('user')) {
    $user_liked = $db->fetchOne("SELECT id FROM user_podcast_likes WHERE user_id = ? AND podcast_id = ?", [$_SESSION['user_id'], $podcast['id']]) ? true : false;
    $user_saved = $db->fetchOne("SELECT id FROM user_saved_podcasts WHERE user_id = ? AND podcast_id = ?", [$_SESSION['user_id'], $podcast['id']]) ? true : false;
    $notif = $db->fetchOne("SELECT enabled FROM user_podcast_notifications WHERE user_id = ? AND podcast_id = ?", [$_SESSION['user_id'], $podcast['id']]);
    $user_notification = $notif ? (bool)$notif['enabled'] : false;
}

$page_title = $podcast['title'];
include 'includes/header.php';
?>

<!-- Prevent Caching -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<!-- Include Podcast Player -->
<?php include 'includes/podcast-player.php'; ?>

<div class="container py-5">
    <!-- Podcast Header -->
    <div class="row mb-5">
        <div class="col-lg-4">
            <?php 
            $cover_image = $podcast['cover_image'] ?: $podcast['thumbnail'];
            if ($cover_image) {
                $image_url = UPLOADS_URL . '/podcasts/' . $cover_image;
            } else {
                $image_url = 'https://via.placeholder.com/400x400/6366f1/ffffff?text=Podcast';
            }
            ?>
            <img src="<?= $image_url ?>" 
                 class="img-fluid rounded shadow-lg" 
                 alt="<?= htmlspecialchars($podcast['title']) ?>"
                 onerror="this.src='https://via.placeholder.com/400x400/6366f1/ffffff?text=No+Image'">
        </div>
        
        <div class="col-lg-8">
            <h1 class="display-4 fw-bold mb-3"><?= htmlspecialchars($podcast['title']) ?></h1>
            
            <p class="lead text-muted mb-4"><?= nl2br(htmlspecialchars($podcast['description'])) ?></p>
            
            <div class="mb-4">
                <span class="badge bg-primary me-2"><?= $podcast['category'] ?></span>
                <span class="badge bg-secondary me-2"><?= $podcast['language'] ?></span>
                <?php if ($podcast['is_series']): ?>
                <span class="badge bg-info"><?= count($episodes) ?> Episodes</span>
                <?php endif; ?>
                <?php if ($total_duration > 0): ?>
                <span class="badge bg-dark me-2">
                    <i class="bi bi-clock"></i> 
                    <?php
                    $hours = floor($total_duration / 3600);
                    $minutes = floor(($total_duration % 3600) / 60);
                    ?>
                    <?php if ($hours > 0): ?><?= $hours ?>h <?php endif; ?>
                    <?= $minutes ?>m
                    <?php if ($podcast['is_series']): ?> (Total)<?php endif; ?>
                </span>
                <?php endif; ?>
            </div>
            
            <div class="mb-4">
                <strong><i class="bi bi-person-circle"></i> <?= htmlspecialchars($podcast['author_name']) ?></strong>
                <?php if ($podcast['author_bio']): ?>
                <p class="text-muted mt-2"><?= htmlspecialchars($podcast['author_bio']) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="d-flex gap-2 mb-4">
                <button onclick="likePodcast(<?= $podcast['id'] ?>)" 
                        class="btn <?= $user_liked ? 'btn-danger' : 'btn-outline-danger' ?>" 
                        id="likeBtn">
                    <i class="bi bi-heart<?= $user_liked ? '-fill' : '' ?>"></i> 
                    <span id="likesCount"><?= formatNumber($podcast['likes_count']) ?></span> Likes
                </button>
                
                <button onclick="savePodcast(<?= $podcast['id'] ?>)" 
                        class="btn <?= $user_saved ? 'btn-warning' : 'btn-outline-warning' ?>" 
                        id="saveBtn">
                    <i class="bi bi-bookmark<?= $user_saved ? '-fill' : '' ?>"></i> 
                    <?= $user_saved ? 'Saved' : 'Save' ?>
                </button>
                
                <button onclick="toggleNotification(<?= $podcast['id'] ?>)" 
                        class="btn <?= $user_notification ? 'btn-success' : 'btn-outline-success' ?>" 
                        id="notifBtn">
                    <i class="bi bi-bell<?= $user_notification ? '-fill' : '' ?>"></i> 
                    <span id="notifText"><?= $user_notification ? 'Notifications On' : 'Notify Me' ?></span>
                </button>
            </div>
            
            <div class="text-muted">
                <i class="bi bi-eye"></i> <?= formatNumber($podcast['views_count']) ?> views
            </div>
        </div>
    </div>
    
    <!-- Single Podcast Player -->
    <?php if (!$podcast['is_series']): ?>
    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-body text-center p-5">
                    <h3 class="mb-4"><i class="bi bi-headphones"></i> Listen Now</h3>
                    <?php if (!empty($podcast['audio_url'])): ?>
                    <button onclick="playSinglePodcast()" class="btn btn-primary btn-lg">
                        <i class="bi bi-play-circle-fill"></i> Play Episode
                    </button>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Audio file not available for this podcast.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Episodes List -->
    <?php if ($podcast['is_series'] && !empty($episodes)): ?>
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">
                <i class="bi bi-list-ol"></i> Episodes 
                <button onclick="playAllEpisodes()" class="btn btn-primary float-end">
                    <i class="bi bi-play-fill"></i> Play All
                </button>
            </h2>
            
            <div class="list-group">
                <?php foreach ($episodes as $index => $episode): ?>
                <div class="list-group-item list-group-item-action">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <?php 
                            $ep_thumbnail = $episode['thumbnail'] ?: $podcast['thumbnail'];
                            if ($ep_thumbnail): 
                            ?>
                            <img src="<?= UPLOADS_URL ?>/podcasts/<?= $ep_thumbnail ?>" 
                                 alt="<?= htmlspecialchars($episode['title']) ?>" 
                                 class="rounded"
                                 style="width: 80px; height: 80px; object-fit: cover;"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <?php endif; ?>
                            <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px; <?= $ep_thumbnail ? 'display: none;' : '' ?>">
                                <div class="text-center">
                                    <div><strong>E<?= $episode['episode_number'] ?></strong></div>
                                    <?php if ($episode['season_number']): ?>
                                    <small>S<?= $episode['season_number'] ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col">
                            <h5 class="mb-1"><?= htmlspecialchars($episode['title']) ?></h5>
                            <p class="text-muted mb-1"><?= htmlspecialchars(truncateText($episode['description'], 150)) ?></p>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> <?= gmdate('H:i:s', $episode['duration']) ?>
                                <?php if ($episode['season_number']): ?>
                                | Season <?= $episode['season_number'] ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <div class="col-auto">
                            <button onclick="playEpisodeFromList(<?= $index ?>)" 
                                    class="btn btn-primary btn-lg">
                                <i class="bi bi-play-fill"></i> Play
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Cache version: <?= $cache_version ?> 
console.log('Page loaded at: <?= date('Y-m-d H:i:s') ?>', 'Cache: <?= $cache_version ?>');

// Podcast Data
const podcastData = {
    id: <?= $podcast['id'] ?>,
    title: '<?= addslashes($podcast['title']) ?>',
    author: '<?= addslashes($podcast['author_name']) ?>',
    thumbnail: '<?= $podcast['thumbnail'] ? UPLOADS_URL . '/podcasts/' . $podcast['thumbnail'] : '' ?>',
    isSeries: <?= $podcast['is_series'] ? 'true' : 'false' ?>,
    audioUrl: '<?= $podcast['audio_url'] ?? '' ?>'
};
console.log('Podcast Data:', podcastData);

<?php if (!empty($episodes)): ?>
const episodesData = [
    <?php foreach ($episodes as $ep): ?>
    {
        id: <?= $ep['id'] ?>,
        title: '<?= addslashes($ep['title']) ?>',
        author: '<?= addslashes($podcast['author_name']) ?>',
        thumbnail: '<?= $ep['thumbnail'] ? UPLOADS_URL . '/podcasts/' . $ep['thumbnail'] : ($podcast['thumbnail'] ? UPLOADS_URL . '/podcasts/' . $podcast['thumbnail'] : '') ?>',
        audioUrl: '<?= addslashes($ep['audio_url']) ?>',
        duration: <?= $ep['duration'] ?>
    },
    <?php endforeach; ?>
];
console.log('Episodes Data:', episodesData);
<?php else: ?>
const episodesData = [];
console.log('No episodes found');
<?php endif; ?>

// Play single podcast
function playSinglePodcast() {
    if (podcastData.audioUrl) {
        playPodcastEpisode({
            title: podcastData.title,
            author: podcastData.author,
            thumbnail: podcastData.thumbnail,
            audioUrl: podcastData.audioUrl
        });
    }
}

// Play episode from list
function playEpisodeFromList(index) {
    console.log('Playing episode:', episodesData[index]);
    if (episodesData.length > 0) {
        playPodcastPlaylist(episodesData, index);
    }
}

// Play all episodes
function playAllEpisodes() {
    if (episodesData.length > 0) {
        playPodcastPlaylist(episodesData, 0);
    }
}

function likePodcast(podcastId) {
    fetch(BASE_URL + '/api/podcasts/like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ podcast_id: podcastId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById('likeBtn');
            const icon = btn.querySelector('i');
            const countSpan = document.getElementById('likesCount');
            if (data.liked) {
                btn.classList.remove('btn-outline-danger');
                btn.classList.add('btn-danger');
                icon.classList.add('bi-heart-fill');
                icon.classList.remove('bi-heart');
            } else {
                btn.classList.add('btn-outline-danger');
                btn.classList.remove('btn-danger');
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
            }
            if (data.likes_count !== undefined) {
                countSpan.textContent = data.likes_count;
            }
        } else {
            alert(data.message);
            if (data.message.includes('login')) {
                window.location.href = BASE_URL + '/login.php';
            }
        }
    });
}

function savePodcast(podcastId) {
    fetch(BASE_URL + '/api/podcasts/save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ podcast_id: podcastId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById('saveBtn');
            const icon = btn.querySelector('i');
            if (data.saved) {
                btn.classList.remove('btn-outline-warning');
                btn.classList.add('btn-warning');
                icon.classList.add('bi-bookmark-fill');
                icon.classList.remove('bi-bookmark');
                btn.innerHTML = '<i class="bi bi-bookmark-fill"></i> Saved';
            } else {
                btn.classList.add('btn-outline-warning');
                btn.classList.remove('btn-warning');
                icon.classList.remove('bi-bookmark-fill');
                icon.classList.add('bi-bookmark');
                btn.innerHTML = '<i class="bi bi-bookmark"></i> Save';
            }
        } else {
            alert(data.message);
        }
    });
}

function toggleNotification(podcastId) {
    const btn = document.getElementById('notifBtn');
    const isEnabled = btn.classList.contains('btn-success');
    
    fetch(BASE_URL + '/api/podcasts/notification.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ podcast_id: podcastId, enabled: !isEnabled })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const icon = btn.querySelector('i');
            const text = document.getElementById('notifText');
            if (data.enabled) {
                btn.classList.remove('btn-outline-success');
                btn.classList.add('btn-success');
                icon.classList.add('bi-bell-fill');
                icon.classList.remove('bi-bell');
                text.textContent = 'Notifications On';
            } else {
                btn.classList.add('btn-outline-success');
                btn.classList.remove('btn-success');
                icon.classList.remove('bi-bell-fill');
                icon.classList.add('bi-bell');
                text.textContent = 'Notify Me';
            }
        } else {
            alert(data.message);
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
