class Tag {
  final String name, id;
  DateTime createdAt;

  Tag({
    required this.name,
    required this.id,
    required this.createdAt
  });

  factory Tag.fromJson(Map<String, dynamic> d) {
    return Tag(
        id: d['id'].toString(),
        name: d['name'],
        createdAt: DateTime.parse(d['created_at'])
    );
  }
}