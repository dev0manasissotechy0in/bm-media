<?php
/**
 * API Helper Functions
 */

/**
 * Construct correct image URL with proper subdirectory
 * 
 * @param string|null $imagePath The image path from database
 * @param string $type The subdirectory type (articles, categories, authors, etc.)
 * @return string|null The complete image URL or null
 */
function getImageUrl($imagePath, $type = 'articles') {
    if (!$imagePath) return null;
    
    $imagePath = ltrim($imagePath, '/');
    
    // If already a full URL, return as is
    if (preg_match('/^https?:\/\//', $imagePath)) {
        return $imagePath;
    }
    
    // If already contains uploads/, use as is
    if (strpos($imagePath, 'uploads/') === 0) {
        return BASE_URL . '/' . $imagePath;
    }
    
    // Otherwise, prepend uploads/{type}/
    return BASE_URL . '/uploads/' . $type . '/' . $imagePath;
}
?>
