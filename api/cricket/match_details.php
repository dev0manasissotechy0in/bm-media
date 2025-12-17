<?php
/**
 * Cricket API - Match Details
 * Returns detailed information about a specific cricket match
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    $match_id = $_GET['id'] ?? null;
    
    if (!$match_id) {
        throw new Exception('Match ID is required');
    }
    
    // Fetch match details
    $match = $db->fetchOne("
        SELECT 
            m.*,
            t1.name as team1_name,
            t1.short_name as team1_short,
            t1.logo as team1_logo,
            t1.color_code as team1_color,
            t2.name as team2_name,
            t2.short_name as team2_short,
            t2.logo as team2_logo,
            t2.color_code as team2_color
        FROM cricket_matches m
        JOIN cricket_teams t1 ON m.team1_id = t1.id
        JOIN cricket_teams t2 ON m.team2_id = t2.id
        WHERE m.id = ?
    ", [$match_id]);
    
    if (!$match) {
        throw new Exception('Match not found');
    }
    
    // Fetch scorecard
    $scorecard = $db->fetchAll("
        SELECT 
            s.*,
            p.name as player_name,
            p.photo as player_photo,
            t.name as team_name
        FROM cricket_scores s
        JOIN cricket_players p ON s.player_id = p.id
        JOIN cricket_teams t ON s.team_id = t.id
        WHERE s.match_id = ?
        ORDER BY s.batting_order ASC
    ", [$match_id]);
    
    // Fetch ball-by-ball commentary (last 50 balls for live matches)
    $commentary = [];
    if ($match['status'] === 'live') {
        $commentary = $db->fetchAll("
            SELECT 
                b.*,
                p_bat.name as batsman_name,
                p_bowl.name as bowler_name
            FROM cricket_ball_by_ball b
            LEFT JOIN cricket_players p_bat ON b.batsman_id = p_bat.id
            LEFT JOIN cricket_players p_bowl ON b.bowler_id = p_bowl.id
            WHERE b.match_id = ?
            ORDER BY b.over_number DESC, b.ball_number DESC
            LIMIT 50
        ", [$match_id]);
    }
    
    // Group scorecard by team
    $team1_scorecard = array_filter($scorecard, fn($s) => $s['team_id'] == $match['team1_id']);
    $team2_scorecard = array_filter($scorecard, fn($s) => $s['team_id'] == $match['team2_id']);
    
    // Calculate team totals
    $team1_runs = array_sum(array_column($team1_scorecard, 'runs_scored'));
    $team1_wickets = count(array_filter($team1_scorecard, fn($s) => $s['is_out']));
    $team2_runs = array_sum(array_column($team2_scorecard, 'runs_scored'));
    $team2_wickets = count(array_filter($team2_scorecard, fn($s) => $s['is_out']));
    
    // Add logo URLs
    $match['team1_logo_url'] = !empty($match['team1_logo']) 
        ? UPLOADS_URL . '/cricket/teams/' . $match['team1_logo'] 
        : null;
    $match['team2_logo_url'] = !empty($match['team2_logo']) 
        ? UPLOADS_URL . '/cricket/teams/' . $match['team2_logo'] 
        : null;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'match' => $match,
            'scorecard' => [
                'team1' => [
                    'players' => array_values($team1_scorecard),
                    'total_runs' => $team1_runs,
                    'total_wickets' => $team1_wickets,
                    'overs' => $match['team1_overs']
                ],
                'team2' => [
                    'players' => array_values($team2_scorecard),
                    'total_runs' => $team2_runs,
                    'total_wickets' => $team2_wickets,
                    'overs' => $match['team2_overs']
                ]
            ],
            'commentary' => array_reverse($commentary),
            'match_summary' => [
                'current_score' => $match['current_score'],
                'current_over' => $match['current_over'],
                'status_text' => $match['status_text']
            ]
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
