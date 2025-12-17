# Testing & Debug Files

This folder contains all testing scripts, debugging tools, and verification utilities for the News Website project.

## 📂 File Categories

### Database Tests (`check-*.php`)
- **check-database.php** - Database connection and structure verification
- **check-reels.php** - Reels table and data verification
- **check-podcasts.php** - Podcast table verification
- **check-podcast-structure.php** - Podcast structure validation
- **check-reporters-table.php** - Reporters table check
- **check-article-status.php** - Article status verification
- **check-media-columns.php** - Media table columns check
- **check-layout-settings.php** - Layout settings verification
- **check-current-layout.php** - Current layout check
- **check-image-paths.php** - Image path validation
- **check-all-reels.php** - Complete reels verification

### Feature Tests (`test-*.php`, `test-*.html`)
- **test-api-output.html** - API output testing interface
- **test-api-responses.php** - API response testing
- **test-auth-api.html** - Authentication API testing
- **test-cat.php** - Category testing
- **test-category.php** - Category functionality test
- **test-category-live.php** - Live category testing
- **test-connection.php** - Database connection test
- **test-image-query.php** - Image query testing
- **test-local-podcast-api.php** - Local podcast API test
- **test-podcast-api.php** - Podcast API testing
- **test-raw.php** - Raw data output test
- **test-reel-output.html** - Reel output visualization
- **test-reels-complete.php** - Complete reels test
- **test-social-login.html** - Social login testing
- **test-timeline-apis.php** - Timeline API testing
- **test-url-fixes.php** - URL fixing verification
- **test-video-category.php** - Video category testing
- **test-videos-podcasts.php** - Video podcast testing
- **test_otp.ps1** - OTP testing PowerShell script

### Debug Tools (`debug-*.php`)
- **debug-all-reels.php** - Comprehensive reels debugging
- **debug-reel-128.php** - Specific reel debugging (ID 128)

### Fix Utilities (`fix-*.php`)
- **fix-article-128.php** - Fix specific article (ID 128)
- **fix-article-thumbnails.php** - Article thumbnail corrections
- **fix-layout-setting.php** - Layout setting fixes
- **fix-reel-videos.php** - Reel video path fixes
- **fix-specific-thumbnails.php** - Specific thumbnail corrections

### Find Utilities (`find-*.php`)
- **find-old-thumbnail.php** - Locate old thumbnail references
- **find-wrong-thumbnails.php** - Find incorrect thumbnails

### Database Utilities
- **run-auth-migration.php** - Authentication migration script
- **update-article-133.php** - Update specific article
- **update-podcasts-table.php** - Podcast table updates
- **verify-database-state.php** - Database state verification
- **verify-otp.php** - OTP verification test
- **verify-reporter.php** - Reporter verification

### Layout Utilities
- **set-layout-3.php** - Set layout to version 3

## 🚀 Usage

### Running Tests

**Database Tests:**
```bash
php check-database.php
php check-reels.php
php check-podcasts.php
```

**Feature Tests:**
```bash
php test-connection.php
php test-podcast-api.php
php test-category.php
```

**Debug Tools:**
```bash
php debug-all-reels.php
php debug-reel-128.php
```

### Fix Utilities

**CAUTION:** Fix utilities modify database records. Always backup before running!

```bash
php fix-article-thumbnails.php
php fix-reel-videos.php
```

### Verification Tools

```bash
php verify-database-state.php
php verify-reporter.php
```

## 🔒 Safety Notes

1. **Backup First**: Always backup your database before running fix utilities
2. **Test Environment**: Run tests in development environment first
3. **Read Before Running**: Check file contents to understand what each script does
4. **Database Impact**: Some scripts modify data - use with caution
5. **Dependencies**: Ensure config files and database connections are properly set

## 📊 Test Categories by Purpose

### Pre-Deployment Checks
- `check-database.php`
- `check-article-status.php`
- `verify-database-state.php`

### Feature Validation
- `test-podcast-api.php`
- `test-timeline-apis.php`
- `test-reels-complete.php`

### Debugging Issues
- `debug-all-reels.php`
- `debug-reel-128.php`
- `check-image-paths.php`

### Data Fixes
- `fix-article-thumbnails.php`
- `fix-reel-videos.php`
- `update-podcasts-table.php`

## 🛠️ Development Workflow

1. **Before Deployment**
   - Run all `check-*.php` scripts
   - Verify `test-connection.php` passes
   - Test critical features

2. **After Feature Addition**
   - Create new test file
   - Run feature-specific tests
   - Update test documentation

3. **When Issues Arise**
   - Use `debug-*.php` tools
   - Check relevant `check-*.php` scripts
   - Apply `fix-*.php` if needed

4. **Regular Maintenance**
   - Run `verify-database-state.php` weekly
   - Check `find-*.php` for data issues
   - Update test scripts as needed

## 📝 Adding New Tests

When creating new test files:
1. Use descriptive naming: `test-[feature].php`
2. Include error handling
3. Output clear results
4. Document in this README
5. Add to appropriate category

## ⚠️ Important Notes

- These files are for **testing/development only**
- Do NOT deploy test files to production
- Keep test files updated with feature changes
- Document any new testing procedures
- Test files may contain hardcoded values for specific testing scenarios

## 🔄 Maintenance

Regular tasks:
- Remove obsolete test files
- Update tests when features change
- Keep documentation current
- Archive old debug scripts
