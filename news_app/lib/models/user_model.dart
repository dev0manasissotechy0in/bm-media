import 'package:news_app/services/api_service.dart';
import 'package:news_app/services/firestore_stub.dart';
import 'author_info.dart';
import 'subscription.dart';

class UserModel {
  final String id, email, name;
  DateTime? createdAt;
  DateTime? updatedAt;
  final String? imageUrl;
  List? role;
  List<String>? bookmarkedIds, likedIds;
  bool? isDisbaled;
  AuthorInfo? authorInfo;
  Subscription? subscription;
  String? platform;
  bool? isPremiumUser;

  UserModel({
    required this.id,
    required this.email,
    this.imageUrl,
    required this.name,
    this.role,
    this.bookmarkedIds,
    this.likedIds,
    this.isDisbaled,
    this.createdAt,
    this.updatedAt,
    this.authorInfo,
    this.subscription,
    this.platform,
    this.isPremiumUser,
  });

  factory UserModel.fromFirebase(DocumentSnapshot snap) {
    Map d = snap.data() as Map<String, dynamic>;
    return UserModel(
      id: snap.id,
      email: d['email'],
      imageUrl: ApiService.fixImageUrl(d['image_url']),
      name: d['name'],
      role: d['role'] ?? [],
      isDisbaled: d['disabled'] ?? false,
      createdAt: d['created_at'] == null ? null : (d['created_at'] as Timestamp).toDate().toLocal(),
      updatedAt: d['updated_at'] == null ? null : (d['updated_at'] as Timestamp).toDate().toLocal(),
      authorInfo: d['author_info'] == null ? null : AuthorInfo.fromMap(d['author_info']),
      subscription: d['subscription'] == null ? null : Subscription.fromFirestore(d['subscription']),
      platform: d['platform'],
      bookmarkedIds: d['bookmark_ids'] != null ? List<String>.from(d['bookmark_ids']) : [],
      likedIds: d['liked_ids'] != null ? List<String>.from(d['liked_ids']) : [],
      isPremiumUser: _isPremiumUser(d),
    );
  }

  factory UserModel.fromJson(Map<String, dynamic> d) {
    return UserModel(
      id: d['id'].toString(),
      email: d['email'] ?? '',
      imageUrl: ApiService.fixImageUrl(d['image_url'] ?? d['profile_photo']),
      name: d['name'] ?? d['full_name'] ?? '',
      role: d['role'] ?? [],
      isDisbaled: d['disabled'] ?? false,
      createdAt: d['created_at'] == null ? null : DateTime.tryParse(d['created_at'])?.toLocal(),
      updatedAt: d['updated_at'] == null ? null : DateTime.tryParse(d['updated_at'])?.toLocal(),
      authorInfo: d['author_info'] == null ? null : AuthorInfo.fromMap(d['author_info']),
      subscription: d['subscription'] == null ? null : Subscription.fromJson(d['subscription']),
      platform: d['platform'],
      bookmarkedIds: d['bookmark_ids'] != null ? List<String>.from(d['bookmark_ids']) : [],
      likedIds: d['liked_ids'] != null ? List<String>.from(d['liked_ids']) : [],
      isPremiumUser: _isPremiumUser(d),
    );
  }

  static Map<String, dynamic> getMap(UserModel d) {
    return {
      'name': d.name,
      'email': d.email,
      'created_at': d.createdAt,
      'image_url': d.imageUrl,
      'platform': d.platform,
    };
  }

  static bool _isPremiumUser(Map d) {
    bool value = false;
    final Subscription? subscription = d['subscription'] == null ? null : Subscription.fromFirestore(d['subscription']);
    if (subscription != null) {
      final DateTime expireAt = subscription.expireAt;
      final bool isExpired = _isExpired(expireAt);
      if (!isExpired) {
        value = true;
      }
    }

    return value;
  }

  static bool _isExpired(DateTime expireAt) {
    final DateTime expireDate = expireAt;
    final DateTime now = DateTime.now().toUtc();
    final difference = expireDate.difference(now).inDays;
    if (difference >= 0) {
      return false;
    } else {
      return true;
    }
  }
}
