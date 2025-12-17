<?php
/**
 * Cricket API - Scorecard
 * Returns detailed scorecard for a match
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    $match_id = $_GET['match_id'] ?? null;
    
    if (!$match_id) {
        throw new Exception('Match ID is required');
    }
    
    // Fetch match details
    $match = $db->fetchOne("
        SELECT 
            m.*,
            t1.name as team1_name,
            t1.short_name as team1_short,
            t2.name as team2_name,
            t2.short_name as team2_short
        FROM cricket_matches m
        JOIN cricket_teams t1 ON m.team1_id = t1.id
        JOIN cricket_teams t2 ON m.team2_id = t2.id
        WHERE m.id = ?
    ", [$match_id]);
    
    if (!$match) {
        throw new Exception('Match not found');
    }
    
    // Fetch batting scorecard
    $batting = $db->fetchAll("
        SELECT 
            s.*,
            p.name as player_name,
            p.photo as player_photo,
            p.batting_style,
            p.bowling_style
        FROM cricket_scores s
        JOIN cricket_players p ON s.player_id = p.id
        WHERE s.match_id = ?
        ORDER BY s.team_id, s.batting_order ASC
    ", [$match_id]);
    
    // Fetch bowling figures
    $bowling = $db->fetchAll("
        SELECT 
            player_id,
            SUM(overs_bowled) as overs,
            SUM(runs_conceded) as runs,
            SUM(wickets_taken) as wickets,
            SUM(maidens) as maidens,
            p.name as player_name
        FROM cricket_scores s
        JOIN cricket_players p ON s.player_id = p.id
        WHERE s.match_id = ? AND s.overs_bowled > 0
        GROUP BY s.player_id
        ORDER BY wickets DESC, runs ASC
    ", [$match_id]);
    
    // Calculate economy rate for bowlers
    foreach ($bowling as &$bowler) {
        $bowler['economy'] = $bowler['overs'] > 0 
            ? round($bowler['runs'] / $bowler['overs'], 2) 
            : 0;
    }
    
    // Fetch partnerships
    $partnerships = $db->fetchAll("
        SELECT 
            wicket_number,
            runs_added,
            balls_faced,
            p1.name as batsman1_name,
            p2.name as batsman2_name
        FROM cricket_partnerships cp
        JOIN cricket_players p1 ON cp.batsman1_id = p1.id
        JOIN cricket_players p2 ON cp.batsman2_id = p2.id
        WHERE cp.match_id = ?
        ORDER BY cp.wicket_number ASC
    ", [$match_id]);
    
    // Fetch fall of wickets
    $fall_of_wickets = $db->fetchAll("
        SELECT 
            s.runs_scored as team_score,
            s.batting_order as wicket_number,
            p.name as batsman_out,
            s.dismissal_type
        FROM cricket_scores s
        JOIN cricket_players p ON s.player_id = p.id
        WHERE s.match_id = ? AND s.is_out = 1
        ORDER BY s.batting_order ASC
    ", [$match_id]);
    
    // Group by teams
    $team1_batting = array_filter($batting, fn($b) => $b['team_id'] == $match['team1_id']);
    $team2_batting = array_filter($batting, fn($b) => $b['team_id'] == $match['team2_id']);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'match' => $match,
            'team1_innings' => [
                'batting' => array_values($team1_batting),
                'total_runs' => array_sum(array_column($team1_batting, 'runs_scored')),
                'total_wickets' => count(array_filter($team1_batting, fn($b) => $b['is_out'])),
                'overs' => $match['team1_overs']
            ],
            'team2_innings' => [
                'batting' => array_values($team2_batting),
                'total_runs' => array_sum(array_column($team2_batting, 'runs_scored')),
                'total_wickets' => count(array_filter($team2_batting, fn($b) => $b['is_out'])),
                'overs' => $match['team2_overs']
            ],
            'bowling' => $bowling,
            'partnerships' => $partnerships,
            'fall_of_wickets' => $fall_of_wickets
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code($e->getMessage() === 'Match ID is required' ? 400 : 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
