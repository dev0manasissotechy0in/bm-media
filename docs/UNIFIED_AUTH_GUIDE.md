# Unified Authentication System - Web & Mobile App

## Overview
This system provides unified user authentication that works seamlessly across both the website and mobile app using the same credentials and database.

## Database Setup

1. Run the SQL file to create the sessions table:
```sql
-- Run this in your database
SOURCE database/user_sessions_table.sql;
```

Or execute directly:
```sql
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `device_info` VARCHAR(255),
  `ip_address` VARCHAR(45),
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_token` (`token`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## API Endpoints

### 1. Register
**Endpoint:** `POST /api/auth/register.php`

**Request Body:**
```json
{
  "email": "user@example.com",
  "phone": "+1234567890",
  "password": "password123",
  "full_name": "John Doe"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Registration successful",
  "token": "abc123...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "phone": "+1234567890",
    "full_name": "John Doe",
    "profile_photo": null,
    "auth_provider": "email",
    "email_verified": false,
    "phone_verified": false
  }
}
```

### 2. Login
**Endpoint:** `POST /api/auth/login.php`

**Request Body:**
```json
{
  "identifier": "user@example.com",
  "password": "password123"
}
```
*Note: `identifier` can be either email or phone number*

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "abc123...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "phone": "+1234567890",
    "full_name": "John Doe",
    "profile_photo": "https://brackoddmedia.com/uploads/users/profile.jpg",
    "auth_provider": "email",
    "email_verified": true,
    "phone_verified": false
  }
}
```

### 3. Get Profile
**Endpoint:** `GET /api/auth/profile.php`

**Headers:**
```
Authorization: Bearer {token}
```

Or use query parameter:
```
GET /api/auth/profile.php?token={token}
```

**Response:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "email": "user@example.com",
    "phone": "+1234567890",
    "full_name": "John Doe",
    "profile_photo": "https://brackoddmedia.com/uploads/users/profile.jpg",
    "auth_provider": "email",
    "email_verified": true,
    "phone_verified": false,
    "last_login": "2025-12-05 10:30:00",
    "created_at": "2025-12-01 08:00:00"
  }
}
```

### 4. Logout
**Endpoint:** `POST /api/auth/logout.php`

**Headers:**
```
Authorization: Bearer {token}
```

Or in request body:
```json
{
  "token": "abc123..."
}
```

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

## Implementation Guide

### For Flutter App

#### 1. Install Dependencies
Add to `pubspec.yaml`:
```yaml
dependencies:
  http: ^1.1.0
  shared_preferences: ^2.2.2
```

#### 2. Create Auth Service
```dart
class AuthService {
  static const String baseUrl = 'https://brackoddmedia.com/api/auth';
  
  Future<Map<String, dynamic>> register({
    required String email,
    String? phone,
    required String password,
    required String fullName,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/register.php'),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'email': email,
        'phone': phone,
        'password': password,
        'full_name': fullName,
      }),
    );
    return json.decode(response.body);
  }
  
  Future<Map<String, dynamic>> login({
    required String identifier,
    required String password,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/login.php'),
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'identifier': identifier,
        'password': password,
      }),
    );
    return json.decode(response.body);
  }
  
  Future<Map<String, dynamic>> getProfile(String token) async {
    final response = await http.get(
      Uri.parse('$baseUrl/profile.php'),
      headers: {'Authorization': 'Bearer $token'},
    );
    return json.decode(response.body);
  }
  
  Future<void> logout(String token) async {
    await http.post(
      Uri.parse('$baseUrl/logout.php'),
      headers: {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      },
    );
  }
}
```

#### 3. Store Token Locally
```dart
class TokenManager {
  static const String _tokenKey = 'auth_token';
  
  Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }
  
  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }
  
  Future<void> removeToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }
}
```

### For Website

#### Update login.php
```php
<?php
session_start();
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier'];
    $password = $_POST['password'];
    
    // Call API
    $ch = curl_init(BASE_URL . '/api/auth/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'identifier' => $identifier,
        'password' => $password
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    
    if ($result['success']) {
        $_SESSION['user_token'] = $result['token'];
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['user'] = $result['user'];
        header('Location: index.php');
    } else {
        $error = $result['message'];
    }
}
?>
```

## Features

✅ **Unified Database** - Same `users` table for both web and app
✅ **Token-Based Auth** - Secure session management with tokens
✅ **Email & Phone Login** - Support both authentication methods
✅ **Social Login Ready** - Already supports Google & Facebook
✅ **Cross-Platform** - Use same credentials on web and mobile
✅ **Secure** - Password hashing with bcrypt
✅ **Session Management** - 30-day token expiration
✅ **Profile API** - Get user data with token

## Security Notes

1. **HTTPS Required** - Always use HTTPS in production
2. **Token Storage** - Store tokens securely (SharedPreferences on mobile, sessions on web)
3. **Token Expiration** - Tokens expire after 30 days
4. **Password Policy** - Minimum 6 characters (increase in production)
5. **Rate Limiting** - Consider adding rate limiting to prevent brute force attacks

## Next Steps

1. Run the database migration to create `user_sessions` table
2. Test the APIs using Postman or similar tool
3. Integrate the APIs into your Flutter app
4. Update website login/register pages to use the new API
5. Test login on both platforms with same credentials
