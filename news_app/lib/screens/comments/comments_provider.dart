import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../models/comment.dart';
import '../../services/firestore_stub.dart';
import '../../services/firebase_service_stub.dart';

final commentsProvider = StateNotifierProvider.autoDispose<CommentsData, List<Comment>>((ref) => CommentsData());

final hasCommentsProvider = StateProvider.autoDispose<bool>((ref) => false);

final isCommentsLoadingProvider = StateProvider.autoDispose<bool>((ref) => true);

class CommentsData extends StateNotifier<List<Comment>> {
  CommentsData() : super([]);

  DocumentSnapshot? lastDocument;

  getData(String artilceId, WidgetRef ref) async {
    if (lastDocument == null) {
      await FirebaseService().getCommentsSnapshot(articleId: artilceId).then((QuerySnapshot snapshot) {
        state = snapshot.docs.map((e) => Comment.fromFirebase(e)).toList();
        lastDocument = snapshot.docs.last;
        ref.read(isCommentsLoadingProvider.notifier).update((state) => false);
      }).catchError((e) => _handleError(ref, e.toString()));
    } else {
      ref.read(hasCommentsProvider.notifier).update((state) => true);
      await FirebaseService().getCommentsSnapshot(articleId: artilceId, lastDocument: lastDocument).then((QuerySnapshot? snapshot) {
        state.addAll(snapshot!.docs.map((e) => Comment.fromFirebase(e)).toList());
        lastDocument = snapshot.docs.last;
        ref.read(hasCommentsProvider.notifier).update((state) => false);
      }).catchError((e) => _handleError(ref, e.toString()));
    }
  }

  _handleError(ref, String error) {
    ref.read(isCommentsLoadingProvider.notifier).update((state) => false);
    ref.read(hasCommentsProvider.notifier).update((state) => false);
    debugPrint(error);
  }
}
