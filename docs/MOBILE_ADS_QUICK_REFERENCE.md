# Mobile Ads System - Quick Reference Guide

## System Architecture

```
┌─────────────────────────────────────────────────────┐
│           MOBILE ADS SYSTEM OVERVIEW                │
└─────────────────────────────────────────────────────┘

┌─ AdsManager Class ────────────────────────────────┐
│  includes/AdsManager.php                           │
│  ├─ init()                                        │
│  ├─ showBannerAd()                                │
│  ├─ showArticleAd()                               │
│  ├─ showCustomAd(placement, position)             │
│  └─ getAdsenseScript()                            │
└─────────────────────────────────────────────────┬─┘
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        ▼               ▼               ▼
   ┌────────┐   ┌─────────────┐   ┌──────────┐
   │Desktop │   │   Mobile    │   │ Database │
   │  Ads   │   │    Ads      │   │  Tables  │
   │        │   │             │   │          │
   │        │   │ mobile-     │   │ ads_     │
   │ index  │   │ stories     │   │settings  │
   │article │   │ mobile-     │   │custom_   │
   │        │   │ story       │   │ads       │
   │        │   │             │   │ad_       │
   │        │   │             │   │analytics │
   └────────┘   └─────────────┘   └──────────┘
```

## Mobile Stories Page Flow

```
mobile-stories.php
│
├─ [1] Load Database & Init AdsManager
│
├─ [2] Fetch Stories from Database
│
├─ [3] Include Header
│
├─► TOP AD SECTION
│   ├─ Banner Ad (Google AdSense)
│   └─ Custom Ad (placement: 'mobile', position: 'top')
│
├─► MAIN CONTENT
│   ├─ Stories grouped by category
│   └─ Each story item clickable
│
├─► MIDDLE AD SECTION
│   ├─ Article Ad (Google AdSense)
│   └─ Custom Ad (placement: 'mobile', position: 'middle')
│
└─ Include Footer
```

## Mobile Story Detail Page Flow

```
mobile-story.php
│
├─ [1] Load Database & Init AdsManager
│
├─ [2] Fetch Story Details
│
├─► HTML HEAD SECTION
│   ├─ Meta Tags (Open Graph, Twitter Card)
│   ├─ Styles (Full-screen story viewer)
│   └─► Google AdSense Script
│       └─ AdsManager::getAdsenseScript()
│
├─► HTML BODY SECTION
│   ├─ Full-screen Story Display
│   ├─ Progress Bar
│   ├─ Story Image
│   ├─ Story Title & Links
│   │
│   └─► BOTTOM AD SECTION
│       ├─ Banner Ad (Google AdSense)
│       └─ Custom Ad (placement: 'mobile', position: 'bottom')
│
└─ JavaScript (Story Interactions)
```

## Ad Display Workflow

```
┌─────────────────────────────────────┐
│  Page Load Begins                   │
└────────────┬────────────────────────┘
             │
             ▼
┌─────────────────────────────────────┐
│  AdsManager::init($db)              │
│  - Load ads_settings from DB        │
│  - Load custom_ads from DB          │
│  - Cache settings in memory         │
└────────────┬────────────────────────┘
             │
             ├─────────────────────────────────┬─────────────────────────┐
             │                                 │                         │
             ▼                                 ▼                         ▼
    ┌──────────────────┐          ┌────────────────────┐      ┌───────────────────┐
    │ showBannerAd()   │          │showCustomAd()      │      │getAdsenseScript() │
    │                  │          │                    │      │                   │
    │ Checks:          │          │ Checks:            │      │ Returns:          │
    │ - AdSense active │          │ - Placement match  │      │ - Script tag with │
    │ - Slot ID exists │          │ - Position match   │      │   Publisher ID    │
    │                  │          │ - Status = active  │      │                   │
    │ Returns:         │          │                    │      │ Includes:         │
    │ - HTML ad unit   │          │ Returns:           │      │ - Pagead script   │
    │ - With tracking  │          │ - HTML ad code     │      │ - Auto ads logic  │
    │                  │          │ - Track impression │      │                   │
    └──────────────────┘          └────────────────────┘      └───────────────────┘
             │                                 │                         │
             └─────────────────────────────────┴─────────────────────────┘
                                    │
                                    ▼
                    ┌──────────────────────────────┐
                    │  Insert into ad_analytics    │
                    │  - Event: impression         │
                    │  - Ad ID                     │
                    │  - Page URL                  │
                    │  - User IP & Agent           │
                    │  - Timestamp                 │
                    └──────────────────────────────┘
                                    │
                                    ▼
                    ┌──────────────────────────────┐
                    │  Rendered in Browser         │
                    │  - CSS styling applied       │
                    │  - Responsive layout         │
                    │  - Click tracking ready      │
                    └──────────────────────────────┘
```

## Mobile vs Desktop Placements

```
┌──────────────────────────────────────────────────────┐
│              PLACEMENT LOCATIONS                     │
├──────────────────────────────────────────────────────┤
│                                                      │
│  MOBILE PLACEMENTS          │  DESKTOP PLACEMENTS   │
│  ════════════════════════════════════════════════  │
│                             │                       │
│  Placement: 'mobile'        │  Placement: 'header'  │
│  ├─ Position: 'top'         │  ├─ Position: 'top'   │
│  ├─ Position: 'middle'      │  ├─ Position: 'middle'│
│  └─ Position: 'bottom'      │  └─ Position: 'bottom'│
│                             │                       │
│  Placement: 'sidebar'       │  Placement: 'sidebar' │
│  ├─ Position: 'top'         │  ├─ Position: 'top'   │
│  ├─ Position: 'middle'      │  ├─ Position: 'middle'│
│  └─ Position: 'bottom'      │  └─ Position: 'bottom'│
│                             │                       │
│  Placement: 'article'       │  Placement: 'article' │
│  ├─ Position: 'top'         │  ├─ Position: 'top'   │
│  ├─ Position: 'middle'      │  ├─ Position: 'middle'│
│  └─ Position: 'bottom'      │  └─ Position: 'bottom'│
│                             │                       │
│                             │  Placement: 'footer'  │
│                             │  ├─ Position: 'top'   │
│                             │  ├─ Position: 'middle'│
│                             │  └─ Position: 'bottom'│
│                             │                       │
│                             │  Placement: 'category'│
│                             │  └─ Position: 'top'   │
│                                                    │
└──────────────────────────────────────────────────────┘
```

## Data Flow for Mobile Ads

```
┌─────────────────────────────────────────────────────┐
│              DATA FLOW DIAGRAM                      │
└─────────────────────────────────────────────────────┘

  USER VISITS MOBILE PAGE
        │
        ▼
  ┌──────────────────┐
  │ mobile-stories   │
  │ OR               │
  │ mobile-story     │
  └────────┬─────────┘
           │
           ▼
    ┌─────────────────────────────┐
    │ AdsManager::init()          │
    │                             │
    │ SQL: SELECT * FROM          │
    │ ads_settings WHERE          │
    │ type = 'google_adsense'     │
    │ AND enabled = 1             │
    │                             │
    │ SQL: SELECT * FROM          │
    │ custom_ads WHERE            │
    │ status = 1                  │
    └────────┬────────────────────┘
             │
      ┌──────┴───────────┐
      │                  │
      ▼                  ▼
   ┌──────┐         ┌──────────────┐
   │Check │         │Get Custom    │
   │AdSense│        │Ads for       │
   │Config │        │placement     │
   │      │         │'mobile'      │
   └──────┘         └──────────────┘
             │                  │
             └──────────┬───────┘
                        │
                        ▼
            ┌──────────────────────┐
            │ Display HTML         │
            │ - Ad containers      │
            │ - AdSense script     │
            │ - Custom ad code     │
            │ - CSS classes        │
            └──────┬───────────────┘
                   │
                   ▼
        ┌──────────────────────────┐
        │ Browser Rendering        │
        │ - Applies CSS styling    │
        │ - Loads AdSense script   │
        │ - Responsive sizing      │
        │ - Ready for interaction  │
        └──────┬───────────────────┘
               │
        ┌──────┴──────────┐
        │                 │
        ▼                 ▼
   USER SEES AD      IMPRESSION TRACKED
                    │
                    ▼
              ┌──────────────────┐
              │INSERT ad_analytics│
              │- event: impression
              │- ad_id, placement│
              │- user_ip, user_  │
              │  agent, page_url │
              │- timestamp       │
              └──────────────────┘
```

## API Response Flow

```
┌────────────────────────────────────────────┐
│         API: track-click.php               │
│         POST /api/ads/track-click.php      │
└────────────────────────────────────────────┘
         │
         ▼ (Input Parameters)
    ┌─────────────────────┐
    │ ad_id=5             │
    │ ad_type=custom      │
    │ placement=mobile    │
    └────────┬────────────┘
             │
             ▼
    ┌─────────────────────────────┐
    │ Validate Inputs             │
    │ - Check ad_id exists        │
    │ - Validate ad_type          │
    └────────┬────────────────────┘
             │
      ┌──────┴──────────┐
      │                 │
    YES                 NO
      │                 │
      ▼                 ▼
   INSERT          Return Error
   ad_analytics    400/500
   event: click    │
      │            ▼
      │       JSON Response:
      │       {"success": false,
      │        "message": "..."}
      │
      └──────────┬──────────────┐
                 │              │
                 ▼              ▼
            Increment     Return
            clicks in     Success
            custom_ads    │
                 │        ▼
                 │    JSON:
                 │    {"success": true,
                 │     "message": "..."}
                 │
                 └────────┬─────────┘
                          │
                          ▼
                   Analytics Updated
                   Visible in
                   Admin Panel
```

## Mobile Page Integration Timeline

```
COMPLETION SEQUENCE:

Step 1: ✅ COMPLETED
├─ mobile-stories.php updated
│  ├─ Added AdsManager init
│  ├─ Added TOP ad placement
│  └─ Added MIDDLE ad placement

Step 2: ✅ COMPLETED  
├─ mobile-story.php updated
│  ├─ Added AdsManager init
│  ├─ Added AdSense script in head
│  └─ Added BOTTOM ad placement

Step 3: ✅ COMPLETED
├─ Documentation created
│  ├─ MOBILE_ADS_SETUP.md (guide)
│  └─ MOBILE_ADS_INTEGRATION_SUMMARY.md (summary)

Step 4: 📍 YOUR NEXT STEP
├─ Create mobile-specific ads
│  ├─ Go to Admin Panel
│  ├─ Create 3 custom ads
│  ├─ Set placement: 'mobile'
│  ├─ Set positions: top, middle, bottom
│  └─ Save and test

Step 5: 📍 AFTER CREATION
├─ Test on mobile devices
│  ├─ Desktop browser mobile view
│  ├─ Real phone devices
│  └─ Verify responsive behavior

Step 6: 📍 ONGOING
├─ Monitor analytics
│  ├─ Track impressions/clicks
│  ├─ Check CTR rates
│  └─ Optimize placements
```

## Quick Command Reference

### Creating Mobile Ads via Admin Panel

```
1. URL: /admin/ads.php
2. Tab: "Custom Ads"
3. Button: "Add New Ad"

Form Fields:
├─ Title: [Your ad name]
├─ Code: [HTML/JS code]
├─ Placement: 'mobile'        ← IMPORTANT
├─ Position: 'top' | 'middle' | 'bottom'
└─ Status: Active

Save: Click "Add Ad" button
```

### Testing Mobile View (Browser)

```
1. Open mobile-stories.php
2. Press F12 (DevTools)
3. Click device toolbar icon
4. Select mobile device
5. View ads in mobile layout
6. Reload to verify ads appear
```

### Viewing Mobile Ad Analytics

```
SQL Query:
SELECT 
  id, title, placement, position,
  impressions, clicks,
  ROUND(clicks/impressions*100, 2) as ctr
FROM custom_ads
WHERE placement = 'mobile'
ORDER BY impressions DESC;
```

## File Structure After Integration

```
c:\xampp\htdocs\
├── mobile-stories.php ..................... ✅ UPDATED
├── mobile-story.php ....................... ✅ UPDATED
├── includes/
│   └── AdsManager.php ..................... (existing)
├── admin/
│   └── ads.php ............................ (existing)
├── api/
│   └── ads/
│       └── track-click.php ................ (existing)
├── database/
│   └── ads_management.sql ................. (existing)
├── assets/css/
│   └── style.css .......................... (existing)
├── MOBILE_ADS_SETUP.md .................... ✅ NEW
├── MOBILE_ADS_INTEGRATION_SUMMARY.md ...... ✅ NEW
├── ADS_MANAGEMENT_GUIDE.md ................ (existing)
├── ADS_SYSTEM_INTEGRATION.md .............. (existing)
└── QUICK_START_ADS.md ..................... (existing)
```

## Next Actions Checklist

- [ ] Read MOBILE_ADS_SETUP.md for detailed guide
- [ ] Go to Admin Panel → Ads Management
- [ ] Create first custom ad with placement: 'mobile'
- [ ] Test on mobile-stories.php
- [ ] Test on mobile-story.php with real device
- [ ] Monitor analytics daily
- [ ] Optimize based on performance data
- [ ] Create more mobile-specific ads

---

**Mobile ads system is fully integrated and ready to use!** 🎉

