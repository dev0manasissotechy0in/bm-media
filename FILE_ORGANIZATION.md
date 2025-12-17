# Project File Organization Guide

## Directory Structure

```
/
├── admin/              # Admin panel files
├── api/                # API endpoints
├── assets/             # Static assets (CSS, JS, images)
├── auth/               # Authentication pages
├── author/             # Author section
├── config/             # Configuration files
├── database/           # Database migrations
├── docs/               # Documentation files (*.md)
├── email-templates/    # Email templates
├── includes/           # PHP includes and classes
├── news_app/           # Flutter mobile app
├── tests/              # All test files
│   ├── auth-tests/     # Authentication test files
│   ├── otp-tests/      # OTP system test files
│   └── smtp-tests/     # SMTP/Email test files
├── uploads/            # User uploaded files
├── user/               # User dashboard and pages
├── vendor/             # Composer dependencies
└── views/              # View templates
```

## Test Files Location

### SMTP Tests (`tests/smtp-tests/`)
- `test-email-send.php` - Test email sending with OTP
- `test-smtp-connection.php` - Test SMTP server connection
- `check-smtp-port.php` - Check SMTP port availability
- `fix-smtp-config.php` - SMTP configuration validator
- `quick-fix-smtp.php` - Quick SMTP configuration fix

### OTP Tests (`tests/otp-tests/`)
- `test-otp-connection.php` - OTP database connection test
- `test-otp-send.php` - OTP generation and sending test
- `check-otp-status.php` - OTP system status checker
- `get-otp-debug.php` - Retrieve OTP from database (dev only)

### Auth Tests (`tests/auth-tests/`)
- `quick-test.html` - Quick authentication test
- `test-auth.html` - Authentication flow test
- `verify-test.html` - Verification test
- `test-mysql.php` - MySQL connection test
- `verify-otp.php` - OTP verification test page

## Documentation (`docs/`)
All `.md` files have been moved to the docs folder

## Main Application Files (Root)
Production files remain in root:
- `index.php`, `article.php`, `category.php`
- `login.php`, `register.php`, `verify-otp.php`
- And other production files

## How to Access Test Files

### SMTP Tests:
- http://localhost/tests/smtp-tests/check-smtp-port.php
- http://localhost/tests/smtp-tests/test-email-send.php
- http://localhost/tests/smtp-tests/fix-smtp-config.php

### OTP Tests:
- http://localhost/tests/otp-tests/test-otp-connection.php
- http://localhost/tests/otp-tests/get-otp-debug.php?email=EMAIL

### Auth Tests:
- http://localhost/tests/auth-tests/test-auth.html
- http://localhost/tests/auth-tests/test-mysql.php

## Cleanup Complete
✅ All test files organized in subdirectories
✅ Documentation centralized in `/docs/`
✅ Production files remain accessible in root
✅ Test URLs updated to reflect new structure
