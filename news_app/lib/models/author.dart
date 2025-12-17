import 'article.dart';
import '../services/api_service.dart';

/// Sub-model of [Article]

class Author {
  final String? id;
  final String name;
  final String? imageUrl;

  Author({
    this.id,
    required this.name,
    this.imageUrl,
  });

  factory Author.fromMap(Map<String, dynamic> map) {
    return Author(
      id: map['id']?.toString(),
      name: map['name'] ?? 'Unknown',
      imageUrl: ApiService.fixImageUrl(map['image_url']),
      
    );
  }

  static Map<String, dynamic> getMap(Author d) {
    return {
      'id': d.id,
      'name': d.name,
      'image_url': d.imageUrl,
    };
  }
}