<?php
require_once '../config/config.php';
require_once 'auth_check.php';

function formatBytes($bytes, $precision = 2) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), $precision) . ' ' . $sizes[$i];
}

$test_url = $_GET['url'] ?? '';
$result = [];

if ($test_url) {
    $result['url'] = $test_url;
    
    // Check if URL is valid
    $result['valid_url'] = filter_var($test_url, FILTER_VALIDATE_URL) ? 'Yes' : 'No';
    
    // Check file extension
    $result['has_audio_ext'] = preg_match('/\.(mp3|wav|ogg|m4a|aac)(\?.*)?$/i', $test_url) ? 'Yes' : 'No';
    
    // Try to get headers
    $headers = @get_headers($test_url, 1);
    if ($headers) {
        $result['accessible'] = strpos($headers[0], '200') !== false ? 'Yes' : 'No';
        $result['status'] = $headers[0];
        $result['content_type'] = $headers['Content-Type'] ?? 'Unknown';
        $result['content_length'] = isset($headers['Content-Length']) ? formatBytes($headers['Content-Length']) : 'Unknown';
    } else {
        $result['accessible'] = 'No';
        $result['error'] = 'Could not fetch headers';
    }
}

$page_title = 'Test Audio URL';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Test Audio URL Accessibility</h1>
        <a href="podcasts.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Podcasts
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="mb-4">
                <div class="row">
                    <div class="col-md-10">
                        <label class="form-label">Audio URL to Test</label>
                        <input type="url" name="url" class="form-control" value="<?= htmlspecialchars($test_url) ?>" placeholder="https://example.com/audio.mp3" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Test URL</button>
                    </div>
                </div>
            </form>

            <?php if (!empty($result)): ?>
            <div class="alert alert-info">
                <h5>Test Results</h5>
                <table class="table table-sm">
                    <tr>
                        <th width="200">URL:</th>
                        <td><?= htmlspecialchars($result['url']) ?></td>
                    </tr>
                    <tr>
                        <th>Valid URL Format:</th>
                        <td>
                            <?php if ($result['valid_url'] === 'Yes'): ?>
                                <span class="badge bg-success">Yes</span>
                            <?php else: ?>
                                <span class="badge bg-danger">No</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Has Audio Extension:</th>
                        <td>
                            <?php if ($result['has_audio_ext'] === 'Yes'): ?>
                                <span class="badge bg-success">Yes</span>
                            <?php else: ?>
                                <span class="badge bg-warning">No</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Accessible:</th>
                        <td>
                            <?php if ($result['accessible'] === 'Yes'): ?>
                                <span class="badge bg-success">Yes</span>
                            <?php else: ?>
                                <span class="badge bg-danger">No</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (isset($result['status'])): ?>
                    <tr>
                        <th>HTTP Status:</th>
                        <td><?= htmlspecialchars($result['status']) ?></td>
                    </tr>
                    <tr>
                        <th>Content Type:</th>
                        <td><?= htmlspecialchars($result['content_type']) ?></td>
                    </tr>
                    <tr>
                        <th>File Size:</th>
                        <td><?= htmlspecialchars($result['content_length']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (isset($result['error'])): ?>
                    <tr>
                        <th>Error:</th>
                        <td><span class="text-danger"><?= htmlspecialchars($result['error']) ?></span></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <?php if ($result['accessible'] === 'Yes'): ?>
                <div class="mt-3">
                    <h6>Test Player:</h6>
                    <audio controls class="w-100">
                        <source src="<?= htmlspecialchars($test_url) ?>" type="audio/mpeg">
                        Your browser does not support the audio element.
                    </audio>
                </div>
                <?php else: ?>
                <div class="alert alert-warning mt-3">
                    <i class="bi bi-exclamation-triangle"></i> This URL is not accessible or valid. The audio player will not work with this URL.
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <h6>Requirements for Audio URLs:</h6>
                <ul class="mb-0">
                    <li>Must be a valid HTTP/HTTPS URL</li>
                    <li>Should end with audio file extension (.mp3, .wav, .ogg, .m4a, .aac)</li>
                    <li>Must be publicly accessible (not behind authentication)</li>
                    <li>Server must allow CORS if hosted externally</li>
                    <li>Recommended: Use file upload instead of external URLs for reliability</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
