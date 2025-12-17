class CustomPage {
  final int id;
  final String title;
  final String slug;
  final String url;
  final String type;
  final String? description;
  final int order;

  CustomPage({
    required this.id,
    required this.title,
    required this.slug,
    required this.url,
    required this.type,
    this.description,
    required this.order,
  });

  factory CustomPage.fromJson(Map<String, dynamic> json) {
    return CustomPage(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      slug: json['slug'] ?? '',
      url: json['url'] ?? '',
      type: json['type'] ?? 'text',
      description: json['description'],
      order: json['order'] ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'title': title,
      'slug': slug,
      'url': url,
      'type': type,
      'description': description,
      'order': order,
    };
  }
}
