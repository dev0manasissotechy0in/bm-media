-- Cleanup old content view records (older than 30 days)
-- Run this periodically to keep the table size manageable

DELETE FROM content_views 
WHERE viewed_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Optional: Get statistics before cleanup
SELECT 
    content_type,
    COUNT(*) as total_views,
    COUNT(DISTINCT fingerprint) as unique_devices,
    MIN(viewed_at) as oldest_view,
    MAX(viewed_at) as newest_view
FROM content_views
GROUP BY content_type;
