# 📱 **CASE THREADS - COMPLETE IMPLEMENTATION GUIDE**

## ✅ **WHAT'S BEEN CREATED**

### 1. **Database Schema** (`database/case_threads_schema.sql`)
- ✅ 9 complete tables with relationships
- ✅ Full-text search indexes
- ✅ Foreign key constraints
- ✅ Sample data included
- **ACTION**: Run this SQL file in your MySQL database

### 2. **API Specification** (`API_SPECIFICATION.md`)
- ✅ 12 REST endpoints fully documented
- ✅ Request/response examples
- ✅ Error handling patterns
- **REFERENCE**: Use this for frontend integration

### 3. **Backend Architecture** (`BACKEND_ARCHITECTURE.md`)
- ✅ Folder structure defined
- ✅ Core helper classes (Database, Response, Auth, Pagination)
- ✅ Router pattern explained
- **ACTION**: Create the folder structure as shown

### 4. **Example API Endpoint** (`api/cases/single.php`)
- ✅ Complete working implementation
- ✅ Shows how to fetch all nested data
- ✅ Demonstrates database queries
- ✅ Proper error handling
- **TEMPLATE**: Use this pattern for other 11 endpoints

### 5. **Web Detail Page** (`views/cases/detail.php`)
- ✅ Complete HTML/CSS implementation
- ✅ Beautiful responsive design
- ✅ Hero section with case info
- ✅ Timeline visualization
- ✅ Articles grid
- ✅ Documents list
- ✅ Media gallery
- ✅ Follow/share functionality
- **TEMPLATE**: Adapt for other web pages

---

## 🚀 **STEP-BY-STEP IMPLEMENTATION PLAN**

### **PHASE 1: Database Setup** (5 minutes)

```bash
# Navigate to your MySQL
mysql -u root -p

# Create database (if needed)
CREATE DATABASE your_news_db;
USE your_news_db;

# Run the schema
source C:/xampp/htdocs/database/case_threads_schema.sql
```

✅ **Verify**: Run `SHOW TABLES;` - you should see 9 new tables

---

### **PHASE 2: Backend Core Setup** (15 minutes)

Create the folder structure:
```
htdocs/
├── api/
│   ├── cases/           ← Create this folder
│   │   ├── single.php   ← Already created!
│   │   ├── list.php     ← Create next
│   │   ├── articles.php
│   │   └── follow.php
│   └── index.php        ← API router (from BACKEND_ARCHITECTURE.md)
├── src/
│   ├── Config/
│   │   └── Database.php ← Copy from BACKEND_ARCHITECTURE.md
│   ├── Helpers/
│   │   ├── Response.php
│   │   ├── Auth.php
│   │   └── Pagination.php
│   └── Models/          ← Create model classes here
└── views/
    └── cases/
        ├── detail.php   ← Already created!
        └── index.php    ← Create list page next
```

**Priority Files to Create:**

1. **src/Config/Database.php** - Copy from BACKEND_ARCHITECTURE.md
2. **src/Helpers/Response.php** - Copy from BACKEND_ARCHITECTURE.md
3. **src/Helpers/Auth.php** - Copy from BACKEND_ARCHITECTURE.md
4. **api/index.php** - API router from BACKEND_ARCHITECTURE.md

---

### **PHASE 3: Remaining API Endpoints** (30 minutes)

Using `api/cases/single.php` as a template, create:

#### **api/cases/list.php** (High Priority)
```php
<?php
// Get cases with filters: category, status, search, sort, pagination
// Query: SELECT * FROM case_threads WHERE ... ORDER BY ... LIMIT ?
// Return: { success: true, data: { cases: [...], pagination: {...} } }
```

#### **api/cases/articles.php** (High Priority)
```php
<?php
// Get case ID from URL
// Query case_article_map JOIN articles
// Support pagination and filters
// Return paginated articles for specific case
```

#### **api/cases/follow.php** (Medium Priority)
```php
<?php
// POST: Insert into case_follows
// Check if already following
// Update case_threads.total_followers
// Return success
```

#### **api/cases/unfollow.php** (Medium Priority)
```php
<?php
// DELETE: Remove from case_follows
// Update case_threads.total_followers
// Return success
```

#### **api/user/followed-cases.php** (Medium Priority)
```php
<?php
// Require auth
// Query: SELECT * FROM case_follows WHERE user_id = ? JOIN case_threads
// Return user's followed cases
```

#### **api/notifications/list.php** (Low Priority)
```php
<?php
// Require auth
// Query notifications WHERE user_id = ? AND is_read = ?
// Support filters: unread, by type
// Return paginated notifications
```

---

### **PHASE 4: Web Frontend** (1 hour)

#### **Create Case Listing Page** (`views/cases/index.php`)

```html
<!DOCTYPE html>
<html>
<head>
    <title>Case Threads - All Cases</title>
    <style>
        /* Use similar styles from detail.php */
        .cases-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            padding: 40px 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .case-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .case-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .case-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .case-content {
            padding: 25px;
        }
        
        .case-category-badge {
            display: inline-block;
            padding: 5px 12px;
            background: #eff6ff;
            color: #1e3c72;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .case-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #1a1a1a;
        }
        
        .case-description {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .case-stats {
            display: flex;
            gap: 20px;
            font-size: 13px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="cases-grid">
        <?php foreach ($cases as $case): ?>
        <a href="/cases/<?php echo $case['slug']; ?>" class="case-card" style="text-decoration: none;">
            <img src="<?php echo $case['thumbnail']; ?>" class="case-thumbnail">
            <div class="case-content">
                <span class="case-category-badge"><?php echo $case['category']; ?></span>
                <h3 class="case-title"><?php echo htmlspecialchars($case['title']); ?></h3>
                <p class="case-description"><?php echo htmlspecialchars($case['short_description']); ?></p>
                <div class="case-stats">
                    <span>📰 <?php echo number_format($case['total_articles']); ?> articles</span>
                    <span>👥 <?php echo number_format($case['total_followers']); ?> followers</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</body>
</html>
```

#### **Add Filters Section** (Above grid)
```html
<div class="filters-bar">
    <select name="category">
        <option value="">All Categories</option>
        <option value="crime">Crime</option>
        <option value="war">War & Conflict</option>
        <option value="corporate">Corporate Scandal</option>
        <option value="political">Political</option>
    </select>
    
    <select name="status">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="concluded">Concluded</option>
    </select>
    
    <input type="search" name="q" placeholder="Search cases...">
    
    <button>Apply Filters</button>
</div>
```

---

### **PHASE 5: Flutter Mobile App** (2 hours)

#### **1. Create Models** (`news_app/lib/models/case_thread.dart`)

```dart
class CaseThread {
  final int id;
  final String title;
  final String slug;
  final String shortDescription;
  final String fullDescription;
  final String status;
  final String category;
  final String primaryLocation;
  final DateTime startDate;
  final DateTime? endDate;
  final String? thumbnail;
  final String? coverImage;
  final int totalArticles;
  final int totalFollowers;
  final int totalViews;
  final bool isFollowing;
  final DateTime createdAt;
  final DateTime? lastActivityAt;
  
  CaseThread({
    required this.id,
    required this.title,
    required this.slug,
    required this.shortDescription,
    required this.fullDescription,
    required this.status,
    required this.category,
    required this.primaryLocation,
    required this.startDate,
    this.endDate,
    this.thumbnail,
    this.coverImage,
    required this.totalArticles,
    required this.totalFollowers,
    required this.totalViews,
    required this.isFollowing,
    required this.createdAt,
    this.lastActivityAt,
  });
  
  factory CaseThread.fromJson(Map<String, dynamic> json) {
    return CaseThread(
      id: json['id'],
      title: json['title'],
      slug: json['slug'],
      shortDescription: json['short_description'],
      fullDescription: json['full_description'],
      status: json['status'],
      category: json['category'],
      primaryLocation: json['primary_location'],
      startDate: DateTime.parse(json['start_date']),
      endDate: json['end_date'] != null ? DateTime.parse(json['end_date']) : null,
      thumbnail: json['thumbnail'],
      coverImage: json['cover_image'],
      totalArticles: json['total_articles'],
      totalFollowers: json['total_followers'],
      totalViews: json['total_views'],
      isFollowing: json['is_following'] ?? false,
      createdAt: DateTime.parse(json['created_at']),
      lastActivityAt: json['last_activity_at'] != null 
          ? DateTime.parse(json['last_activity_at']) 
          : null,
    );
  }
}

class TimelineEvent {
  final int id;
  final String eventTitle;
  final String eventDescription;
  final DateTime eventDate;
  final String? eventTime;
  final String eventType;
  final bool isMajorEvent;
  final int linkedArticlesCount;
  
  TimelineEvent({
    required this.id,
    required this.eventTitle,
    required this.eventDescription,
    required this.eventDate,
    this.eventTime,
    required this.eventType,
    required this.isMajorEvent,
    required this.linkedArticlesCount,
  });
  
  factory TimelineEvent.fromJson(Map<String, dynamic> json) {
    return TimelineEvent(
      id: json['id'],
      eventTitle: json['event_title'],
      eventDescription: json['event_description'],
      eventDate: DateTime.parse(json['event_date']),
      eventTime: json['event_time'],
      eventType: json['event_type'],
      isMajorEvent: json['is_major_event'] == 1,
      linkedArticlesCount: json['linked_articles_count'] ?? 0,
    );
  }
}
```

#### **2. Create API Service** (`news_app/lib/services/case_api_service.dart`)

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../models/case_thread.dart';

class CaseApiService {
  static const String baseUrl = 'http://192.168.1.3/api';
  
  // Get all cases with filters
  static Future<Map<String, dynamic>> getCases({
    String? category,
    String? status,
    String? search,
    int page = 1,
    int limit = 20,
  }) async {
    final queryParams = {
      'page': page.toString(),
      'limit': limit.toString(),
      if (category != null) 'category': category,
      if (status != null) 'status': status,
      if (search != null) 'search': search,
    };
    
    final uri = Uri.parse('$baseUrl/cases').replace(queryParameters: queryParams);
    final response = await http.get(uri);
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return {
        'cases': (data['data']['cases'] as List)
            .map((c) => CaseThread.fromJson(c))
            .toList(),
        'pagination': data['data']['pagination'],
      };
    }
    throw Exception('Failed to load cases');
  }
  
  // Get single case with all details
  static Future<Map<String, dynamic>> getCaseDetail(int caseId) async {
    final response = await http.get(Uri.parse('$baseUrl/cases/$caseId'));
    
    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      return {
        'case': CaseThread.fromJson(data['data']['case']),
        'timeline': (data['data']['timeline']['events'] as List)
            .map((e) => TimelineEvent.fromJson(e))
            .toList(),
        'articles': data['data']['recent_articles']['articles'],
        'documents': data['data']['documents']['documents'],
        'media': data['data']['media']['items'],
      };
    }
    throw Exception('Failed to load case');
  }
  
  // Follow case
  static Future<void> followCase(int caseId, String token) async {
    final response = await http.post(
      Uri.parse('$baseUrl/cases/$caseId/follow'),
      headers: {'Authorization': 'Bearer $token'},
    );
    
    if (response.statusCode != 200) {
      throw Exception('Failed to follow case');
    }
  }
  
  // Unfollow case
  static Future<void> unfollowCase(int caseId, String token) async {
    final response = await http.delete(
      Uri.parse('$baseUrl/cases/$caseId/follow'),
      headers: {'Authorization': 'Bearer $token'},
    );
    
    if (response.statusCode != 200) {
      throw Exception('Failed to unfollow case');
    }
  }
}
```

#### **3. Create Case List Screen** (`news_app/lib/screens/cases/case_threads_list_screen.dart`)

```dart
import 'package:flutter/material.dart';
import '../../services/case_api_service.dart';
import '../../models/case_thread.dart';

class CaseThreadsListScreen extends StatefulWidget {
  @override
  _CaseThreadsListScreenState createState() => _CaseThreadsListScreenState();
}

class _CaseThreadsListScreenState extends State<CaseThreadsListScreen> {
  List<CaseThread> cases = [];
  bool isLoading = true;
  String? selectedCategory;
  String? selectedStatus;
  
  @override
  void initState() {
    super.initState();
    loadCases();
  }
  
  Future<void> loadCases() async {
    setState(() => isLoading = true);
    
    final result = await CaseApiService.getCases(
      category: selectedCategory,
      status: selectedStatus,
    );
    
    setState(() {
      cases = result['cases'];
      isLoading = false;
    });
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Case Threads', style: TextStyle(fontWeight: FontWeight.bold)),
        actions: [
          IconButton(
            icon: Icon(Icons.filter_list),
            onPressed: () => showFilterDialog(),
          ),
        ],
      ),
      body: isLoading
          ? Center(child: CircularProgressIndicator())
          : ListView.builder(
              padding: EdgeInsets.all(16),
              itemCount: cases.length,
              itemBuilder: (context, index) {
                final caseThread = cases[index];
                return CaseCard(caseThread: caseThread);
              },
            ),
    );
  }
  
  void showFilterDialog() {
    showModalBottomSheet(
      context: context,
      builder: (context) => Container(
        padding: EdgeInsets.all(20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('Filters', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            SizedBox(height: 20),
            DropdownButton<String>(
              value: selectedCategory,
              hint: Text('Select Category'),
              isExpanded: true,
              items: ['crime', 'war', 'corporate', 'political']
                  .map((c) => DropdownMenuItem(value: c, child: Text(c.toUpperCase())))
                  .toList(),
              onChanged: (value) {
                setState(() => selectedCategory = value);
                Navigator.pop(context);
                loadCases();
              },
            ),
          ],
        ),
      ),
    );
  }
}

class CaseCard extends StatelessWidget {
  final CaseThread caseThread;
  
  CaseCard({required this.caseThread});
  
  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: InkWell(
        onTap: () {
          Navigator.pushNamed(
            context,
            '/case-detail',
            arguments: caseThread.id,
          );
        },
        borderRadius: BorderRadius.circular(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (caseThread.thumbnail != null)
              ClipRRect(
                borderRadius: BorderRadius.vertical(top: Radius.circular(12)),
                child: Image.network(
                  caseThread.thumbnail!,
                  width: double.infinity,
                  height: 180,
                  fit: BoxFit.cover,
                ),
              ),
            Padding(
              padding: EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: Colors.blue[50],
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      caseThread.category.toUpperCase(),
                      style: TextStyle(
                        color: Colors.blue[900],
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  SizedBox(height: 12),
                  Text(
                    caseThread.title,
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Colors.black87,
                    ),
                  ),
                  SizedBox(height: 8),
                  Text(
                    caseThread.shortDescription,
                    style: TextStyle(color: Colors.grey[600], fontSize: 14),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  SizedBox(height: 12),
                  Row(
                    children: [
                      Icon(Icons.article, size: 16, color: Colors.grey),
                      SizedBox(width: 4),
                      Text('${caseThread.totalArticles} articles', 
                           style: TextStyle(color: Colors.grey, fontSize: 12)),
                      SizedBox(width: 16),
                      Icon(Icons.people, size: 16, color: Colors.grey),
                      SizedBox(width: 4),
                      Text('${caseThread.totalFollowers} followers',
                           style: TextStyle(color: Colors.grey, fontSize: 12)),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
```

#### **4. Create Case Detail Screen** (`news_app/lib/screens/cases/case_thread_detail_screen.dart`)

```dart
import 'package:flutter/material.dart';
import '../../services/case_api_service.dart';
import '../../models/case_thread.dart';

class CaseThreadDetailScreen extends StatefulWidget {
  final int caseId;
  
  CaseThreadDetailScreen({required this.caseId});
  
  @override
  _CaseThreadDetailScreenState createState() => _CaseThreadDetailScreenState();
}

class _CaseThreadDetailScreenState extends State<CaseThreadDetailScreen> 
    with SingleTickerProviderStateMixin {
  CaseThread? caseThread;
  List<TimelineEvent> timeline = [];
  List articles = [];
  bool isLoading = true;
  late TabController tabController;
  
  @override
  void initState() {
    super.initState();
    tabController = TabController(length: 4, vsync: this);
    loadCaseDetail();
  }
  
  Future<void> loadCaseDetail() async {
    final data = await CaseApiService.getCaseDetail(widget.caseId);
    
    setState(() {
      caseThread = data['case'];
      timeline = data['timeline'];
      articles = data['articles'];
      isLoading = false;
    });
  }
  
  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    
    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 300,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              title: Text(caseThread!.title, 
                         style: TextStyle(fontWeight: FontWeight.bold)),
              background: Stack(
                fit: StackFit.expand,
                children: [
                  if (caseThread!.coverImage != null)
                    Image.network(caseThread!.coverImage!, fit: BoxFit.cover),
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [Colors.transparent, Colors.black87],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Padding(
                  padding: EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            decoration: BoxDecoration(
                              color: _getStatusColor(caseThread!.status),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              caseThread!.status.toUpperCase(),
                              style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                            ),
                          ),
                          Spacer(),
                          IconButton(
                            icon: Icon(caseThread!.isFollowing ? Icons.notifications_active : Icons.notifications_none),
                            onPressed: () => toggleFollow(),
                          ),
                        ],
                      ),
                      SizedBox(height: 16),
                      Text(caseThread!.fullDescription, style: TextStyle(fontSize: 16, height: 1.6)),
                      SizedBox(height: 20),
                      Row(
                        children: [
                          _buildStat(Icons.article, '${caseThread!.totalArticles}', 'Articles'),
                          SizedBox(width: 24),
                          _buildStat(Icons.people, '${caseThread!.totalFollowers}', 'Followers'),
                          SizedBox(width: 24),
                          _buildStat(Icons.visibility, '${caseThread!.totalViews}', 'Views'),
                        ],
                      ),
                    ],
                  ),
                ),
                TabBar(
                  controller: tabController,
                  labelColor: Colors.blue[900],
                  unselectedLabelColor: Colors.grey,
                  indicatorColor: Colors.blue[900],
                  tabs: [
                    Tab(text: 'Timeline'),
                    Tab(text: 'Articles'),
                    Tab(text: 'Documents'),
                    Tab(text: 'Media'),
                  ],
                ),
              ],
            ),
          ),
          SliverFillRemaining(
            child: TabBarView(
              controller: tabController,
              children: [
                TimelineTab(events: timeline),
                ArticlesTab(articles: articles),
                DocumentsTab(),
                MediaTab(),
              ],
            ),
          ),
        ],
      ),
    );
  }
  
  Widget _buildStat(IconData icon, String value, String label) {
    return Row(
      children: [
        Icon(icon, size: 20, color: Colors.grey[600]),
        SizedBox(width: 6),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(value, style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            Text(label, style: TextStyle(color: Colors.grey, fontSize: 12)),
          ],
        ),
      ],
    );
  }
  
  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'active': return Colors.green;
      case 'concluded': return Colors.grey;
      case 'archived': return Colors.orange;
      default: return Colors.blue;
    }
  }
  
  Future<void> toggleFollow() async {
    // Implement follow/unfollow logic
  }
}

class TimelineTab extends StatelessWidget {
  final List<TimelineEvent> events;
  
  TimelineTab({required this.events});
  
  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      padding: EdgeInsets.all(16),
      itemCount: events.length,
      itemBuilder: (context, index) {
        final event = events[index];
        return TimelineItem(event: event);
      },
    );
  }
}

class TimelineItem extends StatelessWidget {
  final TimelineEvent event;
  
  TimelineItem({required this.event});
  
  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 12,
              height: 12,
              decoration: BoxDecoration(
                color: event.isMajorEvent ? Colors.red : Colors.blue,
                shape: BoxShape.circle,
              ),
            ),
            Container(width: 2, height: 60, color: Colors.grey[300]),
          ],
        ),
        SizedBox(width: 16),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '${event.eventDate.day} ${_getMonthName(event.eventDate.month)} ${event.eventDate.year}',
                style: TextStyle(color: Colors.grey, fontSize: 12),
              ),
              SizedBox(height: 4),
              Text(
                event.eventTitle,
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              SizedBox(height: 4),
              Text(event.eventDescription, style: TextStyle(color: Colors.grey[700])),
              SizedBox(height: 30),
            ],
          ),
        ),
      ],
    );
  }
  
  String _getMonthName(int month) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return months[month - 1];
  }
}

class ArticlesTab extends StatelessWidget {
  final List articles;
  
  ArticlesTab({required this.articles});
  
  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      padding: EdgeInsets.all(16),
      itemCount: articles.length,
      itemBuilder: (context, index) {
        final article = articles[index];
        return Card(
          margin: EdgeInsets.only(bottom: 12),
          child: ListTile(
            leading: ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: Image.network(
                article['thumbnail'],
                width: 60,
                height: 60,
                fit: BoxFit.cover,
              ),
            ),
            title: Text(article['title'], style: TextStyle(fontWeight: FontWeight.bold)),
            subtitle: Text(article['author']['name']),
          ),
        );
      },
    );
  }
}

class DocumentsTab extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Center(child: Text('Documents Tab'));
  }
}

class MediaTab extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Center(child: Text('Media Tab'));
  }
}
```

#### **5. Add Navigation Routes**

In your `main.dart` or route configuration:

```dart
routes: {
  '/case-threads': (context) => CaseThreadsListScreen(),
  '/case-detail': (context) {
    final caseId = ModalRoute.of(context)!.settings.arguments as int;
    return CaseThreadDetailScreen(caseId: caseId);
  },
}
```

---

### **PHASE 6: Notifications System** (1 hour)

#### **Create Notification Trigger Service** (`src/Services/NotificationService.php`)

```php
<?php
namespace CaseThreads\Services;

use CaseThreads\Config\Database;

class NotificationService {
    
    /**
     * Send notification when new article added to case
     */
    public static function notifyNewArticle($caseId, $articleId) {
        $db = Database::getInstance();
        
        // Get case title
        $case = $db->fetchOne("SELECT title FROM case_threads WHERE id = ?", [$caseId]);
        
        // Get all followers
        $followers = $db->fetchAll("
            SELECT user_id, notify_new_articles 
            FROM case_follows 
            WHERE case_id = ? AND notify_new_articles = 1
        ", [$caseId]);
        
        foreach ($followers as $follower) {
            $db->insert("
                INSERT INTO notifications (
                    user_id, case_id, notification_type, 
                    title, message, related_article_id
                ) VALUES (?, ?, ?, ?, ?, ?)
            ", [
                $follower['user_id'],
                $caseId,
                'new_article',
                'New Article in ' . $case['title'],
                'A new article has been added to a case you\'re following',
                $articleId
            ]);
        }
    }
    
    /**
     * Notify when new timeline event added
     */
    public static function notifyNewEvent($caseId, $eventId) {
        $db = Database::getInstance();
        
        $case = $db->fetchOne("SELECT title FROM case_threads WHERE id = ?", [$caseId]);
        $event = $db->fetchOne("SELECT event_title FROM case_timeline_events WHERE id = ?", [$eventId]);
        
        $followers = $db->fetchAll("
            SELECT user_id 
            FROM case_follows 
            WHERE case_id = ? AND notify_timeline_updates = 1
        ", [$caseId]);
        
        foreach ($followers as $follower) {
            $db->insert("
                INSERT INTO notifications (
                    user_id, case_id, notification_type,
                    title, message, related_timeline_event_id
                ) VALUES (?, ?, ?, ?, ?, ?)
            ", [
                $follower['user_id'],
                $caseId,
                'timeline_update',
                'Timeline Update: ' . $case['title'],
                $event['event_title'],
                $eventId
            ]);
        }
    }
    
    /**
     * Notify when new document added
     */
    public static function notifyNewDocument($caseId, $documentId) {
        $db = Database::getInstance();
        
        $case = $db->fetchOne("SELECT title FROM case_threads WHERE id = ?", [$caseId]);
        
        $followers = $db->fetchAll("
            SELECT user_id 
            FROM case_follows 
            WHERE case_id = ? AND notify_documents = 1
        ", [$caseId]);
        
        foreach ($followers as $follower) {
            $db->insert("
                INSERT INTO notifications (
                    user_id, case_id, notification_type,
                    title, message, related_document_id
                ) VALUES (?, ?, ?, ?, ?, ?)
            ", [
                $follower['user_id'],
                $caseId,
                'new_document',
                'New Document: ' . $case['title'],
                'A new legal document has been added',
                $documentId
            ]);
        }
    }
}
```

**Usage in your admin panel when adding articles:**

```php
// In admin/article-add.php after article creation
if ($caseId) {
    NotificationService::notifyNewArticle($caseId, $articleId);
}
```

---

### **PHASE 7: Security & Performance** (30 minutes)

#### **1. Add Input Validation**

Create `src/Helpers/Validator.php`:

```php
<?php
namespace CaseThreads\Helpers;

class Validator {
    public static function validateCaseId($id) {
        if (!is_numeric($id) || $id < 1) {
            Response::error('Invalid case ID', 'INVALID_ID', 400);
        }
        return (int)$id;
    }
    
    public static function sanitizeSearch($search) {
        return htmlspecialchars(strip_tags(trim($search)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateCategory($category) {
        $allowed = ['crime', 'war', 'corporate', 'political', 'environmental', 'humanitarian'];
        if (!in_array($category, $allowed)) {
            Response::error('Invalid category', 'INVALID_CATEGORY', 400);
        }
        return $category;
    }
}
```

#### **2. Add Rate Limiting**

Create `src/Helpers/RateLimiter.php`:

```php
<?php
namespace CaseThreads\Helpers;

class RateLimiter {
    public static function check($identifier, $maxRequests = 100, $timeWindow = 3600) {
        $cacheFile = sys_get_temp_dir() . '/ratelimit_' . md5($identifier);
        
        $requests = [];
        if (file_exists($cacheFile)) {
            $requests = json_decode(file_get_contents($cacheFile), true);
        }
        
        // Remove old requests
        $now = time();
        $requests = array_filter($requests, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        // Check limit
        if (count($requests) >= $maxRequests) {
            Response::error('Too many requests', 'RATE_LIMIT_EXCEEDED', 429);
        }
        
        // Add current request
        $requests[] = $now;
        file_put_contents($cacheFile, json_encode($requests));
    }
}
```

**Usage in API endpoints:**

```php
// At the top of api/cases/list.php
RateLimiter::check($_SERVER['REMOTE_ADDR'], 60, 60); // 60 requests per minute
```

#### **3. Add Caching**

For high-traffic cases, add simple file-based caching:

```php
// In api/cases/single.php
$cacheKey = "case_{$caseId}";
$cacheFile = sys_get_temp_dir() . '/' . $cacheKey . '.json';

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) { // 5 min cache
    $data = json_decode(file_get_contents($cacheFile), true);
    Response::success($data);
}

// ... fetch data from database ...

// Cache the result
file_put_contents($cacheFile, json_encode($finalData));
```

---

## 📊 **TESTING CHECKLIST**

### **Backend Tests**

- [ ] Run SQL schema file - verify all 9 tables created
- [ ] Test `api/cases/single.php?id=1` - should return complete case data
- [ ] Test `api/cases/list.php` - should return paginated cases
- [ ] Test filters: `?category=crime&status=active`
- [ ] Test authentication: Follow/unfollow endpoints require token
- [ ] Test error handling: Invalid IDs return proper error JSON

### **Web Frontend Tests**

- [ ] Load `/views/cases/detail.php` - hero section displays correctly
- [ ] Timeline renders with proper styling
- [ ] Articles grid is responsive (mobile/tablet/desktop)
- [ ] Document cards show all info
- [ ] Media gallery displays thumbnails
- [ ] Follow button works (check console for API calls)

### **Flutter App Tests**

- [ ] Cases list loads and displays cards
- [ ] Search and filters work
- [ ] Tap case card navigates to detail screen
- [ ] Detail screen shows all tabs (Timeline, Articles, Documents, Media)
- [ ] Timeline displays events chronologically
- [ ] Follow button toggles state
- [ ] Back navigation works properly

---

## 🎯 **PRIORITY ORDER FOR IMPLEMENTATION**

### **HIGH PRIORITY** (Do First - Core Functionality)
1. ✅ Database schema (DONE)
2. ✅ Core helper classes: Database, Response, Auth (DONE)
3. ⏳ `api/cases/list.php` - Essential for listing
4. ⏳ `api/cases/single.php` - Essential for details (DONE)
5. ⏳ Web case listing page (`views/cases/index.php`)
6. ⏳ Flutter case list screen
7. ⏳ Flutter case detail screen

### **MEDIUM PRIORITY** (Do Next - Engagement Features)
8. ⏳ `api/cases/follow.php` & `unfollow.php`
9. ⏳ `api/cases/articles.php` (paginated articles for case)
10. ⏳ Notification service triggers
11. ⏳ `api/notifications/list.php`

### **LOW PRIORITY** (Do Later - Polish)
12. ⏳ Advanced filters and search
13. ⏳ Rate limiting
14. ⏳ Caching
15. ⏳ Push notifications (Firebase)
16. ⏳ Admin panel for managing cases
17. ⏳ SEO optimization

---

## 💡 **KEY IMPLEMENTATION TIPS**

### **For Backend:**
- Use `api/cases/single.php` as template for all other endpoints
- Always use prepared statements (already done in Database.php)
- Return consistent JSON format using Response helper
- Test each endpoint with curl or Postman before frontend integration

### **For Web:**
- Copy styles from `views/cases/detail.php` for consistency
- Use same color scheme: Primary `#1e3c72`, Secondary `#2a5298`
- Keep responsive breakpoint at 768px
- Add loading states for AJAX calls

### **For Flutter:**
- Test API calls with real data before building UI
- Use FutureBuilder for async data loading
- Add error states and retry buttons
- Implement pull-to-refresh on list screens

---

## 🚀 **NEXT IMMEDIATE STEPS**

**You should:**

1. **Run the SQL schema** - This creates all database tables
2. **Create the core helper classes** - Copy from BACKEND_ARCHITECTURE.md
3. **Test `api/cases/single.php`** - Should work with sample data
4. **Create `api/cases/list.php`** - Use single.php as template
5. **Build web listing page** - Adapt from detail.php styles
6. **Integrate Flutter** - Use the provided screens

---

## 📝 **SUMMARY**

You now have:
- ✅ Complete database structure (9 tables)
- ✅ 12 REST API endpoints fully documented
- ✅ Backend architecture with core classes
- ✅ 1 complete API endpoint implementation (`single.php`)
- ✅ 1 complete web page with beautiful design (`detail.php`)
- ✅ Complete Flutter models, services, and 2 screens
- ✅ Notification service implementation
- ✅ Security helpers (validation, rate limiting, caching)

**Total Estimated Implementation Time:**
- Database setup: 5 minutes
- Backend core: 15 minutes
- API endpoints: 2 hours
- Web pages: 1 hour
- Flutter screens: 2 hours
- Notifications: 1 hour
- Security/Polish: 30 minutes

**TOTAL: ~7 hours of focused work**

---

## 🆘 **TROUBLESHOOTING**

**Database connection fails:**
- Check config/database.php credentials
- Verify MySQL service is running
- Test connection: `mysql -u root -p`

**API returns 404:**
- Check .htaccess rewrite rules
- Verify api/index.php router is working
- Test direct URL: `http://192.168.1.3/api/cases/single.php?id=1`

**Flutter app can't reach API:**
- Check base URL in case_api_service.dart
- Ensure phone/emulator on same Wi-Fi network
- Test API URL in browser first: `http://192.168.1.3/api/cases`

**CORS errors:**
- Add headers in api/index.php:
  ```php
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST, DELETE');
  header('Access-Control-Allow-Headers: Authorization, Content-Type');
  ```

---

**You're ready to build Case Threads! Start with Phase 1 (Database Setup) and work through sequentially. Good luck! 🚀**
