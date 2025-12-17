<?php
// ============================================================
// ELECTION API - GET RESULTS
// ============================================================

header('Content-Type: application/json');
require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    // Get overall summary
    $total_constituencies = $db->count('election_constituencies', "status = 'active'");
    $results_declared = $db->count('election_results', "status IN ('won', 'lost')");
    $results_pending = $total_constituencies - $results_declared;
    
    // Get average turnout (this would come from election_constituencies in real scenario)
    $avg_turnout = 65.5; // Sample data
    
    $summary = [
        'total_seats' => $total_constituencies,
        'seats_counted' => $results_declared,
        'seats_pending' => $results_pending,
        'voter_turnout' => $avg_turnout
    ];
    
    // Get party-wise results
    $party_results = $db->fetchAll("
        SELECT 
            p.id,
            p.name,
            p.short_name,
            p.color_code,
            COUNT(CASE WHEN r.status = 'won' THEN 1 END) as seats_won,
            COUNT(CASE WHEN r.status = 'leading' THEN 1 END) as seats_leading,
            SUM(r.votes) as total_votes
        FROM election_parties p
        LEFT JOIN election_results r ON p.id = r.party_id
        WHERE p.status = 'active'
        GROUP BY p.id
        ORDER BY seats_won DESC, seats_leading DESC
    ");
    
    // Get constituency-wise results (top 50 for performance)
    $constituencies = $db->fetchAll("
        SELECT 
            cons.id,
            cons.name as constituency_name,
            cons.state,
            cand.name as candidate_name,
            cand.photo as candidate_photo,
            p.name as party_name,
            p.short_name as party_short_name,
            p.color_code as party_color,
            r.votes,
            r.vote_percentage,
            r.status as result_status
        FROM election_constituencies cons
        LEFT JOIN election_results r ON cons.id = r.constituency_id
        LEFT JOIN election_candidates cand ON r.candidate_id = cand.id
        LEFT JOIN election_parties p ON r.party_id = p.id
        WHERE r.status IN ('leading', 'won')
        ORDER BY cons.name ASC
        LIMIT 50
    ");
    
    // Get live election news
    $live_news = $db->fetchAll("
        SELECT * FROM election_news 
        ORDER BY published_at DESC 
        LIMIT 10
    ");
    
    // Get latest updates
    $live_updates = $db->fetchAll("
        SELECT * FROM election_updates 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    
    jsonResponse([
        'success' => true,
        'data' => [
            'summary' => $summary,
            'party_results' => $party_results,
            'constituencies' => $constituencies,
            'live_news' => $live_news,
            'live_updates' => $live_updates
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Failed to fetch election results'
    ], 500);
}
?>
