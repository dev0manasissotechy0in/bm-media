# Article Date Display Fix - Summary of Changes

## Problem
Some article dates were displaying as "(N/A)" instead of showing the actual publication date.

## Root Cause
Articles in the database had NULL or empty `published_at` values, and the date formatting functions (`formatDate()` and `timeAgo()`) were not falling back to the `created_at` date.

## Solution
Updated date formatting functions and all pages displaying article dates to use `created_at` as a fallback when `published_at` is empty or NULL.

---

## Files Modified

### 1. **includes/Functions.php**
- **Updated `formatDate()` function** (lines 29-45):
  - Added `$fallback` parameter
  - Now checks fallback value if primary date is empty
  - Returns 'N/A' only if both dates are empty

- **Updated `timeAgo()` function** (lines 52-70):
  - Added `$fallback` parameter
  - Now uses fallback date if primary is empty
  - Maintains all time-ago formatting logic

### 2. **article.php** (line 186)
- Changed: `formatDate($article['published_at'] ?: $article['created_at'], ...)`
- To: `formatDate($article['published_at'] ?? '', 'd M, Y H:i', $article['created_at'] ?? '')`
- Uses new fallback parameter instead of PHP ternary operator

### 3. **api/articles/download.php** (line 68)
- Updated PDF generation to use fallback date
- Changed from: `formatDate($article['published_at'], 'd M, Y')`
- To: `formatDate($article['published_at'] ?? '', 'd M, Y', $article['created_at'] ?? '')`

### 4. **tag.php** (line 104)
- Changed: `timeAgo($article['published_at'])`
- To: `timeAgo($article['published_at'], $article['created_at'])`

### 5. **subcategory.php** (line 115)
- Changed: `timeAgo($article['published_at'])`
- To: `timeAgo($article['published_at'], $article['created_at'])`

### 6. **search.php** (line 132)
- Changed: `timeAgo($article['published_at'])`
- To: `timeAgo($article['published_at'], $article['created_at'])`

### 7. **views/cases/detail.php** (line 570)
- Changed: `date('M j, Y', strtotime($article['published_at']))`
- To: `formatDate($article['published_at'] ?? '', 'M j, Y', $article['created_at'] ?? '')`
- Uses proper error handling with formatDate function

### 8. **rss.php** (line 56)
- Changed: `date('r', strtotime($article['published_at']))`
- To: `date('r', strtotime($article['published_at'] ?: $article['created_at']))`
- RSS feed now shows created_at if published_at is empty

### 9. **category.php** (line 105)
- Changed: `timeAgo($article['published_at'])`
- To: `timeAgo($article['published_at'], $article['created_at'])`

### 10. **index.php** (Multiple locations)
- Updated 7 instances of `timeAgo()` calls to include fallback:
  - Line 150: Live news updated_at → `timeAgo($news['updated_at'], $news['created_at'])`
  - Line 205: Main featured published_at → `timeAgo($main_featured['published_at'], $main_featured['created_at'])`
  - Line 234: Side featured published_at → `timeAgo($featured['published_at'], $featured['created_at'])`
  - Line 267: Top news published_at → `timeAgo($news['published_at'], $news['created_at'])`
  - Line 301: Gallery articles published_at → `timeAgo($article['published_at'], $article['created_at'])`
  - Line 336: Video articles published_at → `timeAgo($article['published_at'], $article['created_at'])`
  - Line 440: Category articles published_at → `timeAgo($article['published_at'], $article['created_at'])`

---

## Database Migration

### 11. **database/fix_missing_published_dates.sql**
Created a migration script to fix existing articles in the database:

```sql
-- Set published_at from created_at for published articles that don't have published_at set
UPDATE articles 
SET published_at = created_at 
WHERE status = 'published' AND published_at IS NULL AND created_at IS NOT NULL;
```

**How to run:**
```bash
mysql -u [user] -p [database] < database/fix_missing_published_dates.sql
```

---

## Impact

### Fixed Pages
- ✅ Article detail page (article.php)
- ✅ Category page (category.php)
- ✅ Tag page (tag.php)
- ✅ Subcategory page (subcategory.php)
- ✅ Search results page (search.php)
- ✅ Home page (index.php) - all article sections
- ✅ Case detail view (views/cases/detail.php)
- ✅ PDF downloads (api/articles/download.php)
- ✅ RSS feeds (rss.php)

### Backward Compatibility
- ✅ All changes are backward compatible
- ✅ No breaking changes to existing function signatures
- ✅ New fallback parameter is optional (has default value)
- ✅ Existing code that doesn't use fallback continues to work

---

## Testing Recommendations

1. **Test article pages with NULL published_at:**
   - Verify articles without published_at show created_at date
   - Check formatting is correct (not showing "N/A")

2. **Test article pages with valid published_at:**
   - Verify articles with published_at show that date (not created_at)
   - Confirm date formatting is correct

3. **Test various article sections:**
   - Featured articles
   - Gallery articles
   - Video articles
   - Category articles
   - Search results

4. **Test special cases:**
   - PDF downloads
   - RSS feeds
   - Case detail pages
   - Tag pages

---

## Notes

- The fallback behavior automatically uses `created_at` when `published_at` is empty/NULL
- Only displays "N/A" if BOTH dates are empty (unlikely scenario)
- No data is modified on display - only date selection logic improved
- Optional database migration should be run to fix historical data
