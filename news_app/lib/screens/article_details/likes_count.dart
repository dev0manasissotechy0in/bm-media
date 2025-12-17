import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/providers/app_settings_provider.dart';

// Temporarily disabled during migration
final likesStreamProvider = StateProvider.autoDispose.family<int, String>((ref, articleId) {
  return 0; // Return static value during migration
});

class LikesCount extends ConsumerWidget {
  final Article article;
  final Color? textColor;

  const LikesCount({super.key, required this.article, this.textColor});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.read(appSettingsProvider);
    if (settings?.likes == false) return const SizedBox.shrink();
    
    // During migration, show the article's current like count
    return _text(article.likes ?? 0);
  }

  Row _text(int count) {
    return Row(
      children: [
        Icon(Icons.favorite, size: 20, color: textColor ?? Colors.grey),
        const SizedBox(width: 3),
        Text(
          'count-likes',
          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w600, color: textColor ?? Colors.grey),
        ).tr(args: [count.toString()]),
      ],
    );
  }
}
