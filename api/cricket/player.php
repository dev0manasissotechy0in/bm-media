<?php
/**
 * Cricket API - Player Details
 * Returns detailed information about a cricket player
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    $player_id = $_GET['id'] ?? null;
    
    if (!$player_id) {
        throw new Exception('Player ID is required');
    }
    
    // Fetch player details
    $player = $db->fetchOne("
        SELECT 
            p.*,
            t.name as team_name,
            t.short_name as team_short_name,
            t.logo as team_logo
        FROM cricket_players p
        LEFT JOIN cricket_teams t ON p.team_id = t.id
        WHERE p.id = ?
    ", [$player_id]);
    
    if (!$player) {
        throw new Exception('Player not found');
    }
    
    // Fetch player statistics from recent matches
    $recent_stats = $db->fetchAll("
        SELECT 
            s.*,
            m.match_name,
            m.match_date,
            m.venue,
            t1.name as opponent_team
        FROM cricket_scores s
        JOIN cricket_matches m ON s.match_id = m.id
        LEFT JOIN cricket_teams t1 ON (
            CASE 
                WHEN s.team_id = m.team1_id THEN m.team2_id 
                ELSE m.team1_id 
            END = t1.id
        )
        WHERE s.player_id = ?
        ORDER BY m.match_date DESC
        LIMIT 10
    ", [$player_id]);
    
    // Calculate career statistics
    $career_stats = $db->fetchOne("
        SELECT 
            COUNT(DISTINCT match_id) as matches_played,
            SUM(runs_scored) as total_runs,
            MAX(runs_scored) as highest_score,
            AVG(runs_scored) as average_runs,
            SUM(balls_faced) as total_balls,
            SUM(fours) as total_fours,
            SUM(sixes) as total_sixes,
            SUM(wickets_taken) as total_wickets,
            SUM(overs_bowled) as total_overs,
            SUM(runs_conceded) as runs_conceded,
            COUNT(CASE WHEN runs_scored >= 50 THEN 1 END) as fifties,
            COUNT(CASE WHEN runs_scored >= 100 THEN 1 END) as hundreds
        FROM cricket_scores
        WHERE player_id = ?
    ", [$player_id]);
    
    // Calculate strike rate and economy
    $career_stats['strike_rate'] = $career_stats['total_balls'] > 0 
        ? round(($career_stats['total_runs'] / $career_stats['total_balls']) * 100, 2) 
        : 0;
    $career_stats['bowling_average'] = $career_stats['total_wickets'] > 0 
        ? round($career_stats['runs_conceded'] / $career_stats['total_wickets'], 2) 
        : 0;
    $career_stats['economy_rate'] = $career_stats['total_overs'] > 0 
        ? round($career_stats['runs_conceded'] / $career_stats['total_overs'], 2) 
        : 0;
    
    // Add photo URL
    $player['photo_url'] = !empty($player['photo']) 
        ? UPLOADS_URL . '/cricket/players/' . $player['photo'] 
        : null;
    $player['team_logo_url'] = !empty($player['team_logo']) 
        ? UPLOADS_URL . '/cricket/teams/' . $player['team_logo'] 
        : null;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'player' => $player,
            'career_stats' => $career_stats,
            'recent_performances' => $recent_stats
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code($e->getMessage() === 'Player ID is required' ? 400 : 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
