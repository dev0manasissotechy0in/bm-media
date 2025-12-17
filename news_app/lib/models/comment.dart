import 'comment_user.dart';
import '../services/firestore_stub.dart';

class Comment {
  final String id, articleId, articleAuthorId, articleTitle, comment;
  final CommentUser commentUser;
  final DateTime createdAt;

  Comment({
    required this.id,
    required this.articleId,
    required this.articleAuthorId,
    required this.articleTitle,
    required this.commentUser,
    required this.createdAt,
    required this.comment,
  });

  factory Comment.fromFirebase(DocumentSnapshot snap) {
    Map d = snap.data() as Map<String, dynamic>;
    return Comment(
      id: snap.id,
      articleId: d['article_id'],
      articleAuthorId: d['article_author_id'],
      articleTitle: d['article_title'],
      comment: d['comment'],
      createdAt: (d['created_at'] as Timestamp).toDate(),
      commentUser: CommentUser.fromFirebase(d['user']),
    );
  }

  factory Comment.fromJson(Map<String, dynamic> d) {
    return Comment(
      id: d['id'].toString(),
      articleId: d['article_id'].toString(),
      articleAuthorId: d['article_author_id']?.toString() ?? '',
      articleTitle: d['article_title'] ?? '',
      comment: d['comment'],
      createdAt: DateTime.parse(d['created_at']),
      commentUser: CommentUser.fromJson(d['user']),
    );
  }

  static Map<String, dynamic> getMap (Comment d){
    return {
      'article_id': d.articleId,
      'article_author_id': d.articleAuthorId,
      'article_title': d.articleTitle,
      'comment': d.comment,
      'created_at': d.createdAt,
      'user': CommentUser.getMap(d.commentUser),
    };
  }

  
}