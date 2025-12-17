# Custom Page Management System - User Guide

## Overview

The Custom Page Management System allows you to create, edit, and manage static pages dynamically through the admin panel. This system includes:

- **Custom Pages Module**: Create pages like About Us, Privacy Policy, Terms & Conditions, FAQ, etc.
- **Contact Form System**: Manage contact queries with email notifications
- **SMTP Configuration**: Set up email delivery for contact form submissions
- **Dynamic Footer Links**: Control which pages appear in the footer

---

## Table of Contents

1. [Creating Custom Pages](#creating-custom-pages)
2. [Managing Pages](#managing-pages)
3. [Page Types](#page-types)
4. [Contact Form Setup](#contact-form-setup)
5. [SMTP Configuration](#smtp-configuration)
6. [Contact Queries Management](#contact-queries-management)
7. [Footer Display Control](#footer-display-control)

---

## Creating Custom Pages

### Step 1: Access Pages Management
1. Log into the admin panel
2. Navigate to **Pages & Contact** → **Custom Pages**
3. Click the **"Add New Page"** button

### Step 2: Fill Page Details
1. **Title**: Enter the page title (e.g., "About Us")
2. **Slug**: URL-friendly version (auto-generated from title)
   - Example: "about-us" → `/page.php?slug=about-us`
3. **Page Type**: Choose between:
   - **Text Page**: Static content page
   - **Category Articles**: Display articles from a specific category

### Step 3: Add Content
For **Text Pages**:
- Write your content in the large text area
- Content supports line breaks (automatically converted to `<br>` tags)
- Write plain text or HTML

For **Category Articles**:
- Select the category whose articles you want to display
- Articles will be shown in a grid layout with pagination

### Step 4: SEO Settings (Optional)
- **Meta Title**: Custom title for search engines (60 chars max)
- **Meta Description**: Brief description for search results (160 chars max)

### Step 5: Display Options
- **Status**: Choose "Draft" or "Published"
- **Show in Footer**: Enable to display link in website footer
- **Footer Order**: Lower numbers appear first in footer

### Step 6: Publish
- Click **"Create Page"** to save

---

## Managing Pages

### Viewing All Pages
The pages list shows:
- **Order**: Footer display order
- **Title**: Page name
- **Slug**: URL identifier
- **Type**: Text or Category Articles
- **Status**: Draft or Published
- **Show in Footer**: Yes/No toggle
- **Views**: Page view count
- **Created**: Creation date

### Editing Pages
1. Click the **pencil icon** next to any page
2. Modify the details
3. Click **"Update Page"** to save changes

### Deleting Pages
1. Click the **trash icon** next to the page
2. Confirm deletion
3. **Warning**: This action cannot be undone

### Toggle Footer Visibility
- Click the **Yes/No badge** in the "Show in Footer" column
- Page will immediately appear/disappear from footer

---

## Page Types

### 1. Text Page
**Best for**:
- About Us
- Privacy Policy
- Terms & Conditions
- FAQ
- Contact Information
- Company History

**Features**:
- Static content display
- Supports HTML formatting
- Clean, readable layout
- Mobile responsive

**Example Content**:
```
About Us

We are a leading news website dedicated to bringing you the latest news from around the world.

Our Mission:
To provide accurate, unbiased news coverage that keeps you informed.

Our Team:
We have 50+ journalists working 24/7 to bring you breaking news.
```

### 2. Category Articles Page
**Best for**:
- Special Collections (e.g., "Investigative Journalism")
- Featured Content (e.g., "Editor's Picks")
- Themed Pages (e.g., "COVID-19 Coverage")

**Features**:
- Displays articles from selected category
- Grid layout with images
- Pagination (12 articles per page)
- Shows article metadata (author, date, category)

---

## Contact Form Setup

### Frontend Contact Page
The contact page is automatically available at:
```
https://yoursite.com/contact.php
```

### Form Fields
1. **Name** (required)
2. **Email** (required, validated)
3. **Subject** (required)
4. **Message** (required, min 10 characters)

### Features
- **Rate Limiting**: Prevents spam (5-minute cooldown between submissions)
- **IP Tracking**: Records submitter's IP address
- **Email Notifications**: Sends alerts to admin (if SMTP enabled)
- **Database Storage**: All queries saved for review

---

## SMTP Configuration

### Accessing SMTP Settings
1. Go to **Pages & Contact** → **SMTP Settings**
2. Or click **SMTP Settings** from Contact Queries page

### Configuration Steps

#### 1. Enable SMTP
- Toggle **"Enable SMTP Email"** switch
- When enabled, contact form submissions will trigger email notifications

#### 2. Enter SMTP Details

**Required Fields**:
- **SMTP Host**: Your mail server address
- **SMTP Port**: Server port (common: 587, 465, 25)
- **Encryption**: TLS (recommended), SSL, or None
- **From Email**: Email address to send from
- **Contact Email**: Your email to receive queries

**Optional Fields**:
- **SMTP Username**: Usually your email address
- **SMTP Password**: Your email password or app password
- **From Name**: Display name for sent emails

#### 3. Common SMTP Services

**Gmail**:
```
Host: smtp.gmail.com
Port: 587
Encryption: TLS
Username: your-email@gmail.com
Password: [App Password - not your regular password]
```

**How to get Gmail App Password**:
1. Go to Google Account settings
2. Security → 2-Step Verification
3. App passwords → Generate new
4. Use generated password in SMTP settings

**Outlook/Office 365**:
```
Host: smtp-mail.outlook.com
Port: 587
Encryption: TLS
```

**Yahoo Mail**:
```
Host: smtp.mail.yahoo.com
Port: 587
Encryption: TLS
```

#### 4. Save Settings
- Click **"Save Settings"**
- Test by submitting contact form on your website

### Security Best Practices
- Use app-specific passwords (not your main password)
- Keep SMTP credentials secure
- Enable 2-factor authentication on your email account
- Consider using environment variables for production

---

## Contact Queries Management

### Accessing Queries
Navigate to **Pages & Contact** → **Contact Queries**

### Query List Features

#### Filter Tabs
- **All**: Show all queries
- **Unread**: New queries (highlighted in yellow)
- **Read**: Processed queries

#### Query Information
- **Status Icon**: Envelope (unread) or Open Envelope (read)
- **Name**: Sender's name
- **Email**: Clickable mailto link
- **Subject**: Query subject
- **Message**: Click to view full message in modal
- **Date**: Submission timestamp

### Managing Queries

#### Viewing Full Message
1. Click the message preview
2. Modal opens with complete details:
   - Full name
   - Email address
   - Subject
   - Complete message
   - Submission date

#### Marking Read/Unread
- Click the **envelope icon** to toggle read status
- Unread queries show yellow highlight

#### Replying to Queries
1. Click **"Reply via Email"** in the message modal
2. Your default email client opens with:
   - To: Sender's email
   - Subject: Re: [Original Subject]
3. Compose and send your reply

#### Deleting Queries
1. Click the **trash icon**
2. Confirm deletion
3. Query permanently removed from database

### Pagination
- 20 queries per page
- Navigate using page numbers at bottom

---

## Footer Display Control

### How Footer Links Work

1. **Always Visible**:
   - Home link
   - Contact Us link

2. **Dynamic Pages**:
   - Pages with "Show in Footer" enabled appear automatically
   - Ordered by "Footer Order" value (ascending)
   - Maximum 8 pages shown

### Best Practices

1. **Essential Pages Only**:
   - About Us
   - Privacy Policy
   - Terms & Conditions
   - FAQ

2. **Ordering**:
   ```
   Order 1: About Us
   Order 2: Privacy Policy
   Order 3: Terms & Conditions
   Order 4: Contact (always visible)
   ```

3. **Keep It Clean**:
   - Don't overcrowd footer
   - 5-8 links is optimal
   - Most important pages first

### Preview Footer
- Visit your website frontend
- Scroll to bottom
- Verify links appear and work correctly

---

## Database Tables

### `custom_pages`
Stores all custom pages:
- `id`: Unique identifier
- `title`: Page title
- `slug`: URL-friendly identifier
- `content`: Page content (LONGTEXT)
- `page_type`: 'text' or 'category_articles'
- `category_id`: Linked category (for category_articles type)
- `status`: 'draft' or 'published'
- `show_in_footer`: Boolean (0/1)
- `order_id`: Footer display order
- `meta_title`: SEO title
- `meta_description`: SEO description
- `views_count`: Page view counter
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

### `contact_queries`
Stores contact form submissions:
- `id`: Unique identifier
- `name`: Sender name
- `email`: Sender email
- `subject`: Query subject
- `message`: Full message text
- `is_read`: Boolean (0/1)
- `ip_address`: Submitter's IP
- `created_at`: Submission timestamp

### `settings`
Stores SMTP and other settings:
- `id`: Unique identifier
- `setting_key`: Setting name (e.g., 'smtp_host')
- `setting_value`: Setting value
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

---

## Troubleshooting

### Pages Not Appearing in Footer
1. Check page status is "Published"
2. Verify "Show in Footer" is enabled
3. Clear browser cache
4. Check order_id is set correctly

### Contact Form Not Sending Emails
1. Verify SMTP is enabled in settings
2. Check SMTP credentials are correct
3. Test SMTP connection manually
4. Check spam folder
5. Review server PHP mail logs
6. Ensure firewall allows outbound SMTP

### Gmail "Less Secure Apps" Error
- Gmail no longer supports less secure apps
- **Solution**: Use App Passwords instead
- Enable 2-factor authentication first
- Generate app-specific password

### Contact Queries Not Saving
1. Check database connection
2. Verify `contact_queries` table exists
3. Check form validation errors
4. Review browser console for JS errors

### Slug Conflicts
- Each slug must be unique
- System will show error if slug exists
- Modify slug to make it unique

---

## Advanced Tips

### Custom HTML in Pages
You can use HTML tags in text pages:
```html
<h2>Section Title</h2>
<p>Paragraph text</p>
<ul>
  <li>List item 1</li>
  <li>List item 2</li>
</ul>
<a href="https://example.com">External Link</a>
```

### SEO Optimization
1. Use descriptive meta titles
2. Write compelling meta descriptions
3. Include keywords naturally
4. Keep titles under 60 characters
5. Keep descriptions under 160 characters

### URL Structure
Pages are accessible via:
```
https://yoursite.com/page.php?slug=your-page-slug
```

Consider using `.htaccess` for cleaner URLs:
```apache
RewriteRule ^page/([a-z0-9-]+)$ page.php?slug=$1 [L]
```

This allows:
```
https://yoursite.com/page/your-page-slug
```

---

## Support

For additional help:
1. Check admin panel tooltips
2. Review error messages carefully
3. Verify all required fields are filled
4. Test on staging environment first
5. Keep database backups before major changes

---

**Last Updated**: December 2, 2025  
**Version**: 1.0