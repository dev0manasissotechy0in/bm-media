# Project Organization Guide

## 📁 Folder Structure Overview

The News Website project is now organized into logical folders for better maintainability and clarity.

```
c:\xampp\htdocs\
├── 📚 docs/               (42 files) - All documentation
├── 🧪 tests/              (47 files) - Testing & debug scripts  
├── 📦 archive/            (7 files)  - Deprecated & sample files
├── 🎨 assets/             - CSS, JS, images
├── 🔌 api/                - API endpoints
├── ⚙️ admin/              - Admin panel
├── 📰 views/              - Frontend views
├── 🗄️ database/           - SQL schemas
├── 📱 news_app/           - Flutter mobile app
├── 🔐 auth/               - Authentication
├── 👤 user/               - User dashboard
├── ✍️ author/             - Author dashboard
├── 📤 uploads/            - Uploaded media
├── ⚙️ config/             - Configuration files
├── 🔧 includes/           - Shared PHP includes
├── 📦 vendor/             - Composer dependencies
└── 🌐 *.php               - Main website pages
```

## 📚 Documentation Folder (`/docs/`)

**Contains:** 42 documentation files organized by feature and topic

### Categories:
- **Getting Started** - Installation, setup, quick start guides
- **System Architecture** - Backend, API, system design docs
- **Feature Guides** - Case Threads, Podcasts, Splash Screen, etc.
- **Authentication** - Social login, unified auth system
- **Content Management** - Pages, content types, live updates
- **Troubleshooting** - Fixes, configuration guides
- **Advanced Features** - SMTP, email distribution, analytics

### Quick Access:
- **Start Here:** `docs/README.md` or `docs/QUICK_START.md`
- **API Reference:** `docs/API_SPECIFICATION.md`
- **Features:** Look for `[FEATURE_NAME]_GUIDE.md` files
- **Troubleshooting:** Check files with "FIX" or "COMPLETE" in name

**Index:** See `docs/README_DOCS.md` for complete file listing

## 🧪 Tests Folder (`/tests/`)

**Contains:** 47 testing, debugging, and verification scripts

### Categories:
- **Database Tests** (`check-*.php`) - Verify database structure and data
- **Feature Tests** (`test-*.php`) - Test specific features
- **Debug Tools** (`debug-*.php`) - Debugging utilities
- **Fix Utilities** (`fix-*.php`) - Data correction scripts
- **Find Tools** (`find-*.php`) - Locate issues
- **Verification** (`verify-*.php`) - State verification

### Common Usage:
```bash
# Database checks
php tests/check-database.php
php tests/check-podcasts.php

# Feature testing
php tests/test-podcast-api.php
php tests/test-category.php

# Debugging
php tests/debug-all-reels.php
```

⚠️ **Warning:** Fix utilities modify data - backup before use!

**Index:** See `tests/README_TESTS.md` for complete file listing

## 📦 Archive Folder (`/archive/`)

**Contains:** 7 sample files and one-time setup utilities

### Types:
- **Sample Code** - Example implementations
- **Setup Scripts** - One-time initialization (already executed)
- **Test Data** - Initial development test files
- **Deprecated** - Old implementations kept for reference

### Files:
- `add_live_updates_sample.php` - Live update sample
- `generate_sample_articles.php` - Article generator
- `setup-uploads.php` - Uploads initialization
- `setup-podcasts.php` - Podcast setup
- `setup-app-settings.php` - App settings setup
- `test_banner.json` - Test banner data

⚠️ **Note:** These are reference files only - not for active use

**Index:** See `archive/README_ARCHIVE.md` for details

## 🎯 Main Website Structure

### Core Pages (Root Directory)
```
index.php              - Homepage
article.php            - Article detail
category.php           - Category listing
subcategory.php        - Subcategory listing
tag.php                - Tag listing
search.php             - Search results
case.php               - Case Thread detail
case-threads.php       - Case Threads listing
podcast.php            - Single podcast page
podcasts.php           - Podcast listing
podcast-player.php     - Podcast player
reels.php              - Reels listing
reel-player.php        - Reel player
stories.php            - Stories listing
story.php              - Story detail
mobile-stories.php     - Mobile stories view
mobile-story.php       - Mobile story detail
contact.php            - Contact page
page.php               - Custom pages
```

### Authentication
```
login.php              - Login page
login-otp.php          - OTP login
register.php           - Registration
forgot-password.php    - Password reset
verify-otp.php         - OTP verification
logout.php             - Logout handler
```

### Dashboards
```
cricket-dashboard.php  - Cricket dashboard
election-dashboard.php - Election dashboard
market-dashboard.php   - Market dashboard
```

### Utilities
```
sitemap.php            - Sitemap generator
sitemap.xml.php        - XML sitemap
rss.php                - RSS feed
robots.txt             - Robots file
service-worker.js      - PWA service worker
404.php                - 404 error page
```

## 🔌 API Structure (`/api/`)

```
api/
├── auth/              - Authentication endpoints
├── cases/             - Case Threads APIs
├── podcasts/          - Podcast APIs
├── splash/            - Splash screen APIs
├── notifications/     - Notification APIs
├── articles/          - Article APIs
├── categories/        - Category APIs
└── ...                - Other feature APIs
```

## ⚙️ Admin Structure (`/admin/`)

```
admin/
├── dashboard.php      - Admin dashboard
├── articles.php       - Article management
├── categories.php     - Category management
├── cases.php          - Case Threads management
├── podcasts.php       - Podcast management
├── authors.php        - Author management
├── users.php          - User management
├── settings.php       - General settings
├── splash-screen-settings.php - Splash settings
├── tests/             - Admin testing scripts
├── archive/           - Admin archived files
└── ...                - Other admin pages
```

## 🗄️ Database Structure (`/database/`)

```
database/
├── schema.sql         - Main database schema
├── case_threads_schema.sql - Case Threads tables
├── splash_screen_schema.sql - Splash screen tables
├── podcast_schema.sql - Podcast tables
├── samples/           - Sample data (future)
└── migrations/        - Database migrations (future)
```

## 📱 Mobile App (`/news_app/`)

```
news_app/
├── lib/
│   ├── screens/       - App screens
│   ├── models/        - Data models
│   ├── services/      - API services
│   ├── providers/     - State management
│   ├── components/    - Reusable widgets
│   └── utils/         - Utilities
├── android/           - Android config
├── ios/               - iOS config
├── assets/            - App assets
└── pubspec.yaml       - Dependencies
```

## 🔍 Finding Files

### By Purpose:
- **Documentation:** Check `/docs/` first
- **Testing:** Look in `/tests/`
- **Reference/Old Code:** Check `/archive/`
- **Active Code:** Root directory and feature folders

### By File Type:
- **Markdown (*.md):** In `/docs/`
- **Test Scripts:** In `/tests/`
- **Sample Files:** In `/archive/`
- **Main Pages:** Root directory
- **Admin Pages:** `/admin/`
- **APIs:** `/api/`

### By Feature:
- **Case Threads:** `case.php`, `api/cases/`, `admin/cases.php`, `docs/CASE_*`
- **Podcasts:** `podcast*.php`, `api/podcasts/`, `admin/podcasts.php`, `docs/PODCAST_*`
- **Splash Screen:** `api/splash/`, `admin/splash-screen-settings.php`, `docs/SPLASH_*`
- **Authentication:** `auth/`, `login*.php`, `docs/UNIFIED_AUTH_GUIDE.md`

## 📊 Organization Benefits

### Before Organization:
- ❌ 40+ test files mixed with production code
- ❌ 40+ documentation files in root
- ❌ Hard to find specific files
- ❌ Cluttered root directory
- ❌ No clear structure

### After Organization:
- ✅ Clean root directory
- ✅ Logical folder structure
- ✅ Easy to find documentation
- ✅ Clear separation of concerns
- ✅ Better maintainability
- ✅ Professional project structure

## 🛠️ Maintenance Guidelines

### Adding New Files:

**Documentation:**
```bash
# Add to docs/ folder
docs/NEW_FEATURE_GUIDE.md
```

**Testing Scripts:**
```bash
# Add to tests/ folder
tests/test-new-feature.php
tests/check-new-feature.php
```

**Sample/Demo Files:**
```bash
# Add to archive/ folder
archive/sample_new_feature.php
```

### Regular Cleanup:

**Monthly:**
- Review and remove obsolete test files
- Update documentation indexes
- Archive completed one-time scripts

**Quarterly:**
- Delete very old archive files (6+ months)
- Review and consolidate documentation
- Clean up unused test scripts

## 🎯 Best Practices

1. **Documentation:**
   - Always place in `/docs/`
   - Use descriptive names
   - Update index files

2. **Testing:**
   - Place in `/tests/`
   - Prefix with purpose (test-, check-, debug-, fix-)
   - Document in tests README

3. **Samples:**
   - Place in `/archive/`
   - Mark with execution date
   - Set deletion timeline

4. **Production Code:**
   - Keep in appropriate folders
   - Never mix test code with production
   - Use clear naming

5. **Database Scripts:**
   - Keep schemas in `/database/`
   - One-time migrations in `/archive/`
   - Test scripts in `/tests/`

## 📝 Quick Reference

| Need to... | Look in... |
|------------|-----------|
| Read documentation | `/docs/` |
| Run tests | `/tests/` |
| Find sample code | `/archive/` |
| Edit main page | Root directory |
| Manage admin | `/admin/` |
| Create API | `/api/` |
| Update mobile app | `/news_app/` |
| Configure database | `/database/` |

## 🔄 Migration Complete

### Files Moved:
- ✅ 42 documentation files → `/docs/`
- ✅ 47 test/debug files → `/tests/`
- ✅ 7 sample/setup files → `/archive/`

### Folders Created:
- ✅ `/docs/` with complete index
- ✅ `/tests/` with categorized scripts
- ✅ `/archive/` with archived files
- ✅ `/admin/tests/` for admin tests
- ✅ `/admin/archive/` for admin archives
- ✅ `/database/samples/` for sample data

### Documentation Added:
- ✅ `docs/README_DOCS.md` - Documentation index
- ✅ `tests/README_TESTS.md` - Testing guide
- ✅ `archive/README_ARCHIVE.md` - Archive guide
- ✅ `PROJECT_ORGANIZATION.md` - This file

## 📞 Support

If you can't find a file after reorganization:
1. Check the appropriate folder (docs/tests/archive)
2. Search by filename in File Explorer
3. Check folder README files for indexes
4. Look for similar named files in new structure

---

**Organization Date:** December 8, 2025  
**Files Organized:** 96 files  
**Folders Created:** 7 folders  
**Status:** ✅ Complete
