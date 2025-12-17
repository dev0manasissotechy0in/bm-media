# Social Login Integration Setup Guide

## Overview
This integration connects Facebook and Google login from the admin panel to the Flutter news app, allowing you to control social login features dynamically from the backend.

## Setup Steps

### 1. Database Setup ✅
The database migration has been applied successfully. The following tables were created:
- `app_users` - Stores mobile app users with social login support
- `user_bookmarks` - Stores user bookmarked articles
- `user_likes` - Stores user liked articles
- Social login settings added to `site_settings` table

### 2. Backend API Endpoints Created ✅
The following API endpoints have been created:

#### a) `api/settings/app-config.php`
- **Purpose**: Returns app configuration including social login settings
- **Method**: GET
- **Response**: JSON with google/facebook login enabled status and client IDs
- **Test URL**: http://localhost/api/settings/app-config.php

#### b) `api/auth/google-login.php`
- **Purpose**: Handles Google login authentication
- **Method**: POST
- **Parameters**: 
  - `id_token` (required)
  - `email` (required)
  - `name` (required)
  - `photo_url` (optional)
  - `uid` (optional)
- **Test**: Use Postman or curl

#### c) `api/auth/facebook-login.php`
- **Purpose**: Handles Facebook login authentication
- **Method**: POST
- **Parameters**:
  - `access_token` (required)
  - `email` (required)
  - `name` (required)
  - `photo_url` (optional)
  - `uid` (optional)

### 3. Admin Panel Configuration

#### Access Admin Settings:
1. Open: **http://localhost/admin/advanced-settings.php**
2. Login with admin credentials
3. Navigate to "Social Login" tab

#### Configure Google Login:
1. **Enable Google Login**: Check the toggle
2. **Google Client ID**: Get from [Google Cloud Console](https://console.cloud.google.com/)
   - Create a new project or use existing
   - Enable Google+ API
   - Create OAuth 2.0 credentials
   - Add authorized redirect URIs:
     - `http://localhost/auth/google-callback.php`
     - `http://192.168.1.3/auth/google-callback.php`
     - For Flutter: Add your package name
3. **Google Client Secret**: Copy from Google Cloud Console
4. Click "Save Settings"

#### Configure Facebook Login:
1. **Enable Facebook Login**: Check the toggle
2. **Facebook App ID**: Get from [Facebook Developers](https://developers.facebook.com/)
   - Create a new app or use existing
   - Add Facebook Login product
   - Configure OAuth redirect URIs:
     - `http://localhost/auth/facebook-callback.php`
     - `http://192.168.1.3/auth/facebook-callback.php`
3. **Facebook App Secret**: Copy from Facebook Developers
4. Click "Save Settings"

### 4. Flutter App Updates ✅

#### Files Created/Modified:
1. **`lib/models/app_config_model.dart`** - Model for app configuration
2. **`lib/providers/app_config_provider.dart`** - Provider for fetching and managing config
3. **`lib/services/api_service.dart`** - Added methods:
   - `getAppConfig()` - Fetch app configuration
   - `googleLogin()` - Handle Google login
   - `facebookLogin()` - Handle Facebook login
4. **`lib/screens/auth/social_logins.dart`** - Updated to use app config provider
5. **`lib/configs/features_config.dart`** - Updated comments to indicate backend control

#### How It Works:
1. **App Launch**: The app fetches configuration from `api/settings/app-config.php`
2. **Dynamic Display**: Google/Facebook login buttons appear based on admin settings
3. **Authentication Flow**:
   - User taps Google/Facebook button
   - Firebase Auth handles the OAuth flow
   - App sends user data to backend API
   - Backend validates and creates/updates user in `app_users` table
   - User is logged in

### 5. Testing Instructions

#### Test Admin Panel:
1. Open http://localhost/admin/advanced-settings.php
2. Enable Google Login
3. Add dummy Client ID: `test-client-id.apps.googleusercontent.com`
4. Save settings
5. Verify API response: http://localhost/api/settings/app-config.php
   - Should show `"google_enabled": true`

#### Test Flutter App:
1. Hot restart the Flutter app
2. Navigate to Login screen
3. You should see:
   - "Sign In With Google" button (if enabled in admin)
   - "Sign In With Facebook" button (if enabled in admin)
4. Buttons will show/hide based on admin panel settings

#### Test API Endpoints:
```bash
# Test app config
curl http://localhost/api/settings/app-config.php

# Test Google login (after getting real token from Firebase)
curl -X POST http://localhost/api/auth/google-login.php \
  -H "Content-Type: application/json" \
  -d '{
    "id_token": "your_google_id_token",
    "email": "user@gmail.com",
    "name": "Test User",
    "uid": "firebase_uid"
  }'
```

### 6. Real Implementation Steps

#### For Production Google Login:
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create OAuth 2.0 credentials (Android & iOS)
3. For **Android**:
   - Package name: `com.yourcompany.newsapp`
   - SHA-1 certificate fingerprint (debug & release)
4. For **iOS**:
   - Bundle ID: `com.yourcompany.newsapp`
   - App Store ID (if published)
5. Add OAuth Client IDs to admin panel
6. Update `android/app/google-services.json` and `ios/Runner/GoogleService-Info.plist`

#### For Production Facebook Login:
1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create/configure app
3. Add Facebook Login product
4. Configure Android & iOS platforms:
   - **Android**: Package name and key hash
   - **iOS**: Bundle ID
5. Add App ID and Secret to admin panel
6. Update `android/app/strings.xml` and `ios/Info.plist` with Facebook App ID

### 7. Required Flutter Packages (To Re-enable)

To enable actual Google/Facebook login (currently disabled), uncomment in `pubspec.yaml`:
```yaml
dependencies:
  # google_sign_in: ^6.1.5
  # flutter_facebook_auth: ^6.0.3
```

Then uncomment the code in:
- `lib/services/auth_service.dart` (Google and Facebook sign-in methods)
- Re-implement the OAuth flow with backend API integration

### 8. Security Considerations

1. **HTTPS in Production**: Always use HTTPS for OAuth redirects
2. **Token Validation**: Backend should validate Google/Facebook tokens with their APIs
3. **Rate Limiting**: Add rate limiting to login endpoints
4. **Secure Storage**: Store client secrets securely (environment variables)
5. **CORS**: Configure proper CORS headers for production domain

### 9. Troubleshooting

#### Login buttons not appearing:
- Check if admin panel settings are saved
- Test API: http://localhost/api/settings/app-config.php
- Check Flutter console for "App Config Data" log

#### API returns 403 Forbidden:
- Login is disabled in admin panel
- Enable it in advanced settings

#### Database errors:
- Ensure migration was run successfully
- Check if `app_users` table exists:
  ```sql
  USE news_website;
  SHOW TABLES LIKE 'app_users';
  ```

### 10. Admin Panel Access

- **URL**: http://localhost/admin/advanced-settings.php
- **Alternative**: http://192.168.1.3/admin/advanced-settings.php (from mobile device)
- **Features**:
  - Toggle Google/Facebook login on/off
  - Configure OAuth credentials
  - OTP settings
  - Email (SMTP) settings
  - Social media links

### 11. Next Steps

1. **Test with Real Credentials**:
   - Add real Google Client ID/Secret in admin panel
   - Add real Facebook App ID/Secret in admin panel
   - Test login flow on device

2. **Re-enable Social Login Packages**:
   - Uncomment packages in pubspec.yaml
   - Run `flutter pub get`
   - Uncomment auth_service.dart methods
   - Integrate with backend API calls

3. **Handle User Data Sync**:
   - After successful login, sync user data
   - Store bookmarks, likes, preferences in backend
   - Implement user profile management

4. **Testing**:
   - Test on both Android and iOS
   - Test with real OAuth credentials
   - Test enable/disable from admin panel
   - Test user creation and login flow

## Summary

✅ **Backend**: API endpoints created and ready
✅ **Database**: Tables created with social login support
✅ **Admin Panel**: Social login settings available
✅ **Flutter App**: Configuration provider implemented, dynamic button display
⏳ **Next**: Configure OAuth credentials and test with real accounts

The system is now **fully connected** and controlled by the admin panel. Enable/disable social logins from:
**http://localhost/admin/advanced-settings.php**
