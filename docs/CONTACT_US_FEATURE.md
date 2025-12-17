# Contact Us Feature Implementation

## Overview
Implemented a complete Contact Us form feature for the mobile app that integrates with the website's contact queries system. The system tracks whether queries come from the app or website.

## Features Implemented

### 1. Backend API (`api/app/submit-contact.php`)
- ✅ Full input validation
  - Name: 2-100 characters
  - Email: Valid email format
  - Subject: 3-200 characters
  - Message: 10-5000 characters
- ✅ Security features
  - Spam keyword filtering
  - Rate limiting (5 minutes between submissions per IP)
  - Duplicate detection (1 hour window)
  - IP address logging
- ✅ Source tracking (marks submissions as 'app')
- ✅ JSON response format

### 2. Database Updates
- ✅ Added `source` column to `contact_queries` table
  - Type: ENUM('website', 'app')
  - Default: 'website'
- ✅ Updated existing website form to set source='website'

### 3. Flutter Mobile App (`lib/screens/contact_us_screen.dart`)
- ✅ Professional UI with Material Design
- ✅ Form validation matching API requirements
- ✅ Loading states during submission
- ✅ Success/error feedback with SnackBars
- ✅ Character counters
- ✅ Auto-clear form on success
- ✅ Beautiful header card with instructions
- ✅ Proper keyboard types for each field

### 4. Contact Service (`lib/services/contact_service.dart`)
- ✅ HTTP POST request to API
- ✅ JSON encoding/decoding
- ✅ Error handling for network issues
- ✅ Clean service layer architecture

### 5. Integration in Settings
- ✅ Replaced email-based contact with in-app form
- ✅ Added navigation to Contact Us screen
- ✅ Proper icon and styling

### 6. Admin Panel Updates (`admin/contact-queries.php`)
- ✅ Added source column display in table
- ✅ Color-coded badges:
  - Blue badge with phone icon for "App" 
  - Gray badge with globe icon for "Website"
- ✅ Source filter tabs (All, Unread, Read, App, Website)
- ✅ Source display in modal details
- ✅ Count displays for each filter

## Testing Results

### API Test
```bash
POST http://192.168.1.3/api/app/submit-contact.php
Body: {
  "name": "Test User",
  "email": "test@example.com",
  "subject": "Test from App",
  "message": "This is a test message..."
}

Response: {
  "success": true,
  "message": "Thank you for contacting us! We will get back to you soon.",
  "query_id": "2"
}
```

### Database Verification
```
+----+---------------+-------------------+---------------+---------+---------------------+
| id | name          | email             | subject       | source  | created_at          |
+----+---------------+-------------------+---------------+---------+---------------------+
|  2 | Test User     | test@example.com  | Test from App | app     | 2025-12-13 21:06:34 |
|  1 | Gorge Willson | jhonj@outlook.com | Request...    | website | 2025-12-13 11:23:52 |
+----+---------------+-------------------+---------------+---------+---------------------+
```

## Usage

### For Users
1. Open the app
2. Navigate to Profile/Settings tab
3. Tap "Contact Us"
4. Fill in the form:
   - Name (required, 2-100 chars)
   - Email (required, valid format)
   - Subject (required, 3-200 chars)
   - Message (required, 10-5000 chars)
5. Tap "Send Message"
6. Receive success confirmation

### For Admins
1. Login to admin panel
2. Navigate to "Contact Queries" section
3. Use filter tabs:
   - **All**: View all queries
   - **Unread**: View unread queries
   - **Read**: View read queries  
   - **App**: View queries from mobile app only
   - **Website**: View queries from website only
4. See source badge on each query (App/Website)
5. Click query to view full details

## Files Modified/Created

### Created Files
- `/api/app/submit-contact.php` - Contact form API endpoint
- `/news_app/lib/screens/contact_us_screen.dart` - Contact form UI
- `/news_app/lib/services/contact_service.dart` - API service layer

### Modified Files
- `/admin/contact-queries.php` - Added source filtering and display
- `/contact.php` - Added source='website' tracking
- `/news_app/lib/screens/tabs/profile_tab/settings.dart` - Integrated contact screen
- `contact_queries` table - Added source column

## Security Considerations

1. **Rate Limiting**: Prevents spam by limiting submissions to once every 5 minutes per IP
2. **Input Validation**: Server-side validation ensures data integrity
3. **Spam Detection**: Filters out common spam keywords
4. **Duplicate Prevention**: Blocks identical messages within 1 hour
5. **IP Logging**: Tracks submission IPs for abuse prevention
6. **XSS Protection**: All output is properly escaped with htmlspecialchars()

## Benefits

1. **Better User Experience**: In-app form instead of opening email client
2. **Centralized Management**: All queries in one admin panel
3. **Source Tracking**: Know which platform users prefer for contact
4. **Analytics Ready**: Can track app vs website engagement
5. **Professional Look**: Modern UI with validation feedback
6. **Spam Protected**: Multiple layers of abuse prevention

## Future Enhancements (Optional)

- [ ] Add push notification when new query received
- [ ] Add reply functionality in admin panel
- [ ] Add contact query analytics dashboard
- [ ] Add export to CSV feature
- [ ] Add file attachment support
- [ ] Add predefined subject categories
- [ ] Add admin reply templates
- [ ] Add user query history in app profile
