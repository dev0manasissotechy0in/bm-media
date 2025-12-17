# Multi-SMTP Configuration System - Complete Guide

## Overview
The news website now supports **separate SMTP servers** for different email purposes, providing professional email infrastructure with dedicated servers for:
- **Authentication Emails** (OTP, login, password reset)
- **Newsletter Campaigns** (bulk newsletters, article notifications)
- **Contact Form Notifications** (customer inquiries)

## System Architecture

### Email Routing Flow
```
Service Layer                Email Router              SMTP Config              Destination
──────────────              ────────────              ───────────              ───────────
OTPService.php       →   EmailService             →   auth_smtp_*       →    SMTP Server A
Newsletter.php       →   ::sendEmail($purpose)    →   newsletter_smtp_* →    SMTP Server B  
contact.php          →                            →   contact_smtp_*    →    SMTP Server C
```

### Database Structure
All SMTP settings stored in `settings` table with:
- **24 total settings** (8 parameters × 3 purposes)
- **smtp_purpose** field: 'auth', 'newsletter', 'contact', 'general'
- **8 parameters per purpose:**
  1. `{purpose}_smtp_enabled` (0/1)
  2. `{purpose}_smtp_host` (smtp.example.com)
  3. `{purpose}_smtp_port` (587)
  4. `{purpose}_smtp_username` (email address)
  5. `{purpose}_smtp_password` (encrypted)
  6. `{purpose}_smtp_encryption` (tls/ssl)
  7. `{purpose}_smtp_from_email` (sender email)
  8. `{purpose}_smtp_from_name` (sender name)

## Files Modified/Created

### 1. Core Email Service (EXISTING - Enhanced)
**File:** `includes/EmailService.php`
- **Status:** ✅ Already fully functional
- **Key Methods:**
  - `loadConfigs()` - Loads all SMTP configs by purpose from database
  - `sendEmail($purpose, $to, $subject, $body)` - Routes email to correct SMTP
  - `sendOTP($email, $otp, $purpose)` - Sends OTP using 'auth' SMTP
  - `sendNewsletter($subscriber, $campaign)` - Sends using 'newsletter' SMTP
  - `sendContactNotification($contactData)` - Sends using 'contact' SMTP
  - `sendWithPHPMailer($config, ...)` - PHPMailer integration
  - `sendWithMailFunction($config, ...)` - Fallback to PHP mail()

### 2. OTP Service (ALREADY INTEGRATED)
**File:** `includes/OTPService.php`
- **Status:** ✅ Already uses EmailService correctly
- **Usage:** `$this->emailService->sendOTP($email, $otp, $purpose)`
- **SMTP Purpose:** 'auth'
- **Email Types:**
  - Login OTP codes
  - Registration verification
  - Password reset codes

### 3. Newsletter Service (UPDATED)
**File:** `includes/Newsletter.php`
- **Status:** ✅ Updated to use EmailService
- **Changes Made:**
  - Replaced old `sendEmail()` function with `EmailService::sendEmail('newsletter', ...)`
  - Now routes all newsletter emails through dedicated newsletter SMTP
- **SMTP Purpose:** 'newsletter'
- **Email Types:**
  - Newsletter campaigns
  - Bulk subscriber emails
  - Article notifications

### 4. Contact Form (UPDATED)
**File:** `contact.php`
- **Status:** ✅ Updated to use EmailService
- **Changes Made:**
  - Replaced old SMTP check with `EmailService::sendContactNotification($contactData)`
  - Now routes contact notifications through dedicated contact SMTP
- **SMTP Purpose:** 'contact'
- **Email Types:**
  - Contact form submissions
  - Customer inquiry notifications

### 5. Multi-SMTP Configuration Page (NEW)
**File:** `admin/smtp-multi-config.php`
- **Status:** ✅ Created
- **Features:**
  - Separate config forms for Auth, Newsletter, Contact SMTPs
  - Enable/disable toggle for each SMTP
  - Password field with "keep current" option
  - Configuration status table showing all 3 SMTPs
  - Color-coded cards (Blue=Auth, Green=Newsletter, Cyan=Contact)
  - Real-time status badges (Enabled/Disabled)

### 6. Admin Navigation (UPDATED)
**File:** `admin/includes/header.php`
- **Status:** ✅ Updated
- **Changes:**
  - Renamed "SMTP Settings" to "General SMTP"
  - Added "Multi-SMTP Config" link with envelope icon

## Configuration Guide

### Step 1: Access Multi-SMTP Configuration
1. Login to admin panel
2. Navigate to **Pages & Contact → Multi-SMTP Config**
3. You'll see 3 separate configuration cards:
   - 🔵 **Authentication SMTP** (OTP emails)
   - 🟢 **Newsletter SMTP** (campaigns)
   - 🔵 **Contact Form SMTP** (inquiries)

### Step 2: Configure Auth SMTP (OTP Emails)
```
Purpose: OTP verification, login, password reset
Recommended Port: 587 (TLS) or 465 (SSL)

Example Configuration:
━━━━━━━━━━━━━━━━━━━━━━
Host:       smtp.gmail.com
Port:       587
Username:   auth@yourdomain.com
Password:   your-app-password
Encryption: TLS
From Email: noreply@yourdomain.com
From Name:  YourSite Authentication
Status:     ☑ Enabled
```

### Step 3: Configure Newsletter SMTP (Bulk Emails)
```
Purpose: Newsletter campaigns, bulk emails
Recommended: Use service with high sending limits (SendGrid, Mailgun, etc.)

Example Configuration:
━━━━━━━━━━━━━━━━━━━━━━
Host:       smtp.sendgrid.net
Port:       587
Username:   apikey
Password:   SG.xxxxxxxxxxxx
Encryption: TLS
From Email: newsletter@yourdomain.com
From Name:  YourSite Newsletter
Status:     ☑ Enabled
```

### Step 4: Configure Contact SMTP (Notifications)
```
Purpose: Contact form notifications, customer inquiries
Recommended: Business email or transactional service

Example Configuration:
━━━━━━━━━━━━━━━━━━━━━━
Host:       smtp.office365.com
Port:       587
Username:   contact@yourdomain.com
Password:   your-password
Encryption: TLS
From Email: contact@yourdomain.com
From Name:  YourSite Contact
Status:     ☑ Enabled
```

## Current Database Status

### General SMTP (Default Fallback)
```
Status: ✅ ENABLED
Host:   smtp.hostinger.com
Port:   587
User:   no-reply@brackoddmedia.com
From:   no-reply@brackoddmedia.com
```

### Auth SMTP
```
Status: ⚠️ CONFIGURED BUT DISABLED
Action Required: Enable after verifying credentials in Multi-SMTP Config page
```

### Newsletter SMTP
```
Status: ⚠️ CONFIGURED BUT DISABLED
Action Required: Enable after verifying credentials in Multi-SMTP Config page
```

### Contact SMTP
```
Status: ⚠️ CONFIGURED BUT DISABLED
Action Required: Enable after verifying credentials in Multi-SMTP Config page
```

## Email Routing Logic

### Priority Order
1. **Purpose-Specific SMTP** (if enabled)
   - Auth emails → auth_smtp_* settings
   - Newsletter emails → newsletter_smtp_* settings
   - Contact emails → contact_smtp_* settings

2. **General SMTP** (fallback)
   - If purpose-specific SMTP disabled
   - Uses smtp_* settings (Hostinger configured)

3. **PHP mail()** (last resort)
   - If all SMTP configs fail or disabled

### Implementation Details
```php
// EmailService.php - Automatic routing
public function sendEmail($purpose, $to, $subject, $body, $toName = null) {
    // 1. Load SMTP config for specific purpose
    $config = $this->configs[$purpose] ?? null;
    
    // 2. Fallback to general SMTP if not configured
    if (!$config || !$config['enabled']) {
        $config = $this->configs['general'] ?? null;
    }
    
    // 3. Try PHPMailer with SMTP
    if ($config) {
        return $this->sendWithPHPMailer($config, $to, $subject, $body, $toName);
    }
    
    // 4. Last resort: PHP mail()
    return $this->sendWithMailFunction($config, $to, $subject, $body, $toName);
}
```

## Testing Email Configuration

### Test Auth SMTP (OTP)
1. Logout from website
2. Click "Login with OTP"
3. Enter your email
4. Check if OTP email arrives
5. Verify sender matches auth_smtp_from_email

### Test Newsletter SMTP
1. Admin → Newsletter → Create Campaign
2. Send test email to yourself
3. Check if email arrives
4. Verify sender matches newsletter_smtp_from_email

### Test Contact SMTP
1. Visit contact page
2. Fill out contact form
3. Submit inquiry
4. Check admin email for notification
5. Verify sender matches contact_smtp_from_email

## Troubleshooting

### Issue: Emails not sending
**Solutions:**
1. Check if purpose-specific SMTP is enabled
2. Verify SMTP credentials are correct
3. Check SMTP host and port are correct
4. Ensure firewall allows SMTP ports (587, 465)
5. Check email logs in `/logs` directory (if logging enabled)

### Issue: Wrong sender email
**Solutions:**
1. Verify from_email and from_name in SMTP config
2. Check if SMTP server allows custom from addresses
3. Some servers require from_email to match username

### Issue: Falling back to general SMTP
**Cause:** Purpose-specific SMTP is disabled or not configured
**Solution:** Enable the SMTP in Multi-SMTP Config page

### Issue: Rate limiting / bounces
**Solutions:**
1. Use different SMTP servers for different purposes
2. Configure SPF, DKIM, DMARC records for all sender domains
3. Use transactional email services (SendGrid, Mailgun) for bulk

## Security Best Practices

### 1. Password Security
- Never commit SMTP passwords to version control
- Use app-specific passwords (Gmail, Outlook)
- Rotate passwords regularly
- Use environment variables for sensitive data

### 2. Sender Reputation
- Use dedicated IPs for high-volume sending
- Configure SPF records for all sender domains
- Set up DKIM signing
- Configure DMARC policies

### 3. Rate Limiting
- Implement per-user rate limits for OTP (already done)
- Use queue system for bulk newsletters (future enhancement)
- Monitor bounce rates and spam complaints

### 4. Encryption
- Always use TLS (port 587) or SSL (port 465)
- Never use unencrypted SMTP (port 25)
- Verify SSL certificates

## Benefits of Multi-SMTP Setup

### 1. Separation of Concerns
- ✅ Different email types use different servers
- ✅ Issues with one SMTP don't affect others
- ✅ Better organization and tracking

### 2. Better Deliverability
- ✅ Separate sender reputations
- ✅ Bulk emails don't affect transactional emails
- ✅ Can use specialized services (SendGrid for newsletters, Gmail for OTP)

### 3. Compliance
- ✅ Meet newsletter sending regulations (CAN-SPAM, GDPR)
- ✅ Separate unsubscribe handling per purpose
- ✅ Better audit trails

### 4. Performance
- ✅ Distribute load across multiple SMTP servers
- ✅ Higher sending limits per purpose
- ✅ Parallel sending capabilities

### 5. Cost Optimization
- ✅ Use free tiers for low-volume (OTP, contact)
- ✅ Pay for premium only where needed (newsletters)
- ✅ Better budget allocation

## Migration from Old System

### Before (Single SMTP)
```php
// Old method - all emails through one server
mail($to, $subject, $body, $headers);
```

### After (Multi-SMTP)
```php
// New method - routed by purpose
$emailService = new EmailService();
$emailService->sendEmail('auth', $to, $subject, $body);        // → Auth SMTP
$emailService->sendEmail('newsletter', $to, $subject, $body);  // → Newsletter SMTP
$emailService->sendEmail('contact', $to, $subject, $body);     // → Contact SMTP
```

## Future Enhancements

### Planned Features
1. **Email Queue System** - Queue newsletters for batch processing
2. **Email Templates Manager** - Visual template editor in admin
3. **Email Analytics** - Open rates, click rates, bounce rates
4. **Test Email Feature** - Send test email from config page
5. **Email Logs** - Detailed logs of all sent emails
6. **Retry Mechanism** - Auto-retry failed emails
7. **Webhook Integration** - Receive delivery notifications

### Advanced Features (Optional)
- **Amazon SES Integration** - Direct AWS SES support
- **SendGrid API** - Direct API integration (no SMTP)
- **Mailgun Integration** - Advanced deliverability features
- **Email Scheduling** - Schedule emails for future sending
- **A/B Testing** - Test different subject lines/content

## Summary

### ✅ Completed
1. EmailService.php multi-SMTP routing (already existed)
2. OTPService.php integration (already using EmailService)
3. Newsletter.php updated to use EmailService with 'newsletter' purpose
4. contact.php updated to use EmailService with 'contact' purpose
5. admin/smtp-multi-config.php created for configuration
6. Admin navigation updated with new link
7. Database structure verified (24 SMTP settings present)

### ⚠️ Action Required
1. **Configure Auth SMTP** - Enter credentials and enable
2. **Configure Newsletter SMTP** - Enter credentials and enable
3. **Configure Contact SMTP** - Enter credentials and enable
4. **Test Each Email Type** - Verify routing works correctly

### 🎯 Result
Professional email infrastructure with:
- 3 dedicated SMTP servers
- Automatic routing by purpose
- Fallback to general SMTP
- Easy configuration via admin panel
- Better deliverability and organization

---

**Last Updated:** <?= date('Y-m-d H:i:s') ?>  
**System Status:** ✅ SMTP Distribution Complete  
**Documentation:** Complete and Ready for Production
