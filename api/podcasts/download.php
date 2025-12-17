<?php
require_once '../../config/database.php';

$podcast_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($podcast_id === 0) {
    header('Location: ../../podcasts.php');
    exit;
}

// Fetch podcast
$stmt = $pdo->prepare("SELECT * FROM podcasts WHERE id = ? AND is_active = 1");
$stmt->execute([$podcast_id]);
$podcast = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$podcast) {
    header('Location: ../../podcasts.php');
    exit;
}

// Increment download count
$stmt = $pdo->prepare("UPDATE podcasts SET download_count = download_count + 1 WHERE id = ?");
$stmt->execute([$podcast_id]);

// Track download
session_start();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$guest_token = isset($_COOKIE['guest_token']) ? $_COOKIE['guest_token'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$stmt = $pdo->prepare("
    INSERT INTO podcast_downloads (podcast_id, user_id, guest_token, ip_address, user_agent)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$podcast_id, $user_id, $guest_token, $ip_address, $user_agent]);

// Get file path
$file_path = '../../' . $podcast['audio_file'];

if (!file_exists($file_path)) {
    die('File not found');
}

// Send file
$filename = basename($podcast['audio_file']);
header('Content-Description: File Transfer');
header('Content-Type: audio/mpeg');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

readfile($file_path);
exit;
