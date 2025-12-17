# Sidebar & Category Ads Fix

## Issue
Sidebar Middle Ad and Category Top Ad were not displaying on the website.

## Root Cause Analysis

### 1. Sidebar Middle Ad Issue
- **Location**: The sidebar ad placement call existed in `index.php` line 384
- **Problem**: The home page (index.php) doesn't have a sidebar layout, so the ad placement was in the wrong location
- **Actual Need**: Sidebar ads should display on article pages where an actual sidebar exists

### 2. Category Top Ad Issue
- **Location**: Category pages (`category.php`)
- **Problem 1**: AdsManager was not initialized in category.php
- **Problem 2**: No `showCustomAd('category', 'top')` call existed anywhere in the file

## Solution Implemented

### 1. Fixed Sidebar Ad Placement

#### Removed from index.php
```php
// REMOVED - No sidebar exists on home page
<?php echo AdsManager::showCustomAd('sidebar', 'middle'); ?>
```

#### Added to article.php
```php
<!-- Sidebar Ad -->
<?php echo AdsManager::showCustomAd('sidebar', 'middle'); ?>
```

**Location**: Between "Related Articles" widget and "Popular Articles" widget in the sidebar section (line ~752)

### 2. Fixed Category Ad Placement

#### Initialized AdsManager in category.php
```php
// Initialize ads manager
require_once 'includes/AdsManager.php';
AdsManager::init($db);
```

**Location**: After `$db = Database::getInstance();` (line 14-15)

#### Added Category Top Ad
```php
<!-- Category Ad -->
<?php echo AdsManager::showCustomAd('category', 'top'); ?>
```

**Location**: Right after `<div class="container py-4">` and before the category header (line 59)

## Files Modified

1. **index.php**
   - Removed incorrect sidebar ad placement (home page has no sidebar)
   
2. **article.php**
   - Added sidebar middle ad in the sidebar section
   - Placed between related articles and trending articles widgets
   
3. **category.php**
   - Added AdsManager initialization
   - Added category top ad placement after container opening

## Testing Checklist

- [x] Verify sidebar ad displays on article pages
- [x] Verify category ad displays on category pages
- [x] Confirm no broken ad calls on index.php
- [x] Verify all other ads still working (header, article, footer)

## Sample Ads in Database

Ensure these ads exist in `custom_ads` table:

```sql
-- Sidebar Middle Ad
INSERT INTO custom_ads (title, code, placement, position, status) VALUES
('Sidebar Middle Ad', '<div style="...">Sidebar Ad Content</div>', 'sidebar', 'middle', 1);

-- Category Top Ad
INSERT INTO custom_ads (title, code, placement, position, status) VALUES
('Category Top Ad', '<div style="...">Category Ad Content</div>', 'category', 'top', 1);
```

## Current Ad Placements

| Page | Placement | Position | Status |
|------|-----------|----------|--------|
| All Pages | Header | Top | ✅ Working |
| Article Page | Article | Middle | ✅ Working |
| Article Page | Sidebar | Middle | ✅ Fixed |
| Category Page | Category | Top | ✅ Fixed |
| All Pages | Footer | Bottom | ✅ Working |

## How Ads Work

1. **AdsManager::init($db)** - Must be called at the top of each page
2. **AdsManager::showCustomAd($placement, $position)** - Displays the ad
3. **Database Query** - Fetches active ads from `custom_ads` table WHERE `status=1`
4. **Impression Tracking** - Automatically logs view in `ad_analytics` table

## Notes

- The sidebar ad is now correctly placed only on article pages where a sidebar actually exists
- Category ads will display at the top of every category page before the article grid
- All ad placements now follow the correct page structure
- AdsManager must be initialized on any page that uses custom ads

## Date
December 2024
