<?php
/**
 * Cricket Dashboard
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Functions.php';
require_once '../includes/Session.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$page_title = 'Cricket Dashboard';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Cricket Dashboard</h1>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 
        <strong>Special Feature:</strong> Live cricket scores and match updates module.
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-baseball-ball me-1"></i> Cricket Module
                </div>
                <div class="card-body">
                    <h5>Features:</h5>
                    <ul>
                        <li>Live match scores and updates</li>
                        <li>Ball-by-ball commentary</li>
                        <li>Team and player statistics</li>
                        <li>Match schedules and fixtures</li>
                        <li>Points table and rankings</li>
                        <li>Historical match data</li>
                        <li>API integration with cricket data providers</li>
                    </ul>
                    
                    <h5 class="mt-4">Database Schema:</h5>
                    <pre class="bg-light p-3"><code>CREATE TABLE cricket_matches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    match_id VARCHAR(50) UNIQUE,
    team1 VARCHAR(100),
    team2 VARCHAR(100),
    match_type ENUM('Test', 'ODI', 'T20', 'T10') DEFAULT 'T20',
    venue VARCHAR(255),
    match_date DATETIME,
    status ENUM('upcoming', 'live', 'completed') DEFAULT 'upcoming',
    team1_score VARCHAR(50),
    team2_score VARCHAR(50),
    result TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE cricket_players (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    team VARCHAR(100),
    role ENUM('Batsman', 'Bowler', 'All-rounder', 'Wicket-keeper'),
    photo VARCHAR(255),
    matches INT DEFAULT 0,
    runs INT DEFAULT 0,
    wickets INT DEFAULT 0
);</code></pre>
                    
                    <h5 class="mt-4">API Integration:</h5>
                    <p>The cricket API endpoint is available at: <code>api/cricket/live_matches.php</code></p>
                    <p class="text-muted">This endpoint returns live match data in JSON format.</p>
                    
                    <h5 class="mt-4">Recommended Data Providers:</h5>
                    <ul>
                        <li><strong>Cricbuzz API:</strong> Comprehensive cricket data</li>
                        <li><strong>ESPNcricinfo API:</strong> Detailed match statistics</li>
                        <li><strong>CricAPI:</strong> Free tier available</li>
                        <li><strong>RapidAPI Cricket:</strong> Multiple cricket data sources</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i> Quick Stats
                </div>
                <div class="card-body text-center">
                    <h6>Live Matches</h6>
                    <h2 class="text-success">0</h2>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <h5>0</h5>
                            <small>Upcoming</small>
                        </div>
                        <div class="col-6">
                            <h5>0</h5>
                            <small>Completed</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-tasks me-1"></i> Admin Actions
                </div>
                <div class="card-body">
                    <button class="btn btn-primary w-100 mb-2" disabled>
                        <i class="fas fa-plus"></i> Add Match
                    </button>
                    <button class="btn btn-info w-100 mb-2" disabled>
                        <i class="fas fa-sync"></i> Sync Live Scores
                    </button>
                    <button class="btn btn-success w-100" disabled>
                        <i class="fas fa-calendar"></i> Manage Schedule
                    </button>
                    <p class="text-muted small mt-3">
                        <i class="fas fa-info-circle"></i> Full functionality requires database setup
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-lightbulb me-1"></i> Tips
                </div>
                <div class="card-body">
                    <ul class="small">
                        <li>Use caching for API responses</li>
                        <li>Update scores every 30 seconds during live matches</li>
                        <li>Store historical data locally</li>
                        <li>Implement fallback for API failures</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <i class="fas fa-code me-1"></i> Frontend Display
        </div>
        <div class="card-body">
            <p>Cricket scores are displayed on: <code><?= BASE_URL ?>/cricket-dashboard.php</code></p>
            <p>The frontend uses AJAX to fetch live scores from <code>api/cricket/live_matches.php</code></p>
            
            <a href="<?= BASE_URL ?>/cricket-dashboard.php" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-external-link-alt"></i> View Public Dashboard
            </a>
        </div>
    </div>
    
    <a href="dashboard.php" class="btn btn-primary mt-3">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php include 'includes/footer.php'; ?>