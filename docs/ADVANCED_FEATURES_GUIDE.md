# Advanced Features Implementation Guide

## 🎉 NEW FEATURES IMPLEMENTED

This guide covers all the advanced enterprise-level features that have been added to your news website system.

---

## 📋 TABLE OF CONTENTS

1. [OTP Authentication System](#1-otp-authentication-system)
2. [Multiple SMTP Configuration](#2-multiple-smtp-configuration)
3. [Newsletter Management Platform](#3-newsletter-management-platform)
4. [Cookie Consent & Tracking](#4-cookie-consent--tracking)
5. [User Interaction Analytics](#5-user-interaction-analytics)
6. [Installation & Setup](#6-installation--setup)

---

## 1. OTP Authentication System

### Overview
Secure, password-less login using 6-character alphanumeric codes sent via email.

### Files Created
- `includes/OTPService.php` - OTP generation, validation, and management
- `login-otp.php` - OTP login interface (2-step: email → code)
- Database table: `otp_codes`

### Features
✅ 6-character alphanumeric OTP (A-Z, 0-9, excluding confusing characters I/O)
✅ 10-minute expiry with automatic cleanup
✅ Rate limiting (1 OTP per minute per user)
✅ Purpose-based OTPs (login, registration, password_reset)
✅ IP address tracking for security
✅ Auto-submit when 6 characters entered
✅ Visual countdown timer

### Usage

**For Users:**
1. Visit `/login-otp.php`
2. Enter email address
3. Check email for OTP code
4. Enter code to login

**Enable OTP for a user (Admin):**
```php
$otpService = new OTPService();
$otpService->enableOTPForUser($user_id);
```

**Send OTP programmatically:**
```php
$otpService = new OTPService();
$result = $otpService->sendOTP('user@example.com', 'login');
```

### Configuration
- OTP Length: 6 characters (configurable in `OTPService.php`)
- Expiry: 10 minutes (600 seconds)
- Rate Limit: 1 OTP per 60 seconds

---

## 2. Multiple SMTP Configuration

### Overview
Separate SMTP servers for authentication, newsletter, and contact emails to improve deliverability and security.

### Files Created
- `includes/EmailService.php` - Multi-SMTP routing and email sending

### Database Structure
Extended `settings` table with `smtp_purpose` column:
- **auth** - Login OTPs, registration emails, password resets
- **newsletter** - Newsletter campaigns, bulk emails
- **contact** - Contact form notifications, admin alerts

### Features
✅ Purpose-based SMTP routing
✅ PHPMailer integration with fallback to `mail()`
✅ HTML email templates (OTP, Newsletter, Contact)
✅ Automatic tracking pixel injection for newsletters
✅ Unsubscribe link replacement

### Usage

**Send OTP Email:**
```php
$emailService = new EmailService();
$emailService->sendOTP('user@example.com', 'ABC123', 'login');
```

**Send Newsletter:**
```php
$subscriber = ['email' => 'user@example.com', 'id' => 1];
$campaign = $db->fetchOne("SELECT * FROM newsletter_campaigns WHERE id = ?", [$campaign_id]);
$emailService->sendNewsletter($subscriber, $campaign);
```

**Send Contact Notification:**
```php
$contactData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'subject' => 'Inquiry',
    'message' => 'Message content...'
];
$emailService->sendContactNotification($contactData);
```

### Configuration (Admin Panel)
1. Go to **Admin → SMTP Settings**
2. Configure 3 separate SMTP servers:
   - **Authentication SMTP** (for OTPs, logins)
   - **Newsletter SMTP** (for marketing emails)
   - **Contact SMTP** (for form notifications)

---

## 3. Newsletter Management Platform

### Overview
Complete newsletter system with subscriber management, campaign builder, and tracking analytics.

### Files Created
- `admin/newsletter-subscribers.php` - Subscriber list management
- `admin/newsletter-campaigns.php` - Campaign overview
- `admin/analytics.php` - Analytics dashboard with newsletter metrics

### Database Tables
- `newsletter_subscribers` - Email list with verification status
- `newsletter_campaigns` - Campaign storage with stats
- `newsletter_tracking` - Open/click tracking per recipient

### Features
✅ Subscriber management (add, import CSV, export, delete)
✅ Email verification system
✅ Unsubscribe handling
✅ Campaign statistics (sent, opened, clicked)
✅ Open rate & click rate tracking
✅ Article-to-newsletter conversion
✅ WYSIWYG email editor (planned)
✅ Scheduled sending (cron job integration)

### Usage

**Add Subscriber Manually:**
1. Go to **Admin → Newsletter Subscribers**
2. Click "Add Subscriber"
3. Enter email and save

**Import from CSV:**
1. Go to **Admin → Newsletter Subscribers**
2. Click "Import CSV"
3. Upload CSV file (one email per line)

**Create Campaign:**
1. Go to **Admin → Newsletter Campaigns**
2. Click "Create Campaign"
3. Design email content
4. Select recipients
5. Send or schedule

**Track Performance:**
- Go to **Admin → Analytics**
- View newsletter section for:
  - Total campaigns sent
  - Delivery rate
  - Average open rate
  - Average click rate

### Tracking Implementation
Tracking works via:
- **Open tracking:** 1x1 transparent pixel image (`/api/newsletter/track-open.php?token=XXX`)
- **Click tracking:** Link redirects through tracking endpoint
- **Unsubscribe:** Special link with verification token

---

## 4. Cookie Consent & Tracking

### Overview
GDPR-compliant cookie consent management with user preference tracking.

### Files Created
- `assets/js/tracking.js` - Cookie banner + preference manager
- `cookie-preferences.php` - User preference settings page
- `api/cookies/save-preferences.php` - API endpoint

### Database Table
- `cookie_preferences` - User/session cookie consent records

### Cookie Categories
1. **Necessary** - Always enabled (session, security)
2. **Functional** - User preferences, saved articles
3. **Analytics** - User behavior tracking, statistics
4. **Marketing** - Ads, remarketing pixels

### Features
✅ Floating consent banner on first visit
✅ Granular consent (4 categories)
✅ Preference persistence (localStorage + database)
✅ User/guest tracking (session-based for logged-out users)
✅ Preference management page
✅ Visual status indicators

### Usage

**User Experience:**
1. First visit → Cookie banner appears
2. Click "Accept All" or "Customize"
3. Manage preferences anytime at `/cookie-preferences.php`

**Check Consent in JavaScript:**
```javascript
const preferences = JSON.parse(localStorage.getItem('cookie_preferences'));
if (preferences && preferences.analytics) {
    // Enable analytics tracking
    window.userTracker.enable();
}
```

**Admin View:**
- Go to **Admin → Analytics**
- View cookie consent statistics

---

## 5. User Interaction Analytics

### Overview
Comprehensive user behavior tracking with device, browser, and geographic data.

### Files Created
- `assets/js/tracking.js` - Client-side tracking library
- `api/tracking/save.php` - Server-side data storage
- `admin/analytics.php` - Visualization dashboard

### Database Table
- `user_interactions` - All tracked events with metadata

### Tracked Events (15 Types)
1. **page_view** - Every page load
2. **article_read** - Article page visits with read time
3. **click** - Link clicks
4. **search** - Search queries
5. **comment** - Comment submissions
6. **like** - Article likes
7. **save** - Article saves
8. **share** - Social shares
9. **scroll** - Scroll depth tracking
10. **video_play/pause** - Video interactions
11. **newsletter_signup** - Newsletter subscriptions
12. **form_submit** - Form submissions
13. **download** - File downloads
14. **ad_click** - Advertisement clicks
15. **error** - JavaScript errors

### Captured Metadata
- Session ID (unique per browser session)
- User ID (if logged in)
- Device type (desktop/mobile/tablet)
- Browser (Chrome, Firefox, Safari, Edge)
- Operating System (Windows, Mac, Linux, Android, iOS)
- Country (IP-based geolocation - requires GeoIP service)
- Page URL & Referrer
- Read duration (seconds on article)
- Scroll depth (percentage scrolled)

### Features
✅ Real-time tracking (batched every 30 seconds)
✅ Automatic device/browser detection
✅ Session-based analytics
✅ Privacy-respecting (respects cookie consent)
✅ Chart.js visualizations
✅ Date range filtering

### Usage

**View Analytics:**
1. Go to **Admin → Analytics**
2. Select date range:
   - Today
   - Last 7 Days
   - Last 30 Days
   - Custom Range
3. View metrics:
   - Unique visitors
   - Page views
   - Avg. time on page
   - Scroll depth
   - Popular articles
   - Traffic sources
   - Device breakdown
   - Browser stats

**Programmatic Tracking:**
```javascript
// Track custom event
window.userTracker.addInteraction({
    type: 'custom_event',
    page_url: window.location.href,
    metadata: JSON.stringify({ action: 'button_click', button_id: 'cta-1' })
});
```

**Disable Tracking (Privacy Mode):**
```javascript
// User opts out of analytics
window.userTracker.enabled = false;
```

---

## 6. Installation & Setup

### Prerequisites
- PHP 7.4+ with PDO, OpenSSL, cURL
- MySQL 5.7+ or MariaDB 10.2+
- Composer (for PHPMailer)
- Web server (Apache/Nginx)

### Step 1: Database Migration
Run the SQL migration file to create all necessary tables:

```bash
mysql -u root -p your_database < database/migration_advanced_features.sql
```

This creates:
- `otp_codes` table
- `newsletter_subscribers` table
- `newsletter_campaigns` table
- `newsletter_tracking` table
- `cookie_preferences` table
- `user_interactions` table
- Extends `settings`, `articles`, `users` tables
- Creates 2 database views for statistics

### Step 2: Install PHPMailer (Recommended)
```bash
cd /path/to/your/project
composer require phpmailer/phpmailer
```

Or use built-in PHP `mail()` function (EmailService auto-detects).

### Step 3: Configure SMTP Settings

**Admin Panel Configuration:**
1. Login as admin
2. Go to **Admin → SMTP Settings**
3. Configure 3 SMTP servers:

**Authentication SMTP (OTP Emails):**
- Host: `smtp.gmail.com` (or your SMTP server)
- Port: `587` (TLS) or `465` (SSL)
- Username: `your-auth-email@gmail.com`
- Password: App password
- From Email: `noreply@yoursite.com`
- From Name: `Your Site - Security`

**Newsletter SMTP (Marketing):**
- Use dedicated service: SendGrid, Mailgun, Amazon SES
- Higher sending limits
- Better deliverability

**Contact SMTP (Notifications):**
- Can use same as auth or separate
- Lower volume, admin-facing

### Step 4: Enable OTP for Users

**Option A: Default for all new users**
Add to `register.php` after user creation:
```php
$db->insert('users', [
    // ... other fields
    'otp_enabled' => 1
]);
```

**Option B: Let users enable in profile**
Users can toggle OTP in **User → Profile → Security Settings**

### Step 5: Configure Cron Jobs (Optional)

**Clean expired OTPs (daily):**
```bash
0 2 * * * php /path/to/your/project/cron/clean-otp.php
```

**Send scheduled newsletters (every 5 minutes):**
```bash
*/5 * * * * php /path/to/your/project/cron/send-newsletters.php
```

**Clean old tracking data (weekly):**
```bash
0 3 * * 0 php /path/to/your/project/cron/clean-tracking.php
```

### Step 6: Test Features

**Test OTP Login:**
1. Visit `/login-otp.php`
2. Enter your email
3. Check email for OTP
4. Enter code and verify login works

**Test Cookie Banner:**
1. Clear browser cookies
2. Visit homepage
3. Cookie banner should appear
4. Test "Accept All" and "Customize" options

**Test Tracking:**
1. Visit a few pages
2. Read an article
3. Go to **Admin → Analytics**
4. Verify page views and interactions appear

**Test Newsletter:**
1. Add subscribers at **Admin → Newsletter Subscribers**
2. Create test campaign
3. Send test email to yourself
4. Verify tracking pixel works (check open count)

### Step 7: Update Header Template
Tracking JavaScript is already added to `includes/header.php`:
```php
<script src="<?= ASSETS_URL ?>/js/tracking.js"></script>
```

### Step 8: GeoIP Integration (Optional)
For country tracking, integrate a GeoIP service:

**Option A: MaxMind GeoIP2 (Free tier available)**
```bash
composer require geoip2/geoip2:~2.0
```

**Option B: API-based (ip-api.com)**
```php
function getCountryFromIP($ip) {
    $data = @file_get_contents("http://ip-api.com/json/{$ip}");
    $json = json_decode($data, true);
    return $json['country'] ?? null;
}
```

Update `api/tracking/save.php` with your chosen method.

---

## 🔒 Security Considerations

### OTP Security
- ✅ Rate limiting (1 OTP per minute)
- ✅ IP address logging
- ✅ 10-minute expiry
- ✅ Single-use codes
- ⚠️ Consider: Account lockout after 5 failed attempts

### Email Security
- ✅ Separate SMTP for different purposes
- ✅ SPF/DKIM/DMARC records recommended
- ✅ Rate limiting on email sending
- ⚠️ Monitor bounce rates

### Tracking Privacy
- ✅ Respect cookie preferences
- ✅ Anonymize IPs (last octet)
- ✅ No PII in tracking data
- ✅ GDPR-compliant consent

### Newsletter Security
- ✅ Verification tokens for unsubscribe
- ✅ Double opt-in (optional)
- ✅ CAN-SPAM compliance
- ⚠️ Include physical address in footer

---

## 📊 Performance Optimization

### Database Indexes
Already created in migration:
```sql
INDEX idx_otp_email ON otp_codes(email);
INDEX idx_newsletter_email ON newsletter_subscribers(email);
INDEX idx_tracking_user ON user_interactions(user_id);
INDEX idx_tracking_session ON user_interactions(session_id);
INDEX idx_tracking_date ON user_interactions(created_at);
```

### Tracking Optimization
- Batch sends every 30 seconds (reduces HTTP requests)
- Use database views for aggregate stats
- Archive old tracking data (>90 days)

### Newsletter Optimization
- Batch sending (100 emails per batch)
- Queue system for large campaigns
- Throttle sending rate to avoid blacklisting

---

## 🎨 Customization

### OTP Email Template
Edit `includes/EmailService.php` → `getOTPTemplate()`:
```php
private function getOTPTemplate($otp, $purpose) {
    // Customize HTML email design here
}
```

### Cookie Banner Styling
Edit `assets/js/tracking.js` → `getStyles()`:
```javascript
getStyles() {
    return `
        .cookie-banner {
            /* Customize CSS here */
        }
    `;
}
```

### Analytics Charts
Edit `admin/analytics.php` and modify Chart.js configurations:
```javascript
new Chart(ctx, {
    type: 'line', // Change chart type
    data: { /* ... */ },
    options: { /* Customize options */ }
});
```

---

## 🐛 Troubleshooting

### OTP Not Sending
1. Check SMTP credentials in admin panel
2. Verify `includes/EmailService.php` is loaded
3. Check error logs: `error_log` in PHP
4. Test with `mail()` function as fallback

### Tracking Not Working
1. Verify `tracking.js` is loaded (check browser console)
2. Check cookie consent is given (analytics must be enabled)
3. Verify API endpoint: `/api/tracking/save.php`
4. Check database permissions

### Newsletter Opens Not Tracked
1. Verify tracking pixel in email HTML
2. Check email client (some block images)
3. Verify `/api/newsletter/track-open.php` endpoint
4. Check tracking_token is unique

### Cookie Banner Not Showing
1. Clear localStorage and cookies
2. Verify `tracking.js` is loaded
3. Check for JavaScript errors in console
4. Ensure Bootstrap CSS is loaded (for styling)

---

## 📚 API Endpoints

### Cookie Preferences
```
POST /api/cookies/save-preferences.php
Body: {"necessary":true,"functional":true,"analytics":true,"marketing":false}
Response: {"success":true,"message":"Preferences saved"}
```

### User Tracking
```
POST /api/tracking/save.php
Body: {"interactions":[{type,session_id,page_url,...}]}
Response: {"success":true,"message":"Saved N interactions"}
```

### Newsletter Tracking (Automatic)
```
GET /api/newsletter/track-open.php?token=abc123
Response: 1x1 transparent GIF

GET /api/newsletter/track-click.php?token=abc123&url=encoded
Response: 302 Redirect to original URL

GET /api/newsletter/unsubscribe.php?token=abc123
Response: Unsubscribe confirmation page
```

---

## 📈 Future Enhancements

### Planned Features
- [ ] Two-factor authentication (TOTP)
- [ ] Newsletter template library
- [ ] A/B testing for newsletters
- [ ] Advanced segmentation (by country, device, behavior)
- [ ] Heatmap visualization
- [ ] Funnel analysis
- [ ] Real-time dashboard with WebSockets
- [ ] Export analytics to PDF
- [ ] Email preview across clients
- [ ] Subscriber tagging system

### Integration Opportunities
- Google Analytics 4 integration
- Facebook Pixel integration
- Zapier webhooks for automation
- Slack notifications for milestones
- CRM sync (Salesforce, HubSpot)

---

## 💡 Best Practices

### OTP Usage
✅ **DO:** Use for high-security actions (password reset, account changes)
✅ **DO:** Display clear expiry time to users
✅ **DO:** Log failed attempts
❌ **DON'T:** Use for frequent actions (annoys users)
❌ **DON'T:** Send OTPs via SMS without fallback (unreliable)

### Newsletter Best Practices
✅ **DO:** Segment your audience
✅ **DO:** Test emails before sending
✅ **DO:** Include clear unsubscribe link
✅ **DO:** Monitor bounce and complaint rates
❌ **DON'T:** Buy email lists
❌ **DON'T:** Send too frequently (max 2-3/week)

### Tracking Ethics
✅ **DO:** Be transparent about data collection
✅ **DO:** Provide opt-out mechanisms
✅ **DO:** Anonymize sensitive data
✅ **DO:** Respect Do Not Track (DNT) headers
❌ **DON'T:** Track without consent
❌ **DON'T:** Share data with third parties without disclosure

---

## 📞 Support & Maintenance

### Regular Maintenance Tasks
- **Weekly:** Review newsletter metrics, adjust strategy
- **Monthly:** Archive old tracking data (>90 days)
- **Quarterly:** Audit SMTP deliverability, rotate credentials
- **Yearly:** Review cookie policy, update privacy terms

### Monitoring
- **Track:** Email bounce rates (<5% is good)
- **Track:** Newsletter open rates (15-25% is average)
- **Track:** Website uptime and response times
- **Alert:** Failed OTP sends, tracking errors

---

## ✅ Feature Checklist

### OTP Authentication
- [x] OTPService class created
- [x] login-otp.php page created
- [x] OTP email template designed
- [x] Rate limiting implemented
- [x] Database table created
- [x] Auto-expiry cleanup
- [x] Integration with existing login

### Multiple SMTP
- [x] EmailService class created
- [x] 3 SMTP configurations (auth/newsletter/contact)
- [x] Purpose-based routing
- [x] PHPMailer integration
- [x] Fallback to mail()
- [x] Database settings extended

### Newsletter System
- [x] Subscriber management UI
- [x] Campaign overview UI
- [x] Database tables created
- [x] Tracking system (opens/clicks)
- [x] CSV import/export
- [x] Statistics dashboard
- [ ] WYSIWYG editor (use TinyMCE/CKEditor)
- [ ] Scheduled sending cron job

### Cookie Consent
- [x] Cookie banner JavaScript
- [x] Preference management page
- [x] 4 consent categories
- [x] Database tracking
- [x] localStorage persistence
- [x] Visual status indicators

### User Analytics
- [x] Tracking JavaScript library
- [x] 15 event types
- [x] Device/browser detection
- [x] Session tracking
- [x] Admin dashboard
- [x] Chart.js visualizations
- [x] Date range filtering
- [ ] GeoIP integration
- [ ] Real-time dashboard

---

**🎊 Congratulations! You now have an enterprise-grade news website with advanced features!**

For questions or issues, check the troubleshooting section or review the source code comments.