# User Authentication & Podcast Notifications Setup

## ✅ COMPLETED CHANGES

### 1. Email Verification Bypass

**Status:** ✅ Already working - no changes needed!

The login system **does NOT require email verification** to login. Users can login immediately after registration.

**Login Flow:**
1. User registers → account created with `email_verified = 0`
2. User can login immediately (no verification check)
3. Email verification is optional for added security

**Tables Used:**
- `users` - Main user authentication
- `user_sessions` - Token-based sessions for app & website
- `user_otps` - OTP verification (optional)

---

## 2. Podcast Notifications System

### Database Structure

**Table:** `user_podcast_notifications`
```sql
CREATE TABLE user_podcast_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    podcast_id INT NOT NULL,
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_podcast (user_id, podcast_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (podcast_id) REFERENCES podcasts(id) ON DELETE CASCADE
);
```

**Status:** ✅ Table already exists in database

---

## API Endpoints

### 1. Toggle Podcast Notification

**Endpoint:** `POST /api/podcasts/notification.php`

**Authentication:** Supports both session (website) and Bearer token (mobile app)

**Headers:**
```
Content-Type: application/json
Authorization: Bearer {token}  // For mobile app
```

**Request Body:**
```json
{
    "podcast_id": 2,
    "enabled": true
}
```

**Response:**
```json
{
    "success": true,
    "enabled": true,
    "message": "Notifications enabled for this podcast"
}
```

**Status:** ✅ Updated to support both web and app authentication

---

### 2. Get All Notifications

**Endpoint:** `GET /api/podcasts/notifications-list.php`

**Authentication:** Supports both session (website) and Bearer token (mobile app)

**Headers:**
```
Authorization: Bearer {token}  // For mobile app
```

**Response:**
```json
{
    "success": true,
    "notifications": [
        {
            "podcast_id": 2,
            "title": "Series Check",
            "thumbnail": "http://192.168.1.3/uploads/podcasts/image.jpg",
            "is_series": true,
            "total_episodes": 2,
            "notifications_enabled": true
        },
        {
            "podcast_id": 1,
            "title": "Podcast Check",
            "thumbnail": null,
            "is_series": false,
            "total_episodes": 0,
            "notifications_enabled": false
        }
    ]
}
```

**Status:** ✅ Created new endpoint

---

## Authentication Methods

### For Website (Session-based)
User logs in through website, PHP session is created automatically.

### For Mobile App (Token-based)

**1. Login:**
```
POST /api/auth/login.php
Body: {
    "identifier": "user@example.com",  // or phone number
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "token": "abc123...xyz789",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "phone": "+1234567890",
        "full_name": "John Doe",
        "profile_photo": "http://192.168.1.3/uploads/users/photo.jpg",
        "email_verified": false,
        "phone_verified": false
    }
}
```

**2. Use Token in Subsequent Requests:**
```
Authorization: Bearer abc123...xyz789
```

**Token Validity:** 30 days

---

## Usage Examples

### Website Implementation

```javascript
// Toggle notification
async function togglePodcastNotification(podcastId, enabled) {
    const response = await fetch('/api/podcasts/notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            podcast_id: podcastId,
            enabled: enabled
        })
    });
    
    const data = await response.json();
    return data;
}

// Get notifications list
async function getNotificationsList() {
    const response = await fetch('/api/podcasts/notifications-list.php');
    const data = await response.json();
    return data.notifications;
}
```

### Mobile App Implementation (Flutter/React Native)

```dart
// Login
final response = await http.post(
  Uri.parse('http://192.168.1.3/api/auth/login.php'),
  headers: {'Content-Type': 'application/json'},
  body: jsonEncode({
    'identifier': 'user@example.com',
    'password': 'password123'
  })
);

final data = jsonDecode(response.body);
final token = data['token'];  // Save this token

// Toggle notification with token
final notifResponse = await http.post(
  Uri.parse('http://192.168.1.3/api/podcasts/notification.php'),
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Bearer $token'
  },
  body: jsonEncode({
    'podcast_id': 2,
    'enabled': true
  })
);

// Get notifications list
final listResponse = await http.get(
  Uri.parse('http://192.168.1.3/api/podcasts/notifications-list.php'),
  headers: {
    'Authorization': 'Bearer $token'
  }
);
```

---

## Testing Instructions

### 1. Test Login (No Email Verification Required)

**Using cURL:**
```bash
curl -X POST http://192.168.1.3/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"identifier":"user@example.com","password":"password123"}'
```

**Expected:** Should login successfully even if email_verified = 0

---

### 2. Test Toggle Notification

**Using cURL:**
```bash
# Get token from login first, then:
curl -X POST http://192.168.1.3/api/podcasts/notification.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{"podcast_id":2,"enabled":true}'
```

**Check Database:**
```sql
SELECT * FROM user_podcast_notifications WHERE user_id = 1;
```

---

### 3. Test Get Notifications List

**Using cURL:**
```bash
curl -X GET http://192.168.1.3/api/podcasts/notifications-list.php \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Database Queries for Testing

```sql
-- Check if user can login without verification
SELECT id, email, email_verified, status FROM users WHERE email = 'user@example.com';

-- Check user sessions/tokens
SELECT user_id, token, expires_at FROM user_sessions WHERE user_id = 1;

-- Check podcast notifications
SELECT 
    u.full_name,
    p.title as podcast_title,
    upn.enabled,
    upn.created_at
FROM user_podcast_notifications upn
JOIN users u ON upn.user_id = u.id
JOIN podcasts p ON upn.podcast_id = p.id;

-- Enable notification for testing
INSERT INTO user_podcast_notifications (user_id, podcast_id, enabled)
VALUES (1, 2, 1)
ON DUPLICATE KEY UPDATE enabled = 1;
```

---

## Summary

✅ **Email Verification:** Bypassed - users can login immediately after registration
✅ **Token-Based Auth:** Works for both app and website
✅ **Podcast Notifications:** Fully implemented with 2 API endpoints
✅ **Database:** All tables exist and are properly structured
✅ **Cross-Platform:** Same database (`users` table) for both app and website

## What You Need to Do

**For Mobile App Developer:**
1. Use `/api/auth/login.php` for login → save the token
2. Send token in Authorization header for all authenticated requests
3. Use `/api/podcasts/notification.php` to toggle notifications
4. Use `/api/podcasts/notifications-list.php` to display notification settings

**For Website:**
- Everything already works with PHP sessions
- No changes needed

**For Testing:**
- Create a test user account
- Login to get a token
- Test the notification endpoints
- Verify data in database
