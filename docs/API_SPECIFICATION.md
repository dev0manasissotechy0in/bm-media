# CASE THREADS - REST API SPECIFICATION

Base URL: `http://yourdomain.com/api`

## Authentication
All authenticated endpoints require:
```
Authorization: Bearer {token}
```

---

## 1. CASE THREADS LISTING

### GET /api/cases

Get list of all case threads with filtering and pagination.

**Query Parameters:**
```
?category=Crime
&status=ongoing
&search=nirbhaya
&sort=latest_activity|most_followed|most_articles
&page=1
&limit=20
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cases": [
      {
        "id": 1,
        "title": "Nirbhaya Case - Delhi Gang Rape",
        "slug": "nirbhaya-case-delhi-gang-rape",
        "short_description": "The 2012 Delhi gang rape...",
        "status": "closed",
        "category": "Crime",
        "primary_location": "New Delhi, India",
        "start_date": "2012-12-16",
        "thumbnail": "https://example.com/uploads/cases/nirbhaya.jpg",
        "total_articles": 245,
        "total_followers": 15420,
        "total_views": 892341,
        "last_activity_at": "2023-12-01 15:30:00",
        "created_at": "2023-01-15 10:00:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 15,
      "total_results": 289,
      "per_page": 20
    }
  }
}
```

---

## 2. SINGLE CASE DETAILS (COMPLETE)

### GET /api/cases/{id}

Get complete details of a single case thread including all nested data.

**Response:**
```json
{
  "success": true,
  "data": {
    "case": {
      "id": 1,
      "title": "Nirbhaya Case - Delhi Gang Rape",
      "slug": "nirbhaya-case-delhi-gang-rape",
      "short_description": "The 2012 Delhi gang rape...",
      "full_description": "On December 16, 2012...",
      "status": "closed",
      "category": "Crime",
      "primary_location": "New Delhi, India",
      "start_date": "2012-12-16",
      "end_date": "2020-03-20",
      "thumbnail": "https://example.com/uploads/cases/nirbhaya.jpg",
      "cover_image": "https://example.com/uploads/cases/nirbhaya-cover.jpg",
      "total_articles": 245,
      "total_followers": 15420,
      "total_views": 892341,
      "is_following": true,
      "last_activity_at": "2023-12-01 15:30:00"
    },
    "timeline": {
      "total": 12,
      "events": [
        {
          "id": 1,
          "event_title": "Incident Occurred",
          "event_description": "Brutal gang rape and assault...",
          "event_date": "2012-12-16",
          "event_time": "21:30:00",
          "event_type": "incident",
          "is_major_event": true,
          "linked_articles_count": 15,
          "created_at": "2023-01-15 10:00:00"
        },
        {
          "id": 2,
          "event_title": "Death Penalty Pronounced",
          "event_description": "Fast-track court pronounces death...",
          "event_date": "2013-09-13",
          "event_type": "verdict",
          "is_major_event": true,
          "linked_articles_count": 42,
          "created_at": "2023-01-15 10:00:00"
        }
      ]
    },
    "recent_articles": {
      "total": 245,
      "showing": 10,
      "articles": [
        {
          "id": 1042,
          "title": "Nirbhaya Convicts Hanged at Tihar Jail",
          "slug": "nirbhaya-convicts-hanged",
          "description": "All four convicts in the Nirbhaya...",
          "thumbnail": "https://example.com/uploads/articles/...",
          "published_at": "2020-03-20 05:30:00",
          "source": "The Times of India",
          "author": {
            "name": "John Doe",
            "avatar": "https://example.com/..."
          },
          "is_key_article": true,
          "relevance_score": 10
        }
      ]
    },
    "documents": {
      "total": 8,
      "documents": [
        {
          "id": 1,
          "title": "Supreme Court Judgment - Death Penalty Upheld",
          "document_type": "judgment",
          "description": "Final Supreme Court judgment...",
          "plain_language_summary": "The Supreme Court rejected...",
          "file_url": "https://example.com/uploads/documents/sc-judgment.pdf",
          "file_type": "pdf",
          "file_size": 2456789,
          "document_date": "2017-05-05",
          "source": "Supreme Court of India",
          "official_reference_number": "Criminal Appeal No. 1234/2015"
        }
      ]
    },
    "media": {
      "total": 24,
      "photos": 15,
      "videos": 8,
      "audio": 1,
      "items": [
        {
          "id": 1,
          "media_type": "photo",
          "title": "India Gate Protest - December 2012",
          "caption": "Thousands gathered at India Gate demanding justice",
          "file_url": "https://example.com/uploads/media/protest-1.jpg",
          "thumbnail_url": "https://example.com/uploads/media/thumbs/protest-1.jpg",
          "media_date": "2012-12-22",
          "source": "Reuters",
          "is_featured": true
        },
        {
          "id": 2,
          "media_type": "video",
          "title": "Court Proceedings - Fast Track Court",
          "caption": "Video from inside the courtroom",
          "file_url": "https://example.com/uploads/media/court-video.mp4",
          "thumbnail_url": "https://example.com/uploads/media/thumbs/court-video.jpg",
          "duration": 180,
          "media_date": "2013-09-10",
          "source": "NDTV"
        }
      ]
    },
    "reviews": {
      "total": 5,
      "reviews": [
        {
          "id": 1,
          "title": "Legal Analysis: Impact on Criminal Law Amendments",
          "review_type": "legal_analysis",
          "summary": "The Nirbhaya case led to significant changes...",
          "author_name": "Dr. Rajesh Kumar",
          "author_designation": "Senior Advocate, Supreme Court",
          "organization": "Supreme Court Bar Association",
          "review_date": "2020-03-21",
          "external_url": "https://example.com/analysis/...",
          "is_verified": true
        }
      ]
    }
  }
}
```

---

## 3. CASE ARTICLES (Paginated)

### GET /api/cases/{id}/articles

Get all articles related to a case with filtering.

**Query Parameters:**
```
?page=1
&limit=20
&publisher=times-of-india
&date_from=2020-01-01
&date_to=2020-12-31
&sort=latest|relevance
&key_articles_only=true
```

**Response:**
```json
{
  "success": true,
  "data": {
    "case_id": 1,
    "case_title": "Nirbhaya Case",
    "articles": [
      {
        "id": 1042,
        "title": "Nirbhaya Convicts Hanged at Tihar Jail",
        "slug": "nirbhaya-convicts-hanged",
        "description": "All four convicts...",
        "thumbnail": "https://...",
        "published_at": "2020-03-20 05:30:00",
        "source": "The Times of India",
        "content_type": "article",
        "is_key_article": true,
        "relevance_score": 10,
        "article_context": "This article covers the final execution"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 13,
      "total_results": 245,
      "per_page": 20
    }
  }
}
```

---

## 4. CASE TIMELINE

### GET /api/cases/{id}/timeline

Get chronological timeline events for a case.

**Response:**
```json
{
  "success": true,
  "data": {
    "case_id": 1,
    "case_title": "Nirbhaya Case",
    "total_events": 12,
    "events": [
      {
        "id": 1,
        "event_title": "Incident Occurred",
        "event_description": "Brutal gang rape...",
        "event_date": "2012-12-16",
        "event_time": "21:30:00",
        "event_type": "incident",
        "is_major_event": true,
        "linked_articles": [
          {
            "id": 1,
            "title": "Delhi Gang Rape: Woman Critical",
            "slug": "delhi-gang-rape-woman-critical"
          }
        ],
        "linked_documents": [
          {
            "id": 1,
            "title": "FIR Copy",
            "document_type": "fir"
          }
        ]
      }
    ]
  }
}
```

---

## 5. CASE MEDIA

### GET /api/cases/{id}/media

Get all media items for a case.

**Query Parameters:**
```
?type=photo|video|audio
&page=1
&limit=20
```

**Response:**
```json
{
  "success": true,
  "data": {
    "case_id": 1,
    "media": [
      {
        "id": 1,
        "media_type": "photo",
        "title": "India Gate Protest",
        "caption": "Thousands gathered...",
        "file_url": "https://...",
        "thumbnail_url": "https://...",
        "media_date": "2012-12-22",
        "source": "Reuters"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 2,
      "total_results": 24,
      "per_page": 20
    }
  }
}
```

---

## 6. FOLLOW CASE

### POST /api/cases/{id}/follow

Follow a case thread to receive notifications.

**Request Body:**
```json
{
  "notify_new_articles": true,
  "notify_timeline_events": true,
  "notify_documents": true,
  "notify_verdicts": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "You are now following Nirbhaya Case",
  "data": {
    "case_id": 1,
    "is_following": true,
    "followed_at": "2024-12-07 10:30:00"
  }
}
```

---

## 7. UNFOLLOW CASE

### DELETE /api/cases/{id}/follow

Unfollow a case thread.

**Response:**
```json
{
  "success": true,
  "message": "You have unfollowed Nirbhaya Case",
  "data": {
    "case_id": 1,
    "is_following": false
  }
}
```

---

## 8. USER'S FOLLOWED CASES

### GET /api/user/followed-cases

Get all cases that the authenticated user is following.

**Query Parameters:**
```
?page=1
&limit=20
&status=ongoing
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_following": 12,
    "cases": [
      {
        "id": 1,
        "title": "Nirbhaya Case",
        "slug": "nirbhaya-case",
        "thumbnail": "https://...",
        "status": "closed",
        "category": "Crime",
        "total_articles": 245,
        "unread_updates": 3,
        "last_activity_at": "2023-12-01 15:30:00",
        "followed_at": "2023-06-15 10:00:00",
        "notification_preferences": {
          "notify_new_articles": true,
          "notify_timeline_events": true,
          "notify_documents": true,
          "notify_verdicts": true
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "per_page": 20
    }
  }
}
```

---

## 9. NOTIFICATIONS

### GET /api/notifications

Get user's notifications with filtering.

**Query Parameters:**
```
?page=1
&limit=20
&unread_only=true
&case_id=1
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_unread": 5,
    "notifications": [
      {
        "id": 123,
        "notification_type": "new_article",
        "title": "New article added to Nirbhaya Case",
        "message": "15 new articles have been added covering the anniversary memorial",
        "case": {
          "id": 1,
          "title": "Nirbhaya Case",
          "thumbnail": "https://..."
        },
        "action_url": "/cases/1/articles/1042",
        "entity_type": "article",
        "entity_id": 1042,
        "is_read": false,
        "created_at": "2024-12-07 09:15:00"
      },
      {
        "id": 124,
        "notification_type": "verdict",
        "title": "Major verdict in Russia-Ukraine War",
        "message": "International Court of Justice issued ruling",
        "case": {
          "id": 5,
          "title": "Russia-Ukraine War",
          "thumbnail": "https://..."
        },
        "is_read": false,
        "created_at": "2024-12-07 08:00:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 3,
      "total_results": 48,
      "per_page": 20
    }
  }
}
```

---

## 10. MARK NOTIFICATION AS READ

### POST /api/notifications/{id}/read

Mark a notification as read.

**Response:**
```json
{
  "success": true,
  "message": "Notification marked as read",
  "data": {
    "notification_id": 123,
    "is_read": true,
    "read_at": "2024-12-07 10:45:00"
  }
}
```

---

## 11. MARK ALL NOTIFICATIONS AS READ

### POST /api/notifications/read-all

Mark all notifications as read for the authenticated user.

**Response:**
```json
{
  "success": true,
  "message": "All notifications marked as read",
  "data": {
    "total_marked": 48
  }
}
```

---

## 12. CASE SEARCH

### GET /api/cases/search

Advanced search for cases.

**Query Parameters:**
```
?q=nirbhaya delhi rape
&category=Crime,Scam
&status=ongoing,closed
&date_from=2012-01-01
&date_to=2023-12-31
&location=Delhi
&sort=relevance|latest|popular
&page=1
&limit=20
```

**Response:**
```json
{
  "success": true,
  "data": {
    "query": "nirbhaya delhi rape",
    "total_results": 1,
    "cases": [
      {
        "id": 1,
        "title": "Nirbhaya Case - Delhi Gang Rape",
        "slug": "nirbhaya-case-delhi-gang-rape",
        "short_description": "The 2012 Delhi gang rape...",
        "match_score": 0.95,
        "highlighted_text": "...2012 <mark>Delhi</mark> gang <mark>rape</mark>..."
      }
    ]
  }
}
```

---

## ERROR RESPONSES

All endpoints return errors in this format:

```json
{
  "success": false,
  "error": {
    "code": "CASE_NOT_FOUND",
    "message": "Case thread not found",
    "details": "No case exists with ID 999"
  }
}
```

Common error codes:
- `CASE_NOT_FOUND` - Case doesn't exist
- `UNAUTHORIZED` - Invalid or missing auth token
- `FORBIDDEN` - User doesn't have permission
- `VALIDATION_ERROR` - Invalid request parameters
- `ALREADY_FOLLOWING` - User already follows this case
- `NOT_FOLLOWING` - User doesn't follow this case
- `RATE_LIMIT_EXCEEDED` - Too many requests
