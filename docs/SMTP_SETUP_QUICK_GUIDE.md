# Quick SMTP Setup Guide - Gmail

## Your Current Issue
✅ OTP system works (OTP is in database)  
❌ Email not sending (SMTP not configured)

## Solution: Configure Gmail SMTP

### Step 1: Get Gmail App Password

1. **Go to Google Account:** https://myaccount.google.com/
2. **Click "Security"** in left sidebar
3. **Enable 2-Step Verification** (if not already enabled)
4. **Go to App Passwords:** https://myaccount.google.com/apppasswords
5. **Select:**
   - App: Mail
   - Device: Other (Custom name) → Type: "News Website"
6. **Click Generate**
7. **Copy the 16-character password** (e.g., `abcd efgh ijkl mnop`)

### Step 2: Configure in Admin Panel

1. **Open:** http://localhost/admin/smtp-multi-config.php
2. **Click "Auth SMTP" tab**
3. **Fill in the Auth SMTP settings:**

```
SMTP Host: smtp.gmail.com
SMTP Port: 587
SMTP Username: YOUR_GMAIL@gmail.com
SMTP Password: [paste app password here - no spaces]
SMTP Encryption: TLS (select from dropdown)
From Email: YOUR_GMAIL@gmail.com
From Name: Your Website Name
```

5. **Click "Save Settings"**

### Step 3: Test Email

**Option A: Use test script**
```
http://localhost/api/auth/test-otp-send.php?email=YOUR_EMAIL@gmail.com
```

**Option B: Use Flutter app**
1. Open app → Login with OTP
2. Enter email
3. Click "Send OTP"
4. Check your email inbox

## Alternative: Use Existing OTP (Temporary Workaround)

**You already have an active OTP!**

1. **Get your OTP:**
```
http://localhost/api/auth/get-otp-debug.php?email=manas8teen@gmail.com
```

2. **Response will show:**
```json
{
  "success": true,
  "otp": "123456",
  "expires_at": "2025-12-13 13:09:55"
}
```

3. **Use this OTP in your app** to login without waiting for email

⚠️ **This is ONLY for testing. Remove get-otp-debug.php in production!**

## Other SMTP Providers

### Mailtrap (Testing)
```
Host: smtp.mailtrap.io
Port: 587
Username: [from mailtrap.io]
Password: [from mailtrap.io]
Encryption: TLS
```

### SendGrid
```
Host: smtp.sendgrid.net
Port: 587
Username: apikey
Password: [your SendGrid API key]
Encryption: TLS
```

### Mailgun
```
Host: smtp.mailgun.org
Port: 587
Username: [from mailgun]
Password: [from mailgun]
Encryption: TLS
```

## Verify Configuration

After configuring SMTP, run:
```
http://localhost/api/auth/check-otp-status.php
```

Should show:
```json
"smtp_configured": true
```

## Common Gmail Issues

### "Less secure apps" error
- Solution: Use App Password (not regular password)
- Regular Gmail password won't work for SMTP

### "Username and Password not accepted"
- Check 2-Step Verification is enabled
- Generate new App Password
- Remove spaces from App Password when pasting

### "Could not authenticate"
- Verify email is correct
- Verify App Password is correct
- Try port 465 with SSL encryption instead

## Test Commands

**1. Check status:**
```
http://localhost/api/auth/check-otp-status.php
```

**2. Test sending:**
```
http://localhost/api/auth/test-otp-send.php?email=YOUR_EMAIL
```

**3. Get existing OTP (debug):**
```
http://localhost/api/auth/get-otp-debug.php?email=YOUR_EMAIL
```

## Current Active OTP

Based on your database, you have:
- Email: manas8teen@gmail.com
- OTP: Check via get-otp-debug.php
- Expires: 2025-12-13 13:09:55

You can use this OTP right now in your app!

## Quick Test Flow

1. **Without Email (Fast):**
   - Get OTP: `http://localhost/api/auth/get-otp-debug.php?email=manas8teen@gmail.com`
   - Copy OTP
   - Paste in app
   - Verify & Login ✅

2. **With Email (After SMTP Setup):**
   - Configure Gmail SMTP
   - Request OTP in app
   - Check email
   - Enter OTP
   - Verify & Login ✅
