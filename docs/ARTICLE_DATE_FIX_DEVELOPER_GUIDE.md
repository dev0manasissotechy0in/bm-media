# Article Date Display Fix - Developer Guide

## Quick Reference

### Problem Resolved
Article dates were showing "(N/A)" when the `published_at` database field was NULL or empty.

### Solution Applied
- Updated `formatDate()` and `timeAgo()` helper functions to accept fallback dates
- Updated all article display pages to pass `created_at` as fallback parameter
- Created database migration to populate `published_at` from `created_at` for existing articles

---

## Updated Function Signatures

### formatDate()
```php
formatDate($date, $format = 'd M, Y', $fallback = null)
```
**Before:** `formatDate('2024-01-15', 'd M, Y')` → returns "N/A" if date is empty
**After:** `formatDate('', 'd M, Y', '2024-01-15')` → returns "15 Jan, 2024"

### timeAgo()
```php
timeAgo($datetime, $fallback = null)
```
**Before:** `timeAgo(null)` → returns "N/A"
**After:** `timeAgo(null, '2024-01-15')` → returns "50 days ago"

---

## Where to Use the Fallback Pattern

### In PHP Pages (when displaying articles):
```php
// CORRECT - with fallback
<?= timeAgo($article['published_at'], $article['created_at']) ?>

// For formatDate with specific format
<?= formatDate($article['published_at'] ?? '', 'd M, Y', $article['created_at'] ?? '') ?>
```

### NOT Recommended:
```php
// OLD PATTERN - may show N/A
<?= timeAgo($article['published_at']) ?>

// OLD PATTERN - may show N/A
<?= formatDate($article['published_at'], 'd M, Y') ?>
```

---

## Affected Files & Line Numbers

| File | Function | Line(s) | Status |
|------|----------|---------|--------|
| includes/Functions.php | formatDate() | 29-45 | ✅ Updated |
| includes/Functions.php | timeAgo() | 52-70 | ✅ Updated |
| article.php | Article detail date | 186 | ✅ Updated |
| api/articles/download.php | PDF date | 68 | ✅ Updated |
| tag.php | Tag page dates | 104 | ✅ Updated |
| subcategory.php | Subcategory dates | 115 | ✅ Updated |
| search.php | Search result dates | 132 | ✅ Updated |
| views/cases/detail.php | Case detail dates | 570 | ✅ Updated |
| rss.php | RSS feed dates | 56 | ✅ Updated |
| category.php | Category page dates | 105 | ✅ Updated |
| index.php | Homepage dates | 150, 205, 234, 267, 301, 336, 440 | ✅ Updated |

---

## Database Migration

To fix historical data, run:
```bash
mysql -u username -p database_name < database/fix_missing_published_dates.sql
```

This sets `published_at = created_at` for all published articles where `published_at` is NULL.

---

## Testing Checklist

- [ ] Article detail page shows date (not N/A)
- [ ] Home page featured articles show dates
- [ ] Category pages show article dates
- [ ] Tag pages show article dates
- [ ] Search results show article dates
- [ ] Gallery articles show dates
- [ ] Video articles show dates
- [ ] PDF downloads include date
- [ ] RSS feed includes publication date
- [ ] Case detail page shows article dates

---

## For New Development

When adding new article display locations:

1. Always select both `published_at` and `created_at` from database:
   ```sql
   SELECT a.published_at, a.created_at, ...
   ```

2. Use fallback pattern when displaying dates:
   ```php
   <?= timeAgo($article['published_at'], $article['created_at']) ?>
   ```

3. This ensures dates always display unless both are empty (which shouldn't happen for published articles)

---

## Backward Compatibility

✅ **Fully backward compatible**
- Fallback parameter is optional (default: null)
- Existing code without fallback continues to work
- No breaking changes to function signatures
- No database schema changes (only migrations to populate data)

---

## Performance Note

The updated functions are equally performant as the original versions:
- Same number of database queries
- Same string processing logic
- Added fallback check (negligible overhead)

---

## Questions?

Review the detailed changes in: `ARTICLE_DATE_FIX_SUMMARY.md`
