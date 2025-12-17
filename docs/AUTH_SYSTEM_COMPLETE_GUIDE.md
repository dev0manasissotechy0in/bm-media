# 🔐 AUTHENTICATION SYSTEM - COMPLETE DEBUG GUIDE

## ✅ FIXES APPLIED

### 1. **Database Schema Fixed**
- ✅ Added `user_type` and `verified` columns to `otp_codes` table
- ✅ Enhanced `user_sessions` table
- ✅ Added proper indexes for performance
- ✅ Migration file created: `database/fix_auth_system.sql`

### 2. **API Endpoints Enhanced**
- ✅ **login.php** - Better error messages for user not found, invalid password
- ✅ **verify-otp.php** - Fixed user data format to match app expectations  
- ✅ **forgot-password.php** - NEW endpoint for password reset
- ✅ **reset-password.php** - NEW endpoint to verify OTP and reset password
- ✅ **send-otp.php** - Already working, tested

### 3. **Email System Updated**
- ✅ EmailHelper now supports password reset OTPs
- ✅ Dynamic email templates based on purpose (login/reset)

### 4. **Debug Tools Created**
- ✅ **api/debug-auth.php** - Comprehensive connection and auth test
- ✅ Tests database connection, tables, columns, and endpoints

---

## 🚀 SETUP INSTRUCTIONS

### Step 1: Run Database Migration
```bash
# Connect to your MySQL database
mysql -u root -p news_website

# Run the fix script
SOURCE c:/xampp/htdocs/database/fix_auth_system.sql;
```

OR using phpMyAdmin:
1. Open phpMyAdmin
2. Select `news_website` database
3. Go to SQL tab
4. Copy contents of `database/fix_auth_system.sql`
5. Execute

### Step 2: Test Database Connection
Visit: `http://192.168.1.3/api/debug-auth.php`

**Expected Result:**
```json
{
  "overall_status": "✅ ALL SYSTEMS OPERATIONAL",
  "summary": {
    "total_tests": 10,
    "passed": 10,
    "warnings": 0,
    "failed": 0
  }
}
```

### Step 3: Test Auth Endpoints

#### A. Test Send OTP (Email Login)
```bash
curl -X POST http://192.168.1.3/api/auth/send-otp.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "name": "Test User",
    "user_type": "user"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "OTP sent successfully to your email",
  "expires_in_minutes": 10
}
```

#### B. Test Verify OTP
```bash
curl -X POST http://192.168.1.3/api/auth/verify-otp.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "otp": "123456",
    "name": "Test User",
    "user_type": "user"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "full_name": "Test User",
    "email": "test@example.com",
    "profile_photo": null,
    "email_verified": true
  },
  "token": "base64_encoded_token"
}
```

#### C. Test Password Login
```bash
curl -X POST http://192.168.1.3/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

**Expected Responses:**

**Success:**
```json
{
  "success": true,
  "message": "Login successful",
  "token": "random_64_char_token",
  "user": {
    "id": 1,
    "email": "test@example.com",
    "full_name": "Test User",
    "profile_photo": null
  }
}
```

**User Not Found (404):**
```json
{
  "success": false,
  "error_code": "USER_NOT_FOUND",
  "message": "No account found with this email/phone. Please register first."
}
```

**Invalid Password (401):**
```json
{
  "success": false,
  "error_code": "INVALID_PASSWORD",
  "message": "Invalid password. Please try again."
}
```

**No Password Set (400):**
```json
{
  "success": false,
  "error_code": "NO_PASSWORD_SET",
  "message": "This account uses social login or email OTP. Please use the appropriate login method."
}
```

#### D. Test Forgot Password
```bash
curl -X POST http://192.168.1.3/api/auth/forgot-password.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Password reset code sent to your email",
  "expires_in_minutes": 10
}
```

#### E. Test Reset Password
```bash
curl -X POST http://192.168.1.3/api/auth/reset-password.php \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "otp": "123456",
    "new_password": "newpassword123"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Password reset successfully. You can now login with your new password."
}
```

---

## 📱 MOBILE APP INTEGRATION

### Update Base URL in App

Edit `news_app/lib/services/api_service.dart`:
```dart
class ApiService {
  // Change this to your server IP
  static const String baseUrl = 'http://192.168.1.3';
  // ...
}
```

### Test from Flutter App

1. **Email OTP Login Flow:**
   - Open app
   - Tap "Continue with Email OTP"
   - Enter email → OTP sent
   - Enter 6-digit OTP → Logged in

2. **Password Login Flow:**
   - Open app
   - Tap "Login with Password" (expansion tile)
   - Enter email & password → Logged in

3. **Forgot Password Flow:**
   - Open app
   - Tap "Forgot Password"
   - Enter email → OTP sent
   - Enter OTP + new password → Password reset

---

## 🐛 TROUBLESHOOTING

### Issue: "User Not Found"
**Solution:**
```sql
-- Check if user exists
SELECT * FROM users WHERE email = 'test@example.com';

-- Create test user if needed
INSERT INTO users (email, full_name, password, status, email_verified, auth_provider) 
VALUES (
  'test@example.com', 
  'Test User', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
  'active', 
  1, 
  'email'
);
```

### Issue: "OTP Not Sent"
**Causes:**
1. SMTP not configured
2. Email table doesn't exist
3. Network issue

**Solution:**
```sql
-- Check SMTP settings
SELECT * FROM settings WHERE setting_key LIKE '%smtp%';

-- Check if EmailHelper exists
-- File: includes/EmailHelper.php should exist

-- Test email manually (replace with your email)
-- File: test_email.php
```

### Issue: "Invalid or Expired OTP"
**Solution:**
```sql
-- Check recent OTPs
SELECT email, otp_code, purpose, expires_at, is_used, verified,
       CASE WHEN expires_at > NOW() THEN 'Valid' ELSE 'Expired' END as status
FROM otp_codes 
WHERE email = 'test@example.com'
ORDER BY created_at DESC 
LIMIT 5;

-- Manually verify OTP for testing
UPDATE otp_codes 
SET expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
WHERE email = 'test@example.com' AND is_used = 0;
```

### Issue: "Database Connection Failed"
**Solution:**
```php
// Check config/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'news_website');
define('DB_USER', 'root');
define('DB_PASS', '');

// Test MySQL connection
mysql -u root -p
USE news_website;
SHOW TABLES;
```

---

## 🔑 API ERROR CODES REFERENCE

| Error Code | HTTP Status | Meaning | Solution |
|------------|-------------|---------|----------|
| `USER_NOT_FOUND` | 404 | Email/phone not in database | User needs to register |
| `INVALID_PASSWORD` | 401 | Wrong password | User should try again or reset |
| `NO_PASSWORD_SET` | 400 | Account is social login only | Use email OTP or social login |
| `INVALID_OTP` | 400 | OTP doesn't match | Check OTP code |
| `EXPIRED_OTP` | 400 | OTP timeout passed | Request new OTP |

---

## ✨ FEATURES SUMMARY

### ✅ Implemented Features:
1. ✅ Email + Password Login
2. ✅ Email + OTP Login (No password needed)
3. ✅ OTP Auto-registration (creates user if not exists)
4. ✅ Forgot Password (OTP-based reset)
5. ✅ Session Management with tokens
6. ✅ FCM Token storage for push notifications
7. ✅ User existence check before operations
8. ✅ Detailed error messages
9. ✅ Security notices in emails
10. ✅ Auto-expiry for OTPs and sessions

### 🎯 Mobile App Features:
- ✅ Primary login method: Email OTP (passwordless)
- ✅ Secondary login: Email + Password
- ✅ Forgot password with OTP
- ✅ Auto-save user session
- ✅ Profile data caching

---

## 📞 SUPPORT

If issues persist:
1. Check `api/debug-auth.php` output
2. Check PHP error logs: `tail -f /xampp/apache/logs/error.log`
3. Check database exists: `SHOW DATABASES;`
4. Verify tables: `SHOW TABLES FROM news_website;`

---

**Last Updated:** December 13, 2025  
**Status:** ✅ All systems operational
