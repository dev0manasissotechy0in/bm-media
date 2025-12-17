import 'package:easy_localization/easy_localization.dart';
import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/providers/app_settings_provider.dart';
import '../../models/article.dart';
import '../../utils/next_screen.dart';
import '../comments/comments.dart';

class CommentsButton extends StatelessWidget {
  const CommentsButton({
    super.key,
    required this.article,
    required this.ref,
  });

  final Article article;
  final WidgetRef ref;

  @override
  Widget build(BuildContext context) {
    final settings = ref.read(appSettingsProvider);
    final bool isEnbaled = settings?.comments == true && (article.isCommentsEnabled ?? true);
    if (!isEnbaled) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: TextButton.icon(
        style: TextButton.styleFrom(
          padding: const EdgeInsets.only(left: 15, right: 15),
          backgroundColor: Theme.of(context).primaryColor,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(25)),
          elevation: 0,
        ),
        icon: const Icon(FeatherIcons.messageCircle, color: Colors.white, size: 20),
        label: const Text('comments', style: TextStyle(color: Colors.white)).tr(),
        onPressed: () => NextScreen.openBottomSheet(context, Comments(article)),
      ),
    );
  }
}
