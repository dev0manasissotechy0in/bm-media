# Firebase Cloud Messaging v1 API Setup Guide

## Your Current Situation
- **Legacy API**: Disabled (deprecated since June 2023)
- **Solution**: Use FCM v1 API with Service Account authentication

## Steps to Enable FCM v1 API

### 1. Download Service Account JSON

1. In Firebase Console, go to **Project Settings** (⚙️ icon)
2. Click on **Service accounts** tab
3. Click **Generate new private key** button
4. Save the JSON file as `firebase-service-account.json`
5. Upload it to: `C:\xampp\htdocs\` (root directory, same level as config folder)

**⚠️ IMPORTANT**: Add this to your `.gitignore`:
```
firebase-service-account.json
```

### 2. Install Google Client Library

Open PowerShell in `C:\xampp\htdocs\` and run:

```powershell
composer require google/apiclient:^2.0
```

This installs the Google API client needed for OAuth authentication.

### 3. Update Your Code

The new `FCMv1Helper.php` class is already created. To use it:

**In your notification sending code**, replace:
```php
require_once INCLUDES_PATH . '/FCMHelper.php';
$fcm = new FCMHelper();
```

With:
```php
require_once INCLUDES_PATH . '/FCMv1Helper.php';
$fcm = new FCMv1Helper('brackoddmedia-56b89');
```

### 4. Update Mobile App (Flutter)

Your mobile app needs to use the **v1/FCM** configuration. Check that your `firebase_messaging` plugin is up to date in `pubspec.yaml`:

```yaml
dependencies:
  firebase_messaging: ^14.7.0  # Or latest version
```

### 5. Alternative: Enable Legacy API (Temporary)

If you need a quick fix while migrating:

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select project **brackoddmedia-56b89**
3. Search for "**Firebase Cloud Messaging API**" in the search bar
4. Click on it and **Enable** the API
5. Return to Firebase Console → Cloud Messaging
6. The Server Key should now be visible

**Note**: This is temporary as the legacy API will be fully removed eventually.

## Testing

After setup, test with:
```php
require_once 'includes/FCMv1Helper.php';
$fcm = new FCMv1Helper('brackoddmedia-56b89');
$result = $fcm->sendToTopic('all', 'Test', 'Hello from FCM v1!', ['type' => 'test']);
```

## Benefits of FCM v1 API

✅ More secure (OAuth 2.0 instead of static key)  
✅ Better error messages  
✅ More features (platform-specific options)  
✅ Future-proof (won't be deprecated)  
✅ No need to store keys in database (uses JSON file)
