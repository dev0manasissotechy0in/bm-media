<?php
require_once 'config/config.php';

$page_title = 'Stock Market Live';
$page_description = 'Live stock market updates, Sensex, Nifty, top gainers and losers';

include 'includes/header.php';
?>

<script>
const PAGE_TYPE = 'market';
const ENABLE_LIVE_UPDATES = true;
</script>

<div class="container py-4">
    <div class="dashboard-header mb-4">
        <h1 class="display-5"><i class="bi bi-graph-up"></i> Stock Market Live</h1>
        <p class="text-muted">Real-time updates every <?= LIVE_UPDATE_INTERVAL ?> seconds</p>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Major Indices -->
    <div class="row g-3 mb-4" id="indicesContainer">
        <!-- Populated by JavaScript -->
    </div>

    <!-- Market Sentiment -->
    <div class="card mb-4" id="sentimentCard" style="display: none;">
        <div class="card-body">
            <h5 class="card-title">Market Sentiment</h5>
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="sentiment-stat">
                        <div class="fs-3 fw-bold text-success" id="advancingCount">0</div>
                        <div class="text-muted">Advancing</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="sentiment-stat">
                        <div class="fs-3 fw-bold text-danger" id="decliningCount">0</div>
                        <div class="text-muted">Declining</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="sentiment-stat">
                        <div class="fs-3 fw-bold text-secondary" id="unchangedCount">0</div>
                        <div class="text-muted">Unchanged</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top Gainers -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-arrow-up-circle"></i> Top Gainers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Symbol</th>
                                    <th>Price</th>
                                    <th>Change</th>
                                    <th>% Change</th>
                                </tr>
                            </thead>
                            <tbody id="gainersTable">
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Losers -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-arrow-down-circle"></i> Top Losers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Symbol</th>
                                    <th>Price</th>
                                    <th>Change</th>
                                    <th>% Change</th>
                                </tr>
                            </thead>
                            <tbody id="losersTable">
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Most Active -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-activity"></i> Most Active</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Price</th>
                            <th>Change</th>
                            <th>% Change</th>
                            <th>Volume</th>
                        </tr>
                    </thead>
                    <tbody id="activeTable">
                        <tr>
                            <td colspan="5" class="text-center text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Market News -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-newspaper"></i> Market News</h5>
        </div>
        <div class="card-body">
            <div id="marketNewsContainer">
                <p class="text-muted">Loading news...</p>
            </div>
        </div>
    </div>

    <!-- Last Updated -->
    <div class="text-center text-muted mt-4">
        <small>Last updated: <span id="lastUpdated">-</span></small>
    </div>
</div>

<script>
// Load market data on page load
$(document).ready(function() {
    loadMarketData();
});

function loadMarketData() {
    $('#loadingIndicator').show();
    
    $.ajax({
        url: '<?= BASE_URL ?>/api/market/get_indices.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#loadingIndicator').hide();
            
            if (data.success) {
                displayIndices(data.indices);
                displayGainers(data.gainers);
                displayLosers(data.losers);
                displayActive(data.active);
                displaySentiment(data.sentiment);
                displayMarketNews(data.news);
                $('#lastUpdated').text(new Date().toLocaleTimeString());
            } else {
                showToast('Error loading market data', 'error');
            }
        },
        error: function() {
            $('#loadingIndicator').hide();
            showToast('Failed to load market data', 'error');
        }
    });
}

function displayIndices(indices) {
    let html = '';
    indices.forEach(index => {
        const changeClass = index.change >= 0 ? 'text-success' : 'text-danger';
        const changeIcon = index.change >= 0 ? 'bi-arrow-up' : 'bi-arrow-down';
        
        html += `
            <div class="col-md-6">
                <div class="card index-card">
                    <div class="card-body">
                        <h5 class="card-title">${index.name}</h5>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="index-value fs-3 fw-bold">${index.value.toFixed(2)}</div>
                            <div class="${changeClass}">
                                <div class="fs-5">
                                    <i class="bi ${changeIcon}"></i> ${Math.abs(index.change).toFixed(2)}
                                </div>
                                <div>${index.change_percent.toFixed(2)}%</div>
                            </div>
                        </div>
                        ${index.last_updated ? `<small class="text-muted">Updated: ${new Date(index.last_updated).toLocaleTimeString()}</small>` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    $('#indicesContainer').html(html);
}

function displayGainers(gainers) {
    let html = '';
    if (gainers && gainers.length > 0) {
        gainers.forEach(stock => {
            html += `
                <tr>
                    <td><strong>${stock.symbol}</strong></td>
                    <td>₹${stock.price.toFixed(2)}</td>
                    <td class="text-success">+${stock.change.toFixed(2)}</td>
                    <td class="text-success">+${stock.change_percent.toFixed(2)}%</td>
                </tr>
            `;
        });
    } else {
        html = '<tr><td colspan="4" class="text-center text-muted">No data available</td></tr>';
    }
    $('#gainersTable').html(html);
}

function displayLosers(losers) {
    let html = '';
    if (losers && losers.length > 0) {
        losers.forEach(stock => {
            html += `
                <tr>
                    <td><strong>${stock.symbol}</strong></td>
                    <td>₹${stock.price.toFixed(2)}</td>
                    <td class="text-danger">${stock.change.toFixed(2)}</td>
                    <td class="text-danger">${stock.change_percent.toFixed(2)}%</td>
                </tr>
            `;
        });
    } else {
        html = '<tr><td colspan="4" class="text-center text-muted">No data available</td></tr>';
    }
    $('#losersTable').html(html);
}

function displayActive(active) {
    let html = '';
    if (active && active.length > 0) {
        active.forEach(stock => {
            const changeClass = stock.change >= 0 ? 'text-success' : 'text-danger';
            html += `
                <tr>
                    <td><strong>${stock.symbol}</strong></td>
                    <td>₹${stock.price.toFixed(2)}</td>
                    <td class="${changeClass}">${stock.change.toFixed(2)}</td>
                    <td class="${changeClass}">${stock.change_percent.toFixed(2)}%</td>
                    <td>${formatVolume(stock.volume)}</td>
                </tr>
            `;
        });
    } else {
        html = '<tr><td colspan="5" class="text-center text-muted">No data available</td></tr>';
    }
    $('#activeTable').html(html);
}

function displaySentiment(sentiment) {
    if (sentiment) {
        $('#advancingCount').text(sentiment.advancing || 0);
        $('#decliningCount').text(sentiment.declining || 0);
        $('#unchangedCount').text(sentiment.unchanged || 0);
        $('#sentimentCard').show();
    }
}

function displayMarketNews(news) {
    let html = '';
    if (news && news.length > 0) {
        news.forEach(item => {
            html += `
                <div class="news-item mb-3 pb-3 border-bottom">
                    <h6>${item.title}</h6>
                    <small class="text-muted">${new Date(item.created_at).toLocaleString()}</small>
                </div>
            `;
        });
    } else {
        html = '<p class="text-muted">No news available</p>';
    }
    $('#marketNewsContainer').html(html);
}

function formatVolume(volume) {
    if (volume >= 10000000) {
        return (volume / 10000000).toFixed(2) + ' Cr';
    } else if (volume >= 100000) {
        return (volume / 100000).toFixed(2) + ' L';
    }
    return volume.toLocaleString();
}
</script>

<style>
.index-card {
    border-left: 4px solid #007bff;
    transition: transform 0.2s;
}

.index-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.sentiment-stat {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}
</style>

<?php include 'includes/footer.php'; ?>
