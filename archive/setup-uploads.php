<?php
/**
 * Create Upload Directories
 * Run this file once to create all required upload directories
 */

// Define base uploads path
$base_path = __DIR__ . '/uploads';

// List of required directories
$directories = [
    '/articles',
    '/users',
    '/reporters',
    '/ads',
    '/categories',
    '/election',
    '/cricket',
    '/stories',
    '/reels',
    '/gallery',
    '/videos',
    '/custom-pages'
];

// Create base uploads directory if it doesn't exist
if (!file_exists($base_path)) {
    if (mkdir($base_path, 0755, true)) {
        echo "✓ Created base uploads directory<br>";
    } else {
        echo "✗ Failed to create base uploads directory<br>";
    }
} else {
    echo "✓ Base uploads directory already exists<br>";
}

// Create all subdirectories
echo "<h3>Creating Upload Directories:</h3>";
foreach ($directories as $dir) {
    $full_path = $base_path . $dir;
    
    if (!file_exists($full_path)) {
        if (mkdir($full_path, 0755, true)) {
            echo "✓ Created: {$dir}<br>";
        } else {
            echo "✗ Failed to create: {$dir}<br>";
        }
    } else {
        echo "✓ Already exists: {$dir}<br>";
    }
}

echo "<br><h3>Creating .htaccess for security:</h3>";

// Create .htaccess to prevent PHP execution in uploads
$htaccess_content = "# Prevent PHP execution in uploads directory
<Files *.php>
    deny from all
</Files>

# Allow images and other media
<FilesMatch \"\.(jpg|jpeg|png|gif|svg|webp|mp4|avi|mov|pdf|doc|docx)$\">
    Allow from all
</FilesMatch>
";

$htaccess_path = $base_path . '/.htaccess';
if (file_put_contents($htaccess_path, $htaccess_content)) {
    echo "✓ Created .htaccess for security<br>";
} else {
    echo "✗ Failed to create .htaccess<br>";
}

echo "<br><h3>Summary:</h3>";
echo "All upload directories are ready!<br>";
echo "<a href='admin/dashboard.php'>Go to Admin Dashboard</a>";
?>
