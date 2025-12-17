<?php
/**
 * Election API - Get Polls
 * Returns opinion polls and exit polls data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    $poll_type = $_GET['type'] ?? 'all'; // 'opinion', 'exit', 'all'
    $poll_id = $_GET['id'] ?? null;
    
    if ($poll_id) {
        // Fetch specific poll with options and votes
        $poll = $db->fetchOne("
            SELECT * FROM election_polls 
            WHERE id = ? AND status = 'active'
        ", [$poll_id]);
        
        if (!$poll) {
            throw new Exception('Poll not found');
        }
        
        // Fetch poll options with vote counts
        $options = $db->fetchAll("
            SELECT 
                po.*,
                p.name as party_name,
                p.color_code as party_color,
                COUNT(pv.id) as vote_count
            FROM election_poll_options po
            LEFT JOIN election_parties p ON po.party_id = p.id
            LEFT JOIN election_poll_votes pv ON po.id = pv.option_id
            WHERE po.poll_id = ?
            GROUP BY po.id
            ORDER BY vote_count DESC
        ", [$poll_id]);
        
        $total_votes = array_sum(array_column($options, 'vote_count'));
        
        // Calculate percentages
        foreach ($options as &$option) {
            $option['percentage'] = $total_votes > 0 
                ? round(($option['vote_count'] / $total_votes) * 100, 2) 
                : 0;
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'poll' => $poll,
                'options' => $options,
                'total_votes' => $total_votes
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } else {
        // Fetch all polls
        $conditions = ["status = 'active'"];
        $params = [];
        
        if ($poll_type !== 'all') {
            $conditions[] = "poll_type = ?";
            $params[] = $poll_type;
        }
        
        $where_clause = implode(' AND ', $conditions);
        
        $polls = $db->fetchAll("
            SELECT 
                p.*,
                COUNT(DISTINCT pv.id) as total_votes,
                COUNT(DISTINCT po.id) as option_count
            FROM election_polls p
            LEFT JOIN election_poll_options po ON p.id = po.poll_id
            LEFT JOIN election_poll_votes pv ON po.id = pv.option_id
            WHERE $where_clause
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ", $params);
        
        echo json_encode([
            'success' => true,
            'data' => $polls,
            'total_polls' => count($polls),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch polls data',
        'message' => $e->getMessage()
    ]);
}
