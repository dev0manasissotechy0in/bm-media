import 'package:easy_localization/easy_localization.dart';
import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/article_tiles/article_tile3.dart';
import 'package:news_app/configs/app_assets.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/utils/empty_animation.dart';
import 'package:quiver/iterables.dart';
import '../components/loading_list_tile.dart';
import '../providers/user_data_provider.dart';
import '../services/firebase_service_stub.dart';

final bookmarksProvider = FutureProvider<List<Article>>((ref) async {
  final List<Article> articles = [];
  final user = ref.watch(userDataProvider)!;
  final articleIds = user.bookmarkedIds ?? [];
  final chunks = partition(articleIds, 10);

  final querySnapshots = await Future.wait(chunks.map((chunk) => FirebaseService().getArticlesQuery(chunk)).toList());
  for (var element in querySnapshots) {
    articles.addAll(element.docs.map((e) => Article.fromFirestore(e)).toList());
  }

  return articles;
});

class Bookmarks extends ConsumerWidget {
  const Bookmarks({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final user = ref.watch(userDataProvider);
    final bookmarks = ref.watch(bookmarksProvider);
    return Scaffold(
      backgroundColor: Theme.of(context).canvasColor,
      appBar: AppBar(
        title: const Text('bookmarks').tr(),
        titleTextStyle: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600),
        leading: IconButton(onPressed: () => Navigator.pop(context), icon: const Icon(FeatherIcons.chevronLeft)),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.refresh(bookmarksProvider),
        child: user == null || user.bookmarkedIds == null || user.bookmarkedIds!.isEmpty
            ? EmptyAnimation(animationString: emptyAnimation, title: 'no-articles'.tr())
            : bookmarks.when(
                skipLoadingOnRefresh: false,
                loading: () => const LoadingListTile(height: 160),
                error: (error, stackTrace) => Center(
                  child: Text(error.toString()),
                ),
                data: (data) {
                  return ListView.separated(
                    padding: const EdgeInsets.all(20),
                    itemCount: data.length,
                    separatorBuilder: (context, index) => const SizedBox(height: 20),
                    itemBuilder: (context, index) {
                      final Article article = data[index];
                      return ArticleTile3(article: article);
                    },
                  );
                },
              ),
      ),
    );
  }
}
