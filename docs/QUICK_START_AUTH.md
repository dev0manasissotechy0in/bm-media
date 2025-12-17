# 🚀 QUICK START - AUTH SYSTEM

## 1️⃣ RUN DATABASE MIGRATION (REQUIRED!)
```bash
# Option A: Command line
mysql -u root -p news_website < c:/xampp/htdocs/database/fix_auth_system.sql

# Option B: phpMyAdmin
# Open http://localhost/phpmyadmin
# Select 'news_website' database
# Go to 'SQL' tab
# Copy paste contents of fix_auth_system.sql
# Click 'Go'
```

## 2️⃣ TEST DATABASE & API
Open in browser: `http://192.168.1.3/api/debug-auth.php`

**Should show:** ✅ ALL SYSTEMS OPERATIONAL

## 3️⃣ TEST AUTH UI
Open in browser: `http://192.168.1.3/test-auth.html`

Test all 5 flows:
- ✅ Send OTP
- ✅ Verify OTP  
- ✅ Password Login
- ✅ Forgot Password
- ✅ Reset Password

## 4️⃣ TEST FROM MOBILE APP

### Update App Config:
File: `news_app/lib/services/api_service.dart`
```dart
static const String baseUrl = 'http://192.168.1.3';
```

### Test Login Flow:
1. Open app
2. Tap "Continue with Email OTP"
3. Enter email → OTP sent
4. Check email for 6-digit code
5. Enter OTP → Logged in ✅

## 🐛 IF ISSUES:

### "Connection refused"
```bash
# Check XAMPP is running
# Check Apache is on port 80
# Check IP matches your computer's IP
ipconfig  # Windows
ifconfig  # Mac/Linux
```

### "User not found"
```sql
-- Create test user
INSERT INTO users (email, full_name, password, status, email_verified) 
VALUES ('test@example.com', 'Test User', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 1);
-- Password is: password
```

### "OTP not sent"
- Email system needs SMTP setup
- For testing, check OTP in database:
```sql
SELECT otp_code FROM otp_codes 
WHERE email = 'test@example.com' 
ORDER BY created_at DESC LIMIT 1;
```

## 📱 APP TESTING CREDENTIALS
```
Email: test@example.com
Password: password
```

## 🔗 USEFUL LINKS
- Debug Tool: http://192.168.1.3/api/debug-auth.php
- Test UI: http://192.168.1.3/test-auth.html
- Documentation: AUTH_SYSTEM_COMPLETE_GUIDE.md

## ✅ ALL FIXED FILES
1. ✅ api/auth/login.php - Better error handling
2. ✅ api/auth/verify-otp.php - Fixed user data format
3. ✅ api/auth/forgot-password.php - NEW
4. ✅ api/auth/reset-password.php - NEW
5. ✅ api/debug-auth.php - NEW debug tool
6. ✅ includes/EmailHelper.php - Support password reset
7. ✅ database/fix_auth_system.sql - NEW migration
8. ✅ test-auth.html - NEW test UI
9. ✅ database/migration_advanced_features.sql - Updated OTP table

## 📊 SUCCESS CRITERIA
- ✅ All tests in debug-auth.php pass
- ✅ Can send OTP via test-auth.html
- ✅ Can login with password
- ✅ Can reset password
- ✅ Mobile app can login with OTP
- ✅ Mobile app shows user profile after login

---
**Status:** Ready for testing!  
**Next:** Run Step 1 database migration then test!
