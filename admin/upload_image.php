<?php
/**
 * TinyMCE Image Upload Handler
 */

require_once 'auth_check.php';
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No file uploaded or upload error occurred']);
    exit;
}

$file = $_FILES['file'];
$file_info = pathinfo($file['name']);
$file_ext = strtolower($file_info['extension']);
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

// Validate file type
if (!in_array($file_ext, $allowed)) {
    echo json_encode(['error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed)]);
    exit;
}

// Validate file size (max 5MB)
$max_size = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $max_size) {
    echo json_encode(['error' => 'File size exceeds 5MB limit']);
    exit;
}

// Create upload directory if it doesn't exist
$upload_dir = UPLOAD_ARTICLE_PATH;
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$new_filename = 'editor_' . time() . '_' . uniqid() . '.' . $file_ext;
$upload_path = $upload_dir . '/' . $new_filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $upload_path)) {
    // Return the URL for TinyMCE
    $file_url = BASE_URL . '/uploads/articles/' . $new_filename;
    echo json_encode(['location' => $file_url]);
} else {
    echo json_encode(['error' => 'Failed to save uploaded file']);
}
