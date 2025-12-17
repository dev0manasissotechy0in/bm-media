
class ArticleCategory {
  final String id, name;

  ArticleCategory({required this.id, required this.name});

  factory ArticleCategory.fromMap(Map<String, dynamic> map) {
    return ArticleCategory(
      id: map['id']?.toString() ?? '0',
      name: map['name'] ?? 'Uncategorized',
    );
  }

  static Map<String, dynamic> getMap(ArticleCategory d) {
    return {
      'id': d.id,
      'name': d.name,
    };
  }
}