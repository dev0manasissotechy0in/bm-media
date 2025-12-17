# Google AdSense & Custom Ads Management System

## Overview

This document explains the comprehensive ads management system for the news website, including Google AdSense integration and custom ads management.

## System Architecture

### Components

1. **Database Layer**
   - `ads_settings`: Stores Google AdSense configuration
   - `custom_ads`: Stores custom ad HTML/code with placement options
   - `ad_analytics`: Tracks impressions and clicks for reporting

2. **Backend Helper**
   - `includes/AdsManager.php`: Main class for ads display and management

3. **Admin Panel**
   - `admin/ads.php`: Complete ads management interface

4. **Frontend Integration**
   - `index.php`: Homepage with multiple ad placements
   - `article.php`: Article detail pages with in-content ads
   - `assets/css/style.css`: Ad styling and responsive design

5. **API Endpoint**
   - `api/ads/track-click.php`: Click tracking for analytics

## Setup Instructions

### Step 1: Import Database Schema

Run the SQL script to create the necessary tables:

```sql
-- Located at: database/ads_management.sql
-- Import using phpMyAdmin or command line:
mysql -u username -p database_name < database/ads_management.sql
```

### Step 2: Configure Google AdSense (Optional)

1. Go to Admin Panel → Ads Management
2. Click on "Google AdSense Settings" tab
3. Enter your Publisher ID (e.g., `ca-pub-xxxxxxxxxxxxxxxx`)
4. Enter Ad Slot IDs for:
   - **Banner Ads**: For header/full-width placements
   - **Sidebar Ads**: For sidebar placements
   - **Article Ads**: For in-content placements
5. Toggle "Enable AdSense" if you want to activate it
6. Save changes

### Step 3: Create Custom Ads (Optional)

1. Go to Admin Panel → Ads Management
2. Click on "Custom Ads" tab
3. Click "Add New Ad" button
4. Fill in the form:
   - **Title**: Name of the ad for your reference
   - **Ad Code**: Your ad HTML/JavaScript code
   - **Placement**: Where to display (header, sidebar, article, footer, category)
   - **Position**: Vertical position (top, middle, bottom)
   - **Status**: Active or Inactive

## Ad Placements

### Homepage (index.php)

The homepage has 4 strategic ad placements:

1. **Header Banner** (After Live News)
   - Displays full-width banner ads
   - Uses Google AdSense banner slot
   - Shows custom ads with placement: 'header', position: 'top'

2. **Featured News Area** (After Featured News)
   - Second banner ad placement
   - Uses Google AdSense banner slot
   - Shows custom ads with placement: 'header', position: 'middle'

3. **In-Content Area** (After Top News)
   - Large in-content ad section
   - Uses Google AdSense article/in-content slot
   - Shows custom ads with placement: 'article', position: 'top'

4. **Sidebar Area** (Before Reels)
   - Vertical sidebar ad
   - Uses Google AdSense sidebar slot
   - Shows custom ads with placement: 'sidebar', position: 'middle'

5. **Footer Area** (Before Categories)
   - Full-width footer ad
   - Uses Google AdSense banner slot
   - Shows custom ads with placement: 'footer', position: 'top'

### Article Pages (article.php)

- **In-Article Ad** (Middle of article content)
  - Large ad placement within article text
  - Uses Google AdSense in-content slot
  - Shows custom ads with placement: 'article', position: 'middle'

## CSS Classes

### Ad Wrapper Classes

```css
.ad-banner-wrapper      /* 250px height on desktop */
.ad-sidebar-wrapper     /* 300px height on desktop */
.ad-article-wrapper     /* 280px height with gradient background */
.custom-ad-wrapper      /* 90px minimum height */
```

All ad wrappers include:
- Light gray background (#f8f9fa)
- Border (1px solid #e9ecef)
- Rounded corners (8px)
- Responsive sizing for mobile devices
- Center content alignment

## API Endpoints

### Track Ad Click

**Endpoint**: `/api/ads/track-click.php`

**Method**: POST or GET

**Parameters**:
- `ad_id`: ID of the ad (required)
- `ad_type`: Type of ad - 'custom' or 'adsense' (default: 'custom')
- `placement`: Where the ad is displayed (optional)

**Example**:
```javascript
fetch('/api/ads/track-click.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'ad_id=5&ad_type=custom&placement=article'
})
.then(response => response.json())
.then(data => console.log(data));
```

## Using AdsManager Class

### Initialization

```php
require_once 'includes/AdsManager.php';
$db = Database::getInstance();
AdsManager::init($db);
```

### Common Methods

```php
// Check if AdSense is enabled
if (AdsManager::isAdsenseEnabled()) {
    // AdSense is active
}

// Get AdSense script tag
echo AdsManager::getAdsenseScript();

// Show banner ad
echo AdsManager::showBannerAd();

// Show sidebar ad
echo AdsManager::showSidebarAd();

// Show in-article ad
echo AdsManager::showArticleAd();

// Show custom ad for specific placement
echo AdsManager::showCustomAd('header', 'top');

// Get all custom ads for placement
$ads = AdsManager::getCustomAds('article');

// Get ad statistics
$stats = AdsManager::getAdStats($ad_id, 'custom');
// Returns: ['impressions' => 150, 'clicks' => 3, 'ctr' => 2.0]
```

## Database Schema

### ads_settings

Stores Google AdSense configuration:

```sql
CREATE TABLE ads_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(50),           -- 'google_adsense'
    client_id VARCHAR(100),     -- Publisher ID
    ad_slot_banner VARCHAR(50), -- Banner ad slot ID
    ad_slot_sidebar VARCHAR(50),-- Sidebar ad slot ID
    ad_slot_article VARCHAR(50),-- In-article ad slot ID
    enabled BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### custom_ads

Stores custom HTML ads:

```sql
CREATE TABLE custom_ads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    code LONGTEXT,              -- Ad HTML/JavaScript
    placement VARCHAR(50),      -- header, sidebar, article, footer, category
    position VARCHAR(50),       -- top, middle, bottom
    status BOOLEAN DEFAULT 1,
    impressions INT DEFAULT 0,
    clicks INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### ad_analytics

Tracks impressions and clicks:

```sql
CREATE TABLE ad_analytics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ad_id INT,
    ad_type VARCHAR(50),        -- 'custom' or 'adsense'
    placement VARCHAR(50),
    event_type VARCHAR(50),     -- 'impression' or 'click'
    page_url VARCHAR(500),
    user_ip VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (ad_id, event_type),
    INDEX (created_at)
);
```

## Admin Panel Features

### Google AdSense Settings Tab

- Form to enter Publisher ID
- Individual input fields for ad slot IDs
- Help text with instructions for finding IDs
- Enable/Disable toggle
- Success/Error notification system

### Custom Ads Tab

- **List View**:
  - Table with all custom ads
  - Status badges (Active/Inactive)
  - Placement and position columns
  - View count and click count
  - Edit and Delete buttons

- **Add/Edit Form**:
  - Title input
  - Ad code textarea (HTML/JavaScript)
  - Placement dropdown (5 options)
  - Position dropdown (3 options)
  - Status toggle
  - Cancel and Save buttons

## Best Practices

### For Google AdSense

1. **Use Correct Ad Slot IDs**: Ensure slot IDs match those in your AdSense account
2. **Avoid Ad Overcrowding**: Don't place ads too close together
3. **Responsive Design**: Use responsive ad units for mobile compatibility
4. **Quality Content**: Ensure high-quality content around ads for better performance
5. **User Experience**: Place ads where they don't disrupt reading experience

### For Custom Ads

1. **Test Before Posting**: Test ad code in staging before going live
2. **Responsive Code**: Ensure your ad HTML is mobile-friendly
3. **Size Consistency**: Keep ads at standard sizes (728x90, 300x250, 336x280)
4. **Clear Labeling**: Mark ads as "Advertisement" for transparency
5. **Monitor Performance**: Use analytics to track which placements perform best

### General Tips

1. **Monitor Analytics**: Check ad performance regularly in admin panel
2. **A/B Testing**: Try different placements and rotate ads
3. **Performance**: Limit ad requests to avoid slowing down page load
4. **Compliance**: Follow Google AdSense policies and FTC guidelines
5. **User Privacy**: Ensure ad tracking complies with privacy regulations

## Troubleshooting

### Ads Not Displaying

1. Check if AdSense is enabled in admin panel
2. Verify Publisher ID and ad slot IDs are correct
3. Ensure custom ads status is set to "Active"
4. Clear browser cache and reload page
5. Check browser console for JavaScript errors

### Low CTR (Click-Through Rate)

1. Try different placements (above the fold performs better)
2. Change ad positioning or placement
3. Ensure ads are relevant to content
4. Check if ads are being blocked by ad blockers
5. Test different ad sizes

### Performance Issues

1. Limit number of ads per page (3-5 is optimal)
2. Use lazy loading for ads below the fold
3. Optimize ad code for performance
4. Disable ads on slow connections
5. Use Content Delivery Network (CDN) for ad resources

## Revenue Optimization

### Recommended Placements

1. **Sticky Header Ad**: Follows user while scrolling (good CTR)
2. **In-Content Ad**: Placed between paragraphs (high engagement)
3. **Sidebar Ad**: Vertical placement (good for brand ads)
4. **Footer Ad**: Last impression before leaving
5. **Between Sections**: Separates different article sections

### Ad Size Recommendations

- **Header/Footer**: 728×90 (Leaderboard) or 970×90 (Full-Width)
- **Sidebar**: 300×250 (Medium Rectangle) or 336×280 (Large Rectangle)
- **In-Content**: 336×280 (Large Rectangle) or 300×600 (Half Page)
- **Mobile**: 320×50 (Mobile Leaderboard) or 300×250 (Medium Rectangle)

## Support & Documentation

For more information:
- Check admin panel help text
- Review code comments in AdsManager.php
- Check database schema in database/ads_management.sql
- Review integration examples in index.php and article.php

