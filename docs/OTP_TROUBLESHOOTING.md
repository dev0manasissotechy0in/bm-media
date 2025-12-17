# OTP System Troubleshooting Guide

## Quick Tests

### 1. Check OTP System Status
**URL:** `http://localhost/api/auth/check-otp-status.php`

This will show:
- ✅ If otp_codes table exists
- ✅ Active OTP count
- ✅ Recent OTPs
- ✅ SMTP configuration status
- ✅ OTP settings

### 2. Test OTP Sending
**URL:** `http://localhost/api/auth/test-otp-send.php?email=YOUR_EMAIL@example.com`

This will:
- Generate a test OTP
- Save it to database
- Attempt to send email
- Show the OTP even if email fails

**Example:**
```
http://localhost/api/auth/test-otp-send.php?email=your@email.com
```

## Common Issues & Solutions

### Issue 1: Table doesn't exist
**Error:** `Table otp_codes does not exist`

**Solution:**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Select `news_website` database
3. Run SQL from: `database/migration_advanced_features.sql`

Or run directly:
```sql
CREATE TABLE IF NOT EXISTS otp_codes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    purpose ENUM('login', 'registration', 'password_reset') DEFAULT 'login',
    user_type ENUM('user', 'author', 'admin') DEFAULT 'user',
    expires_at DATETIME NOT NULL,
    is_used BOOLEAN DEFAULT 0,
    verified BOOLEAN DEFAULT 0,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_otp (otp_code),
    INDEX idx_expires (expires_at),
    INDEX idx_user_type (user_type)
);
```

### Issue 2: SMTP not configured
**Error:** `Failed to send OTP email. Please check your email configuration`

**Solution:**
1. Go to: `http://localhost/admin/smtp-multi-config.php`
2. Click **Auth SMTP** tab
3. Configure Auth Email SMTP:
   - SMTP Host: `smtp.gmail.com` (for Gmail)
   - SMTP Port: `587`
   - Username: `your-email@gmail.com`
   - Password: Your App Password (not regular password)
   - Encryption: `TLS`
   - From Email: `your-email@gmail.com`
   - From Name: `Your Site Name`
4. Click **Save Settings**

**Gmail Setup:**
1. Go to: https://myaccount.google.com/apppasswords
2. Generate App Password
3. Use that password in SMTP settings

### Issue 3: OTP not enabled
**Solution:**
1. Go to: `http://localhost/admin/settings.php`
2. Click **Security** tab
3. Check **Enable OTP Login**
4. Set OTP Expiry Minutes (default: 10)
5. Click **Save Settings**

**Note:** OTP enable/disable is in settings.php, but SMTP for emails is in smtp-multi-config.php

### Issue 4: Flutter app can't reach API
**Error:** `Network error. Please check your connection`

**Check:**
1. API Base URL in `lib/constants/api_constants.dart`:
   ```dart
   static const String baseUrl = 'http://192.168.1.3';
   ```
2. Make sure your device/emulator can reach this IP
3. Test in browser: `http://192.168.1.3/api/auth/check-otp-status.php`

**For Android Emulator:**
```dart
static const String baseUrl = 'http://10.0.2.2';
```

**For Real Device:**
- Use your computer's local IP: `http://192.168.x.x`
- Make sure device is on same Wi-Fi network

### Issue 5: Email not sending but OTP in database
**Workaround:**
1. Use test script to get OTP: `http://localhost/api/auth/test-otp-send.php?email=test@example.com`
2. Copy the OTP from the response
3. Use it in the app to verify

This proves the system works even if SMTP is not configured.

## Debug Mode (For Development Only)

Enable debug mode to see OTP in API response:

**In `config/config.php`:**
```php
define('DEBUG_MODE', true);
```

**Response will include:**
```json
{
  "success": true,
  "message": "OTP sent successfully",
  "debug": {
    "otp": "123456",
    "expires_at": "2025-12-13 13:45:00"
  }
}
```

⚠️ **Remove this in production!**

## Testing Flow

### 1. Test API Directly (Browser/Postman)
```
POST http://localhost/api/auth/send-otp.php
Content-Type: application/json

{
  "email": "test@example.com",
  "name": "Test User",
  "user_type": "user"
}
```

**Expected Success Response:**
```json
{
  "success": true,
  "message": "OTP sent successfully to your email",
  "expires_in_minutes": 10
}
```

### 2. Test Flutter App
1. Open email_otp_login screen
2. Enter email
3. Click "Send OTP"
4. Check console for debug logs:
   - `📧 Sending OTP to: ...`
   - `✅ OTP sent successfully` or `❌ OTP send failed`
5. If debug mode enabled, OTP will show in console

### 3. Verify OTP
1. Enter the 6-digit OTP
2. Click "Verify & Login"
3. Should login or register user

## Manual Database Check

**Check if OTP was created:**
```sql
SELECT * FROM otp_codes 
WHERE email = 'test@example.com' 
ORDER BY created_at DESC 
LIMIT 1;
```

**Check if OTP is still valid:**
```sql
SELECT * FROM otp_codes 
WHERE email = 'test@example.com' 
AND expires_at > NOW() 
AND is_used = 0
ORDER BY created_at DESC;
```

## Email Template Check

**Check if email template exists:**
```php
// File: email-templates/otp-login.php
```

If missing, create it or use default template.

## Common Error Messages

| Error | Cause | Solution |
|-------|-------|----------|
| Invalid email address | Email format wrong | Check email validation |
| Table doesn't exist | Migration not run | Run migration SQL |
| SMTP connection failed | Wrong SMTP settings | Check host, port, credentials |
| Failed to send email | Email helper issue | Check EmailHelper.php exists |
| OTP expired | Took too long | Request new OTP |
| Invalid OTP | Wrong code entered | Check OTP in database |

## Quick Fix Checklist

- [ ] Run `database/migration_advanced_features.sql`
- [ ] Configure Auth SMTP in admin/smtp-multi-config.php > Auth SMTP tab
- [ ] Enable OTP in admin/settings.php > Security tab
- [ ] Check base URL in Flutter app matches server IP
- [ ] Test with: `http://localhost/api/auth/test-otp-send.php?email=YOUR_EMAIL`
- [ ] Check PHP error logs: `C:\xampp\apache\logs\error.log`
- [ ] Check Flutter console for debug logs
- [ ] Verify device can reach server IP

## Support Files

**API Endpoints:**
- Send OTP: `/api/auth/send-otp.php`
- Verify OTP: `/api/auth/verify-otp.php`
- Check Status: `/api/auth/check-otp-status.php`
- Test Send: `/api/auth/test-otp-send.php`

**Flutter Files:**
- Service: `lib/services/email_auth_service.dart`
- Screen: `lib/screens/auth/email_otp_login.dart`
- Constants: `lib/constants/api_constants.dart`

**PHP Files:**
- Email Helper: `includes/EmailHelper.php`
- Settings: `includes/Settings.php`
- Database: `includes/Database.php`

## Still Not Working?

1. **Run test script:** `http://localhost/api/auth/test-otp-send.php?email=YOUR_EMAIL`
2. **Get OTP from response** (even if email fails)
3. **Use OTP in app** to verify system works
4. **Check error logs:** `C:\xampp\apache\logs\error.log`
5. **Enable debug mode** to see OTP in API response

The OTP will be saved in database even if email fails, so you can still test the complete flow!
