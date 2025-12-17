import 'dart:io';
import 'package:cloud_firestore/cloud_firestore.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:firebase_storage/firebase_storage.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/models/comment.dart';
import 'package:news_app/screens/all_articles/articles_view.dart';
import '../models/app_settings_model.dart';
import '../models/category.dart';
import '../models/chart_model.dart';
import '../models/purchase_history.dart';
import '../models/subscription.dart';
import '../models/tag.dart';
import '../models/user_model.dart';
import '../utils/toasts.dart';
import 'app_service.dart';

class FirebaseService {
  final FirebaseFirestore firestore = FirebaseFirestore.instance;

  static String getUID(String collectionName) => FirebaseFirestore.instance.collection(collectionName).doc().id;

  Future<List<Article>> getAllArticles() async {
    List<Article> data = [];
    await firestore
        .collection('articles')
        .where('status', isEqualTo: 'live')
        .orderBy('created_at', descending: true)
        .get()
        .then((QuerySnapshot? snapshot) {
      data = snapshot!.docs.map((e) => Article.fromFirestore(e)).toList();
    });
    return data;
  }

  Future<List<Article>> getRelatedArticlesByCategory(Article article, int limit) async {
    List<Article> data = [];
    await firestore
        .collection('articles')
        .where('category.id', isEqualTo: article.category!.id)
        .where(FieldPath.documentId, isNotEqualTo: article.id)
        .where('status', isEqualTo: 'live')
        .limit(limit)
        .get()
        .then((QuerySnapshot? snapshot) {
      data = snapshot!.docs.map((e) => Article.fromFirestore(e)).toList();
    }).catchError((e) {
      debugPrint(e);
    });
    return data;
  }

  Future<List<Article>> getRelatedArticlesByAudio(Article article, int limit) async {
    List<Article> data = [];
    await firestore
        .collection('articles')
        .where('content_type', isEqualTo: 'audio')
        .where(FieldPath.documentId, isNotEqualTo: article.id)
        .where('status', isEqualTo: 'live')
        .limit(limit)
        .get()
        .then((QuerySnapshot? snapshot) {
      data = snapshot!.docs.map((e) => Article.fromFirestore(e)).toList();
    }).catchError((e) {
      debugPrint(e);
    });
    return data;
  }

  Future<List<Article>> getFeaturedArticles() async {
    List<Article> data = [];
    await firestore
        .collection('articles')
        .where('status', isEqualTo: 'live')
        .where('featured', isEqualTo: true)
        .get()
        .then((QuerySnapshot? snapshot) {
      data = snapshot!.docs.map((e) => Article.fromFirestore(e)).toList();
    });
    return data;
  }

  Future<List<Article>> getPopularArticles(int limit) async {
    List<Article> data = [];
    await firestore
        .collection('articles')
        .where('status', isEqualTo: 'live')
        .where('featured', isEqualTo: false)
        .orderBy('views', descending: true)
        .limit(limit)
        .get()
        .then((QuerySnapshot? snapshot) {
      data = snapshot!.docs.map((e) => Article.fromFirestore(e)).toList();
    });
    return data;
  }

  Future<List<Article>> getArticlesByAuthorId({int limit = 3, required String authorId}) async {
    List<Article> data = [];
    await firestore
        .collection('articles')
        .where('author.id', isEqualTo: authorId)
        .where('status', isEqualTo: 'live')
        .limit(limit)
        .get()
        .then((QuerySnapshot? snapshot) {
      data = snapshot!.docs.map((e) => Article.fromFirestore(e)).toList();
    });
    return data;
  }

  Future<List<Category>> getAllCategories() async {
    List<Category> data = [];
    await firestore.collection('categories').get().then((QuerySnapshot? snapshot) {
      data = snapshot!.docs.map((e) => Category.fromFirestore(e)).toList();
    });
    return data;
  }

  Future<List<Tag>> getAllTags(int limit) async {
    List<Tag> data = [];
    await firestore.collection('tags').limit(limit).get().then((QuerySnapshot? snapshot) {
      data = snapshot!.docs.map((e) => Tag.fromFirestore(e)).toList();
    });
    return data;
  }

  Future<List<Tag>> getArticleTags(List tagIds) async {
    final List ids = tagIds.length > 10 ? tagIds.take(10).toList() : tagIds;
    List<Tag> data = [];
    await firestore.collection('tags').where(FieldPath.documentId, whereIn: ids).get().then((QuerySnapshot? snapshot) {
      data = snapshot!.docs.map((e) => Tag.fromFirestore(e)).toList();
    });
    return data;
  }

  Future<UserModel?> getUserData() async {
    UserModel? user;
    try {
      final String userId = FirebaseAuth.instance.currentUser!.uid;
      final DocumentSnapshot snap = await firestore.collection('users').doc(userId).get();
      user = UserModel.fromFirebase(snap);
    } catch (e) {
      debugPrint('error on getting user data: $e');
    }

    return user;
  }

  Future<UserModel?> getAuthorData(String authorId) async {
    final DocumentSnapshot snap = await firestore.collection('users').doc(authorId).get();
    UserModel? user = UserModel.fromFirebase(snap);
    return user;
  }

  Future<AppSettingsModel?> getAppSettingsData() async {
    AppSettingsModel? settings;
    try {
      final DocumentSnapshot snap = await firestore.collection('settings').doc('app').get();
      settings = AppSettingsModel.fromFirestore(snap);
    } catch (e) {
      debugPrint('error on getting app settings data');
    }
    return settings;
  }

  Future updateBookmark(UserModel user, Article article) async {
    final DocumentReference ref = firestore.collection('users').doc(user.id);
    final newArticleId = article.id;
    final List articles = user.bookmarkedIds ?? [];

    if (articles.contains(newArticleId)) {
      await ref.update({
        'bookmark_ids': FieldValue.arrayRemove([newArticleId])
      });
    } else {
      articles.add(newArticleId);
      await ref.update({'bookmark_ids': FieldValue.arrayUnion(articles)});
    }
  }

  Future updateLikedIds(UserModel user, Article article) async {
    final DocumentReference ref = firestore.collection('users').doc(user.id);
    final newArticleId = article.id;
    final List articles = user.likedIds ?? [];

    if (articles.contains(newArticleId)) {
      await ref.update({
        'liked_ids': FieldValue.arrayRemove([newArticleId])
      });
    } else {
      articles.add(newArticleId);
      await ref.update({'liked_ids': FieldValue.arrayUnion(articles)});
    }
  }

  Future updateSubscription(UserModel user, Subscription subscription) async {
    final DocumentReference ref = firestore.collection('users').doc(user.id);
    final data = Subscription.getMap(subscription);
    await ref.update({'subscription': data});
  }

  Future savePurchaseHistory(UserModel user, PurchaseHistory history) async {
    final Map<String, dynamic> data = PurchaseHistory.getMap(history);
    final DocumentReference ref = firestore.collection('purchases').doc();
    await ref.set(data);
  }

  Future saveUserData(UserModel user) async {
    try {
      final data = UserModel.getMap(user);
      await firestore.collection('users').doc(user.id).set(data);
    } catch (e) {
      debugPrint('error on saving user data: $e');
    }
  }

  Future updateUserProfile(UserModel user) async {
    final data = UserModel.getMap(user);
    try {
      await firestore.collection('users').doc(user.id).set(data, SetOptions(merge: true));
    } catch (e) {
      debugPrint('Error on updating user profile: $e');
      openToast('Failed to update data');
    }
  }

  Future<bool> isUserExists(String userId) async {
    DocumentSnapshot snap = await firestore.collection('users').doc(userId).get();
    if (snap.exists) {
      debugPrint('User Exists');
      return true;
    } else {
      debugPrint('New User');
      return false;
    }
  }

  //for wishlish and my Articles
  Future<QuerySnapshot> getArticlesQuery(chunk) {
    Query itemsQuery = FirebaseFirestore.instance.collection('articles').where(FieldPath.documentId, whereIn: chunk);
    return itemsQuery.get();
  }

  Future<QuerySnapshot> getArticlesSnapshotByCategory({required String categoryId, DocumentSnapshot? lastDocument}) async {
    QuerySnapshot snapshot;
    if (lastDocument == null) {
      snapshot = await firestore
          .collection('articles')
          .where('category.id', isEqualTo: categoryId)
          .where('status', isEqualTo: 'live')
          .orderBy('created_at', descending: true)
          .limit(10)
          .get();
    } else {
      snapshot = await firestore
          .collection('articles')
          .where('category.id', isEqualTo: categoryId)
          .where('status', isEqualTo: 'live')
          .orderBy('created_at', descending: true)
          .startAfterDocument(lastDocument)
          .limit(10)
          .get();
    }
    return snapshot;
  }

  Future<QuerySnapshot> getArticlesSnapshotByPopular({DocumentSnapshot? lastDocument}) async {
    QuerySnapshot snapshot;
    if (lastDocument == null) {
      snapshot = await firestore.collection('articles').orderBy('views', descending: true).where('status', isEqualTo: 'live').limit(10).get();
    } else {
      snapshot = await firestore
          .collection('articles')
          .orderBy('views', descending: true)
          .where('status', isEqualTo: 'live')
          .startAfterDocument(lastDocument)
          .limit(10)
          .get();
    }
    return snapshot;
  }

  Future<QuerySnapshot> getArticlesSnapshotByLatest({DocumentSnapshot? lastDocument}) async {
    QuerySnapshot snapshot;
    if (lastDocument == null) {
      snapshot = await firestore.collection('articles').where('status', isEqualTo: 'live').orderBy('created_at', descending: true).limit(5).get();
    } else {
      snapshot = await firestore
          .collection('articles')
          .where('status', isEqualTo: 'live')
          .orderBy('created_at', descending: true)
          .startAfterDocument(lastDocument)
          .limit(5)
          .get();
    }
    return snapshot;
  }

  Future<QuerySnapshot> getArticlesSnapshotByTag({required String tagId, DocumentSnapshot? lastDocument}) async {
    QuerySnapshot snapshot;
    if (lastDocument == null) {
      snapshot = await firestore.collection('articles').where('tag_ids', arrayContains: tagId).where('status', isEqualTo: 'live').limit(10).get();
    } else {
      snapshot = await firestore
          .collection('articles')
          .where('tag_ids', arrayContains: tagId)
          .where('status', isEqualTo: 'live')
          .startAfterDocument(lastDocument)
          .limit(10)
          .get();
    }
    return snapshot;
  }

  Future<QuerySnapshot> getArticlesSnapshotByAuhtor({required String authorId, DocumentSnapshot? lastDocument}) async {
    QuerySnapshot snapshot;
    if (lastDocument == null) {
      snapshot = await firestore.collection('articles').where('author.id', isEqualTo: authorId).where('status', isEqualTo: 'live').limit(10).get();
    } else {
      snapshot = await firestore
          .collection('articles')
          .where('author.id', isEqualTo: authorId)
          .where('status', isEqualTo: 'live')
          .startAfterDocument(lastDocument)
          .limit(10)
          .get();
    }
    return snapshot;
  }

  Future<QuerySnapshot> getVideoArticlesSnapshot({DocumentSnapshot? lastDocument, required ArticlesBy articlesBy}) async {
    final String orderBy = articlesBy == ArticlesBy.popular ? 'views' : 'created_at';
    QuerySnapshot snapshot;
    if (lastDocument == null) {
      snapshot = await firestore
          .collection('articles')
          .where('status', isEqualTo: 'live')
          .where('content_type', isEqualTo: 'video')
          .orderBy(orderBy, descending: true)
          .limit(5)
          .get();
    } else {
      snapshot = await firestore
          .collection('articles')
          .where('status', isEqualTo: 'live')
          .where('content_type', isEqualTo: 'video')
          .orderBy(orderBy, descending: true)
          .startAfterDocument(lastDocument)
          .limit(5)
          .get();
    }
    return snapshot;
  }

  Future<QuerySnapshot> getPodcastArticlesSnapshot({DocumentSnapshot? lastDocument, required ArticlesBy articlesBy}) async {
    final String orderBy = articlesBy == ArticlesBy.popular ? 'views' : 'created_at';
    QuerySnapshot snapshot;
    if (lastDocument == null) {
      snapshot = await firestore
          .collection('articles')
          .where('status', isEqualTo: 'live')
          .where('content_type', isEqualTo: 'audio')
          .orderBy(orderBy, descending: true)
          .limit(5)
          .get();
    } else {
      snapshot = await firestore
          .collection('articles')
          .where('status', isEqualTo: 'live')
          .where('content_type', isEqualTo: 'audio')
          .orderBy(orderBy, descending: true)
          .startAfterDocument(lastDocument)
          .limit(5)
          .get();
    }
    return snapshot;
  }

  Future<QuerySnapshot> getCommentsSnapshot({required String articleId, DocumentSnapshot? lastDocument}) async {
    QuerySnapshot snapshot;
    if (lastDocument == null) {
      snapshot =
          await firestore.collection('comments').where('article_id', isEqualTo: articleId).orderBy('created_at', descending: true).limit(10).get();
    } else {
      snapshot = await firestore
          .collection('comments')
          .where('article_id', isEqualTo: articleId)
          .orderBy('created_at', descending: true)
          .startAfterDocument(lastDocument)
          .limit(10)
          .get();
    }
    return snapshot;
  }

  Future<String?> uploadImageToHosting(XFile imageFile) async {
    String? imageUrl;
    final File image = File(imageFile.path);
    final String imageName = imageFile.name;
    final Reference storageReference = FirebaseStorage.instance.ref().child('user_images/$imageName');
    final UploadTask uploadTask = storageReference.putFile(image);
    await uploadTask.whenComplete(() async {
      imageUrl = await storageReference.getDownloadURL();
    });
    return imageUrl;
  }

  Future<int> getAuthorCommentsCount(String auhtorId) async {
    final CollectionReference collectionReference = firestore.collection('comments');
    final AggregateQuerySnapshot snap = await collectionReference.where('article_author_id', isEqualTo: auhtorId).count().get();
    int count = snap.count ?? 0;
    return count;
  }

  Future<int> getAuthorArticleCount(String auhtorId) async {
    final CollectionReference collectionReference = firestore.collection('articles');
    final AggregateQuerySnapshot snap =
        await collectionReference.where('author.id', isEqualTo: auhtorId).where('status', isEqualTo: 'live').count().get();
    int count = snap.count ?? 0;
    return count;
  }

  Future deleteUserDatafromDatabase(String userId) async {
    await firestore.collection('users').doc(userId).delete();
  }

  Future updateUserStats() async {
    final String id = AppService.getTodaysID();
    final DocumentReference docRef = firestore.collection('user_stats').doc(id);
    await firestore.runTransaction((transaction) {
      return transaction.get(docRef).then((DocumentSnapshot snapshot) {
        if (snapshot.exists) {
          final ChartModel chartModel = ChartModel.fromFirestore(snapshot);
          final newChartModel = ChartModel(id: chartModel.id, count: chartModel.count + 1, timestamp: chartModel.timestamp);
          final Map<String, dynamic> data = ChartModel.getMap(newChartModel);
          transaction.set(docRef, data, SetOptions(merge: true));
        } else {
          final newChartModel = ChartModel(id: id, count: 1, timestamp: DateTime.now().toUtc());
          final Map<String, dynamic> data = ChartModel.getMap(newChartModel);
          transaction.set(docRef, data, SetOptions(merge: true));
        }
      });
    });
  }

  Future updatePurchaseStats() async {
    final String id = AppService.getTodaysID();
    final DocumentReference docRef = firestore.collection('purchase_stats').doc(id);
    await firestore.runTransaction((transaction) {
      return transaction.get(docRef).then((DocumentSnapshot snapshot) {
        if (snapshot.exists) {
          final ChartModel chartModel = ChartModel.fromFirestore(snapshot);
          final newChartModel = ChartModel(id: chartModel.id, count: chartModel.count + 1, timestamp: chartModel.timestamp);
          final Map<String, dynamic> data = ChartModel.getMap(newChartModel);
          transaction.set(docRef, data, SetOptions(merge: true));
        } else {
          final newChartModel = ChartModel(id: id, count: 1, timestamp: DateTime.now().toUtc());
          final Map<String, dynamic> data = ChartModel.getMap(newChartModel);
          transaction.set(docRef, data, SetOptions(merge: true));
        }
      });
    });
  }

  Future<void> updateViewsCount(String articleId) async {
    await firestore.runTransaction((transaction) async {
      final DocumentSnapshot doc = await transaction.get(
        firestore.collection('articles').doc(articleId),
      );

      final Article article = Article.fromFirestore(doc);
      final int currentViews = article.views ?? 0;
      transaction.update(
        firestore.collection('articles').doc(article.id),
        {'views': currentViews + 1},
      );
    });
  }

  Future<void> updateLikesCount(String articleId, bool isIncrement) async {
    await firestore.runTransaction((transaction) async {
      final DocumentSnapshot doc = await transaction.get(
        firestore.collection('articles').doc(articleId),
      );

      final Article article = Article.fromFirestore(doc);
      final int currentLikes = article.likes ?? 0;
      final count = isIncrement ? currentLikes + 1 : currentLikes - 1;
      transaction.update(
        firestore.collection('articles').doc(article.id),
        {'likes': count},
      );
    });
  }

  Future saveComment(Comment comment) async {
    try {
      final data = Comment.getMap(comment);
      await firestore.collection('comments').doc(comment.id).set(data);
    } catch (e) {
      debugPrint('error on saving user data: $e');
    }
  }

  Future deleteComment(Comment comment) async {
    try {
      await firestore.collection('comments').doc(comment.id).delete();
    } catch (e) {
      debugPrint('error on deleting comment: $e');
    }
  }

  Future<Article?> getArticle(String id) async {
    Article? article;
    final DocumentSnapshot snap = await firestore.collection('articles').doc(id).get();
    article = Article.fromFirestore(snap);
    return article;
  }
}
