# Complete Custom Notification System - Quick Reference

## ✅ Why FCM v1 API is Better for Your Use Case

### Your Requirements:
- ✅ Action buttons (Share, Save)
- ✅ Custom badges/labels
- ✅ Different notification types with different priorities
- ✅ Rich media (images)

### FCM v1 vs Legacy:

| Feature | Legacy API | v1 API |
|---------|------------|--------|
| Action Buttons | ❌ Limited | ✅ Full Support |
| Notification Channels | ❌ No | ✅ Yes |
| Custom Colors | ❌ No | ✅ Yes |
| Rich Media | ⚠️ Basic | ✅ Advanced |
| Priority Control | ⚠️ Limited | ✅ Per Channel |
| Android 13+ Support | ❌ Deprecated | ✅ Full |
| Future-proof | ❌ Ending Soon | ✅ Yes |

## 🚀 Setup Steps

### Backend (PHP) - Already Done ✅

The `FCMv1Helper.php` class now supports:

```php
// Case Study with action buttons
$fcm->sendCaseStudyNotification($caseId, $title, $slug, $isUpdate);

// Live News with Save & Share buttons
$fcm->sendLiveNewsNotification($articleId, $title, $slug, $imageUrl);

// Stories with custom channel
$fcm->sendStoryNotification($storyId, $title, $imageUrl);

// Breaking News (highest priority)
$fcm->sendBreakingNewsNotification($articleId, $title, $slug, $imageUrl);
```

### Mobile App (Flutter) - 3 Steps

**Step 1: Install Firebase Service Account**
1. Firebase Console → Service Accounts → Generate Private Key
2. Save as `firebase-service-account.json` in `C:\xampp\htdocs\`
3. Add to `.gitignore`

**Step 2: Install Google Client**
```bash
composer require google/apiclient:^2.0
```

**Step 3: Test**
```bash
php test-fcm-v1.php
```

## 📱 Notification Types

### 1. Case Study Notifications
```php
// New Case Study
$fcm->sendCaseStudyNotification(123, "Major Legal Case Filed", "case-slug", false);
// Badge: 📋 New Case Study
// Actions: [View Case] [Share]
// Channel: Green, High Priority

// Case Update
$fcm->sendCaseStudyNotification(123, "Court Decision Released", "case-slug", true);
// Badge: 📝 Case Study Update
// Actions: [View Case] [Share]
```

### 2. Live News
```php
$fcm->sendLiveNewsNotification(456, "Election Results Coming In", "slug", "https://example.com/image.jpg");
// Badge: 🔴 Live News
// Actions: [Read Now] [Save] [Share]
// Channel: Red, Max Priority
// Feature: Large image
```

### 3. Stories
```php
$fcm->sendStoryNotification(789, "Behind the Scenes", "https://example.com/story.jpg");
// Badge: 📱 New Latest Story
// Actions: [View Story] [Share]
// Channel: Purple, Default Priority
```

### 4. Breaking News
```php
$fcm->sendBreakingNewsNotification(101, "Major Event Breaking", "slug", "https://example.com/breaking.jpg");
// Badge: 🚨 Breaking News
// Actions: [Read Now] [Save] [Share]
// Channel: Dark Red, Max Priority
// Feature: Highest priority, lights, vibration
```

## 🎨 Notification Channels & Colors

| Channel | Color | Priority | Use Case |
|---------|-------|----------|----------|
| Case Study | 🟢 Green (#10B981) | High | New cases, updates |
| Live News | 🔴 Red (#EF4444) | Max | Real-time news |
| Breaking | 🔴 Dark Red (#DC2626) | Max | Critical alerts |
| Stories | 🟣 Purple (#8B5CF6) | Default | Daily stories |
| Articles | 🔵 Blue (#3B82F6) | Default | Regular news |

## 🔧 How to Use in Your Code

### When Adding a Case Study (admin/case-add.php):
```php
if ($status === 'published') {
    require_once INCLUDES_PATH . '/FCMv1Helper.php';
    $fcm = new FCMv1Helper('brackoddmedia-56b89');
    $fcm->sendCaseStudyNotification($caseId, $title, $slug, false);
}
```

### When Publishing Live News (admin/article-add.php):
```php
if ($isLive && $status === 'published') {
    require_once INCLUDES_PATH . '/FCMv1Helper.php';
    $fcm = new FCMv1Helper('brackoddmedia-56b89');
    $imageUrl = !empty($featured_image) ? SITE_URL . '/uploads/' . $featured_image : null;
    $fcm->sendLiveNewsNotification($articleId, $title, $slug, $imageUrl);
}
```

### When Adding a Story (admin/story-add.php):
```php
if ($status === 'published') {
    require_once INCLUDES_PATH . '/FCMv1Helper.php';
    $fcm = new FCMv1Helper('brackoddmedia-56b89');
    $imageUrl = !empty($image) ? SITE_URL . '/uploads/stories/' . $image : null;
    $fcm->sendStoryNotification($storyId, $title, $imageUrl);
}
```

## 📊 Comparison: Before vs After

### Before (Legacy API):
```php
$fcm = new FCMHelper();
$fcm->sendToTopic('all', 'New Article', 'Check it out');
// ❌ No action buttons
// ❌ No custom channels
// ❌ Same priority for everything
// ❌ No rich formatting
```

### After (v1 API):
```php
$fcm = new FCMv1Helper('brackoddmedia-56b89');
$fcm->sendLiveNewsNotification($id, $title, $slug, $image);
// ✅ Action buttons: Read, Save, Share
// ✅ Custom red channel for live news
// ✅ Max priority
// ✅ Large image support
// ✅ Custom badge: 🔴 Live News
```

## 🎯 Next Steps

1. **Download Service Account JSON** from Firebase Console
2. **Save to** `C:\xampp\htdocs\firebase-service-account.json`
3. **Run** `composer require google/apiclient:^2.0`
4. **Test** with: `http://localhost/admin/test-notification.php`
5. **Update Flutter app** with notification channels (see FLUTTER_NOTIFICATION_SETUP.md)

## 📚 Documentation Files

- `FCM_V1_SETUP_GUIDE.md` - Complete setup instructions
- `FLUTTER_NOTIFICATION_SETUP.md` - Mobile app implementation
- `includes/FCMv1Helper.php` - Ready-to-use PHP class
