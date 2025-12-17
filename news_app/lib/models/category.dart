import '../services/api_service.dart';

class Category {
  final String name, id, thumbnailUrl;
  final DateTime createdAt;
  final String? parentId;

  Category({
    required this.name,
    required this.id,
    required this.thumbnailUrl,
    required this.createdAt,
    this.parentId,
  });

  factory Category.fromJson(Map<String, dynamic> d) {
    return Category(
        id: d['id'].toString(),
        name: d['name'],
        thumbnailUrl: ApiService.fixImageUrl(d['image_url']) ?? '',
        createdAt: DateTime.parse(d['created_at']),
        parentId: d['parent_id']?.toString(),
    );
  }

  static Map<String, dynamic> getMap(Category d) {
    return {
      'name': d.name,
      'image_url': d.thumbnailUrl,
      'created_at': d.createdAt.toIso8601String(),
      'parent_id': d.parentId,
    };
  }
}