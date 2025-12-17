<?php
/**
 * Election Dashboard
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
$page_title = 'Election Dashboard';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Election Dashboard</h1>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 
        <strong>Special Feature:</strong> Election results tracking and visualization module.
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-vote-yea me-1"></i> Election Module
                </div>
                <div class="card-body">
                    <h5>Features:</h5>
                    <ul>
                        <li>Real-time election results display</li>
                        <li>Candidate profiles and information</li>
                        <li>Constituency-wise vote counting</li>
                        <li>Interactive maps and charts</li>
                        <li>Live updates via API integration</li>
                        <li>Historical election data comparison</li>
                        <li>Party-wise seat distribution</li>
                    </ul>
                    
                    <h5 class="mt-4">Database Schema:</h5>
                    <pre class="bg-light p-3"><code>CREATE TABLE elections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    election_date DATE,
    status ENUM('upcoming', 'ongoing', 'completed') DEFAULT 'upcoming',
    total_constituencies INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE candidates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT,
    name VARCHAR(255),
    party VARCHAR(255),
    constituency VARCHAR(255),
    photo VARCHAR(255),
    votes INT DEFAULT 0,
    status ENUM('leading', 'won', 'lost', 'counting') DEFAULT 'counting'
);

CREATE TABLE election_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    election_id INT,
    party VARCHAR(255),
    seats_won INT DEFAULT 0,
    vote_percentage DECIMAL(5,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);</code></pre>
                    
                    <h5 class="mt-4">API Integration:</h5>
                    <p>The election data API endpoint is available at: <code>api/election/get_results.php</code></p>
                    <p class="text-muted">This endpoint returns JSON data for constituency-wise results.</p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i> Quick Stats
                </div>
                <div class="card-body text-center">
                    <h6>Current Election</h6>
                    <p class="text-muted small">No active election</p>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <h5>0</h5>
                            <small>Total Seats</small>
                        </div>
                        <div class="col-6">
                            <h5>0</h5>
                            <small>Results</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tasks me-1"></i> Admin Actions
                </div>
                <div class="card-body">
                    <button class="btn btn-primary w-100 mb-2" disabled>
                        <i class="fas fa-plus"></i> Add New Election
                    </button>
                    <button class="btn btn-info w-100 mb-2" disabled>
                        <i class="fas fa-sync"></i> Update Results
                    </button>
                    <button class="btn btn-success w-100" disabled>
                        <i class="fas fa-chart-bar"></i> View Analytics
                    </button>
                    <p class="text-muted small mt-3">
                        <i class="fas fa-info-circle"></i> Full functionality requires database setup
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <i class="fas fa-code me-1"></i> Frontend Display
        </div>
        <div class="card-body">
            <p>Election results are displayed on: <code><?= BASE_URL ?>/election-dashboard.php</code></p>
            <p>The frontend uses AJAX to fetch results from <code>api/election/get_results.php</code></p>
            
            <a href="<?= BASE_URL ?>/election-dashboard.php" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-external-link-alt"></i> View Public Dashboard
            </a>
        </div>
    </div>
    
    <a href="dashboard.php" class="btn btn-primary mt-3">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php include 'includes/footer.php'; ?>