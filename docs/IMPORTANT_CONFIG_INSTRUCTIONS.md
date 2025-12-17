# IMPORTANT: Configuration Instructions

## SITE_URL Configuration

**CRITICAL:** When configuring `SITE_URL` in `config/config.php`, DO NOT include trailing slash:

### ✅ CORRECT Configuration:
```php
define('SITE_URL', 'https://brackoddmedia.com');
```

### ❌ WRONG Configuration:
```php
define('SITE_URL', 'https://brackoddmedia.com/');
define('SITE_URL', 'https://brackoddmedia.com/brackoddmedia.com');
```

## Why This Matters

The `BASE_URL` constant is set to `SITE_URL`:
```php
define('BASE_URL', SITE_URL);
```

All other URL constants are built from BASE_URL:
```php
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');
```

## Database Host Configuration

For **production** (hosted on brackoddmedia.com):
```php
define('DB_HOST', 'localhost');  // Keep as localhost - this is correct!
```

**Note:** `DB_HOST` should remain as `localhost` even in production because your PHP code runs on the same server as your MySQL database. The database connection is internal to the server.

## Clean URLs Enabled

The following pages now work WITHOUT .php extension:
- ✅ https://brackoddmedia.com/stories
- ✅ https://brackoddmedia.com/reels
- ✅ https://brackoddmedia.com/case-threads
- ✅ https://brackoddmedia.com/login
- ✅ https://brackoddmedia.com/contact
- ✅ https://brackoddmedia.com/page/crypto-badge

## URL Structure for Reels

Reels now use the format:
```
https://brackoddmedia.com/reel/{slug}/{id}
```

Example:
```
https://brackoddmedia.com/reel/breaking-news-reel/123
```

## Troubleshooting

If you see double domain in URLs:
1. Check `config/config.php` - ensure SITE_URL is `https://brackoddmedia.com` (no trailing slash)
2. Clear browser cache
3. Check .htaccess is properly configured
