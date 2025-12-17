# Complete News Website - PHP & MySQL

A fully-featured news website with multiple content types, user/reporter/admin accounts, election dashboard, cricket dashboard, market dashboard, and comprehensive content management system.

## 🌟 Features

### Core Features
- **Multi-type Content Management**: Standard articles, reels, videos, photo galleries, live news
- **Three User Levels**: Users, Reporters, Super Admin
- **Category Management**: Hierarchical categories with drag-drop ordering
- **Tags System**: Dynamic tagging with trending tags
- **SEO Optimized**: Dynamic sitemap, meta tags, schema markup
- **Responsive Design**: Bootstrap 5, mobile-first approach
- **Real-time Updates**: Live news with auto-refresh
- **Stories & Reels**: Instagram-style stories and TikTok-style reels

### User Features
- **Authentication**: Email, Phone, Google, Facebook login
- **Save Articles**: Bookmark favorite articles
- **Like & Comment**: Engage with content
- **Download Articles**: PDF export functionality
- **Personalized Dashboard**: View saved and liked articles

### Reporter Features
- **Unique Reporter ID**: Auto-generated unique IDs
- **Document Management**: Upload multiple verification documents
- **Validity System**: Time-based reporter access
- **Author Promotion**: Can be promoted to author status
- **Article Submission**: Submit news for approval

### Admin Panel
- **Complete CRUD**: Manage all content types
- **User Management**: Control user, reporter, admin accounts
- **Content Moderation**: Approve/reject comments, articles
- **Analytics Dashboard**: View site statistics
- **Ads Management**: Google Ads, custom ads, government ads
- **Newsletter Management**: Create and send newsletters

### Special Dashboards

#### 🗳️ Election Dashboard
- Live election results with auto-updates (10 sec interval)
- Party-wise seat distribution (bar chart + donut chart)
- Constituency-wise results table
- Opinion & exit polls visualization
- Live election news feed
- Real-time updates timeline
- Interactive state maps (ECharts + GeoJSON)
- Candidate details with photos

#### 🏏 Cricket Dashboard
- Live cricket scores with ball-by-ball updates
- Match cards (upcoming, live, completed)
- Detailed scorecard with overs, wickets, run rate
- Player statistics and profiles
- Live polls (Who will win?, Best batsman?)
- Points table for tournaments
- Cricket news feed
- Auto-refresh every 10 seconds

#### 📈 Market Dashboard
- Live Sensex & Nifty ticker
- Top gainers & losers
- Candlestick charts (ECharts)
- Stock detail popups
- Market sentiment polls
- Most active stocks
- Finance news feed
- Real-time price updates

### Additional Features
- **Dynamic Sitemap**: Auto-generated XML sitemap
- **Web Notifications**: Push notifications with Firebase
- **Newsletter System**: Custom newsletters + article newsletters
- **Multiple Ad Support**: Google Ads, Custom Ads, Taboola
- **CSV Import**: Bulk data import for election, cricket, market
- **Comment System**: Nested comments with moderation
- **Rating System**: 5-star ratings for articles
- **Social Sharing**: Facebook, Twitter, WhatsApp, Telegram
- **RSS Feed**: Auto-generated RSS
- **Search**: Full-text search with AJAX suggestions
- **Pagination**: Optimized pagination for all listings

## 📋 Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher (8.0 recommended)
- **Apache/Nginx**: Web server with mod_rewrite enabled
- **Extensions**: PDO, GD, OpenSSL, MBString, JSON

## 🚀 Installation

### Step 1: Database Setup

1. Create a new MySQL database:
```sql
CREATE DATABASE news_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the database schema:
```bash
mysql -u root -p news_website < database/schema.sql
```

Or use phpMyAdmin to import `database/schema.sql`

### Step 2: Configure Database Connection

Edit `config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'news_website');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

### Step 3: Set Base URL

Edit `config/config.php`:

```php
define('SITE_URL', 'http://localhost/news_website');
// OR for live server:
define('SITE_URL', 'https://yourdomain.com');
```

### Step 4: Create Upload Directories

The system will auto-create directories, but you can manually create them:

```bash
mkdir -p uploads/articles
mkdir -p uploads/users
mkdir -p uploads/reporters
mkdir -p uploads/categories
mkdir -p uploads/election
mkdir -p uploads/cricket
mkdir -p uploads/stories
mkdir -p uploads/reels
mkdir -p uploads/ads
mkdir -p logs
```

Set permissions (Linux/Mac):
```bash
chmod -R 755 uploads/
chmod -R 755 logs/
```

### Step 5: Configure Apache (Optional)

If using Apache, ensure `.htaccess` is enabled and mod_rewrite is active.

Create `.htaccess` in root:

```apache
RewriteEngine On
RewriteBase /news_website/

# Remove .php extension
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME}\.php -f
RewriteRule ^(.*)$ $1.php [L]

# Custom Routes
RewriteRule ^article/([a-zA-Z0-9-]+)$ article.php?slug=$1 [L,QSA]
RewriteRule ^category/([a-zA-Z0-9-]+)$ category.php?slug=$1 [L,QSA]
RewriteRule ^tag/([a-zA-Z0-9-]+)$ tag.php?slug=$1 [L,QSA]
```

### Step 6: Default Admin Login

After database import, use these credentials:

- **URL**: http://localhost/news_website/admin/
- **Email**: admin@newswebsite.com
- **Password**: password

**⚠️ IMPORTANT: Change the password immediately after first login!**

## 📁 Project Structure

```
news_website/
├── admin/                  # Admin panel
│   ├── index.php          # Admin dashboard
│   ├── articles/          # Article management
│   ├── users/             # User management
│   ├── reporters/         # Reporter management
│   ├── categories/        # Category management
│   ├── election/          # Election data management
│   ├── cricket/           # Cricket data management
│   ├── market/            # Market data management
│   ├── ads/               # Ads management
│   └── settings/          # Site settings
├── api/                   # REST APIs
│   ├── election/          # Election APIs
│   ├── cricket/           # Cricket APIs
│   ├── market/            # Market APIs
│   ├── articles/          # Article APIs
│   ├── comments/          # Comment APIs
│   ├── newsletter/        # Newsletter APIs
│   └── polls/             # Polls APIs
├── assets/                # Static assets
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   └── images/           # Images
├── auth/                  # Authentication
│   ├── login.php
│   ├── register.php
│   ├── google-callback.php
│   └── facebook-callback.php
├── config/                # Configuration
│   └── config.php        # Main config file
├── database/              # Database files
│   └── schema.sql        # Database schema
├── includes/              # Core includes
│   ├── Database.php      # Database class
│   ├── Security.php      # Security class
│   ├── Session.php       # Session class
│   ├── Functions.php     # Helper functions
│   ├── header.php        # Header template
│   └── footer.php        # Footer template
├── uploads/               # Uploaded files
├── user/                  # User dashboard
├── reporter/              # Reporter dashboard
├── index.php              # Homepage
├── article.php            # Article page
├── category.php           # Category page
├── tag.php                # Tag page
├── election-dashboard.php # Election dashboard
├── cricket-dashboard.php  # Cricket dashboard
├── market-dashboard.php   # Market dashboard
├── search.php             # Search page
├── sitemap.php            # Dynamic sitemap
└── README.md              # This file
```

## 🔧 Configuration Options

### Email Configuration (SMTP)

Edit `config/config.php`:

```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
```

### Social Authentication

1. **Google OAuth**:
   - Get credentials from [Google Cloud Console](https://console.cloud.google.com/)
   - Edit `config/config.php`:
   ```php
   define('GOOGLE_CLIENT_ID', 'your-client-id');
   define('GOOGLE_CLIENT_SECRET', 'your-client-secret');
   ```

2. **Facebook OAuth**:
   - Get credentials from [Facebook Developers](https://developers.facebook.com/)
   - Edit `config/config.php`:
   ```php
   define('FACEBOOK_APP_ID', 'your-app-id');
   define('FACEBOOK_APP_SECRET', 'your-app-secret');
   ```

### Google Analytics & Ads

```php
define('GOOGLE_ANALYTICS_ID', 'UA-XXXXXXXXX-X');
define('GOOGLE_ADSENSE_CLIENT', 'ca-pub-XXXXXXXXXXXXXXXX');
```

### Web Notifications (Firebase)

```php
define('FIREBASE_SERVER_KEY', 'your-server-key');
```

## 📊 Sample Data

To populate the system with sample data:

```sql
-- Sample categories are already included in schema.sql

-- Add sample articles (execute in MySQL)
INSERT INTO articles (title, slug, description, content_type, category_id, author_id, author_type, status, published_at) 
VALUES 
('Breaking: Major News Event', 'breaking-major-news-event', 'This is a sample breaking news article', 'standard', 1, 1, 'admin', 'published', NOW());

-- Add sample election data
INSERT INTO election_parties (name, short_name, color_code) VALUES
('Indian National Congress', 'INC', '#19AAED'),
('Bharatiya Janata Party', 'BJP', '#FF9933'),
('Aam Aadmi Party', 'AAP', '#0E5BAA');
```

## 🎨 Customization

### Change Colors

Edit `assets/css/style.css`:

```css
:root {
    --primary-color: #007bff;  /* Change to your brand color */
    --secondary-color: #6c757d;
    --danger-color: #dc3545;
}
```

### Change Logo

Replace `assets/images/logo.png` with your logo.

### Modify Homepage Layout

Edit `index.php` to customize sections, ordering, and content.

## 🔐 Security Best Practices

1. **Change Default Password**: Change admin password immediately
2. **Update config.php**: Set `DEV_MODE` to `false` in production
3. **HTTPS**: Always use HTTPS in production
4. **Database Backup**: Regular automated backups
5. **File Permissions**: Proper file permissions (644 for files, 755 for directories)
6. **Update PHP**: Keep PHP version updated
7. **SQL Injection**: All queries use PDO prepared statements
8. **XSS Protection**: All output is sanitized
9. **CSRF Protection**: CSRF tokens on all forms
10. **Rate Limiting**: Built-in rate limiting for login attempts

## 📱 API Endpoints

### Election APIs
- `GET /api/election/get_results.php` - Get election results
- `GET /api/election/get_parties.php` - Get all parties
- `GET /api/election/get_constituencies.php` - Get constituencies

### Cricket APIs
- `GET /api/cricket/live_matches.php` - Get live matches
- `GET /api/cricket/match_details.php?id=1` - Get match details
- `GET /api/cricket/scorecard.php?match_id=1` - Get scorecard

### Market APIs
- `GET /api/market/get_indices.php` - Get market indices
- `GET /api/market/get_stock.php?symbol=RELIANCE` - Get stock details
- `GET /api/market/get_graph.php?symbol=SENSEX` - Get graph data

### Article APIs
- `GET /api/articles/search.php?q=keyword` - Search articles
- `POST /api/articles/like.php` - Like article
- `POST /api/articles/save.php` - Save article
- `POST /api/comments/add.php` - Add comment

## 🐛 Troubleshooting

### Database Connection Error
- Check database credentials in `config/config.php`
- Ensure MySQL service is running
- Verify database exists

### File Upload Issues
- Check upload directory permissions
- Verify `upload_max_filesize` in php.ini
- Check `post_max_size` in php.ini

### URL Rewriting Not Working
- Enable mod_rewrite in Apache
- Check `.htaccess` file exists
- Verify `AllowOverride All` in Apache config

### Session Issues
- Check session directory permissions
- Verify `session.save_path` in php.ini

## 📧 Support

For issues or questions:
- Create an issue on GitHub
- Email: support@newswebsite.com

## 📄 License

This project is open-source and available under the MIT License.

## 🙏 Credits

- Bootstrap 5 - UI Framework
- jQuery - JavaScript Library
- Chart.js - Charting Library
- ECharts - Advanced Charts
- Bootstrap Icons - Icon Library
- Font Awesome - Additional Icons

## 🚀 Future Enhancements

- [ ] Mobile App (React Native)
- [ ] Progressive Web App (PWA)
- [ ] AI-powered content recommendations
- [ ] Multi-language support
- [ ] Dark mode
- [ ] Audio articles
- [ ] Podcasts integration
- [ ] Live streaming
- [ ] Advanced analytics dashboard
- [ ] API rate limiting
- [ ] CDN integration
- [ ] ElasticSearch integration

## 📝 Changelog

### Version 1.0.0 (Initial Release)
- Complete news website with all features
- Election, Cricket, Market dashboards
- User, Reporter, Admin accounts
- Multiple content types
- SEO optimization
- Responsive design

---

**Made with ❤️ for journalism and digital media**
