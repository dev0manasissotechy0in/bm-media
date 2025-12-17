# Custom Page Management System - Setup Instructions

## Quick Setup Guide

### Step 1: Run Database Migration

Open phpMyAdmin or MySQL command line and run:

```bash
# From command line:
mysql -u root -p news_website < database/migration_page_management.sql

# Or open the file in phpMyAdmin and execute it
```

This will create:
- `custom_pages` table
- `contact_queries` table  
- `settings` table
- 3 default pages (About Us, Privacy Policy, Terms & Conditions)
- Default SMTP settings

### Step 2: Verify Installation

1. **Check Tables Created**:
```sql
SHOW TABLES LIKE '%custom%';
SHOW TABLES LIKE '%contact%';
SHOW TABLES LIKE '%settings%';
```

2. **Verify Default Pages**:
```sql
SELECT id, title, slug, status, show_in_footer FROM custom_pages;
```

You should see 3 pages:
- About Us (slug: about-us)
- Privacy Policy (slug: privacy-policy)
- Terms & Conditions (slug: terms-and-conditions)

### Step 3: Access Admin Panel

1. Go to: `http://localhost/admin/pages.php`
2. You should see the 3 default pages
3. Try editing one to familiarize yourself with the interface

### Step 4: Configure SMTP (Optional)

1. Go to: `http://localhost/admin/smtp-settings.php`
2. Enter your SMTP credentials:
   - For Gmail: Use App Password (not regular password)
   - Enable SMTP by toggling the switch
   - Enter your contact email to receive queries
3. Click "Save Settings"

### Step 5: Test Contact Form

1. Visit: `http://localhost/contact.php`
2. Fill out the form with test data
3. Submit the form
4. Go to: `http://localhost/admin/contact-queries.php`
5. Verify the query appears in the list

### Step 6: Test Custom Pages

1. Visit: `http://localhost/page.php?slug=about-us`
2. Visit: `http://localhost/page.php?slug=privacy-policy`
3. Visit: `http://localhost/page.php?slug=terms-and-conditions`
4. Check the footer to see if links appear

## Features Available

### Admin Panel Features

✅ **Custom Pages Management** (`admin/pages.php`)
- Create new pages
- Edit existing pages
- Delete pages
- Toggle footer visibility
- View page statistics

✅ **Page Editor** (`admin/page-add.php`, `admin/page-edit.php`)
- Text editor for content
- SEO meta tags
- Category articles option
- Draft/Published status
- Footer display control

✅ **Contact Queries** (`admin/contact-queries.php`)
- View all queries
- Filter by read/unread
- Mark as read/unread
- View full messages in modal
- Reply via email
- Delete queries

✅ **SMTP Settings** (`admin/smtp-settings.php`)
- Enable/disable SMTP
- Configure mail server
- Test email delivery
- Common SMTP presets

### Frontend Features

✅ **Dynamic Pages** (`page.php`)
- Text content pages
- Category article pages
- SEO optimized
- View counter
- Mobile responsive

✅ **Contact Form** (`contact.php`)
- Spam protection (rate limiting)
- Email notifications
- Database storage
- Validation
- Mobile responsive

✅ **Dynamic Footer** (`includes/footer.php`)
- Automatically shows published pages
- Ordered by priority
- Contact link always visible
- Limit 8 pages

## File Structure

```
htdocs/
├── admin/
│   ├── pages.php                  # Pages list
│   ├── page-add.php              # Add new page
│   ├── page-edit.php             # Edit page
│   ├── contact-queries.php       # View queries
│   └── smtp-settings.php         # SMTP config
├── includes/
│   └── footer.php                # Updated with dynamic links
├── database/
│   ├── schema.sql                # Updated with new tables
│   └── migration_page_management.sql  # Migration file
├── contact.php                    # Contact form page
├── page.php                       # Custom page display
├── CUSTOM_PAGE_MANAGEMENT_GUIDE.md  # Full user guide
└── SETUP_PAGE_MANAGEMENT.md      # This file
```

## Troubleshooting

### Issue: Tables not created
**Solution**: 
```sql
-- Check if database exists
SHOW DATABASES LIKE 'news_website';

-- If not, create it first
CREATE DATABASE news_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE news_website;

-- Then run migration again
SOURCE database/migration_page_management.sql;
```

### Issue: Foreign key constraint fails
**Solution**: Categories table must exist first
```sql
-- Check categories table
SELECT COUNT(*) FROM categories;

-- If it doesn't exist, run full schema first
SOURCE database/schema.sql;

-- Then run page management migration
SOURCE database/migration_page_management.sql;
```

### Issue: Pages not showing in footer
**Solution**:
1. Check page status is "Published"
2. Check "Show in Footer" is enabled
3. Clear browser cache
4. Check footer code in `includes/footer.php`

### Issue: Contact form not saving
**Solution**:
```sql
-- Check if table exists
DESCRIBE contact_queries;

-- Check for errors
-- Enable error display in PHP temporarily
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Issue: SMTP not sending emails
**Solution**:
1. Check SMTP is enabled in settings
2. Verify credentials are correct
3. For Gmail: Use App Password
4. Check firewall allows port 587/465
5. Check spam folder

## Testing Checklist

- [ ] Database migration completed
- [ ] 3 default pages created
- [ ] Admin pages.php accessible
- [ ] Can create new page
- [ ] Can edit existing page
- [ ] Can delete page
- [ ] Footer toggle works
- [ ] Contact form accessible
- [ ] Contact form submits successfully
- [ ] Query appears in admin
- [ ] Can mark query as read
- [ ] Can view full message
- [ ] SMTP settings accessible
- [ ] Dynamic footer links work
- [ ] Custom pages display correctly

## Next Steps

1. **Customize Default Pages**:
   - Edit About Us with your company info
   - Update Privacy Policy with your policies
   - Modify Terms & Conditions

2. **Create Additional Pages**:
   - FAQ page
   - Careers page
   - Press/Media page
   - Advertise with us

3. **Configure SMTP**:
   - Set up Gmail/Outlook account
   - Generate app password
   - Test email delivery

4. **Customize Footer**:
   - Reorder pages by priority
   - Add/remove pages from footer
   - Test on mobile devices

5. **Security**:
   - Change default admin password
   - Keep SMTP credentials secure
   - Regular database backups

## Additional Resources

- **Full User Guide**: `CUSTOM_PAGE_MANAGEMENT_GUIDE.md`
- **Database Schema**: `database/schema.sql`
- **Migration File**: `database/migration_page_management.sql`

## Support

For detailed instructions on each feature, refer to:
- `CUSTOM_PAGE_MANAGEMENT_GUIDE.md` - Complete user guide
- Admin panel tooltips and help text
- Error messages in the system

## Version Information

- **Version**: 1.0
- **Release Date**: December 2, 2025
- **Compatibility**: PHP 7.4+, MySQL 5.7+
- **Dependencies**: PDO, Bootstrap 5, Bootstrap Icons

---

**Setup Complete!** 🎉

Your custom page management system is now ready to use. Visit the admin panel to start creating pages!