<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Image Path Check</h2>";
echo "<p>Checking image_url values in articles table...</p>";

$query = "SELECT id, title, image_url FROM articles WHERE image_url IS NOT NULL AND image_url != '' LIMIT 10";
$result = $db->query($query);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Title</th><th>Image URL (from DB)</th><th>File Exists?</th></tr>";

while ($row = $result->fetch_assoc()) {
    $image_url = $row['image_url'];
    
    // Check different possible paths
    $paths_to_check = [
        'uploads/' . $image_url,
        'uploads/articles/' . $image_url,
        $image_url
    ];
    
    $file_exists = false;
    $found_path = '';
    
    foreach ($paths_to_check as $path) {
        if (file_exists($path)) {
            $file_exists = true;
            $found_path = $path;
            break;
        }
    }
    
    $color = $file_exists ? 'green' : 'red';
    $status = $file_exists ? "✓ Found at: $found_path" : "✗ Not found";
    
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>" . htmlspecialchars(substr($row['title'], 0, 50)) . "</td>";
    echo "<td>" . htmlspecialchars($image_url) . "</td>";
    echo "<td style='color: $color'>$status</td>";
    echo "</tr>";
}

echo "</table>";

echo "<hr>";
echo "<h3>Category Images:</h3>";

$query = "SELECT id, name, icon FROM categories WHERE icon IS NOT NULL AND icon != '' LIMIT 10";
$result = $db->query($query);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Icon Path (from DB)</th><th>File Exists?</th></tr>";

while ($row = $result->fetch_assoc()) {
    $icon = $row['icon'];
    
    // Check different possible paths
    $paths_to_check = [
        'uploads/' . $icon,
        'uploads/categories/' . $icon,
        $icon
    ];
    
    $file_exists = false;
    $found_path = '';
    
    foreach ($paths_to_check as $path) {
        if (file_exists($path)) {
            $file_exists = true;
            $found_path = $path;
            break;
        }
    }
    
    $color = $file_exists ? 'green' : 'red';
    $status = $file_exists ? "✓ Found at: $found_path" : "✗ Not found";
    
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . htmlspecialchars($icon) . "</td>";
    echo "<td style='color: $color'>$status</td>";
    echo "</tr>";
}

echo "</table>";
?>
