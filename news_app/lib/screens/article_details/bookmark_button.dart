import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import '../../models/user_model.dart';
import '../../providers/user_data_provider.dart';
import '../../services/firebase_service_stub.dart';
import '../../utils/next_screen.dart';
import '../../utils/snackbars.dart';
import '../auth/login.dart';

final isBookamrkedProvider = StateProvider.family.autoDispose<bool, Article>((ref, article) {
  final user = ref.watch(userDataProvider);
  if (user != null) {
    if (user.bookmarkedIds?.contains(article.id) ?? false) {
      return true;
    } else {
      return false;
    }
  } else {
    return false;
  }
});

final isBookmarkLoadingProvider = StateProvider.autoDispose((ref) => false);

class BookmarkButton extends ConsumerWidget {
  const BookmarkButton({super.key, required this.article, this.iconSize, this.iconColor});

  final Article article;
  final double? iconSize;
  final Color? iconColor;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final UserModel? user = ref.watch(userDataProvider);
    final bool isBookmarked = ref.watch(isBookamrkedProvider(article));
    final isLoading = ref.watch(isBookmarkLoadingProvider);
    return IconButton(
        onPressed: isLoading
            ? null
            : () async {
                if (user == null) {
                  NextScreen.openBottomSheet(context, const LoginScreen(popUpScreen: true));
                } else {
                  ref.read(isBookmarkLoadingProvider.notifier).update((state) => true);
                  if (isBookmarked) {
                    ref.read(isBookamrkedProvider(article).notifier).update((state) => false);
                    openSnackbar(context, 'remove-bookmark'.tr());
                  } else {
                    ref.read(isBookamrkedProvider(article).notifier).update((state) => true);
                    openSnackbar(context, 'add-bookmark'.tr());
                  }
                  await FirebaseService().updateBookmark(user, article);
                  ref.read(userDataProvider.notifier).getData();
                  ref.read(isBookmarkLoadingProvider.notifier).update((state) => false);
                }
              },
        icon: Icon(
          isBookmarked ? Icons.bookmark : Icons.bookmark_border,
          size: iconSize ?? 25,
          color: iconColor,
        ));
  }
}
