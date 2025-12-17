# Complete News Website - Setup & Installation Guide

## 🚀 Project Overview

A comprehensive, feature-rich news website built with **Core PHP + MySQL** including:

- ✅ Election Dashboard with live results
- ✅ Cricket Dashboard with live scores
- ✅ Market Dashboard with stock data
- ✅ User Account System (Save, Comment, Like, Download)
- ✅ Reporter Account System with validation
- ✅ Master Admin Panel with full control
- ✅ Dynamic SEO & Sitemap
- ✅ Ads Management (Google, Custom, Taboola, Government)
- ✅ Newsletter System
- ✅ Web Push Notifications
- ✅ Custom Pages System
- ✅ Multiple content types (Reels, Videos, Photos, Gallery)

---

## 📋 Prerequisites

- **PHP 7.4+** (8.0+ recommended)
- **MySQL 5.7+** or **MariaDB 10.3+**
- **Apache/Nginx** web server
- **Composer** (for push notification dependencies)
- **XAMPP/WAMP/LAMP** (for local development)

---

## 🔧 Installation Steps

### 1. Setup Database

1. Create a new MySQL database:
```sql
CREATE DATABASE news_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u root -p news_website < database/schema.sql
```

Or use phpMyAdmin to import `database/schema.sql`

### 2. Configure Application

1. Edit `config/config.php`:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'news_website');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site Configuration
define('SITE_NAME', 'Your News Website');
define('SITE_URL', 'http://localhost/news_website');
define('SITE_EMAIL', 'contact@newswebsite.com');
```

2. Create upload directories:
```bash
mkdir -p uploads/articles uploads/users uploads/reporters uploads/ads 
mkdir -p uploads/categories uploads/election uploads/cricket uploads/market
mkdir -p uploads/stories uploads/reels
```

3. Set proper permissions:
```bash
chmod -R 755 uploads/
chmod -R 755 assets/
```

### 3. Install Dependencies (For Push Notifications)

```bash
composer require minishlink/web-push
```

### 4. Generate VAPID Keys (For Push Notifications)

Run this PHP script once:

```php
<?php
require 'vendor/autoload.php';
$keys = Minishlink\WebPush\VAPID::createVapidKeys();
echo "Public Key: " . $keys['publicKey'] . "\n";
echo "Private Key: " . $keys['privateKey'] . "\n";
?>
```

Add keys to `config/config.php`:
```php
define('VAPID_PUBLIC_KEY', 'your-public-key');
define('VAPID_PRIVATE_KEY', 'your-private-key');
```

### 5. Create Admin User

Insert an admin user manually:

```sql
INSERT INTO admin_users (username, email, password, full_name, role, status) 
VALUES (
    'admin',
    'admin@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'Admin User',
    'super_admin',
    'active'
);
```

**Default Login:**
- Email: `admin@example.com`
- Password: `password`

⚠️ **Change this immediately after first login!**

---

## 🌐 Access Points

### Frontend
- Homepage: `http://localhost/news_website/`
- Article: `http://localhost/news_website/article.php?slug=article-slug`
- Category: `http://localhost/news_website/category.php?slug=category-slug`
- Election Dashboard: `http://localhost/news_website/election-dashboard.php`
- Cricket Dashboard: `http://localhost/news_website/cricket-dashboard.php`
- Market Dashboard: `http://localhost/news_website/market-dashboard.php`

### Admin Panel
- Login: `http://localhost/news_website/admin/login.php`
- Dashboard: `http://localhost/news_website/admin/dashboard.php`

### APIs

#### Election APIs
- Get Parties: `/api/election/get_parties.php`
- Get Constituencies: `/api/election/get_constituencies.php`
- Get Candidate: `/api/election/get_candidate.php?id=1`
- Get Polls: `/api/election/get_polls.php`
- Live News: `/api/election/live_news.php`

#### Cricket APIs
- Live Matches: `/api/cricket/live_matches.php`
- Match Details: `/api/cricket/match_details.php?id=1`
- Player Details: `/api/cricket/player.php?id=1`
- Scorecard: `/api/cricket/scorecard.php?match_id=1`
- Polls: `/api/cricket/polls.php`
- News: `/api/cricket/news.php`

#### Market APIs
- Get Indices: `/api/market/get_indices.php`
- Top Movers: `/api/market/get_movers.php`
- Stock Details: `/api/market/get_stock.php?id=1`
- Graph Data: `/api/market/get_graph.php?stock_id=1&period=1M`
- Polls: `/api/market/polls.php`
- News: `/api/market/news.php`

---

## 📁 Project Structure

```
news_website/
├── admin/                  # Admin panel
│   ├── includes/          # Admin header/footer
│   ├── login.php
│   ├── dashboard.php
│   ├── auth_check.php
│   └── logout.php
├── api/                   # REST APIs
│   ├── articles/
│   ├── comments/
│   ├── cricket/
│   ├── election/
│   ├── market/
│   ├── newsletter/
│   └── notifications/
├── assets/               # Static assets
│   ├── css/
│   ├── js/
│   └── images/
├── config/              # Configuration
│   └── config.php
├── database/            # Database schema
│   └── schema.sql
├── includes/            # PHP includes/helpers
│   ├── Database.php
│   ├── Functions.php
│   ├── Security.php
│   ├── Session.php
│   ├── SEO.php
│   ├── Ads.php
│   ├── Newsletter.php
│   └── PushNotifications.php
├── uploads/             # Upload directories
├── index.php           # Homepage
├── article.php         # Article page
├── category.php        # Category page
├── election-dashboard.php
├── cricket-dashboard.php
├── market-dashboard.php
├── sitemap.xml.php     # Dynamic sitemap
└── service-worker.js   # Push notifications SW
```

---

## ⚙️ Configuration

### SEO Settings

Edit meta tags in each page or use the SEO helper:

```php
require_once 'includes/SEO.php';

echo generateMetaTags([
    'title' => 'Article Title',
    'description' => 'Article description',
    'keywords' => 'news, article, keyword',
    'image' => 'path/to/image.jpg',
    'type' => 'article'
]);
```

### Dynamic Sitemap

Access: `http://localhost/news_website/sitemap.xml.php`

Submit to search engines:
- Google Search Console
- Bing Webmaster Tools

### Ads Configuration

Ads can be configured for different positions:
- Header Banner
- Sidebar (Top/Middle/Bottom)
- Article (Top/Middle/Bottom)
- Footer Banner
- Popup/Overlay
- Sticky Bottom
- In-Feed Native

Ad types supported:
- Google AdSense
- Custom Image/HTML/Video
- Taboola Feed
- Government Ads

### Newsletter Setup

1. Configure SMTP settings in `includes/Newsletter.php`
2. Users subscribe via frontend form
3. Email verification required
4. Send newsletters from admin panel
5. Create newsletter from article

### Push Notifications

1. Install service worker: Already included at `/service-worker.js`
2. Include JS: `<script src="/assets/js/push-notifications.js"></script>`
3. Add subscribe button:
```html
<button id="push-subscribe-btn">Enable Notifications</button>
<button id="push-unsubscribe-btn" style="display:none;">Disable Notifications</button>
```

4. Send notifications from admin panel or via API

---

## 🔒 Security Features

- ✅ SQL Injection Protection (Prepared Statements)
- ✅ XSS Protection (Input Sanitization)
- ✅ CSRF Protection (Session Tokens)
- ✅ Password Hashing (bcrypt)
- ✅ Session Management
- ✅ Role-based Access Control
- ✅ File Upload Validation
- ✅ Rate Limiting (Recommended)

---

## 🎨 Customization

### Themes & Styling

Edit `assets/css/style.css` for custom styling.

Bootstrap 5 is used for responsive design.

### Adding New Features

1. Create necessary database tables in `database/schema.sql`
2. Create API endpoint in `api/` directory
3. Create admin management page in `admin/`
4. Create frontend display page
5. Update navigation in `includes/header.php`

---

## 📊 Database Tables

Key tables:
- `admin_users` - Admin accounts
- `users` - Regular users
- `reporters` - Reporter accounts
- `articles` - News articles
- `categories` - Article categories
- `tags` - Article tags
- `comments` - User comments
- `election_*` - Election data (parties, candidates, results)
- `cricket_*` - Cricket data (matches, players, scores)
- `market_*` - Market data (stocks, indices, history)
- `ads` - Advertisement management
- `newsletter_subscribers` - Newsletter subscribers
- `push_subscriptions` - Push notification subscriptions

---

## 🐛 Troubleshooting

### Database Connection Error
- Check `config/config.php` settings
- Verify MySQL service is running
- Check user permissions

### Upload Directory Errors
- Ensure directories exist
- Set proper permissions (755 or 777)

### Session Issues
- Check PHP session configuration
- Verify `session_start()` is called

### API Not Working
- Check `.htaccess` for URL rewriting
- Verify JSON content-type headers
- Check browser console for errors

---

## 📈 Performance Optimization

1. **Enable Caching:**
   - Browser caching for static assets
   - MySQL query caching
   - Redis/Memcached for sessions

2. **Optimize Images:**
   - Compress images before upload
   - Use WebP format
   - Implement lazy loading

3. **Database Optimization:**
   - Add indexes on frequently queried columns
   - Regular table optimization
   - Use prepared statements

4. **CDN Integration:**
   - Host static assets on CDN
   - Use CDN for Bootstrap/jQuery

---

## 🔄 Backup & Maintenance

### Database Backup
```bash
mysqldump -u root -p news_website > backup_$(date +%Y%m%d).sql
```

### File Backup
```bash
tar -czf backup_$(date +%Y%m%d).tar.gz /path/to/news_website
```

### Regular Maintenance
- Clean old logs
- Optimize database tables
- Remove unused files
- Update dependencies

---

## 📞 Support & Documentation

For issues or questions:
1. Check this README
2. Review code comments
3. Check error logs
4. Contact development team

---

## 📝 License

Copyright © 2025. All rights reserved.

---

## 🎯 Next Steps

1. ✅ Import database schema
2. ✅ Configure settings
3. ✅ Create admin user
4. ✅ Login to admin panel
5. ✅ Add categories
6. ✅ Create first article
7. ✅ Configure ads
8. ✅ Setup newsletter
9. ✅ Enable push notifications
10. ✅ Customize theme

**Your news website is now ready to use! 🎉**
