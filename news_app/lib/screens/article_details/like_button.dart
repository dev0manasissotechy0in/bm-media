import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import '../../models/user_model.dart';
import '../../providers/app_settings_provider.dart';
import '../../providers/user_data_provider.dart';
import '../../services/firebase_service_stub.dart';
import '../../utils/next_screen.dart';
import '../auth/login.dart';

final isLikedProvider = StateProvider.family.autoDispose<bool, Article>((ref, article) {
  final user = ref.watch(userDataProvider);
  if (user != null) {
    if (user.likedIds?.contains(article.id) ?? false) {
      return true;
    } else {
      return false;
    }
  } else {
    return false;
  }
});

final isLikesLoadingProvider = StateProvider.autoDispose((ref) => false);

class LikeButton extends ConsumerWidget {
  const LikeButton({super.key, required this.article, this.iconSize, this.iconColor});

  final Article article;
  final double? iconSize;
  final Color? iconColor;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.read(appSettingsProvider);
    if (settings?.likes == false) return const SizedBox.shrink();

    final UserModel? user = ref.watch(userDataProvider);
    final bool isLiked = ref.watch(isLikedProvider(article));
    final isLoading = ref.watch(isLikesLoadingProvider);

    return IconButton(
        onPressed: isLoading
            ? null
            : () async {
                if (user == null) {
                  NextScreen.openBottomSheet(context, const LoginScreen(popUpScreen: true));
                } else {
                  ref.read(isLikesLoadingProvider.notifier).update((state) => true);
                  if (isLiked) {
                    ref.read(isLikedProvider(article).notifier).update((state) => false);
                    await FirebaseService().updateLikesCount(article.id, false);
                  } else {
                    ref.read(isLikedProvider(article).notifier).update((state) => true);
                    await FirebaseService().updateLikesCount(article.id, true);
                  }
                  await FirebaseService().updateLikedIds(user, article);
                  ref.read(userDataProvider.notifier).getData();
                  ref.read(isLikesLoadingProvider.notifier).update((state) => false);
                }
              },
        icon: Icon(
          isLiked ? Icons.favorite : Icons.favorite_border,
          size: iconSize ?? 25,
          color: iconColor,
        ));
  }
}
