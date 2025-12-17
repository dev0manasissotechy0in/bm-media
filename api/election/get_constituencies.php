<?php
/**
 * Election API - Get Constituencies
 * Returns constituency-wise election results
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    // Get query parameters
    $state = $_GET['state'] ?? null;
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Build query conditions
    $conditions = ["c.status = 'active'"];
    $params = [];
    
    if ($state) {
        $conditions[] = "c.state = ?";
        $params[] = $state;
    }
    
    if ($status) {
        $conditions[] = "r.status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $conditions[] = "(c.name LIKE ? OR c.code LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $where_clause = implode(' AND ', $conditions);
    
    // Fetch constituencies with results
    $constituencies = $db->fetchAll("
        SELECT 
            c.id,
            c.name,
            c.code,
            c.state,
            c.total_voters,
            c.votes_polled,
            c.turnout_percentage,
            r.id as result_id,
            r.candidate_id,
            r.party_id,
            r.votes_received,
            r.vote_percentage,
            r.status as result_status,
            cand.name as candidate_name,
            cand.photo as candidate_photo,
            p.name as party_name,
            p.short_name as party_short_name,
            p.color_code as party_color,
            p.symbol_image as party_symbol
        FROM election_constituencies c
        LEFT JOIN election_results r ON c.id = r.constituency_id AND r.status IN ('won', 'leading')
        LEFT JOIN election_candidates cand ON r.candidate_id = cand.id
        LEFT JOIN election_parties p ON r.party_id = p.id
        WHERE $where_clause
        ORDER BY c.state ASC, c.name ASC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$limit, $offset]));
    
    // Get total count
    $total_count = $db->fetchOne("
        SELECT COUNT(DISTINCT c.id) as count
        FROM election_constituencies c
        LEFT JOIN election_results r ON c.id = r.constituency_id
        WHERE $where_clause
    ", $params)['count'];
    
    // Get states list
    $states = $db->fetchAll("
        SELECT DISTINCT state 
        FROM election_constituencies 
        WHERE status = 'active' 
        ORDER BY state ASC
    ");
    
    echo json_encode([
        'success' => true,
        'data' => $constituencies,
        'pagination' => [
            'total' => (int)$total_count,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total_count
        ],
        'filters' => [
            'states' => array_column($states, 'state')
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch constituencies data',
        'message' => $e->getMessage()
    ]);
}
