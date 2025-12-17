# Ads Management System - Quick Start Guide

## 🚀 Quick Setup (5 Minutes)

### Step 1: Import Database Schema (1 minute)

Open phpMyAdmin or terminal and run:

```sql
-- Copy and paste this into phpMyAdmin SQL tab or run in terminal:
-- File location: database/ads_management.sql

CREATE TABLE `ads_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `type` varchar(50),
  `client_id` varchar(100),
  `ad_slot_banner` varchar(50),
  `ad_slot_sidebar` varchar(50),
  `ad_slot_article` varchar(50),
  `enabled` boolean DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `custom_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `title` varchar(255),
  `code` longtext,
  `placement` varchar(50),
  `position` varchar(50),
  `status` boolean DEFAULT 1,
  `impressions` int DEFAULT 0,
  `clicks` int DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_placement_status` (`placement`, `status`)
);

CREATE TABLE `ad_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `ad_id` int(11),
  `ad_type` varchar(50),
  `placement` varchar(50),
  `event_type` varchar(50),
  `page_url` varchar(500),
  `user_ip` varchar(45),
  `user_agent` varchar(500),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_ad_event` (`ad_id`, `event_type`),
  KEY `idx_created` (`created_at`)
);
```

### Step 2: Verify Files Are in Place (1 minute)

Check these files exist in your installation:

✅ `includes/AdsManager.php` - Ad display helper class  
✅ `admin/ads.php` - Admin management panel  
✅ `api/ads/track-click.php` - Click tracking  
✅ `assets/css/style.css` - Ad styling (updated)  
✅ `index.php` - Homepage (updated with ad placements)  
✅ `article.php` - Article page (updated with ads)  

### Step 3: Configure Google AdSense (2 minutes)

1. Log in to Admin Panel (`/admin/`)
2. Go to **Ads Management**
3. Click **Google AdSense Settings** tab
4. Enter your Publisher ID: `ca-pub-xxxxxxxxxxxxxxxx`
5. Enter Ad Slot IDs:
   - **Banner Slot ID**: Your banner ad slot
   - **Sidebar Slot ID**: Your sidebar ad slot
   - **Article Slot ID**: Your in-content ad slot
6. Check **"Enable AdSense"** checkbox
7. Click **"Save Settings"**

### Step 4: Test the System (1 minute)

1. Visit your homepage: `http://localhost/`
2. Look for ad containers (gray boxes) in these places:
   - After Live News section
   - After Featured News section
   - After Top News section
   - Before Reels section
   - Before Category sections
3. Open browser DevTools (F12) → Console
4. Check for any JavaScript errors
5. Verify AdSense script loads (check Network tab)

## 📊 Creating Your First Custom Ad

1. Go to Admin Panel → **Ads Management**
2. Click **Custom Ads** tab
3. Click **Add New Ad** button
4. Fill in the form:

```
Title: "Test Banner Ad"
Code: <div style="width:100%;text-align:center;background:#ddd;padding:20px;">
      <h4>Test Advertisement</h4>
      <p>This is a test ad</p>
      </div>
Placement: "header"
Position: "top"
Status: "Active"
```

5. Click **Save Ad**
6. Refresh homepage to see your ad

## 📍 Ad Placements

### Homepage (`index.php`)

| Position | Type | Slot | Placement |
|----------|------|------|-----------|
| After Live News | Banner/Custom | ad_slot_banner | header:top |
| After Featured | Banner/Custom | ad_slot_banner | header:middle |
| After Top News | Article/Custom | ad_slot_article | article:top |
| Before Reels | Sidebar/Custom | ad_slot_sidebar | sidebar:middle |
| Before Categories | Banner/Custom | ad_slot_banner | footer:top |

### Article Pages (`article.php`)

| Position | Type | Slot |
|----------|------|------|
| In Article Content | Article/Custom | article:middle |

## 🎨 CSS Classes for Custom Styling

Need to customize ad appearance? Edit `assets/css/style.css`:

```css
/* Full-width banner ads */
.ad-banner-wrapper {
    min-height: 250px;
}

/* Vertical sidebar ads */
.ad-sidebar-wrapper {
    min-height: 300px;
}

/* In-content article ads */
.ad-article-wrapper {
    min-height: 280px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

/* Custom ads */
.custom-ad-wrapper {
    min-height: 90px;
}
```

## 📈 Tracking Ad Performance

### View Stats in Admin Panel

1. Go to Admin Panel → **Ads Management**
2. **Custom Ads** tab shows:
   - Impressions count
   - Clicks count
   - Auto-calculated CTR

### Database Queries

Check detailed analytics:

```sql
-- Total impressions for an ad
SELECT COUNT(*) FROM ad_analytics 
WHERE ad_id = 5 AND event_type = 'impression';

-- Total clicks for an ad
SELECT COUNT(*) FROM ad_analytics 
WHERE ad_id = 5 AND event_type = 'click';

-- Impressions by placement
SELECT placement, COUNT(*) as count 
FROM ad_analytics 
WHERE event_type = 'impression' 
GROUP BY placement;

-- Impressions by date
SELECT DATE(created_at), COUNT(*) 
FROM ad_analytics 
WHERE event_type = 'impression' 
GROUP BY DATE(created_at);
```

## 🔧 API Endpoints

### Track Ad Click

**URL**: `/api/ads/track-click.php`  
**Method**: POST or GET  

**Parameters**:
```
ad_id=5              (required - the ad ID)
ad_type=custom       (optional - 'custom' or 'adsense')
placement=article    (optional - where the ad appears)
```

**JavaScript Example**:
```javascript
// Track a click when user clicks an ad
function trackAdClick(adId, adType = 'custom') {
    fetch('/api/ads/track-click.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ad_id=${adId}&ad_type=${adType}`
    })
    .then(r => r.json())
    .then(data => console.log('Ad click tracked:', data));
}

// Use it:
trackAdClick(5, 'custom');
```

## ✅ Checklist for Going Live

- [ ] Database schema imported
- [ ] Google AdSense Publisher ID configured (if using AdSense)
- [ ] AdSense enabled in admin panel
- [ ] Tested homepage displays ads correctly
- [ ] Tested article page displays ads correctly
- [ ] Checked browser console for errors
- [ ] Created at least one test custom ad
- [ ] Verified ads are responsive on mobile
- [ ] Checked ad placements don't break layout
- [ ] Reviewed analytics setup

## 📚 Full Documentation

For detailed information, see:

- **ADS_MANAGEMENT_GUIDE.md** - Complete reference guide
- **ADS_SYSTEM_INTEGRATION.md** - Integration details
- **admin/ads.php** - Admin panel code
- **includes/AdsManager.php** - Helper class source

## 🐛 Troubleshooting

### Ads not showing?
1. Check if database tables exist
2. Verify AdSense Publisher ID is saved
3. Check if ads are enabled in admin panel
4. Clear browser cache (Ctrl+F5)
5. Check browser console (F12) for errors

### Ads looking weird?
1. Edit CSS classes in `assets/css/style.css`
2. Adjust `.ad-*-wrapper` heights
3. Change background colors
4. Add custom styling as needed

### Analytics not tracking?
1. Verify `ad_analytics` table exists
2. Check if impressions are being recorded
3. Use JavaScript console to test click tracking
4. Check server logs for API errors

## 💡 Pro Tips

1. **A/B Test**: Try different placements and sizes
2. **Monitor Performance**: Check analytics weekly
3. **Mobile First**: Ensure ads work on mobile
4. **User Experience**: Don't overload with ads
5. **Responsive**: Test on all device sizes
6. **Loading Speed**: Lazy load ads below the fold
7. **Quality**: High-quality content = better ad performance

## 🚢 Support

If you need help:

1. Check the admin panel help text
2. Review code comments in `includes/AdsManager.php`
3. See full guide: `ADS_MANAGEMENT_GUIDE.md`
4. Check database schema: `database/ads_management.sql`

---

**All Done!** Your ads management system is ready to use! 🎉

