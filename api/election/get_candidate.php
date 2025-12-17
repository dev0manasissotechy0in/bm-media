<?php
/**
 * Election API - Get Candidate Details
 * Returns detailed information about a specific candidate
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    $candidate_id = $_GET['id'] ?? null;
    
    if (!$candidate_id) {
        throw new Exception('Candidate ID is required');
    }
    
    // Fetch candidate details
    $candidate = $db->fetchOne("
        SELECT 
            c.*,
            p.name as party_name,
            p.short_name as party_short_name,
            p.color_code as party_color,
            p.symbol_image as party_symbol
        FROM election_candidates c
        LEFT JOIN election_parties p ON c.party_id = p.id
        WHERE c.id = ?
    ", [$candidate_id]);
    
    if (!$candidate) {
        throw new Exception('Candidate not found');
    }
    
    // Fetch election results for this candidate
    $results = $db->fetchAll("
        SELECT 
            r.*,
            cons.name as constituency_name,
            cons.code as constituency_code,
            cons.state,
            cons.total_voters
        FROM election_results r
        JOIN election_constituencies cons ON r.constituency_id = cons.id
        WHERE r.candidate_id = ?
        ORDER BY r.votes_received DESC
    ", [$candidate_id]);
    
    // Calculate statistics
    $total_votes = array_sum(array_column($results, 'votes_received'));
    $constituencies_won = count(array_filter($results, fn($r) => $r['status'] === 'won'));
    $constituencies_leading = count(array_filter($results, fn($r) => $r['status'] === 'leading'));
    $constituencies_lost = count(array_filter($results, fn($r) => $r['status'] === 'lost'));
    
    // Add full URLs
    $candidate['photo_url'] = !empty($candidate['photo']) 
        ? UPLOADS_URL . '/election/candidates/' . $candidate['photo'] 
        : null;
    $candidate['party_symbol_url'] = !empty($candidate['party_symbol']) 
        ? UPLOADS_URL . '/election/parties/' . $candidate['party_symbol'] 
        : null;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'candidate' => $candidate,
            'results' => $results,
            'statistics' => [
                'total_votes' => $total_votes,
                'constituencies_won' => $constituencies_won,
                'constituencies_leading' => $constituencies_leading,
                'constituencies_lost' => $constituencies_lost,
                'total_constituencies' => count($results)
            ]
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code($e->getMessage() === 'Candidate ID is required' ? 400 : 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
