# Mobile Ads Integration - Setup Guide

## Overview

The ads management system has been integrated with the mobile/phone pages of your website. This allows you to display Google AdSense and custom ads on mobile-optimized content.

## Mobile Pages with Ads

### 1. Mobile Stories Listing (`mobile-stories.php`)

Shows all mobile stories grouped by category.

**Ad Placements:**
- **Top**: Full-width banner after header
  - Uses: `AdsManager::showBannerAd()` + custom ads (placement: 'mobile', position: 'top')
  - Location: Line 41

- **Middle**: In-content ad between story groups
  - Uses: `AdsManager::showArticleAd()` + custom ads (placement: 'mobile', position: 'middle')
  - Location: After all story groups

### 2. Mobile Story Detail (`mobile-story.php`)

Individual story viewer with sharing options.

**Features:**
- Custom HTML layout (not using main header/footer)
- Full-screen story display
- Share buttons (WhatsApp, Facebook, Copy Link)
- Mobile-optimized design

**Ad Placements:**
- **AdSense Script**: Injected in `<head>`
  - Location: Line 218

- **Bottom**: Ad section before closing body
  - Uses: `AdsManager::showBannerAd()` + custom ads (placement: 'mobile', position: 'bottom')
  - Location: Before closing body tag

## Mobile Ad Placements Comparison

| Page | Placement | Type | Position |
|------|-----------|------|----------|
| Stories List | Header | Banner + Custom | top |
| Stories List | Content | Article + Custom | middle |
| Story Detail | Header | AdSense Script | - |
| Story Detail | Footer | Banner + Custom | bottom |

## Creating Mobile-Specific Ads

### In Admin Panel

1. Go to **Ads Management** → **Custom Ads**
2. Click **Add New Ad**
3. Fill the form:

```
Title: "Mobile Story Banner"
Code: <div style="width:100%;padding:10px;background:#f0f0f0;text-align:center;">
      <img src="your-image.jpg" style="max-width:100%;height:auto;">
      </div>
Placement: "mobile"    ← NEW PLACEMENT TYPE
Position: "top"         ← (top, middle, or bottom)
Status: "Active"
```

4. Save and test on mobile

### Mobile-Friendly Ad Code

Ensure your custom ads are mobile-optimized:

```html
<!-- Good: Responsive Ad -->
<div style="width:100%; max-width:100%; overflow:hidden;">
  <img src="ad-image.jpg" style="width:100%; height:auto; display:block;">
  <p style="margin:5px 10px; font-size:12px;">Advertisement</p>
</div>

<!-- Better: Responsive Banner -->
<div style="background:#fff; padding:10px; text-align:center; border-radius:8px;">
  <a href="your-link" style="text-decoration:none;">
    <img src="banner.jpg" style="width:100%; max-width:100%; height:auto; display:block; border-radius:4px;">
  </a>
</div>
```

### Mobile Ad Sizes (Best Practices)

- **Mobile Leaderboard**: 320×50px (top placement)
- **Mobile Rectangle**: 300×250px (middle/bottom)
- **Mobile Full Width**: 320×100px (responsive)
- **Responsive**: Use `max-width:100%` for all ads

## CSS for Mobile Ads

The ad styling is responsive and mobile-friendly:

```css
/* From assets/css/style.css */
.ad-banner-wrapper {
    min-height: 250px;  /* Desktop */
}

@media (max-width: 768px) {
    .ad-banner-wrapper {
        min-height: 180px;  /* Mobile */
    }
}
```

Mobile devices will automatically get reduced ad heights for better user experience.

## Tracking Mobile Ads

### Analytics View

Mobile ads are tracked with the placement: 'mobile'

```sql
-- Check mobile ad impressions
SELECT COUNT(*) as impressions 
FROM ad_analytics 
WHERE placement = 'mobile' 
AND event_type = 'impression';

-- Check mobile ad clicks
SELECT COUNT(*) as clicks 
FROM ad_analytics 
WHERE placement = 'mobile' 
AND event_type = 'click';

-- Mobile CTR
SELECT 
  (SELECT COUNT(*) FROM ad_analytics 
   WHERE placement = 'mobile' AND event_type = 'click') /
  (SELECT COUNT(*) FROM ad_analytics 
   WHERE placement = 'mobile' AND event_type = 'impression') * 100 as ctr;
```

### Admin Panel

1. Go to **Ads Management** → **Custom Ads**
2. Find ads with placement: 'mobile'
3. View impressions and clicks in the table

## Mobile vs. Desktop Ads

Both mobile and desktop pages can run simultaneously:

**Desktop Pages** (placement: header, sidebar, article, footer, category)
- `index.php` - Homepage
- `article.php` - Article detail
- All category pages

**Mobile Pages** (placement: mobile)
- `mobile-stories.php` - Stories listing
- `mobile-story.php` - Story detail

You can create different ads for each placement to optimize for their specific context.

## AdSense on Mobile Story Detail

The `mobile-story.php` page includes a custom AdSense script in the `<head>` tag (Line 218).

This allows AdSense auto ads or manually placed ads on the story detail page.

**Note**: The mobile story detail page has a custom layout, so the standard ad display methods might not work. The script tag is included automatically for AdSense auto ads.

## Performance Considerations

### Mobile Optimization

1. **Lazy Loading**: Ads below fold are lazy-loaded by browsers
2. **Smaller Sizes**: Mobile ads use reduced heights (180px vs 250px)
3. **Network**: Ads use HTTPS and CDN for fast loading
4. **Responsive**: All ads scale to screen size

### Best Practices

1. **Limit Ad Count**: 2-3 ads per mobile page (max)
2. **Above Fold**: Place primary ad above fold
3. **Content Priority**: Mobile users prioritize content over ads
4. **Spacing**: Leave whitespace around ads (10px minimum)
5. **Test**: Always test on real mobile devices

## Responsive CSS Example

To make custom ads fully responsive:

```html
<div style="
  width: 100%;
  max-width: 100%;
  margin: 10px 0;
  padding: 10px;
  background: #f9f9f9;
  border-radius: 8px;
  box-sizing: border-box;
">
  <img src="mobile-ad.jpg" style="
    width: 100%;
    height: auto;
    display: block;
    border-radius: 4px;
  ">
  <p style="margin: 8px 0; font-size: 11px; color: #999;">
    Advertisement
  </p>
</div>
```

## Testing Mobile Ads

### In Browser

1. Open `mobile-stories.php` in desktop browser
2. Open DevTools (F12)
3. Click device toolbar icon to switch to mobile view
4. Select **iPhone** or **Android** device
5. Reload page and verify ads display

### On Real Device

1. Connect phone to same WiFi as computer
2. Visit: `http://computer-ip:8000/mobile-stories.php`
3. Verify layout and ads look correct
4. Test touch interactions

### Common Issues

**Ads not showing**
- Check if custom ads exist with placement: 'mobile'
- Verify status is "Active" in admin panel
- Clear browser cache (Ctrl+Shift+Delete)

**Ads too large**
- Edit CSS in `assets/css/style.css`
- Reduce `.ad-banner-wrapper` min-height for mobile
- Use `max-width: 100%` on all ad images

**Layout broken**
- Check if ad code has fixed widths
- Use percentage widths (100%, 80%, etc.)
- Test on Chrome DevTools mobile view

## Advanced: Custom Mobile Placement

To add ads to other mobile pages, follow this pattern:

```php
<?php
// In your mobile page
require_once 'config/config.php';
$db = Database::getInstance();

// Initialize AdsManager
require_once 'includes/AdsManager.php';
AdsManager::init($db);
?>

<!-- In your HTML -->
<?php echo AdsManager::showBannerAd(); ?>
<?php echo AdsManager::showCustomAd('mobile', 'top'); ?>

<!-- Your mobile content here -->

<?php echo AdsManager::showCustomAd('mobile', 'bottom'); ?>
```

## Revenue Tips

1. **Test Placements**: Try top, middle, bottom positions
2. **A/B Testing**: Compare custom vs AdSense performance
3. **High CPC**: Mobile users click higher value ads
4. **Engagement**: Users spend more time on stories = more impressions
5. **Exclusive Ads**: Create mobile-exclusive ad campaigns

## Support

For issues or questions:

1. Check `ADS_MANAGEMENT_GUIDE.md` for general info
2. Check `AdsManager.php` for available methods
3. Review database schema in `database/ads_management.sql`
4. Check admin panel help text for configuration issues

