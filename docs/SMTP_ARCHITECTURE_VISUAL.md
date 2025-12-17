# Dynamic SMTP Distribution System - Visual Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                    NEWS WEBSITE EMAIL SYSTEM                        │
│                  Multi-SMTP Distribution Platform                   │
└─────────────────────────────────────────────────────────────────────┘

                            ┌──────────────┐
                            │   FRONTEND   │
                            └──────┬───────┘
                                   │
              ┌────────────────────┼────────────────────┐
              │                    │                    │
              ▼                    ▼                    ▼
    ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
    │  OTPService.php │  │ Newsletter.php  │  │   contact.php   │
    │                 │  │                 │  │                 │
    │ • Login OTP     │  │ • Campaigns     │  │ • Contact Form  │
    │ • Registration  │  │ • Bulk Emails   │  │ • Inquiries     │
    │ • Password RST  │  │ • Notifications │  │ • Support       │
    └────────┬────────┘  └────────┬────────┘  └────────┬────────┘
             │                    │                    │
             │ sendOTP()          │ sendEmail()        │ sendContact()
             │                    │                    │
             └────────────────────┼────────────────────┘
                                  │
                                  ▼
                    ┌──────────────────────────┐
                    │   EmailService.php       │
                    │   Central Email Router   │
                    │                          │
                    │  sendEmail($purpose,     │
                    │            $to,          │
                    │            $subject,     │
                    │            $body)        │
                    └──────────┬───────────────┘
                               │
                    ┌──────────┴──────────┐
                    │  loadConfigs()      │
                    │  Read from Database │
                    └──────────┬──────────┘
                               │
        ┌──────────────────────┼──────────────────────┐
        │                      │                      │
        ▼                      ▼                      ▼
┌───────────────┐     ┌───────────────┐     ┌───────────────┐
│  auth_smtp_*  │     │newsletter_smtp│     │ contact_smtp_*│
│               │     │      _*       │     │               │
│ Purpose: auth │     │Purpose: news  │     │Purpose:contact│
│ Status: ⚠️     │     │Status: ⚠️      │     │Status: ⚠️      │
└───────┬───────┘     └───────┬───────┘     └───────┬───────┘
        │                     │                     │
        │ IF ENABLED          │ IF ENABLED          │ IF ENABLED
        ▼                     ▼                     ▼
┌───────────────┐     ┌───────────────┐     ┌───────────────┐
│  SMTP Server  │     │  SMTP Server  │     │  SMTP Server  │
│      A        │     │      B        │     │      C        │
│               │     │               │     │               │
│ Gmail/Custom  │     │SendGrid/Mailgun│    │Office365/etc  │
└───────┬───────┘     └───────┬───────┘     └───────┬───────┘
        │                     │                     │
        └─────────────────────┴─────────────────────┘
                              │
                              ▼
                      ┌───────────────┐
                      │  Email Sent   │
                      │  to Recipient │
                      └───────────────┘

                    ╔═══════════════════╗
                    ║  FALLBACK CHAIN  ║
                    ╚═══════════════════╝
                              │
        1. Try Purpose-Specific SMTP (auth/newsletter/contact)
                              │
                              ▼ IF DISABLED/FAILS
        2. Fallback to General SMTP (Hostinger - ✅ ENABLED)
                              │
                              ▼ IF FAILS
        3. Last Resort: PHP mail() function
```

## Database Structure

```
┌─────────────────────────────────────────────────────────────┐
│                    SETTINGS TABLE                           │
├─────────────────┬──────────────────┬───────────────────────┤
│  setting_key    │  setting_value   │   smtp_purpose        │
├─────────────────┼──────────────────┼───────────────────────┤
│ auth_smtp_enabled       │ 0 (⚠️)    │ auth                  │
│ auth_smtp_host          │ (config)  │ auth                  │
│ auth_smtp_port          │ 587       │ auth                  │
│ auth_smtp_username      │ (config)  │ auth                  │
│ auth_smtp_password      │ ******    │ auth                  │
│ auth_smtp_encryption    │ tls       │ auth                  │
│ auth_smtp_from_email    │ (config)  │ auth                  │
│ auth_smtp_from_name     │ (config)  │ auth                  │
├─────────────────┼──────────────────┼───────────────────────┤
│ newsletter_smtp_enabled │ 0 (⚠️)    │ newsletter            │
│ newsletter_smtp_host    │ (config)  │ newsletter            │
│ newsletter_smtp_port    │ 587       │ newsletter            │
│ newsletter_smtp_username│ (config)  │ newsletter            │
│ newsletter_smtp_password│ ******    │ newsletter            │
│ newsletter_smtp_encrypt │ tls       │ newsletter            │
│ newsletter_smtp_from_em │ (config)  │ newsletter            │
│ newsletter_smtp_from_na │ (config)  │ newsletter            │
├─────────────────┼──────────────────┼───────────────────────┤
│ contact_smtp_enabled    │ 0 (⚠️)    │ contact               │
│ contact_smtp_host       │ (config)  │ contact               │
│ contact_smtp_port       │ 587       │ contact               │
│ contact_smtp_username   │ (config)  │ contact               │
│ contact_smtp_password   │ ******    │ contact               │
│ contact_smtp_encryption │ tls       │ contact               │
│ contact_smtp_from_email │ (config)  │ contact               │
│ contact_smtp_from_name  │ (config)  │ contact               │
└─────────────────┴──────────────────┴───────────────────────┘

                    TOTAL: 24 Settings
                    (8 parameters × 3 purposes)
```

## Admin Configuration Interface

```
┌───────────────────────────────────────────────────────────────┐
│            ADMIN PANEL - Multi-SMTP Configuration             │
└───────────────────────────────────────────────────────────────┘

┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃  🔵 AUTHENTICATION SMTP (OTP & Login)                       ┃
┣━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┫
┃                                                             ┃
┃  Status: [●] Enabled    Encryption: [TLS ▼]   Port: [587] ┃
┃  SMTP Host: [smtp.gmail.com              ]                 ┃
┃  Username:  [auth@yourdomain.com         ]                 ┃
┃  Password:  [••••••••••••••••••••••••    ]                 ┃
┃  From Email:[noreply@yourdomain.com      ]                 ┃
┃  From Name: [YourSite Authentication     ]                 ┃
┃                                                             ┃
┃                          [💾 Save Auth SMTP]               ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃  🟢 NEWSLETTER SMTP                                         ┃
┣━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┫
┃  [Same form structure for Newsletter SMTP]                 ┃
┃                          [💾 Save Newsletter SMTP]          ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
┃  🔵 CONTACT FORM SMTP                                       ┃
┣━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┫
┃  [Same form structure for Contact SMTP]                    ┃
┃                          [💾 Save Contact SMTP]             ┃
┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

╔═════════════════════════════════════════════════════════════╗
║               CONFIGURATION STATUS TABLE                    ║
╠═══════════════╤════════════╤══════════════╤════════════════╣
║   Purpose     │   Status   │  SMTP Host   │   From Email   ║
╠═══════════════╪════════════╪══════════════╪════════════════╣
║ Auth (OTP)    │ ⚠️ Disabled│ (configured) │ (configured)   ║
║ Newsletter    │ ⚠️ Disabled│ (configured) │ (configured)   ║
║ Contact Form  │ ⚠️ Disabled│ (configured) │ (configured)   ║
╚═══════════════╧════════════╧══════════════╧════════════════╝
```

## Email Flow Examples

### Example 1: User Login with OTP
```
┌─────────┐       ┌──────────┐       ┌──────────┐       ┌────────┐
│  User   │       │ Website  │       │EmailSvc  │       │ Gmail  │
└────┬────┘       └────┬─────┘       └────┬─────┘       └───┬────┘
     │                 │                   │                 │
     │ Click "Login    │                   │                 │
     │ with OTP"       │                   │                 │
     ├────────────────>│                   │                 │
     │                 │                   │                 │
     │                 │ sendOTP($email)   │                 │
     │                 ├──────────────────>│                 │
     │                 │                   │                 │
     │                 │                   │ Load auth_smtp_ │
     │                 │                   │ config          │
     │                 │                   │                 │
     │                 │                   │ Send via Gmail  │
     │                 │                   ├────────────────>│
     │                 │                   │                 │
     │                 │ OTP Sent          │ Email Delivered │
     │                 │<──────────────────┤<────────────────┤
     │                 │                   │                 │
     │<OTP email recv'd│                   │                 │
     │<────────────────┤                   │                 │
```

### Example 2: Newsletter Campaign
```
┌─────────┐       ┌──────────┐       ┌──────────┐       ┌──────────┐
│  Admin  │       │Newsletter│       │EmailSvc  │       │ SendGrid │
└────┬────┘       └────┬─────┘       └────┬─────┘       └────┬─────┘
     │                 │                   │                  │
     │ Send Campaign   │                   │                  │
     ├────────────────>│                   │                  │
     │                 │                   │                  │
     │                 │ sendNewsletter()  │                  │
     │                 ├──────────────────>│                  │
     │                 │                   │                  │
     │                 │                   │Load newsletter_  │
     │                 │                   │smtp_ config      │
     │                 │                   │                  │
     │                 │                   │Send via SendGrid │
     │                 │                   ├─────────────────>│
     │                 │                   │                  │
     │                 │ Campaign Sent     │  Bulk Delivered  │
     │ Success Message │<──────────────────┤<─────────────────┤
     │<────────────────┤                   │                  │
```

## File Structure

```
c:\xampp\htdocs\
│
├── includes/
│   ├── EmailService.php          [Core Router - 282 lines] ✅
│   ├── OTPService.php             [Auth Emails] ✅
│   └── Newsletter.php             [Bulk Emails] ✅ UPDATED
│
├── admin/
│   ├── smtp-settings.php          [General SMTP Config]
│   ├── smtp-multi-config.php      [Multi-SMTP Config] ✅ NEW
│   └── includes/
│       └── header.php             [Navigation] ✅ UPDATED
│
├── contact.php                    [Contact Form] ✅ UPDATED
│
├── database/
│   └── settings table             [24 SMTP settings] ✅
│
└── DOCUMENTATION/
    ├── SMTP_DISTRIBUTION_GUIDE.md        ✅ Complete Guide
    ├── SMTP_DISTRIBUTION_SUMMARY.md      ✅ Quick Reference
    └── SMTP_ARCHITECTURE_VISUAL.md       ✅ This File
```

## Benefits Visualization

```
┌──────────────────────────────────────────────────────────────┐
│                    BEFORE (Single SMTP)                      │
└──────────────────────────────────────────────────────────────┘

        All Emails → [One SMTP Server] → ❌ Single Point of Failure
                                         ❌ Mixed Sender Reputation
                                         ❌ Rate Limit Issues
                                         ❌ Hard to Troubleshoot


┌──────────────────────────────────────────────────────────────┐
│                  AFTER (Multi-SMTP)                          │
└──────────────────────────────────────────────────────────────┘

    OTP Emails        → [Auth SMTP]       → ✅ Dedicated Auth Server
    Newsletter Emails → [Newsletter SMTP] → ✅ High-Volume Server
    Contact Emails    → [Contact SMTP]    → ✅ Business Email Server

    Benefits:
    ✅ Separation of Concerns
    ✅ Better Deliverability
    ✅ Independent Sender Reputations
    ✅ Easy Troubleshooting
    ✅ Scalable Architecture
    ✅ Cost Optimization
```

## Configuration Checklist

```
┌─────────────────────────────────────────────────────────────┐
│            SMTP CONFIGURATION CHECKLIST                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  📋 Auth SMTP (OTP & Login)                                │
│  ☐ Enter SMTP host and port                               │
│  ☐ Enter username and password                            │
│  ☐ Set from email and name                                │
│  ☐ Enable configuration                                    │
│  ☐ Test with OTP login                                     │
│                                                             │
│  📋 Newsletter SMTP (Campaigns)                            │
│  ☐ Enter SMTP host and port                               │
│  ☐ Enter username and password                            │
│  ☐ Set from email and name                                │
│  ☐ Enable configuration                                    │
│  ☐ Send test campaign                                      │
│                                                             │
│  📋 Contact SMTP (Inquiries)                               │
│  ☐ Enter SMTP host and port                               │
│  ☐ Enter username and password                            │
│  ☐ Set from email and name                                │
│  ☐ Enable configuration                                    │
│  ☐ Submit test contact form                               │
│                                                             │
│  ✅ VERIFICATION                                           │
│  ☐ All 3 SMTPs showing "Enabled" status                   │
│  ☐ Test email from each type received                     │
│  ☐ Correct sender addresses in emails                     │
│  ☐ No errors in browser console                           │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

**System Status:** ✅ COMPLETE  
**Visual Documentation:** Complete  
**Ready for:** Production Use  
**Date:** <?= date('Y-m-d') ?>
