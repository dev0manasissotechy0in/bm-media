# Podcast Duration Auto-Calculation - Implementation Summary

## Overview
Duration is now automatically calculated from audio files and displayed appropriately for single podcasts and series.

## Changes Made

### 1. Admin Podcast Management (podcasts.php)
**Added Duration Column:**
- Query updated to calculate `total_duration`:
  - **Single Podcasts**: Shows the single podcast duration
  - **Series**: Shows sum of all published episode durations (Total)
- Duration displayed in human-readable format: `Xh Ym Zs`
- Series durations show "(Total)" label

**Display Format:**
```
Single Podcast: 1h 25m 30s
Series: 2h 45m 15s (Total)
```

### 2. Series Management (podcast-series.php)
**Added Total Duration:**
- Query includes `total_duration` calculation (sum of all episode durations)
- Duration displayed prominently in blue info alert box
- Format: "Total Duration: Xh Ym Zs"

### 3. Frontend Podcast Display (podcast.php)
**Duration Badge:**
- Calculates total duration for series by summing all episode durations
- Shows single podcast duration for standalone podcasts
- Displays as dark badge with clock icon
- Format: `Xh Ym` (Total) for series

**Individual Episodes:**
- Each episode shows its own duration: `H:i:s` format
- Episodes display duration alongside season/episode info

### 4. Auto-Detection from Audio Files

**podcast-add.php (Single Podcasts):**
- JavaScript automatically detects duration when audio file is uploaded
- Audio preview player appears
- Duration field is read-only and auto-populated
- Shows formatted duration: "1h 25m 30s (5130 seconds)"

**podcast-episode-add.php (Series Episodes):**
- Toggle between "Upload File" and "External URL"
- When file is uploaded:
  - JavaScript reads audio metadata
  - Auto-detects and fills duration
  - Shows formatted duration display
  - Audio preview player appears
- Duration field is read-only

### 5. Database Queries

**Podcasts List Query:**
```sql
SELECT 
    p.*,
    (SELECT COUNT(*) FROM podcast_episodes WHERE podcast_id = p.id) as episode_count,
    CASE 
        WHEN p.is_series = 1 THEN 
            (SELECT COALESCE(SUM(duration), 0) 
             FROM podcast_episodes 
             WHERE podcast_id = p.id AND status = 'published')
        ELSE p.duration
    END as total_duration
FROM podcasts p
```

**Series List Query:**
```sql
SELECT 
    p.*,
    (SELECT COALESCE(SUM(duration), 0) 
     FROM podcast_episodes 
     WHERE podcast_id = p.id) as total_duration
FROM podcasts p
WHERE is_series = 1
```

## Duration Display Formats

### Admin Pages
- **Hours + Minutes + Seconds**: `2h 45m 30s`
- **Series Label**: `(Total)` appended to series durations

### Frontend
- **Badge Format**: `2h 45m (Total)`
- **Episode List**: `HH:MM:SS` (e.g., `01:25:30`)

### Auto-Detection Display
- **Full Format**: `1h 25m 30s (5130 seconds)`
- **Status**: Shows in green text when detected successfully
- **Error**: Shows in red text if audio file can't be loaded

## User Experience

### Admin Creating Single Podcast:
1. Fill in podcast details
2. Select "Single Podcast" type
3. Upload audio file
4. Duration automatically detected and displayed
5. Submit form - duration saved to database

### Admin Creating Series:
1. Fill in series details
2. Select "Series (Multiple Episodes)" type
3. No audio upload needed (handled per episode)
4. Create series
5. Add episodes with individual durations
6. **Total duration automatically calculated** as sum of all episodes

### Admin Adding Episode:
1. Choose "Upload File" or "External URL"
2. If uploading file:
   - Select audio file
   - Duration automatically detected
   - Preview player appears
3. Fill in other details
4. Submit - duration saved to database

### Frontend Display:
1. **Single Podcast**: Shows single duration badge
2. **Series**: Shows total duration badge with "(Total)" label
3. **Episode List**: Each episode shows individual duration
4. **Player**: Duration used for progress bar and time display

## Benefits

✅ **No Manual Entry**: Users don't need to calculate or enter durations
✅ **Accurate**: Duration extracted directly from audio file metadata
✅ **Consistent**: Same format across all pages
✅ **Informative**: Series show both total and individual episode durations
✅ **Visual Feedback**: Audio preview player confirms file loaded correctly
✅ **Automatic Updates**: Series totals update as episodes are added/removed

## Technical Details

### JavaScript Duration Detection
```javascript
// Create object URL from file
const fileURL = URL.createObjectURL(file);
audioPreview.src = fileURL;

// Wait for metadata to load
audioPreview.addEventListener('loadedmetadata', function() {
    const duration = Math.round(audioPreview.duration);
    durationInput.value = duration;
    
    // Format for display
    const hours = Math.floor(duration / 3600);
    const minutes = Math.floor((duration % 3600) / 60);
    const seconds = duration % 60;
    
    // Display: "1h 25m 30s (5130 seconds)"
});
```

### PHP Duration Formatting
```php
<?php
$hours = floor($duration / 3600);
$minutes = floor(($duration % 3600) / 60);
$seconds = $duration % 60;
?>
<?php if ($hours > 0): ?><?= $hours ?>h <?php endif; ?>
<?php if ($minutes > 0): ?><?= $minutes ?>m <?php endif; ?>
<?= $seconds ?>s
```

## Files Modified

1. ✅ `admin/podcasts.php` - Added duration column with calculations
2. ✅ `admin/podcast-series.php` - Added total duration display
3. ✅ `admin/podcast-add.php` - Auto-detect duration from uploaded audio
4. ✅ `admin/podcast-episode-add.php` - Auto-detect episode duration
5. ✅ `podcast.php` - Show total duration badge and individual episode durations

## Database Schema

### podcasts table
- `duration` (INT) - Duration in seconds (for single podcasts)

### podcast_episodes table
- `duration` (INT) - Duration in seconds (for each episode)

## Browser Compatibility

The auto-detection feature works in all modern browsers:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

Uses HTML5 Audio API for metadata reading.

---

**Implementation Date:** December 7, 2025
**Status:** Complete ✅
**Duration Calculation:** Fully Automated 🎯
