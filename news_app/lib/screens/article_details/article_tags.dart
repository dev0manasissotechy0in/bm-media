import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import '../../models/tag.dart';
import '../../providers/app_settings_provider.dart';
import '../../services/firebase_service_stub.dart';
import '../../utils/next_screen.dart';
import '../all_articles/articles_view.dart';

final articleTagsProvider = FutureProvider.family.autoDispose<List<Tag>, List<String>>((ref, tagIds) async {
  List<Tag> tags = await FirebaseService().getArticleTags(tagIds);
  return tags;
});

class ArticleTags extends ConsumerWidget {
  const ArticleTags({
    super.key,
    required this.article,
  });

  final Article article;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.read(appSettingsProvider);
    if (settings?.tags == false) return const SizedBox.shrink();

    final tagsRef = ref.watch(articleTagsProvider(article.tagIDs ?? []));
    return Visibility(
      visible: article.tagIDs!.isNotEmpty,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 20),
        child: tagsRef.when(
            loading: () => Container(),
            error: (error, stackTrace) => Container(),
            data: (tags) {
              return Wrap(
                spacing: 10,
                runSpacing: 8,
                children: tags.map((tag) {
                  return InkWell(
                    onTap: () => NextScreen.normal(
                      context,
                      AllArticlesView(
                        articlesBy: ArticlesBy.tag,
                        title: '#${tag.name}',
                        tagId: tag.id,
                      ),
                    ),
                    child: Chip(
                      labelStyle: Theme.of(context).textTheme.titleMedium,
                      labelPadding: const EdgeInsets.symmetric(horizontal: 15),
                      label: Text('#${tag.name}'),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
                      elevation: 0,
                    ),
                  );
                }).toList(),
              );
            }),
      ),
    );
  }
}
