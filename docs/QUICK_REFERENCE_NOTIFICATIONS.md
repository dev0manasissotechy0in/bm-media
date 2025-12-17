# 🚀 Quick Reference - Podcast Notifications API

## Base URL
```
http://192.168.1.3
```

## 📱 API Endpoints Summary

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/api/auth/login.php` | POST | No | Login user, get token |
| `/api/podcasts/notification.php` | POST | Yes | Toggle notification on/off |
| `/api/podcasts/notifications-list.php` | GET | Yes | Get all notifications |

## 🔐 Authentication

### Option 1: Session (Website)
Login through website - automatic PHP session

### Option 2: Token (Mobile App)
```http
POST /api/auth/login.php
Content-Type: application/json

{
  "identifier": "user@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "token": "abc123xyz789...",
  "user": {...}
}
```

**Use Token:**
```http
Authorization: Bearer abc123xyz789...
```

## 📝 Quick Examples

### 1. Enable Notification
```bash
curl -X POST http://192.168.1.3/api/podcasts/notification.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"podcast_id": 2, "enabled": true}'
```

### 2. Disable Notification
```bash
curl -X POST http://192.168.1.3/api/podcasts/notification.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"podcast_id": 2, "enabled": false}'
```

### 3. Get All Notifications
```bash
curl -X GET http://192.168.1.3/api/podcasts/notifications-list.php \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## 🧪 Testing Page
Open in browser:
```
http://192.168.1.3/test-podcast-notifications.html
```

## ✅ What's Working

- ✅ Email verification **NOT required** for login
- ✅ Users can login immediately after registration
- ✅ Same `users` table for both app and website
- ✅ Token-based authentication for mobile app
- ✅ Session-based authentication for website
- ✅ Podcast notifications fully functional
- ✅ Database tables already exist

## 📊 Database Tables

### users
- Contains all user accounts
- `email_verified` field exists but not checked during login

### user_sessions
- Stores authentication tokens
- 30-day expiration

### user_podcast_notifications
- Stores notification preferences
- Links users to podcasts

## 🎯 For Mobile App Developers

**Step 1:** Login to get token
```dart
final response = await http.post(
  Uri.parse('http://192.168.1.3/api/auth/login.php'),
  body: jsonEncode({
    'identifier': 'user@example.com',
    'password': 'password123'
  })
);
String token = jsonDecode(response.body)['token'];
```

**Step 2:** Use token in all requests
```dart
headers: {
  'Authorization': 'Bearer $token'
}
```

**Step 3:** Toggle notifications
```dart
await http.post(
  Uri.parse('http://192.168.1.3/api/podcasts/notification.php'),
  headers: {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json'
  },
  body: jsonEncode({
    'podcast_id': 2,
    'enabled': true
  })
);
```

## 📞 Need Help?

1. Check `/PODCAST_NOTIFICATIONS_SETUP.md` for detailed documentation
2. Test using `/test-podcast-notifications.html`
3. Check database: `SELECT * FROM user_podcast_notifications`
