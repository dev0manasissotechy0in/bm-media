<?php
/**
 * Market API - Polls
 * Returns market sentiment polls and predictions
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    $poll_id = $_GET['id'] ?? null;
    
    if ($poll_id) {
        // Fetch specific poll with vote counts
        $poll = $db->fetchOne("
            SELECT * FROM market_polls 
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
            FROM market_poll_options po
            LEFT JOIN market_poll_votes pv ON po.id = pv.option_id
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
        $polls = $db->fetchAll("
            SELECT 
                p.*,
                COUNT(DISTINCT pv.id) as total_votes,
                COUNT(DISTINCT po.id) as option_count
            FROM market_polls p
            LEFT JOIN market_poll_options po ON p.id = po.poll_id
            LEFT JOIN market_poll_votes pv ON po.id = pv.option_id
            WHERE p.status = 'active'
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ");
        
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
