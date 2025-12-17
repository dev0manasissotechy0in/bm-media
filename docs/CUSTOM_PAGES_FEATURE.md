# Custom Pages Feature - Implementation Guide

## Overview
The custom pages feature allows you to display admin-created pages directly within your mobile app, providing a seamless in-app experience for static content like About Us, Privacy Policy, Terms & Conditions, and more.

## Components Created

### 1. Backend API Endpoint
**File:** `api/app/get-page-details.php`

**Purpose:** Fetches detailed information about a custom page by slug or ID.

**Parameters:**
- `slug` (optional): The page slug (e.g., "about-us")
- `id` (optional): The page ID (numeric)

**Response Format:**
```json
{
  "success": true,
  "page": {
    "id": 1,
    "title": "About Us",
    "slug": "about-us",
    "type": "text",
    "content": "<html content>",
    "views": 42,
    "share_url": "http://yoursite.com/page.php?slug=about-us",
    "articles": [] // For category_articles or tag_articles types
  }
}
```

**Example API Calls:**
```bash
# By slug
http://192.168.1.3/api/app/get-page-details.php?slug=about-us

# By ID
http://192.168.1.3/api/app/get-page-details.php?id=1
```

### 2. Flutter Model
**File:** `news_app/lib/models/custom_page_detail.dart`

**Classes:**
- `CustomPageDetail`: Main page model with all page properties
- `CustomPageArticle`: Simplified article model for pages that display articles

### 3. Flutter Service
**File:** `news_app/lib/services/custom_pages_service.dart`

**New Method Added:**
```dart
Future<CustomPageDetail?> getPageDetails({String? slug, int? pageId})
```

### 4. Flutter Screen
**File:** `news_app/lib/screens/custom_page_details_screen.dart`

**Features:**
- ✅ Displays HTML content with proper styling
- ✅ Share page functionality
- ✅ View count tracking
- ✅ Pull-to-refresh
- ✅ Error handling with retry
- ✅ Loading states
- ✅ Support for article-based pages (category_articles, tag_articles)
- ✅ Responsive design with Flutter HTML rendering

**Usage:**
```dart
// Navigate by slug
NextScreen.normal(
  context,
  CustomPageDetailsScreen(
    slug: 'about-us',
    pageTitle: 'About Us',
  ),
);

// Navigate by ID
NextScreen.normal(
  context,
  CustomPageDetailsScreen(
    pageId: 1,
    pageTitle: 'About Us',
  ),
);
```

### 5. Integration with Settings
**File:** `news_app/lib/screens/tabs/profile_tab/settings.dart`

**Change:** Custom pages now open in-app instead of external browser.

**Before:**
```dart
onTap: () => AppService().openLinkWithCustomTab(page.url)
```

**After:**
```dart
onTap: () => NextScreen.normal(
  context,
  CustomPageDetailsScreen(
    slug: page.slug,
    pageTitle: page.title,
  ),
)
```

## Database Requirements

### Custom Pages Table
The `custom_pages` table must have these columns:
- `id` - Primary key
- `title` - Page title
- `slug` - URL-friendly identifier
- `page_type` - Type of page (text, category_articles, tag_articles, etc.)
- `content` - HTML content
- `category_id` - For category_articles type
- `tag_id` - For tag_articles type
- `status` - published/draft
- `show_in_app` - BOOLEAN (must be 1 to show in app)
- `views_count` - View tracking
- `created_at` - Timestamp
- `updated_at` - Timestamp

### Enable Pages in App
Run this SQL to enable common pages:
```sql
UPDATE custom_pages 
SET show_in_app = 1 
WHERE slug IN ('about-us', 'privacy-policy', 'terms-and-conditions') 
AND status = 'published';
```

## Admin Panel Integration

### File: `admin/pages.php`
The admin panel already has the `show_in_app` toggle feature. To enable a page in the app:
1. Go to Admin → Custom Pages
2. Edit the page
3. Check "Show in App" checkbox
4. Save

## Supported Page Types

### 1. Text Pages
Standard HTML content pages.
```
Type: text
Content: HTML formatted text
```

### 2. Category Articles Pages
Display articles from a specific category.
```
Type: category_articles
Content: Automatically fetches articles from category_id
```

### 3. Tag Articles Pages
Display articles tagged with a specific tag.
```
Type: tag_articles
Content: Automatically fetches articles from tag_id
```

## HTML Content Styling

The screen uses `flutter_html` package with custom styling:
- Responsive images
- Proper heading hierarchy (h1, h2, h3)
- List formatting (ul, ol)
- Link styling with app theme colors
- Proper spacing and margins

## Testing

### API Testing
```powershell
# Test page details API
$page = (Invoke-WebRequest -Uri "http://192.168.1.3/api/app/get-page-details.php?slug=about-us" -UseBasicParsing).Content | ConvertFrom-Json
Write-Host "Success: $($page.success), Title: $($page.page.title)"

# Test pages list API
$pages = (Invoke-WebRequest -Uri "http://192.168.1.3/api/app/get-app-pages.php" -UseBasicParsing).Content | ConvertFrom-Json
Write-Host "Count: $($pages.count), Pages: $($pages.pages.title -join ', ')"
```

### App Testing
1. Build and run the Flutter app
2. Navigate to Settings/Profile tab
3. Click on any custom page (About Us, Privacy Policy, etc.)
4. Verify:
   - Page loads within app
   - Content displays properly
   - Share button works
   - Pull-to-refresh works
   - Back button navigation works

## Benefits

✅ **Better User Experience**: Users stay in-app instead of opening browser
✅ **Native Look & Feel**: Pages use app theme and styling
✅ **Offline Support**: Can be extended to cache content
✅ **Analytics**: Track page views directly in your database
✅ **Consistent Navigation**: Same navigation patterns as rest of app
✅ **Share Integration**: Native share functionality
✅ **Admin Control**: Easy management through admin panel

## Future Enhancements

Consider adding:
- [ ] Offline caching of pages
- [ ] Search within page content
- [ ] Bookmark pages
- [ ] Dark mode specific content styling
- [ ] Table of contents for long pages
- [ ] Page analytics dashboard in admin
- [ ] Multi-language support for pages

## Troubleshooting

### Pages Not Showing in App List
- Check `show_in_app = 1` in database
- Verify `status = 'published'`
- Clear app cache and refresh

### Content Not Displaying
- Check HTML validity in admin panel
- Verify API response has content field
- Check console logs for parsing errors

### Images Not Loading
- Ensure image paths are absolute URLs
- Check image file permissions
- Verify uploads directory is accessible

### Share Not Working
- Verify `share_plus` package is installed
- Check platform-specific share configurations
- Test on physical device (simulator may have limitations)

## Dependencies Used

```yaml
dependencies:
  flutter_html: # For HTML content rendering
  share_plus: # For sharing functionality
  http: # For API calls
```

Make sure these are in your `pubspec.yaml` file.

## Maintenance

### Regular Tasks
1. Monitor view counts in admin panel
2. Update content as needed
3. Check for broken links in HTML content
4. Review page performance analytics
5. Keep `show_in_app` flags updated

### When Adding New Pages
1. Create page in admin panel
2. Set `show_in_app = 1`
3. Set appropriate icon in settings.dart (optional)
4. Test on both platforms (iOS & Android)
5. Update navigation if needed

---

**Last Updated:** December 13, 2025
**Version:** 1.0
**Tested On:** Flutter 3.x, Android & iOS
