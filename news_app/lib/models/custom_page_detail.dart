class CustomPageDetail {
  final int id;
  final String title;
  final String slug;
  final String type;
  final String content;
  final int? categoryId;
  final int? tagId;
  final String? metaTitle;
  final String? metaDescription;
  final int views;
  final String createdAt;
  final String updatedAt;
  final String shareUrl;
  final List<CustomPageArticle>? articles;

  CustomPageDetail({
    required this.id,
    required this.title,
    required this.slug,
    required this.type,
    required this.content,
    this.categoryId,
    this.tagId,
    this.metaTitle,
    this.metaDescription,
    required this.views,
    required this.createdAt,
    required this.updatedAt,
    required this.shareUrl,
    this.articles,
  });

  factory CustomPageDetail.fromJson(Map<String, dynamic> json) {
    return CustomPageDetail(
      id: json['id'] as int,
      title: json['title'] as String,
      slug: json['slug'] as String,
      type: json['type'] as String,
      content: json['content'] as String? ?? '',
      categoryId: json['category_id'] as int?,
      tagId: json['tag_id'] as int?,
      metaTitle: json['meta_title'] as String?,
      metaDescription: json['meta_description'] as String?,
      views: json['views'] as int? ?? 0,
      createdAt: json['created_at'] as String,
      updatedAt: json['updated_at'] as String,
      shareUrl: json['share_url'] as String,
      articles: json['articles'] != null
          ? (json['articles'] as List)
              .map((article) => CustomPageArticle.fromJson(article))
              .toList()
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'slug': slug,
      'type': type,
      'content': content,
      'category_id': categoryId,
      'tag_id': tagId,
      'meta_title': metaTitle,
      'meta_description': metaDescription,
      'views': views,
      'created_at': createdAt,
      'updated_at': updatedAt,
      'share_url': shareUrl,
      'articles': articles?.map((a) => a.toJson()).toList(),
    };
  }
}

class CustomPageArticle {
  final String id;
  final String title;
  final String slug;
  final String description;
  final String? imageUrl;
  final String contentType;
  final int views;
  final int likes;
  final String publishedAt;
  final Map<String, String> category;

  CustomPageArticle({
    required this.id,
    required this.title,
    required this.slug,
    required this.description,
    this.imageUrl,
    required this.contentType,
    required this.views,
    required this.likes,
    required this.publishedAt,
    required this.category,
  });

  factory CustomPageArticle.fromJson(Map<String, dynamic> json) {
    return CustomPageArticle(
      id: json['id'] as String,
      title: json['title'] as String,
      slug: json['slug'] as String,
      description: json['description'] as String? ?? '',
      imageUrl: json['image_url'] as String?,
      contentType: json['content_type'] as String? ?? 'standard',
      views: json['views'] as int? ?? 0,
      likes: json['likes'] as int? ?? 0,
      publishedAt: json['published_at'] as String,
      category: Map<String, String>.from(json['category'] ?? {}),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'slug': slug,
      'description': description,
      'image_url': imageUrl,
      'content_type': contentType,
      'views': views,
      'likes': likes,
      'published_at': publishedAt,
      'category': category,
    };
  }
}
