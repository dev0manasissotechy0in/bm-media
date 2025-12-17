<?php
/**
 * Market Dashboard
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
$page_title = 'Market Dashboard';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Market Dashboard</h1>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 
        <strong>Special Feature:</strong> Stock market indices and financial data module.
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i> Market Module
                </div>
                <div class="card-body">
                    <h5>Features:</h5>
                    <ul>
                        <li>Live stock market indices (NSE, BSE, NASDAQ, etc.)</li>
                        <li>Real-time stock prices and changes</li>
                        <li>Currency exchange rates</li>
                        <li>Commodity prices (Gold, Silver, Crude Oil)</li>
                        <li>Market trends and analysis</li>
                        <li>Historical data charts</li>
                        <li>Watchlist for tracked stocks</li>
                        <li>Market news integration</li>
                    </ul>
                    
                    <h5 class="mt-4">Database Schema:</h5>
                    <pre class="bg-light p-3"><code>CREATE TABLE market_indices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(50) UNIQUE,
    name VARCHAR(255),
    market VARCHAR(100),
    current_value DECIMAL(15,2),
    change_value DECIMAL(15,2),
    change_percent DECIMAL(5,2),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE stocks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(50) UNIQUE,
    company_name VARCHAR(255),
    sector VARCHAR(100),
    current_price DECIMAL(15,2),
    change_value DECIMAL(15,2),
    change_percent DECIMAL(5,2),
    volume BIGINT,
    market_cap BIGINT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE currencies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_currency VARCHAR(10),
    to_currency VARCHAR(10),
    exchange_rate DECIMAL(15,6),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);</code></pre>
                    
                    <h5 class="mt-4">API Integration:</h5>
                    <p>The market API endpoint is available at: <code>api/market/get_indices.php</code></p>
                    <p class="text-muted">This endpoint returns market data in JSON format.</p>
                    
                    <h5 class="mt-4">Recommended Data Providers:</h5>
                    <ul>
                        <li><strong>Alpha Vantage:</strong> Free tier with stock, forex, and crypto data</li>
                        <li><strong>Yahoo Finance API:</strong> Comprehensive market data</li>
                        <li><strong>NSE/BSE API:</strong> Indian stock market data</li>
                        <li><strong>IEX Cloud:</strong> Real-time and historical market data</li>
                        <li><strong>Twelve Data:</strong> Stock, forex, and cryptocurrency API</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i> Quick Stats
                </div>
                <div class="card-body">
                    <h6 class="text-center">Market Status</h6>
                    <div class="text-center mb-3">
                        <span class="badge bg-secondary">Market Closed</span>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <h5>0</h5>
                            <small>Tracked Indices</small>
                        </div>
                        <div class="col-6">
                            <h5>0</h5>
                            <small>Tracked Stocks</small>
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
                        <i class="fas fa-plus"></i> Add Index
                    </button>
                    <button class="btn btn-info w-100 mb-2" disabled>
                        <i class="fas fa-sync"></i> Sync Market Data
                    </button>
                    <button class="btn btn-success w-100" disabled>
                        <i class="fas fa-cog"></i> API Settings
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
                        <li>Update data every 1-5 minutes during market hours</li>
                        <li>Use caching to reduce API calls</li>
                        <li>Display delayed data disclaimer</li>
                        <li>Show market timings clearly</li>
                        <li>Color code gains (green) and losses (red)</li>
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
            <p>Market data is displayed on: <code><?= BASE_URL ?>/market-dashboard.php</code></p>
            <p>The frontend uses AJAX to fetch live data from <code>api/market/get_indices.php</code></p>
            
            <a href="<?= BASE_URL ?>/market-dashboard.php" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-external-link-alt"></i> View Public Dashboard
            </a>
        </div>
    </div>
    
    <a href="dashboard.php" class="btn btn-primary mt-3">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php include 'includes/footer.php'; ?>