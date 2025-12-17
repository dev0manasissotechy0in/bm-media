# How to Get the Correct FCM Server Key

## The Issue
The key you provided (`BI5XlhXpkgqOtkBBvxP7yNQoeYdRwD0qnUAYL5TcGa1au9uak4nfOzR3SVA6VJwS0z3D1Zdsd4MSCk-L98IRYmU`) is a **Web Push Certificate (VAPID Key)**, not the FCM Server Key.

## Steps to Get the Correct Server Key

### Option 1: Cloud Messaging API (Legacy) - Recommended for Your Setup

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project: **brackoddmedia-56b89**
3. Click the **⚙️ Settings** icon → **Project settings**
4. Go to the **Cloud Messaging** tab
5. Scroll down to **Cloud Messaging API (Legacy)**
6. If it says "Cloud Messaging API (Legacy) is disabled":
   - Click **⋮ (three dots)** → **Manage API in Google Cloud Console**
   - Enable "Firebase Cloud Messaging API (Legacy)"
7. Copy the **Server key** (it looks like: `AAAAxxxxxxx:APA91bFxxx...`)

### Option 2: Upgrade to FCM v1 API (Modern, Recommended Long-term)

If the legacy API is disabled, you'll need to:
1. Upgrade to FCM v1 API
2. Use OAuth 2.0 service account JSON instead of server key
3. Modify the PHP code to use the new API

## What Each Key Is:

- **Server Key** (starts with `AAAA...`): For server-side push notifications via legacy API
- **Sender ID** (number like `406297029347`): For client-side FCM initialization
- **Web Push Certificate** (starts with `BI5X...`): For web browser push notifications only
- **API Key** (like `AIzaSyB0aDiiNH...`): For general Firebase services, not push notifications

## Quick Fix

Replace the current key in Settings → Notifications with the **Server Key** from step 5-7 above.

The correct format should be: `AAAAxxxxxxx:APA91bFxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
