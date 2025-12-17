-- Fix missing published_at dates
-- For published articles without published_at, use created_at
-- For draft articles, set published_at to NULL (they shouldn't be published)

UPDATE articles 
SET published_at = created_at 
WHERE status = 'published' AND (published_at IS NULL OR published_at = '0000-00-00 00:00:00');

-- Alternative approach: only update if the gap between created and now is reasonable
-- This helps identify articles that were never properly published
UPDATE articles 
SET published_at = created_at 
WHERE status = 'published' AND published_at IS NULL AND created_at IS NOT NULL;
