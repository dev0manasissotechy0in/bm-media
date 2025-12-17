<?php
/**
 * API - Get Single Podcast with Episodes
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../helpers.php';

$db = Database::getInstance();

try {
    $slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (empty($slug) && empty($id)) {
        throw new Exception('Podcast slug or ID is required');
    }

    // Get podcast
    if ($slug) {
        $podcast = $db->fetchOne("SELECT * FROM podcasts WHERE slug = ? AND status = 'published'", [$slug]);
    } else {
        $podcast = $db->fetchOne("SELECT * FROM podcasts WHERE id = ? AND status = 'published'", [$id]);
    }

    if (!$podcast) {
        throw new Exception('Podcast not found');
    }

    // Get episodes if it's a series
    $episodes = [];
    if ($podcast['is_series']) {
        $episodes = $db->fetchAll("
            SELECT 
                id,
                title,
                slug,
                description,
                audio_url,
                duration,
                episode_number,
                season_number,
                thumbnail,
                views_count,
                likes_count,
                published_at
            FROM podcast_episodes
            WHERE podcast_id = ? AND status = 'published'
            ORDER BY season_number DESC, episode_number DESC
        ", [$podcast['id']]);
    }

    // Increment view count
    trackView('podcast', $podcast['id']);

    $response = [
        'success' => true,
        'podcast' => [
            'id' => (string)$podcast['id'],
            'title' => $podcast['title'],
            'slug' => $podcast['slug'],
            'description' => $podcast['description'],
            'thumbnail' => getImageUrl($podcast['thumbnail'], 'podcasts'),
            'cover_image' => getImageUrl($podcast['cover_image'], 'podcasts'),
            'author_name' => $podcast['author_name'],
            'author_bio' => $podcast['author_bio'],
            'category' => $podcast['category'],
            'language' => $podcast['language'],
            'is_series' => (bool)$podcast['is_series'],
            'total_episodes' => (int)$podcast['total_episodes'],
            'views' => (int)$podcast['views_count'],
            'likes' => (int)$podcast['likes_count'],
            'saves' => (int)$podcast['saves_count'],
            'created_at' => $podcast['created_at'],
            'updated_at' => $podcast['updated_at'],
            'episodes' => array_map(function($ep) {
                return [
                    'id' => (string)$ep['id'],
                    'title' => $ep['title'],
                    'slug' => $ep['slug'],
                    'description' => $ep['description'],
                    'audio_url' => $ep['audio_url'],
                    'duration' => (int)$ep['duration'],
                    'duration_formatted' => gmdate('H:i:s', $ep['duration']),
                    'episode_number' => (int)$ep['episode_number'],
                    'season_number' => (int)$ep['season_number'],
                    'thumbnail' => getImageUrl($ep['thumbnail'], 'podcasts'),
                    'views' => (int)$ep['views_count'],
                    'likes' => (int)$ep['likes_count'],
                    'published_at' => $ep['published_at']
                ];
            }, $episodes)
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
