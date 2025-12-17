import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/user_avatar.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/providers/app_settings_provider.dart';
import 'package:news_app/screens/all_articles/articles_view.dart';
import 'package:news_app/utils/next_screen.dart';

class AuthorInfo extends ConsumerWidget {
  const AuthorInfo({super.key, required this.article});

  final Article article;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.read(appSettingsProvider);
    if (settings?.author == false) {
      return const SizedBox.shrink();
    } else {
      return InkWell(
        onTap: () => NextScreen.normal(
          context,
          AllArticlesView(
            articlesBy: ArticlesBy.author,
            title: article.author?.name ?? '',
            authorId: article.author!.id,
          ),
        ),
        child: Row(
          children: [
            UserAvatar(
              imageUrl: article.author?.imageUrl,
              radius: 25,
              iconSize: 14,
            ),
            const SizedBox(
              width: 5,
            ),
            Text(
              article.author?.name ?? "",
              style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      );
    }
  }
}
