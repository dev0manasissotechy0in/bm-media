# Quick Start Guide - News Website

## 🚀 Get Started in 5 Minutes

### Step 1: Import Database (2 minutes)
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create database: `news_website`
3. Import file: `database/schema.sql`

### Step 2: Configure Settings (1 minute)
Edit `config/config.php`:
```php
define('DB_NAME', 'news_website');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_URL', 'http://localhost');
```

### Step 3: Create Admin Account (1 minute)
Run this SQL in phpMyAdmin:
```sql
INSERT INTO admin_users (username, email, password, full_name, role, status) 
VALUES ('admin', 'admin@example.com', 
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
'Admin', 'super_admin', 'active');
```

### Step 4: Login & Start (1 minute)
1. Go to: `http://localhost/admin/login.php`
2. Login with:
   - Email: `admin@example.com`
   - Password: `password`
3. Start managing your news website!

---

## 🎯 First Tasks After Login

### 1. Add Categories (Required)
Admin Panel → Categories → Add Category
```
Examples:
- Politics
- Sports
- Technology
- Entertainment
- Business
```

### 2. Create First Article
Admin Panel → Articles → Add New Article

### 3. Configure Homepage
- Set featured articles
- Set top news
- Configure live news

### 4. Setup Election Dashboard (Optional)
Admin Panel → Election → Add:
1. Parties (with symbols)
2. Constituencies
3. Candidates
4. Results

### 5. Setup Cricket Dashboard (Optional)
Admin Panel → Cricket → Add:
1. Teams
2. Players
3. Matches
4. Live scores

### 6. Setup Market Dashboard (Optional)
Admin Panel → Market → Add:
1. Indices (Sensex, Nifty)
2. Stocks
3. Market data

---

## 📱 Access Your Website

### Frontend URLs
- Homepage: `http://localhost/`
- Articles: `http://localhost/article.php?slug=...`
- Categories: `http://localhost/category.php?slug=...`
- Election: `http://localhost/election-dashboard.php`
- Cricket: `http://localhost/cricket-dashboard.php`
- Market: `http://localhost/market-dashboard.php`

### Admin URLs
- Login: `http://localhost/admin/login.php`
- Dashboard: `http://localhost/admin/dashboard.php`

### API URLs
- Election API: `http://localhost/api/election/get_parties.php`
- Cricket API: `http://localhost/api/cricket/live_matches.php`
- Market API: `http://localhost/api/market/get_indices.php`

---

## 🔧 Common Issues & Fixes

### Database Connection Error
**Problem:** Can't connect to database
**Fix:** 
1. Check MySQL is running
2. Verify credentials in `config/config.php`
3. Check database name matches

### Blank Admin Page
**Problem:** White screen after login
**Fix:**
1. Enable PHP errors: `ini_set('display_errors', 1);`
2. Check PHP error logs
3. Verify all files are uploaded

### Upload Not Working
**Problem:** Can't upload images
**Fix:**
1. Create uploads folder: `mkdir uploads`
2. Set permissions: `chmod 777 uploads -R`
3. Check PHP upload limits in `php.ini`

### APIs Return Error
**Problem:** API endpoints not working
**Fix:**
1. Check `.htaccess` exists
2. Enable mod_rewrite in Apache
3. Check file paths are correct

---

## 💡 Pro Tips

### For Better Performance
1. Enable caching in PHP
2. Optimize database queries
3. Use CDN for static assets
4. Enable GZIP compression

### For Security
1. Change default admin password immediately
2. Update `config/config.php` with strong credentials
3. Set proper file permissions (755 for folders, 644 for files)
4. Keep PHP and MySQL updated

### For SEO
1. Submit sitemap to Google: `http://localhost/sitemap.xml.php`
2. Enable friendly URLs
3. Add meta descriptions to all articles
4. Use proper heading tags

---

## 📊 Sample Data (Optional)

### Create Test Article
```sql
INSERT INTO articles (title, slug, description, category_id, status, published_at) 
VALUES (
    'Breaking News: Test Article',
    'test-article',
    'This is a test article description.',
    1,
    'published',
    NOW()
);
```

### Create Test User
```sql
INSERT INTO users (email, password, full_name, status) 
VALUES (
    'user@example.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Test User',
    'active'
);
```
Password: `password`

---

## 📚 Next Steps

1. ✅ **Customize Design**
   - Edit `assets/css/style.css`
   - Upload logo to `assets/images/`
   - Change colors in header.php

2. ✅ **Add Content**
   - Create 10-20 articles
   - Upload images
   - Add categories and tags

3. ✅ **Configure Features**
   - Setup ads
   - Enable newsletter
   - Configure notifications

4. ✅ **Test Everything**
   - Test all pages
   - Test APIs
   - Test admin panel
   - Test on mobile

5. ✅ **Go Live**
   - Move to production server
   - Update config URLs
   - Setup SSL certificate
   - Configure domain

---

## 🆘 Need Help?

1. Check `INSTALLATION_GUIDE.md` for detailed setup
2. Check `DEVELOPMENT_SUMMARY.md` for features list
3. Review code comments in files
4. Check PHP error logs

---

## 🎉 You're Ready!

Your news website is now set up and ready to use. Start adding content and customize it to match your brand!

**Happy Publishing! 📰**

---

**Quick Reference:**
- Admin Login: `http://localhost/admin/login.php`
- Default Email: `admin@example.com`
- Default Password: `password` (⚠️ Change this!)

*Generated: December 2, 2025*
