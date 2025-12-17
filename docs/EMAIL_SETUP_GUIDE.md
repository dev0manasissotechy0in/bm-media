# Email Setup Guide for OTP Authentication

## Overview
The OTP authentication system now requires proper email configuration to send verification codes. Debug OTP display has been removed for security.

## SMTP Configuration

### 1. Access SMTP Settings
1. Login to Admin Panel: `http://your-domain.com/admin`
2. Go to **Settings** → **Email Settings** tab
3. Configure SMTP details

### 2. Gmail SMTP Setup (Recommended for Testing)

**SMTP Settings:**
- **Host:** `smtp.gmail.com`
- **Port:** `587` (TLS) or `465` (SSL)
- **Username:** Your Gmail address (e.g., `yourapp@gmail.com`)
- **Password:** App-specific password (NOT your Gmail password)
- **From Email:** Same as username
- **From Name:** Your App Name

**Getting Gmail App Password:**
1. Go to Google Account: https://myaccount.google.com/
2. Navigate to **Security** → **2-Step Verification**
3. Scroll down to **App passwords**
4. Select **Mail** and **Other (Custom name)**
5. Name it "News App" and click **Generate**
6. Copy the 16-character password (remove spaces)
7. Use this password in SMTP settings

### 3. Other Email Providers

#### SendGrid
- Host: `smtp.sendgrid.net`
- Port: `587`
- Username: `apikey`
- Password: Your SendGrid API Key

#### Mailgun
- Host: `smtp.mailgun.org`
- Port: `587`
- Username: Your Mailgun SMTP username
- Password: Your Mailgun SMTP password

#### AWS SES
- Host: `email-smtp.us-east-1.amazonaws.com` (region-specific)
- Port: `587`
- Username: Your SMTP username from AWS
- Password: Your SMTP password from AWS

#### Custom SMTP Server
- Host: Your SMTP server address
- Port: `587` (TLS) or `465` (SSL)
- Username: Your SMTP username
- Password: Your SMTP password

## Testing Email Configuration

### Method 1: Using Admin Check SMTP Tool
1. Go to Admin Panel → **Settings** → **Email Settings**
2. Click **Test SMTP Connection** button
3. Check the response for success/error

### Method 2: Manual Test
1. Access: `http://your-domain.com/admin/check-smtp.php`
2. Enter a test email address
3. Click **Send Test Email**
4. Check your inbox (and spam folder)

### Method 3: API Test
Use the OTP API endpoint:
```bash
curl -X POST http://your-domain.com/api/auth/send-otp.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","name":"Test User","user_type":"user"}'
```

## Troubleshooting

### Common Issues

#### 1. "Failed to send OTP email"
**Causes:**
- SMTP credentials are incorrect
- SMTP server is not reachable
- Port is blocked by firewall
- SSL/TLS settings mismatch

**Solutions:**
- Verify SMTP credentials
- Try different ports (587 or 465)
- Check firewall settings
- Enable "Less secure app access" (Gmail) or use App Password

#### 2. "SMTP connect() failed"
**Causes:**
- Wrong SMTP host
- Firewall blocking outbound SMTP
- SSL certificate issues

**Solutions:**
- Double-check SMTP host address
- Contact hosting provider about SMTP ports
- Try without SSL/TLS first

#### 3. Email goes to spam
**Solutions:**
- Add SPF record to your domain
- Set up DKIM authentication
- Use a reputable email service
- Avoid spam trigger words

#### 4. "Authentication failed"
**Solutions:**
- Use App-specific password (Gmail)
- Enable SMTP authentication
- Check username format (some require full email)

## Security Notes

1. **Never commit SMTP credentials** to version control
2. **Use environment variables** for sensitive data in production
3. **Enable 2FA** on email accounts used for SMTP
4. **Use App-specific passwords** instead of actual account passwords
5. **Regularly rotate** SMTP credentials
6. **Monitor email sending logs** for suspicious activity

## Database Configuration

SMTP settings are stored in the `settings` table:
```sql
-- Check current SMTP settings
SELECT * FROM settings WHERE setting_key LIKE 'smtp_%';

-- Update SMTP host
UPDATE settings SET setting_value = 'smtp.gmail.com' WHERE setting_key = 'smtp_host';
```

## Email Templates

OTP emails are sent using templates in `includes/EmailHelper.php`. To customize:
1. Open `includes/EmailHelper.php`
2. Locate the `sendOTP()` method
3. Modify the HTML email template
4. Save and test

## Multi-SMTP Support

The system supports multiple SMTP configurations:
- **auth_smtp_*** - For authentication emails (OTP, password reset)
- **newsletter_smtp_*** - For newsletter emails
- **contact_smtp_*** - For contact form emails

Configure each separately in the database `settings` table with `smtp_purpose` field.

## Production Checklist

- [ ] SMTP credentials configured correctly
- [ ] Test email sent and received successfully
- [ ] From email address verified/whitelisted
- [ ] SPF/DKIM records configured (if using custom domain)
- [ ] Email sending logs monitored
- [ ] Rate limiting configured (if needed)
- [ ] Backup SMTP provider configured
- [ ] Error notifications set up

## Support

For issues:
1. Check PHP error logs: `/xampp/apache/logs/error.log`
2. Check email sending logs in database
3. Verify SMTP server status
4. Contact your hosting provider for firewall/port issues

## Additional Resources

- [Gmail App Passwords Guide](https://support.google.com/accounts/answer/185833)
- [SendGrid SMTP Guide](https://docs.sendgrid.com/for-developers/sending-email/integrating-with-the-smtp-api)
- [PHPMailer Documentation](https://github.com/PHPMailer/PHPMailer)
