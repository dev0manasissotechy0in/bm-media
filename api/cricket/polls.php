<?php
/**
 * Cricket API - Polls
 * Returns cricket polls (match predictions, best player, etc.)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    $poll_id = $_GET['id'] ?? null;
    $match_id = $_GET['match_id'] ?? null;
    
    if ($poll_id) {
        // Fetch specific poll with vote counts
        $poll = $db->fetchOne("
            SELECT * FROM cricket_polls 
            WHERE id = ? AND status = 'active'
        ", [$poll_id]);
        
        if (!$poll) {
            throw new Exception('Poll not found');
        }
        
        // Fetch poll options with votes
        $options = $db->fetchAll("
            SELECT 
                po.*,
                COUNT(pv.id) as vote_count
            FROM cricket_poll_options po
            LEFT JOIN cricket_poll_votes pv ON po.id = pv.option_id
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
        // Fetch all active polls
        $conditions = ["status = 'active'"];
        $params = [];
        
        if ($match_id) {
            $conditions[] = "match_id = ?";
            $params[] = $match_id;
        }
        
        $where_clause = implode(' AND ', $conditions);
        
        $polls = $db->fetchAll("
            SELECT 
                p.*,
                COUNT(DISTINCT pv.id) as total_votes,
                m.match_name,
                m.team1_id,
                m.team2_id,
                t1.name as team1_name,
                t2.name as team2_name
            FROM cricket_polls p
            LEFT JOIN cricket_poll_options po ON p.id = po.poll_id
            LEFT JOIN cricket_poll_votes pv ON po.id = pv.option_id
            LEFT JOIN cricket_matches m ON p.match_id = m.id
            LEFT JOIN cricket_teams t1 ON m.team1_id = t1.id
            LEFT JOIN cricket_teams t2 ON m.team2_id = t2.id
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
