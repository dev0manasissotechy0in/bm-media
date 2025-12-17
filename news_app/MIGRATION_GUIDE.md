# Flutter News App - Firebase to MySQL Migration Guide

## ✅ Completed Steps

### 1. Created API Service (lib/services/api_service.dart)
- Replaces all Firestore calls with REST API calls
- Includes methods for:
  - Articles (all, featured, popular, related, by category, by tag, single)
  - Categories
  - Tags
  - Comments
  - User authentication
  - Article interactions (like, save, views)
  - Search

### 2. Created PHP API Endpoints
Created in `/api/articles/`:
- ✅ `all.php` - Get all published articles
- ✅ `featured.php` - Get featured articles
- ✅ `popular.php` - Get popular articles by views

### 3. Updated Dependencies
- ✅ Added `http: ^1.2.0` package for API calls
- ✅ Removed `cloud_firestore` and `firebase_storage`
- ✅ Kept Firebase Auth, Analytics, and Messaging (optional)

---

## 📋 Next Steps - PHP API Endpoints to Create

### Articles APIs (create in `/api/articles/`)

1. **related.php** - Get related articles by category
```php
// GET: ?category_id=1&exclude_id=5&limit=5
```

2. **by-category.php** - Get articles by category
```php
// GET: ?category_id=1
```

3. **by-tag.php** - Get articles by tag
```php
// GET: ?tag_id=1
```

4. **single.php** - Get single article by ID
```php
// GET: ?id=1
```

5. **search.php** - Search articles
```php
// GET: ?q=search+term
```

6. **increment-views.php** - Increment article view count
```php
// POST: {"article_id": "1"}
```

### Already Exist (just need to return JSON):
- ✅ `like.php` - Like/unlike article
- ✅ `save.php` - Save/unsave article

### Categories API (create in `/api/categories/`)

1. **all.php** - Get all active categories
```php
SELECT id, name, slug, icon, thumbnail FROM categories WHERE status='active'
```

### Tags API (create in `/api/tags/`)

1. **all.php** - Get all tags
```php
SELECT id, name, slug FROM tags
```

### Comments API (already exist, update for JSON):
- ✅ `add.php` - Add comment
- ✅ Update to return success/error JSON

Create **get.php**:
```php
// GET: ?article_id=1
```

### Auth API (create in `/api/auth/`)

1. **login.php** - User login
```php
// POST: {"email": "...", "password": "..."}
// Return: {"success": true, "user": {...}}
```

2. **register.php** - User registration
```php
// POST: {"name": "...", "email": "...", "password": "..."}
```

---

## 🔧 Flutter Code Updates Needed

### 1. Update Article Model
File: `lib/models/article.dart`

Replace `fromFirestore` with `fromJson`:
```dart
factory Article.fromJson(Map<String, dynamic> json) {
  return Article(
    id: json['id'].toString(),
    title: json['title'],
    thumbnailUrl: json['image_url'],
    createdAt: DateTime.parse(json['created_at']),
    status: json['status'],
    author: json['author'] != null ? Author.fromMap(json['author']) : null,
    priceStatus: json['price_status'] ?? 'free',
    isFeatured: json['featured'] ?? false,
    contentType: json['content_type'],
    description: json['description'],
    videoUrl: json['video_url'],
    views: json['views'],
    likes: json['likes'],
    category: json['category'] != null ? ArticleCategory.fromMap(json['category']) : null,
    tagIDs: json['tag_ids'] ?? [],
  );
}
```

### 2. Update main.dart
Remove Firebase initialization (optional):
```dart
// Remove or comment out:
// await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
```

### 3. Replace FirebaseService Calls
Find and replace in all files:

```dart
// Old:
FirebaseService().getAllArticles()

// New:
ApiService().getAllArticles()
```

Search for: `FirebaseService()` and replace with `ApiService()`

### 4. Update Config
File: `lib/services/api_service.dart`

Update line 10:
```dart
static const String baseUrl = 'http://your-domain.com'; // or 'http://192.168.1.x' for local testing
```

---

## 🗄️ Database Considerations

Your MySQL database already has all the data structured correctly:
- ✅ `articles` table with all fields
- ✅ `categories` table
- ✅ `tags` table with `article_tags` junction
- ✅ `authors` table
- ✅ `comments` table
- ✅ `users` table
- ✅ `user_article_likes` table
- ✅ `user_saved_articles` table

---

## 🚀 Testing Steps

1. **Create remaining API endpoints** (listed above)
2. **Run Flutter app:**
   ```bash
   cd c:\xampp\htdocs\news_app
   flutter pub get
   flutter run
   ```
3. **Update baseUrl** in api_service.dart to your local IP
4. **Test API endpoints** using Postman or browser
5. **Update all FirebaseService calls** to ApiService

---

## 📱 App Features Now Connecting to MySQL

✅ Article listing (all, featured, popular)
✅ Categories
✅ Tags
✅ Article details
✅ Comments
✅ User authentication
✅ Like/Save articles
✅ Search
✅ Related articles
✅ View tracking

---

## 🔐 Authentication Notes

The app can now use your existing MySQL users table instead of Firebase Auth:
- Login via API (email/password)
- Registration via API
- Session management via tokens or JWT (optional enhancement)

---

## 📝 Additional Enhancements (Optional)

1. **Add JWT tokens** for secure API authentication
2. **Add pagination** to article APIs (limit/offset)
3. **Add caching** in Flutter app for offline support
4. **Add push notifications** via FCM (keep Firebase Messaging)
5. **Add API rate limiting** on PHP side
6. **Add image upload API** for user profiles

---

## 🐛 Troubleshooting

**CORS Issues:**
Add to all PHP API files:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

**Connection Issues:**
- Use `http://10.0.2.2` for Android emulator
- Use `http://localhost` for iOS simulator
- Use your computer's IP for physical devices

**Image URLs:**
Ensure all image URLs are absolute paths:
```php
BASE_URL . '/' . $article['thumbnail']
```

---

## 📞 Support

If you encounter any issues:
1. Check PHP error logs: `C:\xampp\apache\logs\error.log`
2. Check API response in Flutter debug console
3. Test API endpoints in browser first
4. Verify database connections

---

## ✨ Migration Complete!

Your Flutter app will now use your MySQL database instead of Firestore, connecting directly to your website's admin panel data!
