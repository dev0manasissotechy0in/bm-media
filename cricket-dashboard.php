<?php
require_once 'config/config.php';

$page_title = 'Live Cricket Scores';
$page_description = 'Follow live cricket scores, match updates, and ball-by-ball commentary';

include 'includes/header.php';
?>

<script>
const PAGE_TYPE = 'cricket';
const ENABLE_LIVE_UPDATES = true;
</script>

<div class="container py-4">
    <div class="dashboard-header mb-4">
        <h1 class="display-5"><i class="bi bi-award"></i> Live Cricket Scores</h1>
        <p class="text-muted">Real-time updates every <?= LIVE_UPDATE_INTERVAL ?> seconds</p>
    </div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Live Matches -->
    <div id="liveMatches" class="mb-4">
        <h3 class="mb-3">
            <span class="badge bg-danger pulse">LIVE</span> Live Matches
        </h3>
        <div class="row g-3" id="liveMatchesContainer">
            <!-- Populated by JavaScript -->
        </div>
    </div>

    <!-- Upcoming Matches -->
    <div id="upcomingMatches" class="mb-4">
        <h3 class="mb-3">
            <i class="bi bi-calendar-event"></i> Upcoming Matches
        </h3>
        <div class="row g-3" id="upcomingMatchesContainer">
            <!-- Populated by JavaScript -->
        </div>
    </div>

    <!-- Recent Matches -->
    <div id="recentMatches">
        <h3 class="mb-3">
            <i class="bi bi-clock-history"></i> Recent Results
        </h3>
        <div class="row g-3" id="recentMatchesContainer">
            <!-- Populated by JavaScript -->
        </div>
    </div>

    <!-- Last Updated -->
    <div class="text-center text-muted mt-4">
        <small>Last updated: <span id="lastUpdated">-</span></small>
    </div>
</div>

<script>
// Load matches on page load
$(document).ready(function() {
    loadCricketData();
});

function loadCricketData() {
    $('#loadingIndicator').show();
    
    $.ajax({
        url: '<?= BASE_URL ?>/api/cricket/live_matches.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#loadingIndicator').hide();
            
            if (data.success) {
                displayMatches(data.matches);
                $('#lastUpdated').text(new Date().toLocaleTimeString());
            } else {
                showToast('Error loading cricket data', 'error');
            }
        },
        error: function() {
            $('#loadingIndicator').hide();
            showToast('Failed to load cricket data', 'error');
        }
    });
}

function displayMatches(matches) {
    const liveMatches = matches.filter(m => m.match_status === 'live');
    const upcomingMatches = matches.filter(m => m.match_status === 'upcoming');
    const completedMatches = matches.filter(m => m.match_status === 'completed');
    
    // Display live matches
    if (liveMatches.length > 0) {
        let html = '';
        liveMatches.forEach(match => {
            html += createMatchCard(match, true);
        });
        $('#liveMatchesContainer').html(html);
        $('#liveMatches').show();
    } else {
        $('#liveMatches').hide();
    }
    
    // Display upcoming matches
    if (upcomingMatches.length > 0) {
        let html = '';
        upcomingMatches.forEach(match => {
            html += createMatchCard(match, false);
        });
        $('#upcomingMatchesContainer').html(html);
        $('#upcomingMatches').show();
    } else {
        $('#upcomingMatches').hide();
    }
    
    // Display completed matches
    if (completedMatches.length > 0) {
        let html = '';
        completedMatches.forEach(match => {
            html += createMatchCard(match, false);
        });
        $('#recentMatchesContainer').html(html);
        $('#recentMatches').show();
    } else {
        $('#recentMatches').hide();
    }
}

function createMatchCard(match, isLive) {
    const team1Score = match.team1_score || '-';
    const team2Score = match.team2_score || '-';
    
    return `
        <div class="col-md-6">
            <div class="card cricket-match-card ${isLive ? 'border-danger' : ''}">
                <div class="card-body">
                    ${isLive ? '<span class="badge bg-danger pulse mb-2">LIVE</span>' : ''}
                    
                    <div class="match-info mb-2">
                        <small class="text-muted">
                            <i class="bi bi-trophy"></i> ${match.tournament || 'Tournament'}
                            ${match.match_format ? ' • ' + match.match_format.toUpperCase() : ''}
                        </small>
                    </div>
                    
                    <div class="teams">
                        <div class="team d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>${match.team1_name}</strong>
                                ${match.team1_flag ? `<img src="${match.team1_flag}" alt="" class="ms-1" width="20">` : ''}
                            </div>
                            <div class="score fs-5 fw-bold">${team1Score}</div>
                        </div>
                        
                        <div class="team d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${match.team2_name}</strong>
                                ${match.team2_flag ? `<img src="${match.team2_flag}" alt="" class="ms-1" width="20">` : ''}
                            </div>
                            <div class="score fs-5 fw-bold">${team2Score}</div>
                        </div>
                    </div>
                    
                    <div class="match-status mt-3 text-center">
                        <span class="badge ${getMatchStatusBadge(match.match_status)}">
                            ${match.status_text}
                        </span>
                    </div>
                    
                    ${match.venue ? `<div class="venue text-center text-muted small mt-2"><i class="bi bi-geo-alt"></i> ${match.venue}</div>` : ''}
                    ${match.match_date ? `<div class="match-date text-center text-muted small"><i class="bi bi-calendar"></i> ${new Date(match.match_date).toLocaleDateString()}</div>` : ''}
                </div>
            </div>
        </div>
    `;
}

function getMatchStatusBadge(status) {
    switch(status) {
        case 'live': return 'bg-danger';
        case 'upcoming': return 'bg-warning text-dark';
        case 'completed': return 'bg-success';
        default: return 'bg-secondary';
    }
}
</script>

<style>
.cricket-match-card {
    transition: transform 0.2s;
}

.cricket-match-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.cricket-match-card.border-danger {
    border-width: 2px;
}

.teams .team {
    padding: 8px;
    background: #f8f9fa;
    border-radius: 4px;
}
</style>

<?php include 'includes/footer.php'; ?>
