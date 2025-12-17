<?php
require_once 'config/database.php';
session_start();

// Get podcast ID
$podcast_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($podcast_id === 0) {
    header('Location: podcasts.php');
    exit;
}

// Fetch podcast details
$stmt = $pdo->prepare("
    SELECT p.*, pc.name as category_name, pc.color as category_color
    FROM podcasts p
    LEFT JOIN podcast_categories pc ON p.category_id = pc.id
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$podcast_id]);
$podcast = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$podcast) {
    header('Location: podcasts.php');
    exit;
}

// Get user ID or guest token
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$guest_token = null;

if (!$user_id) {
    // Generate or retrieve guest token from cookie
    if (isset($_COOKIE['guest_token'])) {
        $guest_token = $_COOKIE['guest_token'];
    } else {
        $guest_token = bin2hex(random_bytes(32));
        setcookie('guest_token', $guest_token, time() + (86400 * 365), '/'); // 1 year
    }
}

// Fetch user's progress for this podcast
$progress = null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM podcast_progress WHERE user_id = ? AND podcast_id = ?");
    $stmt->execute([$user_id, $podcast_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM podcast_progress WHERE guest_token = ? AND podcast_id = ?");
    $stmt->execute([$guest_token, $podcast_id]);
}
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

$start_time = $progress ? $progress['current_time'] : 0;
$playback_speed = $progress ? $progress['playback_speed'] : 1.0;

// Increment play count
$stmt = $pdo->prepare("UPDATE podcasts SET play_count = play_count + 1 WHERE id = ?");
$stmt->execute([$podcast_id]);

// Check if user liked this podcast
$is_liked = false;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM podcast_likes WHERE podcast_id = ? AND user_id = ?");
    $stmt->execute([$podcast_id, $user_id]);
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM podcast_likes WHERE podcast_id = ? AND guest_token = ?");
    $stmt->execute([$podcast_id, $guest_token]);
}
$is_liked = $stmt->fetchColumn() > 0;

// Format duration
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf("%d:%02d:%02d", $hours, $minutes, $secs);
    }
    return sprintf("%d:%02d", $minutes, $secs);
}

$page_title = htmlspecialchars($podcast['title']) . ' - Podcast';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #000;
            color: #fff;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Cover Background */
        .podcast-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('<?= htmlspecialchars($podcast['cover_image']) ?>');
            background-size: cover;
            background-position: center;
            filter: blur(40px) brightness(0.3);
            z-index: -1;
        }

        .podcast-background::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.7), rgba(0,0,0,0.9));
        }

        /* Main Content */
        .podcast-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-bottom: 200px; /* Space for player */
        }

        .podcast-content {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 40px 20px;
        }

        .podcast-cover {
            width: 400px;
            height: 400px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.7);
            object-fit: cover;
        }

        .podcast-info {
            flex: 1;
            padding-left: 60px;
        }

        .podcast-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .podcast-meta {
            font-size: 1.1rem;
            color: rgba(255,255,255,0.7);
            margin-bottom: 2rem;
        }

        .podcast-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: rgba(255,255,255,0.8);
            max-width: 700px;
        }

        /* Player Controls (Fixed Bottom) */
        .podcast-player {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.8), rgba(0,0,0,0.95));
            backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 20px 40px 30px;
            z-index: 1000;
        }

        /* Timeline */
        .timeline-container {
            margin-bottom: 20px;
        }

        .timeline {
            width: 100%;
            height: 6px;
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .timeline-progress {
            height: 100%;
            background: linear-gradient(to right, #1db954, #1ed760);
            border-radius: 3px;
            width: 0%;
            transition: width 0.1s linear;
        }

        .timeline-thumb {
            position: absolute;
            top: 50%;
            left: 0%;
            transform: translate(-50%, -50%);
            width: 16px;
            height: 16px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.5);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .timeline:hover .timeline-thumb {
            opacity: 1;
        }

        .time-display {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.6);
            margin-top: 8px;
        }

        /* Control Buttons */
        .player-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .controls-left,
        .controls-center,
        .controls-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .controls-center {
            flex: 1;
            justify-content: center;
        }

        .btn-control {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-control:hover {
            background: rgba(255,255,255,0.1);
            transform: scale(1.1);
        }

        .btn-play-pause {
            width: 56px;
            height: 56px;
            background: #1db954;
            font-size: 1.8rem;
        }

        .btn-play-pause:hover {
            background: #1ed760;
            transform: scale(1.05);
        }

        .btn-control i {
            font-size: 1.2rem;
        }

        .btn-skip i {
            font-size: 1.5rem;
        }

        /* Volume Control */
        .volume-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .volume-slider {
            width: 100px;
            height: 4px;
            -webkit-appearance: none;
            appearance: none;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            outline: none;
        }

        .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 12px;
            height: 12px;
            background: #fff;
            border-radius: 50%;
            cursor: pointer;
        }

        .volume-slider::-moz-range-thumb {
            width: 12px;
            height: 12px;
            background: #fff;
            border-radius: 50%;
            cursor: pointer;
        }

        /* Speed Control */
        .speed-display {
            font-size: 0.9rem;
            min-width: 45px;
            text-align: center;
            padding: 4px 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
        }

        /* Action Buttons */
        .action-btn {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .action-btn:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.3);
            color: #fff;
        }

        .action-btn.active {
            background: #1db954;
            border-color: #1db954;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .podcast-content {
                flex-direction: column;
                text-align: center;
            }

            .podcast-cover {
                width: 300px;
                height: 300px;
                margin-bottom: 30px;
            }

            .podcast-info {
                padding-left: 0;
            }

            .podcast-title {
                font-size: 2rem;
            }

            .podcast-player {
                padding: 15px 20px 20px;
            }

            .controls-left,
            .controls-right {
                display: none;
            }

            .volume-control {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Blurred Background -->
    <div class="podcast-background"></div>

    <!-- Main Content -->
    <div class="podcast-container">
        <!-- Top Navigation -->
        <div class="container-fluid py-3">
            <a href="podcasts.php" class="btn btn-outline-light">
                <i class="bi bi-arrow-left"></i> Back to Podcasts
            </a>
        </div>

        <!-- Podcast Content -->
        <div class="podcast-content">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-lg-5 text-center">
                        <img src="<?= htmlspecialchars($podcast['cover_image']) ?>" 
                             alt="<?= htmlspecialchars($podcast['title']) ?>" 
                             class="podcast-cover">
                    </div>
                    <div class="col-lg-7">
                        <div class="podcast-info">
                            <div class="podcast-meta">
                                <span class="badge" style="background-color: <?= $podcast['category_color'] ?>; font-size: 1rem;">
                                    <?= htmlspecialchars($podcast['category_name']) ?>
                                </span>
                                <?php if ($podcast['episode_number']): ?>
                                    <span class="ms-2">Episode <?= $podcast['episode_number'] ?></span>
                                <?php endif; ?>
                            </div>

                            <h1 class="podcast-title"><?= htmlspecialchars($podcast['title']) ?></h1>

                            <div class="podcast-meta">
                                <?php if ($podcast['host']): ?>
                                    <i class="bi bi-mic"></i> Hosted by <?= htmlspecialchars($podcast['host']) ?>
                                <?php endif; ?>
                                <?php if ($podcast['guest']): ?>
                                    <span class="ms-3">
                                        <i class="bi bi-people"></i> With <?= htmlspecialchars($podcast['guest']) ?>
                                    </span>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <i class="bi bi-clock"></i> <?= formatDuration($podcast['duration']) ?>
                                    <span class="ms-3">
                                        <i class="bi bi-calendar3"></i> <?= date('M d, Y', strtotime($podcast['published_at'])) ?>
                                    </span>
                                </div>
                            </div>

                            <div class="podcast-description">
                                <?= nl2br(htmlspecialchars($podcast['description'])) ?>
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-4 d-flex flex-wrap gap-2">
                                <button class="action-btn <?= $is_liked ? 'active' : '' ?>" id="likeBtn">
                                    <i class="bi bi-heart<?= $is_liked ? '-fill' : '' ?>"></i>
                                    <span id="likeCount"><?= number_format($podcast['like_count']) ?></span>
                                </button>
                                <button class="action-btn" id="shareBtn">
                                    <i class="bi bi-share"></i> Share
                                </button>
                                <a href="api/podcasts/download.php?id=<?= $podcast_id ?>" class="action-btn">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Player Controls -->
    <div class="podcast-player">
        <!-- Timeline -->
        <div class="timeline-container">
            <div class="timeline" id="timeline">
                <div class="timeline-progress" id="timelineProgress"></div>
                <div class="timeline-thumb" id="timelineThumb"></div>
            </div>
            <div class="time-display">
                <span id="currentTime">0:00</span>
                <span id="totalDuration"><?= formatDuration($podcast['duration']) ?></span>
            </div>
        </div>

        <!-- Controls -->
        <div class="player-controls">
            <div class="controls-left">
                <!-- Volume -->
                <div class="volume-control">
                    <button class="btn-control" id="volumeBtn">
                        <i class="bi bi-volume-up" id="volumeIcon"></i>
                    </button>
                    <input type="range" class="volume-slider" id="volumeSlider" min="0" max="100" value="100">
                </div>
            </div>

            <div class="controls-center">
                <!-- Skip Backward -->
                <button class="btn-control btn-skip" id="skipBackBtn">
                    <i class="bi bi-skip-start"></i>
                </button>

                <!-- Rewind 15s -->
                <button class="btn-control" id="rewind15Btn">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>

                <!-- Play/Pause -->
                <button class="btn-control btn-play-pause" id="playPauseBtn">
                    <i class="bi bi-play-fill" id="playPauseIcon"></i>
                </button>

                <!-- Forward 15s -->
                <button class="btn-control" id="forward15Btn">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>

                <!-- Skip Forward -->
                <button class="btn-control btn-skip" id="skipForwardBtn">
                    <i class="bi bi-skip-end"></i>
                </button>
            </div>

            <div class="controls-right">
                <!-- Speed Control -->
                <button class="btn-control" id="speedBtn">
                    <div class="speed-display" id="speedDisplay"><?= $playback_speed ?>x</div>
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden Audio Element -->
    <audio id="audioPlayer" preload="auto">
        <source src="<?= htmlspecialchars($podcast['audio_file']) ?>" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>

    <script>
        // Player State
        const podcastId = <?= $podcast_id ?>;
        const userId = <?= $user_id ? $user_id : 'null' ?>;
        const guestToken = <?= $guest_token ? "'" . $guest_token . "'" : 'null' ?>;
        const startTime = <?= $start_time ?>;

        const audio = document.getElementById('audioPlayer');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const playPauseIcon = document.getElementById('playPauseIcon');
        const timeline = document.getElementById('timeline');
        const timelineProgress = document.getElementById('timelineProgress');
        const timelineThumb = document.getElementById('timelineThumb');
        const currentTimeDisplay = document.getElementById('currentTime');
        const volumeSlider = document.getElementById('volumeSlider');
        const volumeIcon = document.getElementById('volumeIcon');
        const speedBtn = document.getElementById('speedBtn');
        const speedDisplay = document.getElementById('speedDisplay');
        const likeBtn = document.getElementById('likeBtn');
        const shareBtn = document.getElementById('shareBtn');

        // Initialize
        audio.currentTime = startTime;
        audio.playbackRate = <?= $playback_speed ?>;
        audio.volume = <?= $progress ? $progress['volume'] : 1.0 ?>;
        volumeSlider.value = audio.volume * 100;

        // Play/Pause
        playPauseBtn.addEventListener('click', () => {
            if (audio.paused) {
                audio.play();
                playPauseIcon.className = 'bi bi-pause-fill';
            } else {
                audio.pause();
                playPauseIcon.className = 'bi bi-play-fill';
            }
        });

        // Timeline Update
        audio.addEventListener('timeupdate', () => {
            const progress = (audio.currentTime / audio.duration) * 100;
            timelineProgress.style.width = progress + '%';
            timelineThumb.style.left = progress + '%';
            currentTimeDisplay.textContent = formatTime(audio.currentTime);

            // Save progress every 5 seconds
            if (Math.floor(audio.currentTime) % 5 === 0) {
                saveProgress();
            }
        });

        // Timeline Click
        timeline.addEventListener('click', (e) => {
            const rect = timeline.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const percentage = clickX / rect.width;
            audio.currentTime = audio.duration * percentage;
        });

        // Volume Control
        volumeSlider.addEventListener('input', (e) => {
            audio.volume = e.target.value / 100;
            updateVolumeIcon();
        });

        function updateVolumeIcon() {
            if (audio.volume === 0) {
                volumeIcon.className = 'bi bi-volume-mute';
            } else if (audio.volume < 0.5) {
                volumeIcon.className = 'bi bi-volume-down';
            } else {
                volumeIcon.className = 'bi bi-volume-up';
            }
        }

        document.getElementById('volumeBtn').addEventListener('click', () => {
            if (audio.volume > 0) {
                audio.dataset.prevVolume = audio.volume;
                audio.volume = 0;
                volumeSlider.value = 0;
            } else {
                audio.volume = audio.dataset.prevVolume || 1;
                volumeSlider.value = audio.volume * 100;
            }
            updateVolumeIcon();
        });

        // Speed Control
        const speeds = [0.5, 0.75, 1.0, 1.25, 1.5, 1.75, 2.0];
        let currentSpeedIndex = speeds.indexOf(<?= $playback_speed ?>);

        speedBtn.addEventListener('click', () => {
            currentSpeedIndex = (currentSpeedIndex + 1) % speeds.length;
            audio.playbackRate = speeds[currentSpeedIndex];
            speedDisplay.textContent = speeds[currentSpeedIndex] + 'x';
            saveProgress();
        });

        // Skip Controls
        document.getElementById('rewind15Btn').addEventListener('click', () => {
            audio.currentTime = Math.max(0, audio.currentTime - 15);
        });

        document.getElementById('forward15Btn').addEventListener('click', () => {
            audio.currentTime = Math.min(audio.duration, audio.currentTime + 15);
        });

        // Save Progress to Database
        function saveProgress() {
            const data = {
                podcast_id: podcastId,
                user_id: userId,
                guest_token: guestToken,
                current_time: Math.floor(audio.currentTime),
                duration: Math.floor(audio.duration),
                playback_speed: audio.playbackRate,
                volume: audio.volume
            };

            fetch('api/podcasts/save-progress.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
        }

        // Save progress on page unload
        window.addEventListener('beforeunload', saveProgress);

        // Like/Unlike
        likeBtn.addEventListener('click', async () => {
            const response = await fetch('api/podcasts/toggle-like.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({podcast_id: podcastId, user_id: userId, guest_token: guestToken})
            });
            const data = await response.json();
            
            if (data.success) {
                likeBtn.classList.toggle('active');
                likeBtn.querySelector('i').className = data.liked ? 'bi bi-heart-fill' : 'bi bi-heart';
                document.getElementById('likeCount').textContent = data.like_count;
            }
        });

        // Share
        shareBtn.addEventListener('click', () => {
            if (navigator.share) {
                navigator.share({
                    title: '<?= addslashes($podcast['title']) ?>',
                    text: 'Listen to this podcast!',
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(window.location.href);
                alert('Link copied to clipboard!');
            }
        });

        // Format Time
        function formatTime(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = Math.floor(seconds % 60);
            
            if (h > 0) {
                return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
            }
            return `${m}:${s.toString().padStart(2, '0')}`;
        }

        // Keyboard Shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space') {
                e.preventDefault();
                playPauseBtn.click();
            } else if (e.code === 'ArrowLeft') {
                audio.currentTime -= 5;
            } else if (e.code === 'ArrowRight') {
                audio.currentTime += 5;
            } else if (e.code === 'ArrowUp') {
                audio.volume = Math.min(1, audio.volume + 0.1);
                volumeSlider.value = audio.volume * 100;
                updateVolumeIcon();
            } else if (e.code === 'ArrowDown') {
                audio.volume = Math.max(0, audio.volume - 0.1);
                volumeSlider.value = audio.volume * 100;
                updateVolumeIcon();
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
