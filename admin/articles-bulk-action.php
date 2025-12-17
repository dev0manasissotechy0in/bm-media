<?php
/**
 * Bulk Actions Handler for Articles
 */

require_once 'auth_check.php';

header('Content-Type: application/json');

// Enable error logging for debugging
error_log("Bulk Action Request: " . json_encode($_POST));

$db = Database::getInstance();

// Get POST data
$action = $_POST['action'] ?? '';
$article_ids = $_POST['article_ids'] ?? [];

// Validate inputs
if (empty($action) || empty($article_ids) || !is_array($article_ids)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Please select articles and an action.'
    ]);
    exit;
}

// Sanitize article IDs
$article_ids = array_map('intval', $article_ids);
$placeholders = implode(',', array_fill(0, count($article_ids), '?'));

error_log("Processing action: $action for IDs: " . implode(', ', $article_ids));

try {
    $affected = 0;
    $message = '';

    switch ($action) {
        case 'delete':
            // Delete selected articles
            $query = "DELETE FROM articles WHERE id IN ($placeholders)";
            $stmt = $db->query($query, $article_ids);
            $affected = $stmt ? $stmt->rowCount() : 0;
            $message = "$affected article(s) deleted successfully";
            break;

        case 'publish':
            // Publish selected articles
            $query = "UPDATE articles SET status = 'published' WHERE id IN ($placeholders)";
            $stmt = $db->query($query, $article_ids);
            $affected = $stmt ? $stmt->rowCount() : 0;
            $message = "$affected article(s) published successfully";
            break;

        case 'draft':
            // Move to draft
            $query = "UPDATE articles SET status = 'draft' WHERE id IN ($placeholders)";
            $stmt = $db->query($query, $article_ids);
            if ($stmt === false) {
                throw new Exception('Query execution failed');
            }
            $affected = $stmt->rowCount();
            error_log("Draft query executed. Rows affected: $affected");
            $message = "$affected article(s) moved to draft";
            break;

        case 'archive':
            // Archive selected articles
            $query = "UPDATE articles SET status = 'archived' WHERE id IN ($placeholders)";
            $stmt = $db->query($query, $article_ids);
            $affected = $stmt ? $stmt->rowCount() : 0;
            $message = "$affected article(s) archived successfully";
            break;

        case 'feature':
            // Mark as featured
            $query = "UPDATE articles SET is_featured = 1 WHERE id IN ($placeholders)";
            $stmt = $db->query($query, $article_ids);
            $affected = $stmt ? $stmt->rowCount() : 0;
            $message = "$affected article(s) marked as featured";
            break;

        case 'unfeature':
            // Remove featured status
            $query = "UPDATE articles SET is_featured = 0 WHERE id IN ($placeholders)";
            $stmt = $db->query($query, $article_ids);
            $affected = $stmt ? $stmt->rowCount() : 0;
            $message = "$affected article(s) unmarked as featured";
            break;

        case 'breaking':
            // Mark as breaking news
            $query = "UPDATE articles SET is_breaking = 1 WHERE id IN ($placeholders)";
            $stmt = $db->query($query, $article_ids);
            $affected = $stmt ? $stmt->rowCount() : 0;
            $message = "$affected article(s) marked as breaking news";
            break;

        case 'unbreaking':
            // Remove breaking news status
            $query = "UPDATE articles SET is_breaking = 0 WHERE id IN ($placeholders)";
            $stmt = $db->query($query, $article_ids);
            $affected = $stmt ? $stmt->rowCount() : 0;
            $message = "$affected article(s) unmarked as breaking news";
            break;

        case 'category':
            // Change category
            $category_id = (int)($_POST['category_id'] ?? 0);
            if ($category_id > 0) {
                $params = array_merge([$category_id], $article_ids);
                $query = "UPDATE articles SET category_id = ? WHERE id IN ($placeholders)";
                $stmt = $db->query($query, $params);
                $affected = $stmt ? $stmt->rowCount() : 0;
                $message = "$affected article(s) moved to new category";
            } else {
                throw new Exception('Invalid category selected');
            }
            break;

        case 'author':
            // Change author
            $author_type = $_POST['author_type'] ?? '';
            $author_id = (int)($_POST['author_id'] ?? 0);
            
            if (in_array($author_type, ['admin', 'reporter']) && $author_id > 0) {
                $params = array_merge([$author_type, $author_id], $article_ids);
                $query = "UPDATE articles SET author_type = ?, author_id = ? WHERE id IN ($placeholders)";
                $stmt = $db->query($query, $params);
                $affected = $stmt ? $stmt->rowCount() : 0;
                $message = "$affected article(s) author updated";
            } else {
                throw new Exception('Invalid author selected');
            }
            break;

        default:
            throw new Exception('Invalid action selected');
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'affected' => $affected
    ]);
    
    error_log("Success response: $message");

} catch (Exception $e) {
    error_log("Bulk action error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
