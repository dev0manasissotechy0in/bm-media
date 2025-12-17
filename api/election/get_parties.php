<?php
/**
 * Election API - Get Parties
 * Returns all election parties with their details and seat counts
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    // Fetch parties with their seat counts
    $parties = $db->fetchAll("
        SELECT 
            p.id,
            p.name,
            p.short_name,
            p.color_code,
            p.symbol_image,
            p.founded_year,
            p.leader_name,
            p.description,
            p.status,
            COUNT(CASE WHEN r.status = 'won' THEN 1 END) as seats_won,
            COUNT(CASE WHEN r.status = 'leading' THEN 1 END) as seats_leading,
            COUNT(CASE WHEN r.status = 'lost' THEN 1 END) as seats_lost,
            SUM(CASE WHEN r.status IN ('won', 'leading') THEN r.votes_received ELSE 0 END) as total_votes,
            p.created_at
        FROM election_parties p
        LEFT JOIN election_results r ON p.id = r.party_id
        WHERE p.status = 'active'
        GROUP BY p.id
        ORDER BY seats_won DESC, seats_leading DESC, total_votes DESC
    ");
    
    // Calculate vote share percentages
    $total_votes_all = array_sum(array_column($parties, 'total_votes'));
    
    foreach ($parties as &$party) {
        $party['vote_share'] = $total_votes_all > 0 
            ? round(($party['total_votes'] / $total_votes_all) * 100, 2) 
            : 0;
        $party['total_seats'] = (int)$party['seats_won'] + (int)$party['seats_leading'];
        $party['symbol_url'] = !empty($party['symbol_image']) 
            ? UPLOADS_URL . '/election/parties/' . $party['symbol_image'] 
            : null;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $parties,
        'total_parties' => count($parties),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch parties data',
        'message' => $e->getMessage()
    ]);
}
