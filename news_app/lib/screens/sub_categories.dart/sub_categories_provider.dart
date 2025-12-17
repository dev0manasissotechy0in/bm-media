import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import '../../services/firestore_stub.dart';
import '../../services/firebase_service_stub.dart';

final subcategoryArticlesProvider = StateNotifierProvider.autoDispose<ArticleData, List<Article>>((ref) => ArticleData());

final hasDataSubcategoriesProvider = StateProvider.autoDispose<bool>((ref) => false);

final isLoadingSubcategoriesProvider = StateProvider.autoDispose<bool>((ref) => true);

class ArticleData extends StateNotifier<List<Article>> {
  ArticleData() : super([]);

  DocumentSnapshot? lastDocument;

  getData(String categoryId, WidgetRef ref) async {
    if (lastDocument == null) {
      await FirebaseService().getArticlesSnapshotByCategory(categoryId: categoryId).then((QuerySnapshot snapshot) {
        state = snapshot.docs.map((e) => Article.fromFirestore(e)).toList();
        lastDocument = snapshot.docs.last;
        ref.read(isLoadingSubcategoriesProvider.notifier).update((state) => false);
      }).catchError((e) => _handleError(ref, e.toString()));
    } else {
      ref.read(hasDataSubcategoriesProvider.notifier).update((state) => true);
      await FirebaseService().getArticlesSnapshotByCategory(categoryId: categoryId, lastDocument: lastDocument).then((QuerySnapshot? snapshot) {
        state.addAll(snapshot!.docs.map((e) => Article.fromFirestore(e)).toList());
        lastDocument = snapshot.docs.last;
        ref.read(hasDataSubcategoriesProvider.notifier).update((state) => false);
      }).catchError((e) => _handleError(ref, e.toString()));
    }
  }

  _handleError(ref, String error) {
    ref.read(isLoadingSubcategoriesProvider.notifier).update((state) => false);
    ref.read(hasDataSubcategoriesProvider.notifier).update((state) => false);
    debugPrint(error);
  }
}
