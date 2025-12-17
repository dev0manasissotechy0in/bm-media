# 🔧 Flutter App Connection Issue - SOLUTION

## Problem
```
Connection timed out
address = 10.0.2.2, port = 38470
```

The Android emulator cannot connect to your localhost (XAMPP).

---

## ✅ SOLUTION 1: Use Your Computer's IP Address (RECOMMENDED)

### Step 1: Find Your Computer's IP Address

**Windows (PowerShell):**
```powershell
ipconfig
```
Look for "IPv4 Address" under your active network adapter (WiFi or Ethernet)
Example: `192.168.1.3`

**Verify XAMPP is accessible:**
Open browser on your computer: `http://192.168.1.3`

### Step 2: Update Flutter App Configuration

In your Flutter app, change the API base URL:

**Before:**
```dart
final String baseUrl = 'http://10.0.2.2/api';
```

**After:**
```dart
final String baseUrl = 'http://192.168.1.3/api';  // Use YOUR IP
```

### Step 3: Test Connection

**From browser on your phone/emulator:**
```
http://192.168.1.3/api/test-connection.php
```

You should see:
```json
{
  "success": true,
  "message": "API is accessible!"
}
```

---

## ✅ SOLUTION 2: Allow XAMPP Through Firewall

Sometimes Windows Firewall blocks external connections to XAMPP.

### Option A: Add Firewall Rule (Windows)

1. Open **Windows Defender Firewall**
2. Click **Advanced settings**
3. Click **Inbound Rules** → **New Rule**
4. Select **Port** → Next
5. Select **TCP**, Specific local ports: **80, 443**
6. Select **Allow the connection**
7. Apply to all profiles
8. Name it: "XAMPP Apache"

### Option B: Temporarily Disable Firewall (For Testing Only)

1. Windows Security → Firewall & network protection
2. Turn off for Private network (temporarily)
3. Test your app
4. Turn it back on

---

## ✅ SOLUTION 3: Update Apache Configuration

Edit `C:\xampp\apache\conf\extra\httpd-xampp.conf`

Find this section:
```apache
<Directory "C:/xampp/htdocs">
    Require local
</Directory>
```

Change to:
```apache
<Directory "C:/xampp/htdocs">
    Require all granted
</Directory>
```

**Restart Apache** after changing.

---

## 📱 Quick Test in Flutter

```dart
// Test connection endpoint
Future<void> testConnection() async {
  try {
    final response = await http.get(
      Uri.parse('http://192.168.1.3/api/test-connection.php'),
    );
    
    if (response.statusCode == 200) {
      print('✅ Connected successfully!');
      print(response.body);
    } else {
      print('❌ Error: ${response.statusCode}');
    }
  } catch (e) {
    print('❌ Connection error: $e');
  }
}
```

---

## 🎯 Common Issues & Fixes

### Issue: Still timing out with IP address
**Fix:** Check if both devices are on the same WiFi network

### Issue: "No route to host"
**Fix:** Disable VPN if you're using one

### Issue: Works on emulator but not on real device
**Fix:** Make sure your phone is on the same WiFi as your computer

### Issue: Connection refused (not timeout)
**Fix:** Apache might not be running, start XAMPP

---

## 🧪 Testing Checklist

- [ ] Find your computer's IP: `ipconfig`
- [ ] Open in browser: `http://YOUR_IP/api/test-connection.php`
- [ ] Update Flutter app with your IP
- [ ] Test from emulator: `http://YOUR_IP/api/test-connection.php`
- [ ] Test categories API: `http://YOUR_IP/api/categories/all.php`
- [ ] Check firewall settings
- [ ] Both devices on same WiFi

---

## 📝 Example Configuration

**Your Computer:**
- IP Address: `192.168.1.3`
- XAMPP running on port 80
- Firewall allows port 80

**Flutter App (lib/config/api_config.dart):**
```dart
class ApiConfig {
  // Development
  static const String baseUrl = 'http://192.168.1.3';
  
  // Endpoints
  static const String categoriesUrl = '$baseUrl/api/categories/all.php';
  static const String articlesUrl = '$baseUrl/api/articles/list.php';
  static const String loginUrl = '$baseUrl/api/auth/login.php';
  
  // Test connection
  static const String testUrl = '$baseUrl/api/test-connection.php';
}
```

---

## 🚀 Next Steps

1. Find your IP address
2. Test in browser: `http://YOUR_IP/api/test-connection.php`
3. Update Flutter app with your IP
4. Run the app again

**Note:** When deploying to production, use your actual domain name!

---

## Need Help?

If you're still having issues:
1. Check XAMPP Apache is running (green in control panel)
2. Test URL in browser on your computer first
3. Test URL in browser on your phone (same WiFi)
4. Only then update Flutter app

Your API endpoint exists and works - it's just a network connectivity issue! 🎉
