import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/models/notification_model.dart';
import 'package:news_app/screens/article_details/layouts/details_view1.dart';
import 'package:news_app/services/api_service.dart';
import 'package:news_app/utils/loading_widget.dart';

final notifcationArticleProvider = FutureProvider.family.autoDispose<Article?, NotificationModel>((ref, notifcation) async {
  final Article? article = await ApiService().getArticleById(notifcation.articleId.toString());
  return article;
});

class ArticleNotificationDeatils extends ConsumerWidget {
  const ArticleNotificationDeatils({super.key, required this.notification});

  final NotificationModel notification;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final articleRef = ref.watch(notifcationArticleProvider(notification));
    return articleRef.when(
      data: (article) {
        if (article != null) {
          return ArticleDetailsView1(article: article);
        } else {
          return Scaffold(
            appBar: AppBar(),
            body: Center(child: const Text('no-articles').tr()),
          );
        }
      },
      error: (error, stackTrace) => Center(
        child: Scaffold(
          appBar: AppBar(),
          body: Center(child: const Text('no-articles').tr()),
        ),
      ),
      loading: () => Scaffold(
        appBar: AppBar(),
        body: const Center(
          child: LoadingIndicatorWidget(),
        ),
      ),
    );
  }
}
