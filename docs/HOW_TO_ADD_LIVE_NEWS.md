# How to Add Live News Articles - Complete Guide

## 📋 Overview
Live news articles allow you to provide real-time updates on breaking stories. Updates appear in a beautiful timeline format on the article page, with the latest updates at the top.

---

## 🚀 Method 1: Using the Admin Panel (Recommended)

### Step 1: Create or Edit an Article

1. **Login to Admin Panel**
   - Go to: `http://localhost/admin/`
   - Login with your admin credentials

2. **Create New Article or Edit Existing**
   - Click "Articles" in the sidebar
   - Click "Add New Article" OR click "Edit" on an existing article

### Step 2: Mark Article as LIVE

1. In the article form, scroll to the **"Article Flags"** section (right sidebar)
2. Check the checkbox: ✅ **"Live"**
3. Save the article by clicking **"Create Article"** or **"Update Article"**

### Step 3: Add Live Updates

1. **From Articles List:**
   - Go to "Articles" page
   - Find your LIVE article (it will have a red "LIVE" badge)
   - Click the red **broadcast icon** (📡) button to manage updates

2. **From Article Edit Page:**
   - When editing a LIVE article, you'll see a red button at the top: **"Manage Live Updates"**
   - Click it to access the live updates manager

3. **Add Your First Update:**
   - In the "Add New Live Update" form on the left
   - Enter your update text (e.g., "Breaking: Officials confirm new development")
   - Click **"Publish Update"**

4. **Continue Adding Updates:**
   - Keep adding updates as events unfold
   - Latest updates automatically appear at the top
   - Each update shows timestamp and relative time

---

## 💻 Method 2: Using Database Direct Insert (Advanced)

### Step 1: Mark Article as Live
```sql
UPDATE articles 
SET is_live = 1 
WHERE id = YOUR_ARTICLE_ID;
```

### Step 2: Insert Live Updates
```sql
INSERT INTO article_live_updates (article_id, update_text, created_at)
VALUES (
    YOUR_ARTICLE_ID,
    'Your update text here',
    NOW()
);
```

### Example with Multiple Updates:
```sql
-- Mark article #5 as live
UPDATE articles SET is_live = 1 WHERE id = 5;

-- Add multiple updates
INSERT INTO article_live_updates (article_id, update_text, created_at) VALUES
(5, 'Breaking: Major announcement expected soon', '2025-12-02 14:30:00'),
(5, 'Update: Officials are gathering for press conference', '2025-12-02 14:45:00'),
(5, 'Latest: Press conference begins now', '2025-12-02 15:00:00');
```

---

## 🔧 Method 3: Using PHP Script (Bulk Import)

### Create a Script (e.g., `add_live_updates.php`):
```php
<?php
require_once 'config/config.php';

$db = Database::getInstance();

// Your article ID
$article_id = 5;

// Mark as live
$db->update('articles', ['is_live' => 1], 'id = ?', [$article_id]);

// Updates to add
$updates = [
    'Breaking: Emergency meeting called by officials',
    'Update: First statement released moments ago',
    'Developing: More details emerging from the scene',
    'Alert: Situation developing rapidly',
    'Latest: Official confirmation just received'
];

// Add each update
foreach ($updates as $index => $text) {
    $time = date('Y-m-d H:i:s', strtotime("-" . ($index * 10) . " minutes"));
    
    $db->insert('article_live_updates', [
        'article_id' => $article_id,
        'update_text' => $text,
        'created_at' => $time
    ]);
    
    echo "✓ Added: $text\n";
}

echo "\nDone! View at: " . BASE_URL . "/article/YOUR-ARTICLE-SLUG\n";
```

Run it:
```bash
php add_live_updates.php
```

---

## 📱 What Readers See

When readers visit your live article, they'll see:

### 🎨 Timeline Features:
- ✅ **Vertical timeline** with gradient line
- ✅ **"LATEST" badge** on newest update
- ✅ **Pulsing red marker** on latest update
- ✅ **Time stamps** (e.g., "3:45 PM 2 Dec")
- ✅ **Relative time** (e.g., "2 hours ago")
- ✅ **Live indicator** with blinking red dot
- ✅ **Update counter** badge
- ✅ **Smooth animations** on scroll

### Example Display:
```
┌─────────────────────────────────────────────┐
│ 🔴 LIVE Updates Timeline    [5 Updates]     │
├─────────────────────────────────────────────┤
│                                              │
│  [LATEST]                                    │
│    ● ──┬─── 3:45 PM 2 Dec                   │
│        │    Breaking: Officials confirm...   │
│        │    📅 5 minutes ago                 │
│        │                                     │
│    ● ──┬─── 3:30 PM 2 Dec                   │
│        │    Update: Press conference...      │
│        │    📅 20 minutes ago                │
│        │                                     │
│    ● ──┴─── 3:15 PM 2 Dec                   │
│            Alert: New development...         │
│            📅 35 minutes ago                 │
└─────────────────────────────────────────────┘
```

---

## 📊 Managing Live Updates

### View All Live Articles:
1. Go to Admin → **Articles**
2. Articles with red "LIVE" badges are live
3. Live article count shown in dashboard stats

### Edit/Delete Updates:
1. Click the **broadcast icon** (📡) on any live article
2. See all updates in the timeline
3. Click the **trash icon** to delete any update
4. Confirm deletion when prompted

### Stop Live Coverage:
1. Edit the article
2. Uncheck the ✅ **"Live"** checkbox
3. Save the article
4. Timeline remains visible but article no longer marked as actively updating

---

## 💡 Best Practices

### 📝 Writing Updates:
- **Keep them concise** - 1-3 sentences per update
- **Start with action words** - "Breaking:", "Update:", "Alert:", "Confirmed:"
- **Include specific times** when relevant
- **Verify information** before posting
- **Update frequently** during active coverage

### ⏰ Timing:
- Add updates as events unfold in real-time
- Don't batch updates - post them individually as they happen
- Latest updates automatically appear at the top
- Updates show both absolute time and "time ago"

### 🎯 When to Use Live Coverage:
- ✅ Breaking news events
- ✅ Live sports matches
- ✅ Elections/Results
- ✅ Weather emergencies
- ✅ Press conferences
- ✅ Ongoing investigations
- ❌ Regular evergreen content

---

## 🔍 Quick Reference

### Admin URLs:
- **Articles List:** `http://localhost/admin/articles.php`
- **Add Article:** `http://localhost/admin/article-add.php`
- **Edit Article:** `http://localhost/admin/article-edit.php?id=ARTICLE_ID`
- **Manage Live Updates:** `http://localhost/admin/live-updates.php?article_id=ARTICLE_ID`

### Database Tables:
- **Articles:** `articles` (contains `is_live` column)
- **Live Updates:** `article_live_updates` (contains timeline updates)

### Key Columns:
```
articles table:
- is_live (0 or 1)

article_live_updates table:
- id
- article_id
- update_text
- created_at
```

---

## 🆘 Troubleshooting

### Updates Not Showing?
1. ✅ Check article has `is_live = 1`
2. ✅ Verify updates exist in `article_live_updates` table
3. ✅ Clear browser cache
4. ✅ Check article status is "published"

### Timeline Not Styled?
1. ✅ Check CSS file includes timeline styles
2. ✅ Clear cache and hard refresh (Ctrl+F5)
3. ✅ Verify Bootstrap Icons are loading

### Can't Add Updates?
1. ✅ Check you're logged into admin panel
2. ✅ Verify database table exists
3. ✅ Check write permissions
4. ✅ Look for error messages

---

## 📞 Need Help?

If you encounter any issues:
1. Check the PHP error log
2. Verify database connection
3. Ensure all required tables exist
4. Check file permissions

**Happy Live Reporting! 📰🔴**
