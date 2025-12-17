<?php
require_once 'config/config.php';

$page_title = 'Election Results Dashboard';
$page_description = 'Live election results, constituency-wise updates, party-wise seat counts, and real-time analysis';

$db = Database::getInstance();

// Initial data load
$summary_data = $db->fetchOne("
    SELECT 
        (SELECT COUNT(*) FROM election_constituencies WHERE status = 'active') as total_seats,
        (SELECT COUNT(DISTINCT constituency_id) FROM election_results WHERE status IN ('won', 'lost')) as seats_counted,
        (SELECT SUM(total_voters) FROM election_constituencies WHERE status = 'active') as total_voters
");

$party_results = $db->fetchAll("
    SELECT 
        p.*,
        COUNT(CASE WHEN r.status = 'won' THEN 1 END) as seats_won,
        COUNT(CASE WHEN r.status = 'leading' THEN 1 END) as seats_leading
    FROM election_parties p
    LEFT JOIN election_results r ON p.id = r.party_id
    WHERE p.status = 'active'
    GROUP BY p.id
    ORDER BY seats_won DESC, seats_leading DESC
");

include 'includes/header.php';
?>

<script>
const PAGE_TYPE = 'election';
const ENABLE_LIVE_UPDATES = true;
const LIVE_UPDATE_INTERVAL = <?= ELECTION_UPDATE_INTERVAL ?>;
</script>

<div class="container">
    <div class="election-header mb-4">
        <h1 class="display-4">
            <i class="bi bi-ballot-fill text-primary"></i> Election Results Dashboard
        </h1>
        <p class="lead">Live updates and comprehensive analysis</p>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Updates every 10 seconds | Last updated: <span id="lastUpdateTime"><?= date('H:i:s') ?></span>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="election-summary mb-4">
        <div class="summary-card">
            <h3 id="totalSeats"><?= $summary_data['total_seats'] ?></h3>
            <p class="text-muted">Total Seats</p>
        </div>
        <div class="summary-card">
            <h3 id="seatsCounted" class="text-success"><?= $summary_data['seats_counted'] ?></h3>
            <p class="text-muted">Results Declared</p>
        </div>
        <div class="summary-card">
            <h3 id="seatsPending" class="text-warning"><?= $summary_data['total_seats'] - $summary_data['seats_counted'] ?></h3>
            <p class="text-muted">Pending Results</p>
        </div>
        <div class="summary-card">
            <h3 id="voterTurnout" class="text-info">65.5%</h3>
            <p class="text-muted">Voter Turnout</p>
        </div>
    </div>

    <!-- Party-wise Results -->
    <section class="mb-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-bar-chart-fill"></i> Party-wise Results</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6">
                        <canvas id="partyBarChart"></canvas>
                    </div>
                    <div class="col-lg-6">
                        <canvas id="partyDonutChart"></canvas>
                    </div>
                </div>
                <div class="table-responsive mt-4">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Party</th>
                                <th>Won</th>
                                <th>Leading</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="partyTableBody">
                            <?php foreach ($party_results as $party): ?>
                            <tr>
                                <td>
                                    <span class="party-color-box" style="background-color: <?= $party['color_code'] ?>"></span>
                                    <?= htmlspecialchars($party['name']) ?> (<?= htmlspecialchars($party['short_name']) ?>)
                                </td>
                                <td><strong><?= $party['seats_won'] ?></strong></td>
                                <td><?= $party['seats_leading'] ?></td>
                                <td><?= $party['seats_won'] + $party['seats_leading'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Constituency Results -->
    <section class="mb-5">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0"><i class="bi bi-list-check"></i> Constituency-wise Results</h4>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <input type="text" id="constituencySearch" class="form-control" placeholder="Search constituency...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover" id="constituencyTable">
                        <thead class="table-light">
                            <tr>
                                <th>Constituency</th>
                                <th>State</th>
                                <th>Leading/Won Candidate</th>
                                <th>Party</th>
                                <th>Votes</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="constituencyTableBody">
                            <!-- Will be populated via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Opinion & Exit Polls -->
    <section class="mb-5">
        <div class="card">
            <div class="card-header bg-warning">
                <h4 class="mb-0"><i class="bi bi-graph-up"></i> Opinion & Exit Polls</h4>
            </div>
            <div class="card-body">
                <canvas id="pollsChart"></canvas>
            </div>
        </div>
    </section>

    <!-- Live Election News -->
    <section class="mb-5">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0"><i class="bi bi-newspaper"></i> Live Election News</h4>
            </div>
            <div class="card-body">
                <div id="electionNewsContainer">
                    <!-- Will be populated via AJAX -->
                </div>
            </div>
        </div>
    </section>

    <!-- Live Updates -->
    <section class="mb-5">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0"><i class="bi bi-broadcast"></i> Live Updates</h4>
            </div>
            <div class="card-body">
                <div id="liveUpdatesContainer" class="live-updates" style="max-height: 400px; overflow-y: auto;">
                    <!-- Will be populated via AJAX -->
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize charts
    initElectionCharts();
    
    // Load constituency data
    loadConstituencyData();
    
    // Load election news
    loadElectionNews();
    
    // Load live updates
    loadLiveUpdates();
    
    // Search functionality
    $('#constituencySearch').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('#constituencyTableBody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Auto-refresh
    setInterval(function() {
        updateElectionResults();
        $('#lastUpdateTime').text(new Date().toLocaleTimeString());
    }, LIVE_UPDATE_INTERVAL);
});

let partyBarChart, partyDonutChart;

function initElectionCharts() {
    // Bar Chart
    const barCtx = document.getElementById('partyBarChart').getContext('2d');
    partyBarChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($p) { return "'" . $p['short_name'] . "'"; }, $party_results)); ?>],
            datasets: [{
                label: 'Seats Won',
                data: [<?php echo implode(',', array_column($party_results, 'seats_won')); ?>],
                backgroundColor: [<?php echo implode(',', array_map(function($p) { return "'" . $p['color_code'] . "'"; }, $party_results)); ?>]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: { display: true, text: 'Seats Won by Party' }
            }
        }
    });
    
    // Donut Chart
    const donutCtx = document.getElementById('partyDonutChart').getContext('2d');
    partyDonutChart = new Chart(donutCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php echo implode(',', array_map(function($p) { return "'" . $p['short_name'] . "'"; }, $party_results)); ?>],
            datasets: [{
                data: [<?php echo implode(',', array_map(function($p) { return $p['seats_won'] + $p['seats_leading']; }, $party_results)); ?>],
                backgroundColor: [<?php echo implode(',', array_map(function($p) { return "'" . $p['color_code'] . "'"; }, $party_results)); ?>]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: { display: true, text: 'Total Seats Distribution' }
            }
        }
    });
}

function loadConstituencyData() {
    $.ajax({
        url: BASE_URL + '/api/election/get_results.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.constituencies) {
                updateConstituencyTable(response.data.constituencies);
            }
        }
    });
}

function updateConstituencyTable(constituencies) {
    let html = '';
    constituencies.forEach(function(cons) {
        const statusBadge = cons.result_status === 'won' ? 
            '<span class="badge bg-success">Won</span>' : 
            '<span class="badge bg-warning">Leading</span>';
        
        html += `
            <tr>
                <td><strong>${cons.constituency_name}</strong></td>
                <td>${cons.state}</td>
                <td>${cons.candidate_name}</td>
                <td>
                    <span class="party-color-box" style="background-color: ${cons.party_color}"></span>
                    ${cons.party_short_name}
                </td>
                <td>${cons.votes.toLocaleString()} (${cons.vote_percentage}%)</td>
                <td>${statusBadge}</td>
            </tr>
        `;
    });
    $('#constituencyTableBody').html(html);
}

function loadElectionNews() {
    $.ajax({
        url: BASE_URL + '/api/election/get_results.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.live_news) {
                updateElectionNews(response.data.live_news);
            }
        }
    });
}

function updateElectionNews(news) {
    let html = '';
    news.forEach(function(item) {
        const breaking = item.is_breaking ? '<span class="badge bg-danger me-2">BREAKING</span>' : '';
        html += `
            <div class="border-bottom pb-3 mb-3">
                ${breaking}
                <h6>${item.title}</h6>
                <p class="text-muted small mb-0">${item.description}</p>
                <small class="text-muted">${item.published_at}</small>
            </div>
        `;
    });
    $('#electionNewsContainer').html(html);
}

function loadLiveUpdates() {
    $.ajax({
        url: BASE_URL + '/api/election/get_results.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data.live_updates) {
                let html = '';
                response.data.live_updates.forEach(function(update) {
                    html += `
                        <div class="alert alert-light border-start border-primary border-3">
                            <small class="text-muted">${update.created_at}</small>
                            <p class="mb-0 mt-1">${update.update_text}</p>
                        </div>
                    `;
                });
                $('#liveUpdatesContainer').html(html);
            }
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
