<?php
// ============================================================
// CRICKET API - GET LIVE MATCHES
// ============================================================

header('Content-Type: application/json');
require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    // Get live and recent matches
    $matches = $db->fetchAll("
        SELECT 
            m.*,
            t1.name as team1_name,
            t1.short_name as team1_short,
            t1.logo as team1_logo,
            t2.name as team2_name,
            t2.short_name as team2_short,
            t2.logo as team2_logo,
            tw.name as toss_winner_name,
            mw.name as match_winner_name
        FROM cricket_matches m
        LEFT JOIN cricket_teams t1 ON m.team1_id = t1.id
        LEFT JOIN cricket_teams t2 ON m.team2_id = t2.id
        LEFT JOIN cricket_teams tw ON m.toss_winner_id = tw.id
        LEFT JOIN cricket_teams mw ON m.match_winner_id = mw.id
        WHERE m.status IN ('live', 'upcoming', 'completed')
        ORDER BY 
            CASE m.status
                WHEN 'live' THEN 1
                WHEN 'upcoming' THEN 2
                WHEN 'completed' THEN 3
            END,
            m.match_date DESC
        LIMIT 20
    ");
    
    // Get scores for each match
    foreach ($matches as &$match) {
        $scores = $db->fetchAll("
            SELECT 
                s.*,
                t.name as team_name,
                t.short_name as team_short
            FROM cricket_scores s
            LEFT JOIN cricket_teams t ON s.team_id = t.id
            WHERE s.match_id = ?
            ORDER BY s.innings ASC
        ", [$match['id']]);
        
        $match['scores'] = $scores;
        
        // Get team1 and team2 scores separately
        foreach ($scores as $score) {
            if ($score['team_id'] == $match['team1_id']) {
                $match['team1_runs'] = $score['runs'];
                $match['team1_wickets'] = $score['wickets'];
                $match['team1_overs'] = $score['overs'];
                $match['team1_run_rate'] = $score['run_rate'];
            } elseif ($score['team_id'] == $match['team2_id']) {
                $match['team2_runs'] = $score['runs'];
                $match['team2_wickets'] = $score['wickets'];
                $match['team2_overs'] = $score['overs'];
                $match['team2_run_rate'] = $score['run_rate'];
            }
        }
        
        // Format status text
        if ($match['status'] == 'live') {
            $match['status_text'] = 'Live';
        } elseif ($match['status'] == 'completed' && $match['result_text']) {
            $match['status_text'] = $match['result_text'];
        } elseif ($match['status'] == 'upcoming') {
            $match['status_text'] = 'Upcoming';
        }
    }
    
    jsonResponse([
        'success' => true,
        'data' => $matches,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Failed to fetch cricket matches'
    ], 500);
}
?>
